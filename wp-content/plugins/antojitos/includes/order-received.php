<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * =========================================================
 * ANTOJITOS EL CHINO
 * CONFIRMACIÓN DEL PEDIDO
 * =========================================================
 *
 * Sustituye los bloques genéricos de WooCommerce:
 *
 * - Estado genérico
 * - Resumen genérico
 * - Campos adicionales duplicados
 *
 * Conserva:
 *
 * - Nuestra confirmación personalizada
 * - Detalles de productos
 * - Totales
 * - Información específica de los productos
 *
 * IMPORTANTE:
 * No utilizamos el filtro global gettext.
 */


/**
 * =========================================================
 * OBTENER PEDIDO ACTUAL
 * =========================================================
 */

function aec_order_received_current_order()
{
    if (
        !function_exists('is_order_received_page') ||
        !is_order_received_page()
    ) {
        return false;
    }


    $order_id =
        absint(
            get_query_var('order-received')
        );


    /**
     * Fallback mediante la llave del pedido.
     */

    if (
        !$order_id &&
        isset($_GET['key'])
    ) {

        $order_key =
            wc_clean(
                wp_unslash(
                    $_GET['key']
                )
            );


        $order_id =
            wc_get_order_id_by_order_key(
                $order_key
            );
    }


    if (!$order_id) {
        return false;
    }


    $order =
        wc_get_order(
            $order_id
        );


    if (
        !($order instanceof WC_Order)
    ) {
        return false;
    }


    /**
     * Si existe una llave en la URL,
     * comprobamos que realmente pertenezca
     * al pedido solicitado.
     */

    if (
        isset($_GET['key'])
    ) {

        $request_key =
            wc_clean(
                wp_unslash(
                    $_GET['key']
                )
            );


        if (
            !hash_equals(
                (string) $order->get_order_key(),
                (string) $request_key
            )
        ) {
            return false;
        }
    }


    return $order;
}


/**
 * =========================================================
 * ¿SE PUEDE MOSTRAR NUESTRA CONFIRMACIÓN?
 * =========================================================
 *
 * Si WooCommerce identifica que el pedido falló,
 * fue cancelado o reembolsado, conservamos su
 * mensaje nativo de error.
 */

function aec_order_received_can_customize(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return false;
    }


    if (
        $order->has_status(
            [
                'failed',
                'cancelled',
                'refunded',
            ]
        )
    ) {
        return false;
    }


    return true;
}


/**
 * =========================================================
 * OBTENER CAMPO DEL CHECKOUT BLOCK
 * =========================================================
 */

function aec_order_received_checkout_field(
    $order,
    $field_id
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return '';
    }


    /**
     * Método de WooCommerce Checkout Blocks.
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
         * Si el servicio no estuviera disponible,
         * continuamos con el meta del pedido.
         */
    }


    /**
     * Fallback.
     */

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
 * TIPO DE PEDIDO
 * =========================================================
 */

function aec_order_received_tipo_codigo(
    $order
) {
    return aec_order_received_checkout_field(
        $order,
        'antojitos-el-chino/tipo-pedido'
    );
}


/**
 * =========================================================
 * NOMBRE DE LA MODALIDAD
 * =========================================================
 */

function aec_order_received_tipo_nombre(
    $order
) {
    $tipo =
        aec_order_received_tipo_codigo(
            $order
        );


    switch ($tipo) {

        case 'comer_aqui':

            return 'Comer aquí';


        case 'recoger':

            return 'Recoger';
    }


    return '';
}


/**
 * =========================================================
 * HORA DE RECOLECCIÓN
 * =========================================================
 */

function aec_order_received_hora(
    $order
) {
    return aec_order_received_checkout_field(
        $order,
        'antojitos-el-chino/hora-recoleccion'
    );
}


/**
 * =========================================================
 * MENSAJE SEGÚN MODALIDAD
 * =========================================================
 */

function aec_order_received_message(
    $order
) {
    $tipo =
        aec_order_received_tipo_codigo(
            $order
        );


    if (
        $tipo === 'recoger'
    ) {

        return
            'Recibimos tu pedido correctamente. '
            . 'Lo prepararemos para que esté listo '
            . 'a la hora que seleccionaste.';
    }


    if (
        $tipo === 'comer_aqui'
    ) {

        return
            'Recibimos tu pedido correctamente. '
            . 'Comenzaremos a prepararlo para ti.';
    }


    return
        'Recibimos tu pedido correctamente.';
}


/**
 * =========================================================
 * GENERAR TARJETA PERSONALIZADA
 * =========================================================
 */

