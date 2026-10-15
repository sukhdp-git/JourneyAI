<?php
declare(strict_types=1);

namespace App\Trading;

/** journzey.ai University mini-course: "Trading Foundations" — five modules, beginner to intermediate. Content lives in views/terminal/course. */
final class Course
{
    public const TITLE = 'Trading Foundations';

    /** n => [slug, title, short, minutes, icon, summary] */
    public const MODULES = [
        1 => ['harsh-reality', 'The harsh reality of trading', 'Harsh reality', 9, 'alert', 'Why most retail traders lose money — emotions, leverage and no journal — and what the numbers really say.'],
        2 => ['market-fundamentals', 'Market fundamentals', 'Fundamentals', 16, 'bars', 'Stocks, indices, forex, commodities and crypto; candlesticks, market structure, support & resistance and every order type.'],
        3 => ['risk-management', 'The golden rule: risk management', 'Risk management', 14, 'shield', 'Risk:reward, break-even win rates, expectancy and position sizing so one trade never costs more than 1–2%.'],
        4 => ['trading-psychology', 'Trading psychology & emotional traps', 'Psychology', 11, 'brain', 'Revenge trading, FOMO, cutting winners early, letting losers run — how each trap works and the rule that stops it.'],
        5 => ['habits-of-successful-traders', 'Habits of successful traders', 'Habits', 10, 'check-circle', 'Backtesting, a written trading plan and journaling every trade — the routine that turns practice into skill.'],
    ];

    public static function find(string $slug): ?int
    {
        foreach (self::MODULES as $n => $m) {
            if ($m[0] === $slug) {
                return $n;
            }
        }
        return null;
    }

    public static function minutes(): int
    {
        return array_sum(array_column(self::MODULES, 3));
    }
}
