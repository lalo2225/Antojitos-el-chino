<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * =========================================================
 * CATÁLOGOS
 * =========================================================
 */

function aec_catalogo_opciones()
{
    $carnes = [
        'arrachera'          => 'Arrachera',
        'bistec'             => 'Bistec',
        'aguja_nortena'      => 'Aguja norteña',
        'cecina_natural'     => 'Cecina natural',
        'cecina_adobada'     => 'Cecina adobada',
        'longaniza'          => 'Longaniza',
        'longaniza_verde'    => 'Longaniza verde',
        'pollo'              => 'Pollo',
        'pechuga_empanizada' => 'Pechuga empanizada',
    ];


    $guisados = [
        'queso'         => 'Queso',
        'pollo'         => 'Pollo',
        'papa'          => 'Papa',
        'carne'         => 'Carne',
        'chicharron'    => 'Chicharrón',
        'picadillo'     => 'Picadillo',
        'huitlacoche'   => 'Huitlacoche',
        'champinones'   => 'Champiñones',
        'setas'         => 'Setas',
        'sesos'         => 'Sesos',
        'pancita'       => 'Pancita',
        'flor_calabaza' => 'Flor de calabaza',
    ];


    /*
     * Usamos prefijos internos para evitar conflictos.
     *
     * Ejemplo:
     *
     * carne_pollo
     * guisado_pollo
     *
     * Visualmente el cliente solo verá "Pollo".
     */

    $carne_o_guisado = [];

    $grupo_carnes = [];

    $grupo_guisados = [];


    foreach ($carnes as $valor => $nombre) {

        $clave =
            'carne_' . $valor;

        $carne_o_guisado[$clave] =
            $nombre;

        $grupo_carnes[$clave] =
            $nombre;
    }


    foreach ($guisados as $valor => $nombre) {

        $clave =
            'guisado_' . $valor;

        $carne_o_guisado[$clave] =
            $nombre;

        $grupo_guisados[$clave] =
            $nombre;
    }


    return [

        'carnes' =>
            $carnes,

        'guisados' =>
            $guisados,

        'carne_o_guisado' =>
            $carne_o_guisado,

        'carne_o_guisado_grupos' => [

            'Carnes' =>
                $grupo_carnes,

            'Guisados' =>
                $grupo_guisados,
        ],


        'pambazos' => [

            'papa_chorizo' =>
                'Papa con chorizo',

            'pollo' =>
                'Pollo',

            'carne' =>
                'Carne',
        ],


        'tlacoyos' => [

            'frijol' =>
                'Frijol',

            'haba' =>
                'Haba',

            'requeson' =>
                'Requesón',
        ],


        'gorditas' => [

            'chicharron' =>
                'Chicharrón',

            'pollo' =>
                'Pollo',
        ],


        'base_sope_volcan' => [

            'frijol' =>
                'Frijol',

            'requeson' =>
                'Requesón',
        ],


        'acompanamiento' => [

            'nopal' =>
                'Nopal',

            'quelite' =>
                'Quelite',
        ],


        'agua_sabores' => [

            'horchata' =>
                'Horchata',

            'jamaica' =>
                'Jamaica',

            'tamarindo' =>
                'Tamarindo',
        ],


        'refrescos' => [

            'boing_mango' =>
                'Boing Mango',

            'boing_guayaba' =>
                'Boing Guayaba',

            'coca_cola' =>
                'Coca-Cola',

            'coca_sin_azucar' =>
                'Coca-Cola Sin Azúcar',

            'sidral_mundet' =>
                'Sidral Mundet',
        ],
    ];
}


/**
 * =========================================================
 * CONFIGURACIÓN DE CADA PRODUCTO
 * =========================================================
 */

