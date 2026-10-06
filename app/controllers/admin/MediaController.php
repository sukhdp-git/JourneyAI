<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Media;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;

/** Media library: upload, browse/search, alt text, copy URL, delete, and the JSON picker used by image fields. */
final class MediaController extends AdminController
{
    public function __construct()
    {
        Auth::authorize('media');
    }

    private function query(Request $req, int $per): array
    {
        $q = mb_substr(trim((string) $req->query('q', '')), 0, 100);
        $where = '1=1';
        $p = [];
        if ($q !== '') {
            $where = '(original_name LIKE :a OR alt_text LIKE :b OR filename LIKE :c)';
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $p = ['a' => $like, 'b' => $like, 'c' => $like];
        }
        $total = (int) Database::value("SELECT COUNT(*) FROM media WHERE $where", $p);
        $pg = new Paginator($total, $per, $req->int('page', 1));
        $rows = Database::all("SELECT m.*, a.name AS uploader FROM media m LEFT JOIN admins a ON a.id = m.uploaded_by WHERE $where ORDER BY m.created_at DESC, m.id DESC LIMIT :lim OFFSET :off", $p + ['lim' => $per, 'off' => $pg->offset]);
        return [$rows, $pg, $q];
    }

    public function index(Request $req): never
    {
        [$rows, $pg, $q] = $this->query($req, 30);
        $usage = (int) Database::value('SELECT COALESCE(SUM(size), 0) FROM media');
        $this->render('media/index', ['rows' => $rows, 'pager' => $pg, 'q' => $q, 'usage' => $usage], 'Media library', [['Media', null], ['Library', null]]);
    }

    /** JSON for the media picker modal. */
    public function browse(Request $req): never
    {
        [$rows, $pg] = $this->query($req, 24);
        Response::json(['ok' => true, 'page' => $pg->page, 'pages' => $pg->pages, 'items' => array_map(fn ($m) => [
            'id' => (int) $m['id'], 'path' => $m['path'], 'url' => media_url($m['path']), 'thumb' => media_medium($m['path']),
            'name' => $m['original_name'], 'alt' => $m['alt_text'], 'size' => human_size((int) $m['size']), 'dims' => $m['width'] ? $m['width'] . '×' . $m['height'] : '',
        ], $rows)]);
    }

    public function upload(Request $req): never
    {
        $files = $_FILES['files'] ?? $_FILES['file'] ?? null;
        $list = [];
        if ($files && is_array($files['name'])) {
            foreach ($files['name'] as $i => $n) {
                $list[] = ['name' => $n, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
            }
        } elseif ($files) {
            $list[] = $files;
        }
        if (!$list) {
            $this->respond($req, false, 'Choose at least one image (the file may exceed the server upload limit).');
        }
        $saved = [];
        $errors = [];
        foreach (array_slice($list, 0, 20) as $f) {
            try {
                $m = Media::upload($f);
                $saved[] = ['id' => (int) $m['id'], 'path' => $m['path'], 'url' => media_url($m['path']), 'thumb' => media_medium($m['path']), 'name' => $m['original_name']];
                Activity::log('upload', 'media', (int) $m['id'], 'Uploaded ' . $m['original_name']);
            } catch (\RuntimeException $e) {
                $errors[] = basename((string) $f['name']) . ': ' . $e->getMessage();
            }
        }
        $msg = $saved ? count($saved) . ' file' . (count($saved) === 1 ? '' : 's') . ' uploaded.' : 'Nothing was uploaded.';
        if ($errors) {
            $msg .= ' ' . implode(' ', $errors);
        }
        $this->respond($req, (bool) $saved, $msg, ['items' => $saved, 'errors' => $errors]);
    }

    public function update(Request $req): never
    {
        $m = Database::one('SELECT id FROM media WHERE id = :id', ['id' => (int) $req->params['id']]) ?? Response::abort(404);
        $alt = mb_substr(trim((string) $req->post('alt_text')), 0, 255);
        Database::update('media', ['alt_text' => $alt], 'id = :id', ['id' => $m['id']]);
        Activity::log('update', 'media', (int) $m['id'], 'Updated alt text');
        $this->respond($req, true, 'Alt text saved.');
    }

    public function delete(Request $req): never
    {
        $id = (int) $req->params['id'];
        $m = Database::one('SELECT original_name, path FROM media WHERE id = :id', ['id' => $id]) ?? Response::abort(404);
        Media::delete($id);
        Activity::log('delete', 'media', $id, 'Deleted ' . $m['original_name']);
        $this->respond($req, true, 'File deleted. Any page still using it will show no image.');
    }

    private function respond(Request $req, bool $ok, string $message, array $extra = []): never
    {
        if ($req->isAjax()) {
            Response::json(['ok' => $ok, 'message' => $message] + $extra, $ok ? 200 : 422);
        }
        $this->back('/' . ADMIN_PREFIX . '/media', $ok ? 'success' : 'error', $message);
    }
}
