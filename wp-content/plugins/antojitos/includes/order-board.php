<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * =========================================================
 * ANTOJITOS EL CHINO
 * PANTALLA OPERATIVA DE PEDIDOS
 * =========================================================
 *
 * Vista de operación para cocina / preparación.
 *
 * Grupos:
 *
 * - Urgentes / atrasados
 * - Próximos
 * - Comer aquí
 * - Listos
 *
 * La información se actualiza mediante AJAX.
 */


/**
 * =========================================================
 * PERMISOS
 * =========================================================
 */

function aec_order_board_user_can_view()
{
    return
        current_user_can('manage_woocommerce')
        ||
        current_user_can('edit_shop_orders');
}


/**
 * =========================================================
 * REGISTRAR MENÚ
 * =========================================================
 */

function aec_order_board_menu()
{
    add_submenu_page(
        'woocommerce',
        'Pantalla de pedidos',
        'Pantalla de pedidos',
        'edit_shop_orders',
        'aec-order-board',
        'aec_order_board_page'
    );
}


add_action(
    'admin_menu',
    'aec_order_board_menu',
    30
);


/**
 * =========================================================
 * URL DE EDICIÓN DEL PEDIDO
 * =========================================================
 */

function aec_order_board_edit_url(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return '';
    }


    if (
        method_exists(
            $order,
            'get_edit_order_url'
        )
    ) {

        return
            $order->get_edit_order_url();
    }


    return admin_url(
        'post.php?post='
        .
        $order->get_id()
        .
        '&action=edit'
    );
}


/**
 * =========================================================
 * TIPO DE PEDIDO
 * =========================================================
 */

