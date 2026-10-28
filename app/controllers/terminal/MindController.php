<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Trading\Ledger;
use App\Trading\Mind;
use App\Trading\Wall;

/** Mind tab: Roast Mode, Death Clock, the Ghost, Boss Fight and the weekly Psych Art wallpaper (active account). */
final class MindController extends TerminalController
{
    public static function for(array $m, array $acc, string $tz): Mind
    {
        $trades = Ledger::forAnalytics((int) $m['id'], (int) $acc['id'], $tz, (new \DateTimeImmutable('-120 days'))->format('Y-m-d'));
        return new Mind($trades, (float) Ledger::balance($acc)['equity'], $tz, (int) $m['id'], (string) $acc['currency']);
    }

    public function index(Request $req): never
    {
        $mind = self::for($this->m, $this->acc, $this->tz);
        $level = (string) ($this->m['roast_level'] ?? 'savage');
        $week = max(-8, min(0, (int) $req->query('week', 0)));
        $this->render('mind', [
            'level' => array_key_exists($level, Mind::LEVELS) ? $level : 'savage',
            'roast' => $mind->roast($level === 'off' ? 'savage' : $level), 'clock' => $mind->deathClock(), 'ghost' => $mind->ghost(),
            'boss' => $mind->boss(), 'art' => $mind->art($week),
        ], 'Mind', 'mind');
    }

    public function level(Request $req): never
    {
        $lvl = (string) $req->post('level', 'savage');
        if (!array_key_exists($lvl, Mind::LEVELS)) {
            Response::json(['ok' => false, 'error' => 'Unknown level.'], 422);
        }
        Database::update('user_settings', ['roast_level' => $lvl], 'user_id = :u', ['u' => $this->uid]);
        Response::json(['ok' => true, 'level' => $lvl]);
    }

    /** Wall of L's: submit an anonymised loss (optionally prefilled from one of the member's losing trades). */
    public function wall(Request $req): never
    {
        $pre = [];
        if ($id = (int) $req->query('trade', 0)) {
            $t = Ledger::trade($this->uid, $id);
            if ($t && (float) $t['pnl'] < 0) {
                $r = $t['risk_amount'] ? abs((float) $t['pnl']) / abs((float) $t['risk_amount']) : null;
                $pre = ['instrument' => $t['symbol'], 'mistake' => $t['mistake_tag'] !== 'NONE' ? $t['mistake_tag'] : 'IGNORED_PLAN',
                    'loss_text' => $r ? '−' . number_format($r, 1) . 'R' : money((float) $t['pnl'], $this->acc['currency'], true)];
            }
        }
        $mine = Database::all('SELECT id, loss_text, mistake, status, created_at FROM wall_posts WHERE user_id = :u ORDER BY id DESC LIMIT 10', ['u' => $this->uid]);
        $this->render('wall', ['pre' => $pre, 'mine' => $mine], "Wall of L's", 'mind');
    }

    public function wallSubmit(Request $req): never
    {
        $err = Wall::submit($this->uid, $_POST);
        if ($err) {
            $this->back('/terminal/wall', 'error', $err);
        }
        $this->back('/terminal/wall', 'success', 'Submitted. The team reviews every L before it goes on the wall; you\'ll see the status below.');
    }
}
