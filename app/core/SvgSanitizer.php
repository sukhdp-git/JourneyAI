<?php
declare(strict_types=1);

namespace App\Core;

use DOMDocument;
use DOMElement;

/** Strict allow-list SVG sanitiser: shapes, paths, gradients and text only — no scripts, links, styles or external refs. */
final class SvgSanitizer
{
    private const ELEMENTS = ['svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'defs', 'lineargradient', 'radialgradient', 'stop', 'title', 'desc', 'text', 'tspan', 'clippath', 'mask', 'symbol'];
    private const ATTRS = ['id', 'class', 'viewbox', 'width', 'height', 'x', 'y', 'x1', 'x2', 'y1', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'd', 'points', 'fill', 'fill-rule', 'fill-opacity', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'stroke-opacity', 'stroke-dasharray', 'stroke-miterlimit', 'opacity', 'transform', 'offset', 'stop-color', 'stop-opacity', 'gradientunits', 'gradienttransform', 'clip-path', 'clip-rule', 'mask', 'font-family', 'font-size', 'font-weight', 'text-anchor', 'xmlns', 'version', 'preserveaspectratio', 'fx', 'fy', 'dominant-baseline', 'letter-spacing'];

    public static function clean(string $svg): ?string
    {
        if (strlen($svg) > 512 * 1024 || preg_match('/<!DOCTYPE|<!ENTITY|<\?xml-stylesheet/i', $svg)) {
            return null;
        }
        $doc = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $ok = $doc->loadXML($svg, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (!$ok || !$doc->documentElement || strtolower($doc->documentElement->localName) !== 'svg') {
            return null;
        }
        if (!self::walk($doc->documentElement)) {
            return null;
        }
        $doc->documentElement->setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        return $doc->saveXML($doc->documentElement) ?: null;
    }

    private static function walk(DOMElement $el): bool
    {
        if (!in_array(strtolower($el->localName), self::ELEMENTS, true)) {
            return false;
        }
        foreach (iterator_to_array($el->attributes) as $a) {
            $n = strtolower($a->nodeName);
            $v = $a->nodeValue ?? '';
            if (!in_array($n, self::ATTRS, true) || preg_match('/(url\s*\(\s*[\'"]?\s*(?!#)|javascript:|data:|expression|@import)/i', $v)) {
                $el->removeAttributeNode($a);
            }
        }
        foreach (iterator_to_array($el->childNodes) as $c) {
            if ($c instanceof DOMElement) {
                if (!self::walk($c)) {
                    return false;
                }
            } elseif ($c->nodeType !== XML_TEXT_NODE) {
                $el->removeChild($c);
            }
        }
        return true;
    }

    public static function dimensions(string $svg): array
    {
        if (preg_match('/viewBox="\s*[\d.\-]+[\s,]+[\d.\-]+[\s,]+([\d.]+)[\s,]+([\d.]+)/i', $svg, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }
        return [0, 0];
    }
}
