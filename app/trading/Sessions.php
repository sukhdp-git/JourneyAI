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
    public const CLOCKS = [
        'India' => ['Asia/Kolkata', 555, 930], 'New York' => ['America/New_York', 570, 960], 'London' => ['Europe/London', 480, 990],
        'Tokyo' => ['Asia/Tokyo', 540, 900], 'Singapore' => ['Asia/Singapore', 540, 1020], 'Dubai' => ['Asia/Dubai', 600, 900],
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

    public static function isValidTz(string $tz): bool
    {
        return in_array($tz, DateTimeZone::listIdentifiers(), true);
    }
}
