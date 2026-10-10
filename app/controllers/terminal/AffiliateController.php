<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Trading\Affiliates;

/** The influencer's own affiliate dashboard. Only the affiliate (and the site admin, in the Control Panel) can see it. */
final class AffiliateController extends TerminalController
{
    public function index(Request $req): never
    {
        $a = Affiliates::forUser($this->uid);
        if (!$a) {
            Response::redirect('/affiliates');
        }
        $stats = $a['status'] === 'approved' || $a['status'] === 'suspended' ? Affiliates::stats((int) $a['id']) : null;
        $this->render('affiliate', ['a' => $a, 'stats' => $stats], 'Affiliate dashboard', 'affiliate');
    }

    public function payout(Request $req): never
    {
        $a = Affiliates::forUser($this->uid);
        if (!$a) {
            Response::redirect('/affiliates');
        }
        $details = mb_substr(trim((string) $req->post('payout_details', '')), 0, 500);
        Database::update('affiliates', ['payout_details' => $details !== '' ? $details : null], 'id = :id', ['id' => $a['id']]);
        $this->back('/terminal/affiliate', 'success', 'Payout details saved.');
    }
}
