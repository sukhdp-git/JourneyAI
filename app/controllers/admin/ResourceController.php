<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Database;
use App\Core\HtmlSanitizer;
use App\Core\Media;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;

/**
 * Generic CRUD engine for Control Panel modules defined in resources.php:
 * list (search, filter, sort, paginate), create, edit, delete, enable/disable toggle and drag-and-drop reorder.
 */
final class ResourceController extends AdminController
{
    private static ?array $defs = null;
    private array $d;

    public function __construct(private readonly string $key)
    {
        self::$defs ??= require __DIR__ . '/resources.php';
        $this->d = self::$defs[$key] ?? throw new \RuntimeException("Unknown resource $key");
        Auth::authorize($this->d['perm']);
    }

    public static function keys(): array
    {
        self::$defs ??= require __DIR__ . '/resources.php';
        return array_keys(self::$defs);
    }

    public static function definition(string $key): array
    {
        self::$defs ??= require __DIR__ . '/resources.php';
        return self::$defs[$key];
    }

    private function fields(?array $row): array
    {
        $f = $this->d['fields'];
        return is_callable($f) ? $f($row) : $f;
    }

    private function crumbs(?string $last = null): array
    {
        $c = [];
        if (!empty($this->d['parent'])) {
            $c[] = [$this->d['parent'][0], $this->d['parent'][1]];
        }
        $c[] = [$this->d['label'], $last === null ? null : $this->key];
        if ($last !== null) {
            $c[] = [$last, null];
        }
        return $c;
    }

    private function find(int $id): array
    {
        $row = Database::one("SELECT * FROM `{$this->d['table']}` WHERE id = :id", ['id' => $id]);
        if (!$row) {
            Response::abort(404);
        }
        return $row;
    }

    // ---------------------------------------------------------------- list
    public function index(Request $req): never
    {
        $t = $this->d['table'];
        $where = ['1=1'];
        $params = [];
        $q = mb_substr(trim((string) $req->query('q', '')), 0, 100);
        if ($q !== '' && !empty($this->d['search'])) {
            $or = [];
            foreach ($this->d['search'] as $i => $col) {
                $or[] = "t.`$col` LIKE :q$i";
                $params["q$i"] = '%' . addcslashes($q, '%_\\') . '%';
            }
            $where[] = '(' . implode(' OR ', $or) . ')';
        }
        $filters = [];
        foreach ($this->d['filters'] ?? [] as $col => $f) {
            $opts = is_callable($f['options']) ? $f['options']() : $f['options'];
            $v = (string) $req->query($col, (string) ($this->d['default_filter'][$col] ?? ''));
            $filters[$col] = ['label' => $f['label'], 'options' => $opts, 'value' => '', 'required' => !empty($f['required'])];
            if ($v !== '' && array_key_exists($v, $opts)) {
                $where[] = "t.`$col` = :f_$col";
                $params["f_$col"] = $v;
                $filters[$col]['value'] = $v;
            }
        }
        $sortable = $this->d['sortable'] ?? [];
        $sort = (string) $req->query('sort', '');
        $dir = strtolower((string) $req->query('dir', 'asc')) === 'desc' ? 'DESC' : 'ASC';
        $order = isset($sortable[$sort]) ? "t.`$sort` $dir, t.id DESC" : $this->d['order'];
        $w = implode(' AND ', $where);
        $total = (int) Database::value("SELECT COUNT(*) FROM `$t` t WHERE $w", $params);
        $per = $this->d['per_page'] ?? 20;
        $pg = new Paginator($total, $per, max(1, $req->int('page', 1)));
        $extra = !empty($this->d['select_extra']) ? ', ' . $this->d['select_extra'] : '';
        $rows = Database::all("SELECT t.*$extra FROM `$t` t WHERE $w ORDER BY $order LIMIT :lim OFFSET :off", $params + ['lim' => $per, 'off' => $pg->offset]);
        foreach ($rows as &$r) {
            unset($r['password_hash']);
            $r['_view'] = isset($this->d['view']) ? ($this->d['view'])($r) : null;
        }
        unset($r);
        $canReorder = !empty($this->d['reorder']) && $q === '' && !isset($sortable[$sort]) && $pg->pages === 1;
        $this->render('resource/index', [
            'key' => $this->key, 'd' => $this->d, 'rows' => $rows, 'pager' => $pg, 'q' => $q, 'filters' => $filters,
            'sort' => $sort, 'dir' => $dir, 'sortable' => $sortable, 'canReorder' => $canReorder,
        ], $this->d['label'], $this->crumbs());
    }

