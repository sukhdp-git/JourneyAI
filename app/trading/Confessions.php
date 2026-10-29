<?php
declare(strict_types=1);

namespace App\Trading;

use App\Core\Database;
use App\Core\RateLimiter;

/**
 * Confessions: an anonymous feed for paying members. Everyone posts under a unique @handle (never a name or
 * email), P&L can only come from a trade in the member's own journal (so nothing can be typed in as a flex),
 * and a spam shield blocks links, contact details and selling before anything is saved.
 * A like is shown as $15 of "respect" — a display only, no money is involved anywhere.
 */
final class Confessions
{
    public const LIKE_VALUE = 15;
    public const TAGS = [
        'CONFESSION' => ['🙊', 'Confession'], 'LOSS' => ['🔴', 'Loss'], 'WIN' => ['🟢', 'Win'], 'PSYCHOLOGY' => ['🧠', 'Psychology'],
        'DISCIPLINE' => ['🛡️', 'Discipline'], 'JOURNEY' => ['🛣️', 'Journey'], 'MEME' => ['😂', 'Meme'], 'QUESTION' => ['🙋', 'Question'],
    ];
    public const MOODS = ['😤', '😭', '😎', '🤡', '🧘', '💀', '🔥', '🫠', '🤝', '📉', '📈', '😴'];
    public const REPORT_REASONS = ['spam' => 'Spam or selling', 'scam' => 'Scam / signals / DM me', 'abuse' => 'Abuse or harassment', 'personal' => 'Personal details', 'other' => 'Something else'];
    public const MAX_POST = 600;
    public const MAX_COMMENT = 400;
    private const AVATARS = ['🦊', '🐺', '🦉', '🐼', '🦁', '🐯', '🐸', '🐙', '🦈', '🐲', '🦄', '🐝', '🦅', '🐻', '🐧', '🦝', '🐨', '🐵', '🦖', '🐳'];
    private const RESERVED = ['admin', 'administrator', 'journzey', 'support', 'official', 'moderator', 'mod', 'team', 'staff', 'system', 'help', 'root', 'owner', 'ceo', 'founder'];

    // ------------------------------------------------------------------ profiles

    public static function profile(int $uid): ?array
    {
        return Database::one('SELECT * FROM confession_profiles WHERE user_id = :u', ['u' => $uid]);
    }

    /** Emoji + hue for a generated avatar (no photos: the feed stays anonymous). */
    public static function avatar(int $seed): array
    {
        return [self::AVATARS[$seed % count(self::AVATARS)], ($seed * 47) % 360];
    }

    /** @return string|null error message */
    public static function validateHandle(string $h): ?string
    {
        if (!preg_match('/^[a-z0-9_.]{3,20}$/', $h)) {
            return 'Use 3–20 characters: lowercase letters, numbers, dot or underscore.';
        }
        if (preg_match('/^[._]|[._]$|\.\./', $h)) {
            return 'A handle cannot start or end with a dot or underscore.';
        }
        foreach (self::RESERVED as $r) {
            if ($h === $r || str_contains($h, 'journzey') || str_starts_with($h, 'admin') || str_starts_with($h, 'official')) {
                return 'That handle is reserved. Pick another one.';
            }
        }
        if (preg_match('/\d{7,}/', $h) || preg_match('/(signal|vip|dm|telegram|whatsapp|insta|tg_|wa_|profit|guarantee|invest|forexpro|account_?manag)/', $h)) {
            return 'Handles cannot advertise, carry phone numbers or look like a selling account.';
        }
        return null;
    }

    /** Creates or changes the member's anonymous handle (one change every 30 days). */
    public static function saveHandle(int $uid, string $raw): ?string
    {
        $h = strtolower(trim(ltrim($raw, '@')));
        if ($e = self::validateHandle($h)) {
            return $e;
        }
        $mine = self::profile($uid);
        if ($mine && $mine['handle'] === $h) {
            return null;
        }
        if ($mine && $mine['handle_changed_at'] && strtotime($mine['handle_changed_at'] . ' UTC') > time() - 30 * 86400) {
            return 'You can change your handle once every 30 days.';
        }
        if (Database::value('SELECT 1 FROM confession_profiles WHERE handle = :h AND user_id <> :u', ['h' => $h, 'u' => $uid])) {
            return '@' . $h . ' is taken. Try another one.';
        }
        if ($mine) {
            Database::update('confession_profiles', ['handle' => $h, 'handle_changed_at' => gmdate('Y-m-d H:i:s')], 'user_id = :u', ['u' => $uid]);
        } else {
            Database::insert('confession_profiles', ['user_id' => $uid, 'handle' => $h, 'avatar_seed' => random_int(1, 1000000)]);
        }
        return null;
    }

