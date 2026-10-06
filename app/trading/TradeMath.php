<?php
declare(strict_types=1);

namespace App\Trading;

/**
 * Server-authoritative trade calculations. Results are rounded to the storage precision
 * (money 2 dp, R 4 dp) and stored in DECIMAL columns.
 *  risk distance = |entry − stop|
 *  LONG exit  = entry + risk × R,  SHORT exit = entry − risk × R
 *  R = (exit − entry) × direction / risk
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
     * Converts a quote-currency amount to the account currency using only the trade's own price.
     * Returns null when not derivable (the member must then enter the broker-reported P&L).
     */
    public static function toAccount(array $inst, float $amountQuote, float $refPrice, string $accountCurrency): ?float
    {
        $acc = strtoupper($accountCurrency);
        if ($inst['quote'] === $acc) {
            return $amountQuote;
        }
        if ($inst['base'] === $acc && $refPrice != 0.0) {
            return $amountQuote / $refPrice;
        }
        return null;
    }

    /** @return array{pnl:?string, rr:?string, risk:?string} */
    public static function compute(string $symbol, string $side, float $entry, ?float $exit, ?float $stop, float $lots, float $fees, string $accountCurrency): array
    {
        $inst = Instruments::get($symbol);
        $out = ['pnl' => null, 'rr' => null, 'risk' => null];
        if (!$inst) {
            return $out;
        }
        if ($exit !== null) {
            $gross = ($exit - $entry) * self::dir($side) * $lots * $inst['contract'];
            $conv = self::toAccount($inst, $gross, $exit, $accountCurrency);
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
            $riskA = self::toAccount($inst, $riskQ, $stop, $accountCurrency);
            if ($riskA !== null && $riskA > 0) {
                $out['risk'] = self::money($riskA);
            }
        }
        return $out;
    }

    /** Lot size for a given account risk %. */
    public static function lotSize(string $symbol, float $equity, float $riskPct, float $entry, float $stop, string $accountCurrency): ?array
    {
        $inst = Instruments::get($symbol);
        if (!$inst || $entry == $stop) {
            return null;
        }
        $riskAmount = $equity * $riskPct / 100;
        $perLot = self::toAccount($inst, abs($entry - $stop) * $inst['contract'], $entry, $accountCurrency);
        if (!$perLot) {
            return null;
        }
        $lots = floor($riskAmount / $perLot * 100) / 100;
        return ['lots' => number_format($lots, 2, '.', ''), 'risk_amount' => self::money($riskAmount), 'per_lot_risk' => self::money($perLot)];
    }

    public static function money(float $v): string
    {
        return number_format(round($v + 0.0, 2), 2, '.', '');
    }
}
