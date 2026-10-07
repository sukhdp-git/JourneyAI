<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Database;
use App\Core\Request;
use App\Trading\Analytics;
use App\Trading\Domain;
use App\Trading\Journal;
use App\Trading\Ledger;

/** Daily psychology journal: typed or dictated day review, rule adherence, emotions, ratings and key lessons. */
final class NotepadController extends TerminalController
{
    private function demo(): int
    {
        return (int) $this->acc['has_demo_data'];
    }

    public function index(Request $req): never
    {
        $today = (new \DateTimeImmutable('now', new \DateTimeZone($this->tz)))->format('Y-m-d');
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $req->query('date')) ? (string) $req->query('date') : $today;
        $entry = Database::one('SELECT * FROM journal_entries WHERE user_id = :u AND journal_date = :d AND is_demo = :x', ['u' => $this->uid, 'd' => $date, 'x' => $this->demo()]);
        $history = Database::all('SELECT journal_date, compliance, rules_answer, emotional_state, discipline_rating, key_lesson FROM journal_entries WHERE user_id = :u AND is_demo = :x ORDER BY journal_date DESC LIMIT 60', ['u' => $this->uid, 'x' => $this->demo()]);
        $lessons = Database::all("SELECT journal_date, key_lesson FROM journal_entries WHERE user_id = :u AND is_demo = :x AND key_lesson IS NOT NULL AND key_lesson <> '' ORDER BY journal_date DESC LIMIT 100", ['u' => $this->uid, 'x' => $this->demo()]);
        $from = (new \DateTimeImmutable($date . ' 00:00:00', new \DateTimeZone($this->tz)))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $to = (new \DateTimeImmutable($date . ' 00:00:00', new \DateTimeZone($this->tz)))->modify('+1 day')->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $dayAll = Database::all(Ledger::TRADE_SELECT . ' WHERE t.user_id = :u AND t.account_id = :a AND t.executed_at >= :f AND t.executed_at < :t ORDER BY t.executed_at', ['u' => $this->uid, 'a' => $this->acc['id'], 'f' => $from, 't' => $to]);
        $this->render('notepad', [
            'date' => $date, 'today' => $today, 'entry' => $entry, 'history' => $history, 'lessons' => $lessons, 'dayTrades' => $dayAll,
            'daySum' => Analytics::summarize($dayAll), 'score' => Journal::disciplineScore($entry, $dayAll, $this->m, $this->acc, $date, $this->tz),
        ], 'Daily Notepad', 'notepad');
    }

    public function save(Request $req): never
    {
        $date = (string) $req->post('journal_date');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !\DateTime::createFromFormat('Y-m-d', $date)) {
            $this->back('/terminal/notepad', 'error', 'Choose a valid date.');
        }
        $rating = is_numeric($req->post('discipline_rating')) && (int) $req->post('discipline_rating') >= 1 && (int) $req->post('discipline_rating') <= 10 ? (int) $req->post('discipline_rating') : null;
        $emotion = (string) $req->post('emotional_state');
        $data = [
            'rules' => in_array($req->post('rules_answer'), ['yes', 'partial', 'no'], true) ? $req->post('rules_answer') : null,
            'emotion' => isset(Domain::JOURNAL_EMOTIONS[$emotion]) || in_array($emotion, Domain::EMOTIONS, true) ? $emotion : null,
            'rating' => $rating,
            'reflection' => mb_substr(trim((string) $req->post('reflection', '')), 0, 20000) ?: null,
            'lesson' => mb_substr(trim((string) $req->post('key_lesson', '')), 0, 500) ?: null,
        ];
        Database::query('INSERT INTO journal_entries (user_id, account_id, journal_date, is_demo, rules_answer, emotional_state, discipline_rating, reflection, key_lesson)
            VALUES (:u, :a, :d, :x, :ra, :e, :r, :ref, :k)
            ON DUPLICATE KEY UPDATE rules_answer = VALUES(rules_answer), emotional_state = VALUES(emotional_state), discipline_rating = VALUES(discipline_rating), reflection = VALUES(reflection), key_lesson = VALUES(key_lesson), updated_at = NOW()',
            ['u' => $this->uid, 'a' => $this->acc['id'], 'd' => $date, 'x' => $this->demo(), 'ra' => $data['rules'], 'e' => $data['emotion'], 'r' => $data['rating'], 'ref' => $data['reflection'], 'k' => $data['lesson']]);
        $this->back('/terminal/notepad?date=' . $date, 'success', 'Journal saved for ' . $date . '. It is now part of your reviews and AI Coach context.');
    }
}
