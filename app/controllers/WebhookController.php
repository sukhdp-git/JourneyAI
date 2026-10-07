<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Trading\Payments;

/** Payment-provider webhooks. No session, no CSRF — every request is authenticated by an HMAC signature. */
final class WebhookController
{
    private function raw(): string
    {
        $raw = (string) file_get_contents('php://input', false, null, 0, 1024 * 1024);
        return $raw;
    }

    public function razorpay(Request $req): never
    {
        [$st, $msg] = Payments::razorpayWebhook($this->raw(), (string) ($_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? ''));
        Response::json(['ok' => $st === 200, 'message' => $msg], $st);
    }

    public function stripe(Request $req): never
    {
        [$st, $msg] = Payments::stripeWebhook($this->raw(), (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? ''));
        Response::json(['ok' => $st === 200, 'message' => $msg], $st);
    }
}
