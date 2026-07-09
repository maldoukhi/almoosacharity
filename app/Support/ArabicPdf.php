<?php

namespace App\Support;

use ArPHP\I18N\Arabic;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfInstance;

/**
 * dompdf has no Unicode bidi algorithm or Arabic glyph shaping, so raw
 * Arabic HTML comes out letter-reversed and disconnected. This helper renders
 * a Blade view, then rewrites every Arabic run in the HTML into shaped
 * presentation forms in visual order (ar-php's utf8Glyphs) before handing it
 * to dompdf — the standard recipe for Arabic dompdf output. Tag markup is
 * ASCII, so arIdentify() only ever touches the text runs between tags.
 *
 * IMPORTANT: templates rendered through this helper must NOT set
 * `direction: rtl` in CSS — the shaped runs are already in visual order, and
 * dompdf would reverse them a second time. Use `text-align: right` (or
 * explicit table column order) for the RTL look instead.
 */
class ArabicPdf
{
    public static function loadView(string $view, array $data = []): PdfInstance
    {
        return Pdf::loadHTML(self::shape(view($view, $data)->render()));
    }

    /**
     * Shape every Arabic segment of an HTML (or plain-text) string into
     * visual-order presentation forms.
     */
    public static function shape(string $html): string
    {
        $arabic = new Arabic;

        $positions = $arabic->arIdentify($html);

        // Walk backwards so earlier offsets stay valid while substrings are
        // replaced with their (equal-or-shorter) shaped forms.
        for ($i = count($positions) - 2; $i >= 0; $i -= 2) {
            $segment = substr($html, $positions[$i], $positions[$i + 1] - $positions[$i]);

            $html = substr_replace($html, self::shapeSegment($arabic, $segment), $positions[$i], $positions[$i + 1] - $positions[$i]);
        }

        return $html;
    }

    /**
     * Shape one Arabic segment, protecting embedded Western number runs
     * (amounts, dates, times) from the bidi pass: utf8Glyphs would otherwise
     * flip their group order (24,600.00 -> 00,600.24). Each digit run is
     * swapped for a same-length sentinel (a palindrome, so the visual
     * reordering can't scramble it), then the originals are restored in
     * reverse — the shaped output lists the runs in reversed visual order.
     */
    private static function shapeSegment(Arabic $arabic, string $segment): string
    {
        // Newlines inside an HTML text run are just whitespace to the
        // renderer, but utf8Glyphs shapes line-by-line — flatten them so the
        // reversed-order restore below stays a single-line problem.
        $segment = strtr($segment, ["\r" => ' ', "\n" => ' ', "\t" => ' ']);

        $numbers = [];

        $protected = preg_replace_callback(
            '/[0-9][0-9.,:\/\-%]*[0-9]|[0-9]/',
            function (array $match) use (&$numbers): string {
                $numbers[] = $match[0];

                return str_repeat("\x06", strlen($match[0]));
            },
            $segment,
        );

        // Huge max_chars disables utf8Glyphs' own line-wrapping (dompdf
        // wraps); hindo=false keeps Western digits as typed.
        $shaped = $arabic->utf8Glyphs($protected, 10000, false);

        if ($numbers === []) {
            return $shaped;
        }

        $numbers = array_reverse($numbers);
        $next = 0;

        return preg_replace_callback(
            '/\x06+/',
            function () use (&$numbers, &$next): string {
                return $numbers[$next++] ?? '';
            },
            $shaped,
        );
    }
}
