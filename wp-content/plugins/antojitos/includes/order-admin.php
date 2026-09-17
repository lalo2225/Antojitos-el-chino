<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * =========================================================
 * ANTOJITOS EL CHINO
 * ADMINISTRACIÓN DE PEDIDOS
 * =========================================================
 *
 * Agrega información operativa a la lista de pedidos:
 *
 * - Cliente
 * - Teléfono para pedidos Recoger
 * - Modalidad
 * - Hora de recolección
 * - Forma de pago
 *
 * Compatible con:
 *
 * - WooCommerce HPOS
 * - Lista clásica de pedidos
 */


/**
 * =========================================================
 * OBTENER CAMPO ADICIONAL DEL PEDIDO
 * =========================================================
 */

function aec_admin_order_checkout_field(
    $order,
    $field_id
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return '';
    }


    /**
     * Checkout Fields de WooCommerce Blocks.
     */
    try {

        if (
            class_exists(
                '\Automattic\WooCommerce\Blocks\Package'
            ) &&
            class_exists(
                '\Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields'
            )
        ) {

            $checkout_fields =
                \Automattic\WooCommerce\Blocks\Package
                    ::container()
                    ->get(
                        \Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class
                    );


            $value =
                $checkout_fields
                    ->get_field_from_object(
                        $field_id,
                        $order,
                        'other'
                    );


            if (
                is_scalar($value)
            ) {

                return sanitize_text_field(
                    (string) $value
                );
            }
        }

    } catch (Throwable $e) {

        /**
         * Continuar con fallback.
         */
    }


    return sanitize_text_field(
        (string)
        $order->get_meta(
            '_wc_other/' . $field_id,
            true
        )
    );
}


/**
 * =========================================================
 * MODALIDAD
 * =========================================================
 */

function aec_admin_order_tipo_codigo(
    $order
) {
    return aec_admin_order_checkout_field(
        $order,
        'antojitos-el-chino/tipo-pedido'
    );
}


function aec_admin_order_tipo_nombre(
    $order
) {
    $tipo =
        aec_admin_order_tipo_codigo(
            $order
        );


    if (
        $tipo === 'comer_aqui'
    ) {
        return 'Comer aquí';
    }


    if (
        $tipo === 'recoger'
    ) {
        return 'Recoger';
    }


    return '—';
}


/**
 * =========================================================
 * HORA DE RECOLECCIÓN
 * =========================================================
 */

function aec_admin_order_hora(
    $order
) {
    $tipo =
        aec_admin_order_tipo_codigo(
            $order
        );


    if (
        $tipo !== 'recoger'
    ) {
        return '—';
    }


    $hora =
        aec_admin_order_checkout_field(
            $order,
            'antojitos-el-chino/hora-recoleccion'
        );


    if (
        $hora === ''
    ) {
        return '—';
    }


    return $hora;
}


/**
 * =========================================================
 * CLIENTE
 * =========================================================
 */

function aec_admin_order_customer_name(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return '';
    }


    /**
     * Usar módulo de identificación
     * creado para el checkout.
     */
    if (
        function_exists(
            'aec_customer_order_name'
        )
    ) {

        $name =
            aec_customer_order_name(
                $order
            );


        if (
            $name !== ''
        ) {
            return $name;
        }
    }


    /**
     * Fallback para pedidos anteriores
     * o compatibilidad.
     */
    $name =
        trim(
            $order->get_billing_first_name()
            .
            ' '
            .
            $order->get_billing_last_name()
        );


    return sanitize_text_field(
        $name
    );
}


/**
 * =========================================================
 * TELÉFONO
 * =========================================================
 */

function aec_admin_order_customer_phone(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return '';
    }


    /**
     * Solo mostrar teléfono operativo
     * para pedidos Recoger.
     */
    if (
        aec_admin_order_tipo_codigo(
            $order
        ) !== 'recoger'
    ) {
        return '';
    }


    if (
        function_exists(
            'aec_customer_order_phone'
        )
    ) {

        $phone =
            aec_customer_order_phone(
                $order
            );


        if (
            $phone !== ''
        ) {
            return $phone;
        }
    }


    return sanitize_text_field(
        $order->get_billing_phone()
    );
}


/**
 * =========================================================
 * COLUMNAS HPOS
 * =========================================================
 */

