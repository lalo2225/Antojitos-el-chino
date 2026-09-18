<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * =========================================================
 * ANTOJITOS EL CHINO
 * CHECKOUT
 * =========================================================
 *
 * Checkout simplificado:
 *
 * - Correo electrónico
 * - Comer aquí / Recoger
 * - Hora si es Recoger
 * - Método de pago
 * - Nota opcional
 *
 * No solicitamos:
 *
 * - Nombre
 * - Apellido
 * - Empresa
 * - Dirección
 * - Ciudad
 * - Estado
 * - Código postal
 * - Teléfono
 *
 * El país se conserva internamente como México,
 * porque WooCommerce lo necesita para su Store API.
 */


/**
 * =========================================================
 * CHECKOUT SIMPLIFICADO
 * =========================================================
 */


/**
 * ---------------------------------------------------------
 * PAÍS PREDETERMINADO
 * ---------------------------------------------------------
 *
 * WooCommerce necesita conocer el país aunque no
 * mostremos la dirección al cliente.
 */

function aec_cliente_ubicacion_predeterminada($location)
{
    return 'MX';
}


add_filter(
    'woocommerce_customer_default_location',
    'aec_cliente_ubicacion_predeterminada',
    20
);


/**
 * ---------------------------------------------------------
 * OCULTAR CAMPOS DE DIRECCIÓN PARA MÉXICO
 * ---------------------------------------------------------
 */

function aec_simplificar_locale_mexico($locale)
{
    if (!isset($locale['MX'])) {
        return $locale;
    }


    $campos_ocultos = [
        'first_name',
        'last_name',
        'company',
        'address_1',
        'address_2',
        'city',
        'state',
        'postcode',
        'phone',
    ];


    foreach ($campos_ocultos as $campo) {

        if (!isset($locale['MX'][$campo])) {
            $locale['MX'][$campo] = [];
        }


        $locale['MX'][$campo]['required'] =
            false;

        $locale['MX'][$campo]['hidden'] =
            true;
    }


    return $locale;
}


add_filter(
    'woocommerce_get_country_locale',
    'aec_simplificar_locale_mexico',
    100
);


/**
 * ---------------------------------------------------------
 * OCULTAR CAMPOS TAMBIÉN ANTES DE QUE EL CHECKOUT
 * TENGA PAÍS RESUELTO
 * ---------------------------------------------------------
 */

function aec_simplificar_locale_default($fields)
{
    $campos_ocultos = [
        'first_name',
        'last_name',
        'company',
        'address_1',
        'address_2',
        'city',
        'state',
        'postcode',
        'phone',
    ];


    foreach ($campos_ocultos as $campo) {

        if (!isset($fields[$campo])) {
            $fields[$campo] = [];
        }


        $fields[$campo]['required'] =
            false;

        $fields[$campo]['hidden'] =
            true;
    }


    return $fields;
}


add_filter(
    'woocommerce_get_country_locale_default',
    'aec_simplificar_locale_default',
    100
);


/**
 * ---------------------------------------------------------
 * ASEGURAR QUE TELÉFONO, EMPRESA Y DIRECCIÓN 2
 * PERMANEZCAN OCULTOS
 * ---------------------------------------------------------
 */

function aec_checkout_campo_oculto($value)
{
    return 'hidden';
}


add_filter(
    'option_woocommerce_checkout_phone_field',
    'aec_checkout_campo_oculto'
);

add_filter(
    'default_option_woocommerce_checkout_phone_field',
    'aec_checkout_campo_oculto'
);


add_filter(
    'option_woocommerce_checkout_company_field',
    'aec_checkout_campo_oculto'
);

add_filter(
    'default_option_woocommerce_checkout_company_field',
    'aec_checkout_campo_oculto'
);


add_filter(
    'option_woocommerce_checkout_address_2_field',
    'aec_checkout_campo_oculto'
);

add_filter(
    'default_option_woocommerce_checkout_address_2_field',
    'aec_checkout_campo_oculto'
);


/**
 * =========================================================
 * CSS DEL CHECKOUT
 * =========================================================
 */

