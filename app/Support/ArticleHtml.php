<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class ArticleHtml
{
    /**
     * @var array<string, list<string>>
     */
    private const TAGS = [
        'p' => ['style'],
        'br' => [],
        'strong' => [],
        'em' => [],
        'u' => [],
        's' => [],
        'h2' => ['style'],
        'h3' => ['style'],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'blockquote' => [],
        'pre' => [],
        'code' => [],
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt'],
        'hr' => [],
    ];

    /**
     * Tags whose contents must not survive as text.
     *
     * @var list<string>
     */
    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math'];

    public static function clean(?string $html): ?string
    {
        $html = trim((string) $html);

        if ($html === '' || in_array($html, ['<p></p>', '<p><br></p>'], true)) {
            return null;
        }

        if (! preg_match('/<[^>]+>/', $html)) {
            $lines = preg_split("/\r\n|\n|\r/", $html) ?: [];
            $html = collect($lines)
                ->map(fn (string $line) => '<p>'.e($line).'</p>')
                ->implode('');
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><body>'.$html.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body) {
            return null;
        }

        self::sanitizeChildren($body);

        $clean = '';
        foreach ($body->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }

        $clean = trim($clean);
        $text = trim(html_entity_decode(strip_tags(str_replace('<img', 'image', $clean))));

        return $text === '' ? null : $clean;
    }

    private static function sanitizeChildren(DOMNode $node): void
    {
        for ($index = $node->childNodes->length - 1; $index >= 0; $index--) {
            $child = $node->childNodes->item($index);

            if (! $child || $child->nodeType === XML_COMMENT_NODE) {
                if ($child) {
                    $node->removeChild($child);
                }

                continue;
            }

            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            self::sanitizeChildren($child);

            $tag = strtolower($child->nodeName);

            if (! isset(self::TAGS[$tag])) {
                if (! in_array($tag, self::DROP, true)) {
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                }
                $node->removeChild($child);

                continue;
            }

            self::sanitizeAttributes($child, self::TAGS[$tag]);
        }
    }

    /**
     * @param  list<string>  $allowed
     */
    private static function sanitizeAttributes(DOMElement $element, array $allowed): void
    {
        $remove = [];

        foreach ($element->attributes as $attribute) {
            $name = strtolower($attribute->name);

            if (! in_array($name, $allowed, true) || str_starts_with($name, 'on')) {
                $remove[] = $attribute->name;

                continue;
            }

            if ($name === 'href' && ! self::safeUrl($attribute->value, allowMailto: true)) {
                $remove[] = $attribute->name;
            }

            if ($name === 'src' && ! self::safeUrl($attribute->value, allowMailto: false)) {
                $remove[] = $attribute->name;
            }

            if ($name === 'style' && ! preg_match('/^text-align:\s*(left|center|right|justify)\s*;?$/i', trim($attribute->value))) {
                $remove[] = $attribute->name;
            }

            if ($name === 'target' && ! in_array($attribute->value, ['_blank', '_self'], true)) {
                $remove[] = $attribute->name;
            }
        }

        foreach ($remove as $name) {
            $element->removeAttribute($name);
        }

        if (strtolower($element->nodeName) === 'a' && $element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }

        if (strtolower($element->nodeName) === 'img' && $element->getAttribute('src') === '') {
            $element->parentNode?->removeChild($element);
        }
    }

    private static function safeUrl(string $value, bool $allowMailto): bool
    {
        $value = trim($value);

        if ($value === '' || str_starts_with($value, '//') || str_starts_with(strtolower($value), 'javascript:') || str_starts_with(strtolower($value), 'data:')) {
            return false;
        }

        if (str_starts_with($value, '/') || str_starts_with($value, '#')) {
            return true;
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);

        if (! is_string($scheme)) {
            return false;
        }

        return in_array(strtolower($scheme), $allowMailto ? ['http', 'https', 'mailto'] : ['http', 'https'], true);
    }
}