    public static function suggestHandle(): string
    {
        $a = ['calm', 'patient', 'silent', 'green', 'stoic', 'lucky', 'night', 'early', 'humble', 'sharp', 'quiet', 'iron', 'zen', 'brave'];
        $b = ['trader', 'scalper', 'bull', 'bear', 'pip', 'candle', 'wick', 'sniper', 'monk', 'owl', 'wolf', 'fox', 'whale', 'hawk'];
        for ($i = 0; $i < 20; $i++) {
            $h = $a[array_rand($a)] . '_' . $b[array_rand($b)] . random_int(10, 999);
            if (!Database::value('SELECT 1 FROM confession_profiles WHERE handle = :h', ['h' => $h])) {
                return $h;
            }
        }
        return 'trader' . random_int(10000, 99999);
    }

    // ------------------------------------------------------------------ spam shield

    /** Text with common disguises undone: dot-com spelled out, spaced letters, leetspeak, zero-width chars. */
    public static function normalize(string $t): string
    {
        $t = mb_strtolower(preg_replace('/[\x{200B}-\x{200F}\x{2060}\x{FEFF}]/u', '', $t) ?? '');
        $t = strtr($t, ['［' => '[', '］' => ']', '（' => '(', '）' => ')', '。' => '.', '．' => '.', '＠' => '@']);
        $t = preg_replace('/\s*(\(|\[|\{)\s*(dot|\.)\s*(\)|\]|\})\s*/u', '.', $t) ?? $t;
        $t = preg_replace('/\s+(dot|d0t|period)\s+(?=[a-z]{2,6}\b)/u', '.', $t) ?? $t;
        $t = preg_replace('/\s*(\(|\[)\s*(at)\s*(\)|\])\s*/u', '@', $t) ?? $t;
        // "t e l e g r a m" / "t.e.l.e.g.r.a.m" → "telegram"
        $t = preg_replace_callback('/\b(?:[a-z0-9][\s.\-_*]){3,}[a-z0-9]\b/u', fn ($m) => preg_replace('/[\s.\-_*]/u', '', $m[0]) ?? $m[0], $t) ?? $t;
        return $t;
    }