function aec_cargar_css_checkout()
{
    if (
        !function_exists('is_checkout') ||
        !is_checkout()
    ) {
        return;
    }


    if (
        function_exists(
            'is_order_received_page'
        ) &&
        is_order_received_page()
    ) {
        return;
    }


    wp_enqueue_style(
        'aec-checkout',
        AEC_PLUGIN_URL .
        'assets/css/checkout.css',
        [],
        '1.0.0'
    );
}


add_action(
    'wp_enqueue_scripts',
    'aec_cargar_css_checkout',
    30
);


/**
 * =========================================================
 * CONDICIÓN PARA CAMPOS ADICIONALES
 * =========================================================
 */

function aec_checkout_condicion_campo(
    $field_id,
    $valor
) {
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

                            $field_id => [

                                'const' =>
                                    $valor,
                            ],
                        ],

                        'required' => [
                            $field_id,
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
 * CAMPOS PERSONALIZADOS
 * =========================================================
 */

function aec_registrar_campos_checkout()
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
     * TIPO DE PEDIDO
     * -----------------------------------------------------
     */

    woocommerce_register_additional_checkout_field(
        [

            'id' =>
                'antojitos-el-chino/tipo-pedido',

            'label' =>
                '¿Cómo quieres tu pedido?',

            'location' =>
                'order',

            'type' =>
                'select',

            'required' =>
                true,

            'placeholder' =>
                'Selecciona una modalidad',

            'options' => [

                [

                    'value' =>
                        'comer_aqui',

                    'label' =>
                        'Comer aquí',
                ],

                [

                    'value' =>
                        'recoger',

                    'label' =>
                        'Recoger',
                ],
            ],
        ]
    );


    /**
     * tipo-pedido = recoger
     */

    $condicion_recoger =
        aec_checkout_condicion_campo(
            'antojitos-el-chino/tipo-pedido',
            'recoger'
        );


    /**
     * -----------------------------------------------------
     * HORA DE RECOLECCIÓN
     * -----------------------------------------------------
     */

    woocommerce_register_additional_checkout_field(
        [

            'id' =>
                'antojitos-el-chino/hora-recoleccion',

            'label' =>
                'Hora de recolección (HH:MM)',

            'optionalLabel' =>
                'Hora de recolección (HH:MM)',

            'location' =>
                'order',

            'type' =>
                'text',

            'required' =>
                $condicion_recoger,

            'hidden' => [

                'not' =>
                    $condicion_recoger,
            ],

            'attributes' => [

                'pattern' =>
                    '([01][0-9]|2[0-3]):[0-5][0-9]',

                'maxLength' =>
                    5,

                'autocomplete' =>
                    'off',

                'title' =>
                    'Ingresa la hora en formato HH:MM. Ejemplo: 14:30.',
            ],

            'sanitize_callback' =>
                function ($value) {

                    return trim(
                        sanitize_text_field(
                            $value
                        )
                    );
                },
        ]
    );
}


add_action(
    'woocommerce_init',
    'aec_registrar_campos_checkout'
);


/**
 * =========================================================
 * VALIDAR CAMPOS
 * =========================================================
 */

function aec_validar_campos_checkout(
    WP_Error $errors,
    $fields,
    $group
) {
    if (
        $group !== 'other'
    ) {
        return;
    }


    $tipo_id =
        'antojitos-el-chino/tipo-pedido';


    $hora_id =
        'antojitos-el-chino/hora-recoleccion';


    $tipo =
        isset(
            $fields[$tipo_id]
        )
            ? sanitize_key(
                $fields[$tipo_id]
            )
            : '';


    /**
     * Tipo válido.
     */

    if (
        $tipo !== '' &&
        !in_array(
            $tipo,
            [
                'comer_aqui',
                'recoger',
            ],
            true
        )
    ) {

        $errors->add(
            'aec_tipo_pedido_invalido',
            'Selecciona una modalidad de pedido válida.'
        );

        return;
    }


    /**
     * Comer aquí no necesita hora.
     */

    if (
        $tipo === 'comer_aqui'
    ) {
        return;
    }


    if (
        $tipo !== 'recoger'
    ) {
        return;
    }


    $hora =
        isset(
            $fields[$hora_id]
        )
            ? trim(
                sanitize_text_field(
                    $fields[$hora_id]
                )
            )
            : '';


    if (
        $hora === ''
    ) {
        return;
    }


    if (
        !preg_match(
            '/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/',
            $hora
        )
    ) {

        $errors->add(
            'aec_hora_recoleccion_invalida',
            'Ingresa la hora de recolección en formato HH:MM. Ejemplo: 14:30.'
        );
    }
}


