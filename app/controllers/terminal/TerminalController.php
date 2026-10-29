<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Trading\Ledger;
use App\Trading\RiskLimits;
use App\Trading\Members;

/**
 * Base for every /app page. Requires a signed-in, onboarded member; resolves the active trading account.
 * All data access below uses $this->uid (from the session) — never an id supplied by the browser alone.
 */
abstract class TerminalController
{
    protected array $m;
    protected int $uid;
    protected array $acc;
    protected string $tz;

    public function __construct()
    {
        $req = new Request();
        $m = Members::current();
        if (!$m) {
            if ($req->isAjax()) {
                Response::json(['ok' => false, 'error' => 'Your session has expired. Please sign in again.'], 401);
            }
            Response::redirect('/login?next=' . rawurlencode($req->path));
        }
        if (!(int) $m['onboarded']) {
            Response::redirect('/onboarding');
        }
        if ($req->method === 'POST') {
            Csrf::verify($req);
        }
        $this->m = $m;
        $this->uid = (int) $m['id'];
        $this->tz = $m['timezone'] ?: 'UTC';
        $GLOBALS['__lang'] = $m['language'] ?: 'en';
        $GLOBALS['__tz'] = $this->tz;
        $this->acc = Ledger::active($m);
        // A demo journal is anchored to the day it was loaded; keep it current so every feature has recent examples.
        if (!empty($this->acc['has_demo_data']) && Ledger::refreshDemoIfStale($m, $this->acc)) {
            $this->acc = Ledger::active($m);
        }
    }

    protected function render(string $view, array $data, string $title, string $nav): never
    {
        $GLOBALS['__errors'] = Session::takeErrors();
        $GLOBALS['__old'] = Session::takeOld();
        $balance = Ledger::balance($this->acc);
        View::show('terminal/' . $view, $data + [
            'm' => $this->m, 'acc' => $this->acc, 'accounts' => Ledger::accounts($this->uid), 'balance' => $balance,
            'limits' => RiskLimits::status($this->m, $this->acc, $balance, $this->tz),
            'capitalHistory' => Database::all('SELECT type, amount, note, occurred_at FROM capital_transactions WHERE user_id = :u AND account_id = :a ORDER BY occurred_at DESC, id DESC LIMIT 5', ['u' => $this->uid, 'a' => $this->acc['id']]),
            'flash' => Session::takeFlash(), 'pageTitle' => $title, 'nav' => $nav, 'tz' => $this->tz,
            'brief' => $this->brief($nav),
        ], 'terminal/layout');
    }

    /** Weekly do's & don'ts: auto-shown on Monday/Tuesday mornings; on Home it is always available behind a button. */
    private function brief(string $nav): ?array
    {
        $auto = \App\Trading\WeeklyBrief::isBriefTime($this->tz) || (new Request())->query('brief') === '1';
        if (!$auto && $nav !== 'home') {
            return null;
        }
        $key = $this->acc['id'] . ':' . (new \DateTimeImmutable('now', new \DateTimeZone($this->tz)))->format('Y-m-d');
        $data = $_SESSION['jz_brief'][$key] ?? null;
        if (!$data) {
            $data = \App\Trading\WeeklyBrief::build($this->m, $this->acc, $this->tz);
            $_SESSION['jz_brief'] = [$key => $data];
        }
        return $data + ['auto' => $auto, 'name' => trim(explode(' ', trim((string) $this->m['name']))[0] ?? '')];
    }

    /** Blocks writes to live accounts without an active paid plan. */
    protected function requireWritable(?array $acc = null): void
    {
        $acc ??= $this->acc;
        if (!Ledger::writable($this->m, $acc)) {
            $msg = 'Live accounts are read-only without an active paid plan. Upgrade or switch to a demo account.';
            if ((new Request())->isAjax()) {
                Response::json(['ok' => false, 'error' => $msg, 'upgrade' => url('/pricing')], 402);
            }
            Session::flash('error', $msg);
            Response::redirect('/terminal/billing');
        }
    }

    protected function back(string $to, string $type, string $msg): never
    {
        Session::flash($type, $msg);
        Response::redirect($to);
    }

    /** Date-range presets in the member's timezone → [from, to, key] as Y-m-d (inclusive). */
    protected function range(Request $req): array
    {
        $key = (string) $req->query('range', 'all');
        $now = new \DateTimeImmutable('now', new \DateTimeZone($this->tz));
        return match ($key) {
            'today' => [$now->format('Y-m-d'), $now->format('Y-m-d'), $key],
            'week' => [$now->modify('monday this week')->format('Y-m-d'), $now->format('Y-m-d'), $key],
            'month' => [$now->format('Y-m-01'), $now->format('Y-m-d'), $key],
            'last_month' => [$now->modify('first day of last month')->format('Y-m-d'), $now->modify('last day of last month')->format('Y-m-d'), $key],
            'custom' => [
                preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $req->query('from')) ? (string) $req->query('from') : null,
                preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $req->query('to')) ? (string) $req->query('to') : null, $key,
            ],
            default => [null, null, 'all'],
        };
    }
}
