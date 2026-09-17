<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * =========================================================
 * ANTOJITOS EL CHINO
 * PRIORIDAD OPERATIVA DE PEDIDOS
 * =========================================================
 *
 * Aplica únicamente a pedidos "Recoger".
 *
 * REGLAS:
 *
 * Más de 30 minutos:
 *     Programado
 *
 * De 16 a 30 minutos:
 *     Próximo
 *
 * De 1 a 15 minutos:
 *     Urgente
 *
 * Hora alcanzada o superada:
 *     Atrasado
 *
 *
 * IMPORTANTE:
 *
 * Actualmente el checkout solicita solamente hora,
 * por lo que la fecha de recolección se considera
 * igual a la fecha de creación del pedido.
 */


/**
 * =========================================================
 * OBTENER CAMPO DEL CHECKOUT
 * =========================================================
 */

function aec_priority_checkout_field(
    $order,
    $field_id
) {
    if (!($order instanceof WC_Order)) {
        return '';
    }


    /**
     * Reutilizar helper existente del módulo
     * order-admin.php cuando esté disponible.
     */
    if (
        function_exists(
            'aec_admin_order_checkout_field'
        )
    ) {

        return
            aec_admin_order_checkout_field(
                $order,
                $field_id
            );
    }


    /**
     * Fallback usando Checkout Fields.
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


            if (is_scalar($value)) {

                return sanitize_text_field(
                    (string) $value
                );
            }
        }

    } catch (Throwable $e) {

        /**
         * Continuar con fallback meta.
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
 * TIPO DE PEDIDO
 * =========================================================
 */

function aec_priority_order_type(
    $order
) {
    return aec_priority_checkout_field(
        $order,
        'antojitos-el-chino/tipo-pedido'
    );
}


/**
 * =========================================================
 * HORA DE RECOLECCIÓN
 * =========================================================
 */

function aec_priority_pickup_time(
    $order
) {
    $hora =
        aec_priority_checkout_field(
            $order,
            'antojitos-el-chino/hora-recoleccion'
        );


    /**
     * Validar HH:MM.
     */
    if (
        !preg_match(
            '/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/',
            $hora
        )
    ) {
        return '';
    }


    return $hora;
}


/**
 * =========================================================
 * PEDIDO FINALIZADO
 * =========================================================
 */

function aec_priority_order_is_closed(
    $order
) {
    if (!($order instanceof WC_Order)) {
        return true;
    }


    /**
     * Estados nativos donde la prioridad
     * ya no tiene sentido.
     */
    if (
        $order->has_status(
            [
                'completed',
                'cancelled',
                'refunded',
                'failed',
            ]
        )
    ) {
        return true;
    }


    /**
     * Nuestro estado personalizado Entregado.
     */
    if (
        function_exists(
            'aec_workflow_status_slug_by_label'
        )
    ) {

        $delivered =
            aec_workflow_status_slug_by_label(
                'Entregado'
            );


        if (
            $delivered !== '' &&
            $order->has_status(
                $delivered
            )
        ) {
            return true;
        }
    }


    return false;
}


/**
 * =========================================================
 * FECHA/HORA DE RECOLECCIÓN
 * =========================================================
 */

function aec_priority_pickup_datetime(
    $order
) {
    if (!($order instanceof WC_Order)) {
        return false;
    }


    $hora =
        aec_priority_pickup_time(
            $order
        );


    if ($hora === '') {
        return false;
    }


    /**
     * Fecha de creación del pedido.
     *
     * Actualmente:
     *
     * fecha de pedido = fecha de recolección.
     */

    $created =
        $order->get_date_created();


    if (!$created) {
        return false;
    }


    $created =
        clone $created;


    $timezone =
        wp_timezone();


    $created->setTimezone(
        $timezone
    );


    $date =
        $created->format(
            'Y-m-d'
        );


    try {

        return new DateTimeImmutable(
            $date . ' ' . $hora,
            $timezone
        );

    } catch (Exception $e) {

        return false;
    }
}


/**
 * =========================================================
 * MINUTOS RESTANTES
 * =========================================================
 *
 * Positivo:
 * todavía falta tiempo.
 *
 * Negativo:
 * la hora ya pasó.
 */

function aec_priority_minutes_remaining(
    $order
) {
    $pickup =
        aec_priority_pickup_datetime(
            $order
        );


    if (!$pickup) {
        return null;
    }


    $now =
        current_datetime();


    $seconds =
        $pickup->getTimestamp()
        -
        $now->getTimestamp();


    /**
     * Evitar que 10 segundos de atraso
     * aparezcan como 0 minutos.
     */
    if ($seconds >= 0) {

        return (int)
            ceil(
                $seconds / 60
            );
    }


    return (int)
        floor(
            $seconds / 60
        );
}


/**
 * =========================================================
 * FORMATEAR DURACIÓN
 * =========================================================
 */

function aec_priority_format_duration(
    $minutes
) {
    $minutes =
        abs(
            (int) $minutes
        );


    if ($minutes < 60) {

        return sprintf(
            '%d min',
            $minutes
        );
    }


    if ($minutes < 1440) {

        $hours =
            intdiv(
                $minutes,
                60
            );


        $remaining_minutes =
            $minutes % 60;


        if (
            $remaining_minutes === 0
        ) {

            return sprintf(
                '%d h',
                $hours
            );
        }


        return sprintf(
            '%d h %d min',
            $hours,
            $remaining_minutes
        );
    }


    $days =
        intdiv(
            $minutes,
            1440
        );


    $remaining =
        $minutes % 1440;


    $hours =
        intdiv(
            $remaining,
            60
        );


    if ($hours === 0) {

        return sprintf(
            '%d d',
            $days
        );
    }


    return sprintf(
        '%d d %d h',
        $days,
        $hours
    );
}


/**
 * =========================================================
 * CALCULAR PRIORIDAD
 * =========================================================
 */

function aec_priority_get(
    $order
) {
    if (!($order instanceof WC_Order)) {
        return false;
    }


    /**
     * Solo aplica a Recoger.
     */
    if (
        aec_priority_order_type(
            $order
        ) !== 'recoger'
    ) {
        return false;
    }


    /**
     * Pedido ya cerrado.
     */
    if (
        aec_priority_order_is_closed(
            $order
        )
    ) {

        return [

            'key' =>
                'closed',

            'label' =>
                'Finalizado',

            'detail' =>
                'Pedido cerrado',

        ];
    }


    $hora =
        aec_priority_pickup_time(
            $order
        );


    if ($hora === '') {

        return [

            'key' =>
                'missing',

            'label' =>
                'Sin hora',

            'detail' =>
                'Revisar pedido',

        ];
    }


    $minutes =
        aec_priority_minutes_remaining(
            $order
        );


    if ($minutes === null) {

        return [

            'key' =>
                'missing',

            'label' =>
                'Sin hora',

            'detail' =>
                'Revisar pedido',

        ];
    }


    /**
     * Hora alcanzada o superada.
     */
    if ($minutes <= 0) {

        return [

            'key' =>
                'overdue',

            'label' =>
                'Atrasado',

            'detail' =>
                'Hace ' .
                aec_priority_format_duration(
                    $minutes
                ),

        ];
    }


    /**
     * 1 a 15 minutos.
     */
    if ($minutes <= 15) {

        return [

            'key' =>
                'urgent',

            'label' =>
                'Urgente',

            'detail' =>
                'Faltan ' .
                aec_priority_format_duration(
                    $minutes
                ),

        ];
    }


    /**
     * 16 a 30 minutos.
     */
    if ($minutes <= 30) {

        return [

            'key' =>
                'soon',

            'label' =>
                'Próximo',

            'detail' =>
                'Faltan ' .
                aec_priority_format_duration(
                    $minutes
                ),

        ];
    }


    /**
     * Más de 30 minutos.
     */
    return [

        'key' =>
            'scheduled',

        'label' =>
            'Programado',

        'detail' =>
            'Faltan ' .
            aec_priority_format_duration(
                $minutes
            ),

    ];
}


/**
 * =========================================================
 * INSERTAR COLUMNA
 * =========================================================
 */

function aec_priority_insert_column(
    $columns
) {
    $new_columns = [];


    $inserted =
        false;


    foreach (
        $columns as
        $key => $label
    ) {

        $new_columns[$key] =
            $label;


        /**
         * order-admin.php ya agrega
         * la columna aec_hora.
         */
        if (
            $key === 'aec_hora'
        ) {

            $new_columns[
                'aec_prioridad'
            ] =
                'Prioridad';


            $inserted =
                true;
        }
    }


    /**
     * Fallback.
     */
    if (!$inserted) {

        $new_columns[
            'aec_prioridad'
        ] =
            'Prioridad';
    }


    return $new_columns;
}


/**
 * HPOS.
 */

add_filter(
    'manage_woocommerce_page_wc-orders_columns',
    'aec_priority_insert_column',
    25
);


/**
 * Lista clásica.
 */

add_filter(
    'manage_edit-shop_order_columns',
    'aec_priority_insert_column',
    25
);


/**
 * =========================================================
 * RENDERIZAR PRIORIDAD
 * =========================================================
 */

function aec_priority_render(
    $order
) {
    if (!($order instanceof WC_Order)) {
        echo '—';
        return;
    }


    $priority =
        aec_priority_get(
            $order
        );


    /**
     * Comer aquí.
     */
    if (!$priority) {

        echo '—';
        return;
    }


    $class =
        'aec-priority-badge '
        .
        'aec-priority-' .
        sanitize_html_class(
            $priority['key']
        );


    ?>

    <div class="aec-priority-cell">

        <span
            class="<?php
                echo esc_attr(
                    $class
                );
            ?>"
        >

            <?php
            echo esc_html(
                $priority['label']
            );
            ?>

        </span>


        <small>

            <?php
            echo esc_html(
                $priority['detail']
            );
            ?>

        </small>

    </div>

    <?php
}


/**
 * =========================================================
 * CONTENIDO HPOS
 * =========================================================
 */

function aec_priority_column_hpos(
    $column,
    $order
) {
    if (
        $column !==
        'aec_prioridad'
    ) {
        return;
    }


    aec_priority_render(
        $order
    );
}


add_action(
    'manage_woocommerce_page_wc-orders_custom_column',
    'aec_priority_column_hpos',
    25,
    2
);


/**
 * =========================================================
 * CONTENIDO LISTA CLÁSICA
 * =========================================================
 */

function aec_priority_column_legacy(
    $column,
    $post_id
) {
    if (
        $column !==
        'aec_prioridad'
    ) {
        return;
    }


    $order =
        wc_get_order(
            $post_id
        );


    aec_priority_render(
        $order
    );
}


add_action(
    'manage_shop_order_posts_custom_column',
    'aec_priority_column_legacy',
    25,
    2
);


/**
 * =========================================================
 * PANEL DE PRIORIDAD DENTRO DEL PEDIDO
 * =========================================================
 */

function aec_priority_order_panel(
    $order
) {
    if (!($order instanceof WC_Order)) {
        return;
    }


    /**
     * Solo Recoger.
     */
    if (
        aec_priority_order_type(
            $order
        ) !== 'recoger'
    ) {
        return;
    }


    $priority =
        aec_priority_get(
            $order
        );


    if (!$priority) {
        return;
    }


    $hora =
        aec_priority_pickup_time(
            $order
        );


    ?>

    <div
        class="
            aec-priority-panel
            aec-priority-panel-<?php
                echo esc_attr(
                    $priority['key']
                );
            ?>
        "
    >

        <div class="aec-priority-panel-header">

            <div>

                <span>
                    Prioridad del pedido
                </span>

                <strong>
                    <?php
                    echo esc_html(
                        $priority['label']
                    );
                    ?>
                </strong>

            </div>


            <?php
            if ($hora !== ''):
            ?>

                <div class="aec-priority-panel-time">

                    <span>
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

        </div>


        <div class="aec-priority-panel-detail">

            <?php
            echo esc_html(
                $priority['detail']
            );
            ?>

        </div>

    </div>

    <?php
}


/**
 * order-admin.php:
 * prioridad 20
 *
 * este panel:
 * prioridad 30
 *
 * order-workflow.php:
 * prioridad 40
 */

add_action(
    'woocommerce_admin_order_data_after_order_details',
    'aec_priority_order_panel',
    30
);


/**
 * =========================================================
 * ESTILOS ADMIN
 * =========================================================
 */

function aec_priority_admin_styles()
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


    if (!$screen) {
        return;
    }


    $allowed = [

        'woocommerce_page_wc-orders',
        'shop_order',

    ];


    if (
        !in_array(
            $screen->id,
            $allowed,
            true
        )
    ) {
        return;
    }


    ?>

    <style>

        /* =============================================
           COLUMNA
           ============================================= */

        .column-aec_prioridad {
            width: 125px;
        }


        .aec-priority-cell {
            align-items: flex-start;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }


        .aec-priority-cell small {
            color: #646970;
            font-size: 11px;
            line-height: 1.3;
        }


        /* =============================================
           BADGES
           ============================================= */

        .aec-priority-badge {
            border-radius: 999px;
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
            padding: 7px 10px;
            white-space: nowrap;
        }


        /* Programado */

        .aec-priority-scheduled {
            background: #eeeeee;
            color: #50575e;
        }


        /* Próximo */

        .aec-priority-soon {
            background: #fff3cd;
            color: #684d00;
        }


        /* Urgente */

        .aec-priority-urgent {
            background: #ffe2bd;
            color: #8a3d00;
        }


        /* Atrasado */

        .aec-priority-overdue {
            background: #f8d7da;
            color: #8a1f2d;
        }


        /* Sin hora */

        .aec-priority-missing {
            background: #f8d7da;
            color: #8a1f2d;
        }


        /* Cerrado */

        .aec-priority-closed {
            background: #e8f1e4;
            color: #315325;
        }


        /* =============================================
           PANEL DEL PEDIDO
           ============================================= */

        .aec-priority-panel {
            background: #f6f7f7;
            border: 1px solid #dcdcde;
            border-left: 5px solid #8c8f94;
            border-radius: 10px;
            box-sizing: border-box;
            clear: both;
            margin-top: 22px;
            padding: 18px;
        }


        .aec-priority-panel-header {
            align-items: center;
            display: flex;
            gap: 20px;
            justify-content: space-between;
        }


        .aec-priority-panel-header span {
            color: #646970;
            display: block;
            font-size: 12px;
            margin-bottom: 4px;
        }


        .aec-priority-panel-header strong {
            color: #1d2327;
            display: block;
            font-size: 20px;
        }


        .aec-priority-panel-time {
            text-align: right;
        }


        .aec-priority-panel-time strong {
            font-size: 26px;
        }


        .aec-priority-panel-detail {
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            font-size: 14px;
            font-weight: 700;
            margin-top: 14px;
            padding-top: 12px;
        }


        /* Programado */

        .aec-priority-panel-scheduled {
            border-left-color: #8c8f94;
        }


        /* Próximo */

        .aec-priority-panel-soon {
            background: #fffaf0;
            border-left-color: #f2b544;
        }


        /* Urgente */

        .aec-priority-panel-urgent {
            background: #fff4e8;
            border-left-color: #d97706;
        }


        /* Atrasado */

        .aec-priority-panel-overdue,
        .aec-priority-panel-missing {
            background: #fff1f2;
            border-left-color: #c63b32;
        }


        /* Cerrado */

        .aec-priority-panel-closed {
            background: #f3f8f1;
            border-left-color: #557a46;
        }


        /* =============================================
           MÓVIL ADMIN
           ============================================= */

        @media (max-width: 782px) {

            .aec-priority-panel-header {
                align-items: flex-start;
                flex-direction: column;
            }


            .aec-priority-panel-time {
                text-align: left;
            }

        }

    </style>

    <?php
}


add_action(
    'admin_head',
    'aec_priority_admin_styles'
);
