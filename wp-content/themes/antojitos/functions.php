<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * =========================================================
 * ANTOJITOS EL CHINO
 * FUNCIONES DEL TEMA
 * =========================================================
 */


/**
 * =========================================================
 * ESTILOS GENERALES DEL TEMA
 * =========================================================
 */

function antojitos_theme_assets()
{
    $theme =
        wp_get_theme();


    /**
     * -----------------------------------------------------
     * CSS principal del tema
     * -----------------------------------------------------
     */

    wp_enqueue_style(
        'antojitos-style',
        get_stylesheet_uri(),
        [],
        $theme->get('Version')
    );


    /**
     * -----------------------------------------------------
     * CSS exclusivo del carrito
     * -----------------------------------------------------
     *
     * Usamos filemtime() para cambiar automáticamente
     * la versión del archivo cuando cart.css se modifica.
     * Esto evita problemas de caché durante desarrollo.
     */

    if (
        function_exists('is_cart')
        &&
        is_cart()
    ) {

        $cart_css_relative =
            '/assets/css/cart.css';


        $cart_css_path =
            get_theme_file_path(
                $cart_css_relative
            );


        $cart_css_version =
            file_exists(
                $cart_css_path
            )
                ? (string) filemtime(
                    $cart_css_path
                )
                : $theme->get('Version');


        wp_enqueue_style(
            'antojitos-cart',
            get_theme_file_uri(
                $cart_css_relative
            ),
            [
                'antojitos-style',
            ],
            $cart_css_version
        );
    }
}


add_action(
    'wp_enqueue_scripts',
    'antojitos_theme_assets'
);


/**
 * =========================================================
 * MENU PRINCIPAL
 * =========================================================
 */

function aec_theme_menu_assets()
{
    if (
        !is_page(
            'menu'
        )
    ) {
        return;
    }


    $relative =
        '/assets/css/menu.css';


    $path =
        get_template_directory()
        .
        $relative;


    if (
        !file_exists(
            $path
        )
    ) {
        return;
    }


    wp_enqueue_style(
        'aec-menu-page',
        get_template_directory_uri()
        .
        $relative,
        [
            'antojitos-style',
        ],
        (string)
        filemtime(
            $path
        )
    );
}


add_action(
    'wp_enqueue_scripts',
    'aec_theme_menu_assets',
    50
);


/**
 * =========================================================
 * PAGINA COMIDA
 * =========================================================
 */

function aec_theme_comida_assets()
{
    if (
        !is_page(
            'comida'
        )
    ) {
        return;
    }


    $relative =
        '/assets/css/comida.css';


    $path =
        get_template_directory()
        .
        $relative;


    if (
        !file_exists(
            $path
        )
    ) {
        return;
    }


    wp_enqueue_style(
        'aec-comida-page',
        get_template_directory_uri()
        .
        $relative,
        [
            'antojitos-style',
        ],
        (string)
        filemtime(
            $path
        )
    );
}


add_action(
    'wp_enqueue_scripts',
    'aec_theme_comida_assets',
    60
);


/**
 * =========================================================
 * PAGINA BEBIDAS
 * =========================================================
 */

function aec_theme_bebidas_assets()
{
    if (
        !is_page(
            'bebidas'
        )
    ) {
        return;
    }


    $relative =
        '/assets/css/bebidas.css';


    $path =
        get_template_directory()
        .
        $relative;


    if (
        !file_exists(
            $path
        )
    ) {
        return;
    }


    wp_enqueue_style(
        'aec-bebidas-page',
        get_template_directory_uri()
        .
        $relative,
        [
            'antojitos-style',
        ],
        (string)
        filemtime(
            $path
        )
    );
}


add_action(
    'wp_enqueue_scripts',
    'aec_theme_bebidas_assets',
    60
);


/**
 * =========================================================
 * PRODUCTO INDIVIDUAL
 * =========================================================
 */

function aec_theme_single_product_visual_assets()
{
    if (
        !function_exists(
            'is_product'
        )
        ||
        !is_product()
    ) {
        return;
    }


    $relative =
        '/assets/css/product-page.css';


    $path =
        get_template_directory()
        .
        $relative;


    if (
        !file_exists(
            $path
        )
    ) {
        return;
    }


    wp_enqueue_style(
        'aec-single-product-page',
        get_template_directory_uri()
        .
        $relative,
        [
            'antojitos-style',
        ],
        (string)
        filemtime(
            $path
        )
    );
}


add_action(
    'wp_enqueue_scripts',
    'aec_theme_single_product_visual_assets',
    80
);

