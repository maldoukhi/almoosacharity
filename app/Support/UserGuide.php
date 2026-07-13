<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Serves slices of the illustrated user guide (docs/user-guide.html): the
 * contextual "شرح" modal shows only the section matching the current screen,
 * so this extracts one <section id="..."> plus the guide's <style> head and
 * wraps it into a small standalone page for the modal's iframe.
 */
class UserGuide
{
    /**
     * Section anchors that exist in the guide — the route whitelist.
     *
     * @var array<int, string>
     */
    public const SECTIONS = [
        'roles', 'basics', 'dashboard', 'beneficiaries', 'import', 'aids',
        'recurring', 'approvals', 'disbursement', 'confirmation', 'surveys',
        'messaging', 'reports', 'admin', 'walkthrough', 'faq',
    ];

    /**
     * A standalone HTML page containing just the requested section, or null
     * when the section (or the guide file) is missing. Cached per section,
     * keyed by the guide file's mtime so republishing the guide invalidates.
     */
    public static function sectionPage(string $section): ?string
    {
        if (! in_array($section, self::SECTIONS, true)) {
            return null;
        }

        $path = base_path('docs/user-guide.html');

        if (! is_file($path)) {
            return null;
        }

        return Cache::remember(
            'user-guide-section:'.$section.':'.filemtime($path),
            now()->addDay(),
            function () use ($path, $section): ?string {
                $guide = file_get_contents($path);

                if (! preg_match('/<style>.*?<\/style>/s', $guide, $style)) {
                    return null;
                }

                $pattern = '/<section class="module" id="'.preg_quote($section, '/').'">.*?<\/section>/s';

                if (! preg_match($pattern, $guide, $body)) {
                    return null;
                }

                return '<!DOCTYPE html>'
                    .'<html dir="rtl" lang="ar"><head><meta charset="utf-8">'
                    .'<meta name="viewport" content="width=device-width, initial-scale=1">'
                    .$style[0]
                    .'</head><body style="padding: 8px 20px 28px;"><main style="max-width: 100%;">'
                    .$body[0]
                    .'</main></body></html>';
            },
        );
    }
}