function aec_configuracion_producto($producto)
{
    if (!$producto) {
        return null;
    }


    if (
        $producto->get_meta(
            '_aec_configurable'
        ) !== 'yes'
    ) {
        return null;
    }


    $catalogo =
        aec_catalogo_opciones();


    $sku =
        $producto->get_sku();


    switch ($sku) {


        /**
         * -------------------------------------------------
         * TACO
         * -------------------------------------------------
         */

        case 'AEC-TACO':

            return [

                'tipo' =>
                    'comida',

                'label' =>
                    'Elige tu carne',

                'meta' =>
                    'Carne',

                'opciones' =>
                    $catalogo['carnes'],

                'grupos' =>
                    [],

                'preparacion' => [

                    'papa' =>
                        'Papa',

                    'nopal' =>
                        'Nopal',

                    'cebolla' =>
                        'Cebolla',
                ],

                'ejemplo_especial' =>
                    'Ej. cecina con longaniza, más nopal o cebolla aparte.',
            ];



        /**
         * -------------------------------------------------
         * QUESADILLA
         * -------------------------------------------------
         */

        case 'AEC-QUESADILLA':

            return [

                'tipo' =>
                    'comida',

                'label' =>
                    'Elige tu guisado',

                'meta' =>
                    'Guisado',

                'opciones' =>
                    $catalogo['guisados'],

                'grupos' =>
                    [],

                'preparacion' => [

                    'lechuga' =>
                        'Lechuga',

                    'queso' =>
                        'Queso',

                    'crema' =>
                        'Crema',
                ],

                'ejemplo_especial' =>
                    'Ej. huitlacoche con champiñones, más queso o crema aparte.',
            ];



        /**
         * -------------------------------------------------
         * PAMBAZO
         * -------------------------------------------------
         */

        case 'AEC-PAMBAZO':

            return [

                'tipo' =>
                    'comida',

                'label' =>
                    'Elige tu relleno',

                'meta' =>
                    'Relleno',

                'opciones' =>
                    $catalogo['pambazos'],

                'grupos' =>
                    [],

                'preparacion' => [

                    'lechuga' =>
                        'Lechuga',

                    'queso' =>
                        'Queso',

                    'crema' =>
                        'Crema',
                ],

                'ejemplo_especial' =>
                    'Ej. combinar rellenos, más queso o crema aparte.',
            ];



        /**
         * -------------------------------------------------
         * VOLCÁN
         * -------------------------------------------------
         */

        case 'AEC-VOLCAN':

            return [

                'tipo' =>
                    'comida',

                'label' =>
                    'Elige carne o guisado',

                'meta' =>
                    'Elección',

                'opciones' =>
                    $catalogo[
                        'carne_o_guisado'
                    ],

                'grupos' =>
                    $catalogo[
                        'carne_o_guisado_grupos'
                    ],

                'base' =>
                    $catalogo[
                        'base_sope_volcan'
                    ],

                'preparacion' => [

                    'nopal' =>
                        'Nopal',

                    'quelite' =>
                        'Quelite',

                    'lechuga' =>
                        'Lechuga',

                    'queso' =>
                        'Queso',

                    'crema' =>
                        'Crema',

                    'papas' =>
                        'Papas',
                ],

                'ejemplo_especial' =>
                    'Ej. cecina con longaniza, más queso o papas aparte.',
            ];



        /**
         * -------------------------------------------------
         * SOPE
         * -------------------------------------------------
         */

        case 'AEC-SOPE':

            return [

                'tipo' =>
                    'comida',

                'label' =>
                    'Elige carne o guisado',

                'meta' =>
                    'Elección',

                'opciones' =>
                    $catalogo[
                        'carne_o_guisado'
                    ],

                'grupos' =>
                    $catalogo[
                        'carne_o_guisado_grupos'
                    ],

                'base' =>
                    $catalogo[
                        'base_sope_volcan'
                    ],

                'preparacion' => [

                    'nopal' =>
                        'Nopal',

                    'quelite' =>
                        'Quelite',

                    'lechuga' =>
                        'Lechuga',

                    'queso' =>
                        'Queso',

                    'crema' =>
                        'Crema',

                    'papas' =>
                        'Papas',
                ],

                'ejemplo_especial' =>
                    'Ej. bistec con longaniza, más crema o sin papas.',
            ];



        /**
         * -------------------------------------------------
         * HUARACHE
         * -------------------------------------------------
         */

        case 'AEC-HUARACHE':

            return [

                'tipo' =>
                    'comida',

                'label' =>
                    'Elige carne o guisado',

                'meta' =>
                    'Elección',

                'opciones' =>
                    $catalogo[
                        'carne_o_guisado'
                    ],

                'grupos' =>
                    $catalogo[
                        'carne_o_guisado_grupos'
                    ],

                'preparacion' => [

                    'nopal' =>
                        'Nopal',

                    'quelite' =>
                        'Quelite',

                    'lechuga' =>
                        'Lechuga',

                    'queso' =>
                        'Queso',

                    'crema' =>
                        'Crema',

                    'papas' =>
                        'Papas',
                ],

                'ejemplo_especial' =>
                    'Ej. combinar dos carnes, más nopal o crema aparte.',
            ];



        /**
         * -------------------------------------------------
         * TLACOYO
         * -------------------------------------------------
         */

        case 'AEC-TLACOYO':

            return [

                'tipo' =>
                    'comida',

                'label' =>
                    'Elige tu relleno',

                'meta' =>
                    'Relleno',

                'opciones' =>
                    $catalogo['tlacoyos'],

                'grupos' =>
                    [],

                'acompanamiento' =>
                    $catalogo[
                        'acompanamiento'
                    ],

                'queso' =>
                    true,

                'ejemplo_especial' =>
                    'Ej. frijol con requesón, más nopal o sin queso.',
            ];



        /**
         * -------------------------------------------------
         * GORDITA
         * -------------------------------------------------
         */

        case 'AEC-GORDITA':

            return [

                'tipo' =>
                    'comida',

                'label' =>
                    'Elige tu relleno',

                'meta' =>
                    'Relleno',

                'opciones' =>
                    $catalogo['gorditas'],

                'grupos' =>
                    [],

                'acompanamiento' =>
                    $catalogo[
                        'acompanamiento'
                    ],

                'queso' =>
                    true,

                'ejemplo_especial' =>
                    'Ej. gordita con carne, más quelite o sin queso.',
            ];



        /**
         * -------------------------------------------------
         * AGUA FRESCA
         * -------------------------------------------------
         */

        case 'AEC-AGUA-FRESCA':

            return [

                'tipo' =>
                    'agua',

                'sabores' =>
                    $catalogo[
                        'agua_sabores'
                    ],
            ];



        /**
         * -------------------------------------------------
         * REFRESCO
         * -------------------------------------------------
         */

        case 'AEC-REFRESCO':

            return [

                'tipo' =>
                    'refresco',

                'refrescos' =>
                    $catalogo[
                        'refrescos'
                    ],
            ];
    }


    return null;
}


