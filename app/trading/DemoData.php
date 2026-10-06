<?php
declare(strict_types=1);

namespace App\Trading;

/**
 * Deterministic DEMO DATA set (Aug–Oct 2026): 42 trades and 13 journal entries showing clean trades,
 * losses, FOMO, revenge trades, moved stops and other rule violations. Always written to a demo account
 * (is_demo = 1) and never mixed into live analytics.
 */
final class DemoData
{
    private const SPECS = [
        ['2026-08-03', '08:14', 'XAUUSD', 'LONG', 'VAL Bounce', 3, 'CALM', 'NONE', 1, 'Clean acceptance back into value, held to target.'],
        ['2026-08-03', '14:02', 'US500', 'SHORT', 'VAH Rejection', -1, 'FOCUSED', 'NONE', 1, 'Valid setup, stopped by CPI drift.'],
        ['2026-08-04', '09:31', 'EURUSD', 'LONG', 'Order Block', 2, 'CONFIDENT', 'NONE', 1, 'London OB respected, partials at 1R.'],
        ['2026-08-05', '14:47', 'NAS100', 'LONG', 'ICT Silver Bullet', 2.5, 'FOCUSED', 'NONE', 1, 'Silver bullet FVG with displacement.'],
        ['2026-08-06', '15:20', 'NAS100', 'SHORT', 'Liquidity Sweep', -1, 'ANXIOUS', 'LATE_ENTRY', 0, 'Entered after the move; poor location.'],
        ['2026-08-07', '07:55', 'XAUUSD', 'SHORT', 'Fair Value Gap', 2, 'CALM', 'NONE', 1, 'Asia high swept, FVG short into London.'],
        ['2026-08-10', '13:45', 'BTCUSDT', 'LONG', 'Trend Following', 1.8, 'CONFIDENT', 'NONE', 1, 'Pullback to 20 EMA in uptrend.'],
        ['2026-08-11', '14:05', 'USOIL', 'SHORT', 'VAH Rejection', -1, 'FOMO', 'FOMO_ENTRY', 0, 'Chased the inventory headline. No plan.'],
        ['2026-08-11', '14:18', 'USOIL', 'LONG', 'Liquidity Sweep', -1, 'REVENGE', 'REVENGE_TRADE', 0, 'Revenge flip right after the loss.'],
        ['2026-08-11', '14:31', 'USOIL', 'SHORT', 'Trend Following', -1.6, 'REVENGE', 'MOVED_STOP', 0, 'Moved stop wider. Third loss in 30 min — tilt.'],
        ['2026-08-13', '08:40', 'EURUSD', 'SHORT', 'POC', 1.5, 'NEUTRAL', 'NONE', 1, 'Rotation back to POC.'],
        ['2026-08-14', '14:33', 'US500', 'LONG', 'Volume Profile', 2, 'FOCUSED', 'NONE', 1, 'LVN acceptance, trended to HVN.'],
        ['2026-08-18', '08:22', 'XAUUSD', 'LONG', 'VAL Bounce', -1, 'CALM', 'NONE', 1, 'Good trade, bad outcome.'],
        ['2026-08-19', '15:10', 'NAS100', 'LONG', 'ICT Silver Bullet', 3, 'FOCUSED', 'NONE', 1, 'Textbook draw on liquidity.'],
        ['2026-08-20', '13:58', 'BTCUSDT', 'SHORT', 'Liquidity Sweep', 2.2, 'CALM', 'NONE', 1, 'Swept equal highs then rejected.'],
        ['2026-08-24', '09:05', 'EURUSD', 'LONG', 'Fair Value Gap', -1, 'GREEDY', 'OVERSIZED', 0, 'Doubled size after two wins.'],
        ['2026-08-26', '14:40', 'US500', 'SHORT', 'VAH Rejection', 2.5, 'FOCUSED', 'NONE', 1, 'Failed auction above VAH.'],
        ['2026-08-28', '08:10', 'XAUUSD', 'SHORT', 'Order Block', 0.6, 'ANXIOUS', 'EARLY_EXIT', 0, 'Cut early on noise; target hit later.'],
        ['2026-09-01', '14:36', 'NAS100', 'LONG', 'Trend Following', 1.5, 'CONFIDENT', 'NONE', 1, 'Trend day, trailed stop.'],
        ['2026-09-02', '08:18', 'XAUUSD', 'LONG', 'VAL Bounce', 3, 'CALM', 'NONE', 1, 'Best setup of the month.'],
        ['2026-09-03', '13:50', 'USOIL', 'LONG', 'Volume Profile', -1, 'NEUTRAL', 'NONE', 1, 'Stopped on EIA spike.'],
        ['2026-09-04', '12:31', 'US500', 'SHORT', 'ICT Silver Bullet', -1, 'FOMO', 'NEWS_GAMBLE', 0, 'Traded straight into NFP.'],
        ['2026-09-08', '09:12', 'EURUSD', 'SHORT', 'Liquidity Sweep', 2, 'FOCUSED', 'NONE', 1, 'Frankfurt high sweep.'],
        ['2026-09-09', '14:44', 'BTCUSDT', 'LONG', 'Order Block', -1, 'CALM', 'NONE', 1, 'Clean loss, plan followed.'],
        ['2026-09-10', '08:05', 'XAUUSD', 'SHORT', 'Fair Value Gap', 2.4, 'FOCUSED', 'NONE', 1, 'London open displacement.'],
        ['2026-09-11', '15:02', 'NAS100', 'SHORT', 'VAH Rejection', -2.1, 'FRUSTRATED', 'NO_STOP', 0, 'No hard stop; mental stop failed.'],
        ['2026-09-15', '14:38', 'US500', 'LONG', 'POC', 1.2, 'NEUTRAL', 'NONE', 1, 'POC magnet rotation.'],
        ['2026-09-16', '08:27', 'XAUUSD', 'LONG', 'Liquidity Sweep', 2.8, 'CONFIDENT', 'NONE', 1, 'Sell-side sweep into demand.'],
        ['2026-09-17', '18:15', 'BTCUSDT', 'SHORT', 'Trend Following', -1, 'BORED', 'OVERTRADING', 0, 'Off-hours boredom trade.'],
        ['2026-09-22', '14:50', 'NAS100', 'LONG', 'ICT Silver Bullet', 2, 'FOCUSED', 'NONE', 1, 'Silver bullet continuation.'],
        ['2026-09-23', '09:40', 'EURUSD', 'LONG', 'Order Block', 1.6, 'CALM', 'NONE', 1, 'H1 OB with BOS.'],
        ['2026-09-24', '14:12', 'USOIL', 'SHORT', 'VAH Rejection', 2, 'FOCUSED', 'NONE', 1, 'Rejected value high cleanly.'],
        ['2026-09-25', '08:33', 'XAUUSD', 'SHORT', 'Order Block', -1, 'CALM', 'NONE', 1, 'Invalidated by USD weakness.'],
        ['2026-09-29', '14:41', 'US500', 'LONG', 'Volume Profile', 2.2, 'CONFIDENT', 'NONE', 1, 'Composite profile HVN reclaim.'],
        ['2026-09-30', '15:05', 'NAS100', 'SHORT', 'Liquidity Sweep', -1, 'GREEDY', 'CHASING', 0, 'Chased extension at month end.'],
        ['2026-10-01', '08:12', 'XAUUSD', 'LONG', 'VAL Bounce', 2.5, 'CALM', 'NONE', 1, 'Q4 open: VAL hold.'],
        ['2026-10-01', '14:36', 'BTCUSDT', 'LONG', 'Fair Value Gap', 1.4, 'FOCUSED', 'NONE', 1, 'FVG continuation.'],
        ['2026-10-02', '12:45', 'EURUSD', 'SHORT', 'Trend Following', -1, 'ANXIOUS', 'IGNORED_PLAN', 0, 'Took a B-setup outside plan.'],
        ['2026-10-02', '14:48', 'NAS100', 'LONG', 'ICT Silver Bullet', 2.6, 'FOCUSED', 'NONE', 1, 'Clean silver bullet.'],
        ['2026-10-05', '08:20', 'XAUUSD', 'SHORT', 'Liquidity Sweep', 1.9, 'CALM', 'NONE', 1, 'Swept Asia high into supply.'],
        ['2026-10-05', '13:55', 'USOIL', 'LONG', 'Order Block', -1, 'NEUTRAL', 'NONE', 1, 'Valid loss.'],
        ['2026-10-05', '14:40', 'US500', 'SHORT', 'VAH Rejection', 1.8, 'FOCUSED', 'NONE', 1, 'VAH failure into close.'],
    ];
    private const MODEL = [
        'XAUUSD' => [3340, 120, 5, 0.4], 'US500' => [6380, 140, 10, 20], 'NAS100' => [23250, 600, 35, 6],
        'EURUSD' => [1.162, 0.02, 0.0022, 0.9], 'BTCUSDT' => [112000, 7000, 900, 0.22], 'USOIL' => [66, 4, 0.35, 0.55],
    ];

