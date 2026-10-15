<?php
declare(strict_types=1);

namespace App\Core;

use Throwable;

/** Converts errors to exceptions, logs them privately and shows branded error pages (never raw errors). */
final class ErrorHandler
{
    private static string $rid = '';

    /** Short per-request reference: sent as X-Request-Id, added to every log line and shown on 500 errors. */
    public static function requestId(): string
    {
        return self::$rid !== '' ? self::$rid : (self::$rid = strtoupper(bin2hex(random_bytes(5))));
    }

    public static function register(): void
    {
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            header('X-Request-Id: ' . self::requestId());
        }
        $started = microtime(true);
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
        set_exception_handler([self::class, 'handle']);
        register_shutdown_function(static function () use ($started): void {
            $e = error_get_last();
            if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                // Includes "Maximum execution time exceeded" and memory exhaustion, which a proxy may report as 502/504.
                Logger::error('Fatal: ' . $e['message'], ['file' => $e['file'], 'line' => $e['line'], 'method' => $_SERVER['REQUEST_METHOD'] ?? '', 'uri' => strtok($_SERVER['REQUEST_URI'] ?? '', '?')]);
                if (!headers_sent()) {
                    http_response_code(500);
                }
            }
            $secs = microtime(true) - $started;
            if (PHP_SAPI !== 'cli' && $secs > 20) {
                // Slow requests are the usual cause of gateway timeouts; the path (no query string, no body) is enough to find them.
                Logger::info('Slow request', ['method' => $_SERVER['REQUEST_METHOD'] ?? '', 'uri' => strtok($_SERVER['REQUEST_URI'] ?? '', '?'), 'seconds' => round($secs, 1), 'status' => http_response_code()]);
            }
        });
    }

    public static function handle(Throwable $e): void
    {
        if ($e instanceof HttpException) {
            self::render($e->status, $e->getMessage());
            return;
        }
        Logger::error(get_class($e) . ': ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'method' => $_SERVER['REQUEST_METHOD'] ?? '', 'uri' => strtok($_SERVER['REQUEST_URI'] ?? '', '?')]);
        if (APP_DEBUG) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo $e;
            return;
        }
        self::render(500);
    }

    public static function render(int $status, string $message = ''): void
    {
        if (!headers_sent()) {
            http_response_code($status);
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
        if ($wantsJson) {
            header('Content-Type: application/json');
            $text = $message !== '' ? $message : self::title($status) . ($status >= 500 ? '. Please try again. (Ref: ' . self::requestId() . ')' : '');
            echo json_encode(['ok' => false, 'error' => $text, 'ref' => $status >= 500 ? self::requestId() : null]);
            return;
        }
        try {
            echo View::render('public/errors/error', ['status' => $status, 'title' => self::title($status), 'message' => $message !== '' ? $message : ($status >= 500 ? 'Please try again in a moment. If it keeps happening, contact support with reference ' . self::requestId() . '.' : '')], 'public/layouts/minimal');
        } catch (Throwable $inner) {
            Logger::error('Error page failed: ' . $inner->getMessage());
            header('Content-Type: text/html; charset=utf-8');
            echo '<!doctype html><title>' . $status . '</title><h1>' . $status . ' — ' . htmlspecialchars(self::title($status)) . '</h1>';
        }
    }

    public static function title(int $status): string
    {
        return match ($status) {
            400 => 'Bad request',
            403 => 'Access denied',
            404 => 'Page not found',
            405 => 'Method not allowed',
            419 => 'Session expired',
            429 => 'Too many requests',
            503 => 'Service unavailable',
            default => 'Something went wrong',
        };
    }
}
