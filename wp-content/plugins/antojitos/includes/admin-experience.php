<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * =========================================================
 * ANTOJITOS EL CHINO
 * EXPERIENCIA ADMINISTRATIVA
 * =========================================================
 *
 * Objetivos:
 *
 * - Mantener una interfaz operativa limpia.
 * - Ocultar menús genéricos de WordPress.
 * - Entrar directamente al Panel Antojitos.
 * - Mantener acceso técnico para administradores.
 *
 * No elimina funcionalidades.
 *
 * Únicamente modifica navegación y presentación.
 */


/**
 * =========================================================
 * META DEL MODO ADMINISTRATIVO
 * =========================================================
 */

define(
    'AEC_ADMIN_MODE_META',
    '_aec_admin_interface_mode'
);


/**
 * =========================================================
 * ¿USUARIO OPERATIVO?
 * =========================================================
 */

function aec_admin_experience_is_user()
{
    return
        is_user_logged_in()
        &&
        (
            current_user_can(
                'manage_woocommerce'
            )
            ||
            current_user_can(
                'edit_shop_orders'
            )
        );
}


/**
 * =========================================================
 * ¿PUEDE USAR MODO TÉCNICO?
 * =========================================================
 *
 * Solo administradores reales de WordPress.
 */

function aec_admin_experience_can_use_technical_mode()
{
    return current_user_can(
        'manage_options'
    );
}


/**
 * =========================================================
 * MODO ACTUAL
 * =========================================================
 */

function aec_admin_experience_mode()
{
    /**
     * Usuarios que no son administradores técnicos
     * siempre permanecen en modo operativo.
     */
    if (
        !aec_admin_experience_can_use_technical_mode()
    ) {
        return 'operation';
    }


    $mode =
        get_user_meta(
            get_current_user_id(),
            AEC_ADMIN_MODE_META,
            true
        );


    return
        $mode === 'technical'
            ? 'technical'
            : 'operation';
}


/**
 * =========================================================
 * ¿ESTAMOS EN MODO OPERATIVO?
 * =========================================================
 */

function aec_admin_experience_is_operation_mode()
{
    return
        aec_admin_experience_is_user()
        &&
        aec_admin_experience_mode()
            === 'operation';
}


/**
 * =========================================================
 * URL DEL PANEL
 * =========================================================
 */

function aec_admin_experience_dashboard_url()
{
    return admin_url(
        'admin.php?page=aec-admin'
    );
}


/**
 * =========================================================
 * URL PARA CAMBIAR MODO
 * =========================================================
 */

function aec_admin_experience_mode_url(
    $mode
) {
    $mode =
        $mode === 'technical'
            ? 'technical'
            : 'operation';


    return wp_nonce_url(
        add_query_arg(
            [
                'action' =>
                    'aec_admin_change_mode',

                'mode' =>
                    $mode,
            ],
            admin_url(
                'admin-post.php'
            )
        ),
        'aec_admin_change_mode_'
        .
        $mode
    );
}


/**
 * =========================================================
 * CAMBIAR MODO
 * =========================================================
 */

function aec_admin_experience_change_mode()
{
    if (
        !is_user_logged_in()
        ||
        !aec_admin_experience_can_use_technical_mode()
    ) {

        wp_die(
            'No tienes permisos para realizar esta acción.'
        );
    }


    $mode =
        isset($_GET['mode'])
            ? sanitize_key(
                wp_unslash(
                    $_GET['mode']
                )
            )
            : '';


    if (
        !in_array(
            $mode,
            [
                'operation',
                'technical',
            ],
            true
        )
    ) {

        wp_die(
            'Modo administrativo inválido.'
        );
    }


    check_admin_referer(
        'aec_admin_change_mode_'
        .
        $mode
    );


    update_user_meta(
        get_current_user_id(),
        AEC_ADMIN_MODE_META,
        $mode
    );


    /**
     * Modo técnico:
     * ir al escritorio tradicional.
     */
    if (
        $mode === 'technical'
    ) {

        wp_safe_redirect(
            admin_url(
                'index.php'
            )
        );


        exit;
    }


    /**
     * Modo operativo:
     * regresar al panel Antojitos.
     */
    wp_safe_redirect(
        aec_admin_experience_dashboard_url()
    );


    exit;
}


add_action(
    'admin_post_aec_admin_change_mode',
    'aec_admin_experience_change_mode'
);


/**
 * =========================================================
 * SUBMENÚ PARA CAMBIAR A MODO TÉCNICO
 * =========================================================
 */

