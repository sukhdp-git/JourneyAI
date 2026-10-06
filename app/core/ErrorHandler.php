<?php
declare(strict_types=1);

namespace App\Core;

use Throwable;

/** Converts errors to exceptions, logs them privately and shows branded error pages (never raw errors). */
final class ErrorHandler
{
    public static function register(): void
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
        set_exception_handler([self::class, 'handle']);
        register_shutdown_function(static function (): void {
            $e = error_get_last();
            if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                Logger::error('Fatal: ' . $e['message'], ['file' => $e['file'], 'line' => $e['line']]);
                if (!headers_sent()) {
                    http_response_code(500);
                }
            }
        });
    }

    public static function handle(Throwable $e): void
    {
        if ($e instanceof HttpException) {
            self::render($e->status, $e->getMessage());
            return;
        }
        Logger::error(get_class($e) . ': ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'uri' => $_SERVER['REQUEST_URI'] ?? '']);
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
            echo json_encode(['ok' => false, 'error' => $message !== '' ? $message : self::title($status)]);
            return;
        }
        try {
            echo View::render('public/errors/error', ['status' => $status, 'title' => self::title($status), 'message' => $message], 'public/layouts/minimal');
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
