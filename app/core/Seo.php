<?php
declare(strict_types=1);

namespace App\Core;

/** Builds page metadata (title, description, canonical, robots, Open Graph, Twitter) and JSON-LD from CMS data. */
final class Seo
{
    private static array $schemas = [];

    public static function meta(array $p = []): array
    {
        $site = Settings::get('site_name', 'journzey.ai');
        $sep = Settings::get('seo_title_separator', '|') ?: '|';
        $title = trim((string) ($p['title'] ?? ''));
        $fullTitle = $title === '' ? (Settings::get('seo_default_title') ?: $site) : (!empty($p['raw_title']) ? $title : "$title $sep $site");
        $desc = trim((string) ($p['description'] ?? '')) ?: Settings::get('seo_default_description');
        $canonicalBase = rtrim(Settings::get('seo_canonical_base') ?: BASE_URL, '/');
        $path = (string) ($p['path'] ?? '/');
        $canonical = !empty($p['canonical']) ? (string) $p['canonical'] : $canonicalBase . ($path === '/' ? '/' : '/' . ltrim($path, '/'));
        $robots = !empty($p['noindex']) || !Settings::bool('seo_allow_indexing', true) ? 'noindex, nofollow' : (Settings::get('seo_robots') ?: 'index, follow');
        $image = (string) ($p['image'] ?? '') ?: Settings::get('seo_og_image');
        return [
            'title' => mb_substr($fullTitle, 0, 120),
            'description' => mb_substr(excerpt($desc, 320), 0, 320),
            'canonical' => $canonical,
            'robots' => $robots,
            'og_type' => $p['type'] ?? 'website',
            'og_title' => $p['og_title'] ?? ($title !== '' ? $title : (Settings::get('seo_og_title') ?: $fullTitle)),
            'og_description' => $p['og_description'] ?? ($p['description'] ?? '' ?: (Settings::get('seo_og_description') ?: $desc)),
            'og_image' => $image !== '' ? media_url($image) : '',
            'twitter_card' => Settings::get('seo_twitter_card') ?: 'summary_large_image',
            'twitter_site' => Settings::get('seo_twitter_site'),
            'published' => $p['published'] ?? null,
            'modified' => $p['modified'] ?? null,
        ];
    }

    public static function add(array $schema): void
    {
        self::$schemas[] = ['@context' => 'https://schema.org'] + $schema;
    }

    /** Organization + WebSite are always true statements; LocalBusiness only when a real address is configured. */
    public static function globals(): array
    {
        $out = self::$schemas;
        if (!Settings::bool('seo_schema_enabled', true)) {
            return $out;
        }
        $site = Settings::get('site_name', 'journzey.ai');
        $logo = Settings::get('logo_desktop');
        $same = array_values(array_filter(array_map(fn ($k) => Settings::get($k), ['social_x', 'social_linkedin', 'social_youtube', 'social_instagram', 'social_facebook', 'social_github'])));
        $org = ['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $site, 'url' => BASE_URL . '/'];
        if ($logo !== '') {
            $org['logo'] = media_url($logo);
        }
        if ($same) {
            $org['sameAs'] = $same;
        }
        if (Settings::get('contact_email') !== '') {
            $org['email'] = Settings::get('contact_email');
        }
        $out[] = $org;
        $out[] = ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => $site, 'url' => BASE_URL . '/'];
        if (Settings::bool('seo_local_business') && Settings::get('contact_address') !== '') {
            $lb = ['@context' => 'https://schema.org', '@type' => 'LocalBusiness', 'name' => $site, 'url' => BASE_URL . '/', 'address' => Settings::get('contact_address')];
            if (Settings::get('contact_phone') !== '') {
                $lb['telephone'] = Settings::get('contact_phone');
            }
            $out[] = $lb;
        }
        return $out;
    }

    /** @param array<array{0:string,1:string}> $crumbs [label, path] */
    public static function breadcrumbs(array $crumbs): void
    {
        $items = [];
        foreach (array_values($crumbs) as $i => [$label, $path]) {
            $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $label, 'item' => url($path)];
        }
        self::add(['@type' => 'BreadcrumbList', 'itemListElement' => $items]);
    }

    public static function faq(array $faqs): void
    {
        if (!$faqs) {
            return;
        }
        self::add(['@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => [
            '@type' => 'Question',
            'name' => $f['question'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => excerpt($f['answer'], 1000)],
        ], $faqs)]);
    }

    public static function json(array $schema): string
    {
        return str_replace('</', '<\/', (string) json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG));
    }
}
