<?php
/** Appearance settings → CSS custom properties. Every value is validated so settings cannot inject CSS. */
$c = fn (string $k, string $d) => safe_color(setting($k, $d), $d);
$font = function (string $k, string $d): string {
    $f = setting($k, $d);
    if (!in_array($f, font_options(), true)) {
        $f = $d;
    }
    return $f === 'System' ? 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif' : '"' . $f . '", system-ui, -apple-system, "Segoe UI", sans-serif';
};
$radius = max(0, min(32, (int) setting('radius', '16')));
$btn = match (setting('button_style', 'pill')) { 'square' => '4px', 'rounded' => max(6, (int) round($radius * 0.6)) . 'px', default => '999px' };
$spacing = match (setting('section_spacing', 'normal')) { 'compact' => '72px', 'spacious' => '144px', default => '112px' };
$container = max(960, min(1600, (int) setting('container_width', '1200')));
$base = max(14, min(20, (int) setting('font_base_size', '16')));
$hw = in_array(setting('heading_weight', '600'), ['500', '600', '700'], true) ? setting('heading_weight', '600') : '600';
$css = ':root{'
    . '--primary:' . $c('color_primary', '#7c5cff') . ';--secondary:' . $c('color_secondary', '#22d3ee') . ';--accent:' . $c('color_accent', '#34d399') . ';'
    . '--text:' . $c('color_text', '#c9cfdd') . ';--heading:' . $c('color_heading', '#ffffff') . ';--muted:' . $c('color_muted', '#8f99ae') . ';'
    . '--bg:' . $c('color_bg', '#070a12') . ';--surface:' . $c('color_surface', '#0d1220') . ';--border:' . $c('color_border', '#1c2436') . ';'
    . '--font-heading:' . $font('font_heading', 'Space Grotesk') . ';--font-body:' . $font('font_body', 'Inter') . ';'
    . '--radius:' . $radius . 'px;--btn-radius:' . $btn . ';--section-y:' . $spacing . ';--container:' . $container . 'px;--base-size:' . $base . 'px;--heading-weight:' . $hw . ';'
    . '--btn-transform:' . (setting_on('button_uppercase') ? 'uppercase' : 'none') . ';--btn-glow:' . (setting_on('button_glow', true) ? '1' : '0') . ';}';
$custom = trim(setting('custom_css'));
?>
<style id="theme"><?= $css ?></style>
<?php if ($custom !== ''): ?>
<style id="custom-css"><?= str_replace('<', '\3c ', $custom) ?></style>
<?php endif; ?>
