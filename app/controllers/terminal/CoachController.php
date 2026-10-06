<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Trading\AiCoach;
use App\Trading\Domain;
use App\Trading\Ledger;
use App\Trading\Members;

/** AI Coach: chat grounded in the member's aggregated statistics, plus weekly/monthly reviews. */
final class CoachController extends TerminalController
{
    private const PROMPTS = [
        'What is my biggest weakness right now?',
        'Which setup has the best expectancy and why?',
        'How do my emotions affect my results?',
        'Which session should I focus on?',
        'Am I cutting winners too early?',
        'Give me three rules for next week based on my data.',
    ];

    private function usedToday(): int
    {
        return (int) Database::value("SELECT COUNT(*) FROM ai_messages WHERE user_id = :u AND role = 'user' AND created_at >= :d", ['u' => $this->uid, 'd' => gmdate('Y-m-d 00:00:00')]);
    }

    private function remaining(): int
    {
        return max(0, (int) $this->m['ent']['ai_daily'] - $this->usedToday());
    }

    public function index(Request $req): never
    {
        $id = $req->params['id'] ?? null;
        $conv = $id ? Database::one('SELECT * FROM ai_conversations WHERE id = :id AND user_id = :u', ['id' => (int) $id, 'u' => $this->uid]) : null;
        if ($id && !$conv) {
            Response::redirect('/terminal/coach');
        }
        $this->render('coach', [
            'configured' => AiCoach::configured(),
            'conversations' => Database::all('SELECT id, title, updated_at FROM ai_conversations WHERE user_id = :u ORDER BY updated_at DESC LIMIT 50', ['u' => $this->uid]),
            'conv' => $conv,
            'messages' => $conv ? Database::all('SELECT role, content, model, created_at FROM ai_messages WHERE conversation_id = :c AND user_id = :u ORDER BY id', ['c' => $conv['id'], 'u' => $this->uid]) : [],
            'prompts' => self::PROMPTS, 'remaining' => $this->remaining(), 'limit' => (int) $this->m['ent']['ai_daily'],
        ], 'AI Coach', 'coach');
    }

    private function guard(): void
    {
        if (!AiCoach::configured()) {
            Response::json(['ok' => false, 'error' => t('ai.not_configured')], 503);
        }
        if ($this->remaining() <= 0) {
            $msg = $this->m['ent']['paid'] ? 'You have used today\'s AI Coach messages. The limit resets at 00:00 UTC.' : 'You have used today\'s free AI Coach messages. Upgrade for a higher daily limit, or try again tomorrow.';
            Response::json(['ok' => false, 'error' => $msg, 'upgrade' => url('/pricing')], 429);
        }
    }

    private function langLine(string $lang): string
    {
        $lang = isset(Domain::LANGUAGES[$lang]) ? $lang : 'en';
        return "\n\nRespond in " . ['en' => 'English', 'ru' => 'Russian', 'zh' => 'Simplified Chinese', 'pt' => 'Portuguese'][$lang] . '.';
    }

    /** Last 90 days of closed trades plus recent journal entries for the active account. */
    private function context(string $lang, ?string $from = null, ?string $to = null, string $label = 'last 90 days'): string
    {
        $from ??= (new \DateTimeImmutable('now', new \DateTimeZone($this->tz)))->modify('-90 days')->format('Y-m-d');
        $trades = Ledger::forAnalytics($this->uid, (int) $this->acc['id'], $this->tz, $from, $to);
        $journals = Database::all('SELECT journal_date, compliance, emotional_state, discipline_rating, reflection, key_lesson FROM journal_entries
            WHERE user_id = :u AND is_demo = :x AND journal_date >= :f' . ($to ? ' AND journal_date <= :t' : '') . ' ORDER BY journal_date DESC LIMIT 14',
            ['u' => $this->uid, 'x' => (int) $this->acc['has_demo_data'], 'f' => $from] + ($to ? ['t' => $to] : []));
        return AiCoach::context($this->m, $this->acc, $trades, $journals, $label) . $this->langLine($lang);
    }

