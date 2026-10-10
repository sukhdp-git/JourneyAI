<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Request;
use App\Core\Response;
use App\Trading\MarketWidgets;

/** Markets: TradingView heatmaps, news and economic calendar (display-only third-party widgets). */
final class MarketsController extends TerminalController
{
    public const TABS = ['stocks' => ['Stock heatmap', 'grid'], 'crypto' => ['Crypto heatmap', 'layers'], 'news' => ['News', 'file'], 'calendar' => ['Economic calendar', 'calendar']];

    public function index(Request $req): never
    {
        if (!MarketWidgets::enabled()) {
            Response::abort(404);
        }
        $tab = (string) $req->query('tab', 'stocks');
        $this->render('markets', ['tab' => isset(self::TABS[$tab]) ? $tab : 'stocks', 'tabs' => self::TABS], 'Markets', 'markets');
    }
}
