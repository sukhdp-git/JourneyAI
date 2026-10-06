<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Crypto;
use App\Core\Database;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Trading\Ingest;
use App\Trading\Members;
use App\Trading\Payments;

/** Server-to-server endpoints. No session, no CSRF — every request is authenticated by an HMAC signature. */
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

    /**
     * Signed trade push: X-Journzey-Timestamp (unix s) + X-Journzey-Signature: sha256=HMAC(secret, "{ts}.{body}").
     * Rejects stale timestamps (>5 min), replays the same event_id idempotently, dedupes trades by broker id.
     */
    public function trades(Request $req): never
    {
        $key = (string) $req->params['key'];
        if (!preg_match('/^[a-f0-9]{24}$/', $key) || !RateLimiter::hit('wh:' . $key, 120, 60)) {
            Response::json(['ok' => false, 'error' => 'not found or rate limited'], 404);
        }
        $conn = Database::one("SELECT c.*, a.id AS acc_id FROM broker_connections c JOIN trading_accounts a ON a.id = c.account_id WHERE c.public_id = :p AND c.status = 'active'", ['p' => $key]);
        $raw = $this->raw();
        $ts = (int) ($_SERVER['HTTP_X_JOURNZEY_TIMESTAMP'] ?? 0);
        $sig = preg_replace('/^sha256=/', '', (string) ($_SERVER['HTTP_X_JOURNZEY_SIGNATURE'] ?? ''));
        $secret = $conn ? (Crypto::decrypt((string) $conn['secret_enc']) ?? '') : '';
        if (!$conn || $secret === '' || $sig === '' || !hash_equals(hash_hmac('sha256', $ts . '.' . $raw, $secret), $sig)) {
            Response::json(['ok' => false, 'error' => 'invalid signature'], 401);
        }
        if (abs(time() - $ts) > 300) {
            Response::json(['ok' => false, 'error' => 'timestamp outside the 5-minute window'], 401);
        }
        $j = json_decode($raw, true);
        $eventId = is_array($j) ? trim((string) ($j['event_id'] ?? '')) : '';
        if ($eventId === '' || mb_strlen($eventId) > 100) {
            Response::json(['ok' => false, 'error' => 'event_id is required (max 100 chars)'], 422);
        }
        $prev = Database::one('SELECT status, message FROM webhook_events WHERE connection_id = :c AND event_id = :e', ['c' => $conn['id'], 'e' => $eventId]);
        if ($prev) {
            Response::json(['ok' => true, 'duplicate_event' => true, 'status' => $prev['status'], 'message' => $prev['message']]);
        }
        $acc = Database::one('SELECT * FROM trading_accounts WHERE id = :a AND user_id = :u', ['a' => $conn['account_id'], 'u' => $conn['user_id']]);
        $user = Database::one('SELECT * FROM users WHERE id = :u', ['u' => $conn['user_id']]);
        if (!$acc || !$user || $user['status'] !== 'active') {
            Response::json(['ok' => false, 'error' => 'account unavailable'], 410);
        }
        if (!(int) $acc['is_demo'] && !Members::entitlements($user)['live']) {
            $this->log($conn, $eventId, 'rejected', 'live account requires an active plan');
            Response::json(['ok' => false, 'error' => 'live account requires an active paid plan'], 402);
        }
        $items = isset($j['trades']) && is_array($j['trades']) ? array_slice($j['trades'], 0, 100) : (isset($j['trade']) && is_array($j['trade']) ? [$j['trade']] : []);
        if (!$items) {
            Response::json(['ok' => false, 'error' => 'trade or trades[] is required'], 422);
        }
        $res = ['imported' => 0, 'duplicate' => 0, 'errors' => []];
        foreach ($items as $i => $t) {
            $row = is_array($t) ? Ingest::fromWebhook($t) : 'trade must be an object';
            if (is_string($row)) {
                $res['errors'][] = ['index' => $i, 'error' => $row];
                continue;
            }
            $r = Ingest::insert((int) $conn['user_id'], $acc, $row, 'WEBHOOK');
            isset($res[$r]) && $r !== 'errors' ? $res[$r]++ : $res['errors'][] = ['index' => $i, 'error' => $r];
        }
        $status = $res['errors'] && !$res['imported'] && !$res['duplicate'] ? 'failed' : 'processed';
        $this->log($conn, $eventId, $status, sprintf('%d imported, %d duplicate, %d errors', $res['imported'], $res['duplicate'], count($res['errors'])));
        Response::json(['ok' => $status === 'processed'] + $res, $status === 'processed' ? 200 : 422);
    }

    private function log(array $conn, string $eventId, string $status, string $msg): void
    {
        Database::query('INSERT IGNORE INTO webhook_events (connection_id, event_id, status, message) VALUES (:c, :e, :s, :m)', ['c' => $conn['id'], 'e' => $eventId, 's' => $status, 'm' => mb_substr($msg, 0, 255)]);
        Database::query('UPDATE broker_connections SET events_count = events_count + 1, last_event_at = UTC_TIMESTAMP() WHERE id = :id', ['id' => $conn['id']]);
    }
}
