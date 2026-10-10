<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Learn;

/** journzey.ai University: every playbook strategy explained, for signed-in members. */
final class UniversityController extends TerminalController
{
    public function index(Request $req): never
    {
        $this->render('university', ['strategies' => Learn::all(), 'mine' => $this->mine()], 'University', 'university');
    }

    public function show(Request $req): never
    {
        $all = Learn::all();
        $pos = null;
        foreach ($all as $i => $s) {
            if ($s['slug'] === ($req->params['slug'] ?? '')) {
                $pos = $i;
            }
        }
        if ($pos === null) {
            Response::abort(404);
        }
        $s = $all[$pos];
        $this->render('university-show', [
            's' => $s, 'number' => $pos + 1, 'total' => count($all),
            'prev' => $all[$pos - 1] ?? null, 'next' => $all[$pos + 1] ?? null,
            'owned' => in_array(mb_substr($s['title'], 0, 80), $this->mine(), true),
        ], $s['short_title'] ?: $s['title'], 'university');
    }

    /** Names of the member's own strategies (to show which playbook strategies were already copied). */
    private function mine(): array
    {
        return array_column(Database::all('SELECT name FROM strategies WHERE user_id = :u', ['u' => $this->uid]), 'name');
    }
}
