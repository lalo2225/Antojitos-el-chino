<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * =========================================================
 * ANTOJITOS EL CHINO
 * IDENTIFICACIÓN DEL PEDIDO
 * =========================================================
 *
 * COMER AQUÍ:
 *
 * - Nombre obligatorio.
 * - Sin teléfono obligatorio.
 *
 *
 * RECOGER:
 *
 * - Nombre obligatorio.
 * - Teléfono obligatorio.
 *
 *
 * No se crea ningún campo adicional de correo.
 */


/**
 * =========================================================
 * CONSTANTES
 * =========================================================
 */

define(
    'AEC_CUSTOMER_NAME_FIELD',
    'antojitos-el-chino/nombre-pedido'
);


define(
    'AEC_CUSTOMER_PHONE_FIELD',
    'antojitos-el-chino/telefono-pedido'
);


define(
    'AEC_ORDER_TYPE_FIELD',
    'antojitos-el-chino/tipo-pedido'
);


/**
 * =========================================================
 * CONDICIÓN: MODALIDAD = RECOGER
 * =========================================================
 *
 * WooCommerce Checkout Blocks expone los campos adicionales
 * dentro de:
 *
 * checkout.additional_fields
 *
 * Esta condición será reutilizada para decidir cuándo
 * mostrar y exigir el teléfono.
 */

function aec_customer_recoger_schema()
{
    return [

        'type' =>
            'object',

        'properties' => [

            'checkout' => [

                'type' =>
                    'object',

                'properties' => [

                    'additional_fields' => [

                        'type' =>
                            'object',

                        'properties' => [

                            AEC_ORDER_TYPE_FIELD => [

                                'const' =>
                                    'recoger',
                            ],
                        ],

                        'required' => [

                            AEC_ORDER_TYPE_FIELD,
                        ],
                    ],
                ],

                'required' => [

                    'additional_fields',
                ],
            ],
        ],

        'required' => [

            'checkout',
        ],
    ];
}


/**
 * =========================================================
 * CONDICIÓN: NO ES RECOGER
 * =========================================================
 *
 * Cuando todavía no se ha elegido modalidad o cuando
 * seleccionan "Comer aquí", ocultamos el teléfono.
 */

function aec_customer_no_recoger_schema()
{
    return [

        'type' =>
            'object',

        'not' =>
            aec_customer_recoger_schema(),
    ];
}


/**
 * =========================================================
 * REGISTRAR CAMPOS
 * =========================================================
 */

function aec_customer_register_checkout_fields()
{
    if (
        !function_exists(
            'woocommerce_register_additional_checkout_field'
        )
    ) {
        return;
    }


    /**
     * -----------------------------------------------------
     * NOMBRE DEL PEDIDO
     * -----------------------------------------------------
     */

    woocommerce_register_additional_checkout_field(
        [

            'id' =>
                AEC_CUSTOMER_NAME_FIELD,

            'label' =>
                '¿A nombre de quién ponemos tu pedido?',

            'location' =>
                'order',

            'type' =>
                'text',

            'required' =>
                true,

            'attributes' => [

                'autocomplete' =>
                    'name',

                'autocapitalize' =>
                    'words',

                'maxLength' =>
                    60,

                'title' =>
                    'Escribe el nombre con el que identificaremos tu pedido.',
            ],


            /**
             * Limpiar el valor antes de guardarlo.
             */

            'sanitize_callback' =>
                function ($value) {

                    return sanitize_text_field(
                        trim(
                            (string) $value
                        )
                    );
                },


            /**
             * Validación básica.
             */

            'validate_callback' =>
                function ($value) {

                    $value =
                        trim(
                            (string) $value
                        );


                    if ($value === '') {
                        return;
                    }


                    $length =
                        function_exists(
                            'mb_strlen'
                        )
                            ? mb_strlen($value)
                            : strlen($value);


                    if ($length < 2) {

                        return new WP_Error(
                            'aec_customer_name_too_short',
                            'Escribe un nombre válido para identificar tu pedido.'
                        );
                    }


                    if ($length > 60) {

                        return new WP_Error(
                            'aec_customer_name_too_long',
                            'El nombre del pedido es demasiado largo.'
                        );
                    }
                },
        ]
    );


    /**
     * -----------------------------------------------------
     * TELÉFONO
     * -----------------------------------------------------
     *
     * Se muestra y se vuelve obligatorio únicamente
     * cuando modalidad = Recoger.
     */

    woocommerce_register_additional_checkout_field(
        [

            'id' =>
                AEC_CUSTOMER_PHONE_FIELD,

            'label' =>
                'Teléfono de contacto',

            'optionalLabel' =>
                'Teléfono de contacto',

            'location' =>
                'order',

            'type' =>
                'text',


            /**
             * Obligatorio solamente para Recoger.
             */

            'required' =>
                aec_customer_recoger_schema(),


            /**
             * Oculto cuando NO sea Recoger.
             */

            'hidden' =>
                aec_customer_no_recoger_schema(),


            'attributes' => [

                'autocomplete' =>
                    'tel',

                'maxLength' =>
                    20,

                'title' =>
                    'Escribe un número telefónico de 10 dígitos.',
            ],


            'sanitize_callback' =>
                function ($value) {

                    return sanitize_text_field(
                        trim(
                            (string) $value
                        )
                    );
                },


            /**
             * Permitimos escribir:
             *
             * 5512345678
             * 55 1234 5678
             * 55-1234-5678
             *
             * pero deben existir exactamente 10 dígitos.
             */

            'validate_callback' =>
                function ($value) {

                    $value =
                        trim(
                            (string) $value
                        );


                    if ($value === '') {

                        /**
                         * WooCommerce se encargará del
                         * "required" cuando sea Recoger.
                         */

                        return;
                    }


                    $digits =
                        preg_replace(
                            '/\D+/',
                            '',
                            $value
                        );


                    if (
                        strlen($digits)
                        !== 10
                    ) {

                        return new WP_Error(
                            'aec_customer_invalid_phone',
                            'Escribe un teléfono válido de 10 dígitos.'
                        );
                    }
                },
        ]
    );
}