add_action(
    'woocommerce_blocks_validate_location_other_fields',
    'aec_validar_campos_checkout',
    10,
    3
);


/**
 * =========================================================
 * LEER TIPO DE PEDIDO DESDE STORE API
 * =========================================================
 */

function aec_tipo_pedido_desde_request(
    $request
) {
    if (
        !($request instanceof WP_REST_Request)
    ) {
        return '';
    }


    $campos =
        $request->get_param(
            'additional_fields'
        );


    if (
        !is_array($campos)
    ) {
        return '';
    }


    $field_id =
        'antojitos-el-chino/tipo-pedido';


    if (
        !isset(
            $campos[$field_id]
        )
    ) {
        return '';
    }


    $tipo =
        sanitize_key(
            $campos[$field_id]
        );


    if (
        !in_array(
            $tipo,
            [
                'comer_aqui',
                'recoger',
            ],
            true
        )
    ) {
        return '';
    }


    return $tipo;
}


/**
 * =========================================================
 * GUARDAR MODALIDAD EN SESIÓN
 * =========================================================
 */

function aec_guardar_tipo_pedido_sesion(
    $request
) {
    if (
        !WC()->session
    ) {
        return;
    }


    $tipo =
        aec_tipo_pedido_desde_request(
            $request
        );


    if (
        $tipo === ''
    ) {
        return;
    }


    WC()->session->set(
        'aec_tipo_pedido',
        $tipo
    );
}


add_action(
    'woocommerce_store_api_checkout_update_draft',
    'aec_guardar_tipo_pedido_sesion',
    10,
    1
);


/**
 * =========================================================
 * COMPATIBILIDAD CON ORDEN / DRAFT
 * =========================================================
 */

function aec_guardar_tipo_pedido_sesion_orden(
    $order,
    $request
) {
    aec_guardar_tipo_pedido_sesion(
        $request
    );
}


add_action(
    'woocommerce_store_api_checkout_update_order_from_request',
    'aec_guardar_tipo_pedido_sesion_orden',
    10,
    2
);


/**
 * =========================================================
 * MODALIDAD ACTUAL
 * =========================================================
 */

function aec_tipo_pedido_actual()
{
    if (
        !WC()->session
    ) {
        return '';
    }


    $tipo =
        sanitize_key(
            (string)
            WC()->session->get(
                'aec_tipo_pedido',
                ''
            )
        );


    if (
        !in_array(
            $tipo,
            [
                'comer_aqui',
                'recoger',
            ],
            true
        )
    ) {
        return '';
    }


    return $tipo;
}


/**
 * =========================================================
 * MÉTODOS DE PAGO
 * =========================================================
 *
 * bacs = Transferencia
 * cod  = Efectivo
 *
 *
 * COMER AQUÍ:
 *
 * - Efectivo
 * - Transferencia
 *
 *
 * RECOGER:
 *
 * - Transferencia
 */

function aec_filtrar_metodos_pago(
    $gateways
) {
    if (
        !is_array($gateways)
    ) {
        return $gateways;
    }


    if (
        is_admin() &&
        !wp_doing_ajax() &&
        !defined('REST_REQUEST')
    ) {
        return $gateways;
    }


    $tipo =
        aec_tipo_pedido_actual();


    /**
     * Efectivo solamente para Comer aquí.
     */

    if (
        $tipo !== 'comer_aqui'
    ) {

        if (
            isset(
                $gateways['cod']
            )
        ) {

            unset(
                $gateways['cod']
            );
        }
    }


    return $gateways;
}


add_filter(
    'woocommerce_available_payment_gateways',
    'aec_filtrar_metodos_pago',
    100
);


/**
 * =========================================================
 * NOMBRE DEL MÉTODO COD
 * =========================================================
 */

