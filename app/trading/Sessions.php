<?php
declare(strict_types=1);

namespace App\Trading;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Trading-session classification using each market's local time (DST handled by the tz database):
 * Asia = Tokyo 09:00–15:00, London = 08:00–16:30, New York = 08:00–17:00, overlap = both London and NY.
 */
final class Sessions
{
    /** Main trading sessions in each market's own local time (minutes from midnight), Monday–Friday. */
    public const MARKETS = [
        'TOKYO' => ['Tokyo / Asian', 'Asia/Tokyo', 540, 1080],
        'LONDON' => ['London', 'Europe/London', 480, 1020],
        'NEW_YORK' => ['New York', 'America/New_York', 480, 1020],
    ];
    /** Timezones offered in the Home Hub clock (label => IANA zone). */
    public const ZONES = [
        'India' => 'Asia/Kolkata', 'New York' => 'America/New_York', 'London' => 'Europe/London', 'Tokyo' => 'Asia/Tokyo',
        'Singapore' => 'Asia/Singapore', 'Hong Kong' => 'Asia/Hong_Kong', 'Dubai' => 'Asia/Dubai', 'Sydney' => 'Australia/Sydney',
        'Frankfurt' => 'Europe/Berlin', 'Chicago' => 'America/Chicago', 'UTC' => 'UTC',
    ];

    private static function minutes(DateTimeImmutable $utc, string $tz): int
    {
        $l = $utc->setTimezone(new DateTimeZone($tz));
        return (int) $l->format('G') * 60 + (int) $l->format('i');
    }

    public static function classify(string $utcDateTime): string
    {
        $t = new DateTimeImmutable($utcDateTime, new DateTimeZone('UTC'));
        $ldn = self::minutes($t, 'Europe/London');
        $ny = self::minutes($t, 'America/New_York');
        $tky = self::minutes($t, 'Asia/Tokyo');
        $inL = $ldn >= 480 && $ldn < 990;
        $inN = $ny >= 480 && $ny < 1020;
        return match (true) {
            $inL && $inN => 'LONDON_NY_OVERLAP',
            $inL => 'LONDON',
            $inN => 'NEW_YORK',
            $tky >= 540 && $tky < 900 => 'ASIA',
            default => 'OFF_HOURS',
        };
    }

    /**
     * Analytics bucket (Asian / London / New York). New York takes precedence once it opens (08:00 New York),
     * London covers London morning until then; everything else is grouped as Asian.
     */
    public static function bucket(string $utcDateTime): string
    {
        $t = new DateTimeImmutable($utcDateTime, new DateTimeZone('UTC'));
        $ny = self::minutes($t, 'America/New_York');
        $ldn = self::minutes($t, 'Europe/London');
        return match (true) {
            $ny >= 480 && $ny < 1020 => 'NEW_YORK',
            $ldn >= 480 && $ldn < 1020 => 'LONDON',
            default => 'ASIAN',
        };
    }

    /** Which market sessions are open right now, with their open/close times converted to $tz. */
    public static function status(string $tz, ?int $now = null): array
    {
        $utc = (new DateTimeImmutable('@' . ($now ?? time())))->setTimezone(new DateTimeZone('UTC'));
        $out = [];
        foreach (self::MARKETS as $key => [$label, $zone, $open, $close]) {
            $local = $utc->setTimezone(new DateTimeZone($zone));
            $mins = (int) $local->format('G') * 60 + (int) $local->format('i');
            $weekday = (int) $local->format('N') <= 5;
            $o = $local->setTime(intdiv($open, 60), $open % 60)->setTimezone(new DateTimeZone($tz));
            $c = $local->setTime(intdiv($close, 60), $close % 60)->setTimezone(new DateTimeZone($tz));
            $out[$key] = ['label' => $label, 'zone' => $zone, 'open' => $open, 'close' => $close, 'active' => $weekday && $mins >= $open && $mins < $close,
                'local_hours' => $o->format('H:i') . '–' . $c->format('H:i')];
        }
        return $out;
    }

    public static function isValidTz(string $tz): bool
    {
        return in_array($tz, DateTimeZone::listIdentifiers(), true);
    }
}