    public const JOURNALS = [
        ['2026-08-03', 5, 'CALM', 9, 'Followed the pre-market routine. Waited for acceptance back into value before entering gold.', 'Patience at value edges pays.'],
        ['2026-08-06', 3, 'ANXIOUS', 5, 'Entered NAS short late after missing the initial move. Location was poor.', 'If I missed it, I missed it.'],
        ['2026-08-11', 1, 'REVENGE', 2, 'Three oil losses in under 30 minutes. Chased the headline, flipped in revenge, then moved my stop. Classic tilt.', 'After two losses in a session, stop trading for the day.'],
        ['2026-08-14', 5, 'FOCUSED', 9, 'Reset after the tilt day. Took only A+ volume-profile setups.', 'Fewer trades, better trades.'],
        ['2026-08-24', 2, 'GREEDY', 4, 'Doubled position size after two winners. The loss cost more than both wins combined.', 'Size is fixed by the plan, not by mood.'],
        ['2026-08-28', 3, 'ANXIOUS', 6, 'Closed gold short at 0.6R on noise. Price hit my target an hour later.', 'Let the stop and target do their job.'],
        ['2026-09-02', 5, 'CALM', 10, 'Best execution of the month on the VAL bounce.', 'My edge is strongest at London open on gold.'],
        ['2026-09-04', 2, 'FOMO', 3, 'Traded into NFP despite my rule. Pure gamble.', 'No positions 15 minutes either side of tier-1 news.'],
        ['2026-09-11', 1, 'FRUSTRATED', 2, 'Did not place a hard stop on NAS. Mental stop failed and the loss reached 2R.', 'Hard stop is placed before the entry fills. Always.'],
        ['2026-09-17', 2, 'BORED', 4, 'Off-hours BTC trade out of boredom.', 'Close the platform when the session ends.'],
        ['2026-09-24', 5, 'FOCUSED', 9, 'Oil VAH rejection executed exactly to plan.', 'Checklist before every entry.'],
        ['2026-10-02', 3, 'ANXIOUS', 6, 'Took a B-setup on EURUSD outside my plan, then recovered with a clean NAS silver bullet.', 'Only trade the playbook.'],
        ['2026-10-05', 5, 'CALM', 9, 'Strong start to October. Two winners, one valid loss.', 'Valid losses are part of the edge.'],
    ];