function aec_nombre_metodo_pago(
    $title,
    $gateway_id
) {
    if (
        $gateway_id === 'cod'
    ) {
        return 'Efectivo';
    }


    return $title;
}


add_filter(
    'woocommerce_gateway_title',
    'aec_nombre_metodo_pago',
    10,
    2
);


/**
 * =========================================================
 * DESCRIPCIÓN EFECTIVO
 * =========================================================
 */

function aec_descripcion_metodo_pago(
    $description,
    $gateway_id
) {
    if (
        $gateway_id === 'cod'
    ) {

        return
            'Paga en efectivo al momento de recibir tu pedido para comer aquí.';
    }


    return $description;
}


add_filter(
    'woocommerce_gateway_description',
    'aec_descripcion_metodo_pago',
    10,
    2
);


/**
 * =========================================================
 * LEER MODALIDAD DESDE LA ORDEN
 * =========================================================
 */

function aec_tipo_pedido_desde_orden(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return '';
    }


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


            $tipo =
                $checkout_fields
                    ->get_field_from_object(
                        'antojitos-el-chino/tipo-pedido',
                        $order,
                        'other'
                    );


            if (
                is_string($tipo)
            ) {

                $tipo =
                    sanitize_key(
                        $tipo
                    );


                if (
                    in_array(
                        $tipo,
                        [
                            'comer_aqui',
                            'recoger',
                        ],
                        true
                    )
                ) {

                    return $tipo;
                }
            }
        }

    } catch (Throwable $e) {

        /**
         * Fallback posterior.
         */
    }


    $tipo =
        sanitize_key(
            (string)
            $order->get_meta(
                '_wc_other/antojitos-el-chino/tipo-pedido',
                true
            )
        );


    return $tipo;
}


/**
 * =========================================================
 * SEGURIDAD:
 * RECOGER + EFECTIVO = NO PERMITIDO
 * =========================================================
 */

function aec_validar_pago_antes_procesar(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return;
    }


    $tipo =
        aec_tipo_pedido_desde_orden(
            $order
        );


    $metodo =
        $order->get_payment_method();


    if (
        $tipo === 'recoger' &&
        $metodo === 'cod'
    ) {

        throw new
        \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
            'aec_efectivo_no_permitido',
            'El pago en efectivo solo está disponible para pedidos de Comer aquí.',
            400
        );
    }
}


add_action(
    'woocommerce_store_api_checkout_order_processed',
    'aec_validar_pago_antes_procesar',
    20,
    1
);


/**
 * =========================================================
 * LIMPIAR SESIÓN
 * =========================================================
 */

function aec_limpiar_tipo_pedido_sesion()
{
    if (
        WC()->session
    ) {

        WC()->session->set(
            'aec_tipo_pedido',
            null
        );
    }
}


add_action(
    'woocommerce_cart_emptied',
    'aec_limpiar_tipo_pedido_sesion'
);
/**
 * ==========================================================
 * BOTON "VOLVER AL MENU" EN CHECKOUT
 * ==========================================================
 *
 * Carga únicamente la interfaz que permite regresar al menú
 * antes de seleccionar/finalizar el pago.
 *
 * No modifica:
 *
 * - carrito
 * - métodos de pago
 * - Mercado Pago
 * - transferencias
 * - creación del pedido
 * - Store API
 */
function aec_checkout_more_menu_assets()
{
    if (
        !function_exists('is_checkout') ||
        !is_checkout()
    ) {
        return;
    }


    /**
     * No cargar después de completar un pedido.
     */
    if (
        function_exists('is_order_received_page') &&
        is_order_received_page()
    ) {
        return;
    }


    $relative_path =
        'assets/js/checkout-more-menu.js';


    $file_path =
        dirname(__DIR__)
        . '/'
        . $relative_path;


    $plugin_file =
        dirname(__DIR__)
        . '/antojitos.php';


    wp_enqueue_script(
        'aec-checkout-more-menu',
        plugins_url(
            $relative_path,
            $plugin_file
        ),
        [],
        file_exists($file_path)
            ? filemtime($file_path)
            : null,
        true
    );
}


add_action(
    'wp_enqueue_scripts',
    'aec_checkout_more_menu_assets',
    35
);