function aec_admin_experience_mode_menu()
{
    if (
        !aec_admin_experience_can_use_technical_mode()
    ) {
        return;
    }


    $mode =
        aec_admin_experience_mode();


    /**
     * En modo operativo mostramos una salida
     * hacia la administración completa.
     */
    if (
        $mode === 'operation'
    ) {

        add_submenu_page(
            'aec-admin',
            'Administración técnica',
            'Administración técnica',
            'manage_options',
            'aec-admin-technical-mode',
            'aec_admin_experience_technical_redirect_page'
        );
    }
}


add_action(
    'admin_menu',
    'aec_admin_experience_mode_menu',
    90
);


/**
 * =========================================================
 * REDIRECCIÓN AL MODO TÉCNICO
 * =========================================================
 */

function aec_admin_experience_technical_redirect_page()
{
    if (
        !aec_admin_experience_can_use_technical_mode()
    ) {

        wp_die(
            'No tienes permisos.'
        );
    }


    ?>

    <div class="wrap">

        <h1>
            Administración técnica
        </h1>


        <p>
            Esta vista habilita temporalmente los menús
            completos de WordPress y WooCommerce.
        </p>


        <p>

            <a
                class="button button-primary"
                href="<?php
                    echo esc_url(
                        aec_admin_experience_mode_url(
                            'technical'
                        )
                    );
                ?>"
            >
                Entrar a administración técnica
            </a>

        </p>

    </div>

    <?php
}


/**
 * =========================================================
 * OCULTAR MENÚS GENÉRICOS
 * =========================================================
 *
 * Importante:
 *
 * remove_menu_page() solamente elimina el elemento
 * visual del menú.
 *
 * Las páginas continúan existiendo y los accesos
 * directos siguen funcionando.
 */

function aec_admin_experience_clean_menu()
{
    if (
        !aec_admin_experience_is_operation_mode()
    ) {
        return;
    }


    /**
     * Escritorio.
     */
    remove_menu_page(
        'index.php'
    );


    /**
     * Entradas.
     */
    remove_menu_page(
        'edit.php'
    );


    /**
     * Medios.
     */
    remove_menu_page(
        'upload.php'
    );


    /**
     * Páginas.
     */
    remove_menu_page(
        'edit.php?post_type=page'
    );


    /**
     * Comentarios.
     */
    remove_menu_page(
        'edit-comments.php'
    );


    /**
     * WooCommerce.
     *
     * Las funciones siguen accesibles
     * mediante nuestro panel.
     */
    remove_menu_page(
        'woocommerce'
    );


    /**
     * Productos.
     */
    remove_menu_page(
        'edit.php?post_type=product'
    );


    /**
     * Marketing de WooCommerce.
     */
    remove_menu_page(
        'woocommerce-marketing'
    );


    /**
     * WooCommerce Analytics / WC Admin,
     * cuando aparece como menú independiente.
     */
    remove_menu_page(
        'wc-admin'
    );


    /**
     * Herramientas.
     */
    remove_menu_page(
        'tools.php'
    );


    /**
     * Ajustes generales WordPress.
     *
     * La configuración necesaria para operación
     * entra mediante nuestro Panel.
     */
    remove_menu_page(
        'options-general.php'
    );


    /**
     * Plugins.
     *
     * Solo se necesitan en administración técnica.
     */
    remove_menu_page(
        'plugins.php'
    );


    /**
     * Apariencia.
     */
    remove_menu_page(
        'themes.php'
    );


    /**
     * Usuarios.
     */
    remove_menu_page(
        'users.php'
    );
}


add_action(
    'admin_menu',
    'aec_admin_experience_clean_menu',
    999
);


/**
 * =========================================================
 * OCULTAR OTROS MENÚS GENÉRICOS DINÁMICOS
 * =========================================================
 *
 * Algunos plugins crean menús propios cuyo slug
 * puede variar.
 *
 * Eliminamos únicamente algunos conocidos.
 */

function aec_admin_experience_clean_dynamic_menu()
{
    if (
        !aec_admin_experience_is_operation_mode()
    ) {
        return;
    }


    global $menu;


    if (
        !is_array($menu)
    ) {
        return;
    }


    /**
     * Títulos genéricos que no queremos
     * en la experiencia operativa.
     */
    $blocked_titles = [

        'Pagos',

        'Payments',

        'Analytics',

        'Marketing',

    ];


    foreach (
        $menu as
        $index => $item
    ) {

        if (
            empty($item[0])
        ) {
            continue;
        }


        $title =
            wp_strip_all_tags(
                $item[0]
            );


        foreach (
            $blocked_titles as
            $blocked
        ) {

            if (
                strcasecmp(
                    trim($title),
                    $blocked
                )
                === 0
            ) {

                unset(
                    $menu[$index]
                );


                break;
            }
        }
    }
}