    public function send(Request $req): never
    {
        $this->guard();
        $text = trim((string) $req->post('message', ''));
        if ($text === '' || mb_strlen($text) > 4000) {
            Response::json(['ok' => false, 'error' => $text === '' ? 'Type a question first.' : 'Keep questions under 4,000 characters.'], 422);
        }
        $conv = null;
        if (ctype_digit((string) $req->post('conversation_id', ''))) {
            $conv = Database::one('SELECT * FROM ai_conversations WHERE id = :id AND user_id = :u', ['id' => (int) $req->post('conversation_id'), 'u' => $this->uid]);
        }
        $history = $conv ? Database::all('SELECT role, content FROM ai_messages WHERE conversation_id = :c AND user_id = :u ORDER BY id DESC LIMIT 12', ['c' => $conv['id'], 'u' => $this->uid]) : [];
        $history = array_reverse($history);
        while ($history && $history[0]['role'] !== 'user') {
            array_shift($history);
        }
        $messages = array_map(fn ($r) => ['role' => $r['role'], 'content' => $r['content']], $history);
        $messages[] = ['role' => 'user', 'content' => $text];

        $res = AiCoach::complete($this->context((string) $req->post('lang', $this->m['language'])), $messages);
        if (!$res['ok']) {
            Response::json(['ok' => false, 'error' => $res['error'] ?? t('ai.unavailable')], 502);
        }
        $convId = Database::transaction(function () use ($conv, $text, $res) {
            $cid = $conv ? (int) $conv['id'] : Database::insert('ai_conversations', ['user_id' => $this->uid, 'title' => mb_strimwidth($text, 0, 80, '…')]);
            Database::insert('ai_messages', ['conversation_id' => $cid, 'user_id' => $this->uid, 'role' => 'user', 'content' => $text]);
            Database::insert('ai_messages', ['conversation_id' => $cid, 'user_id' => $this->uid, 'role' => 'assistant', 'content' => $res['text'], 'model' => mb_substr($res['model'], 0, 80)]);
            Database::query('UPDATE ai_conversations SET updated_at = NOW() WHERE id = :id', ['id' => $cid]);
            return $cid;
        });
        Response::json(['ok' => true, 'reply' => $res['text'], 'meta' => 'AI-generated · not financial advice', 'conversation_id' => $convId, 'remaining' => $this->remaining()]);
    }

    public function review(Request $req): never
    {
        $this->guard();
        $period = $req->post('period') === 'monthly' ? 'monthly' : 'weekly';
        $now = new \DateTimeImmutable('now', new \DateTimeZone($this->tz));
        if ($period === 'weekly') {
            $from = $now->modify('monday last week')->format('Y-m-d');
            $to = $now->modify('sunday last week')->format('Y-m-d');
            $label = 'previous week ' . $from . ' to ' . $to;
        } else {
            $from = $now->modify('first day of last month')->format('Y-m-d');
            $to = $now->modify('last day of last month')->format('Y-m-d');
            $label = 'previous month ' . $from . ' to ' . $to;
        }
        $prompt = 'Write my ' . $period . ' trading review for the ' . $label . '. Structure: 1) Summary in 3 lines, 2) What worked, 3) What leaked money (discipline, emotions, sessions, instruments), 4) Three specific rules or focus points for the next ' . ($period === 'weekly' ? 'week' : 'month') . '. If there were no trades in this period, say so and give a short plan for getting quality data.';
        // The review prompt is fixed server-side; it counts toward the daily limit like a chat message.
        $res = AiCoach::complete($this->context((string) $req->post('lang', $this->m['language']), $from, $to, $label), [['role' => 'user', 'content' => $prompt]]);
        if (!$res['ok']) {
            Response::json(['ok' => false, 'error' => $res['error'] ?? t('ai.unavailable')], 502);
        }
        Database::transaction(function () use ($period, $label, $prompt, $res) {
            $cid = Database::insert('ai_conversations', ['user_id' => $this->uid, 'title' => ucfirst($period) . ' review · ' . $label]);
            Database::insert('ai_messages', ['conversation_id' => $cid, 'user_id' => $this->uid, 'role' => 'user', 'content' => $prompt]);
            Database::insert('ai_messages', ['conversation_id' => $cid, 'user_id' => $this->uid, 'role' => 'assistant', 'content' => $res['text'], 'model' => mb_substr($res['model'], 0, 80)]);
        });
        Response::json(['ok' => true, 'text' => $res['text'], 'remaining' => $this->remaining()]);
    }

    public function delete(Request $req): never
    {
        $id = $req->params['id'];
        Database::delete('ai_conversations', 'id = :id AND user_id = :u', ['id' => (int) $id, 'u' => $this->uid]);
        Members::audit($this->uid, 'ai_conversation_deleted', 'Conversation #' . (int) $id);
        $this->back('/terminal/coach', 'success', 'Conversation deleted.');
    }
}
