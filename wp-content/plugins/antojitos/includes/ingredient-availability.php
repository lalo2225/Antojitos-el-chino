<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * =========================================================
 * ANTOJITOS EL CHINO
 * DISPONIBILIDAD EN CONFIGURADOR
 * =========================================================
 */


/**
 * =========================================================
 * CONVERTIR OPCIONES A MAPA DE DISPONIBILIDAD
 * =========================================================
 */

function aec_ingredient_availability_options(
    $opciones
) {
    $resultado = [];


    foreach (
        $opciones as $valor => $nombre
    ) {

        $clave =
            aec_disponibilidad_clave_canonica(
                $valor
            );


        $resultado[$valor] = [

            'key' =>
                $clave,

            'label' =>
                $nombre,

            'available' =>
                aec_disponibilidad_disponible(
                    $clave
                ),
        ];
    }


    return $resultado;
}


/**
 * =========================================================
 * MAPA DEL PRODUCTO ACTUAL
 * =========================================================
 */

function aec_ingredient_availability_product_map(
    $producto
) {
    if (
        !$producto ||
        !function_exists(
            'aec_configuracion_producto'
        )
    ) {
        return [];
    }


    $config =
        aec_configuracion_producto(
            $producto
        );


    if (!$config) {
        return [];
    }


    $campos = [];


    /**
     * =====================================================
     * COMIDA
     * =====================================================
     */

    if (
        $config['tipo']
        === 'comida'
    ) {

        $campos[] = [

            'name' =>
                'aec_opcion',

            'type' =>
                'select',

            'label' =>
                $config['label'],

            'required' =>
                true,

            'options' =>
                aec_ingredient_availability_options(
                    $config['opciones']
                ),
        ];


        if (
            !empty(
                $config['base']
            )
        ) {

            $campos[] = [

                'name' =>
                    'aec_base',

                'type' =>
                    'radio',

                'label' =>
                    'Elige la base',

                'required' =>
                    true,

                'options' =>
                    aec_ingredient_availability_options(
                        $config['base']
                    ),
            ];
        }


        if (
            !empty(
                $config['preparacion']
            )
        ) {

            $campos[] = [

                'name' =>
                    'aec_preparacion[]',

                'type' =>
                    'checkbox',

                'label' =>
                    'Preparación',

                'required' =>
                    false,

                'options' =>
                    aec_ingredient_availability_options(
                        $config['preparacion']
                    ),
            ];
        }


        if (
            !empty(
                $config['acompanamiento']
            )
        ) {

            $campos[] = [

                'name' =>
                    'aec_acompanamiento',

                'type' =>
                    'radio',

                'label' =>
                    'Elige acompañamiento',

                'required' =>
                    true,

                'options' =>
                    aec_ingredient_availability_options(
                        $config[
                            'acompanamiento'
                        ]
                    ),
            ];
        }


        if (
            !empty(
                $config['queso']
            )
        ) {

            $campos[] = [

                'name' =>
                    'aec_queso',

                'type' =>
                    'checkbox',

                'label' =>
                    'Queso',

                'required' =>
                    false,

                'options' => [

                    '1' => [

                        'key' =>
                            'queso',

                        'label' =>
                            'Queso',

                        'available' =>
                            aec_disponibilidad_disponible(
                                'queso'
                            ),
                    ],
                ],
            ];
        }
    }


    /**
     * =====================================================
     * AGUA
     * =====================================================
     */

    if (
        $config['tipo']
        === 'agua'
    ) {

        $campos[] = [

            'name' =>
                'aec_sabor',

            'type' =>
                'select',

            'label' =>
                'Elige el sabor',

            'required' =>
                true,

            'options' =>
                aec_ingredient_availability_options(
                    $config['sabores']
                ),
        ];
    }


    /**
     * =====================================================
     * REFRESCO
     * =====================================================
     */

    if (
        $config['tipo']
        === 'refresco'
    ) {

        $campos[] = [

            'name' =>
                'aec_refresco',

            'type' =>
                'select',

            'label' =>
                'Elige tu bebida',

            'required' =>
                true,

            'options' =>
                aec_ingredient_availability_options(
                    $config['refrescos']
                ),
        ];
    }


    return [

        'fields' =>
            $campos,
    ];
}


