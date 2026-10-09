<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Request;
use App\Core\Response;
use App\Trading\FlexCard;

/** Daily flex card data for the share modal (JSON). */
final class FlexController extends TerminalController
{
    public function day(Request $req): never
    {
        $today = (new \DateTimeImmutable('now', new \DateTimeZone($this->tz)))->format('Y-m-d');
        $date = (string) $req->post('date', $today);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !\DateTime::createFromFormat('Y-m-d', $date) || $date > $today) {
            Response::json(['ok' => false, 'error' => 'Choose a valid trading day.'], 422);
        }
        [$card, $code] = FlexCard::forDay($this->m, $this->acc, $date, $this->tz);
        if (!$card['trades']) {
            Response::json(['ok' => false, 'error' => 'No closed trades on ' . $card['date_label'] . ' in this account.'], 422);
        }
        Response::json(['ok' => true, 'card' => $card, 'url' => url('/verify/' . $code), 'site' => setting('site_name', 'journzey.ai')]);
    }
}
