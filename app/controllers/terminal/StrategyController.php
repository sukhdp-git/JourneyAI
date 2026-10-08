<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Learn;
use App\Trading\Analytics;
use App\Trading\Domain;
use App\Trading\Edge;
use App\Trading\Ledger;

/** Personal strategy builder and visual strategy analytics (scoreboard, session win rates). */
final class StrategyController extends TerminalController
{
    public const SORTS = ['net' => 'Net P&L', 'win' => 'Win rate', 'pf' => 'Profit factor', 'avgr' => 'Average R'];

    public function index(Request $req): never
    {
        $trades = Ledger::forAnalytics($this->uid, (int) $this->acc['id'], $this->tz);
        $strategies = Ledger::strategies($this->uid);
        $stats = [];
        foreach (Analytics::groupBy($trades, fn ($t) => (string) ($t['strategy_id'] ?? '')) as $g) {
            $stats[$g['key']] = $g['s'];
        }
        $sort = isset(self::SORTS[$req->query('sort')]) ? (string) $req->query('sort') : 'net';
        $filter = ctype_digit((string) $req->query('s', '')) ? (int) $req->query('s') : null;
        $sessTrades = $filter ? array_filter($trades, fn ($t) => (int) $t['strategy_id'] === $filter) : $trades;
        $this->render('strategies', [
            'strategies' => $strategies, 'stats' => $stats, 'scoreboard' => Edge::scoreboard($trades, $sort), 'sort' => $sort,
            'sessions' => Edge::sessionStats($sessTrades), 'filter' => $filter, 'sum' => Analytics::summarize($trades),
        ], 'Strategy Analysis', 'strategies');
    }

    public function create(Request $req): never
    {
        $this->render('strategy-form', ['s' => null], 'Add personal strategy', 'strategies');
    }

    public function edit(Request $req): never
    {
        $s = Database::one('SELECT * FROM strategies WHERE id = :id AND user_id = :u', ['id' => (int) $req->params['id'], 'u' => $this->uid]) ?? Response::abort(404);
        $this->render('strategy-form', ['s' => $s], 'Edit strategy', 'strategies');
    }

    /** Accepts "1:3", "3" or "3R" as the target risk-to-reward. */
    private static function parseRr(string $v): ?float
    {
        $v = trim(strtolower(str_replace(' ', '', $v)));
        if (preg_match('/^(\d+(?:\.\d+)?):(\d+(?:\.\d+)?)$/', $v, $m)) {
            return (float) $m[1] > 0 ? round((float) $m[2] / (float) $m[1], 2) : null;
        }
        return preg_match('/^(\d+(?:\.\d+)?)r?$/', $v, $m) && (float) $m[1] > 0 && (float) $m[1] < 100 ? round((float) $m[1], 2) : null;
    }

    private static function lines(string $text, int $max, int $len): ?string
    {
        $items = array_slice(array_values(array_filter(array_map(fn ($l) => mb_substr(trim($l), 0, $len), preg_split('/\R/', $text)), 'strlen')), 0, $max);
        return $items ? implode("\n", $items) : null;
    }

