<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * =========================================================
 * ANTOJITOS EL CHINO
 * BREADCRUMBS
 * =========================================================
 */


/**
 * Personaliza la apariencia base del
 * breadcrumb de WooCommerce.
 */
function aec_breadcrumb_defaults(
    $defaults
) {
    $defaults['delimiter'] =
        '<span
            class="aec-breadcrumb-separator"
            aria-hidden="true"
        >
            ›
        </span>';


    $defaults['wrap_before'] =
        '<nav
            class="woocommerce-breadcrumb"
            aria-label="Navegación"
        >';


    $defaults['wrap_after'] =
        '</nav>';


    $defaults['before'] =
        '<span class="aec-breadcrumb-current">';


    $defaults['after'] =
        '</span>';


    $defaults['home'] =
        'Inicio';


    return $defaults;
}


add_filter(
    'woocommerce_breadcrumb_defaults',
    'aec_breadcrumb_defaults',
    20
);


/**
 * =========================================================
 * CARGAR CSS
 * =========================================================
 */

function aec_breadcrumb_assets()
{
    if (
        !function_exists('is_woocommerce') ||
        !is_woocommerce()
    ) {
        return;
    }


    wp_enqueue_style(
        'aec-breadcrumbs',
        get_stylesheet_directory_uri()
        .
        '/assets/css/breadcrumbs.css',
        [],
        '1.0.0'
    );
}


add_action(
    'wp_enqueue_scripts',
    'aec_breadcrumb_assets',
    30
);
