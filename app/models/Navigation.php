<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Navigation
{
    public const LOCATIONS = ['header' => 'Header menu', 'footer_1' => 'Footer column 1', 'footer_2' => 'Footer column 2', 'footer_3' => 'Footer column 3'];

    /** Enabled menu items for a location, nested one level (dropdowns). */
    public static function tree(string $location): array
    {
        static $cache = [];
        if (isset($cache[$location])) {
            return $cache[$location];
        }
        $rows = Database::all('SELECT id, parent_id, label, url, target FROM navigation WHERE location = :l AND is_enabled = 1 ORDER BY sort_order, id', ['l' => $location]);
        $top = [];
        $children = [];
        foreach ($rows as $r) {
            $r['children'] = [];
            if ($r['parent_id']) {
                $children[$r['parent_id']][] = $r;
            } else {
                $top[$r['id']] = $r;
            }
        }
        foreach ($children as $pid => $list) {
            if (isset($top[$pid])) {
                $top[$pid]['children'] = $list;
            }
        }
        return $cache[$location] = array_values($top);
    }
}
