<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Registra estados operativos propios para Antojitos el Chino.
 */
function antojitos_registrar_estados_pedido()
{
    $estados = [
        'wc-confirmado' => 'Confirmado',
        'wc-preparando' => 'En preparación',
        'wc-listo-recoger' => 'Listo para recoger',
        'wc-entregado' => 'Entregado',
    ];

    foreach ($estados as $slug => $nombre) {

        register_post_status($slug, [
            'label'                     => $nombre,
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                "{$nombre} <span class=\"count\">(%s)</span>",
                "{$nombre} <span class=\"count\">(%s)</span>"
            ),
        ]);
    }
}

add_action(
    'init',
    'antojitos_registrar_estados_pedido'
);


/**
 * Agrega nuestros estados a la lista de WooCommerce.
 */
function antojitos_agregar_estados_woocommerce($estados)
{
    $resultado = [];

    foreach ($estados as $slug => $nombre) {

        $resultado[$slug] = $nombre;

        if ($slug === 'wc-processing') {

            $resultado['wc-confirmado'] =
                'Confirmado';

            $resultado['wc-preparando'] =
                'En preparación';

            $resultado['wc-listo-recoger'] =
                'Listo para recoger';

            $resultado['wc-entregado'] =
                'Entregado';
        }
    }

    return $resultado;
}

add_filter(
    'wc_order_statuses',
    'antojitos_agregar_estados_woocommerce'
);

