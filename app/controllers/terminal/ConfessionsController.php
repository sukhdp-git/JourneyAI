<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Trading\Confessions;
use App\Trading\Members;

/**
 * Confessions: the anonymous trader feed. Paying members read, post, like, comment and share under a unique
 * @handle; free members see a locked preview. Every write goes through the spam shield in Confessions::guard().
 */
final class ConfessionsController extends TerminalController
{
    private function paid(): bool
    {
        return Members::entitlements($this->m)['paid'];
    }

    /** JSON endpoints: paid members with a handle only. */
    private function gate(): array
    {
        if (!$this->paid()) {
            Response::json(['ok' => false, 'error' => 'Confessions are for members with an active plan.'], 403);
        }
        $p = Confessions::profile($this->uid);
        if (!$p) {
            Response::json(['ok' => false, 'error' => 'Create your anonymous handle first.'], 403);
        }
        return $p;
    }

    public function legacy(Request $req): never
    {
        Response::redirect(url('/terminal/confessions'));
    }

    public function index(Request $req): never
    {
        $tab = in_array($req->query('tab'), ['latest', 'top', 'mine'], true) ? (string) $req->query('tab') : 'latest';
        $tag = isset(Confessions::TAGS[(string) $req->query('tag')]) ? (string) $req->query('tag') : null;
        if (!$this->paid()) {
            $this->render('confessions/locked', [], 'Confessions', 'confess');
        }
        $prof = Confessions::profile($this->uid);
        if (!$prof) {
            $this->render('confessions/handle', ['suggest' => Confessions::suggestHandle(), 'prof' => null], 'Confessions', 'confess');
        }
        [$rows, $more] = Confessions::feed($this->uid, $tab, $tag);
        $this->render('confessions/index', [
            'prof' => $prof, 'stats' => Confessions::stats($this->uid), 'rows' => $rows, 'more' => $more, 'tab' => $tab, 'tag' => $tag,
            'trades' => Confessions::attachable($this->uid),
        ], 'Confessions', 'confess');
    }

    /** "Load more" fragment (HTML cards) for infinite scroll. */
    public function more(Request $req): never
    {
        $this->gate();
        $tab = in_array($req->query('tab'), ['latest', 'top', 'mine'], true) ? (string) $req->query('tab') : 'latest';
        $tag = isset(Confessions::TAGS[(string) $req->query('tag')]) ? (string) $req->query('tag') : null;
        [$rows, $more] = Confessions::feed($this->uid, $tab, $tag, max(0, (int) $req->query('before', 0)));
        $html = '';
        foreach ($rows as $i => $c) {
            $html .= View::partial('terminal/confessions/card', ['c' => $c, 'me' => $this->uid, 'i' => $i]);
        }
        $last = $rows ? end($rows) : null;
        Response::json(['ok' => true, 'html' => $html, 'more' => $more, 'next' => $tab === 'top' ? (int) $req->query('before', 0) + count($rows) : ($last ? (int) $last['id'] : 0)]);
    }

    public function handle(Request $req): never
    {
        if (!$this->paid()) {
            $this->back(url('/terminal/confessions'), 'error', 'Confessions are for members with an active plan.');
        }
        $h = (string) $req->post('handle', '');
        if ($e = Confessions::saveHandle($this->uid, $h)) {
            $this->back(url('/terminal/confessions' . (Confessions::profile($this->uid) ? '?settings=1' : '')), 'error', $e);
        }
        $this->back(url('/terminal/confessions'), 'success', 'You are @' . strtolower(ltrim(trim($h), '@')) . '. Welcome to Confessions.');
    }

    public function store(Request $req): never
    {
        $this->gate();
        [$err, $id] = Confessions::post($this->uid, ['body' => $req->str('body', 2000), 'tag' => $req->str('tag', 20), 'mood' => $req->str('mood', 16), 'trade_id' => $req->int('trade_id')]);
        if ($err) {
            Response::json(['ok' => false, 'error' => $err], 422);
        }
        $c = Confessions::one($this->uid, $id);
        Response::json(['ok' => true, 'id' => $id, 'html' => View::partial('terminal/confessions/card', ['c' => $c, 'me' => $this->uid, 'i' => 0, 'fresh' => true])]);
    }

    public function show(Request $req): never
    {
        $id = (int) ($req->params['id'] ?? 0);
        if (!$this->paid()) {
            $this->render('confessions/locked', [], 'Confessions', 'confess');
        }
        $prof = Confessions::profile($this->uid);
        if (!$prof) {
            $this->render('confessions/handle', ['suggest' => Confessions::suggestHandle(), 'prof' => null], 'Confessions', 'confess');
        }
        $c = Confessions::one($this->uid, $id);
        if (!$c) {
            $this->back(url('/terminal/confessions'), 'error', 'That confession is no longer available.');
        }
        $this->render('confessions/show', ['c' => $c, 'prof' => $prof], 'Confession', 'confess');
    }

    public function like(Request $req): never
    {
        $id = (int) ($req->params['id'] ?? 0);
        $this->gate();
        $r = Confessions::like($this->uid, $id);
        $r ? Response::json(['ok' => true] + $r) : Response::json(['ok' => false, 'error' => 'Not available.'], 404);
    }

    public function comments(Request $req): never
    {
        $id = (int) ($req->params['id'] ?? 0);
        $this->gate();
        if (!Confessions::one($this->uid, $id)) {
            Response::json(['ok' => false, 'error' => 'Not available.'], 404);
        }
        $html = '';
        foreach (Confessions::comments($this->uid, $id) as $m) {
            $html .= View::partial('terminal/confessions/comment', ['m' => $m, 'me' => $this->uid]);
        }
        Response::json(['ok' => true, 'html' => $html]);
    }

    public function comment(Request $req): never
    {
        $id = (int) ($req->params['id'] ?? 0);
        $this->gate();
        [$err, $cid] = Confessions::comment($this->uid, $id, $req->str('body', 1000));
        if ($err) {
            Response::json(['ok' => false, 'error' => $err], 422);
        }
        $m = array_values(array_filter(Confessions::comments($this->uid, $id), fn ($x) => (int) $x['id'] === $cid))[0] ?? null;
        Response::json(['ok' => true, 'html' => $m ? View::partial('terminal/confessions/comment', ['m' => $m, 'me' => $this->uid, 'fresh' => true]) : '']);
    }

    public function likeComment(Request $req): never
    {
        $cid = (int) ($req->params['cid'] ?? 0);
        $this->gate();
        $r = Confessions::likeComment($this->uid, $cid);
        $r ? Response::json(['ok' => true] + $r) : Response::json(['ok' => false, 'error' => 'Not available.'], 404);
    }

    public function report(Request $req): never
    {
        $id = (int) ($req->params['id'] ?? 0);
        $this->gate();
        $ok = Confessions::report($this->uid, $req->str('type', 10), $id, $req->str('reason', 20));
        Response::json(['ok' => $ok, 'message' => $ok ? 'Thanks. The team will review it.' : 'You cannot report this.']);
    }

    public function delete(Request $req): never
    {
        $id = (int) ($req->params['id'] ?? 0);
        $this->gate();
        $ok = $req->str('type', 10) === 'comment' ? Confessions::removeComment($this->uid, $id) : Confessions::remove($this->uid, $id);
        Response::json(['ok' => $ok]);
    }

    public function share(Request $req): never
    {
        $id = (int) ($req->params['id'] ?? 0);
        $this->gate();
        Confessions::shared($id);
        Response::json(['ok' => true]);
    }
}