function aec_order_board_type(
    $order
) {
    if (
        function_exists(
            'aec_priority_order_type'
        )
    ) {

        return
            aec_priority_order_type(
                $order
            );
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


/**
 * =========================================================
 * HORA DE RECOLECCIÓN
 * =========================================================
 */

function aec_order_board_pickup_time(
    $order
) {
    if (
        function_exists(
            'aec_priority_pickup_time'
        )
    ) {

        return
            aec_priority_pickup_time(
                $order
            );
    }


    if (
        function_exists(
            'aec_admin_order_hora'
        )
    ) {

        $hora =
            aec_admin_order_hora(
                $order
            );


        return
            $hora === '—'
                ? ''
                : $hora;
    }


    return '';
}


/**
 * =========================================================
 * NOMBRE DEL CLIENTE
 * =========================================================
 */

function aec_order_board_customer_name(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return '';
    }


    /**
     * Módulo de identificación
     * creado para nuestro checkout.
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
     * o compatibilidad con WooCommerce.
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
 * TELÉFONO DEL CLIENTE
 * =========================================================
 */

function aec_order_board_customer_phone(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return '';
    }


    /**
     * El teléfono solo se muestra
     * para pedidos Recoger.
     */
    if (
        aec_order_board_type(
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
 * ESTADOS ACTIVOS
 * =========================================================
 */

function aec_order_board_active_statuses()
{
    if (
        !function_exists(
            'aec_workflow_status_slug_by_label'
        )
    ) {
        return [];
    }


    $statuses = [];


    $labels = [

        'Confirmado',

        'En preparación',

        'Listo para recoger',

    ];


    foreach (
        $labels as
        $label
    ) {

        $slug =
            aec_workflow_status_slug_by_label(
                $label
            );


        if (
            $slug !== ''
        ) {

            $statuses[] =
                $slug;
        }
    }


    return array_values(
        array_unique(
            $statuses
        )
    );
}


/**
 * =========================================================
 * SLUG "LISTO"
 * =========================================================
 */

function aec_order_board_ready_status()
{
    if (
        !function_exists(
            'aec_workflow_status_slug_by_label'
        )
    ) {
        return '';
    }


    return
        aec_workflow_status_slug_by_label(
            'Listo para recoger'
        );
}


/**
 * =========================================================
 * CONSULTAR PEDIDOS ACTIVOS
 * =========================================================
 */

function aec_order_board_get_orders()
{
    if (
        !function_exists(
            'wc_get_orders'
        )
    ) {
        return [];
    }


    $statuses =
        aec_order_board_active_statuses();


    if (
        empty($statuses)
    ) {
        return [];
    }


    return wc_get_orders(
        [
            'status' =>
                $statuses,

            'limit' =>
                100,

            'orderby' =>
                'date',

            'order' =>
                'ASC',

            'return' =>
                'objects',
        ]
    );
}


/**
 * =========================================================
 * CLASIFICAR PEDIDOS
 * =========================================================
 */

function aec_order_board_groups()
{
    $groups = [

        'urgent' =>
            [],

        'soon' =>
            [],

        'dine' =>
            [],

        'ready' =>
            [],

        'review' =>
            [],

    ];


    $orders =
        aec_order_board_get_orders();


    $ready_status =
        aec_order_board_ready_status();


    foreach (
        $orders as
        $order
    ) {

        if (
            !($order instanceof WC_Order)
        ) {
            continue;
        }


        /**
         * LISTOS tienen prioridad sobre
         * cualquier otra clasificación.
         */
        if (
            $ready_status !== ''
            &&
            $order->get_status()
                === $ready_status
        ) {

            $groups['ready'][] =
                $order;

            continue;
        }


        $type =
            aec_order_board_type(
                $order
            );


        /**
         * COMER AQUÍ.
         */
        if (
            $type === 'comer_aqui'
        ) {

            $groups['dine'][] =
                $order;

            continue;
        }


        /**
         * RECOGER.
         */
        if (
            $type === 'recoger'
        ) {

            $priority =
                function_exists(
                    'aec_priority_get'
                )
                    ? aec_priority_get(
                        $order
                    )
                    : false;


            if (
                is_array($priority)
                &&
                isset(
                    $priority['key']
                )
            ) {

                if (
                    in_array(
                        $priority['key'],
                        [
                            'overdue',
                            'urgent',
                        ],
                        true
                    )
                ) {

                    $groups['urgent'][] =
                        $order;

                    continue;
                }


                if (
                    in_array(
                        $priority['key'],
                        [
                            'soon',
                            'scheduled',
                        ],
                        true
                    )
                ) {

                    $groups['soon'][] =
                        $order;

                    continue;
                }
            }


            /**
             * Recoger sin hora o información
             * inconsistente.
             */
            $groups['review'][] =
                $order;

            continue;
        }


        /**
         * Pedido que requiere revisión.
         */
        $groups['review'][] =
            $order;
    }


    return $groups;
}


/**
 * =========================================================
 * HORA DE CREACIÓN
 * =========================================================
 */

function aec_order_board_created_time(
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
            'H:i'
        );
}


/**
 * =========================================================
 * ESTADO VISIBLE
 * =========================================================
 */

function aec_order_board_status_name(
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
 * PRIORIDAD
 * =========================================================
 */

function aec_order_board_priority(
    $order
) {
    if (
        !function_exists(
            'aec_priority_get'
        )
    ) {
        return false;
    }


    return
        aec_priority_get(
            $order
        );
}


/**
 * =========================================================
 * PRODUCTOS DEL PEDIDO
 * =========================================================
 */

function aec_order_board_items(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return;
    }


    $items =
        $order->get_items(
            'line_item'
        );


    if (
        empty($items)
    ) {

        echo '<p class="aec-board-no-items">';
        echo 'Sin productos';
        echo '</p>';

        return;
    }


    echo '<div class="aec-board-items">';


    foreach (
        $items as
        $item
    ) {

        if (
            !($item instanceof WC_Order_Item_Product)
        ) {
            continue;
        }


        $quantity =
            $item->get_quantity();


        $name =
            $item->get_name();


        ?>

        <div class="aec-board-item">

            <div class="aec-board-item-main">

                <span class="aec-board-item-qty">

                    <?php
                    echo esc_html(
                        $quantity
                    );
                    ?>
                    ×

                </span>


                <strong>

                    <?php
                    echo esc_html(
                        $name
                    );
                    ?>

                </strong>

            </div>


            <?php

            $meta =
                wc_display_item_meta(
                    $item,
                    [
                        'echo' =>
                            false,

                        'separator' =>
                            '<br>',

                        'label_before' =>
                            '<strong>',

                        'label_after' =>
                            ':</strong> ',
                    ]
                );


            if (
                $meta !== ''
            ):
            ?>

                <div class="aec-board-item-meta">

                    <?php
                    echo wp_kses_post(
                        $meta
                    );
                    ?>

                </div>

            <?php endif; ?>

        </div>

        <?php
    }


    echo '</div>';
}


/**
 * =========================================================
 * BOTÓN DE FLUJO
 * =========================================================
 */

function aec_order_board_action(
    $order
) {
    if (
        !function_exists(
            'aec_workflow_next_status'
        )
        ||
        !function_exists(
            'aec_workflow_action_url'
        )
    ) {
        return;
    }


    $next =
        aec_workflow_next_status(
            $order
        );


    if (
        !$next
    ) {
        return;
    }


    ?>

    <a
        class="aec-board-action-button"
        href="<?php
            echo esc_url(
                aec_workflow_action_url(
                    $order
                )
            );
        ?>"
    >

        <?php
        echo esc_html(
            $next['label']
        );
        ?>

    </a>

    <?php
}


/**
 * =========================================================
 * RENDER TARJETA
 * =========================================================
 */

function aec_order_board_render_card(
    $order,
    $group
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return;
    }


    $type =
        aec_order_board_type(
            $order
        );


    $hora =
        aec_order_board_pickup_time(
            $order
        );


    $priority =
        aec_order_board_priority(
            $order
        );


    $status =
        aec_order_board_status_name(
            $order
        );


    $edit_url =
        aec_order_board_edit_url(
            $order
        );


    $customer_name =
        aec_order_board_customer_name(
            $order
        );


    $customer_phone =
        aec_order_board_customer_phone(
            $order
        );


    ?>

    <article
        class="
            aec-board-card
            aec-board-card-<?php
                echo esc_attr(
                    $group
                );
            ?>
        "
    >

        <header class="aec-board-card-header">

            <div>

                <a
                    class="aec-board-order-number"
                    href="<?php
                        echo esc_url(
                            $edit_url
                        );
                    ?>"
                >

                    Pedido
                    #<?php
                    echo esc_html(
                        $order->get_order_number()
                    );
                    ?>

                </a>


                <span class="aec-board-created">

                    Recibido:
                    <?php
                    echo esc_html(
                        aec_order_board_created_time(
                            $order
                        )
                    );
                    ?>

                </span>

            </div>


            <span
                class="
                    aec-board-type
                    <?php
                    echo
                        $type === 'recoger'
                            ? 'is-pickup'
                            : 'is-dine';
                    ?>
                "
            >

                <?php

                if (
                    $type === 'recoger'
                ) {

                    echo 'Recoger';

                } elseif (
                    $type === 'comer_aqui'
                ) {

                    echo 'Comer aquí';

                } else {

                    echo 'Revisar';
                }

                ?>

            </span>

        </header>


        <?php
        /**
         * =================================================
         * CLIENTE
         * =================================================
         */
        if (
            $customer_name !== ''
            ||
            $customer_phone !== ''
        ):
        ?>

            <div class="aec-board-customer">

                <span class="aec-board-customer-label">
                    Pedido a nombre de
                </span>


                <?php
                if (
                    $customer_name !== ''
                ):
                ?>

                    <strong
                        class="aec-board-customer-name"
                    >

                        <?php
                        echo esc_html(
                            $customer_name
                        );
                        ?>

                    </strong>

                <?php endif; ?>


                <?php
                if (
                    $type === 'recoger'
                    &&
                    $customer_phone !== ''
                ):

                    $phone_href =
                        preg_replace(
                            '/\D+/',
                            '',
                            $customer_phone
                        );
                ?>

                    <a
                        class="aec-board-customer-phone"
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

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <div class="aec-board-status-row">

            <span class="aec-board-status">

                <?php
                echo esc_html(
                    $status
                );
                ?>

            </span>


            <?php
            if (
                $type === 'recoger'
                &&
                $hora !== ''
            ):
            ?>

                <span
                    class="aec-board-pickup-time"
                >

                    <?php
                    echo esc_html(
                        $hora
                    );
                    ?>

                </span>

            <?php endif; ?>

        </div>


        <?php
        if (
            is_array($priority)
            &&
            isset(
                $priority['label'],
                $priority['detail']
            )
            &&
            $type === 'recoger'
        ):
        ?>

            <div
                class="
                    aec-board-priority
                    aec-board-priority-<?php
                        echo esc_attr(
                            $priority['key']
                        );
                    ?>
                "
            >

                <strong>

                    <?php
                    echo esc_html(
                        $priority['label']
                    );
                    ?>

                </strong>


                <span>

                    <?php
                    echo esc_html(
                        $priority['detail']
                    );
                    ?>

                </span>

            </div>

        <?php endif; ?>


        <?php
        aec_order_board_items(
            $order
        );
        ?>


        <footer class="aec-board-card-footer">

            <span class="aec-board-payment">

                <?php

                $payment =
                    $order
                        ->get_payment_method_title();


                echo esc_html(
                    $payment !== ''
                        ? $payment
                        : 'Sin método de pago'
                );

                ?>

            </span>


            <?php
            aec_order_board_action(
                $order
            );
            ?>

        </footer>

    </article>

    <?php
}


/**
 * =========================================================
 * RENDER GRUPO
 * =========================================================
 */

function aec_order_board_render_group(
    $key,
    $title,
    $orders
) {
    $count =
        count(
            $orders
        );


    ?>

    <section
        class="
            aec-board-section
            aec-board-section-<?php
                echo esc_attr(
                    $key
                );
            ?>
        "
    >

        <header class="aec-board-section-header">

            <h2>

                <?php
                echo esc_html(
                    $title
                );
                ?>

            </h2>


            <span class="aec-board-count">

                <?php
                echo esc_html(
                    $count
                );
                ?>

            </span>

        </header>


        <?php
        if (
            $count === 0
        ):
        ?>

            <div class="aec-board-empty">

                No hay pedidos en esta sección.

            </div>

        <?php else: ?>

            <div class="aec-board-grid">

                <?php

                foreach (
                    $orders as
                    $order
                ) {

                    aec_order_board_render_card(
                        $order,
                        $key
                    );
                }

                ?>

            </div>

        <?php endif; ?>

    </section>

    <?php
}


/**
 * =========================================================
 * CONTENIDO COMPLETO DEL TABLERO
 * =========================================================
 */

function aec_order_board_render_content()
{
    $groups =
        aec_order_board_groups();


    ob_start();


    aec_order_board_render_group(
        'urgent',
        'Urgentes / atrasados',
        $groups['urgent']
    );


    aec_order_board_render_group(
        'soon',
        'Próximos',
        $groups['soon']
    );


    aec_order_board_render_group(
        'dine',
        'Comer aquí',
        $groups['dine']
    );


    aec_order_board_render_group(
        'ready',
        'Listos',
        $groups['ready']
    );


    /**
     * Solo aparece cuando realmente
     * existe alguna inconsistencia.
     */
    if (
        !empty(
            $groups['review']
        )
    ) {

        aec_order_board_render_group(
            'review',
            'Requieren revisión',
            $groups['review']
        );
    }


    return
        ob_get_clean();
}


/**
 * =========================================================
 * PÁGINA PRINCIPAL
 * =========================================================
 */

function aec_order_board_page()
{
    if (
        !aec_order_board_user_can_view()
    ) {

        wp_die(
            'No tienes permisos para ver esta pantalla.'
        );
    }


    ?>

    <div class="wrap aec-order-board-page">

        <div class="aec-board-topbar">

            <div>

                <span class="aec-board-eyebrow">
                    Antojitos el Chino
                </span>

                <h1>
                    Pedidos activos
                </h1>

                <p>
                    Actualización automática cada 15 segundos.
                </p>

            </div>


            <div class="aec-board-topbar-actions">

                <span
                    id="aec-board-updated"
                    class="aec-board-updated"
                >
                    Actualizando...
                </span>


                <button
                    type="button"
                    class="button"
                    id="aec-board-refresh"
                >
                    Actualizar ahora
                </button>


                <button
                    type="button"
                    class="button button-primary"
                    id="aec-board-fullscreen"
                >
                    Pantalla completa
                </button>

            </div>

        </div>


        <div
            id="aec-order-board"
            class="aec-order-board"
        >

            <?php
            echo wp_kses_post(
                aec_order_board_render_content()
            );
            ?>

        </div>


        <div
            id="aec-board-error"
            class="aec-board-error"
            hidden
        >
            No se pudo actualizar la pantalla.
            Se intentará nuevamente.
        </div>

    </div>

    <?php
}


/**
 * =========================================================
 * AJAX
 * =========================================================
 */

function aec_order_board_ajax_refresh()
{
    check_ajax_referer(
        'aec_order_board_refresh',
        'nonce'
    );


    if (
        !aec_order_board_user_can_view()
    ) {

        wp_send_json_error(
            [
                'message' =>
                    'Sin permisos.',
            ],
            403
        );
    }


    wp_send_json_success(
        [
            'html' =>
                aec_order_board_render_content(),

            'updated' =>
                wp_date(
                    'H:i:s'
                ),
        ]
    );
}


add_action(
    'wp_ajax_aec_order_board_refresh',
    'aec_order_board_ajax_refresh'
);


/**
 * =========================================================
 * ASSETS
 * =========================================================
 */

function aec_order_board_assets(
    $hook
) {
    if (
        $hook !==
        'woocommerce_page_aec-order-board'
    ) {
        return;
    }


    wp_enqueue_style(
        'aec-order-board',
        AEC_PLUGIN_URL
        .
        'assets/css/order-board.css',
        [],
        '1.2.0'
    );


    wp_enqueue_script(
        'aec-order-board',
        AEC_PLUGIN_URL
        .
        'assets/js/order-board.js',
        [],
        '1.2.0',
        true
    );


    wp_localize_script(
        'aec-order-board',
        'AECOrderBoard',
        [
            'ajaxUrl' =>
                admin_url(
                    'admin-ajax.php'
                ),

            'nonce' =>
                wp_create_nonce(
                    'aec_order_board_refresh'
                ),

            'interval' =>
                15000,
        ]
    );
}


add_action(
    'admin_enqueue_scripts',
    'aec_order_board_assets'
);