/**
 * =========================================================
 * UTILIDADES POST
 * =========================================================
 */

function aec_post_key($nombre)
{
    if (
        !isset(
            $_POST[$nombre]
        )
    ) {
        return '';
    }


    return sanitize_key(
        wp_unslash(
            $_POST[$nombre]
        )
    );
}


function aec_post_textarea($nombre)
{
    if (
        !isset(
            $_POST[$nombre]
        )
    ) {
        return '';
    }


    return sanitize_textarea_field(
        wp_unslash(
            $_POST[$nombre]
        )
    );
}


/**
 * =========================================================
 * RENDER SELECT
 * =========================================================
 *
 * Si existen grupos:
 *
 * CARNES
 *   Arrachera
 *   Bistec
 *
 * GUISADOS
 *   Queso
 *   Huitlacoche
 *
 * Si no existen grupos, renderiza un select normal.
 */

function aec_render_select(
    $nombre,
    $label,
    $opciones,
    $grupos = []
) {
    ?>

    <div class="aec-field">

        <label
            for="<?php
                echo esc_attr(
                    $nombre
                );
            ?>"
        >
            <?php
            echo esc_html(
                $label
            );
            ?>
        </label>


        <select
            id="<?php
                echo esc_attr(
                    $nombre
                );
            ?>"
            name="<?php
                echo esc_attr(
                    $nombre
                );
            ?>"
        >

            <option value="">
                Selecciona una opción
            </option>


            <?php
            /*
             * Si existen grupos utilizamos OPTGROUP.
             */
            if (!empty($grupos)):
            ?>

                <?php
                foreach (
                    $grupos
                    as $nombre_grupo => $opciones_grupo
                ):
                ?>

                    <optgroup
                        label="<?php
                            echo esc_attr(
                                $nombre_grupo
                            );
                        ?>"
                    >

                        <?php
                        foreach (
                            $opciones_grupo
                            as $valor => $texto
                        ):
                        ?>

                            <option
                                value="<?php
                                    echo esc_attr(
                                        $valor
                                    );
                                ?>"
                            >
                                <?php
                                echo esc_html(
                                    $texto
                                );
                                ?>
                            </option>

                        <?php endforeach; ?>

                    </optgroup>

                <?php endforeach; ?>


            <?php
            /*
             * Select normal.
             */
            else:
            ?>

                <?php
                foreach (
                    $opciones
                    as $valor => $texto
                ):
                ?>

                    <option
                        value="<?php
                            echo esc_attr(
                                $valor
                            );
                        ?>"
                    >
                        <?php
                        echo esc_html(
                            $texto
                        );
                        ?>
                    </option>

                <?php endforeach; ?>

            <?php endif; ?>


        </select>

    </div>

    <?php
}


