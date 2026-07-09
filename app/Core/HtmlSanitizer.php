<?php

declare(strict_types=1);

namespace App\Core;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Dependency-free allowlist HTML sanitizer for user-supplied rich text
 * (the Quill editor output stored as article content).
 *
 * Strategy: parse into a DOM, then walk it removing everything not on the
 * allowlist — disallowed elements are dropped (script/style/iframe/…) or
 * unwrapped, every event-handler / non-allowlisted attribute is stripped, and
 * href/src values are scheme-checked so `javascript:` and non-image `data:`
 * URIs cannot survive. Applied at write time so content is safe at rest.
 */
final class HtmlSanitizer
{
    /** Tags permitted in article content (Quill's default output set). */
    private const ALLOWED_TAGS = [
        'p', 'br', 'hr', 'span', 'div',
        'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'sub', 'sup', 'mark', 'small',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'blockquote', 'pre', 'code',
        'ul', 'ol', 'li',
        'a', 'img',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
    ];

    /** Elements whose entire subtree must be discarded, never unwrapped. */
    private const DROP_SUBTREE = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'link',
        'meta', 'base', 'svg', 'math', 'template', 'noscript', 'frame', 'frameset',
    ];

    /** Allowlisted attributes: '*' applies to every allowed tag. */
    private const ALLOWED_ATTRS = [
        '*'   => ['class'],
        'a'   => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'td'  => ['colspan', 'rowspan'],
        'th'  => ['colspan', 'rowspan'],
    ];

    public static function clean(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $prev = libxml_use_internal_errors(true);
        $doc  = new DOMDocument('1.0', 'UTF-8');
        // The XML PI forces libxml to treat the byte stream as UTF-8; loadHTML
        // auto-wraps the fragment in <html><body>…</body></html>.
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $body = $doc->getElementsByTagName('body')->item(0);
        if (!$body instanceof DOMElement) {
            return '';
        }

        self::sanitizeNode($body);

        $out = '';
        foreach (iterator_to_array($body->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        // Snapshot children first — we mutate the live NodeList as we go.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMComment) {
                $child->parentNode?->removeChild($child);
                continue;
            }

            if (!$child instanceof DOMElement) {
                continue; // text and other nodes are safe to keep
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_SUBTREE, true)) {
                $child->parentNode?->removeChild($child);
                continue;
            }

            if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                // Unknown-but-harmless tag: clean its subtree, then unwrap it.
                self::sanitizeNode($child);
                while ($child->firstChild !== null) {
                    $child->parentNode?->insertBefore($child->firstChild, $child);
                }
                $child->parentNode?->removeChild($child);
                continue;
            }

            self::filterAttributes($child, $tag);
            self::sanitizeNode($child);
        }
    }

    private static function filterAttributes(DOMElement $el, string $tag): void
    {
        $allowed = array_merge(self::ALLOWED_ATTRS['*'], self::ALLOWED_ATTRS[$tag] ?? []);

        $names = [];
        foreach (iterator_to_array($el->attributes) as $attr) {
            $names[] = $attr->nodeName;
        }

        foreach ($names as $name) {
            $lname = strtolower($name);
            $value = $el->getAttribute($name);

            if (str_starts_with($lname, 'on') || !in_array($lname, $allowed, true)) {
                $el->removeAttribute($name);
                continue;
            }

            if (($lname === 'href' || $lname === 'src') && !self::safeUrl($value, $tag === 'img')) {
                $el->removeAttribute($name);
                continue;
            }

            if ($lname === 'class') {
                // Keep only Quill formatting classes (e.g. ql-align-center, ql-syntax).
                $tokens = preg_split('/\s+/', trim($value)) ?: [];
                $keep   = array_filter(
                    $tokens,
                    static fn (string $c): bool => $c !== '' && preg_match('/^ql-[a-z0-9\-]+$/i', $c) === 1
                );
                if ($keep !== []) {
                    $el->setAttribute('class', implode(' ', $keep));
                } else {
                    $el->removeAttribute('class');
                }
                continue;
            }

            if ($lname === 'target' && strtolower($value) === '_blank') {
                $el->setAttribute('rel', 'noopener noreferrer nofollow');
            }
        }
    }

    private static function safeUrl(string $url, bool $allowDataImage): bool
    {
        $u = trim($url);
        if ($u === '') {
            return false;
        }

        // Relative paths and in-page anchors are safe.
        if ($u[0] === '/' || $u[0] === '#' || str_starts_with($u, './') || str_starts_with($u, '../')) {
            return true;
        }

        $scheme = strtolower((string) (parse_url($u, PHP_URL_SCHEME) ?: ''));
        if ($scheme === '') {
            return true; // schemeless relative reference (e.g. "images/x.webp")
        }
        if (in_array($scheme, ['http', 'https', 'mailto'], true)) {
            return true;
        }
        if ($allowDataImage
            && $scheme === 'data'
            && preg_match('#^data:image/(png|jpe?g|gif|webp);base64,#i', $u) === 1
        ) {
            return true;
        }

        return false; // blocks javascript:, vbscript:, data:text/html, etc.
    }
}
