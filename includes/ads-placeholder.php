<?php
/**
 * Policy-Compliant Ad Placeholder Helper
 * Formats clearly disclosed advertisement areas that strictly adhere to
 * Google AdSense placement guidelines (avoiding accidental clicks, fake buttons, or misleading labels).
 *
 * @param string $format 'banner' | 'sidebar' | 'in-feed'
 * @param string $slotId Optional Ad slot identifier
 */
function render_ad_slot($format = 'banner', $slotId = 'default') {
    // If ads are disabled globally, render nothing
    if (!defined('ENABLE_ADS') || !ENABLE_ADS) {
        return;
    }

    $formatClass = 'ad-format-' . htmlspecialchars($format);
    echo '<aside class="ad-placement-container ' . $formatClass . '" aria-label="Advertisement">';
    echo '  <div class="ad-label">Advertisement</div>';
    echo '  <div class="ad-box">';
    echo '    <div class="ad-content-placeholder">';
    echo '      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>';
    echo '      <span>Ad Slot (' . htmlspecialchars(ucfirst($format)) . ') - Ready for AdSense / Partner Script</span>';
    echo '    </div>';
    echo '  </div>';
    echo '</aside>';
}