    /** @return string|null why the text is blocked, null when it is fine */
    public static function guard(string $text): ?string
    {
        $raw = trim($text);
        $t = self::normalize($raw);
        $leet = strtr($t, ['0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't', '@' => 'a', '$' => 's', '|' => 'i']);
        $any = fn (string $re) => preg_match($re, $t) || preg_match($re, $leet);
        if ($any('~(https?://|www\.|\b[a-z0-9-]{2,}\.(com|net|org|io|me|co|in|xyz|ly|gg|app|link|site|online|top|shop|club|live|info|biz|ru|cn|tk|vip|pro|bio|ai|to|cc|us|uk)\b|t\.me|wa\.me|bit\.ly|tinyurl|linktr\.ee|discord\.gg|chat\.whatsapp)~u')) {
            return 'Links are not allowed in Confessions.';
        }
        if (preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/u', $t)) {
            return 'Email addresses are not allowed. Keep it anonymous.';
        }
        if (preg_match('/(\+?\d[\d\s().-]{8,}\d)/u', $raw, $m) && strlen(preg_replace('/\D/', '', $m[1])) >= 9) {
            return 'Phone numbers are not allowed. Keep it anonymous.';
        }
        if (preg_match('/\b(0x[a-f0-9]{40}|(bc1|[13])[a-zA-HJ-NP-Z0-9]{25,39}|T[1-9A-HJ-NP-Za-km-z]{33})\b/', $raw)) {
            return 'Wallet addresses are not allowed.';
        }
        $sell = '~\b(dm|d\.m|inbox|pm|message|msg|text|ping|contact|whats ?app|telegram|tg|insta(gram)?|snap(chat)?|discord|signal app)\s*(me|us|now|for|karo|kro|kijiye|bhai)\b'
            . '|\b(dm|inbox|pm)\s+me\b|\bdm\s+(for|to)\b|\b(join|follow)\s+(my|our)\s+(group|channel|vip|telegram|discord|page|community)\b'
            . '|\b(vip|premium|paid|free)\s+(group|channel|signals?|calls?)\b|\bsignals?\s+(group|channel|service|provider|room|available|for sale)\b|\bselling\s+signals?\b'
            . '|\b(copy|mirror)\s+(my\s+)?trad(es|ing)\b|\baccount\s+(management|manager|handling|passing)\b|\b(manage|handle|pass)\s+your\s+(account|challenge|funded)\b'
            . '|\b(guaranteed|assured|fixed)\s+(profit|returns?|income)\b|\b(100|200|300)\s*%\s*(profit|returns?|accuracy|sure|win)\b|\bdouble\s+your\s+(money|account|capital)\b'
            . '|\b(investment|investing)\s+(plan|scheme|opportunity)\b|\bdaily\s+(profit|income)\s+of\b|\bearn\s+\$?\d+\s*(daily|per day|a day|weekly)\b'
            . '|\blink\s+in\s+(my\s+)?bio\b|\bcheck\s+(my\s+)?(bio|profile)\b|\b(promo|referral|ref|discount|coupon)\s+code\b|\buse\s+my\s+code\b'
            . '|\b(buy|join)\s+my\s+(course|mentorship|indicator|ea|bot)\b|\bmentorship\s+available\b|\b(funded|prop)\s+accounts?\s+for\s+sale\b|\bsend\s+(usdt|btc|crypto|money)\b'
            . '|\bgroup\s+join\s+(karo|kro)\b|\bjoin\s+karo\b|\bcontact\s+karo\b|\bmessage\s+karo\b~u';
        if ($any($sell)) {
            return 'Selling, signals, DMs and groups are not allowed in Confessions. Share your trading, not a pitch.';
        }
        $apps = '(telegram|whats ?app|discord|snapchat|insta(gram)?|signal app|wechat|line app)';
        if ($any('~\b(my|our|join|add|on|in)\s+(\w+\s+){0,2}' . $apps . '\b|\b' . $apps . '\s+(channel|group|id|username|number|link|handle|server)\b|\b(my|our)\s+(channel|group|server)\b|\b(channel|group|server)\s+(on\s+)?' . $apps . '\b~u')) {
            return 'Sharing channels, groups or contact apps is not allowed in Confessions.';
        }
        if (preg_match('/(telegram|whatsapp|whats app|discord)\b.*@\w{3,}|@\w{3,}.*\b(telegram|whatsapp|discord)\b/u', $t)) {
            return 'Contact handles for other apps are not allowed.';
        }
        $letters = preg_replace('/[^\p{L}]/u', '', $raw) ?? '';
        if (mb_strlen($letters) >= 24 && mb_strtoupper($letters) === $letters && mb_strtolower($letters) !== $letters) {
            return 'Please don’t write the whole post in capital letters.';
        }
        if (preg_match('/(.)\1{9,}/u', $raw)) {
            return 'That looks like spam (repeated characters).';
        }
        if (preg_match_all('/#\w+/u', $raw) > 5 || preg_match_all('/@\w+/u', $raw) > 3) {
            return 'Too many hashtags or mentions.';
        }
        return null;
    }

    // ------------------------------------------------------------------ feed

    /** @param string $tab latest|top|mine */
    public static function feed(int $viewer, string $tab = 'latest', ?string $tag = null, int $before = 0, int $limit = 20): array
    {
        $w = ["c.status = 'visible'", 'p.banned = 0'];
        $p = ['v' => $viewer];
        if ($tag && isset(self::TAGS[$tag])) {
            $w[] = 'c.tag = :tag';
            $p['tag'] = $tag;
        }
        if ($tab === 'mine') {
            $w[] = 'c.user_id = :v2';
            $p['v2'] = $viewer;
        }
        if ($tab === 'top') {
            $w[] = 'c.created_at > NOW() - INTERVAL 7 DAY';
            $order = 'c.likes DESC, c.comments DESC, c.id DESC';
            $off = max(0, $before);
        } else {
            if ($before > 0) {
                $w[] = 'c.id < :before';
                $p['before'] = $before;
            }
            $order = 'c.id DESC';
            $off = 0;
        }
        $rows = Database::all('SELECT c.*, p.handle, p.avatar_seed, (l.user_id IS NOT NULL) AS liked FROM confessions c JOIN confession_profiles p ON p.user_id = c.user_id
            LEFT JOIN confession_likes l ON l.confession_id = c.id AND l.user_id = :v WHERE ' . implode(' AND ', $w) . " ORDER BY $order LIMIT " . ($limit + 1) . ' OFFSET ' . (int) $off, $p);
        $more = count($rows) > $limit;
        return [array_slice($rows, 0, $limit), $more];
    }

    public static function one(int $viewer, int $id): ?array
    {
        return Database::one("SELECT c.*, p.handle, p.avatar_seed, (l.user_id IS NOT NULL) AS liked FROM confessions c JOIN confession_profiles p ON p.user_id = c.user_id
            LEFT JOIN confession_likes l ON l.confession_id = c.id AND l.user_id = :v WHERE c.id = :id AND c.status = 'visible' AND p.banned = 0", ['v' => $viewer, 'id' => $id]);
    }

    /** Respect earned (likes on posts and comments × $15) and post count for a member. */
    public static function stats(int $uid): array
    {
        $posts = (int) Database::value("SELECT COUNT(*) FROM confessions WHERE user_id = :u AND status = 'visible'", ['u' => $uid]);
        $likes = (int) Database::value("SELECT COALESCE(SUM(likes), 0) FROM confessions WHERE user_id = :u AND status = 'visible'", ['u' => $uid])
            + (int) Database::value("SELECT COALESCE(SUM(likes), 0) FROM confession_comments WHERE user_id = :u AND status = 'visible'", ['u' => $uid]);
        return ['posts' => $posts, 'likes' => $likes, 'respect' => $likes * self::LIKE_VALUE];
    }

    /** "$1,245" style respect value for a like count. */
    public static function respect(int $likes): string
    {
        $v = $likes * self::LIKE_VALUE;
        return $v >= 1000000 ? '$' . rtrim(rtrim(number_format($v / 1000000, 1), '0'), '.') . 'M' : ($v >= 10000 ? '$' . rtrim(rtrim(number_format($v / 1000, 1), '0'), '.') . 'K' : '$' . number_format($v));
    }

    // ------------------------------------------------------------------ writing

    private static function blocked(int $uid, string $kind, string $body, string $reason): string
    {
        Database::insert('confession_blocked', ['user_id' => $uid, 'kind' => $kind, 'body' => mb_substr($body, 0, 1000), 'reason' => mb_substr($reason, 0, 80)]);
        return $reason;
    }

    /** @return string|null error if the member may not write right now */
    private static function canWrite(array $prof): ?string
    {
        if ((int) $prof['banned']) {
            return 'Your Confessions access has been suspended.';
        }
        if ($prof['muted_until'] && strtotime($prof['muted_until'] . ' UTC') > time()) {
            return 'You are muted until ' . gmdate('M j, H:i', strtotime($prof['muted_until'] . ' UTC')) . ' UTC.';
        }
        return null;
    }

    /** @return array{0:?string,1:?int} [error, new id] */
    public static function post(int $uid, array $in): array
    {
        $prof = self::profile($uid);
        if (!$prof) {
            return ['Create your anonymous handle first.', null];
        }
        if ($e = self::canWrite($prof)) {
            return [$e, null];
        }
        $body = trim(preg_replace("/\R{3,}/u", "\n\n", str_replace("\r", '', (string) ($in['body'] ?? ''))) ?? '');
        $len = mb_strlen($body);
        if ($len < 8) {
            return ['Write at least a sentence (8+ characters).', null];
        }
        if ($len > self::MAX_POST) {
            return ['Keep it under ' . self::MAX_POST . ' characters.', null];
        }
        if ($why = self::guard($body)) {
            return [self::blocked($uid, 'post', $body, $why), null];
        }
        if (Database::value("SELECT 1 FROM confessions WHERE user_id = :u AND body = :b AND created_at > NOW() - INTERVAL 1 DAY", ['u' => $uid, 'b' => $body])) {
            return [self::blocked($uid, 'post', $body, 'You already posted this.'), null];
        }
        if (!RateLimiter::hit('conf_post_h:' . $uid, 5, 3600) || !RateLimiter::hit('conf_post_d:' . $uid, 20, 86400)) {
            return ['Slow down: up to 5 posts an hour and 20 a day.', null];
        }
        $tag = isset(self::TAGS[$in['tag'] ?? '']) ? $in['tag'] : 'CONFESSION';
        $mood = in_array($in['mood'] ?? '', self::MOODS, true) ? $in['mood'] : null;
        $row = ['user_id' => $uid, 'body' => $body, 'tag' => $tag, 'mood' => $mood];
        // P&L only ever comes from a closed trade in the member's own journal
        if (($tid = (int) ($in['trade_id'] ?? 0)) > 0) {
            $t = Database::one("SELECT t.id, t.symbol, t.side, t.pnl, t.rr, a.currency, a.is_demo FROM trades t JOIN trading_accounts a ON a.id = t.account_id
                WHERE t.id = :id AND t.user_id = :u AND t.status = 'CLOSED' AND t.pnl IS NOT NULL", ['id' => $tid, 'u' => $uid]);
            if ($t) {
                $row += ['trade_id' => (int) $t['id'], 'trade_symbol' => $t['symbol'], 'trade_side' => $t['side'], 'trade_pnl' => $t['pnl'], 'trade_currency' => $t['currency'],
                    'trade_r' => $t['rr'], 'trade_demo' => (int) $t['is_demo']];
            }
        }
        return [null, Database::insert('confessions', $row)];
    }

    /** @return array{liked:bool,likes:int,respect:string}|null */
    public static function like(int $uid, int $id): ?array
    {
        if (!self::one($uid, $id)) {
            return null;
        }
        $had = Database::value('SELECT 1 FROM confession_likes WHERE confession_id = :c AND user_id = :u', ['c' => $id, 'u' => $uid]);
        if ($had) {
            Database::query('DELETE FROM confession_likes WHERE confession_id = :c AND user_id = :u', ['c' => $id, 'u' => $uid]);
            Database::query('UPDATE confessions SET likes = GREATEST(likes - 1, 0) WHERE id = :c', ['c' => $id]);
        } else {
            if (!RateLimiter::hit('conf_like:' . $uid, 240, 3600)) {
                return null;
            }
            Database::query('INSERT IGNORE INTO confession_likes (confession_id, user_id) VALUES (:c, :u)', ['c' => $id, 'u' => $uid]);
            Database::query('UPDATE confessions SET likes = likes + 1 WHERE id = :c', ['c' => $id]);
        }
        $n = (int) Database::value('SELECT likes FROM confessions WHERE id = :c', ['c' => $id]);
        return ['liked' => !$had, 'likes' => $n, 'respect' => self::respect($n)];
    }

    public static function comments(int $viewer, int $id): array
    {
        return Database::all("SELECT m.id, m.user_id, m.body, m.likes, m.created_at, p.handle, p.avatar_seed, (l.user_id IS NOT NULL) AS liked,
            (m.user_id = c.user_id) AS by_author FROM confession_comments m JOIN confessions c ON c.id = m.confession_id
            JOIN confession_profiles p ON p.user_id = m.user_id LEFT JOIN confession_comment_likes l ON l.comment_id = m.id AND l.user_id = :v
            WHERE m.confession_id = :id AND m.status = 'visible' AND p.banned = 0 ORDER BY m.likes DESC, m.id ASC LIMIT 200", ['v' => $viewer, 'id' => $id]);
    }

    /** @return array{0:?string,1:?int} */
    public static function comment(int $uid, int $id, string $body): array
    {
        $prof = self::profile($uid);
        if (!$prof) {
            return ['Create your anonymous handle first.', null];
        }
        if ($e = self::canWrite($prof)) {
            return [$e, null];
        }
        if (!self::one($uid, $id)) {
            return ['That confession is no longer available.', null];
        }
        $body = trim(str_replace("\r", '', $body));
        if (mb_strlen($body) < 2 || mb_strlen($body) > self::MAX_COMMENT) {
            return ['Comments are 2–' . self::MAX_COMMENT . ' characters.', null];
        }
        if ($why = self::guard($body)) {
            return [self::blocked($uid, 'comment', $body, $why), null];
        }
        if (!RateLimiter::hit('conf_com:' . $uid, 30, 3600)) {
            return ['Slow down: up to 30 comments an hour.', null];
        }
        $cid = Database::insert('confession_comments', ['confession_id' => $id, 'user_id' => $uid, 'body' => $body]);
        Database::query('UPDATE confessions SET comments = comments + 1 WHERE id = :c', ['c' => $id]);
        return [null, $cid];
    }

    public static function likeComment(int $uid, int $cid): ?array
    {
        $c = Database::one("SELECT m.id FROM confession_comments m JOIN confessions c ON c.id = m.confession_id WHERE m.id = :id AND m.status = 'visible' AND c.status = 'visible'", ['id' => $cid]);
        if (!$c) {
            return null;
        }
        $had = Database::value('SELECT 1 FROM confession_comment_likes WHERE comment_id = :c AND user_id = :u', ['c' => $cid, 'u' => $uid]);
        if ($had) {
            Database::query('DELETE FROM confession_comment_likes WHERE comment_id = :c AND user_id = :u', ['c' => $cid, 'u' => $uid]);
            Database::query('UPDATE confession_comments SET likes = GREATEST(likes - 1, 0) WHERE id = :c', ['c' => $cid]);
        } else {
            Database::query('INSERT IGNORE INTO confession_comment_likes (comment_id, user_id) VALUES (:c, :u)', ['c' => $cid, 'u' => $uid]);
            Database::query('UPDATE confession_comments SET likes = likes + 1 WHERE id = :c', ['c' => $cid]);
        }
        $n = (int) Database::value('SELECT likes FROM confession_comments WHERE id = :c', ['c' => $cid]);
        return ['liked' => !$had, 'likes' => $n, 'respect' => self::respect($n)];
    }

    /** Three reports from different members hide a post or comment until the team reviews it. */
    public static function report(int $uid, string $type, int $id, string $reason): bool
    {
        $type = $type === 'comment' ? 'comment' : 'post';
        $reason = isset(self::REPORT_REASONS[$reason]) ? $reason : 'other';
        $table = $type === 'post' ? 'confessions' : 'confession_comments';
        $owner = Database::value("SELECT user_id FROM $table WHERE id = :id", ['id' => $id]);
        if (!$owner || (int) $owner === $uid) {
            return false;
        }
        $n = Database::query('INSERT IGNORE INTO confession_reports (target_type, target_id, user_id, reason) VALUES (:t, :id, :u, :r)', ['t' => $type, 'id' => $id, 'u' => $uid, 'r' => $reason])->rowCount();
        if ($n) {
            Database::query("UPDATE $table SET reports = reports + 1, status = IF(reports >= 3 AND status = 'visible', 'hidden', status) WHERE id = :id", ['id' => $id]);
        }
        return true;
    }

    public static function remove(int $uid, int $id): bool
    {
        return Database::query("UPDATE confessions SET status = 'removed' WHERE id = :id AND user_id = :u", ['id' => $id, 'u' => $uid])->rowCount() > 0;
    }

    public static function removeComment(int $uid, int $cid): bool
    {
        $c = Database::one('SELECT confession_id FROM confession_comments WHERE id = :id AND user_id = :u AND status = \'visible\'', ['id' => $cid, 'u' => $uid]);
        if (!$c) {
            return false;
        }
        Database::query("UPDATE confession_comments SET status = 'removed' WHERE id = :id", ['id' => $cid]);
        Database::query('UPDATE confessions SET comments = GREATEST(comments - 1, 0) WHERE id = :c', ['c' => $c['confession_id']]);
        return true;
    }

    public static function shared(int $id): void
    {
        Database::query("UPDATE confessions SET shares = shares + 1 WHERE id = :id AND status = 'visible'", ['id' => $id]);
    }

    /** Recent closed trades a member can attach (P&L shown on the post comes from here). */
    public static function attachable(int $uid): array
    {
        return Database::all("SELECT t.id, t.symbol, t.side, t.pnl, t.rr, t.executed_at, a.currency, a.is_demo FROM trades t JOIN trading_accounts a ON a.id = t.account_id
            WHERE t.user_id = :u AND t.status = 'CLOSED' AND t.pnl IS NOT NULL ORDER BY t.executed_at DESC LIMIT 25", ['u' => $uid]);
    }

    public static function ago(string $ts): string
    {
        $s = max(0, time() - strtotime($ts . ' UTC'));
        return match (true) {
            $s < 60 => 'now', $s < 3600 => intdiv($s, 60) . 'm', $s < 86400 => intdiv($s, 3600) . 'h', $s < 7 * 86400 => intdiv($s, 86400) . 'd',
            default => gmdate('M j', strtotime($ts . ' UTC')),
        };
    }
}
