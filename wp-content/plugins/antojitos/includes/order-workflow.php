<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * =========================================================
 * ANTOJITOS EL CHINO
 * FLUJO OPERATIVO DE PEDIDOS
 * =========================================================
 *
 * Flujo:
 *
 * Confirmado
 *      ↓
 * En preparación
 *      ↓
 * Listo para recoger
 *      ↓
 * Entregado
 *
 * El módulo:
 *
 * - Detecta los estados ya registrados en WooCommerce.
 * - Muestra el avance del pedido.
 * - Agrega una acción rápida.
 * - Valida permisos.
 * - Valida nonce.
 * - Impide saltos de estado no permitidos.
 */


/**
 * =========================================================
 * DEFINICIÓN DEL FLUJO
 * =========================================================
 */

function aec_workflow_labels()
{
    return [

        'Confirmado',

        'En preparación',

        'Listo para recoger',

        'Entregado',

    ];
}


/**
 * =========================================================
 * RESOLVER SLUG DE ESTADO POR ETIQUETA
 * =========================================================
 *
 * wc_get_order_statuses() devuelve algo como:
 *
 * wc-processing => Procesando
 *
 * Nosotros buscamos los estados personalizados por
 * su nombre visible para no duplicar su registro.
 */

function aec_workflow_status_slug_by_label(
    $wanted_label
) {
    if (
        !function_exists(
            'wc_get_order_statuses'
        )
    ) {
        return '';
    }


    $statuses =
        wc_get_order_statuses();


    foreach (
        $statuses as
        $status_key => $label
    ) {

        if (
            trim((string) $label) !==
            trim((string) $wanted_label)
        ) {
            continue;
        }


        /**
         * WooCommerce usa:
         *
         * wc-confirmado
         *
         * update_status() necesita:
         *
         * confirmado
         */

        return preg_replace(
            '/^wc-/',
            '',
            (string) $status_key
        );
    }


    return '';
}


/**
 * =========================================================
 * MAPA REAL DEL FLUJO
 * =========================================================
 */

function aec_workflow_status_map()
{
    $map = [];


    foreach (
        aec_workflow_labels() as
        $label
    ) {

        $slug =
            aec_workflow_status_slug_by_label(
                $label
            );


        if (
            $slug === ''
        ) {
            continue;
        }


        $map[$label] =
            $slug;
    }


    return $map;
}


/**
 * =========================================================
 * SIGUIENTE ESTADO
 * =========================================================
 */

function aec_workflow_next_status(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return false;
    }


    $map =
        aec_workflow_status_map();


    /**
     * Si falta alguno de nuestros estados,
     * no inventamos una transición.
     */

    foreach (
        aec_workflow_labels() as
        $label
    ) {

        if (
            !isset($map[$label])
        ) {
            return false;
        }
    }


    $current =
        $order->get_status();


    $transitions = [

        $map['Confirmado'] => [

            'status' =>
                $map['En preparación'],

            'label' =>
                'Iniciar preparación',

        ],


        $map['En preparación'] => [

            'status' =>
                $map['Listo para recoger'],

            'label' =>
                'Marcar como listo',

        ],


        $map['Listo para recoger'] => [

            'status' =>
                $map['Entregado'],

            'label' =>
                'Marcar como entregado',

        ],

    ];


    if (
        !isset(
            $transitions[$current]
        )
    ) {
        return false;
    }


    return
        $transitions[$current];
}


/**
 * =========================================================
 * URL SEGURA DE CAMBIO DE ESTADO
 * =========================================================
 */

function aec_workflow_action_url(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return '';
    }


    $next =
        aec_workflow_next_status(
            $order
        );


    if (
        !$next
    ) {
        return '';
    }


    $url =
        add_query_arg(
            [
                'action' =>
                    'aec_advance_order_status',

                'order_id' =>
                    $order->get_id(),

                'target_status' =>
                    $next['status'],
            ],
            admin_url('admin-post.php')
        );


    return wp_nonce_url(
        $url,
        'aec_advance_order_' .
        $order->get_id()
    );
}


/**
 * =========================================================
 * PROCESAR CAMBIO DE ESTADO
 * =========================================================
 */