/**
 * =========================================================
 * MOSTRAR CONFIGURADOR
 * =========================================================
 */

function aec_mostrar_configurador()
{
    global $product;


    $config =
        aec_configuracion_producto(
            $product
        );


    if (!$config) {
        return;
    }


    wp_nonce_field(
        'aec_configurar_producto',
        'aec_config_nonce'
    );

    ?>


    <div class="aec-configurador">


        <h3>
            Personaliza tu pedido
        </h3>


        <?php
        /**
         * =================================================
         * COMIDA
         * =================================================
         */

        if (
            $config['tipo']
            === 'comida'
        ):
        ?>


            <?php
            aec_render_select(
                'aec_opcion',
                $config['label'],
                $config['opciones'],
                $config['grupos'] ?? []
            );
            ?>


            <?php
            /**
             * ---------------------------------------------
             * BASE
             * Sope / Volcán
             * ---------------------------------------------
             */

            if (
                !empty(
                    $config['base']
                )
            ):
            ?>

                <div class="aec-field">

                    <span
                        class="aec-field-title"
                    >
                        Elige la base
                    </span>


                    <div
                        class="aec-radio-grid"
                    >

                        <?php
                        foreach (
                            $config['base']
                            as $valor => $texto
                        ):
                        ?>

                            <label
                                class="aec-radio-option"
                            >

                                <input
                                    type="radio"
                                    name="aec_base"
                                    value="<?php
                                        echo esc_attr(
                                            $valor
                                        );
                                    ?>"
                                >

                                <span>
                                    <?php
                                    echo esc_html(
                                        $texto
                                    );
                                    ?>
                                </span>

                            </label>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endif; ?>


            <?php
            /**
             * ---------------------------------------------
             * PREPARACIÓN
             * ---------------------------------------------
             */

            if (
                !empty(
                    $config[
                        'preparacion'
                    ]
                )
            ):
            ?>

                <div class="aec-field">

                    <span
                        class="aec-field-title"
                    >
                        ¿Cómo lo quieres preparado?
                    </span>


                    <p
                        class="aec-field-help"
                    >
                        Todos vienen seleccionados normalmente.
                        Desmarca los ingredientes que no quieras.
                    </p>


                    <div
                        class="aec-check-grid"
                    >

                        <?php
                        foreach (
                            $config['preparacion']
                            as $valor => $texto
                        ):
                        ?>

                            <label
                                class="aec-check-option"
                            >

                                <input
                                    type="checkbox"
                                    name="aec_preparacion[]"
                                    value="<?php
                                        echo esc_attr(
                                            $valor
                                        );
                                    ?>"
                                    checked
                                >

                                <span>
                                    <?php
                                    echo esc_html(
                                        $texto
                                    );
                                    ?>
                                </span>

                            </label>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endif; ?>


            <?php
            /**
             * ---------------------------------------------
             * ACOMPAÑAMIENTO
             * Tlacoyo / Gordita
             * ---------------------------------------------
             */

            if (
                !empty(
                    $config[
                        'acompanamiento'
                    ]
                )
            ):
            ?>

                <div class="aec-field">

                    <span
                        class="aec-field-title"
                    >
                        Elige acompañamiento
                    </span>


                    <div
                        class="aec-radio-grid"
                    >

                        <?php
                        foreach (
                            $config['acompanamiento']
                            as $valor => $texto
                        ):
                        ?>

                            <label
                                class="aec-radio-option"
                            >

                                <input
                                    type="radio"
                                    name="aec_acompanamiento"
                                    value="<?php
                                        echo esc_attr(
                                            $valor
                                        );
                                    ?>"
                                >

                                <span>
                                    <?php
                                    echo esc_html(
                                        $texto
                                    );
                                    ?>
                                </span>

                            </label>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endif; ?>


            <?php
            /**
             * ---------------------------------------------
             * QUESO
             * Tlacoyo / Gordita
             * ---------------------------------------------
             */

            if (
                !empty(
                    $config['queso']
                )
            ):
            ?>

                <div class="aec-field">

                    <span
                        class="aec-field-title"
                    >
                        Queso
                    </span>


                    <label
                        class="aec-check-option"
                    >

                        <input
                            type="checkbox"
                            name="aec_queso"
                            value="1"
                            checked
                        >

                        <span>
                            Con queso
                        </span>

                    </label>

                </div>

            <?php endif; ?>


            <?php
            /**
             * ---------------------------------------------
             * INSTRUCCIÓN ESPECIAL
             * ---------------------------------------------
             */
            ?>

            <div
                class="aec-field aec-special-field"
            >

                <label
                    for="aec_especial"
                >
                    ¿Quieres algo especial?
                </label>


                <p
                    class="aec-field-help"
                >
                    Puedes indicar combinaciones
                    o cambios adicionales
                    en tu preparación.
                </p>


                <textarea
                    id="aec_especial"
                    name="aec_especial"
                    rows="3"
                    maxlength="180"
                    placeholder="<?php
                        echo esc_attr(
                            $config[
                                'ejemplo_especial'
                            ]
                        );
                    ?>"
                ></textarea>


                <small
                    class="aec-character-help"
                >
                    Máximo 180 caracteres.
                </small>

            </div>


        <?php endif; ?>



        <?php
        /**
         * =================================================
         * AGUA FRESCA
         * =================================================
         */

        if (
            $config['tipo']
            === 'agua'
        ):
        ?>


            <?php
            aec_render_select(
                'aec_sabor',
                'Elige el sabor',
                $config['sabores']
            );
            ?>


            <div class="aec-field">

                <span
                    class="aec-field-title"
                >
                    Elige el tamaño
                </span>


                <div
                    class="aec-radio-grid"
                >


                    <label
                        class="aec-radio-option"
                    >

                        <input
                            type="radio"
                            name="aec_tamano"
                            value="medio"
                            data-price="30"
                        >

                        <span>
                            1/2 litro — $30
                        </span>

                    </label>


                    <label
                        class="aec-radio-option"
                    >

                        <input
                            type="radio"
                            name="aec_tamano"
                            value="litro"
                            data-price="40"
                        >

                        <span>
                            1 litro — $40
                        </span>

                    </label>


                </div>


                <div
                    id="aec-price-preview"
                    class="aec-price-preview"
                >
                    Selecciona el tamaño
                </div>


            </div>


        <?php endif; ?>



        <?php
        /**
         * =================================================
         * REFRESCO
         * =================================================
         */

        if (
            $config['tipo']
            === 'refresco'
        ):
        ?>


            <?php
            aec_render_select(
                'aec_refresco',
                'Elige tu bebida',
                $config[
                    'refrescos'
                ]
            );
            ?>


        <?php endif; ?>


    </div>


    <?php
}