add_action(
    'woocommerce_init',
    'aec_customer_register_checkout_fields',
    30
);


/**
 * =========================================================
 * LEER CAMPO ADICIONAL DEL PEDIDO
 * =========================================================
 *
 * Usaremos esta función posteriormente en:
 *
 * - Pantalla de pedidos
 * - Administración
 * - Transferencias
 * - Dashboard
 */

function aec_customer_get_order_field(
    $order,
    $field_id
) {
    if (
        is_numeric($order)
    ) {

        $order =
            wc_get_order(
                absint($order)
            );
    }


    if (
        !($order instanceof WC_Order)
    ) {
        return '';
    }


    /**
     * Método oficial de Checkout Fields.
     */

    try {

        if (
            class_exists(
                '\Automattic\WooCommerce\Blocks\Package'
            )
            &&
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


            if (
                method_exists(
                    $checkout_fields,
                    'get_field_from_object'
                )
            ) {

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
        }

    } catch (Throwable $e) {

        /**
         * Si WooCommerce cambia internamente,
         * continuar con fallback.
         */
    }


    /**
     * Fallback compatible con la estructura actual.
     */

    $value =
        $order->get_meta(
            '_wc_other/'
            .
            $field_id,
            true
        );


    return
        is_scalar($value)
            ? sanitize_text_field(
                (string) $value
            )
            : '';
}


/**
 * =========================================================
 * OBTENER NOMBRE
 * =========================================================
 */

function aec_customer_order_name(
    $order
) {
    $name =
        aec_customer_get_order_field(
            $order,
            AEC_CUSTOMER_NAME_FIELD
        );


    /**
     * Compatibilidad futura / pedidos antiguos.
     */

    if (
        $name === ''
        &&
        $order instanceof WC_Order
    ) {

        $name =
            $order->get_billing_first_name();
    }


    return sanitize_text_field(
        $name
    );
}


/**
 * =========================================================
 * OBTENER TELÉFONO
 * =========================================================
 */

function aec_customer_order_phone(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {

        if (
            is_numeric($order)
        ) {

            $order =
                wc_get_order(
                    absint($order)
                );
        }
    }


    if (
        !($order instanceof WC_Order)
    ) {
        return '';
    }


    /**
     * El teléfono solo tiene sentido operativo
     * para pedidos Recoger.
     */

    $type = '';


    if (
        function_exists(
            'aec_admin_order_tipo_codigo'
        )
    ) {

        $type =
            aec_admin_order_tipo_codigo(
                $order
            );

    } else {

        $type =
            aec_customer_get_order_field(
                $order,
                AEC_ORDER_TYPE_FIELD
            );
    }


    if (
        $type !== 'recoger'
    ) {
        return '';
    }


    $phone =
        aec_customer_get_order_field(
            $order,
            AEC_CUSTOMER_PHONE_FIELD
        );


    if ($phone === '') {

        $phone =
            $order->get_billing_phone();
    }


    return sanitize_text_field(
        $phone
    );
}


/**
 * =========================================================
 * SINCRONIZAR CON DATOS BASE DE WOOCOMMERCE
 * =========================================================
 *
 * Esto NO agrega campos de facturación al checkout.
 *
 * Solo copia nombre y teléfono al pedido internamente para
 * mejorar compatibilidad con:
 *
 * - administración WooCommerce
 * - pasarelas de pago
 * - futuros reportes
 */

function aec_customer_sync_order_data(
    $order,
    $request
) {
    if (
        !($order instanceof WC_Order)
        ||
        !($request instanceof WP_REST_Request)
    ) {
        return;
    }


    $fields =
        $request->get_param(
            'additional_fields'
        );


    if (
        !is_array($fields)
    ) {
        return;
    }


    /**
     * Nombre.
     */

    if (
        isset(
            $fields[
                AEC_CUSTOMER_NAME_FIELD
            ]
        )
    ) {

        $name =
            sanitize_text_field(
                trim(
                    (string)
                    $fields[
                        AEC_CUSTOMER_NAME_FIELD
                    ]
                )
            );


        if ($name !== '') {

            $order->set_billing_first_name(
                $name
            );
        }
    }


    /**
     * Modalidad.
     */

    $type =
        isset(
            $fields[
                AEC_ORDER_TYPE_FIELD
            ]
        )
            ? sanitize_key(
                $fields[
                    AEC_ORDER_TYPE_FIELD
                ]
            )
            : '';


    /**
     * Teléfono únicamente para Recoger.
     */

    if (
        $type === 'recoger'
        &&
        isset(
            $fields[
                AEC_CUSTOMER_PHONE_FIELD
            ]
        )
    ) {

        $phone =
            sanitize_text_field(
                trim(
                    (string)
                    $fields[
                        AEC_CUSTOMER_PHONE_FIELD
                    ]
                )
            );


        $order->set_billing_phone(
            $phone
        );

    } elseif (
        $type === 'comer_aqui'
    ) {

        $order->set_billing_phone(
            ''
        );
    }
}


add_action(
    'woocommerce_store_api_checkout_update_order_from_request',
    'aec_customer_sync_order_data',
    40,
    2
);

