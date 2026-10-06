<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $to, int $status = 302): never
    {
        $url = preg_match('#^https?://#', $to) ? $to : url($to);
        header('Location: ' . $url, true, $status);
        exit;
    }

    public static function back(string $fallback = '/'): never
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        // Only same-site referers are honoured (no open redirects).
        if ($ref !== '' && str_starts_with($ref, BASE_URL)) {
            header('Location: ' . $ref, true, 302);
            exit;
        }
        self::redirect($fallback);
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function abort(int $status, string $message = ''): never
    {
        throw new HttpException($status, $message);
    }
}
