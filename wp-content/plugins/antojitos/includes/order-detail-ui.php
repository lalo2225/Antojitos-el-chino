<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * =========================================================
 * ANTOJITOS EL CHINO
 * DETALLE OPERATIVO DEL PEDIDO
 * =========================================================
 *
 * Objetivos:
 *
 * - Mostrar primero la información importante para operación.
 * - Mantener intacta la lógica de WooCommerce.
 * - Reutilizar los datos del checkout y workflow existentes.
 * - No alterar la pantalla en modo técnico.
 */


/**
 * =========================================================
 * OBTENER PEDIDO ACTUAL
 * =========================================================
 */

function aec_order_detail_ui_current_order()
{
    if (
        !is_admin()
        ||
        !function_exists(
            'wc_get_order'
        )
    ) {
        return false;
    }


    $order_id = 0;


    /**
     * HPOS.
     *
     * Ejemplo:
     * admin.php?page=wc-orders&action=edit&id=154
     */
    if (
        isset($_GET['id'])
    ) {

        $order_id =
            absint(
                wp_unslash(
                    $_GET['id']
                )
            );
    }


    /**
     * WooCommerce clásico.
     *
     * Ejemplo:
     * post.php?post=154&action=edit
     */
    if (
        !$order_id
        &&
        isset($_GET['post'])
    ) {

        $order_id =
            absint(
                wp_unslash(
                    $_GET['post']
                )
            );
    }


    if (
        !$order_id
    ) {
        return false;
    }


    $order =
        wc_get_order(
            $order_id
        );


    return
        $order instanceof WC_Order
            ? $order
            : false;
}


/**
 * =========================================================
 * ¿ESTAMOS EN DETALLE DE PEDIDO?
 * =========================================================
 */

function aec_order_detail_ui_is_screen()
{
    if (
        !is_admin()
    ) {
        return false;
    }


    /**
     * =====================================================
     * HPOS DE WOOCOMMERCE
     * =====================================================
     *
     * Ejemplo:
     *
     * admin.php?page=wc-orders&action=edit&id=101
     */

    $page =
        isset($_GET['page'])
            ? sanitize_key(
                wp_unslash(
                    $_GET['page']
                )
            )
            : '';


    $action =
        isset($_GET['action'])
            ? sanitize_key(
                wp_unslash(
                    $_GET['action']
                )
            )
            : '';


    $order_id =
        isset($_GET['id'])
            ? absint(
                wp_unslash(
                    $_GET['id']
                )
            )
            : 0;


    if (
        $page === 'wc-orders'
        &&
        $action === 'edit'
        &&
        $order_id > 0
    ) {
        return true;
    }


    /**
     * =====================================================
     * PEDIDOS CLÁSICOS
     * =====================================================
     *
     * Ejemplo:
     *
     * post.php?post=101&action=edit
     */

    $post_id =
        isset($_GET['post'])
            ? absint(
                wp_unslash(
                    $_GET['post']
                )
            )
            : 0;


    if (
        $action === 'edit'
        &&
        $post_id > 0
    ) {

        $post_type =
            get_post_type(
                $post_id
            );


        if (
            $post_type === 'shop_order'
        ) {
            return true;
        }
    }


    /**
     * =====================================================
     * FALLBACK POR SCREEN ID
     * =====================================================
     */

    $screen =
        function_exists(
            'get_current_screen'
        )
            ? get_current_screen()
            : null;


    if (
        !$screen
    ) {
        return false;
    }


    if (
        in_array(
            $screen->id,
            [
                'woocommerce_page_wc-orders',
                'shop_order',
            ],
            true
        )
    ) {

        return
            aec_order_detail_ui_current_order()
            instanceof WC_Order;
    }


    return false;
}


/**
 * =========================================================
 * ¿MODO OPERATIVO?
 * =========================================================
 */

function aec_order_detail_ui_is_operation_mode()
{
    if (
        function_exists(
            'aec_admin_experience_is_operation_mode'
        )
    ) {

        return
            aec_admin_experience_is_operation_mode();
    }


    /**
     * Fallback.
     *
     * Si por algún motivo el módulo de experiencia
     * administrativa no está disponible, mostramos
     * la interfaz igualmente a usuarios WooCommerce.
     */
    return
        current_user_can(
            'manage_woocommerce'
        );
}


