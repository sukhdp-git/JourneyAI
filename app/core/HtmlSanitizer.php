<?php
declare(strict_types=1);

namespace App\Core;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allow-list HTML sanitizer for rich-text content saved from the control panel.
 * Unknown elements are unwrapped (their text is kept); dangerous ones are removed with their content;
 * every attribute not on the list (including all on* handlers and style) is dropped; URLs are scheme-checked.
 */
final class HtmlSanitizer
{
    private const TAGS = [
        'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'mark' => [], 'small' => [], 'sub' => [], 'sup' => [],
        'a' => ['href', 'title', 'target', 'rel'], 'ul' => [], 'ol' => ['start'], 'li' => [],
        'h2' => ['id'], 'h3' => ['id'], 'h4' => ['id'], 'h5' => [], 'blockquote' => [], 'code' => [], 'pre' => [], 'hr' => [],
        'img' => ['src', 'alt', 'width', 'height', 'title'], 'figure' => [], 'figcaption' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['colspan', 'rowspan', 'scope'], 'td' => ['colspan', 'rowspan'],
        'iframe' => ['src', 'title', 'width', 'height', 'allowfullscreen'], 'div' => [], 'span' => [],
    ];
    private const DROP = ['script', 'style', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'link', 'meta', 'base', 'svg', 'math', 'template', 'noscript', 'frame', 'frameset', 'applet', 'audio', 'video', 'source', 'head', 'title'];
    private const IFRAME_HOSTS = ['www.youtube.com', 'www.youtube-nocookie.com', 'player.vimeo.com', 'www.google.com', 'maps.google.com'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }
        $doc = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $root = $doc->getElementById('__root');
        if (!$root) {
            return e(strip_tags($html));
        }
        self::walk($root);
        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }
        return trim($out);
    }

    private static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP, true)) {
                $node->removeChild($child);
                continue;
            }
            if (!isset(self::TAGS[$tag])) {
                self::walk($child);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            self::cleanAttributes($child, $tag);
            if ($tag === 'iframe' && !$child->hasAttribute('src')) {
                $node->removeChild($child);
                continue;
            }
            self::walk($child);
        }
    }

    private static function cleanAttributes(DOMElement $el, string $tag): void
    {
        $allowed = self::TAGS[$tag];
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);
            $value = trim($attr->value);
            if (!in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->name);
                continue;
            }
            if ($name === 'href' && !self::safeUrl($value, true)) {
                $el->removeAttribute($attr->name);
            } elseif ($name === 'src' && !self::safeUrl($value, false)) {
                $el->removeAttribute($attr->name);
            } elseif ($name === 'src' && $tag === 'iframe' && !in_array((string) parse_url($value, PHP_URL_HOST), self::IFRAME_HOSTS, true)) {
                $el->removeAttribute($attr->name);
            } elseif (in_array($name, ['width', 'height', 'colspan', 'rowspan', 'start'], true) && !ctype_digit($value)) {
                $el->removeAttribute($attr->name);
            } elseif ($name === 'target' && $value !== '_blank') {
                $el->removeAttribute($attr->name);
            } elseif ($name === 'id' && !preg_match('/^[a-z][a-z0-9\-]{0,60}$/', $value)) {
                $el->removeAttribute($attr->name);
            }
        }
        if ($tag === 'a' && $el->getAttribute('target') === '_blank') {
            $el->setAttribute('rel', 'noopener noreferrer');
        }
        if ($tag === 'img') {
            $el->setAttribute('loading', 'lazy');
            $el->setAttribute('decoding', 'async');
        }
        if ($tag === 'iframe') {
            $el->setAttribute('loading', 'lazy');
            $el->setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
        }
    }

    private static function safeUrl(string $url, bool $isLink): bool
    {
        $u = preg_replace('/[\x00-\x20]+/', '', html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '';
        if ($u === '') {
            return false;
        }
        if (preg_match('#^(https?:)?//#i', $u) || str_starts_with($u, '/') || (!str_contains($u, ':') && !str_starts_with($u, '\\'))) {
            return true;
        }
        return $isLink && (bool) preg_match('#^(mailto|tel):#i', $u);
    }
}
