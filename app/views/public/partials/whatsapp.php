<?php
$num = preg_replace('/\D/', '', setting('whatsapp_number') ?: setting('contact_whatsapp'));
if (!setting_on('whatsapp_enabled') || strlen((string) $num) < 6) {
    return;
}
$classes = 'wa-float wa-' . (setting('whatsapp_position') === 'left' ? 'left' : 'right')
    . (setting_on('whatsapp_mobile', true) ? '' : ' wa-hide-mobile') . (setting_on('whatsapp_desktop', true) ? '' : ' wa-hide-desktop');
$href = 'https://wa.me/' . $num . (setting('whatsapp_message') !== '' ? '?text=' . rawurlencode(setting('whatsapp_message')) : '');
?>
<a class="<?= e($classes) ?>" href="<?= e($href) ?>" target="_blank" rel="noopener" aria-label="<?= e(setting('whatsapp_label', 'Chat on WhatsApp')) ?>">
  <svg viewBox="0 0 32 32" width="28" height="28" aria-hidden="true"><path fill="currentColor" d="M16 3a13 13 0 0 0-11.2 19.6L3 29l6.6-1.7A13 13 0 1 0 16 3zm0 23.7c-2 0-4-.5-5.7-1.6l-.4-.2-3.9 1 1-3.8-.3-.4A10.7 10.7 0 1 1 16 26.7zm5.9-8c-.3-.2-1.9-.9-2.2-1-.3-.1-.5-.2-.7.2l-1 1.2c-.2.2-.4.2-.7.1a8.8 8.8 0 0 1-4.4-3.8c-.3-.6.3-.5 1-1.7.1-.2 0-.4 0-.5l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.1 1.1-1.1 2.7s1.2 3.1 1.3 3.3c.2.2 2.3 3.5 5.5 4.9 2 .9 2.8.9 3.8.8.6-.1 1.9-.8 2.1-1.5.3-.7.3-1.3.2-1.5l-.6-.4z"/></svg>
  <span class="wa-label"><?= e(setting('whatsapp_label', 'Chat with us')) ?></span>
</a>
