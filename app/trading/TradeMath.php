<?php
declare(strict_types=1);

namespace App\Trading;

/**
 * Server-authoritative trade calculations. Results are rounded to the storage precision
 * (money 2 dp, R 4 dp) and stored in DECIMAL columns.
 *  risk distance   = |entry − stop|
 *  reward distance = |take profit − entry|
 *  R:R             = reward distance / risk distance
 *  LONG exit  = entry + risk × R,  SHORT exit = entry − risk × R
 *  P&L (quote ccy) = (exit − entry) × direction × lots × contract size
 *  lots for a risk = risk amount / (|entry − stop| × contract size × quote→account rate)
 */
final class TradeMath
{
    public static function dir(string $side): int
    {
        return $side === 'SHORT' ? -1 : 1;
    }

    public static function exitFromR(string $side, float $entry, float $stop, float $r): float
    {
        return $entry + abs($entry - $stop) * $r * self::dir($side);
    }

    public static function realisedR(string $side, float $entry, float $stop, float $exit): ?float
    {
        $risk = abs($entry - $stop);
        return $risk == 0.0 ? null : ($exit - $entry) * self::dir($side) / $risk;
    }

    /**
     * Multiplier that converts an amount in the instrument's quote currency to the account currency.
     * Uses the trade's own price when the account currency is the base (e.g. USDJPY in a USD account);
     * otherwise the caller must supply a conversion rate ("1 quote = rate account"). Null when unknown.
     */
    public static function quoteToAccount(array $inst, float $refPrice, string $accountCurrency, ?float $rate = null): ?float
    {
        $acc = strtoupper($accountCurrency);
        if ($inst['quote'] === $acc) {
            return 1.0;
        }
        if ($inst['base'] === $acc && $refPrice != 0.0) {
            return 1 / $refPrice;
        }
        return $rate !== null && $rate > 0 ? $rate : null;
    }

    public static function toAccount(array $inst, float $amountQuote, float $refPrice, string $accountCurrency, ?float $rate = null): ?float
    {
        $m = self::quoteToAccount($inst, $refPrice, $accountCurrency, $rate);
        return $m === null ? null : $amountQuote * $m;
    }

    /** @return array{pnl:?string, rr:?string, risk:?string} */
    public static function compute(string $symbol, string $side, float $entry, ?float $exit, ?float $stop, float $lots, float $fees, string $accountCurrency, ?float $rate = null): array
    {
        $inst = Instruments::get($symbol);
        $out = ['pnl' => null, 'rr' => null, 'risk' => null];
        if (!$inst) {
            return $out;
        }
        if ($exit !== null) {
            $gross = ($exit - $entry) * self::dir($side) * $lots * $inst['contract'];
            $conv = self::toAccount($inst, $gross, $exit, $accountCurrency, $rate);
            if ($conv !== null) {
                $out['pnl'] = self::money($conv - $fees);
            }
        }
        if ($stop !== null) {
            if ($exit !== null) {
                $r = self::realisedR($side, $entry, $stop, $exit);
                $out['rr'] = $r === null ? null : number_format($r, 4, '.', '');
            }
            $riskQ = abs($entry - $stop) * $lots * $inst['contract'];
            $riskA = self::toAccount($inst, $riskQ, $stop, $accountCurrency, $rate);
            if ($riskA !== null && $riskA > 0) {
                $out['risk'] = self::money($riskA);
            }
        }
        return $out;
    }

    /** Checks that stop and target sit on the correct side of entry for the direction. Returns error messages. */
    public static function validatePlacement(string $side, float $entry, ?float $stop, ?float $target, string $targetLabel = 'Take profit'): array
    {
        $e = [];
        if ($entry <= 0) {
            $e[] = 'Entry price must be positive.';
        }
        if ($stop !== null) {
            if ($stop <= 0) {
                $e[] = 'Stop loss must be positive.';
            } elseif ($stop == $entry) {
                $e[] = 'Stop loss cannot equal entry.';
            } elseif ($side === 'LONG' && $stop > $entry) {
                $e[] = 'For a BUY, the stop loss must be below entry.';
            } elseif ($side === 'SHORT' && $stop < $entry) {
                $e[] = 'For a SELL, the stop loss must be above entry.';
            }
        }
        if ($target !== null && $targetLabel !== 'Exit') {
            if ($target <= 0) {
                $e[] = $targetLabel . ' must be positive.';
            } elseif ($side === 'LONG' && $target <= $entry) {
                $e[] = 'For a BUY, the ' . strtolower($targetLabel) . ' must be above entry.';
            } elseif ($side === 'SHORT' && $target >= $entry) {
                $e[] = 'For a SELL, the ' . strtolower($targetLabel) . ' must be below entry.';
            }
        }
        return $e;
    }