function aec_order_received_card_html(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return '';
    }


    $tipo_codigo =
        aec_order_received_tipo_codigo(
            $order
        );


    $tipo_nombre =
        aec_order_received_tipo_nombre(
            $order
        );


    $hora =
        aec_order_received_hora(
            $order
        );


    $payment =
        $order->get_payment_method_title();


    $message =
        aec_order_received_message(
            $order
        );


    $menu_url =
        home_url('/menu/');


    ob_start();

    ?>

    <section
        class="aec-order-confirmation"
        aria-labelledby="aec-order-title"
    >


        <div class="aec-order-confirmation-top">


            <div
                class="aec-order-success-icon"
                aria-hidden="true"
            >
                ✓
            </div>


            <div class="aec-order-confirmation-heading">

                <span class="aec-order-eyebrow">
                    Pedido confirmado
                </span>


                <h1 id="aec-order-title">
                    ¡Pedido recibido!
                </h1>


                <p>
                    <?php
                    echo esc_html(
                        $message
                    );
                    ?>
                </p>

            </div>


        </div>


        <div class="aec-order-number-bar">

            <span>
                Número de pedido
            </span>

            <strong>
                #<?php
                echo esc_html(
                    $order->get_order_number()
                );
                ?>
            </strong>

        </div>


        <div class="aec-order-summary-grid">


            <?php
            if (
                $tipo_nombre !== ''
            ):
            ?>

                <div class="aec-order-summary-item">

                    <span class="aec-order-summary-label">
                        Modalidad
                    </span>

                    <strong>
                        <?php
                        echo esc_html(
                            $tipo_nombre
                        );
                        ?>
                    </strong>

                </div>

            <?php endif; ?>


            <?php
            if (
                $tipo_codigo === 'recoger' &&
                $hora !== ''
            ):
            ?>

                <div
                    class="
                        aec-order-summary-item
                        aec-order-summary-important
                    "
                >

                    <span class="aec-order-summary-label">
                        Hora de recolección
                    </span>

                    <strong>
                        <?php
                        echo esc_html(
                            $hora
                        );
                        ?>
                    </strong>

                </div>

            <?php endif; ?>


            <div class="aec-order-summary-item">

                <span class="aec-order-summary-label">
                    Forma de pago
                </span>

                <strong>
                    <?php
                    echo esc_html(
                        $payment
                    );
                    ?>
                </strong>

            </div>


            <div class="aec-order-summary-item">

                <span class="aec-order-summary-label">
                    Total
                </span>

                <strong class="aec-order-total">

                    <?php
                    echo wp_kses_post(
                        $order->get_formatted_order_total()
                    );
                    ?>

                </strong>

            </div>


        </div>


        <?php
        if (
            $tipo_codigo === 'recoger' &&
            $hora !== ''
        ):
        ?>

            <div class="aec-order-pickup-notice">

                <div>

                    <span class="aec-order-pickup-label">
                        Tu pedido estará preparado para
                    </span>

                    <strong>
                        <?php
                        echo esc_html(
                            $hora
                        );
                        ?>
                    </strong>

                </div>

            </div>

        <?php endif; ?>


        <div class="aec-order-actions">

            <a
                class="aec-order-menu-button"
                href="<?php
                    echo esc_url(
                        $menu_url
                    );
                ?>"
            >
                Volver al menú
            </a>

        </div>


    </section>

    <?php


    return ob_get_clean();
}


/**
 * =========================================================
 * SUSTITUIR BLOQUE "ORDER STATUS"
 * =========================================================
 *
 * WooCommerce normalmente coloca aquí:
 *
 * Pedido recibido
 * Gracias. Tu pedido ha sido recibido.
 *
 * Nosotros sustituimos ese bloque completo.
 */

function aec_replace_order_confirmation_status(
    $block_content,
    $block
) {
    $order =
        aec_order_received_current_order();


    if (
        !$order ||
        !aec_order_received_can_customize(
            $order
        )
    ) {
        return $block_content;
    }


    return aec_order_received_card_html(
        $order
    );
}


add_filter(
    'render_block_woocommerce/order-confirmation-status',
    'aec_replace_order_confirmation_status',
    20,
    2
);


/**
 * =========================================================
 * ELIMINAR RESUMEN GENÉRICO
 * =========================================================
 *
 * Elimina:
 *
 * Order #
 * Fecha
 * Total
 * Email
 * Payment
 *
 * Esa información relevante ya está presentada
 * correctamente en nuestra tarjeta.
 */

function aec_remove_order_confirmation_summary(
    $block_content,
    $block
) {
    $order =
        aec_order_received_current_order();


    if (
        !$order ||
        !aec_order_received_can_customize(
            $order
        )
    ) {
        return $block_content;
    }


    return '';
}


add_filter(
    'render_block_woocommerce/order-confirmation-summary',
    'aec_remove_order_confirmation_summary',
    20,
    2
);


/**
 * =========================================================
 * ELIMINAR CAMPOS ADICIONALES DUPLICADOS
 * =========================================================
 *
 * Tipo de pedido y hora ya aparecen
 * en nuestra tarjeta.
 */

function aec_remove_order_confirmation_additional_fields(
    $block_content,
    $block
) {
    $order =
        aec_order_received_current_order();


    if (
        !$order ||
        !aec_order_received_can_customize(
            $order
        )
    ) {
        return $block_content;
    }


    return '';
}


add_filter(
    'render_block_woocommerce/order-confirmation-additional-fields-wrapper',
    'aec_remove_order_confirmation_additional_fields',
    20,
    2
);


/**
 * =========================================================
 * ELIMINAR DIRECCIONES VACÍAS
 * =========================================================
 *
 * Nuestro negocio no solicita dirección
 * de facturación ni envío.
 */

function aec_remove_order_confirmation_address(
    $block_content,
    $block
) {
    $order =
        aec_order_received_current_order();


    if (
        !$order ||
        !aec_order_received_can_customize(
            $order
        )
    ) {
        return $block_content;
    }


    return '';
}


add_filter(
    'render_block_woocommerce/order-confirmation-billing-wrapper',
    'aec_remove_order_confirmation_address',
    20,
    2
);


add_filter(
    'render_block_woocommerce/order-confirmation-shipping-wrapper',
    'aec_remove_order_confirmation_address',
    20,
    2
);


/**
 * =========================================================
 * CARGAR CSS
 * =========================================================
 */

function aec_order_received_assets()
{
    if (
        !function_exists(
            'is_order_received_page'
        ) ||
        !is_order_received_page()
    ) {
        return;
    }


    wp_enqueue_style(
        'aec-order-received',
        AEC_PLUGIN_URL .
        'assets/css/order-received.css',
        [],
        '1.1.0'
    );
}


add_action(
    'wp_enqueue_scripts',
    'aec_order_received_assets',
    30
);
