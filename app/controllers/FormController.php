<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Mailer;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Seo;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Content;

/**
 * Public contact and consultation forms.
 * Flow: CSRF → honeypot/timing spam checks → rate limit → validation → SAVE TO MYSQL → attempt email → record status.
 * The submission is stored before any email is attempted, so an SMTP failure can never lose it.
 */
final class FormController extends Controller
{
    public function contact(Request $req): never
    {
        Seo::breadcrumbs([['Home', '/'], ['Contact', '/contact']]);
        $this->render('contact', ['formTs' => self::stamp(), 'custom' => Content::customSections('contact')], [
            'title' => setting('seo_contact_title') ?: 'Contact', 'description' => setting('seo_contact_description'), 'path' => '/contact',
        ]);
    }

    public function booking(Request $req): never
    {
        Seo::breadcrumbs([['Home', '/'], ['Book a demo', '/book-consultation']]);
        $this->render('booking', ['formTs' => self::stamp(), 'services' => Content::services()], [
            'title' => setting('seo_booking_title') ?: 'Book a consultation', 'description' => setting('seo_booking_description'), 'path' => '/book-consultation',
        ]);
    }

    public function contactSubmit(Request $req): never
    {
        Csrf::verify($req);
        $this->guard($req, 'contact', '/contact');
        $v = new Validator($_POST, [
            'name' => 'required|max:120', 'email' => 'required|email|max:190', 'phone' => 'phone|max:40',
            'subject' => 'max:200', 'message' => 'required|min:10|max:5000',
        ], ['message' => 'Message']);
        if ($v->fails()) {
            $this->fail($v, '/contact');
        }
        $d = $v->clean;
        $id = Database::insert('contact_messages', [
            'name' => $d['name'], 'email' => strtolower($d['email']), 'phone' => $d['phone'] ?: null, 'subject' => $d['subject'] ?: null,
            'message' => $d['message'], 'ip' => $req->ip(), 'user_agent' => $req->userAgent(),
        ]);
        $vars = ['name' => $d['name'], 'email' => $d['email'], 'phone' => $d['phone'] ?: '—', 'subject' => $d['subject'] ?: 'Website enquiry', 'message' => $d['message'], 'service' => '—'];
        $result = $this->notify('contact_admin', 'contact_user', setting_on('contact_send_confirmation', true), $d['email'], $d['name'], $vars, 'contact', $id);
        Database::update('contact_messages', ['email_status' => $result['status'], 'email_error' => $result['error'] ?: null], 'id = :id', ['id' => $id]);
        Session::flash('success', setting('contact_success') ?: 'Thank you — your message has been received.');
        Response::redirect('/contact#form');
    }

    public function bookingSubmit(Request $req): never
    {
        Csrf::verify($req);
        $this->guard($req, 'booking', '/book-consultation');
        $serviceIds = array_map('strval', array_column(Content::services(), 'id'));
        $v = new Validator($_POST, [
            'name' => 'required|max:120', 'email' => 'required|email|max:190', 'phone' => 'required|phone|max:40', 'whatsapp' => 'phone|max:40',
            'company' => 'max:150', 'service_id' => $serviceIds ? 'in:' . implode(',', $serviceIds) . ',other' : 'max:10',
            'preferred_date' => 'date', 'preferred_time' => 'time', 'message' => 'max:5000', 'consent' => 'required',
        ], ['service_id' => 'Interested service', 'preferred_date' => 'Preferred date', 'preferred_time' => 'Preferred time', 'consent' => 'Privacy consent']);
        $d = $v->clean;
        if (!$v->fails() && $d['preferred_date'] !== '' && $d['preferred_date'] < gmdate('Y-m-d', time() - 86400)) {
            $v->errors['preferred_date'] = 'Preferred date cannot be in the past.';
        }
        if ($v->fails()) {
            $this->fail($v, '/book-consultation');
        }
        $service = null;
        if ($d['service_id'] !== '' && $d['service_id'] !== 'other') {
            $service = Database::one('SELECT id, title FROM services WHERE id = :id', ['id' => (int) $d['service_id']]);
        }
        $id = Database::insert('leads', [
            'name' => $d['name'], 'email' => strtolower($d['email']), 'phone' => $d['phone'], 'whatsapp' => $d['whatsapp'] ?: null,
            'company' => $d['company'] ?: null, 'service_id' => $service['id'] ?? null, 'service_name' => $service['title'] ?? ($d['service_id'] === 'other' ? 'Other / not sure' : null),
            'preferred_date' => $d['preferred_date'] ?: null, 'preferred_time' => $d['preferred_time'] ?: null, 'message' => $d['message'] ?: null,
            'consent' => 1, 'source' => 'book-consultation', 'ip' => $req->ip(), 'user_agent' => $req->userAgent(),
        ]);
        $vars = [
            'name' => $d['name'], 'email' => $d['email'], 'phone' => $d['phone'], 'service' => $service['title'] ?? 'Not specified',
            'message' => $d['message'] ?: '—', 'preferred_date' => $d['preferred_date'] ?: 'Any day', 'preferred_time' => $d['preferred_time'] ?: '',
        ];
        $result = $this->notify('lead_admin', 'lead_user', setting_on('booking_send_confirmation', true), $d['email'], $d['name'], $vars, 'lead', $id);
        Database::update('leads', ['email_status' => $result['status'], 'email_error' => $result['error'] ?: null], 'id = :id', ['id' => $id]);
        Database::insert('lead_notes', ['lead_id' => $id, 'admin_id' => null, 'type' => 'email', 'body' => 'Admin notification: ' . $result['status'] . ($result['error'] ? ' — ' . $result['error'] : '')]);
        Session::flash('success', setting('booking_success') ?: 'Thank you — your request has been received.');
        Response::redirect('/book-consultation#form');
    }