    // ---------------------------------------------------------------- forms
    public function create(Request $req): never
    {
        if (($this->d['can_create'] ?? true) === false) {
            Response::abort(404);
        }
        $fields = $this->fields(null);
        $values = [];
        foreach ($fields as $f) {
            $values[$f['name']] = $f['default'] ?? ($f['type'] === 'select' && empty($f['nullable']) ? array_key_first($this->options($f)) : ($f['type'] === 'repeater' ? [] : ''));
        }
        foreach ($this->d['filters'] ?? [] as $col => $f) {
            if ($req->query($col) !== null) {
                $values[$col] = (string) $req->query($col);
            }
        }
        $this->render('resource/form', ['key' => $this->key, 'd' => $this->d, 'fields' => $fields, 'values' => $values, 'row' => null], 'New ' . strtolower($this->d['singular']), $this->crumbs('New'));
    }

    public function edit(Request $req): never
    {
        $row = $this->find((int) $req->params['id']);
        $fields = $this->fields($row);
        $values = $row;
        $opts = json_list($row['options'] ?? null);
        foreach ($fields as $f) {
            if (str_starts_with($f['name'], 'opt_')) {
                $values[$f['name']] = $opts[substr($f['name'], 4)] ?? '';
            } elseif ($f['type'] === 'repeater') {
                $values[$f['name']] = json_list($row[$f['name']] ?? null);
            } elseif ($f['type'] === 'datetime') {
                $values[$f['name']] = utc_to_local_input($row[$f['name']] ?? null);
            } elseif ($f['type'] === 'permissions') {
                $values[$f['name']] = json_list($row[$f['name']] ?? null);
            } elseif ($f['type'] === 'password') {
                $values[$f['name']] = '';
            }
        }
        unset($values['password_hash']);
        if (isset($this->d['load'])) {
            $values = ($this->d['load'])($values);
        }
        $title = $row['title'] ?? $row['name'] ?? $row['label'] ?? $row['question'] ?? ('#' . $row['id']);
        $this->render('resource/form', [
            'key' => $this->key, 'd' => $this->d, 'fields' => $fields, 'values' => $values, 'row' => $row,
            'viewUrl' => isset($this->d['view']) ? ($this->d['view'])($row) : null,
        ], 'Edit ' . strtolower($this->d['singular']), $this->crumbs(mb_strimwidth((string) $title, 0, 50, '…')));
    }

    // ---------------------------------------------------------------- save
    public function store(Request $req): never
    {
        if (($this->d['can_create'] ?? true) === false) {
            Response::abort(404);
        }
        $this->save(null);
    }

    public function update(Request $req): never
    {
        $this->save($this->find((int) $req->params['id']));
    }

