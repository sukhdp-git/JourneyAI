<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Learn;
use App\Trading\Course;

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

    /** Trading Foundations course overview. */
    public function course(Request $req): never
    {
        $p = Course::progress($this->uid);
        $this->render('course/index', ['progress' => $p, 'standing' => Course::standing($p), 'cert' => Course::certificate($this->uid)], Course::TITLE, 'university');
    }

    /** Saves a passed (or un-marked) lesson. JSON: {ok, done[], standing}. */
    public function progress(Request $req): never
    {
        $n = $req->int('lesson');
        if ($req->str('action', 10) === 'undo') {
            Course::uncomplete($this->uid, $n);
        } elseif ($e = Course::complete($this->uid, $n, $req->int('score'))) {
            Response::json(['ok' => false, 'error' => $e], 422);
        }
        $p = Course::progress($this->uid);
        Response::json(['ok' => true, 'done' => array_keys($p), 'standing' => Course::standing($p)]);
    }

    /** Certificate of completion: claim form until issued, then the certificate itself. */
    public function certificate(Request $req): never
    {
        $p = Course::progress($this->uid);
        $this->render('course/certificate', ['standing' => Course::standing($p), 'cert' => Course::certificate($this->uid), 'progress' => $p], 'Certificate · ' . Course::TITLE, 'university');
    }

    public function claim(Request $req): never
    {
        [$e, $c] = Course::issue($this->uid, $req->str('name', 120));
        if ($e) {
            $this->back(url('/terminal/university/certificate'), 'error', $e);
        }
        $this->back(url('/terminal/university/certificate'), 'success', 'Congratulations! Your certificate is ready. 🎓');
    }

    /** One course module (views/terminal/course/m{n}.php). */
    public function module(Request $req): never
    {
        $n = Course::find((string) ($req->params['slug'] ?? ''));
        if ($n === null) {
            Response::abort(404);
        }
        $p = Course::progress($this->uid);
        $this->render('course/module', ['n' => $n, 'mod' => Course::MODULES[$n], 'progress' => $p, 'standing' => Course::standing($p)], 'Lesson ' . Course::position($n) . ' · ' . Course::MODULES[$n][2], 'university');
    }

    /** Names of the member's own strategies (to show which playbook strategies were already copied). */
    private function mine(): array
    {
        return array_column(Database::all('SELECT name FROM strategies WHERE user_id = :u', ['u' => $this->uid]), 'name');
    }
}