function aec_admin_order_columns_hpos(
    $columns
) {
    $new_columns = [];


    foreach (
        $columns as
        $key => $label
    ) {

        $new_columns[$key] =
            $label;


        /**
         * Insertamos después de Estado.
         */
        if (
            $key === 'order_status'
        ) {

            $new_columns['aec_cliente'] =
                'Cliente';

            $new_columns['aec_modalidad'] =
                'Modalidad';

            $new_columns['aec_hora'] =
                'Hora';

            $new_columns['aec_pago'] =
                'Pago';
        }
    }


    return $new_columns;
}


add_filter(
    'manage_woocommerce_page_wc-orders_columns',
    'aec_admin_order_columns_hpos',
    20
);


/**
 * =========================================================
 * CONTENIDO COLUMNAS HPOS
 * =========================================================
 */

function aec_admin_order_column_content_hpos(
    $column,
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return;
    }


    switch ($column) {

        case 'aec_cliente':

            aec_admin_render_cliente(
                $order
            );

            break;


        case 'aec_modalidad':

            aec_admin_render_modalidad(
                $order
            );

            break;


        case 'aec_hora':

            aec_admin_render_hora(
                $order
            );

            break;


        case 'aec_pago':

            aec_admin_render_pago(
                $order
            );

            break;
    }
}


add_action(
    'manage_woocommerce_page_wc-orders_custom_column',
    'aec_admin_order_column_content_hpos',
    20,
    2
);


/**
 * =========================================================
 * COLUMNAS LISTA CLÁSICA
 * =========================================================
 */

function aec_admin_order_columns_legacy(
    $columns
) {
    $new_columns = [];


    foreach (
        $columns as
        $key => $label
    ) {

        $new_columns[$key] =
            $label;


        if (
            $key === 'order_status'
        ) {

            $new_columns['aec_cliente'] =
                'Cliente';

            $new_columns['aec_modalidad'] =
                'Modalidad';

            $new_columns['aec_hora'] =
                'Hora';

            $new_columns['aec_pago'] =
                'Pago';
        }
    }


    return $new_columns;
}


add_filter(
    'manage_edit-shop_order_columns',
    'aec_admin_order_columns_legacy',
    20
);


/**
 * =========================================================
 * CONTENIDO LISTA CLÁSICA
 * =========================================================
 */

function aec_admin_order_column_content_legacy(
    $column,
    $post_id
) {
    if (
        !in_array(
            $column,
            [
                'aec_cliente',
                'aec_modalidad',
                'aec_hora',
                'aec_pago',
            ],
            true
        )
    ) {
        return;
    }


    $order =
        wc_get_order(
            $post_id
        );


    if (
        !($order instanceof WC_Order)
    ) {
        return;
    }


    if (
        $column === 'aec_cliente'
    ) {

        aec_admin_render_cliente(
            $order
        );

        return;
    }


    if (
        $column === 'aec_modalidad'
    ) {

        aec_admin_render_modalidad(
            $order
        );

        return;
    }


    if (
        $column === 'aec_hora'
    ) {

        aec_admin_render_hora(
            $order
        );

        return;
    }


    if (
        $column === 'aec_pago'
    ) {

        aec_admin_render_pago(
            $order
        );
    }
}


add_action(
    'manage_shop_order_posts_custom_column',
    'aec_admin_order_column_content_legacy',
    20,
    2
);


/**
 * =========================================================
 * RENDER CLIENTE
 * =========================================================
 */

function aec_admin_render_cliente(
    $order
) {
    $name =
        aec_admin_order_customer_name(
            $order
        );


    $phone =
        aec_admin_order_customer_phone(
            $order
        );


    if (
        $name === ''
    ) {

        echo '—';

        return;
    }


    echo '<div class="aec-admin-customer">';


    echo '<strong class="aec-admin-customer-name">';

    echo esc_html(
        $name
    );

    echo '</strong>';


    if (
        $phone !== ''
    ) {

        $phone_href =
            preg_replace(
                '/\D+/',
                '',
                $phone
            );


        echo '<a class="aec-admin-customer-phone" href="tel:';

        echo esc_attr(
            $phone_href
        );

        echo '">';

        echo esc_html(
            $phone
        );

        echo '</a>';
    }


    echo '</div>';
}


/**
 * =========================================================
 * RENDER MODALIDAD
 * =========================================================
 */