/**
 * =========================================================
 * QUITAR BLOQUE ANTERIOR SOLO EN MODO OPERATIVO
 * =========================================================
 *
 * order-admin.php ya agrega un bloque "Datos del pedido".
 *
 * Como esta pantalla nueva lo sustituye visualmente,
 * eliminamos únicamente ese bloque en modo operativo.
 *
 * Las columnas de la lista de pedidos siguen intactas.
 */

function aec_order_detail_ui_setup()
{
    if (
        !aec_order_detail_ui_is_operation_mode()
    ) {
        return;
    }


    if (
        function_exists(
            'aec_admin_order_details_box'
        )
    ) {

        remove_action(
            'woocommerce_admin_order_data_after_order_details',
            'aec_admin_order_details_box',
            20
        );
    }
}


add_action(
    'admin_init',
    'aec_order_detail_ui_setup',
    100
);


/**
 * =========================================================
 * NOMBRE
 * =========================================================
 */

function aec_order_detail_ui_customer_name(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return 'Sin nombre';
    }


    if (
        function_exists(
            'aec_admin_order_customer_name'
        )
    ) {

        $name =
            trim(
                (string)
                aec_admin_order_customer_name(
                    $order
                )
            );


        if (
            $name !== ''
        ) {
            return $name;
        }
    }


    if (
        function_exists(
            'aec_customer_order_name'
        )
    ) {

        $name =
            trim(
                (string)
                aec_customer_order_name(
                    $order
                )
            );


        if (
            $name !== ''
        ) {
            return $name;
        }
    }


    $name =
        trim(
            $order->get_billing_first_name()
            .
            ' '
            .
            $order->get_billing_last_name()
        );


    return
        $name !== ''
            ? sanitize_text_field(
                $name
            )
            : 'Sin nombre';
}


/**
 * =========================================================
 * TELÉFONO
 * =========================================================
 */

function aec_order_detail_ui_customer_phone(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return '';
    }


    if (
        function_exists(
            'aec_admin_order_customer_phone'
        )
    ) {

        return sanitize_text_field(
            (string)
            aec_admin_order_customer_phone(
                $order
            )
        );
    }


    if (
        function_exists(
            'aec_customer_order_phone'
        )
    ) {

        return sanitize_text_field(
            (string)
            aec_customer_order_phone(
                $order
            )
        );
    }


    return sanitize_text_field(
        (string)
        $order->get_billing_phone()
    );
}


/**
 * =========================================================
 * MODALIDAD
 * =========================================================
 */

function aec_order_detail_ui_type_code(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return '';
    }


    if (
        function_exists(
            'aec_admin_order_tipo_codigo'
        )
    ) {

        return
            aec_admin_order_tipo_codigo(
                $order
            );
    }


    return '';
}


function aec_order_detail_ui_type_name(
    $order
) {
    if (
        function_exists(
            'aec_admin_order_tipo_nombre'
        )
    ) {

        return
            aec_admin_order_tipo_nombre(
                $order
            );
    }


    $type =
        aec_order_detail_ui_type_code(
            $order
        );


    if (
        $type === 'recoger'
    ) {
        return 'Recoger';
    }


    if (
        $type === 'comer_aqui'
    ) {
        return 'Comer aquí';
    }


    return '—';
}


/**
 * =========================================================
 * HORA
 * =========================================================
 */

function aec_order_detail_ui_pickup_time(
    $order
) {
    if (
        aec_order_detail_ui_type_code(
            $order
        )
        !== 'recoger'
    ) {
        return '—';
    }


    if (
        function_exists(
            'aec_admin_order_hora'
        )
    ) {

        $time =
            (string)
            aec_admin_order_hora(
                $order
            );


        return
            $time !== ''
                ? $time
                : '—';
    }


    return '—';
}


/**
 * =========================================================
 * ESTADO
 * =========================================================
 */

function aec_order_detail_ui_status_name(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return '';
    }


    return
        wc_get_order_status_name(
            $order->get_status()
        );
}


/**
 * =========================================================
 * SIGUIENTE PASO DEL WORKFLOW
 * =========================================================
 */