add_action(
    'admin_menu',
    'aec_admin_experience_clean_dynamic_menu',
    1000
);


/**
 * =========================================================
 * REDIRECCIÓN DESPUÉS DEL LOGIN
 * =========================================================
 */

function aec_admin_experience_login_redirect(
    $redirect_to,
    $requested_redirect_to,
    $user
) {
    if (
        !($user instanceof WP_User)
    ) {
        return $redirect_to;
    }


    if (
        user_can(
            $user,
            'manage_woocommerce'
        )
        ||
        user_can(
            $user,
            'edit_shop_orders'
        )
    ) {

        /**
         * Administradores en modo técnico
         * mantienen el escritorio tradicional.
         */
        if (
            user_can(
                $user,
                'manage_options'
            )
        ) {

            $mode =
                get_user_meta(
                    $user->ID,
                    AEC_ADMIN_MODE_META,
                    true
                );


            if (
                $mode === 'technical'
            ) {

                return admin_url(
                    'index.php'
                );
            }
        }


        return
            aec_admin_experience_dashboard_url();
    }


    return $redirect_to;
}


add_filter(
    'login_redirect',
    'aec_admin_experience_login_redirect',
    30,
    3
);


/**
 * =========================================================
 * REDIRECCIÓN DEL ESCRITORIO /WP-ADMIN/
 * =========================================================
 */

function aec_admin_experience_redirect_dashboard()
{
    if (
        !aec_admin_experience_is_operation_mode()
    ) {
        return;
    }


    /**
     * No interferir con AJAX.
     */
    if (
        wp_doing_ajax()
    ) {
        return;
    }


    /**
     * No interferir con admin-post.
     */
    $script =
        isset($_SERVER['PHP_SELF'])
            ? basename(
                sanitize_text_field(
                    wp_unslash(
                        $_SERVER['PHP_SELF']
                    )
                )
            )
            : '';


    if (
        $script !== 'index.php'
    ) {
        return;
    }


    /**
     * Si estamos exactamente en Dashboard,
     * ir a Antojitos.
     */
    wp_safe_redirect(
        aec_admin_experience_dashboard_url()
    );


    exit;
}


add_action(
    'admin_init',
    'aec_admin_experience_redirect_dashboard',
    20
);


/**
 * =========================================================
 * BARRA SUPERIOR DE WORDPRESS
 * =========================================================
 */

function aec_admin_experience_clean_admin_bar(
    $wp_admin_bar
) {
    if (
        !aec_admin_experience_is_operation_mode()
    ) {
        return;
    }


    /**
     * WordPress.
     */
    $wp_admin_bar->remove_node(
        'wp-logo'
    );


    /**
     * Actualizaciones.
     */
    $wp_admin_bar->remove_node(
        'updates'
    );


    /**
     * Comentarios.
     */
    $wp_admin_bar->remove_node(
        'comments'
    );


    /**
     * Crear nuevo.
     */
    $wp_admin_bar->remove_node(
        'new-content'
    );


    /**
     * Personalizar sitio.
     */
    $wp_admin_bar->remove_node(
        'customize'
    );


    /**
     * Buscador administrativo,
     * si está presente.
     */
    $wp_admin_bar->remove_node(
        'search'
    );


    /**
     * Agregar acceso directo a nuestro panel.
     */
    $wp_admin_bar->add_node(
        [
            'id' =>
                'aec-operation-panel',

            'title' =>
                'Panel Antojitos',

            'href' =>
                aec_admin_experience_dashboard_url(),

            'meta' => [

                'title' =>
                    'Ir al Panel Antojitos el Chino',
            ],
        ]
    );
}


add_action(
    'admin_bar_menu',
    'aec_admin_experience_clean_admin_bar',
    999
);


/**
 * =========================================================
 * BOTÓN "VOLVER A MODO OPERATIVO"
 * EN MODO TÉCNICO
 * =========================================================
 */

