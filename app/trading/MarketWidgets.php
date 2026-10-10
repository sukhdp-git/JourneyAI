<?php
declare(strict_types=1);

namespace App\Trading;

/**
 * Free TradingView embed widgets (ticker tape, heatmaps, news, economic calendar). They are display-only: the
 * browser loads TradingView's script, journzey.ai never receives or stores the prices. Data delays depend on the
 * exchange and are labelled by TradingView inside each widget.
 */
final class MarketWidgets
{
    private const BASE = 'https://s3.tradingview.com/external-embedding/embed-widget-';

    /** Most-traded instruments: gold, majors, US indices, oil, crypto. "SYMBOL | Title" per line. */
    public const DEFAULT_TICKER = "OANDA:XAUUSD | Gold\nOANDA:XAGUSD | Silver\nFX:EURUSD | EUR/USD\nFX:GBPUSD | GBP/USD\nFX:USDJPY | USD/JPY\nFX:AUDUSD | AUD/USD\nFX:USDCAD | USD/CAD\nOANDA:NAS100USD | Nasdaq 100\nOANDA:SPX500USD | S&P 500\nOANDA:US30USD | Dow 30\nOANDA:DE30EUR | DAX 40\nTVC:USOIL | WTI Crude\nBITSTAMP:BTCUSD | Bitcoin\nBITSTAMP:ETHUSD | Ethereum\nBINANCE:SOLUSDT | Solana";
    public const DEFAULT_COUNTRIES = 'us,eu,gb,jp,cn,in,au,ca,ch';

    public static function enabled(): bool
    {
        return setting_on('tv_widgets_enabled', true);
    }

    /** @return list<array{proName:string,title:string}> */
    public static function tickerSymbols(): array
    {
        $out = [];
        foreach (preg_split('/\R/', setting('tv_ticker_symbols') ?: self::DEFAULT_TICKER) as $line) {
            [$sym, $title] = array_map('trim', array_pad(explode('|', $line, 2), 2, ''));
            if (preg_match('/^[A-Z0-9_.!]{1,20}:[A-Z0-9_.!]{1,30}$/i', $sym)) {
                $out[] = ['proName' => strtoupper($sym), 'title' => mb_substr($title !== '' ? $title : explode(':', $sym)[1], 0, 30)];
            }
            if (count($out) >= 30) {
                break;
            }
        }
        return $out;
    }

    public static function countries(): string
    {
        $c = array_filter(array_map('trim', explode(',', strtolower(setting('tv_calendar_countries') ?: self::DEFAULT_COUNTRIES))), fn ($x) => preg_match('/^[a-z]{2}$/', $x));
        return implode(',', $c ?: explode(',', self::DEFAULT_COUNTRIES));
    }

    /** TradingView locale codes for the terminal languages. */
    public static function locale(?string $lang): string
    {
        return ['ru' => 'ru', 'zh' => 'zh_CN', 'pt' => 'br'][$lang ?? 'en'] ?? 'en';
    }

    /** @return array{src:string,config:array} */
    public static function widget(string $type, string $theme, string $lang, array $opt = []): array
    {
        // Dark theme: TradingView paints its own dark background (opaque), so text contrast never depends on how the
        // browser composites a transparent cross-site frame. Light theme blends into the panel (transparent).
        $light = $theme === 'clean-light';
        $common = ['colorTheme' => $light ? 'light' : 'dark', 'isTransparent' => $light, 'locale' => self::locale($lang)];
        $map = [
            'chart' => ['advanced-chart', ['autosize' => true, 'symbol' => preg_match('/^[A-Z0-9_.!]{1,20}:[A-Z0-9_.!]{1,30}$/', (string) ($opt['symbol'] ?? '')) ? $opt['symbol'] : 'OANDA:XAUUSD',
                'interval' => '15', 'timezone' => in_array($opt['tz'] ?? '', \DateTimeZone::listIdentifiers(), true) ? $opt['tz'] : 'Etc/UTC', 'theme' => $light ? 'light' : 'dark', 'style' => '1', 'allow_symbol_change' => true, 'hide_side_toolbar' => false,
                'hide_top_toolbar' => false, 'withdateranges' => true, 'details' => true, 'hotlist' => false, 'calendar' => false, 'save_image' => true,
                'backgroundColor' => $light ? 'rgba(255, 255, 255, 1)' : 'rgba(15, 15, 15, 1)', 'gridColor' => $light ? 'rgba(46, 46, 46, 0.06)' : 'rgba(242, 242, 242, 0.06)',
                'support_host' => 'https://www.tradingview.com']],
            'ticker' => ['ticker-tape', ['symbols' => self::tickerSymbols(), 'showSymbolLogo' => true, 'displayMode' => 'adaptive']],
            'stocks' => ['stock-heatmap', ['exchanges' => [], 'dataSource' => in_array($opt['source'] ?? '', ['SPX500', 'NASDAQ100'], true) ? $opt['source'] : 'SPX500', 'grouping' => 'sector',
                'blockSize' => 'market_cap_basic', 'blockColor' => 'change', 'symbolUrl' => '', 'hasTopBar' => true, 'isDataSetEnabled' => false, 'isZoomEnabled' => true,
                'hasSymbolTooltip' => true, 'isMonoSize' => false, 'width' => '100%', 'height' => '100%']],
            'crypto' => ['crypto-coins-heatmap', ['dataSource' => 'Crypto', 'blockSize' => 'market_cap_calc', 'blockColor' => 'change', 'symbolUrl' => '', 'hasTopBar' => true,
                'isDataSetEnabled' => false, 'isZoomEnabled' => true, 'hasSymbolTooltip' => true, 'isMonoSize' => false, 'width' => '100%', 'height' => '100%']],
            'news' => ['timeline', ['feedMode' => 'all_symbols', 'displayMode' => 'regular', 'width' => '100%', 'height' => '100%']],
            'calendar' => ['events', ['importanceFilter' => '0,1', 'countryFilter' => self::countries(), 'width' => '100%', 'height' => '100%']],
        ];
        [$file, $cfg] = $map[$type];
        return ['src' => self::BASE . $file . '.js', 'config' => $cfg + $common];
    }
}