function aec_workflow_process_status_change()
{
    $order_id =
        isset($_GET['order_id'])
            ? absint(
                $_GET['order_id']
            )
            : 0;


    $target_status =
        isset($_GET['target_status'])
            ? sanitize_key(
                wp_unslash(
                    $_GET['target_status']
                )
            )
            : '';


    if (
        !$order_id ||
        $target_status === ''
    ) {

        wp_die(
            'Solicitud de cambio de estado inválida.'
        );
    }


    /**
     * Seguridad CSRF.
     */

    check_admin_referer(
        'aec_advance_order_' .
        $order_id
    );


    /**
     * Permisos.
     */

    if (
        !current_user_can(
            'manage_woocommerce'
        ) &&
        !current_user_can(
            'edit_shop_orders'
        )
    ) {

        wp_die(
            'No tienes permisos para modificar pedidos.'
        );
    }


    $order =
        wc_get_order(
            $order_id
        );


    if (
        !($order instanceof WC_Order)
    ) {

        wp_die(
            'No se encontró el pedido.'
        );
    }


    /**
     * Obtener transición permitida según
     * el estado ACTUAL del pedido.
     */

    $next =
        aec_workflow_next_status(
            $order
        );


    if (
        !$next
    ) {

        wp_die(
            'Este pedido no tiene una transición operativa disponible.'
        );
    }


    /**
     * No permitimos cambiar a un estado
     * distinto del siguiente estado esperado.
     */

    if (
        $next['status'] !==
        $target_status
    ) {

        wp_die(
            'La transición de estado solicitada no está permitida.'
        );
    }


    /**
     * Actualizar estado.
     */

    $order->update_status(
        $target_status,
        'Estado actualizado desde el flujo operativo de Antojitos el Chino.',
        true
    );


    /**
     * URL de regreso.
     */

    $redirect =
        wp_get_referer();


    if (
        !$redirect
    ) {

        $redirect =
            admin_url(
                'admin.php?page=wc-orders'
            );
    }


    $redirect =
        add_query_arg(
            [
                'aec_status_updated' =>
                    '1',

                'aec_order_id' =>
                    $order_id,
            ],
            $redirect
        );


    wp_safe_redirect(
        $redirect
    );

    exit;
}


add_action(
    'admin_post_aec_advance_order_status',
    'aec_workflow_process_status_change'
);


/**
 * =========================================================
 * MENSAJE DE CONFIRMACIÓN
 * =========================================================
 */

function aec_workflow_admin_notice()
{
    if (
        !isset(
            $_GET['aec_status_updated']
        )
    ) {
        return;
    }


    if (
        sanitize_key(
            wp_unslash(
                $_GET['aec_status_updated']
            )
        ) !== '1'
    ) {
        return;
    }


    $order_id =
        isset($_GET['aec_order_id'])
            ? absint(
                $_GET['aec_order_id']
            )
            : 0;


    ?>

    <div
        class="
            notice
            notice-success
            is-dismissible
        "
    >

        <p>

            <?php

            if ($order_id) {

                echo esc_html(
                    sprintf(
                        'Pedido #%d actualizado correctamente.',
                        $order_id
                    )
                );

            } else {

                echo esc_html(
                    'Pedido actualizado correctamente.'
                );
            }

            ?>

        </p>

    </div>

    <?php
}


add_action(
    'admin_notices',
    'aec_workflow_admin_notice'
);


/**
 * =========================================================
 * PROGRESO DEL PEDIDO
 * =========================================================
 */

function aec_workflow_current_index(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return -1;
    }


    $map =
        aec_workflow_status_map();


    $labels =
        aec_workflow_labels();


    $current =
        $order->get_status();


    foreach (
        $labels as
        $index => $label
    ) {

        if (
            isset($map[$label]) &&
            $map[$label] ===
            $current
        ) {

            return $index;
        }
    }


    return -1;
}


/**
 * =========================================================
 * PANEL DENTRO DEL PEDIDO
 * =========================================================
 */

