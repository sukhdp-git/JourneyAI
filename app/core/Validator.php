<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Server-side validation. Rules: required, email, phone, url, max:N, min:N, in:a,b, slug, date, time, int, numeric, color.
 * Client-side validation is a convenience only — this is the authority.
 */
final class Validator
{
    public array $errors = [];
    public array $clean = [];

    public function __construct(private readonly array $data, array $rules, array $labels = [])
    {
        foreach ($rules as $field => $ruleStr) {
            $value = $data[$field] ?? '';
            $value = is_string($value) ? trim($value) : $value;
            $label = $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
            $rules = is_array($ruleStr) ? $ruleStr : explode('|', $ruleStr);
            $required = in_array('required', $rules, true);
            if ($value === '' || $value === null || $value === []) {
                if ($required) {
                    $this->errors[$field] = "$label is required.";
                }
                $this->clean[$field] = is_array($value) ? [] : '';
                continue;
            }
            foreach ($rules as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                $err = $this->check($name, $arg, $value, $label);
                if ($err) {
                    $this->errors[$field] = $err;
                    break;
                }
            }
            $this->clean[$field] = $value;
        }
    }

    private function check(string $rule, ?string $arg, mixed $v, string $label): ?string
    {
        $s = is_scalar($v) ? (string) $v : '';
        return match ($rule) {
            'required' => null,
            'email' => filter_var($s, FILTER_VALIDATE_EMAIL) ? null : "$label must be a valid email address.",
            'phone' => preg_match('/^\+?[0-9 ()\-.]{6,25}$/', $s) ? null : "$label must be a valid phone number.",
            'url' => (filter_var($s, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $s)) ? null : "$label must be a valid http(s) URL.",
            'link' => (preg_match('#^(/|https?://|mailto:|tel:|\#)#i', $s) && !preg_match('#^\s*javascript:#i', $s)) ? null : "$label must start with /, http(s)://, mailto:, tel: or #.",
            'max' => mb_strlen($s) <= (int) $arg ? null : "$label must be at most $arg characters.",
            'min' => mb_strlen($s) >= (int) $arg ? null : "$label must be at least $arg characters.",
            'in' => in_array($s, explode(',', (string) $arg), true) ? null : "$label has an invalid value.",
            'slug' => preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $s) ? null : "$label may only contain lower-case letters, numbers and hyphens.",
            'date' => (\DateTime::createFromFormat('Y-m-d', $s) && \DateTime::createFromFormat('Y-m-d', $s)->format('Y-m-d') === $s) ? null : "$label must be a valid date.",
            'datetime' => strtotime($s) !== false ? null : "$label must be a valid date and time.",
            'time' => preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $s) ? null : "$label must be a valid time.",
            'int' => filter_var($s, FILTER_VALIDATE_INT) !== false ? null : "$label must be a whole number.",
            'numeric' => is_numeric($s) ? null : "$label must be a number.",
            'color' => preg_match('/^#[0-9a-fA-F]{6}$/', $s) ? null : "$label must be a hex colour like #4f46e5.",
            'between' => (static function () use ($s, $arg, $label) {
                [$lo, $hi] = explode(',', (string) $arg);
                return is_numeric($s) && $s >= $lo && $s <= $hi ? null : "$label must be between $lo and $hi.";
            })(),
            default => null,
        };
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }
}
