<?php
/**
 * Theme system — reads settings, emits CSS variables
 */

function theme_css_vars(): string {
    $defaults = [
        'bg' => '#FFFFFF', 'surface' => '#FAFAFA', 'card' => '#FFFFFF',
        'text' => '#111111', 'muted' => '#666666', 'border' => '#E5E5E5',
        'button' => '#111111', 'button_text' => '#FFFFFF', 'accent' => '#111111',
        'header' => '#FFFFFF', 'footer_bg' => '#0A0A0A', 'footer_text' => '#CCCCCC',
    ];
    $vars = [];
    foreach ($defaults as $k => $d) {
        $vars[$k] = setting('theme_' . $k, $d);
    }
    $radius  = (int)setting('theme_border_radius', 12);
    $btnRad  = (int)setting('theme_button_radius', 10);
    $blur    = (int)setting('theme_blur', 14);
    $glass   = (float)setting('theme_glass_opacity', 0.85);
    $anim    = (float)setting('theme_animation', 1);
    $css = ":root{";
    foreach ($vars as $k => $v) $css .= "--c-$k:$v;";
    $css .= "--r-sm:6px;--r-md:{$radius}px;--r-lg:" . min($radius + 4, 24) . "px;--r-xl:24px;--r-pill:999px;";
    $css .= "--blur:{$blur}px;--glass:{$glass};--anim:{$anim};";
    $css .= "}";
    return $css;
}

function theme_presets(): array {
    return [
        'default' => [
            'label' => 'Default White',
            'values' => [
                'theme_bg' => '#FFFFFF', 'theme_surface' => '#FAFAFA', 'theme_card' => '#FFFFFF',
                'theme_text' => '#111111', 'theme_muted' => '#666666', 'theme_border' => '#E5E5E5',
                'theme_button' => '#111111', 'theme_button_text' => '#FFFFFF', 'theme_accent' => '#111111',
                'theme_header' => '#FFFFFF', 'theme_footer_bg' => '#0A0A0A', 'theme_footer_text' => '#CCCCCC',
            ],
        ],
        'midnight' => [
            'label' => 'Midnight',
            'values' => [
                'theme_bg' => '#0A0A0A', 'theme_surface' => '#111111', 'theme_card' => '#161616',
                'theme_text' => '#F5F5F5', 'theme_muted' => '#9A9A9A', 'theme_border' => '#262626',
                'theme_button' => '#F5F5F5', 'theme_button_text' => '#0A0A0A', 'theme_accent' => '#F5F5F5',
                'theme_header' => '#0A0A0A', 'theme_footer_bg' => '#000000', 'theme_footer_text' => '#888888',
            ],
        ],
        'ocean' => [
            'label' => 'Ocean',
            'values' => [
                'theme_bg' => '#F0F7FA', 'theme_surface' => '#FFFFFF', 'theme_card' => '#FFFFFF',
                'theme_text' => '#0A2A3A', 'theme_muted' => '#5A7484', 'theme_border' => '#D4E4EC',
                'theme_button' => '#0A6EA0', 'theme_button_text' => '#FFFFFF', 'theme_accent' => '#0A6EA0',
                'theme_header' => '#FFFFFF', 'theme_footer_bg' => '#0A2A3A', 'theme_footer_text' => '#C4D8E2',
            ],
        ],
        'forest' => [
            'label' => 'Forest',
            'values' => [
                'theme_bg' => '#F5F7F0', 'theme_surface' => '#FAFCF5', 'theme_card' => '#FFFFFF',
                'theme_text' => '#1A2818', 'theme_muted' => '#5C6E58', 'theme_border' => '#D8E0CC',
                'theme_button' => '#2D4A2A', 'theme_button_text' => '#FFFFFF', 'theme_accent' => '#2D4A2A',
                'theme_header' => '#FFFFFF', 'theme_footer_bg' => '#1A2818', 'theme_footer_text' => '#B8C8B4',
            ],
        ],
        'rose' => [
            'label' => 'Rose',
            'values' => [
                'theme_bg' => '#FBF6F4', 'theme_surface' => '#FFFFFF', 'theme_card' => '#FFFFFF',
                'theme_text' => '#2A1A1A', 'theme_muted' => '#806060', 'theme_border' => '#EBD5D0',
                'theme_button' => '#8B3A3A', 'theme_button_text' => '#FFFFFF', 'theme_accent' => '#8B3A3A',
                'theme_header' => '#FFFFFF', 'theme_footer_bg' => '#2A1A1A', 'theme_footer_text' => '#D8C0C0',
            ],
        ],
    ];
}