    private function save(?array $row): never
    {
        $fields = $this->fields($row);
        $data = [];
        $errors = [];
        $rules = [];
        $labels = [];
        $options = json_list($row['options'] ?? null);
        $hasOptions = false;
        $back = $row ? "{$this->key}/{$row['id']}/edit" : "{$this->key}/new";

        foreach ($fields as $f) {
            $name = $f['name'];
            if (!empty($f['virtual']) || in_array($f['type'], ['tags', 'password'], true)) {
                continue;
            }
            if ($row && !empty($f['lock_if']) && !empty($row[$f['lock_if']])) {
                continue;
            }
            $raw = $_POST[$name] ?? null;
            [$value, $err] = $this->collect($f, $raw, $data);
            if ($err) {
                $errors[$name] = $err;
            }
            if (!empty($f['rules'])) {
                $rules[$name] = $f['rules'];
                $labels[$name] = $f['label'];
            }
            if (str_starts_with($name, 'opt_')) {
                $options[substr($name, 4)] = (string) $value;
                $hasOptions = true;
            } else {
                $data[$name] = $value;
            }
        }
        $v = new Validator(array_map(fn ($x) => is_array($x) ? json_encode($x) : $x, $data + array_combine(array_map(fn ($k) => "opt_$k", array_keys($options)), $options)), $rules, $labels);
        $errors += $v->errors;

        foreach ($fields as $f) {
            if ($f['type'] === 'slug' && isset($data[$f['name']]) && !isset($errors[$f['name']])) {
                $slug = $data[$f['name']];
                if (in_array($slug, $f['reserved'] ?? [], true)) {
                    $errors[$f['name']] = 'This URL is reserved by the system. Choose another.';
                }
            }
            if (!empty($f['unique']) && isset($data[$f['name']]) && !isset($errors[$f['name']])) {
                $exists = Database::value("SELECT id FROM `{$this->d['table']}` WHERE `{$f['name']}` = :v" . ($row ? ' AND id <> :id' : ''), ['v' => $data[$f['name']]] + ($row ? ['id' => (int) $row['id']] : []));
                if ($exists) {
                    $errors[$f['name']] = 'This value is already used by another item.';
                }
            }
        }
        if (isset($this->d['validate'])) {
            $errors += ($this->d['validate'])($data, $row);
        }
        if ($errors) {
            $this->fail($errors, '/' . ADMIN_PREFIX . '/' . $back);
        }
        if ($hasOptions) {
            $data['options'] = json_encode($options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        if (isset($this->d['before_save'])) {
            $data = ($this->d['before_save'])($data, $row);
        }
        foreach ($data as $k => $val) {
            if (is_array($val)) {
                $data[$k] = json_encode(array_values($val), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }
        $id = Database::transaction(function () use ($data, $row) {
            if ($row) {
                if ($data) {
                    Database::update($this->d['table'], $data, 'id = :id', ['id' => (int) $row['id']]);
                }
                $id = (int) $row['id'];
            } else {
                if (!empty($this->d['reorder']) && !isset($data['sort_order'])) {
                    $data['sort_order'] = (int) Database::value("SELECT COALESCE(MAX(sort_order), 0) + 10 FROM `{$this->d['table']}`");
                }
                $id = Database::insert($this->d['table'], $data);
            }
            if (isset($this->d['after_save'])) {
                ($this->d['after_save'])($id, $_POST);
            }
            return $id;
        });
        Activity::log($row ? 'update' : 'create', $this->key, $id, ($row ? 'Updated ' : 'Created ') . strtolower($this->d['singular']) . ': ' . mb_substr((string) ($data['title'] ?? $data['name'] ?? $data['label'] ?? $data['question'] ?? $data['subject'] ?? ''), 0, 120));
        Session::flash('success', $this->d['singular'] . ($row ? ' saved.' : ' created.'));
        Response::redirect('/' . ADMIN_PREFIX . "/{$this->key}/$id/edit");
    }

    /** @return array{0:mixed,1:?string} [value, error] */
    private function collect(array $f, mixed $raw, array $siblings): array
    {
        $s = is_string($raw) ? trim($raw) : '';
        switch ($f['type']) {
            case 'richtext':
                return [HtmlSanitizer::clean(is_string($raw) ? $raw : ''), null];
            case 'slug':
                $src = (string) ($_POST[$f['source'] ?? ''] ?? '');
                $slug = $s !== '' ? slugify($s, 150) : ($src !== '' ? slugify($src, 150) : '');
                return [$slug, null];
            case 'image':
                return Media::isValidPath($s) ? [$s, null] : ['', 'Choose an image from the media library.'];
            case 'select':
                $opts = $this->options($f);
                if ($s === '' && !empty($f['nullable'])) {
                    return [null, null];
                }
                return array_key_exists($s, $opts) ? [$s, null] : ['', 'Choose a valid option.'];
            case 'checkbox':
                return [in_array($s, ['1', 'on'], true) ? 1 : 0, null];
            case 'number':
                return [$s === '' ? '' : (string) (int) $s, ($s !== '' && !is_numeric($s)) ? 'Enter a number.' : null];
            case 'datetime':
                if ($s === '') {
                    return [null, null];
                }
                $utc = local_to_utc($s);
                return [$utc, $utc ? null : 'Enter a valid date and time.'];
            case 'color':
                return preg_match('/^#[0-9a-fA-F]{6}$/', $s) ? [strtolower($s), null] : ['', 'Choose a colour.'];
            case 'icon':
                return [$s === '' || in_array($s, icon_names(), true) ? $s : '', null];
            case 'link':
                if ($s !== '' && (!preg_match('#^(/|https?://|mailto:|tel:|\#)#i', $s) || preg_match('/[\s<>"]/', $s))) {
                    return [$s, 'Links must start with /, https://, mailto:, tel: or #.'];
                }
                return [$s, null];
            case 'email':
                return [strtolower($s), null];
            case 'permissions':
                $keys = is_array($raw) ? array_values(array_intersect(array_map('strval', $raw), Auth::allPermissionKeys())) : [];
                return [$keys, null];
            case 'repeater':
                $rows = [];
                foreach (is_array($raw) ? $raw : [] as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $clean = [];
                    $filled = false;
                    foreach ($f['subfields'] as $sf) {
                        [$val, $err] = $this->collect($sf, $item[$sf['name']] ?? null, []);
                        if ($err) {
                            return [[], $sf['label'] . ': ' . $err];
                        }
                        $clean[$sf['name']] = is_string($val) ? mb_substr($val, 0, $sf['type'] === 'richtext' ? 100000 : 5000) : $val;
                        if ($val !== '' && $val !== null && $val !== 0 && !($sf['type'] === 'select')) {
                            $filled = true;
                        }
                    }
                    if ($filled) {
                        $rows[] = $clean;
                    }
                }
                return [array_slice($rows, 0, 50), null];
            default:
                return [is_string($raw) ? trim($raw) : '', null];
        }
    }

    private function options(array $f): array
    {
        $o = $f['options'] ?? [];
        return array_map('strval', is_callable($o) ? $o() : $o);
    }

    // ---------------------------------------------------------------- actions
    public function delete(Request $req): never
    {
        if (($this->d['can_delete'] ?? true) === false) {
            Response::abort(404);
        }
        $row = $this->find((int) $req->params['id']);
        if (isset($this->d['protect']) && ($msg = ($this->d['protect'])($row))) {
            $this->back('/' . ADMIN_PREFIX . '/' . $this->key, 'error', $msg);
        }
        Database::delete($this->d['table'], 'id = :id', ['id' => (int) $row['id']]);
        Activity::log('delete', $this->key, (int) $row['id'], 'Deleted ' . strtolower($this->d['singular']) . ': ' . mb_substr((string) ($row['title'] ?? $row['name'] ?? $row['label'] ?? $row['question'] ?? ''), 0, 120));
        $this->back('/' . ADMIN_PREFIX . '/' . $this->key, 'success', $this->d['singular'] . ' deleted.');
    }

    public function toggle(Request $req): never
    {
        $t = $this->d['toggle'] ?? Response::abort(404);
        $row = $this->find((int) $req->params['id']);
        $new = (string) $row[$t['field']] === (string) $t['on'] ? $t['off'] : $t['on'];
        Database::update($this->d['table'], [$t['field'] => $new], 'id = :id', ['id' => (int) $row['id']]);
        if ($this->d['table'] === 'blog_posts' && $new === 'published' && !$row['published_at']) {
            Database::update('blog_posts', ['published_at' => gmdate('Y-m-d H:i:s')], 'id = :id', ['id' => (int) $row['id']]);
        }
        $on = (string) $new === (string) $t['on'];
        Activity::log($on ? 'enable' : 'disable', $this->key, (int) $row['id'], ($on ? 'Enabled/published ' : 'Disabled/unpublished ') . strtolower($this->d['singular']));
        if ($req->isAjax()) {
            Response::json(['ok' => true, 'on' => $on, 'message' => $this->d['singular'] . ($on ? ' enabled.' : ' disabled.')]);
        }
        $this->back('/' . ADMIN_PREFIX . '/' . $this->key, 'success', $this->d['singular'] . ($on ? ' enabled.' : ' disabled.'));
    }

    public function reorder(Request $req): never
    {
        if (empty($this->d['reorder'])) {
            Response::abort(404);
        }
        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids) || count($ids) > 500) {
            Response::json(['ok' => false, 'error' => 'Invalid order.'], 422);
        }
        Database::transaction(function () use ($ids) {
            foreach (array_values($ids) as $i => $id) {
                if (ctype_digit((string) $id)) {
                    Database::update($this->d['table'], ['sort_order' => ($i + 1) * 10], 'id = :id', ['id' => (int) $id]);
                }
            }
        });
        Activity::log('reorder', $this->key, null, 'Reordered ' . strtolower($this->d['label']));
        Response::json(['ok' => true, 'message' => 'Order saved.']);
    }
}