function aec_admin_render_modalidad(
    $order
) {
    $tipo =
        aec_admin_order_tipo_codigo(
            $order
        );


    if (
        $tipo === 'recoger'
    ) {

        echo '<span class="aec-admin-badge aec-admin-badge-pickup">';
        echo 'Recoger';
        echo '</span>';

        return;
    }


    if (
        $tipo === 'comer_aqui'
    ) {

        echo '<span class="aec-admin-badge aec-admin-badge-dine">';
        echo 'Comer aquí';
        echo '</span>';

        return;
    }


    echo '—';
}


/**
 * =========================================================
 * RENDER HORA
 * =========================================================
 */

function aec_admin_render_hora(
    $order
) {
    $hora =
        aec_admin_order_hora(
            $order
        );


    if (
        $hora === '—'
    ) {

        echo '—';

        return;
    }


    echo '<strong class="aec-admin-pickup-time">';

    echo esc_html(
        $hora
    );

    echo '</strong>';
}


/**
 * =========================================================
 * RENDER PAGO
 * =========================================================
 */

function aec_admin_render_pago(
    $order
) {
    $payment =
        $order->get_payment_method_title();


    if (
        $payment === ''
    ) {

        echo '—';

        return;
    }


    echo esc_html(
        $payment
    );
}


/**
 * =========================================================
 * INFORMACIÓN OPERATIVA DENTRO DEL PEDIDO
 * =========================================================
 */

function aec_admin_order_details_box(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return;
    }


    $tipo =
        aec_admin_order_tipo_nombre(
            $order
        );


    $hora =
        aec_admin_order_hora(
            $order
        );


    $payment =
        $order->get_payment_method_title();


    $customer_name =
        aec_admin_order_customer_name(
            $order
        );


    $customer_phone =
        aec_admin_order_customer_phone(
            $order
        );


    ?>

    <div class="aec-admin-order-info">

        <div class="aec-admin-order-info-header">

            <div>

                <span class="aec-admin-order-eyebrow">
                    Antojitos el Chino
                </span>

                <h3>
                    Datos del pedido
                </h3>

            </div>


            <span class="aec-admin-order-number">

                Pedido
                #<?php
                echo esc_html(
                    $order->get_order_number()
                );
                ?>

            </span>

        </div>


        <div class="aec-admin-order-info-grid">


            <?php
            if (
                $customer_name !== ''
            ):
            ?>

                <div class="aec-admin-order-customer-card">

                    <span>
                        Pedido a nombre de
                    </span>

                    <strong class="aec-admin-order-customer-name">
                        <?php
                        echo esc_html(
                            $customer_name
                        );
                        ?>
                    </strong>

                </div>

            <?php endif; ?>


            <?php
            if (
                $customer_phone !== ''
            ):

                $phone_href =
                    preg_replace(
                        '/\D+/',
                        '',
                        $customer_phone
                    );
            ?>

                <div>

                    <span>
                        Teléfono
                    </span>

                    <strong>

                        <a
                            class="aec-admin-order-phone"
                            href="tel:<?php
                                echo esc_attr(
                                    $phone_href
                                );
                            ?>"
                        >
                            <?php
                            echo esc_html(
                                $customer_phone
                            );
                            ?>
                        </a>

                    </strong>

                </div>

            <?php endif; ?>


            <div>

                <span>
                    Modalidad
                </span>

                <strong>
                    <?php
                    echo esc_html(
                        $tipo
                    );
                    ?>
                </strong>

            </div>


            <?php
            if (
                $hora !== '—'
            ):
            ?>

                <div>

                    <span>
                        Hora de recolección
                    </span>

                    <strong class="aec-admin-order-hour">
                        <?php
                        echo esc_html(
                            $hora
                        );
                        ?>
                    </strong>

                </div>

            <?php endif; ?>


            <div>

                <span>
                    Forma de pago
                </span>

                <strong>
                    <?php
                    echo esc_html(
                        $payment !== ''
                            ? $payment
                            : '—'
                    );
                    ?>
                </strong>

            </div>


            <div>

                <span>
                    Total
                </span>

                <strong>
                    <?php
                    echo wp_kses_post(
                        $order->get_formatted_order_total()
                    );
                    ?>
                </strong>

            </div>


        </div>

    </div>

    <?php
}


add_action(
    'woocommerce_admin_order_data_after_order_details',
    'aec_admin_order_details_box',
    20
);


/**
 * =========================================================
 * CSS ADMIN
 * =========================================================
 */

