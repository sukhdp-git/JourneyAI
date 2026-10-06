<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Secure media uploads. Raster images are decoded and re-encoded with GD (stripping metadata and any
 * embedded payload) to WebP with a medium-size variant. SVG is accepted only after allow-list sanitising.
 * Files are stored under uploads/YYYY/MM with random names; uploads/.htaccess blocks script execution.
 */
final class Media
{
    public const EXT_MIME = [
        'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'webp' => ['image/webp'],
        'svg' => ['image/svg+xml', 'text/xml', 'application/xml', 'text/plain'],
    ];
    private const DANGEROUS = '/\.(php\d?|phtml|phar|pht|phps|cgi|pl|py|sh|bash|exe|com|bat|cmd|js|mjs|jsp|asp|aspx|htaccess|htpasswd|html?|shtml|xhtml|ini|dll|so)(\.|$)/i';
    private const MAX_DIMENSION = 2560;
    private const MEDIUM_WIDTH = 900;

    public static function maxBytes(): int
    {
        $mb = (int) Settings::get('media_max_mb', '5');
        return max(1, min(20, $mb)) * 1024 * 1024;
    }

    /** @return array the saved media row */
    public static function upload(array $file, string $alt = ''): array
    {
        $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(match ($err) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file is larger than the server allows.',
                UPLOAD_ERR_NO_FILE => 'Choose a file to upload.',
                default => 'The upload failed. Please try again.',
            });
        }
        $tmp = (string) $file['tmp_name'];
        if (!is_uploaded_file($tmp) && !defined('MEDIA_ALLOW_LOCAL')) {
            throw new \RuntimeException('Invalid upload.');
        }
        $original = basename(str_replace('\\', '/', (string) ($file['name'] ?? 'file')));
        $size = (int) filesize($tmp);
        if ($size <= 0 || $size > self::maxBytes()) {
            throw new \RuntimeException('Files must be smaller than ' . human_size(self::maxBytes()) . '.');
        }
        if (strlen($original) > 200 || preg_match('/[\x00-\x1f]/', $original)) {
            throw new \RuntimeException('The file name is not allowed.');
        }
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $base = pathinfo($original, PATHINFO_FILENAME);
        if (!isset(self::EXT_MIME[$ext]) || preg_match(self::DANGEROUS, $base . '.')) {
            throw new \RuntimeException('Only JPG, PNG, WebP and SVG images are allowed.');
        }
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!in_array($mime, self::EXT_MIME[$ext], true)) {
            throw new \RuntimeException('The file content does not match its extension.');
        }

        $dirRel = 'uploads/' . gmdate('Y/m');
        $dirAbs = ROOT_PATH . '/' . $dirRel;
        if (!is_dir($dirAbs) && !@mkdir($dirAbs, 0755, true)) {
            throw new \RuntimeException('The uploads folder is not writable. Check folder permissions.');
        }
        $name = slugify($base, 60) . '-' . bin2hex(random_bytes(4));

        if ($ext === 'svg') {
            $clean = SvgSanitizer::clean((string) file_get_contents($tmp));
            if ($clean === null) {
                throw new \RuntimeException('This SVG contains unsupported or unsafe content.');
            }
            $rel = "$dirRel/$name.svg";
            file_put_contents(ROOT_PATH . '/' . $rel, $clean);
            [$w, $h] = SvgSanitizer::dimensions($clean);
            return self::store($original, $rel, 'image/svg+xml', $w, $h, $alt);
        }

        $info = @getimagesize($tmp);
        if (!$info || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > 40_000_000) {
            throw new \RuntimeException('The image could not be read or is too large.');
        }
        $img = @imagecreatefromstring((string) file_get_contents($tmp));
        if (!$img) {
            throw new \RuntimeException('The image could not be processed.');
        }
        $img = self::orient($img, $tmp, $mime);
        $img = self::fit($img, self::MAX_DIMENSION);
        $webp = function_exists('imagewebp');
        $outExt = $webp ? 'webp' : ($mime === 'image/png' ? 'png' : 'jpg');
        $rel = "$dirRel/$name.$outExt";
        self::save($img, ROOT_PATH . '/' . $rel, $outExt);
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w > self::MEDIUM_WIDTH) {
            $md = self::fit($img, self::MEDIUM_WIDTH, true);
            self::save($md, ROOT_PATH . "/$dirRel/$name-md.$outExt", $outExt);
            imagedestroy($md);
        }
        imagedestroy($img);
        return self::store($original, $rel, $outExt === 'webp' ? 'image/webp' : ($outExt === 'png' ? 'image/png' : 'image/jpeg'), $w, $h, $alt);
    }

    private static function store(string $original, string $rel, string $mime, int $w, int $h, string $alt): array
    {
        $id = Database::insert('media', [
            'filename' => basename($rel),
            'original_name' => mb_substr($original, 0, 255),
            'path' => $rel,
            'mime' => $mime,
            'size' => (int) filesize(ROOT_PATH . '/' . $rel),
            'width' => $w ?: null,
            'height' => $h ?: null,
            'alt_text' => mb_substr($alt !== '' ? $alt : ucfirst(str_replace(['-', '_'], ' ', pathinfo($original, PATHINFO_FILENAME))), 0, 255),
            'uploaded_by' => Auth::id(),
        ]);
        return Database::one('SELECT * FROM media WHERE id = :id', ['id' => $id]) ?? [];
    }

    private static function orient(\GdImage $img, string $tmp, string $mime): \GdImage
    {
        if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data($tmp);
        $angle = match ((int) ($exif['Orientation'] ?? 1)) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
        if ($angle !== 0) {
            $rot = imagerotate($img, $angle, 0);
            if ($rot) {
                imagedestroy($img);
                return $rot;
            }
        }
        return $img;
    }

    private static function fit(\GdImage $img, int $max, bool $copy = false): \GdImage
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $scale = $copy ? $max / $w : min(1, $max / max($w, $h));
        if ($scale >= 1 && !$copy) {
            return self::truecolor($img);
        }
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        if (!$copy) {
            imagedestroy($img);
        }
        return $dst;
    }

    private static function truecolor(\GdImage $img): \GdImage
    {
        if (imageistruecolor($img)) {
            imagealphablending($img, false);
            imagesavealpha($img, true);
            return $img;
        }
        $w = imagesx($img);
        $h = imagesy($img);
        $dst = imagecreatetruecolor($w, $h);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopy($dst, $img, 0, 0, 0, 0, $w, $h);
        imagedestroy($img);
        return $dst;
    }

    private static function save(\GdImage $img, string $path, string $ext): void
    {
        $ok = match ($ext) {
            'webp' => imagewebp($img, $path, 82),
            'png' => imagepng($img, $path, 7),
            default => imagejpeg($img, $path, 84),
        };
        if (!$ok) {
            throw new \RuntimeException('The image could not be saved.');
        }
    }

    /** Deletes a media row and its files. Paths are re-validated to stay inside uploads/. */
    public static function delete(int $id): bool
    {
        $m = Database::one('SELECT * FROM media WHERE id = :id', ['id' => $id]);
        if (!$m) {
            return false;
        }
        $root = realpath(UPLOAD_PATH);
        foreach ([$m['path'], preg_replace('#\.(\w+)$#', '-md.$1', $m['path'])] as $rel) {
            $abs = realpath(ROOT_PATH . '/' . $rel);
            if ($abs && $root && str_starts_with($abs, $root . DIRECTORY_SEPARATOR) && is_file($abs)) {
                @unlink($abs);
            }
        }
        Database::delete('media', 'id = :id', ['id' => $id]);
        return true;
    }

    /** True when $path is a stored upload path (used to validate image fields). */
    public static function isValidPath(string $path): bool
    {
        if ($path === '') {
            return true;
        }
        if (preg_match('#^https://[^\s"<>]+$#i', $path)) {
            return true;
        }
        return (bool) preg_match('#^uploads/[A-Za-z0-9/_\-]+\.(webp|jpe?g|png|svg)$#', $path) && !str_contains($path, '..');
    }
}
