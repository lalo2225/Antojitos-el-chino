<?php
/**
 * Plugin Name: Antojitos el Chino
 * Description: Funciones personalizadas para la administración y operación de Antojitos el Chino.
 * Version: 1.2.0
 * Author: Antojitos el Chino
 */

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Rutas principales del plugin.
 */
define(
    'AEC_PLUGIN_DIR',
    plugin_dir_path(__FILE__)
);

define(
    'AEC_PLUGIN_URL',
    plugin_dir_url(__FILE__)
);


/**
 * Inicializa los módulos personalizados.
 */
function antojitos_inicializar_plugin()
{
    if (!class_exists('WooCommerce')) {
        return;
    }

    require_once AEC_PLUGIN_DIR
        . 'admin/disponibilidad.php';

    require_once AEC_PLUGIN_DIR
        . 'includes/order-statuses.php';

    require_once AEC_PLUGIN_DIR
        . 'includes/checkout-fields.php';
    require_once AEC_PLUGIN_DIR
        . 'includes/customer-identification.php';
    require_once AEC_PLUGIN_DIR
        . 'includes/product-configurator.php';
    require_once AEC_PLUGIN_DIR  
        . 'includes/ingredient-availability.php';
    require_once AEC_PLUGIN_DIR 
        . 'includes/ui-breadcrumbs.php';
    require_once AEC_PLUGIN_DIR
        . 'includes/order-received.php';

    require_once AEC_PLUGIN_DIR
        . 'includes/order-admin.php';
    require_once AEC_PLUGIN_DIR 
        . 'includes/order-detail-ui.php';
    require_once AEC_PLUGIN_DIR 
        . 'includes/order-workflow.php';
    require_once AEC_PLUGIN_DIR
        . 'includes/order-entry.php';
    require_once AEC_PLUGIN_DIR 
        . 'includes/transfer-validation.php';
    require_once AEC_PLUGIN_DIR 
        . 'includes/order-priority.php';
    require_once AEC_PLUGIN_DIR   
        . 'includes/order-board.php';
    require_once AEC_PLUGIN_DIR 
        . 'includes/admin-dashboard.php';
    require_once AEC_PLUGIN_DIR  
        . 'includes/admin-experience.php';
    require_once AEC_PLUGIN_DIR 
        . 'includes/admin-products.php';
}
	
add_action(
    'plugins_loaded',
    'antojitos_inicializar_plugin'
);


/**
 * Carga los estilos y scripts del configurador.
 */
function aec_cargar_assets()
{
    if (
        !function_exists('is_woocommerce')
    ) {
        return;
    }

    /*
     * CSS:
     * tienda, producto, carrito y checkout.
     */
    if (
        is_woocommerce() ||
        is_cart() ||
        is_checkout()
    ) {
        wp_enqueue_style(
            'aec-product-configurator',
            AEC_PLUGIN_URL
                . 'assets/css/product-configurator.css',
            [],
            '1.2.0'
        );
    }

    /*
     * JavaScript:
     * solamente se necesita al configurar
     * un producto.
     */
    if (is_product()) {
        wp_enqueue_script(
            'aec-product-configurator',
            AEC_PLUGIN_URL
                . 'assets/js/product-configurator.js',
            [],
            '1.2.0',
            true
        );
    }
}

add_action(
    'wp_enqueue_scripts',
    'aec_cargar_assets'
);