function aec_admin_order_styles()
{
    $screen =
        function_exists(
            'get_current_screen'
        )
            ? get_current_screen()
            : null;


    if (
        !$screen
    ) {
        return;
    }


    $allowed_screens = [

        'woocommerce_page_wc-orders',

        'shop_order',

    ];


    if (
        !in_array(
            $screen->id,
            $allowed_screens,
            true
        )
    ) {
        return;
    }


    ?>

    <style>


        /* =================================================
           COLUMNAS
           ================================================= */

        .column-aec_cliente {
            width: 170px;
        }


        .column-aec_modalidad {
            width: 120px;
        }


        .column-aec_hora {
            width: 85px;
        }


        .column-aec_pago {
            width: 150px;
        }


        /* =================================================
           CLIENTE EN LISTA
           ================================================= */

        .aec-admin-customer {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }


        .aec-admin-customer-name {
            color: #222222;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.2;
        }


        .aec-admin-customer-phone {
            color: #294c73;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
            width: fit-content;
        }


        .aec-admin-customer-phone:hover {
            color: #c63b32;
            text-decoration: underline;
        }


        /* =================================================
           MODALIDAD
           ================================================= */

        .aec-admin-badge {
            border-radius: 999px;
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            padding: 7px 10px;
            white-space: nowrap;
        }


        .aec-admin-badge-pickup {
            background: #fff0ca;
            color: #684600;
        }


        .aec-admin-badge-dine {
            background: #e8f1e4;
            color: #315325;
        }


        /* =================================================
           HORA
           ================================================= */

        .aec-admin-pickup-time {
            color: #294c73;
            font-size: 15px;
        }


        /* =================================================
           BLOQUE DATOS DEL PEDIDO
           ================================================= */

        .aec-admin-order-info {
            background: #fffaf1;
            border: 1px solid #e2ddd3;
            border-radius: 14px;
            box-sizing: border-box;
            clear: both;
            margin-top: 24px;
            padding: 20px;
        }


        .aec-admin-order-info-header {
            align-items: center;
            display: flex;
            gap: 20px;
            justify-content: space-between;
            margin-bottom: 16px;
        }


        .aec-admin-order-eyebrow {
            color: #c63b32;
            display: block;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1px;
            margin-bottom: 3px;
            text-transform: uppercase;
        }


        .aec-admin-order-info h3 {
            color: #222222;
            font-size: 19px;
            margin: 0;
        }


        .aec-admin-order-number {
            background: #294c73;
            border-radius: 999px;
            color: #ffffff;
            font-size: 12px;
            font-weight: 800;
            padding: 7px 11px;
            white-space: nowrap;
        }


        .aec-admin-order-info-grid {
            display: grid;
            gap: 12px;
            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );
        }


        .aec-admin-order-info-grid > div {
            background: #ffffff;
            border: 1px solid #e7e2da;
            border-radius: 9px;
            box-sizing: border-box;
            min-width: 0;
            padding: 13px;
        }


        .aec-admin-order-info-grid span {
            color: #646970;
            display: block;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 5px;
        }


        .aec-admin-order-info-grid strong {
            color: #1d2327;
            font-size: 14px;
        }


        /* =================================================
           CLIENTE DESTACADO
           ================================================= */

        .aec-admin-order-customer-card {
            border-left:
                4px
                solid
                #294c73 !important;
        }


        .aec-admin-order-customer-name {
            color: #294c73 !important;
            display: block;
            font-size: 20px !important;
            font-weight: 800 !important;
            line-height: 1.15;
            text-transform: uppercase;
        }


        .aec-admin-order-phone {
            color: #294c73;
            font-weight: 700;
            text-decoration: none;
        }


        .aec-admin-order-phone:hover {
            color: #c63b32;
            text-decoration: underline;
        }


        .aec-admin-order-hour {
            color: #294c73 !important;
            font-size: 20px !important;
        }


        /* =================================================
           RESPONSIVE ADMIN
           ================================================= */

        @media (max-width: 782px) {

            .aec-admin-order-info-header {
                align-items: flex-start;
                flex-direction: column;
                gap: 10px;
            }


            .aec-admin-order-info-grid {
                grid-template-columns: 1fr;
            }


            .aec-admin-order-number {
                align-self: flex-start;
            }

        }

    </style>

    <?php
}


add_action(
    'admin_head',
    'aec_admin_order_styles'
);
