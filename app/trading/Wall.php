<?php
declare(strict_types=1);

namespace App\Trading;

use App\Core\Database;

/**
 * Wall of L's: anonymised loss autopsies submitted by members (with explicit consent) and approved by the team.
 * Only the chosen handle is ever shown — never the member's name or email.
 */
final class Wall
{
    public const REACTIONS = ['skull' => ['💀', 'been there'], 'melt' => ['🫠', 'me today'], 'hug' => ['🤝', 'respect']];

    public static function approved(int $limit = 60): array
    {
        return Database::all("SELECT id, handle, instrument, mistake, loss_text, story, lesson, react_skull, react_melt, react_hug, approved_at FROM wall_posts
            WHERE status = 'approved' ORDER BY approved_at DESC, id DESC LIMIT " . (int) $limit);
    }

    /** @return string|null error */
    public static function submit(int $uid, array $in): ?string
    {
        $story = trim((string) ($in['story'] ?? ''));
        $lesson = trim((string) ($in['lesson'] ?? ''));
        $loss = trim((string) ($in['loss_text'] ?? ''));
        if (($in['consent'] ?? '') !== '1') {
            return 'Please tick the consent box: your L is only published with your permission.';
        }
        if (mb_strlen($story) < 20 || mb_strlen($story) > 600) {
            return 'Tell the story in 20–600 characters.';
        }
        if (mb_strlen($lesson) < 5 || mb_strlen($lesson) > 300) {
            return 'Write the lesson in 5–300 characters.';
        }
        if ($loss === '' || mb_strlen($loss) > 40) {
            return 'Add the loss, e.g. “−$1,200” or “−3.5R”.';
        }
        if ((int) Database::value("SELECT COUNT(*) FROM wall_posts WHERE user_id = :u AND created_at > NOW() - INTERVAL 1 DAY", ['u' => $uid]) >= 3) {
            return 'You can submit up to 3 L\'s a day.';
        }
        $mistake = array_key_exists((string) ($in['mistake'] ?? ''), Domain::MISTAKES) && $in['mistake'] !== 'NONE' ? $in['mistake'] : 'IGNORED_PLAN';
        $handle = trim(preg_replace('/[^\p{L}\p{N} _.\-]/u', '', (string) ($in['handle'] ?? '')) ?? '');
        Database::insert('wall_posts', [
            'user_id' => $uid, 'handle' => $handle !== '' ? mb_substr($handle, 0, 40) : null, 'instrument' => mb_substr(strtoupper(preg_replace('/[^A-Za-z0-9.]/', '', (string) ($in['instrument'] ?? ''))), 0, 20) ?: null,
            'mistake' => $mistake, 'loss_text' => mb_substr($loss, 0, 40), 'story' => $story, 'lesson' => $lesson,
        ]);
        return null;
    }
}
