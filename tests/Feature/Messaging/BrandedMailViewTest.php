<?php

use App\Mail\OutboundMessage;

/**
 * The branded HTML email (resources/views/mail/message.blade.php) renders
 * the plain body it is given, wrapped in the charity's branded, RTL,
 * inline-styled layout — and never emits unescaped body content.
 */
it('renders the message body inside the branded RTL layout', function () {
    $html = (new OutboundMessage(
        subjectLine: 'إشعار',
        bodyText: 'عزيزي محمد، تمت الموافقة على إعانتكم.',
    ))->render();

    expect($html)
        ->toContain('عزيزي محمد، تمت الموافقة على إعانتكم.')
        ->toContain('dir="rtl"')
        // Brand primary teal is used in the header band.
        ->toContain('#1C545E')
        // Footer chrome is present.
        ->toContain(__('notifications.mail.layout.automated_note'));
});

it('escapes HTML in the body instead of rendering it as markup', function () {
    $html = (new OutboundMessage(
        subjectLine: 'إشعار',
        bodyText: '<script>alert("xss")</script> مرحبًا',
    ))->render();

    // The injected script tag is escaped, never emitted as live markup.
    expect($html)
        ->toContain('&lt;script&gt;')
        ->not->toContain('<script>alert("xss")</script>');
});
