<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Media;
use App\Core\Request;
use App\Core\Response;
use App\Core\Settings;
use App\Core\Validator;

/** Website settings screens (General, Contact, Header, Footer, Social, WhatsApp, SEO, Analytics, Appearance…). */
final class SettingsController extends AdminController
{
    public static function screens(): array
    {
        static $s = null;
        return $s ??= require __DIR__ . '/settings.php';
    }

    private function screen(string $slug): array
    {
        $s = self::screens()[$slug] ?? Response::abort(404);
        Auth::authorize($s[1]);
        return $s;
    }

    public function show(Request $req, string $slug): never
    {
        [$title, , $icon, $intro, $fields] = $this->screen($slug);
        $values = [];
        foreach ($fields as $f) {
            $values[$f['name']] = Settings::get($f['name']);
        }
        $group = str_contains($slug, '/') ? ['Appearance', null] : ['Website', null];
        $this->render('settings/form', ['slug' => $slug, 'title' => $title, 'icon' => $icon, 'intro' => $intro, 'fields' => $fields, 'values' => $values], $title, [$group, [$title, null]]);
    }

    public function save(Request $req, string $slug): never
    {
        [$title, , , , $fields] = $this->screen($slug);
        $data = [];
        $rules = [];
        $labels = [];
        $errors = [];
        foreach ($fields as $f) {
            $raw = $_POST[$f['name']] ?? '';
            $v = is_string($raw) ? trim($raw) : '';
            switch ($f['type']) {
                case 'checkbox':
                    $v = $v === '1' ? '1' : '0';
                    break;
                case 'image':
                    if (!Media::isValidPath($v)) {
                        $errors[$f['name']] = 'Choose an image from the media library.';
                    }
                    break;
                case 'select':
                    if ($v !== '' && !array_key_exists($v, $f['options'])) {
                        $errors[$f['name']] = 'Choose a valid option.';
                    }
                    break;
                case 'link':
                    if ($v !== '' && (!preg_match('#^(/|https?://|mailto:|tel:|\#)#i', $v) || preg_match('/[\s<>"]/', $v))) {
                        $errors[$f['name']] = 'Links must start with /, https://, mailto:, tel: or #.';
                    }
                    break;
                case 'color':
                    $v = strtolower($v);
                    break;
                case 'code':
                    $v = is_string($raw) ? str_replace("\r\n", "\n", $raw) : '';
                    break;
            }
            if ($f['name'] === 'map_embed_url' && $v !== '') {
                if (preg_match('/src="([^"]+)"/', $v, $m)) {
                    $v = html_entity_decode($m[1]);
                }
                if (!preg_match('#^https://(www\.)?google\.[a-z.]+/maps/embed\?[^\s"<>]+$#i', $v)) {
                    $errors[$f['name']] = 'Paste the Google Maps embed address (https://www.google.com/maps/embed?…).';
                }
            }
            if (in_array($f['name'], ['ga_id', 'gtm_id', 'pixel_id'], true) && $v !== '') {
                $pattern = ['ga_id' => '/^(G|UA|AW)-[A-Z0-9\-]+$/', 'gtm_id' => '/^GTM-[A-Z0-9]+$/', 'pixel_id' => '/^\d{5,25}$/'][$f['name']];
                $v = strtoupper($v);
                if (!preg_match($pattern, $v)) {
                    $errors[$f['name']] = 'This ID does not look valid.';
                }
            }
            if ($f['name'] === 'gsc_code' && $v !== '') {
                if (preg_match('/content="([^"]+)"/', $v, $m)) {
                    $v = $m[1];
                }
                if (!preg_match('/^[A-Za-z0-9_\-]{10,120}$/', $v)) {
                    $errors[$f['name']] = 'Paste the verification code (the content value).';
                }
            }
            $data[$f['name']] = $v;
            if (!empty($f['rules'])) {
                $rules[$f['name']] = $f['rules'];
                $labels[$f['name']] = $f['label'];
            }
        }
        $val = new Validator($data, $rules, $labels);
        $errors += $val->errors;
        if ($errors) {
            $this->fail($errors, '/' . ADMIN_PREFIX . '/' . $this->url($slug));
        }
        Settings::save($data, explode('/', $slug)[0]);
        Activity::log('update', 'settings', null, 'Updated settings: ' . $title . ' (' . implode(', ', array_slice(array_keys($data), 0, 12)) . (count($data) > 12 ? '…' : '') . ')');
        $this->back('/' . ADMIN_PREFIX . '/' . $this->url($slug), 'success', $title . ' saved. Changes are live on the website.');
    }

    /** Public URL of a settings screen (e.g. general → settings, seo → seo). */
    public static function url(string $slug): string
    {
        return $slug === 'general' ? 'settings' : $slug;
    }
}