    /**
     * Mode A — P&L and R:R for a planned or finished trade.
     * $target is the exit price (finished trade) or take profit (plan); both give the same maths.
     */
    public static function pnlCalc(string $symbol, string $side, float $entry, float $stop, float $target, float $lots, string $accountCurrency, ?float $rate = null, string $targetLabel = 'Take profit'): array
    {
        $inst = Instruments::get($symbol);
        if (!$inst) {
            return ['ok' => false, 'errors' => ['Choose a supported instrument.']];
        }
        $errors = self::validatePlacement($side, $entry, $stop, $target, $targetLabel);
        if ($lots <= 0) {
            $errors[] = 'Lot size must be positive.';
        }
        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }
        $mult = self::quoteToAccount($inst, $target, $accountCurrency, $rate);
        if ($mult === null) {
            return ['ok' => false, 'needs_rate' => true, 'quote' => $inst['quote'], 'errors' => ['Enter the conversion rate: 1 ' . $inst['quote'] . ' = ? ' . strtoupper($accountCurrency) . '.']];
        }
        $riskDist = abs($entry - $stop);
        $rewardDist = abs($target - $entry);
        $pnl = ($target - $entry) * self::dir($side) * $lots * $inst['contract'] * $mult;
        $risk = $riskDist * $lots * $inst['contract'] * $mult;
        $pipDiv = $inst['pip'] > 0 ? $inst['pip'] : 1;
        return [
            'ok' => true, 'pnl' => round($pnl, 2), 'risk' => round($risk, 2), 'reward' => round($rewardDist * $lots * $inst['contract'] * $mult, 2),
            'rr' => $riskDist > 0 ? round($rewardDist / $riskDist, 2) : null, 'r_multiple' => round(self::realisedR($side, $entry, $stop, $target) ?? 0, 2),
            'stop_distance' => round($riskDist / $pipDiv, 1), 'target_distance' => round($rewardDist / $pipDiv, 1), 'unit' => Instruments::distanceLabel($symbol),
            'pip_value' => round($inst['pip'] * $inst['contract'] * $mult, 4), 'conversion' => $mult,
        ];
    }

    /**
     * Mode B — recommended position size for a maximum risk amount (account currency).
     * Lots are rounded DOWN to the instrument's lot step so the actual risk never exceeds the budget.
     */
    public static function positionSize(string $symbol, string $side, float $entry, float $stop, ?float $tp, float $riskAmount, float $capital, string $accountCurrency, ?float $rate = null): array
    {
        $inst = Instruments::get($symbol);
        if (!$inst) {
            return ['ok' => false, 'errors' => ['Choose a supported instrument.']];
        }
        $errors = self::validatePlacement($side, $entry, $stop, $tp);
        if ($riskAmount <= 0) {
            $errors[] = 'The risk amount must be positive.';
        }
        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }
        $mult = self::quoteToAccount($inst, $entry, $accountCurrency, $rate);
        if ($mult === null) {
            return ['ok' => false, 'needs_rate' => true, 'quote' => $inst['quote'], 'errors' => ['Enter the conversion rate: 1 ' . $inst['quote'] . ' = ? ' . strtoupper($accountCurrency) . '.']];
        }
        $riskDist = abs($entry - $stop);
        $perLot = $riskDist * $inst['contract'] * $mult;
        $raw = $perLot > 0 ? $riskAmount / $perLot : 0;
        $step = $inst['lot_step'] > 0 ? $inst['lot_step'] : 0.01;
        $lots = floor($raw / $step + 1e-9) * $step;
        $lots = round($lots, max(0, (int) -floor(log10($step))));
        $actualRisk = $lots * $perLot;
        $pipDiv = $inst['pip'] > 0 ? $inst['pip'] : 1;
        $out = [
            'ok' => true, 'lots' => $lots, 'raw_lots' => round($raw, 4), 'below_min' => $lots < $inst['min_lot'], 'min_lot' => $inst['min_lot'], 'lot_step' => $step,
            'risk_budget' => round($riskAmount, 2), 'risk_actual' => round($actualRisk, 2), 'per_lot_risk' => round($perLot, 2),
            'stop_distance' => round($riskDist / $pipDiv, 1), 'unit' => Instruments::distanceLabel($symbol),
            'loss_pct' => $capital > 0 ? round($actualRisk / $capital * 100, 2) : null, 'profit' => null, 'rr' => null, 'gain_pct' => null,
        ];
        if ($tp !== null) {
            $rewardDist = abs($tp - $entry);
            $profit = $rewardDist * $lots * $inst['contract'] * $mult;
            $out['profit'] = round($profit, 2);
            $out['rr'] = $riskDist > 0 ? round($rewardDist / $riskDist, 2) : null;
            $out['gain_pct'] = $capital > 0 ? round($profit / $capital * 100, 2) : null;
            $out['target_distance'] = round($rewardDist / $pipDiv, 1);
        }
        return $out;
    }

    public static function money(float $v): string
    {
        return number_format(round($v + 0.0, 2), 2, '.', '');
    }
}