function aec_workflow_order_panel(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return;
    }


    $labels =
        aec_workflow_labels();


    $map =
        aec_workflow_status_map();


    /**
     * Verificar que estén registrados
     * los cuatro estados.
     */

    foreach (
        $labels as
        $label
    ) {

        if (
            !isset(
                $map[$label]
            )
        ) {

            return;
        }
    }


    $current_index =
        aec_workflow_current_index(
            $order
        );


    /**
     * Si el pedido está en un estado nativo
     * fuera de nuestro flujo, no mostramos
     * el panel operativo.
     */

    if (
        $current_index < 0
    ) {
        return;
    }


    $next =
        aec_workflow_next_status(
            $order
        );


    ?>

    <div class="aec-workflow-panel">

        <h3>
            Flujo del pedido
        </h3>


        <div class="aec-workflow-steps">

            <?php

            foreach (
                $labels as
                $index => $label
            ):

                $classes = [
                    'aec-workflow-step',
                ];


                if (
                    $index <
                    $current_index
                ) {

                    $classes[] =
                        'is-complete';

                } elseif (
                    $index ===
                    $current_index
                ) {

                    $classes[] =
                        'is-current';
                }

                ?>

                <div
                    class="<?php
                    echo esc_attr(
                        implode(
                            ' ',
                            $classes
                        )
                    );
                    ?>"
                >

                    <span
                        class="aec-workflow-dot"
                    >

                        <?php

                        if (
                            $index <
                            $current_index
                        ) {

                            echo '✓';

                        } else {

                            echo esc_html(
                                $index + 1
                            );
                        }

                        ?>

                    </span>


                    <span
                        class="aec-workflow-step-label"
                    >
                        <?php
                        echo esc_html(
                            $label
                        );
                        ?>
                    </span>

                </div>

            <?php endforeach; ?>

        </div>


        <?php
        if ($next):
        ?>

            <div
                class="aec-workflow-action"
            >

                <a
                    class="
                        button
                        button-primary
                        button-large
                        aec-workflow-button
                    "
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

            </div>

        <?php else: ?>

            <div
                class="
                    aec-workflow-completed
                "
            >
                Pedido entregado
            </div>

        <?php endif; ?>

    </div>

    <?php
}


add_action(
    'woocommerce_admin_order_data_after_order_details',
    'aec_workflow_order_panel',
    40
);


/**
 * =========================================================
 * COLUMNA "ACCIÓN" - HPOS
 * =========================================================
 */

function aec_workflow_columns_hpos(
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
         * Nuestro módulo order-admin.php
         * ya agrega aec_pago.
         *
         * Colocamos Acción después.
         */

        if (
            $key === 'aec_pago'
        ) {

            $new_columns[
                'aec_workflow_action'
            ] =
                'Acción';
        }
    }


    /**
     * Fallback por si la columna
     * aec_pago no existiera.
     */

    if (
        !isset(
            $new_columns[
                'aec_workflow_action'
            ]
        )
    ) {

        $new_columns[
            'aec_workflow_action'
        ] =
            'Acción';
    }


    return $new_columns;
}


add_filter(
    'manage_woocommerce_page_wc-orders_columns',
    'aec_workflow_columns_hpos',
    30
);


/**
 * =========================================================
 * CONTENIDO COLUMNA HPOS
 * =========================================================
 */

function aec_workflow_column_hpos(
    $column,
    $order
) {
    if (
        $column !==
        'aec_workflow_action'
    ) {
        return;
    }


    if (
        !($order instanceof WC_Order)
    ) {
        return;
    }


    aec_workflow_render_list_action(
        $order
    );
}


add_action(
    'manage_woocommerce_page_wc-orders_custom_column',
    'aec_workflow_column_hpos',
    30,
    2
);


/**
 * =========================================================
 * COLUMNA LISTA CLÁSICA
 * =========================================================
 */

function aec_workflow_columns_legacy(
    $columns
) {
    $columns[
        'aec_workflow_action'
    ] =
        'Acción';


    return $columns;
}


add_filter(
    'manage_edit-shop_order_columns',
    'aec_workflow_columns_legacy',
    30
);


/**
 * =========================================================
 * CONTENIDO LISTA CLÁSICA
 * =========================================================
 */

