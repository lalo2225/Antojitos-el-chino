<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * =========================================================
 * ANTOJITOS EL CHINO
 * ENTRADA DE PEDIDOS AL FLUJO OPERATIVO
 * =========================================================
 *
 * Objetivo:
 *
 * Hacer que los pedidos válidos entren automáticamente
 * al estado personalizado "Confirmado".
 *
 *
 * REGLAS:
 *
 * EFECTIVO
 *     Pedido realizado
 *          ↓
 *     Confirmado
 *
 *
 * MERCADO PAGO / TARJETA
 *     Pago aprobado
 *          ↓
 *     Confirmado
 *
 *
 * TRANSFERENCIA
 *     Pedido realizado
 *          ↓
 *     En espera
 *
 *     Pago validado posteriormente
 *          ↓
 *     Confirmado
 */


/**
 * =========================================================
 * OBTENER ESTADO "CONFIRMADO"
 * =========================================================
 */

function aec_order_entry_confirmed_status()
{
    /**
     * Reutilizamos el helper que ya existe
     * en order-workflow.php.
     */
    if (
        function_exists(
            'aec_workflow_status_slug_by_label'
        )
    ) {

        $status =
            aec_workflow_status_slug_by_label(
                'Confirmado'
            );


        if ($status !== '') {

            return $status;
        }
    }


    return '';
}


/**
 * =========================================================
 * ¿SE PUEDE CONFIRMAR EL PEDIDO?
 * =========================================================
 */

function aec_order_entry_can_confirm(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return false;
    }


    /**
     * Nunca reactivamos pedidos que ya fueron:
     *
     * - cancelados
     * - fallidos
     * - reembolsados
     */
    if (
        $order->has_status(
            [
                'cancelled',
                'failed',
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
 * CAMBIAR A CONFIRMADO
 * =========================================================
 */

function aec_order_entry_set_confirmed(
    $order,
    $note = ''
) {
    if (
        !aec_order_entry_can_confirm(
            $order
        )
    ) {
        return false;
    }


    $confirmed =
        aec_order_entry_confirmed_status();


    if ($confirmed === '') {

        return false;
    }


    /**
     * Ya está confirmado.
     */
    if (
        $order->has_status(
            $confirmed
        )
    ) {

        return true;
    }


    /**
     * No debemos regresar pedidos que ya avanzaron
     * dentro de nuestro flujo.
     */
    if (
        function_exists(
            'aec_workflow_status_slug_by_label'
        )
    ) {

        $preparing =
            aec_workflow_status_slug_by_label(
                'En preparación'
            );


        $ready =
            aec_workflow_status_slug_by_label(
                'Listo para recoger'
            );


        $delivered =
            aec_workflow_status_slug_by_label(
                'Entregado'
            );


        $advanced_statuses =
            array_filter(
                [
                    $preparing,
                    $ready,
                    $delivered,
                ]
            );


        if (
            !empty($advanced_statuses) &&
            $order->has_status(
                $advanced_statuses
            )
        ) {

            return true;
        }
    }


    if ($note === '') {

        $note =
            'Pedido ingresado automáticamente al flujo operativo.';
    }


    $order->update_status(
        $confirmed,
        $note,
        true
    );


    return true;
}


/**
 * =========================================================
 * EFECTIVO
 * =========================================================
 *
 * WooCommerce normalmente envía COD a "processing".
 *
 * Nosotros indicamos desde el origen que debe pasar
 * directamente a "Confirmado".
 */

function aec_order_entry_cod_status(
    $status,
    $order
) {
    $confirmed =
        aec_order_entry_confirmed_status();


    if ($confirmed === '') {

        return $status;
    }


    if (
        $order instanceof WC_Order &&
        !aec_order_entry_can_confirm(
            $order
        )
    ) {

        return $status;
    }


    return $confirmed;
}


add_filter(
    'woocommerce_cod_process_payment_order_status',
    'aec_order_entry_cod_status',
    20,
    2
);


/**
 * =========================================================
 * PAGO COMPLETADO
 * =========================================================
 *
 * Cuando un gateway informa correctamente a WooCommerce
 * que el pago fue completado, utilizamos "Confirmado"
 * como estado operativo inicial.
 *
 * Esto aplica principalmente a pagos electrónicos.
 */

function aec_order_entry_payment_complete_status(
    $status,
    $order_id,
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {

        $order =
            wc_get_order(
                $order_id
            );
    }


    if (
        !aec_order_entry_can_confirm(
            $order
        )
    ) {

        return $status;
    }


    $confirmed =
        aec_order_entry_confirmed_status();


    if ($confirmed === '') {

        return $status;
    }


    return $confirmed;
}


add_filter(
    'woocommerce_payment_complete_order_status',
    'aec_order_entry_payment_complete_status',
    20,
    3
);


/**
 * =========================================================
 * RESPALDO PARA GATEWAYS QUE USAN "PROCESSING"
 * =========================================================
 *
 * Algunos gateways administran directamente el estado
 * "processing" después de aprobar el pago.
 *
 * En esos casos capturamos la transición y colocamos
 * el pedido en "Confirmado".
 */

function aec_order_entry_from_processing(
    $order_id,
    $order = false
) {
    if (
        !($order instanceof WC_Order)
    ) {

        $order =
            wc_get_order(
                $order_id
            );
    }


    if (
        !($order instanceof WC_Order)
    ) {
        return;
    }


    /**
     * Transferencia bancaria NO se confirma solamente
     * por existir el pedido.
     *
     * bacs = transferencia bancaria de WooCommerce.
     */
    if (
        $order->get_payment_method()
        === 'bacs'
    ) {

        return;
    }


    aec_order_entry_set_confirmed(
        $order,
        'Pago aceptado. Pedido ingresado automáticamente al flujo operativo.'
    );
}


add_action(
    'woocommerce_order_status_processing',
    'aec_order_entry_from_processing',
    20,
    2
);


/**
 * =========================================================
 * TRANSFERENCIA MARCADA COMO PAGADA
 * =========================================================
 *
 * Si posteriormente se valida una transferencia y
 * WooCommerce ejecuta payment_complete(), el filtro
 * woocommerce_payment_complete_order_status anterior
 * enviará el pedido a Confirmado.
 *
 * Por eso NO cambiamos automáticamente los pedidos BACS
 * al momento de crear la orden.
 */