    /** Sends the admin notification (+ optional confirmation). Returns the admin notification result. */
    private function notify(string $adminTpl, string $userTpl, bool $confirm, string $email, string $name, array $vars, string $type, int $id): array
    {
        $result = ['status' => 'disabled', 'error' => 'No notification address configured.'];
        try {
            $to = setting('notify_email') ?: setting('contact_email');
            if ($to === '') {
                $to = (string) Database::value('SELECT from_email FROM smtp_settings WHERE id = 1');
            }
            if ($to !== '') {
                $result = Mailer::sendTemplate($adminTpl, $to, $vars, ['reply_to' => $email, 'reply_name' => $name, 'related_type' => $type, 'related_id' => $id]);
            }
            if ($confirm) {
                Mailer::sendTemplate($userTpl, $email, $vars, ['related_type' => $type, 'related_id' => $id]);
            }
        } catch (\Throwable $e) {
            Logger::error('Form notification failed: ' . $e->getMessage());
            $result = ['status' => 'failed', 'error' => 'Unexpected error while sending email.'];
        }
        return $result;
    }

    /** Spam protection: honeypot field, minimum fill time (signed timestamp) and per-IP rate limit. */
    private function guard(Request $req, string $form, string $back): void
    {
        $honeypot = (string) ($_POST['website'] ?? '');
        $ts = (string) ($_POST['_ts'] ?? '');
        $valid = self::checkStamp($ts);
        if ($honeypot !== '' || $valid === false) {
            Logger::info("Spam blocked on $form form", ['ip' => $req->ip()]);
            Session::flash('success', 'Thank you — your submission has been received.');
            Response::redirect($back);
        }
        if (!RateLimiter::hit("form:$form:" . $req->ip(), 5, 600)) {
            Session::flash('error', 'Too many submissions from your connection. Please try again in a few minutes.');
            Response::redirect($back);
        }
    }

    private function fail(Validator $v, string $back): never
    {
        Session::withErrors($v->errors, $_POST);
        Session::flash('error', 'Please correct the highlighted fields.');
        Response::redirect($back . '#form');
    }

    /** HMAC-signed render time, so bots that post instantly (or replay forever) are rejected. */
    public static function stamp(): string
    {
        $t = (string) time();
        return $t . '.' . hash_hmac('sha256', $t, (string) (defined('APP_KEY') ? APP_KEY : 'k'));
    }

    private static function checkStamp(string $stamp): bool
    {
        [$t, $sig] = array_pad(explode('.', $stamp, 2), 2, '');
        if (!ctype_digit($t) || !hash_equals(hash_hmac('sha256', $t, (string) (defined('APP_KEY') ? APP_KEY : 'k')), $sig)) {
            return false;
        }
        $age = time() - (int) $t;
        return $age >= 3 && $age <= 86400;
    }
}
