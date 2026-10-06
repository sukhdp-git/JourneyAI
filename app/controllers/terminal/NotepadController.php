<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Database;
use App\Core\Request;
use App\Trading\Analytics;
use App\Trading\Domain;
use App\Trading\Ledger;

/** Daily notepad: typed or dictated reflections with compliance, emotion and discipline ratings. */
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
        $history = Database::all('SELECT journal_date, compliance, emotional_state, discipline_rating, key_lesson FROM journal_entries WHERE user_id = :u AND is_demo = :x ORDER BY journal_date DESC LIMIT 60', ['u' => $this->uid, 'x' => $this->demo()]);
        $dayTrades = Ledger::forAnalytics($this->uid, (int) $this->acc['id'], $this->tz, $date, $date);
        $this->render('notepad', ['date' => $date, 'today' => $today, 'entry' => $entry, 'history' => $history, 'daySum' => Analytics::summarize($dayTrades)], 'Daily Notepad', 'notepad');
    }

    public function save(Request $req): never
    {
        $date = (string) $req->post('journal_date');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !\DateTime::createFromFormat('Y-m-d', $date)) {
            $this->back('/terminal/notepad', 'error', 'Choose a valid date.');
        }
        $int = fn ($k, $min, $max) => is_numeric($req->post($k)) && (int) $req->post($k) >= $min && (int) $req->post($k) <= $max ? (int) $req->post($k) : null;
        $data = [
            'compliance' => $int('compliance', 1, 5), 'discipline_rating' => $int('discipline_rating', 1, 10),
            'emotional_state' => in_array($req->post('emotional_state'), Domain::EMOTIONS, true) ? $req->post('emotional_state') : null,
            'reflection' => mb_substr(trim((string) $req->post('reflection', '')), 0, 20000) ?: null, 'key_lesson' => mb_substr(trim((string) $req->post('key_lesson', '')), 0, 500) ?: null,
        ];
        Database::query('INSERT INTO journal_entries (user_id, account_id, journal_date, is_demo, compliance, emotional_state, discipline_rating, reflection, key_lesson)
            VALUES (:u, :a, :d, :x, :c, :e, :r, :ref, :k)
            ON DUPLICATE KEY UPDATE compliance = VALUES(compliance), emotional_state = VALUES(emotional_state), discipline_rating = VALUES(discipline_rating), reflection = VALUES(reflection), key_lesson = VALUES(key_lesson), updated_at = NOW()',
            ['u' => $this->uid, 'a' => $this->acc['id'], 'd' => $date, 'x' => $this->demo(), 'c' => $data['compliance'], 'e' => $data['emotional_state'], 'r' => $data['discipline_rating'], 'ref' => $data['reflection'], 'k' => $data['key_lesson']]);
        $this->back('/terminal/notepad?date=' . $date, 'success', 'Journal entry saved for ' . $date . '.');
    }
}
