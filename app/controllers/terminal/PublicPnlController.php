<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Trading\Ledger;
use App\Trading\Members;
use App\Trading\PublicPnl;

/** Create and manage public verified P&L links (/p/{code}). */
final class PublicPnlController extends TerminalController
{
    /** Public links live in Settings now. */
    public function index(Request $req): never
    {
        Response::redirect('/terminal/settings#public-pnl');
    }

    /** Data for the Settings section. */
    public static function data(int $uid, string $tz): array
    {
        $today = new \DateTimeImmutable('now', new \DateTimeZone($tz));
        return ['links' => PublicPnl::links($uid), 'accountsAll' => Ledger::accounts($uid), 'today' => $today->format('Y-m-d'), 'monthStart' => $today->format('Y-m-01')];
    }

    public function store(Request $req): never
    {
        if ((int) Database::value('SELECT COUNT(*) FROM public_pnl_links WHERE user_id = :u', ['u' => $this->uid]) >= 30) {
            $this->back('/terminal/settings#public-pnl', 'error', 'You can keep up to 30 links. Delete old ones first.');
        }
        $d = PublicPnl::input(fn ($k) => $req->post($k), array_map('intval', array_column(Ledger::accounts($this->uid), 'id')), $this->tz);
        if (is_string($d)) {
            $this->back('/terminal/settings#public-pnl', 'error', $d);
        }
        $code = PublicPnl::create($this->uid, $d);
        Members::audit($this->uid, 'public_pnl_created', $code);
        $_SESSION['_new_pnl'] = $code;
        $this->back('/terminal/settings#public-pnl', 'success', 'Your public P&L link is ready — copy it below and share it anywhere.');
    }

    public function update(Request $req): never
    {
        $l = $this->own($req);
        if ($req->post('action') === 'delete') {
            Database::delete('public_pnl_links', 'id = :id AND user_id = :u', ['id' => $l['id'], 'u' => $this->uid]);
            $this->back('/terminal/settings#public-pnl', 'success', 'Link deleted — it no longer opens.');
        }
        if ($req->post('action') === 'toggle') {
            Database::update('public_pnl_links', ['is_active' => (int) $l['is_active'] ? 0 : 1], 'id = :id AND user_id = :u', ['id' => $l['id'], 'u' => $this->uid]);
            $this->back('/terminal/settings#public-pnl', 'success', (int) $l['is_active'] ? 'Link paused — visitors see “not available”.' : 'Link is live again.');
        }
        $d = PublicPnl::input(fn ($k) => $req->post($k), array_map('intval', array_column(Ledger::accounts($this->uid), 'id')), $this->tz);
        if (is_string($d)) {
            $this->back('/terminal/settings#public-pnl', 'error', $d);
        }
        Database::update('public_pnl_links', $d, 'id = :id AND user_id = :u', ['id' => $l['id'], 'u' => $this->uid]);
        $this->back('/terminal/settings#public-pnl', 'success', 'Link updated — the same URL now shows the new settings.');
    }

    private function own(Request $req): array
    {
        return Database::one('SELECT * FROM public_pnl_links WHERE id = :id AND user_id = :u', ['id' => (int) $req->params['id'], 'u' => $this->uid]) ?? Response::abort(404);
    }
}