add_action(
    'woocommerce_before_add_to_cart_button',
    'aec_mostrar_configurador'
);


/**
 * =========================================================
 * VALIDACIÓN
 * =========================================================
 */

function aec_validar_configuracion(
    $valido,
    $product_id,
    $quantity,
    $variation_id = 0,
    $variations = [],
    $cart_item_data = []
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
        return $valido;
    }


    /**
     * NONCE
     */

    $nonce =
        isset(
            $_POST[
                'aec_config_nonce'
            ]
        )
            ? sanitize_text_field(
                wp_unslash(
                    $_POST[
                        'aec_config_nonce'
                    ]
                )
            )
            : '';


    if (
        !$nonce ||
        !wp_verify_nonce(
            $nonce,
            'aec_configurar_producto'
        )
    ) {

        wc_add_notice(
            'Abre el producto y configura tu pedido.',
            'error'
        );

        return false;
    }


    /**
     * COMIDA
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
            !$opcion ||
            !isset(
                $config[
                    'opciones'
                ][$opcion]
            )
        ) {

            wc_add_notice(
                'Selecciona una opción para tu producto.',
                'error'
            );

            return false;
        }


        /**
         * BASE SOPE / VOLCÁN
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
                !$base ||
                !isset(
                    $config[
                        'base'
                    ][$base]
                )
            ) {

                wc_add_notice(
                    'Selecciona Frijol o Requesón.',
                    'error'
                );

                return false;
            }
        }


        /**
         * ACOMPAÑAMIENTO
         */

        if (
            !empty(
                $config[
                    'acompanamiento'
                ]
            )
        ) {

            $acompanamiento =
                aec_post_key(
                    'aec_acompanamiento'
                );


            if (
                !$acompanamiento ||
                !isset(
                    $config[
                        'acompanamiento'
                    ][$acompanamiento]
                )
            ) {

                wc_add_notice(
                    'Selecciona Nopal o Quelite.',
                    'error'
                );

                return false;
            }
        }


        /**
         * ESPECIAL
         */

        $especial =
            aec_post_textarea(
                'aec_especial'
            );


        $longitud =
            function_exists(
                'mb_strlen'
            )
                ? mb_strlen(
                    $especial
                )
                : strlen(
                    $especial
                );


        if ($longitud > 180) {

            wc_add_notice(
                'La indicación especial es demasiado larga.',
                'error'
            );

            return false;
        }
    }


    /**
     * AGUA
     */

    if (
        $config['tipo']
        === 'agua'
    ) {

        $sabor =
            aec_post_key(
                'aec_sabor'
            );


        $tamano =
            aec_post_key(
                'aec_tamano'
            );


        if (
            !$sabor ||
            !isset(
                $config[
                    'sabores'
                ][$sabor]
            )
        ) {

            wc_add_notice(
                'Selecciona el sabor del agua.',
                'error'
            );

            return false;
        }


        if (
            !in_array(
                $tamano,
                [
                    'medio',
                    'litro',
                ],
                true
            )
        ) {

            wc_add_notice(
                'Selecciona el tamaño del agua.',
                'error'
            );

            return false;
        }
    }


    /**
     * REFRESCO
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
            !$refresco ||
            !isset(
                $config[
                    'refrescos'
                ][$refresco]
            )
        ) {

            wc_add_notice(
                'Selecciona tu bebida.',
                'error'
            );

            return false;
        }
    }


    return $valido;
}


add_filter(
    'woocommerce_add_to_cart_validation',
    'aec_validar_configuracion',
    10,
    6
);


/**
 * =========================================================
 * GUARDAR CONFIGURACIÓN EN CARRITO
 * =========================================================
 */