    /** @return array<array> trade rows ready to insert (without user/account/strategy ids) */
    public static function trades(): array
    {
        mt_srand(20260801);
        $rand = fn () => mt_rand() / mt_getrandmax();
        $out = [];
        $n = count(self::SPECS);
        foreach (self::SPECS as $idx => [$date, $time, $sym, $side, $strategy, $r, $emotion, $mistake, $rules, $note]) {
            $inst = Instruments::get($sym);
            [$base, $drift, $stopD, $lot] = self::MODEL[$sym];
            $dec = $inst['decimals'];
            $entry = round($base + $drift * ($idx / $n - 0.5) + ($rand() - 0.5) * $drift * 0.2, $dec);
            $dist = round($stopD * (0.8 + $rand() * 0.4), $dec);
            $stop = $side === 'LONG' ? $entry - $dist : $entry + $dist;
            $exit = round(TradeMath::exitFromR($side, $entry, $stop, $r), $dec);
            $tp = round(TradeMath::exitFromR($side, $entry, $stop, max(2, ceil(abs($r)))), $dec);
            $lots = round($lot * ($mistake === 'OVERSIZED' ? 2.5 : 1) * (0.85 + $rand() * 0.3), 2);
            $exec = "$date $time:00";
            $closed = gmdate('Y-m-d H:i:s', strtotime("$exec UTC") + (12 + (int) floor($rand() * 140)) * 60);
            $out[] = [
                'executed_at' => $exec, 'closed_at' => $closed, 'symbol' => $sym, 'side' => $side, 'entry' => $entry, 'stop' => round($stop, $dec), 'tp' => $tp,
                'exit' => $exit, 'lots' => $lots, 'strategy' => $strategy, 'emotion' => $emotion, 'mistake' => $mistake, 'rules' => $rules, 'notes' => '[DEMO DATA] ' . $note,
            ];
        }
        return $out;
    }
}