/**
 * =========================================================
 * ASSETS EN PÁGINA DEL PRODUCTO
 * =========================================================
 */

function aec_ingredient_availability_assets()
{
    if (
        !function_exists('is_product') ||
        !is_product()
    ) {
        return;
    }


    $product_id =
        get_queried_object_id();


    $producto =
        wc_get_product(
            $product_id
        );


    if (!$producto) {
        return;
    }


    $mapa =
        aec_ingredient_availability_product_map(
            $producto
        );


    if (
        empty(
            $mapa['fields']
        )
    ) {
        return;
    }


    wp_enqueue_style(
        'aec-ingredient-availability',
        AEC_PLUGIN_URL
        .
        'assets/css/ingredient-availability.css',
        [],
        '1.0.0'
    );


    wp_enqueue_script(
        'aec-ingredient-availability',
        AEC_PLUGIN_URL
        .
        'assets/js/ingredient-availability.js',
        [],
        '1.0.0',
        true
    );


    wp_localize_script(
        'aec-ingredient-availability',
        'AECIngredientAvailability',
        $mapa
    );
}


add_action(
    'wp_enqueue_scripts',
    'aec_ingredient_availability_assets',
    45
);


/**
 * =========================================================
 * VALIDAR UNA OPCIÓN
 * =========================================================
 */

function aec_ingredient_availability_validate_option(
    $valor,
    $opciones
) {
    if (
        !$valor ||
        !isset(
            $opciones[$valor]
        )
    ) {
        return true;
    }


    $clave =
        aec_disponibilidad_clave_canonica(
            $valor
        );


    if (
        aec_disponibilidad_disponible(
            $clave
        )
    ) {

        return true;
    }


    wc_add_notice(
        sprintf(
            'La opción "%s" está agotada. Selecciona otra opción.',
            $opciones[$valor]
        ),
        'error'
    );


    return false;
}


/**
 * =========================================================
 * VALIDACIÓN SERVIDOR
 * =========================================================
 */

function aec_ingredient_availability_validate_cart(
    $valido,
    $product_id,
    $quantity,
    $variation_id = 0,
    $variations = [],
    $cart_item_data = []
) {
    if (!$valido) {
        return false;
    }


    $producto =
        wc_get_product(
            $product_id
        );


    $config =
        aec_configuracion_producto(
            $producto
        );


    if (!$config) {
        return $valido;
    }


    /**
     * =====================================================
     * COMIDA
     * =====================================================
     */

    if (
        $config['tipo']
        === 'comida'
    ) {

        $opcion =
            aec_post_key(
                'aec_opcion'
            );


        if (
            !aec_ingredient_availability_validate_option(
                $opcion,
                $config['opciones']
            )
        ) {

            return false;
        }


        /**
         * BASE
         */

        if (
            !empty(
                $config['base']
            )
        ) {

            $base =
                aec_post_key(
                    'aec_base'
                );


            if (
                !aec_ingredient_availability_validate_option(
                    $base,
                    $config['base']
                )
            ) {

                return false;
            }
        }


        /**
         * ACOMPAÑAMIENTO
         */

        if (
            !empty(
                $config['acompanamiento']
            )
        ) {

            $acompanamiento =
                aec_post_key(
                    'aec_acompanamiento'
                );


            if (
                !aec_ingredient_availability_validate_option(
                    $acompanamiento,
                    $config['acompanamiento']
                )
            ) {

                return false;
            }
        }


        /**
         * PREPARACIÓN
         */

        if (
            !empty(
                $config['preparacion']
            ) &&
            isset(
                $_POST['aec_preparacion']
            ) &&
            is_array(
                $_POST['aec_preparacion']
            )
        ) {

            $seleccionados =
                array_map(
                    'sanitize_key',
                    wp_unslash(
                        $_POST[
                            'aec_preparacion'
                        ]
                    )
                );


            foreach (
                $seleccionados as
                $seleccionado
            ) {

                if (
                    !isset(
                        $config[
                            'preparacion'
                        ][$seleccionado]
                    )
                ) {

                    continue;
                }


                if (
                    !aec_ingredient_availability_validate_option(
                        $seleccionado,
                        $config['preparacion']
                    )
                ) {

                    return false;
                }
            }
        }


        /**
         * QUESO
         */

        if (
            !empty(
                $config['queso']
            ) &&
            !empty(
                $_POST['aec_queso']
            ) &&
            !aec_disponibilidad_disponible(
                'queso'
            )
        ) {

            wc_add_notice(
                'El queso está agotado. Continúa sin queso.',
                'error'
            );


            return false;
        }
    }


    /**
     * =====================================================
     * AGUA
     * =====================================================
     */

    if (
        $config['tipo']
        === 'agua'
    ) {

        $sabor =
            aec_post_key(
                'aec_sabor'
            );


        if (
            !aec_ingredient_availability_validate_option(
                $sabor,
                $config['sabores']
            )
        ) {

            return false;
        }
    }


    /**
     * =====================================================
     * REFRESCO
     * =====================================================
     */

    if (
        $config['tipo']
        === 'refresco'
    ) {

        $refresco =
            aec_post_key(
                'aec_refresco'
            );


        if (
            !aec_ingredient_availability_validate_option(
                $refresco,
                $config['refrescos']
            )
        ) {

            return false;
        }
    }


    return $valido;
}


