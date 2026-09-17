<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * =========================================================
 * ANTOJITOS EL CHINO
 * DISPONIBILIDAD DE INGREDIENTES Y OPCIONES
 * =========================================================
 *
 * Controla únicamente las opciones del configurador.
 *
 * NO modifica:
 *
 * - estado de productos
 * - inventario WooCommerce
 * - visibilidad de productos
 */


/**
 * =========================================================
 * CATÁLOGO MAESTRO
 * =========================================================
 */

function aec_disponibilidad_catalogo()
{
    return [

        'Carnes y proteínas' => [

            'arrachera' =>
                'Arrachera',

            'bistec' =>
                'Bistec',

            'aguja_nortena' =>
                'Aguja norteña',

            'cecina_natural' =>
                'Cecina natural',

            'cecina_adobada' =>
                'Cecina adobada',

            'longaniza' =>
                'Longaniza',

            'longaniza_verde' =>
                'Longaniza verde',

            'pollo' =>
                'Pollo',

            'pechuga_empanizada' =>
                'Pechuga empanizada',
        ],


        'Guisados y rellenos' => [

            'carne' =>
                'Carne',

            'chicharron' =>
                'Chicharrón',

            'picadillo' =>
                'Picadillo',

            'huitlacoche' =>
                'Huitlacoche',

            'champinones' =>
                'Champiñones',

            'setas' =>
                'Setas',

            'sesos' =>
                'Sesos',

            'pancita' =>
                'Pancita',

            'flor_calabaza' =>
                'Flor de calabaza',

            'papa_chorizo' =>
                'Papa con chorizo',

            'haba' =>
                'Haba',
        ],


        'Bases, ingredientes y complementos' => [

            'queso' =>
                'Queso',

            'papa' =>
                'Papa / Papas',

            'frijol' =>
                'Frijol',

            'requeson' =>
                'Requesón',

            'nopal' =>
                'Nopal',

            'quelite' =>
                'Quelite',

            'cebolla' =>
                'Cebolla',

            'lechuga' =>
                'Lechuga',

            'crema' =>
                'Crema',
        ],


        'Aguas frescas' => [

            'horchata' =>
                'Horchata',

            'jamaica' =>
                'Jamaica',

            'tamarindo' =>
                'Tamarindo',
        ],


        'Refrescos' => [

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
 * NORMALIZAR CLAVE
 * =========================================================
 */

function aec_disponibilidad_clave_canonica(
    $valor
) {
    $valor =
        sanitize_key(
            (string) $valor
        );


    if (
        str_starts_with(
            $valor,
            'carne_'
        )
    ) {

        $valor =
            substr(
                $valor,
                strlen('carne_')
            );
    }


    if (
        str_starts_with(
            $valor,
            'guisado_'
        )
    ) {

        $valor =
            substr(
                $valor,
                strlen('guisado_')
            );
    }


    $aliases = [

        'papas' =>
            'papa',

    ];


    if (
        isset(
            $aliases[$valor]
        )
    ) {

        return
            $aliases[$valor];
    }


    return $valor;
}


/**
 * =========================================================
 * CATÁLOGO PLANO
 * =========================================================
 */

function aec_disponibilidad_catalogo_plano()
{
    $resultado = [];


    foreach (
        aec_disponibilidad_catalogo()
        as $grupo => $items
    ) {

        foreach (
            $items as $clave => $nombre
        ) {

            $resultado[$clave] =
                $nombre;
        }
    }


    return $resultado;
}


/**
 * =========================================================
 * ESTADO ACTUAL
 * =========================================================
 */

function aec_disponibilidad_estado(
    $clave
) {
    $clave =
        aec_disponibilidad_clave_canonica(
            $clave
        );


    $estados =
        get_option(
            'aec_disponibilidad_opciones',
            []
        );


    if (
        !is_array(
            $estados
        )
    ) {

        $estados = [];
    }


    if (
        isset(
            $estados[$clave]
        )
        &&
        $estados[$clave]
            === 'agotado'
    ) {

        return 'agotado';
    }


    /**
     * Toda opción nueva se considera
     * disponible por defecto.
     */
    return 'disponible';
}


/**
 * =========================================================
 * ¿ESTÁ DISPONIBLE?
 * =========================================================
 */

function aec_disponibilidad_disponible(
    $clave
) {
    return
        aec_disponibilidad_estado(
            $clave
        )
        === 'disponible';
}


/**
 * =========================================================
 * OBTENER NOMBRE
 * =========================================================
 */

function aec_disponibilidad_nombre(
    $clave
) {
    $clave =
        aec_disponibilidad_clave_canonica(
            $clave
        );


    $catalogo =
        aec_disponibilidad_catalogo_plano();


    return
        $catalogo[$clave]
        ??
        $clave;
}


/**
 * =========================================================
 * CONTADORES
 * =========================================================
 */

function aec_disponibilidad_contadores()
{
    $resultado = [

        'total' =>
            0,

        'disponible' =>
            0,

        'agotado' =>
            0,

    ];


    foreach (
        aec_disponibilidad_catalogo_plano()
        as $clave => $nombre
    ) {

        $resultado['total']++;


        $estado =
            aec_disponibilidad_estado(
                $clave
            );


        if (
            $estado === 'agotado'
        ) {

            $resultado['agotado']++;

        } else {

            $resultado['disponible']++;
        }
    }


    return $resultado;
}


/**
 * =========================================================
 * MENÚ WOOCOMMERCE
 * =========================================================
 */

function antojitos_registrar_menu_disponibilidad()
{
    add_submenu_page(
        'woocommerce',
        'Disponibilidad de ingredientes',
        'Disponibilidad',
        'manage_woocommerce',
        'antojitos-disponibilidad',
        'antojitos_render_disponibilidad'
    );
}


add_action(
    'admin_menu',
    'antojitos_registrar_menu_disponibilidad'
);


/**
 * =========================================================
 * GUARDAR
 * =========================================================
 */

function antojitos_guardar_disponibilidad()
{
    if (
        !current_user_can(
            'manage_woocommerce'
        )
    ) {

        wp_die(
            'No tienes permisos para realizar esta acción.'
        );
    }


    check_admin_referer(
        'antojitos_guardar_disponibilidad'
    );


    $catalogo =
        aec_disponibilidad_catalogo_plano();


    $posted =
        isset($_POST['estado'])
        &&
        is_array(
            $_POST['estado']
        )
            ? wp_unslash(
                $_POST['estado']
            )
            : [];


    $estados = [];


    foreach (
        $catalogo as $clave => $nombre
    ) {

        $estado =
            isset(
                $posted[$clave]
            )
                ? sanitize_key(
                    $posted[$clave]
                )
                : 'disponible';


        if (
            !in_array(
                $estado,
                [
                    'disponible',
                    'agotado',
                ],
                true
            )
        ) {

            $estado =
                'disponible';
        }


        $estados[$clave] =
            $estado;
    }


    update_option(
        'aec_disponibilidad_opciones',
        $estados,
        false
    );


    wp_safe_redirect(
        admin_url(
            'admin.php?page=antojitos-disponibilidad&actualizado=1'
        )
    );


    exit;
}


add_action(
    'admin_post_antojitos_guardar_disponibilidad',
    'antojitos_guardar_disponibilidad'
);


/**
 * =========================================================
 * PANTALLA ADMINISTRATIVA
 * =========================================================
 */

function antojitos_render_disponibilidad()
{
    if (
        !current_user_can(
            'manage_woocommerce'
        )
    ) {
        return;
    }


    $catalogo =
        aec_disponibilidad_catalogo();


    $contadores =
        aec_disponibilidad_contadores();


    ?>

    <div class="wrap aec-availability-admin">


        <!-- ===============================================
             CABECERA
             =============================================== -->

        <section class="aec-availability-header">

            <div>

                <span class="aec-availability-eyebrow">
                    Antojitos el Chino
                </span>


                <h1>
                    Disponibilidad
                </h1>


                <p>
                    Controla qué ingredientes, rellenos,
                    complementos y bebidas pueden pedir
                    los clientes.
                </p>

            </div>


            <a
                class="aec-availability-back"
                href="<?php
                    echo esc_url(
                        admin_url(
                            'admin.php?page=aec-admin'
                        )
                    );
                ?>"
            >
                ← Volver al panel
            </a>

        </section>


        <?php
        if (
            isset(
                $_GET['actualizado']
            )
        ):
        ?>

            <div class="aec-availability-success">

                <span
                    class="
                        dashicons
                        dashicons-yes-alt
                    "
                ></span>


                <div>

                    <strong>
                        Disponibilidad actualizada
                    </strong>

                    <span>
                        Los cambios ya se aplican en el menú.
                    </span>

                </div>

            </div>

        <?php endif; ?>


        <!-- ===============================================
             RESUMEN
             =============================================== -->

        <section class="aec-availability-summary">

            <div class="aec-availability-summary-card">

                <span>
                    Total
                </span>

                <strong
                    id="aec-availability-count-total"
                >
                    <?php
                    echo esc_html(
                        $contadores['total']
                    );
                    ?>
                </strong>

                <small>
                    Opciones configuradas
                </small>

            </div>


            <div
                class="
                    aec-availability-summary-card
                    is-available
                "
            >

                <span>
                    Disponibles
                </span>

                <strong
                    id="aec-availability-count-available"
                >
                    <?php
                    echo esc_html(
                        $contadores['disponible']
                    );
                    ?>
                </strong>

                <small>
                    Se ofrecen al cliente
                </small>

            </div>


            <div
                class="
                    aec-availability-summary-card
                    is-soldout
                "
            >

                <span>
                    Agotados
                </span>

                <strong
                    id="aec-availability-count-soldout"
                >
                    <?php
                    echo esc_html(
                        $contadores['agotado']
                    );
                    ?>
                </strong>

                <small>
                    No se pueden seleccionar
                </small>

            </div>

        </section>


        <!-- ===============================================
             INFORMACIÓN
             =============================================== -->

        <div class="aec-availability-info">

            <span
                class="
                    dashicons
                    dashicons-info-outline
                "
            ></span>


            <div>

                <strong>
                    El estado es global.
                </strong>


                <p>
                    Si marcas un ingrediente como agotado,
                    dejará de estar disponible en todos los
                    productos donde se utilice. Los productos
                    completos permanecerán publicados.
                </p>

            </div>

        </div>


        <!-- ===============================================
             FORMULARIO
             =============================================== -->

        <form
            id="aec-availability-form"
            method="post"
            action="<?php
                echo esc_url(
                    admin_url(
                        'admin-post.php'
                    )
                );
            ?>"
        >

            <input
                type="hidden"
                name="action"
                value="antojitos_guardar_disponibilidad"
            >


            <?php
            wp_nonce_field(
                'antojitos_guardar_disponibilidad'
            );
            ?>


            <!-- ===========================================
                 HERRAMIENTAS
                 =========================================== -->

            <div class="aec-availability-toolbar">

                <div class="aec-availability-search">

                    <span
                        class="
                            dashicons
                            dashicons-search
                        "
                    ></span>


                    <input
                        type="search"
                        id="aec-availability-search"
                        placeholder="Buscar ingrediente..."
                        autocomplete="off"
                    >

                </div>


                <div
                    class="aec-availability-filters"
                    role="group"
                    aria-label="Filtrar disponibilidad"
                >

                    <button
                        type="button"
                        class="
                            aec-availability-filter
                            is-active
                        "
                        data-filter="all"
                    >
                        Todos
                    </button>


                    <button
                        type="button"
                        class="aec-availability-filter"
                        data-filter="disponible"
                    >
                        Disponibles
                    </button>


                    <button
                        type="button"
                        class="aec-availability-filter"
                        data-filter="agotado"
                    >
                        Agotados
                    </button>

                </div>

            </div>


            <!-- ===========================================
                 GRUPOS
                 =========================================== -->

            <div
                id="aec-availability-groups"
                class="aec-availability-groups"
            >

                <?php
                foreach (
                    $catalogo as
                    $grupo => $items
                ):
                ?>

                    <section
                        class="aec-availability-group"
                        data-group
                    >

                        <header
                            class="aec-availability-group-header"
                        >

                            <div>

                                <h2>
                                    <?php
                                    echo esc_html(
                                        $grupo
                                    );
                                    ?>
                                </h2>


                                <span>
                                    <?php
                                    echo esc_html(
                                        count(
                                            $items
                                        )
                                    );
                                    ?>

                                    opciones
                                </span>

                            </div>

                        </header>


                        <div
                            class="aec-availability-items"
                        >

                            <?php
                            foreach (
                                $items as
                                $clave => $nombre
                            ):

                                $estado =
                                    aec_disponibilidad_estado(
                                        $clave
                                    );


                                $agotado =
                                    $estado === 'agotado';

                            ?>

                                <div
                                    class="
                                        aec-availability-item
                                        <?php
                                        echo
                                            $agotado
                                                ? 'is-soldout'
                                                : 'is-available';
                                        ?>
                                    "
                                    data-availability-item
                                    data-name="<?php
                                        echo esc_attr(
                                            strtolower(
                                                remove_accents(
                                                    $nombre
                                                )
                                            )
                                        );
                                    ?>"
                                    data-state="<?php
                                        echo esc_attr(
                                            $estado
                                        );
                                    ?>"
                                >

                                    <input
                                        type="hidden"
                                        name="estado[<?php
                                            echo esc_attr(
                                                $clave
                                            );
                                        ?>]"
                                        value="<?php
                                            echo esc_attr(
                                                $estado
                                            );
                                        ?>"
                                        data-state-input
                                    >


                                    <div
                                        class="aec-availability-item-info"
                                    >

                                        <strong>
                                            <?php
                                            echo esc_html(
                                                $nombre
                                            );
                                            ?>
                                        </strong>


                                        <span
                                            data-state-description
                                        >

                                            <?php
                                            echo
                                                $agotado
                                                    ? 'No se ofrecerá al cliente'
                                                    : 'Disponible para pedidos';
                                            ?>

                                        </span>

                                    </div>


                                    <button
                                        type="button"
                                        class="
                                            aec-availability-toggle
                                            <?php
                                            echo
                                                $agotado
                                                    ? 'is-soldout'
                                                    : 'is-available';
                                            ?>
                                        "
                                        data-state-toggle
                                        aria-pressed="<?php
                                            echo
                                                $agotado
                                                    ? 'true'
                                                    : 'false';
                                        ?>"
                                    >

                                        <span
                                            class="aec-availability-status-dot"
                                            aria-hidden="true"
                                        ></span>


                                        <span
                                            data-state-label
                                        >
                                            <?php
                                            echo
                                                $agotado
                                                    ? 'Agotado'
                                                    : 'Disponible';
                                            ?>
                                        </span>

                                    </button>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </section>

                <?php endforeach; ?>

            </div>


            <!-- ===========================================
                 SIN RESULTADOS
                 =========================================== -->

            <div
                id="aec-availability-empty"
                class="aec-availability-empty"
                hidden
            >

                <span
                    class="
                        dashicons
                        dashicons-search
                    "
                ></span>


                <strong>
                    No encontramos coincidencias
                </strong>


                <p>
                    Prueba con otro nombre o cambia el filtro.
                </p>

            </div>


            <!-- ===========================================
                 GUARDAR
                 =========================================== -->

            <div class="aec-availability-savebar">

                <div>

                    <span
                        id="aec-availability-save-status"
                        class="aec-availability-save-status"
                    >
                        Sin cambios pendientes
                    </span>


                    <small>
                        Los cambios se aplicarán al guardar.
                    </small>

                </div>


                <button
                    type="submit"
                    id="aec-availability-save"
                    class="aec-availability-save-button"
                >
                    Guardar disponibilidad
                </button>

            </div>

        </form>

    </div>

    <?php
}


/**
 * =========================================================
 * ASSETS ADMINISTRATIVOS
 * =========================================================
 */

function aec_disponibilidad_admin_assets()
{
    $page =
        isset($_GET['page'])
            ? sanitize_key(
                wp_unslash(
                    $_GET['page']
                )
            )
            : '';


    if (
        $page !== 'antojitos-disponibilidad'
    ) {
        return;
    }


    $css_relative =
        'assets/css/admin-disponibilidad.css';


    $js_relative =
        'assets/js/admin-disponibilidad.js';


    $css_path =
        AEC_PLUGIN_DIR
        .
        $css_relative;


    $js_path =
        AEC_PLUGIN_DIR
        .
        $js_relative;


    if (
        file_exists(
            $css_path
        )
    ) {

        wp_enqueue_style(
            'aec-admin-disponibilidad',
            AEC_PLUGIN_URL
            .
            $css_relative,
            [
                'dashicons',
            ],
            (string)
            filemtime(
                $css_path
            )
        );
    }


    if (
        file_exists(
            $js_path
        )
    ) {

        wp_enqueue_script(
            'aec-admin-disponibilidad',
            AEC_PLUGIN_URL
            .
            $js_relative,
            [],
            (string)
            filemtime(
                $js_path
            ),
            true
        );
    }
}


add_action(
    'admin_enqueue_scripts',
    'aec_disponibilidad_admin_assets',
    50
);