function aec_order_detail_ui_next_step(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return false;
    }


    if (
        !function_exists(
            'aec_workflow_next_status'
        )
        ||
        !function_exists(
            'aec_workflow_action_url'
        )
    ) {
        return false;
    }


    $next =
        aec_workflow_next_status(
            $order
        );


    if (
        !is_array(
            $next
        )
        ||
        empty(
            $next['label']
        )
    ) {
        return false;
    }


    return [

        'label' =>
            sanitize_text_field(
                (string)
                $next['label']
            ),

        'url' =>
            aec_workflow_action_url(
                $order
            ),

    ];
}


/**
 * =========================================================
 * FECHA Y HORA DE RECEPCIÓN
 * =========================================================
 */

function aec_order_detail_ui_created(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return '';
    }


    $created =
        $order->get_date_created();


    if (
        !$created
    ) {
        return '';
    }


    $created =
        clone $created;


    $created->setTimezone(
        wp_timezone()
    );


    return
        $created->format(
            'd/m/Y · H:i'
        );
}


/**
 * =========================================================
 * RENDER PRINCIPAL
 * =========================================================
 */

function aec_order_detail_ui_render()
{
    if (
        !aec_order_detail_ui_is_operation_mode()
        ||
        !aec_order_detail_ui_is_screen()
    ) {
        return;
    }


    $order =
        aec_order_detail_ui_current_order();


    if (
        !($order instanceof WC_Order)
    ) {
        return;
    }


    $name =
        aec_order_detail_ui_customer_name(
            $order
        );


    $phone =
        aec_order_detail_ui_customer_phone(
            $order
        );


    $type =
        aec_order_detail_ui_type_code(
            $order
        );


    $type_name =
        aec_order_detail_ui_type_name(
            $order
        );


    $pickup_time =
        aec_order_detail_ui_pickup_time(
            $order
        );


    $status =
        aec_order_detail_ui_status_name(
            $order
        );


    $status_class =
        sanitize_html_class(
            $order->get_status()
        );


    $payment =
        $order->get_payment_method_title();


    $next =
        aec_order_detail_ui_next_step(
            $order
        );


    $created =
        aec_order_detail_ui_created(
            $order
        );


    $orders_url =
        admin_url(
            'admin.php?page=wc-orders'
        );


    $board_url =
        admin_url(
            'admin.php?page=aec-order-board'
        );


    $transfers_url =
        admin_url(
            'admin.php?page=aec-transferencias'
        );


    $is_transfer_pending =
        (
            $type === 'recoger'
            &&
            $order->get_payment_method()
                === 'bacs'
            &&
            $order->get_status()
                === 'on-hold'
            &&
            $order->get_meta(
                '_aec_transfer_validation_status',
                true
            )
                !== 'validated'
        );


    ?>

    <section class="aec-order-detail-command">


        <!-- ===============================================
             CABECERA
             =============================================== -->

        <header class="aec-order-detail-header">

            <div>

                <span class="aec-order-detail-eyebrow">
                    Antojitos el Chino
                </span>


                <div class="aec-order-detail-title-row">

                    <h1>

                        Pedido

                        #<?php
                        echo esc_html(
                            $order->get_order_number()
                        );
                        ?>

                    </h1>


                    <span
                        class="
                            aec-order-detail-status
                            status-<?php
                            echo esc_attr(
                                $status_class
                            );
                            ?>
                        "
                    >

                        <?php
                        echo esc_html(
                            $status
                        );
                        ?>

                    </span>

                </div>


                <?php
                if (
                    $created !== ''
                ):
                ?>

                    <span class="aec-order-detail-created">

                        Recibido:
                        <?php
                        echo esc_html(
                            $created
                        );
                        ?>

                    </span>

                <?php endif; ?>

            </div>


            <div class="aec-order-detail-header-actions">

                <a
                    href="<?php
                        echo esc_url(
                            $orders_url
                        );
                    ?>"
                    class="
                        aec-order-detail-button
                        is-secondary
                    "
                >
                    ← Pedidos
                </a>


                <a
                    href="<?php
                        echo esc_url(
                            $board_url
                        );
                    ?>"
                    class="
                        aec-order-detail-button
                        is-secondary
                    "
                >
                    Pantalla de pedidos
                </a>

            </div>

        </header>


        <!-- ===============================================
             CLIENTE
             =============================================== -->

        <div class="aec-order-detail-customer">

            <span>
                Pedido a nombre de
            </span>


            <strong>
                <?php
                echo esc_html(
                    $name
                );
                ?>
            </strong>


            <?php
            if (
                $type === 'recoger'
                &&
                $phone !== ''
            ):

                $phone_href =
                    preg_replace(
                        '/\D+/',
                        '',
                        $phone
                    );

            ?>

                <a
                    href="tel:<?php
                        echo esc_attr(
                            $phone_href
                        );
                    ?>"
                >
                    <?php
                    echo esc_html(
                        $phone
                    );
                    ?>
                </a>

            <?php endif; ?>

        </div>


        <!-- ===============================================
             DATOS PRINCIPALES
             =============================================== -->

        <div class="aec-order-detail-summary">


            <div class="aec-order-detail-summary-item">

                <span>
                    Modalidad
                </span>

                <strong>
                    <?php
                    echo esc_html(
                        $type_name
                    );
                    ?>
                </strong>

            </div>


            <?php
            if (
                $type === 'recoger'
            ):
            ?>

                <div
                    class="
                        aec-order-detail-summary-item
                        is-highlight
                    "
                >

                    <span>
                        Hora de recolección
                    </span>

                    <strong>
                        <?php
                        echo esc_html(
                            $pickup_time
                        );
                        ?>
                    </strong>

                </div>

            <?php endif; ?>


            <div class="aec-order-detail-summary-item">

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


            <div
                class="
                    aec-order-detail-summary-item
                    is-total
                "
            >

                <span>
                    Total
                </span>

                <strong>

                    <?php
                    echo wp_kses_post(
                        $order
                            ->get_formatted_order_total()
                    );
                    ?>

                </strong>

            </div>

        </div>


        <!-- ===============================================
             TRANSFERENCIA PENDIENTE
             =============================================== -->

        <?php
        if (
            $is_transfer_pending
        ):
        ?>

            <div class="aec-order-detail-payment-alert">

                <div>

                    <span
                        class="
                            dashicons
                            dashicons-warning
                        "
                    ></span>


                    <div>

                        <strong>
                            Transferencia pendiente de validar
                        </strong>


                        <p>
                            Revisa el comprobante antes de enviar
                            este pedido a preparación.
                        </p>

                    </div>

                </div>


                <a
                    href="<?php
                        echo esc_url(
                            $transfers_url
                        );
                    ?>"
                >
                    Revisar transferencia
                </a>

            </div>

        <?php endif; ?>


        <!-- ===============================================
             SIGUIENTE ACCIÓN
             =============================================== -->

        <?php
        if (
            !$is_transfer_pending
            &&
            is_array(
                $next
            )
        ):
        ?>

            <div class="aec-order-detail-workflow">

                <div>

                    <span>
                        Siguiente paso
                    </span>


                    <strong>
                        <?php
                        echo esc_html(
                            $next['label']
                        );
                        ?>
                    </strong>

                </div>


                <a
                    class="aec-order-detail-next"
                    href="<?php
                        echo esc_url(
                            $next['url']
                        );
                    ?>"
                >

                    Avanzar a:

                    <?php
                    echo esc_html(
                        $next['label']
                    );
                    ?>

                    →

                </a>

            </div>

        <?php endif; ?>


    </section>

    <?php
}


/**
 * =========================================================
 * MOSTRAR ARRIBA DE LA PANTALLA
 * =========================================================
 */
add_action(
    'all_admin_notices',
    'aec_order_detail_ui_render',
    1
);


/**
 * =========================================================
 * CARGAR CSS
 * =========================================================
 */

function aec_order_detail_ui_assets()
{
    if (
        !aec_order_detail_ui_is_operation_mode()
        ||
        !aec_order_detail_ui_is_screen()
    ) {
        return;
    }


    $relative =
        'assets/css/order-detail-admin.css';


    $path =
        AEC_PLUGIN_DIR
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
        'aec-order-detail-admin',
        AEC_PLUGIN_URL
        .
        $relative,
        [
            'dashicons',
        ],
        (string)
        filemtime(
            $path
        )
    );
}


add_action(
    'admin_enqueue_scripts',
    'aec_order_detail_ui_assets',
    50
);