    private function input(): array
    {
        $rr = trim((string) ($_POST['target_rr'] ?? ''));
        return [
            'name' => mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 80),
            'target_rr' => $rr === '' ? null : self::parseRr($rr),
            'style' => isset(Domain::STRATEGY_STYLES[$_POST['style'] ?? '']) ? $_POST['style'] : null,
            'thesis' => mb_substr(trim((string) ($_POST['thesis'] ?? '')), 0, 5000) ?: null,
            'description' => mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 500) ?: null,
            'checklist' => self::lines((string) ($_POST['checklist'] ?? ''), 40, 200),
            'setups' => self::lines((string) ($_POST['setups'] ?? ''), 20, 80),
            'active' => ($_POST['active'] ?? '1') === '1' ? 1 : 0,
        ];
    }

    private function validate(array $d, ?int $id, string $back): void
    {
        $errors = [];
        if ($d['name'] === '') {
            $errors['name'] = 'Give the strategy a name.';
        } elseif (Database::value('SELECT id FROM strategies WHERE user_id = :u AND name = :n' . ($id ? ' AND id <> :id' : ''), ['u' => $this->uid, 'n' => $d['name']] + ($id ? ['id' => $id] : []))) {
            $errors['name'] = 'You already have a strategy with that name.';
        }
        if (trim((string) ($_POST['target_rr'] ?? '')) !== '' && $d['target_rr'] === null) {
            $errors['target_rr'] = 'Use a format like 1:3 or 3.';
        }
        if ($errors) {
            Session::withErrors($errors, $_POST);
            Response::redirect($back);
        }
    }

    public function store(Request $req): never
    {
        $d = $this->input();
        $this->validate($d, null, '/terminal/strategies/new');
        $id = Database::insert('strategies', $d + ['user_id' => $this->uid]);
        $this->back('/terminal/strategies#s' . $id, 'success', 'Strategy “' . $d['name'] . '” created. Tag trades with it to start building its statistics.');
    }

    public function update(Request $req): never
    {
        $s = Database::one('SELECT id FROM strategies WHERE id = :id AND user_id = :u', ['id' => (int) $req->params['id'], 'u' => $this->uid]) ?? Response::abort(404);
        $d = $this->input();
        $this->validate($d, (int) $s['id'], '/terminal/strategies/' . $s['id'] . '/edit');
        Database::update('strategies', $d, 'id = :id AND user_id = :u', ['id' => $s['id'], 'u' => $this->uid]);
        $this->back('/terminal/strategies#s' . $s['id'], 'success', 'Strategy saved.');
    }

    /** Copies a strategy from the public Learning playbook into the member's personal strategies. */
    public function import(Request $req): never
    {
        $p = Learn::find((string) ($req->params['slug'] ?? '')) ?? Response::abort(404);
        $existing = Database::one('SELECT id FROM strategies WHERE user_id = :u AND name = :n', ['u' => $this->uid, 'n' => mb_substr($p['title'], 0, 80)]);
        if ($existing) {
            $this->back('/terminal/strategies/' . $existing['id'] . '/edit', 'info', '“' . $p['title'] . '” is already in your strategies.');
        }
        $checklist = Learn::lines($p['setup_rules']);
        foreach (['Entry' => $p['entry_trigger'], 'Stop' => $p['stop_loss']] as $k => $v) {
            if (trim((string) $v) !== '') {
                $checklist[] = $k . ': ' . trim((string) $v);
            }
        }
        foreach (Learn::lines($p['take_profit']) as $t) {
            $checklist[] = 'Target: ' . $t;
        }
        $id = Database::insert('strategies', [
            'user_id' => $this->uid,
            'name' => mb_substr($p['title'], 0, 80),
            'target_rr' => $p['rr_value'] !== null && (float) $p['rr_value'] > 0 ? (float) $p['rr_value'] : null,
            'style' => isset(Domain::STRATEGY_STYLES[$p['style'] ?? '']) ? $p['style'] : null,
            'thesis' => mb_substr((string) $p['logic'], 0, 5000) ?: null,
            'description' => mb_substr((string) $p['summary'], 0, 500) ?: null,
            'checklist' => implode("\n", array_slice(array_map(fn ($l) => mb_substr($l, 0, 200), $checklist), 0, 40)) ?: null,
            'setups' => null,
            'active' => 1,
        ]);
        $this->back('/terminal/strategies/' . $id . '/edit', 'success', '“' . $p['title'] . '” was added to your strategies. Adjust the rules to fit your own plan, then tag trades with it.');
    }

    public function delete(Request $req): never
    {
        Database::delete('strategies', 'id = :id AND user_id = :u', ['id' => (int) $req->params['id'], 'u' => $this->uid]);
        $this->back('/terminal/strategies', 'success', 'Strategy deleted. Its trades are kept and marked unassigned.');
    }
}