function aec_guardar_configuracion_carrito(
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


    $datos = [];


    /**
     * COMIDA
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
            isset(
                $config[
                    'opciones'
                ][$opcion]
            )
        ) {

            $datos[
                $config['meta']
            ] =
                $config[
                    'opciones'
                ][$opcion];
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
                isset(
                    $config[
                        'base'
                    ][$base]
                )
            ) {

                $datos['Base'] =
                    $config[
                        'base'
                    ][$base];
            }
        }


        /**
         * PREPARACIÓN
         */

        if (
            !empty(
                $config[
                    'preparacion'
                ]
            )
        ) {

            $seleccionados =
                [];


            if (
                isset(
                    $_POST[
                        'aec_preparacion'
                    ]
                ) &&
                is_array(
                    $_POST[
                        'aec_preparacion'
                    ]
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


            $incluidos =
                [];

            $retirados =
                [];


            foreach (
                $config[
                    'preparacion'
                ]
                as $clave => $texto
            ) {

                if (
                    in_array(
                        $clave,
                        $seleccionados,
                        true
                    )
                ) {

                    $incluidos[] =
                        $texto;

                } else {

                    $retirados[] =
                        $texto;
                }
            }


            $datos['Preparación'] =
                !empty(
                    $incluidos
                )
                    ? implode(
                        ', ',
                        $incluidos
                    )
                    : 'Sin ingredientes de preparación';


            if (
                !empty(
                    $retirados
                )
            ) {

                $datos['Sin'] =
                    implode(
                        ', ',
                        $retirados
                    );
            }
        }


        /**
         * ACOMPAÑAMIENTO
         */

        if (
            !empty(
                $config[
                    'acompanamiento'
                ]
            )
        ) {

            $acompanamiento =
                aec_post_key(
                    'aec_acompanamiento'
                );


            if (
                isset(
                    $config[
                        'acompanamiento'
                    ][$acompanamiento]
                )
            ) {

                $datos[
                    'Acompañamiento'
                ] =
                    $config[
                        'acompanamiento'
                    ][$acompanamiento];
            }
        }


        /**
         * QUESO
         */

        if (
            !empty(
                $config['queso']
            )
        ) {

            $datos['Queso'] =
                !empty(
                    $_POST[
                        'aec_queso'
                    ]
                )
                    ? 'Sí'
                    : 'No';
        }


        /**
         * ESPECIAL
         */

        $especial =
            aec_post_textarea(
                'aec_especial'
            );


        if (
            $especial !== ''
        ) {

            $datos['Especial'] =
                $especial;
        }
    }


    /**
     * AGUA
     */

    if (
        $config['tipo']
        === 'agua'
    ) {

        $sabor =
            aec_post_key(
                'aec_sabor'
            );


        $tamano =
            aec_post_key(
                'aec_tamano'
            );


        if (
            isset(
                $config[
                    'sabores'
                ][$sabor]
            )
        ) {

            $datos['Sabor'] =
                $config[
                    'sabores'
                ][$sabor];
        }


        if (
            $tamano === 'medio'
        ) {

            $datos['Tamaño'] =
                '1/2 litro';

            $datos[
                '_aec_price'
            ] = 30;


        } elseif (
            $tamano === 'litro'
        ) {

            $datos['Tamaño'] =
                '1 litro';

            $datos[
                '_aec_price'
            ] = 40;
        }
    }


    /**
     * REFRESCO
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
            isset(
                $config[
                    'refrescos'
                ][$refresco]
            )
        ) {

            $datos['Bebida'] =
                $config[
                    'refrescos'
                ][$refresco];
        }
    }


    $cart_item_data[
        'aec_config'
    ] =
        $datos;


    return $cart_item_data;
}


add_filter(
    'woocommerce_add_cart_item_data',
    'aec_guardar_configuracion_carrito',
    10,
    4
);


/**
 * =========================================================
 * MOSTRAR DATOS EN CARRITO
 * =========================================================
 */

function aec_mostrar_datos_carrito(
    $item_data,
    $cart_item
) {
    if (
        empty(
            $cart_item[
                'aec_config'
            ]
        )
    ) {
        return $item_data;
    }


    foreach (
        $cart_item[
            'aec_config'
        ]
        as $nombre => $valor
    ) {

        if (
            str_starts_with(
                $nombre,
                '_'
            )
        ) {
            continue;
        }


        $item_data[] = [

            'key' =>
                $nombre,

            'value' =>
                $valor,
        ];
    }


    return $item_data;
}


add_filter(
    'woocommerce_get_item_data',
    'aec_mostrar_datos_carrito',
    10,
    2
);


/**
 * =========================================================
 * GUARDAR DATOS EN PEDIDO
 * =========================================================
 */

function aec_guardar_datos_pedido(
    $item,
    $cart_item_key,
    $values,
    $order
) {
    if (
        empty(
            $values[
                'aec_config'
            ]
        )
    ) {
        return;
    }


    foreach (
        $values[
            'aec_config'
        ]
        as $nombre => $valor
    ) {

        if (
            str_starts_with(
                $nombre,
                '_'
            )
        ) {
            continue;
        }


        $item->add_meta_data(
            $nombre,
            $valor,
            true
        );
    }
}


add_action(
    'woocommerce_checkout_create_order_line_item',
    'aec_guardar_datos_pedido',
    10,
    4
);


/**
 * =========================================================
 * PRECIO DINÁMICO AGUA FRESCA
 * =========================================================
 */

function aec_aplicar_precio_configurado(
    $cart
) {
    if (
        is_admin() &&
        !defined(
            'DOING_AJAX'
        )
    ) {
        return;
    }


    foreach (
        $cart->get_cart()
        as $cart_item
    ) {

        if (
            empty(
                $cart_item[
                    'aec_config'
                ][
                    '_aec_price'
                ]
            )
        ) {
            continue;
        }


        $precio =
            (float)
            $cart_item[
                'aec_config'
            ][
                '_aec_price'
            ];


        $cart_item[
            'data'
        ]->set_price(
            $precio
        );
    }
}


add_action(
    'woocommerce_before_calculate_totals',
    'aec_aplicar_precio_configurado'
);


/**
 * =========================================================
 * BOTÓN ELEGIR EN CATÁLOGO
 * =========================================================
 */

function aec_boton_producto_configurable(
    $html,
    $product,
    $args
) {
    if (
        $product->get_meta(
            '_aec_configurable'
        ) !== 'yes'
    ) {
        return $html;
    }


    return sprintf(
        '<a href="%s" class="button aec-elegir-producto">%s</a>',
        esc_url(
            get_permalink(
                $product->get_id()
            )
        ),
        esc_html__(
            'Elegir',
            'antojitos'
        )
    );
}


add_filter(
    'woocommerce_loop_add_to_cart_link',
    'aec_boton_producto_configurable',
    10,
    3
);