add_filter(
    'woocommerce_add_to_cart_validation',
    'aec_ingredient_availability_validate_cart',
    20,
    6
);


/**
 * =========================================================
 * GUARDAR SELECCIONES PARA REVALIDAR EL CARRITO
 * =========================================================
 */

function aec_ingredient_availability_cart_data(
    $cart_item_data,
    $product_id,
    $variation_id,
    $quantity
) {
    $producto =
        wc_get_product(
            $product_id
        );


    $config =
        aec_configuracion_producto(
            $producto
        );


    if (!$config) {
        return $cart_item_data;
    }


    $selecciones = [];


    /**
     * Helper interno.
     */

    $agregar =
        function (
            $valor,
            $opciones
        ) use (
            &$selecciones
        ) {

            if (
                !$valor ||
                !isset(
                    $opciones[$valor]
                )
            ) {
                return;
            }


            $selecciones[] = [

                'key' =>
                    aec_disponibilidad_clave_canonica(
                        $valor
                    ),

                'label' =>
                    $opciones[$valor],
            ];
        };


    if (
        $config['tipo']
        === 'comida'
    ) {

        $agregar(
            aec_post_key(
                'aec_opcion'
            ),
            $config['opciones']
        );


        if (
            !empty(
                $config['base']
            )
        ) {

            $agregar(
                aec_post_key(
                    'aec_base'
                ),
                $config['base']
            );
        }


        if (
            !empty(
                $config['acompanamiento']
            )
        ) {

            $agregar(
                aec_post_key(
                    'aec_acompanamiento'
                ),
                $config['acompanamiento']
            );
        }


        if (
            !empty(
                $config['preparacion']
            )
        ) {

            $seleccionados =
                [];


            if (
                isset(
                    $_POST['aec_preparacion']
                ) &&
                is_array(
                    $_POST['aec_preparacion']
                )
            ) {

                $seleccionados =
                    array_map(
                        'sanitize_key',
                        wp_unslash(
                            $_POST[
                                'aec_preparacion'
                            ]
                        )
                    );
            }


            foreach (
                $seleccionados as
                $seleccionado
            ) {

                $agregar(
                    $seleccionado,
                    $config['preparacion']
                );
            }


            /**
             * Registrar ingredientes agotados
             * que fueron omitidos automáticamente.
             */

            $no_disponibles = [];


            foreach (
                $config['preparacion']
                as $clave => $nombre
            ) {

                if (
                    !aec_disponibilidad_disponible(
                        $clave
                    )
                ) {

                    $no_disponibles[] =
                        $nombre;
                }
            }


            if (
                !empty(
                    $no_disponibles
                ) &&
                !empty(
                    $cart_item_data[
                        'aec_config'
                    ]
                )
            ) {

                /**
                 * Evitar que aparezca como si el
                 * cliente lo hubiera desmarcado.
                 */

                if (
                    !empty(
                        $cart_item_data[
                            'aec_config'
                        ]['Sin']
                    )
                ) {

                    $sin =
                        array_map(
                            'trim',
                            explode(
                                ',',
                                $cart_item_data[
                                    'aec_config'
                                ]['Sin']
                            )
                        );


                    $sin =
                        array_values(
                            array_diff(
                                $sin,
                                $no_disponibles
                            )
                        );


                    if (
                        empty($sin)
                    ) {

                        unset(
                            $cart_item_data[
                                'aec_config'
                            ]['Sin']
                        );

                    } else {

                        $cart_item_data[
                            'aec_config'
                        ]['Sin'] =
                            implode(
                                ', ',
                                $sin
                            );
                    }
                }


                $cart_item_data[
                    'aec_config'
                ]['No disponible'] =
                    implode(
                        ', ',
                        $no_disponibles
                    );
            }
        }


        /**
         * QUESO TLACOYO / GORDITA
         */

        if (
            !empty(
                $config['queso']
            )
        ) {

            if (
                !aec_disponibilidad_disponible(
                    'queso'
                )
            ) {

                if (
                    !empty(
                        $cart_item_data[
                            'aec_config'
                        ]
                    )
                ) {

                    $cart_item_data[
                        'aec_config'
                    ]['Queso'] =
                        'Agotado';
                }

            } elseif (
                !empty(
                    $_POST[
                        'aec_queso'
                    ]
                )
            ) {

                $selecciones[] = [

                    'key' =>
                        'queso',

                    'label' =>
                        'Queso',
                ];
            }
        }
    }


    if (
        $config['tipo']
        === 'agua'
    ) {

        $agregar(
            aec_post_key(
                'aec_sabor'
            ),
            $config['sabores']
        );
    }


    if (
        $config['tipo']
        === 'refresco'
    ) {

        $agregar(
            aec_post_key(
                'aec_refresco'
            ),
            $config['refrescos']
        );
    }


    if (
        !empty(
            $selecciones
        )
    ) {

        $cart_item_data[
            '_aec_availability_selection'
        ] =
            $selecciones;
    }


    return $cart_item_data;
}