function aec_workflow_column_legacy(
    $column,
    $post_id
) {
    if (
        $column !==
        'aec_workflow_action'
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


    aec_workflow_render_list_action(
        $order
    );
}


add_action(
    'manage_shop_order_posts_custom_column',
    'aec_workflow_column_legacy',
    30,
    2
);


/**
 * =========================================================
 * BOTÓN EN LISTA DE PEDIDOS
 * =========================================================
 */

function aec_workflow_render_list_action(
    $order
) {
    $next =
        aec_workflow_next_status(
            $order
        );


    if (
        !$next
    ) {

        if (
            aec_workflow_current_index(
                $order
            ) === 3
        ) {

            echo '<span class="aec-workflow-done">';
            echo 'Entregado';
            echo '</span>';

        } else {

            echo '—';
        }


        return;
    }


    ?>

    <a
        class="
            button
            aec-workflow-list-button
        "
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
 * ESTILOS ADMINISTRATIVOS
 * =========================================================
 */

function aec_workflow_admin_styles()
{
    if (
        !function_exists(
            'get_current_screen'
        )
    ) {
        return;
    }


    $screen =
        get_current_screen();


    if (
        !$screen
    ) {
        return;
    }


    ?>

    <style>

        /* =============================================
           PANEL
           ============================================= */

        .aec-workflow-panel {
            background: #ffffff;
            border:
                1px solid
                #dcdcde;
            border-radius: 12px;
            box-sizing: border-box;
            clear: both;
            margin-top: 22px;
            padding: 20px;
        }


        .aec-workflow-panel h3 {
            font-size: 16px;
            margin:
                0
                0
                18px;
        }


        /* =============================================
           PASOS
           ============================================= */

        .aec-workflow-steps {
            display: grid;
            gap: 8px;
            grid-template-columns:
                repeat(
                    4,
                    minmax(0, 1fr)
                );
        }


        .aec-workflow-step {
            align-items: center;
            background: #f6f7f7;
            border:
                1px solid
                #dcdcde;
            border-radius: 10px;
            display: flex;
            gap: 8px;
            min-height: 46px;
            padding:
                8px
                10px;
        }


        .aec-workflow-dot {
            align-items: center;
            background: #dcdcde;
            border-radius: 50%;
            color: #50575e;
            display: inline-flex;
            flex:
                0
                0
                26px;
            font-size: 12px;
            font-weight: 700;
            height: 26px;
            justify-content: center;
        }


        .aec-workflow-step-label {
            font-size: 12px;
            font-weight: 600;
        }


        /* Terminado */

        .aec-workflow-step.is-complete {
            background: #edf6e9;
            border-color: #b8d9ad;
        }


        .aec-workflow-step.is-complete
        .aec-workflow-dot {
            background: #557a46;
            color: #ffffff;
        }


        /* Actual */

        .aec-workflow-step.is-current {
            background: #fff4df;
            border-color: #f2b544;
        }


        .aec-workflow-step.is-current
        .aec-workflow-dot {
            background: #f2b544;
            color: #222222;
        }


        /* =============================================
           ACCIÓN PRINCIPAL
           ============================================= */

        .aec-workflow-action {
            border-top:
                1px solid
                #eeeeee;
            margin-top: 18px;
            padding-top: 18px;
        }


        .aec-workflow-button {
            background: #c63b32 !important;
            border-color: #c63b32 !important;
            font-weight: 700;
        }


        .aec-workflow-button:hover {
            background: #ac3028 !important;
            border-color: #ac3028 !important;
        }


        .aec-workflow-completed {
            background: #edf6e9;
            border-radius: 8px;
            color: #315325;
            font-weight: 700;
            margin-top: 18px;
            padding: 12px;
            text-align: center;
        }


        /* =============================================
           LISTA DE PEDIDOS
           ============================================= */

        .column-aec_workflow_action {
            width: 165px;
        }


        .aec-workflow-list-button {
            font-size: 11px !important;
            line-height: 1.25 !important;
            min-height: auto !important;
            padding:
                6px
                8px !important;
            white-space: normal;
        }


        .aec-workflow-done {
            color: #557a46;
            font-weight: 700;
        }


        /* =============================================
           RESPONSIVE ADMIN
           ============================================= */

        @media (max-width: 1100px) {

            .aec-workflow-steps {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }

        }


        @media (max-width: 782px) {

            .aec-workflow-steps {
                grid-template-columns: 1fr;
            }


            .aec-workflow-button {
                text-align: center;
                width: 100%;
            }

        }

    </style>

    <?php
}


add_action(
    'admin_head',
    'aec_workflow_admin_styles'
);