function aec_admin_experience_technical_admin_bar(
    $wp_admin_bar
) {
    if (
        !aec_admin_experience_can_use_technical_mode()
        ||
        aec_admin_experience_mode()
            !== 'technical'
    ) {
        return;
    }


    $wp_admin_bar->add_node(
        [
            'id' =>
                'aec-operation-mode',

            'title' =>
                'Volver a Antojitos',

            'href' =>
                aec_admin_experience_mode_url(
                    'operation'
                ),

            'meta' => [

                'title' =>
                    'Volver al modo operativo',
            ],
        ]
    );
}


add_action(
    'admin_bar_menu',
    'aec_admin_experience_technical_admin_bar',
    999
);


/**
 * =========================================================
 * OCULTAR AVISOS GENÉRICOS DENTRO DEL PANEL
 * =========================================================
 *
 * No desactivamos ningún plugin.
 *
 * Únicamente evitamos que avisos de WordPress,
 * WooCommerce o Action Scheduler ensucien
 * nuestras pantallas operativas.
 */

function aec_admin_experience_hide_notices()
{
    if (
        !aec_admin_experience_is_operation_mode()
    ) {
        return;
    }


    $page =
        isset($_GET['page'])
            ? sanitize_key(
                wp_unslash(
                    $_GET['page']
                )
            )
            : '';


    $our_pages = [

        'aec-admin',

        'aec-transferencias',

        'aec-order-board',

        'antojitos-disponibilidad',

    ];


    if (
        !in_array(
            $page,
            $our_pages,
            true
        )
    ) {
        return;
    }


    ?>

    <style>

        /*
         * Ocultar avisos genéricos solamente
         * dentro de nuestras páginas.
         */

        .aec-operation-mode
        #wpbody-content
        > .notice,

        .aec-operation-mode
        #wpbody-content
        > .updated,

        .aec-operation-mode
        #wpbody-content
        > .error,

        .aec-operation-mode
        #wpbody-content
        > .update-nag,

        .aec-operation-mode
        .woocommerce-layout__notice-list-hide {
            display: none !important;
        }

    </style>

    <?php
}


add_action(
    'admin_head',
    'aec_admin_experience_hide_notices',
    100
);


/**
 * =========================================================
 * BODY CLASS
 * =========================================================
 */

function aec_admin_experience_body_class(
    $classes
) {
    if (
        aec_admin_experience_is_operation_mode()
    ) {

        $classes .=
            ' aec-operation-mode';
    }


    return $classes;
}


add_filter(
    'admin_body_class',
    'aec_admin_experience_body_class'
);


/**
 * =========================================================
 * ESTILO DEL MENÚ OPERATIVO
 * =========================================================
 */

function aec_admin_experience_styles()
{
    if (
        !aec_admin_experience_is_operation_mode()
    ) {
        return;
    }


    ?>

    <style>

        /*
         * =================================================
         * MENÚ ANTOJITOS
         * =================================================
         */

        #adminmenu
        #toplevel_page_aec-admin
        > a {
            font-weight: 700;
        }


        #adminmenu
        #toplevel_page_aec-admin
        > a
        .wp-menu-image::before {
            color: #f2b544;
        }


        #adminmenu
        #toplevel_page_aec-admin.wp-has-current-submenu
        > a.wp-has-current-submenu,

        #adminmenu
        #toplevel_page_aec-admin
        > a:hover {
            background: #294c73;
            color: #ffffff;
        }


        #adminmenu
        #toplevel_page_aec-admin
        .wp-submenu
        a {
            transition:
                color 0.15s ease,
                padding-left 0.15s ease;
        }


        #adminmenu
        #toplevel_page_aec-admin
        .wp-submenu
        a:hover {
            color: #f2b544;
            padding-left: 15px;
        }


        /*
         * =================================================
         * ADMIN BAR
         * =================================================
         */

        #wpadminbar
        #wp-admin-bar-aec-operation-panel
        > .ab-item {
            font-weight: 700;
        }


        #wpadminbar
        #wp-admin-bar-aec-operation-panel
        > .ab-item:hover {
            color: #f2b544;
        }


        /*
         * =================================================
         * FOOTER
         * =================================================
         */

        .aec-operation-mode
        #wpfooter {
            opacity: 0.55;
        }

    </style>

    <?php
}


add_action(
    'admin_head',
    'aec_admin_experience_styles',
    110
);


/**
 * =========================================================
 * TEXTO DEL FOOTER
 * =========================================================
 */

function aec_admin_experience_footer_text(
    $text
) {
    if (
        !aec_admin_experience_is_operation_mode()
    ) {
        return $text;
    }


    return
        'Antojitos el Chino · Panel de operación';
}


add_filter(
    'admin_footer_text',
    'aec_admin_experience_footer_text',
    50
);
