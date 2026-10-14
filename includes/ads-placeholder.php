<?php
/**
 * Policy-Compliant Ad Placeholder & Monetization Helper
 * Formats clearly disclosed advertisement areas that strictly adhere to
 * Google AdSense, Adsterra, and Monetag placement guidelines.
 */

require_once __DIR__ . '/monetization-functions.php';

function render_ad_slot($format = 'banner', $slotId = 'default') {
    $slotMap = [
        'header'       => 'header_ad',
        'in-article'   => 'in_article_ad',
        'in-feed'      => 'between_content_ad',
        'between'      => 'between_content_ad',
        'sidebar'      => 'sidebar_ad',
        'below'        => 'below_content_ad',
        'related'      => 'related_content_ad'
    ];

    $slotKey = $slotMap[$format] ?? ($slotMap[$slotId] ?? 'between_content_ad');
    render_monetization_ad($slotKey);
}
