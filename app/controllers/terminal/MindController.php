<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Trading\Ledger;
use App\Trading\Mind;

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
}
