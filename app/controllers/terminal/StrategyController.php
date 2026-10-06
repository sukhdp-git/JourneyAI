<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Trading\Analytics;
use App\Trading\Ledger;

/** Strategy playbooks and per-strategy analytics. */
final class StrategyController extends TerminalController
{
    public function index(Request $req): never
    {
        $trades = Ledger::forAnalytics($this->uid, (int) $this->acc['id'], $this->tz);
        $groups = [];
        foreach (Analytics::groupBy($trades, fn ($t) => (string) ($t['strategy_id'] ?? '')) as $g) {
            $groups[$g['key']] = $g['s'];
        }
        $this->render('strategies', ['strategies' => Ledger::strategies($this->uid), 'stats' => $groups, 'unassigned' => Analytics::summarize(array_filter($trades, fn ($t) => !$t['strategy_id']))], 'Strategy Analysis', 'strategies');
    }

    private function input(): array
    {
        $name = mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 80);
        $rr = trim((string) ($_POST['target_rr'] ?? ''));
        return ['name' => $name, 'description' => mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 500) ?: null,
            'target_rr' => is_numeric($rr) && (float) $rr > 0 && (float) $rr < 100 ? round((float) $rr, 2) : null,
            'checklist' => mb_substr(trim((string) ($_POST['checklist'] ?? '')), 0, 2000) ?: null, 'active' => ($_POST['active'] ?? '1') === '1' ? 1 : 0];
    }

    public function store(Request $req): never
    {
        $d = $this->input();
        if ($d['name'] === '' || Database::value('SELECT id FROM strategies WHERE user_id = :u AND name = :n', ['u' => $this->uid, 'n' => $d['name']])) {
            $this->back('/terminal/strategies', 'error', $d['name'] === '' ? 'Give the strategy a name.' : 'You already have a strategy with that name.');
        }
        Database::insert('strategies', $d + ['user_id' => $this->uid]);
        $this->back('/terminal/strategies', 'success', 'Strategy created.');
    }

    public function update(Request $req): never
    {
        $s = Database::one('SELECT id FROM strategies WHERE id = :id AND user_id = :u', ['id' => (int) $req->params['id'], 'u' => $this->uid]) ?? Response::abort(404);
        $d = $this->input();
        if ($d['name'] === '' || Database::value('SELECT id FROM strategies WHERE user_id = :u AND name = :n AND id <> :id', ['u' => $this->uid, 'n' => $d['name'], 'id' => $s['id']])) {
            $this->back('/terminal/strategies', 'error', 'Strategy names must be unique and not empty.');
        }
        Database::update('strategies', $d, 'id = :id AND user_id = :u', ['id' => $s['id'], 'u' => $this->uid]);
        $this->back('/terminal/strategies#s' . $s['id'], 'success', 'Strategy saved.');
    }

    public function delete(Request $req): never
    {
        Database::delete('strategies', 'id = :id AND user_id = :u', ['id' => (int) $req->params['id'], 'u' => $this->uid]);
        $this->back('/terminal/strategies', 'success', 'Strategy deleted. Its trades are kept and marked unassigned.');
    }
}