add_filter(
    'woocommerce_add_cart_item_data',
    'aec_ingredient_availability_cart_data',
    20,
    4
);


/**
 * =========================================================
 * REVALIDAR CARRITO ANTES DEL CHECKOUT
 * =========================================================
 *
 * Si el administrador marca algo como agotado
 * DESPUÉS de que el cliente lo agregó al carrito,
 * impedimos que el pedido continúe con esa opción.
 */

function aec_ingredient_availability_check_cart()
{
    static $ejecutado =
        false;


    if ($ejecutado) {
        return;
    }


    $ejecutado =
        true;


    if (
        !function_exists('WC') ||
        !WC()->cart
    ) {
        return;
    }


    foreach (
        WC()->cart->get_cart()
        as $cart_item
    ) {

        if (
            empty(
                $cart_item[
                    '_aec_availability_selection'
                ]
            ) ||
            !is_array(
                $cart_item[
                    '_aec_availability_selection'
                ]
            )
        ) {

            continue;
        }


        foreach (
            $cart_item[
                '_aec_availability_selection'
            ]
            as $seleccion
        ) {

            if (
                empty(
                    $seleccion['key']
                )
            ) {
                continue;
            }


            if (
                aec_disponibilidad_disponible(
                    $seleccion['key']
                )
            ) {

                continue;
            }


            $producto =
                isset(
                    $cart_item['data']
                )
                    ? $cart_item[
                        'data'
                    ]->get_name()
                    : 'producto';


            $nombre =
                $seleccion['label']
                ??
                aec_disponibilidad_nombre(
                    $seleccion['key']
                );


            wc_add_notice(
                sprintf(
                    '"%s" del producto "%s" se agotó mientras realizabas tu pedido. Elimina el producto del carrito y vuelve a configurarlo.',
                    $nombre,
                    $producto
                ),
                'error'
            );


            return;
        }
    }
}


add_action(
    'woocommerce_check_cart_items',
    'aec_ingredient_availability_check_cart',
    20
);
