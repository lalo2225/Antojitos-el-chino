<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * =========================================================
 * ANTOJITOS EL CHINO
 * PANEL ADMINISTRATIVO
 * =========================================================
 *
 * Centraliza la operación del negocio sin reemplazar la
 * lógica de WooCommerce ni los módulos que ya funcionan.
 */


/**
 * =========================================================
 * PERMISOS
 * =========================================================
 */

function aec_admin_panel_user_can_access()
{
    return current_user_can(
        'manage_woocommerce'
    );
}


/**
 * =========================================================
 * MENÚ PRINCIPAL
 * =========================================================
 */

function aec_admin_panel_menu()
{
    add_menu_page(
        'Antojitos el Chino',
        'Antojitos el Chino',
        'manage_woocommerce',
        'aec-admin',
        'aec_admin_panel_page',
        'dashicons-store',
        2
    );


    add_submenu_page(
        'aec-admin',
        'Inicio',
        'Inicio',
        'manage_woocommerce',
        'aec-admin',
        'aec_admin_panel_page'
    );


    add_submenu_page(
        'aec-admin',
        'Pedidos',
        'Pedidos',
        'manage_woocommerce',
        'aec-admin-orders',
        'aec_admin_panel_redirect_placeholder'
    );


    add_submenu_page(
        'aec-admin',
        'Pantalla de pedidos',
        'Pantalla de pedidos',
        'manage_woocommerce',
        'aec-admin-board',
        'aec_admin_panel_redirect_placeholder'
    );


    add_submenu_page(
        'aec-admin',
        'Transferencias',
        'Transferencias',
        'manage_woocommerce',
        'aec-transferencias',
        'aec_admin_panel_transfer_page'
    );


    add_submenu_page(
        'aec-admin',
        'Disponibilidad',
        'Disponibilidad',
        'manage_woocommerce',
        'aec-admin-availability',
        'aec_admin_panel_redirect_placeholder'
    );


    add_submenu_page(
        'aec-admin',
        'Menú / Productos',
        'Menú / Productos',
        'manage_woocommerce',
        'aec-admin-products',
        'aec_admin_panel_redirect_placeholder'
    );


    add_submenu_page(
        'aec-admin',
        'Configuración',
        'Configuración',
        'manage_woocommerce',
        'aec-admin-settings',
        'aec_admin_panel_redirect_placeholder'
    );
}


add_action(
    'admin_menu',
    'aec_admin_panel_menu',
    5
);


/**
 * =========================================================
 * CALLBACK DE RESPALDO
 * =========================================================
 */

function aec_admin_panel_redirect_placeholder()
{
    echo '<div class="wrap">';
    echo '<p>Redirigiendo…</p>';
    echo '</div>';
}


/**
 * =========================================================
 * REDIRECCIONES DE SUBMENÚ
 * =========================================================
 */

function aec_admin_panel_handle_redirects()
{
    if (
        !is_admin()
        ||
        wp_doing_ajax()
    ) {
        return;
    }


    if (
        !aec_admin_panel_user_can_access()
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


    $redirects = [

        'aec-admin-orders' =>
            admin_url(
                'admin.php?page=wc-orders'
            ),

        'aec-admin-board' =>
            admin_url(
                'admin.php?page=aec-order-board'
            ),

        'aec-admin-availability' =>
            admin_url(
                'admin.php?page=antojitos-disponibilidad'
            ),

        'aec-admin-products' =>
            admin_url(
                'edit.php?post_type=product'
            ),

        'aec-admin-settings' =>
            admin_url(
                'admin.php?page=wc-settings'
            ),

    ];


    if (
        !isset(
            $redirects[$page]
        )
    ) {
        return;
    }


    wp_safe_redirect(
        $redirects[$page]
    );


    exit;
}


add_action(
    'admin_init',
    'aec_admin_panel_handle_redirects',
    15
);


/**
 * =========================================================
 * URLS DEL PANEL
 * =========================================================
 */

function aec_admin_panel_urls()
{
    return [

        'home' =>
            admin_url(
                'admin.php?page=aec-admin'
            ),

        'orders' =>
            admin_url(
                'admin.php?page=wc-orders'
            ),

        'board' =>
            admin_url(
                'admin.php?page=aec-order-board'
            ),

        'transfers' =>
            admin_url(
                'admin.php?page=aec-transferencias'
            ),

        'availability' =>
            admin_url(
                'admin.php?page=antojitos-disponibilidad'
            ),

        'products' =>
            admin_url(
                'edit.php?post_type=product'
            ),

        'settings' =>
            admin_url(
                'admin.php?page=wc-settings'
            ),

        'payments' =>
            admin_url(
                'admin.php?page=wc-settings&tab=checkout'
            ),

        'site' =>
            home_url('/'),

    ];
}


/**
 * =========================================================
 * PEDIDOS ACTIVOS
 * =========================================================
 */

function aec_admin_panel_active_orders()
{
    if (
        function_exists(
            'aec_order_board_get_orders'
        )
    ) {

        $orders =
            aec_order_board_get_orders();


        return
            is_array($orders)
                ? $orders
                : [];
    }


    return [];
}


/**
 * =========================================================
 * CONTADORES DE PEDIDOS
 * =========================================================
 */

function aec_admin_panel_order_counts()
{
    $result = [

        'total' =>
            0,

        'pickup' =>
            0,

        'dine' =>
            0,

    ];


    foreach (
        aec_admin_panel_active_orders()
        as
        $order
    ) {

        if (
            !($order instanceof WC_Order)
        ) {
            continue;
        }


        $result['total']++;


        $type =
            function_exists(
                'aec_admin_order_tipo_codigo'
            )
                ? aec_admin_order_tipo_codigo(
                    $order
                )
                : '';


        if (
            $type === 'recoger'
        ) {

            $result['pickup']++;

        } elseif (
            $type === 'comer_aqui'
        ) {

            $result['dine']++;
        }
    }


    return $result;
}


/**
 * =========================================================
 * TRANSFERENCIAS PENDIENTES
 * =========================================================
 */

function aec_admin_panel_pending_transfers()
{
    if (
        !function_exists(
            'wc_get_orders'
        )
    ) {
        return [];
    }


    $orders =
        wc_get_orders(
            [

                'status' =>
                    [
                        'on-hold',
                    ],

                'limit' =>
                    -1,

                'orderby' =>
                    'date',

                'order' =>
                    'ASC',

                'return' =>
                    'objects',

            ]
        );


    $pending = [];


    foreach (
        $orders as
        $order
    ) {

        if (
            !($order instanceof WC_Order)
        ) {
            continue;
        }


        if (
            $order->get_payment_method()
            !== 'bacs'
        ) {
            continue;
        }


        if (
            function_exists(
                'aec_admin_order_tipo_codigo'
            )
            &&
            aec_admin_order_tipo_codigo(
                $order
            )
            !== 'recoger'
        ) {
            continue;
        }


        $validation =
            (string)
            $order->get_meta(
                '_aec_transfer_validation_status',
                true
            );


        if (
            $validation === 'validated'
        ) {
            continue;
        }


        $pending[] =
            $order;
    }


    return $pending;
}


/**
 * =========================================================
 * INGREDIENTES AGOTADOS
 * =========================================================
 */

function aec_admin_panel_unavailable_count()
{
    $states =
        get_option(
            'aec_disponibilidad_opciones',
            []
        );


    if (
        !is_array(
            $states
        )
    ) {
        return 0;
    }


    $count = 0;


    foreach (
        $states as
        $state
    ) {

        if (
            $state === 'agotado'
        ) {

            $count++;
        }
    }


    return $count;
}


/**
 * =========================================================
 * PRODUCTOS PUBLICADOS
 * =========================================================
 */

function aec_admin_panel_product_count()
{
    if (
        !function_exists(
            'wc_get_products'
        )
    ) {
        return 0;
    }


    $products =
        wc_get_products(
            [

                'limit' =>
                    -1,

                'status' =>
                    'publish',

                'return' =>
                    'ids',

            ]
        );


    return
        is_array($products)
            ? count($products)
            : 0;
}


/**
 * =========================================================
 * DATOS OPERATIVOS DEL CLIENTE
 * =========================================================
 */

function aec_admin_panel_customer_name(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return 'Sin nombre';
    }


    if (
        function_exists(
            'aec_customer_order_name'
        )
    ) {

        $name =
            trim(
                (string)
                aec_customer_order_name(
                    $order
                )
            );


        if (
            $name !== ''
        ) {
            return $name;
        }
    }


    $name =
        trim(
            $order->get_billing_first_name()
            .
            ' '
            .
            $order->get_billing_last_name()
        );


    return
        $name !== ''
            ? sanitize_text_field(
                $name
            )
            : 'Sin nombre';
}


function aec_admin_panel_customer_phone(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return '';
    }


    if (
        function_exists(
            'aec_customer_order_phone'
        )
    ) {

        return sanitize_text_field(
            (string)
            aec_customer_order_phone(
                $order
            )
        );
    }


    return sanitize_text_field(
        (string)
        $order->get_billing_phone()
    );
}


function aec_admin_panel_pickup_time(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return '—';
    }


    if (
        function_exists(
            'aec_admin_order_hora'
        )
    ) {

        $time =
            (string)
            aec_admin_order_hora(
                $order
            );


        return
            $time !== ''
                ? $time
                : '—';
    }


    return '—';
}


/**
 * =========================================================
 * TARJETA DE ESTADÍSTICA
 * =========================================================
 */

function aec_admin_panel_stat_card(
    $label,
    $value,
    $description,
    $class = ''
) {
    ?>

    <div
        class="
            aec-admin-stat
            <?php
            echo esc_attr(
                $class
            );
            ?>
        "
    >

        <span class="aec-admin-stat-label">

            <?php
            echo esc_html(
                $label
            );
            ?>

        </span>


        <strong class="aec-admin-stat-value">

            <?php
            echo esc_html(
                $value
            );
            ?>

        </strong>


        <span class="aec-admin-stat-description">

            <?php
            echo esc_html(
                $description
            );
            ?>

        </span>

    </div>

    <?php
}


/**
 * =========================================================
 * TARJETA DE ACCESO RÁPIDO
 * =========================================================
 */

function aec_admin_panel_action_card(
    $title,
    $description,
    $url,
    $icon,
    $button = 'Abrir'
) {
    ?>

    <a
        class="aec-admin-action"
        href="<?php
            echo esc_url(
                $url
            );
        ?>"
    >

        <div class="aec-admin-action-icon">

            <span
                class="
                    dashicons
                    <?php
                    echo esc_attr(
                        $icon
                    );
                    ?>
                "
            ></span>

        </div>


        <div class="aec-admin-action-content">

            <strong>

                <?php
                echo esc_html(
                    $title
                );
                ?>

            </strong>


            <p>

                <?php
                echo esc_html(
                    $description
                );
                ?>

            </p>


            <span class="aec-admin-action-link">

                <?php
                echo esc_html(
                    $button
                );
                ?>

                <span aria-hidden="true">
                    →
                </span>

            </span>

        </div>

    </a>

    <?php
}


/**
 * =========================================================
 * DASHBOARD PRINCIPAL
 * =========================================================
 */

function aec_admin_panel_page()
{
    if (
        !aec_admin_panel_user_can_access()
    ) {

        wp_die(
            'No tienes permisos para acceder a este panel.'
        );
    }


    $urls =
        aec_admin_panel_urls();


    $counts =
        aec_admin_panel_order_counts();


    $transfers =
        aec_admin_panel_pending_transfers();


    $transfer_count =
        count(
            $transfers
        );


    $unavailable =
        aec_admin_panel_unavailable_count();


    $products =
        aec_admin_panel_product_count();


    ?>

    <div class="wrap aec-admin-panel">


        <!-- ===============================================
             CABECERA
             =============================================== -->

        <section class="aec-admin-hero">

            <div class="aec-admin-hero-content">

                <span class="aec-admin-eyebrow">
                    Panel de operación
                </span>


                <h1>
                    Antojitos el Chino
                </h1>


                <p>
                    El antojo se sirve caliente.
                </p>

            </div>


            <div class="aec-admin-hero-actions">

                <a
                    class="aec-admin-button is-secondary"
                    href="<?php
                        echo esc_url(
                            $urls['site']
                        );
                    ?>"
                    target="_blank"
                    rel="noopener"
                >
                    Ver sitio
                </a>


                <a
                    class="aec-admin-button is-primary"
                    href="<?php
                        echo esc_url(
                            $urls['board']
                        );
                    ?>"
                >
                    Abrir pantalla de pedidos
                </a>

            </div>

        </section>


        <!-- ===============================================
             RESUMEN
             =============================================== -->

        <section class="aec-admin-section">

            <div class="aec-admin-section-heading">

                <div>

                    <span>
                        Resumen
                    </span>

                    <h2>
                        Operación actual
                    </h2>

                </div>

            </div>


            <div class="aec-admin-stats">

                <?php

                aec_admin_panel_stat_card(
                    'Pedidos activos',
                    $counts['total'],
                    'En operación',
                    'is-blue'
                );


                aec_admin_panel_stat_card(
                    'Para recoger',
                    $counts['pickup'],
                    'Pedidos activos',
                    'is-yellow'
                );


                aec_admin_panel_stat_card(
                    'Comer aquí',
                    $counts['dine'],
                    'Pedidos activos',
                    'is-green'
                );


                aec_admin_panel_stat_card(
                    'Transferencias',
                    $transfer_count,
                    'Pendientes de validar',
                    $transfer_count > 0
                        ? 'is-red'
                        : 'is-green'
                );


                aec_admin_panel_stat_card(
                    'Agotados',
                    $unavailable,
                    'Ingredientes u opciones',
                    $unavailable > 0
                        ? 'is-yellow'
                        : 'is-green'
                );


                aec_admin_panel_stat_card(
                    'Productos',
                    $products,
                    'Publicados en el menú',
                    'is-blue'
                );

                ?>

            </div>

        </section>


        <!-- ===============================================
             OPERACIÓN
             =============================================== -->

        <section class="aec-admin-section">

            <div class="aec-admin-section-heading">

                <div>

                    <span>
                        Accesos rápidos
                    </span>

                    <h2>
                        Operación
                    </h2>

                </div>

            </div>


            <div class="aec-admin-actions-grid">

                <?php

                aec_admin_panel_action_card(
                    'Pantalla de pedidos',
                    'Gestiona pedidos confirmados, preparación y entrega.',
                    $urls['board'],
                    'dashicons-screenoptions',
                    'Abrir tablero'
                );


                aec_admin_panel_action_card(
                    'Pedidos',
                    'Consulta el historial y los detalles de cada pedido.',
                    $urls['orders'],
                    'dashicons-list-view',
                    'Ver pedidos'
                );


                aec_admin_panel_action_card(
                    'Transferencias',
                    'Revisa comprobantes pendientes antes de preparar.',
                    $urls['transfers'],
                    'dashicons-money-alt',
                    'Revisar transferencias'
                );

                ?>

            </div>

        </section>


        <!-- ===============================================
             ADMINISTRACIÓN
             =============================================== -->

        <section class="aec-admin-section">

            <div class="aec-admin-section-heading">

                <div>

                    <span>
                        Gestión
                    </span>

                    <h2>
                        Administración
                    </h2>

                </div>

            </div>


            <div class="aec-admin-actions-grid">

                <?php

                aec_admin_panel_action_card(
                    'Disponibilidad',
                    'Activa o marca como agotados ingredientes y complementos.',
                    $urls['availability'],
                    'dashicons-yes-alt',
                    'Administrar'
                );


                aec_admin_panel_action_card(
                    'Menú / Productos',
                    'Administra productos, precios e información del menú.',
                    $urls['products'],
                    'dashicons-food',
                    'Administrar menú'
                );


                aec_admin_panel_action_card(
                    'Configuración',
                    'Accede a pagos y configuraciones generales.',
                    $urls['settings'],
                    'dashicons-admin-generic',
                    'Configurar'
                );

                ?>

            </div>

        </section>


        <!-- ===============================================
             TRANSFERENCIAS PENDIENTES
             =============================================== -->

        <section class="aec-admin-section">

            <div class="aec-admin-section-heading">

                <div>

                    <span>
                        Pagos
                    </span>

                    <h2>
                        Transferencias pendientes
                    </h2>

                </div>


                <?php
                if (
                    $transfer_count > 0
                ):
                ?>

                    <span class="aec-admin-section-count">

                        <?php
                        echo esc_html(
                            $transfer_count
                        );
                        ?>

                    </span>

                <?php endif; ?>

            </div>


            <?php
            if (
                $transfer_count === 0
            ):
            ?>

                <div class="aec-admin-empty">

                    <span
                        class="
                            dashicons
                            dashicons-yes-alt
                        "
                    ></span>


                    <div>

                        <strong>
                            Todo al día
                        </strong>


                        <p>
                            No hay transferencias pendientes de validar.
                        </p>

                    </div>

                </div>

            <?php else: ?>

                <div class="aec-admin-transfer-list">

                    <?php
                    foreach (
                        array_slice(
                            $transfers,
                            0,
                            5
                        )
                        as
                        $order
                    ):

                        $name =
                            aec_admin_panel_customer_name(
                                $order
                            );


                        $phone =
                            aec_admin_panel_customer_phone(
                                $order
                            );


                        $time =
                            aec_admin_panel_pickup_time(
                                $order
                            );

                    ?>

                        <article class="aec-admin-transfer">

                            <div class="aec-admin-transfer-number">

                                <span>
                                    Pedido
                                </span>

                                <strong>

                                    #<?php
                                    echo esc_html(
                                        $order
                                            ->get_order_number()
                                    );
                                    ?>

                                </strong>

                            </div>


                            <div class="aec-admin-transfer-customer">

                                <span>
                                    Cliente
                                </span>

                                <strong>
                                    <?php
                                    echo esc_html(
                                        $name
                                    );
                                    ?>
                                </strong>


                                <?php
                                if (
                                    $phone !== ''
                                ):
                                ?>

                                    <small>
                                        <?php
                                        echo esc_html(
                                            $phone
                                        );
                                        ?>
                                    </small>

                                <?php endif; ?>

                            </div>


                            <div class="aec-admin-transfer-time">

                                <span>
                                    Recoger
                                </span>

                                <strong>
                                    <?php
                                    echo esc_html(
                                        $time
                                    );
                                    ?>
                                </strong>

                            </div>


                            <div class="aec-admin-transfer-total">

                                <span>
                                    Total
                                </span>

                                <strong>

                                    <?php
                                    echo wp_kses_post(
                                        $order
                                            ->get_formatted_order_total()
                                    );
                                    ?>

                                </strong>

                            </div>


                            <div class="aec-admin-transfer-actions">

                                <?php
                                if (
                                    function_exists(
                                        'aec_transfer_download_url'
                                    )
                                ):
                                ?>

                                    <a
                                        class="aec-admin-mini-button"
                                        href="<?php
                                            echo esc_url(
                                                aec_transfer_download_url(
                                                    $order
                                                )
                                            );
                                        ?>"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        Ver comprobante
                                    </a>

                                <?php endif; ?>


                                <?php
                                if (
                                    function_exists(
                                        'aec_transfer_validate_url'
                                    )
                                ):
                                ?>

                                    <a
                                        class="
                                            aec-admin-mini-button
                                            is-primary
                                        "
                                        href="<?php
                                            echo esc_url(
                                                aec_transfer_validate_url(
                                                    $order
                                                )
                                            );
                                        ?>"
                                        onclick="
                                            return confirm(
                                                '¿Confirmas que revisaste el comprobante y el pago es correcto?'
                                            );
                                        "
                                    >
                                        Validar
                                    </a>

                                <?php endif; ?>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>


                <?php
                if (
                    $transfer_count > 5
                ):
                ?>

                    <p class="aec-admin-more">

                        <a
                            href="<?php
                                echo esc_url(
                                    $urls['transfers']
                                );
                            ?>"
                        >
                            Ver todas las transferencias
                        </a>

                    </p>

                <?php endif; ?>

            <?php endif; ?>

        </section>

    </div>

    <?php
}


/**
 * =========================================================
 * PÁGINA DE TRANSFERENCIAS
 * =========================================================
 */

function aec_admin_panel_transfer_page()
{
    if (
        !aec_admin_panel_user_can_access()
    ) {

        wp_die(
            'No tienes permisos para ver esta pantalla.'
        );
    }


    $orders =
        aec_admin_panel_pending_transfers();


    $pending_count =
        count(
            $orders
        );


    ?>

    <div
        class="
            wrap
            aec-admin-panel
            aec-transfer-page
        "
    >


        <!-- ===============================================
             CABECERA
             =============================================== -->

        <section class="aec-transfer-page-header">

            <div>

                <span class="aec-admin-eyebrow">
                    Antojitos el Chino
                </span>


                <h1>
                    Transferencias
                </h1>


                <p>
                    Revisa los comprobantes de los pedidos
                    para recoger antes de enviarlos a preparación.
                </p>

            </div>


            <a
                class="aec-transfer-back"
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


        <!-- ===============================================
             RESUMEN
             =============================================== -->

        <section class="aec-transfer-summary">

            <div
                class="
                    aec-transfer-summary-card
                    <?php
                    echo esc_attr(
                        $pending_count > 0
                            ? 'has-pending'
                            : 'is-clear'
                    );
                    ?>
                "
            >

                <div class="aec-transfer-summary-icon">

                    <span
                        class="
                            dashicons
                            <?php
                            echo esc_attr(
                                $pending_count > 0
                                    ? 'dashicons-money-alt'
                                    : 'dashicons-yes-alt'
                            );
                            ?>
                        "
                    ></span>

                </div>


                <div>

                    <span>
                        Pendientes de revisión
                    </span>


                    <strong>

                        <?php
                        echo esc_html(
                            $pending_count
                        );
                        ?>

                    </strong>


                    <small>

                        <?php

                        if (
                            $pending_count === 0
                        ) {

                            echo 'No hay pagos esperando validación.';

                        } elseif (
                            $pending_count === 1
                        ) {

                            echo 'Hay una transferencia por revisar.';

                        } else {

                            echo esc_html(
                                sprintf(
                                    'Hay %d transferencias por revisar.',
                                    $pending_count
                                )
                            );
                        }

                        ?>

                    </small>

                </div>

            </div>

        </section>


        <?php
        if (
            empty(
                $orders
            )
        ):
        ?>

            <!-- ===========================================
                 SIN TRANSFERENCIAS
                 =========================================== -->

            <section class="aec-transfer-empty">

                <div class="aec-transfer-empty-icon">

                    <span
                        class="
                            dashicons
                            dashicons-yes-alt
                        "
                    ></span>

                </div>


                <div>

                    <h2>
                        Todo al día
                    </h2>


                    <p>
                        No hay transferencias pendientes
                        de validación.
                    </p>


                    <a
                        href="<?php
                            echo esc_url(
                                admin_url(
                                    'admin.php?page=aec-order-board'
                                )
                            );
                        ?>"
                    >
                        Ir a la pantalla de pedidos →
                    </a>

                </div>

            </section>


        <?php else: ?>

            <!-- ===========================================
                 LISTA
                 =========================================== -->

            <section class="aec-transfer-pending-section">

                <div class="aec-transfer-section-heading">

                    <div>

                        <span>
                            Atención requerida
                        </span>


                        <h2>
                            Comprobantes por validar
                        </h2>

                    </div>


                    <span class="aec-transfer-counter">

                        <?php
                        echo esc_html(
                            $pending_count
                        );
                        ?>

                    </span>

                </div>


                <div class="aec-transfer-cards">

                    <?php
                    foreach (
                        $orders as
                        $order
                    ):

                        if (
                            !($order instanceof WC_Order)
                        ) {
                            continue;
                        }


                        $name =
                            aec_admin_panel_customer_name(
                                $order
                            );


                        $phone =
                            aec_admin_panel_customer_phone(
                                $order
                            );


                        $time =
                            aec_admin_panel_pickup_time(
                                $order
                            );


                        $proof_url =
                            function_exists(
                                'aec_transfer_download_url'
                            )
                                ? aec_transfer_download_url(
                                    $order
                                )
                                : '';


                        $validate_url =
                            function_exists(
                                'aec_transfer_validate_url'
                            )
                                ? aec_transfer_validate_url(
                                    $order
                                )
                                : '';


                        $edit_url = '';


                        if (
                            method_exists(
                                $order,
                                'get_edit_order_url'
                            )
                        ) {

                            $edit_url =
                                $order
                                    ->get_edit_order_url();

                        } elseif (
                            function_exists(
                                'aec_transfer_order_edit_url'
                            )
                        ) {

                            $edit_url =
                                aec_transfer_order_edit_url(
                                    $order
                                );
                        }


                        $created =
                            $order
                                ->get_date_created();


                        $created_text = '';


                        if (
                            $created
                        ) {

                            $created =
                                clone $created;


                            $created->setTimezone(
                                wp_timezone()
                            );


                            $created_text =
                                $created->format(
                                    'H:i'
                                );
                        }

                    ?>

                        <article class="aec-transfer-card">


                            <!-- ===========================
                                 ENCABEZADO
                                 =========================== -->

                            <header class="aec-transfer-card-header">

                                <div>

                                    <span class="aec-transfer-order-label">
                                        Pedido
                                    </span>


                                    <?php
                                    if (
                                        $edit_url !== ''
                                    ):
                                    ?>

                                        <a
                                            class="aec-transfer-order-number"
                                            href="<?php
                                                echo esc_url(
                                                    $edit_url
                                                );
                                            ?>"
                                        >

                                            #<?php
                                            echo esc_html(
                                                $order
                                                    ->get_order_number()
                                            );
                                            ?>

                                        </a>

                                    <?php else: ?>

                                        <strong class="aec-transfer-order-number">

                                            #<?php
                                            echo esc_html(
                                                $order
                                                    ->get_order_number()
                                            );
                                            ?>

                                        </strong>

                                    <?php endif; ?>


                                    <?php
                                    if (
                                        $created_text !== ''
                                    ):
                                    ?>

                                        <small>

                                            Recibido a las

                                            <?php
                                            echo esc_html(
                                                $created_text
                                            );
                                            ?>

                                        </small>

                                    <?php endif; ?>

                                </div>


                                <span class="aec-transfer-pending-badge">

                                    <span aria-hidden="true"></span>

                                    Pendiente

                                </span>

                            </header>


                            <!-- ===========================
                                 CLIENTE
                                 =========================== -->

                            <div class="aec-transfer-customer-block">

                                <span class="aec-transfer-field-label">
                                    Pedido a nombre de
                                </span>


                                <strong class="aec-transfer-customer-name">

                                    <?php
                                    echo esc_html(
                                        $name
                                    );
                                    ?>

                                </strong>


                                <?php
                                if (
                                    $phone !== ''
                                ):

                                    $phone_href =
                                        preg_replace(
                                            '/\D+/',
                                            '',
                                            $phone
                                        );

                                ?>

                                    <a
                                        class="aec-transfer-phone"
                                        href="tel:<?php
                                            echo esc_attr(
                                                $phone_href
                                            );
                                        ?>"
                                    >
                                        <?php
                                        echo esc_html(
                                            $phone
                                        );
                                        ?>
                                    </a>

                                <?php endif; ?>

                            </div>


                            <!-- ===========================
                                 DATOS
                                 =========================== -->

                            <div class="aec-transfer-details">

                                <div>

                                    <span>
                                        Modalidad
                                    </span>

                                    <strong>
                                        Recoger
                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Hora de recolección
                                    </span>

                                    <strong class="aec-transfer-highlight">

                                        <?php
                                        echo esc_html(
                                            $time
                                        );
                                        ?>

                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Forma de pago
                                    </span>

                                    <strong>

                                        <?php
                                        echo esc_html(
                                            $order
                                                ->get_payment_method_title()
                                        );
                                        ?>

                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Total
                                    </span>

                                    <strong class="aec-transfer-total">

                                        <?php
                                        echo wp_kses_post(
                                            $order
                                                ->get_formatted_order_total()
                                        );
                                        ?>

                                    </strong>

                                </div>

                            </div>


                            <!-- ===========================
                                 INDICACIÓN
                                 =========================== -->

                            <div class="aec-transfer-review-message">

                                <span
                                    class="
                                        dashicons
                                        dashicons-info-outline
                                    "
                                ></span>


                                <p>
                                    Compara el comprobante con
                                    el total del pedido antes de
                                    validar la transferencia.
                                </p>

                            </div>


                            <!-- ===========================
                                 ACCIONES
                                 =========================== -->

                            <footer class="aec-transfer-card-actions">


                                <?php
                                if (
                                    $proof_url !== ''
                                ):
                                ?>

                                    <a
                                        class="
                                            aec-transfer-action
                                            is-proof
                                        "
                                        href="<?php
                                            echo esc_url(
                                                $proof_url
                                            );
                                        ?>"
                                        target="_blank"
                                        rel="noopener"
                                    >

                                        <span
                                            class="
                                                dashicons
                                                dashicons-media-document
                                            "
                                        ></span>

                                        Ver comprobante

                                    </a>

                                <?php endif; ?>


                                <?php
                                if (
                                    $validate_url !== ''
                                ):
                                ?>

                                    <a
                                        class="
                                            aec-transfer-action
                                            is-validate
                                        "
                                        href="<?php
                                            echo esc_url(
                                                $validate_url
                                            );
                                        ?>"
                                        onclick="
                                            return confirm(
                                                '¿Confirmas que revisaste el comprobante y el pago es correcto?'
                                            );
                                        "
                                    >

                                        <span
                                            class="
                                                dashicons
                                                dashicons-yes-alt
                                            "
                                        ></span>

                                        Validar transferencia

                                    </a>

                                <?php endif; ?>


                            </footer>

                        </article>

                    <?php endforeach; ?>

                </div>

            </section>

        <?php endif; ?>

    </div>

    <?php
}


/**
 * =========================================================
 * ASSETS
 * =========================================================
 */

function aec_admin_panel_assets()
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
        !in_array(
            $page,
            [
                'aec-admin',
                'aec-transferencias',
            ],
            true
        )
    ) {
        return;
    }


    $css_relative =
        'assets/css/admin-dashboard.css';


    $css_path =
        AEC_PLUGIN_DIR
        .
        $css_relative;


    if (
        !file_exists(
            $css_path
        )
    ) {
        return;
    }


    wp_enqueue_style(
        'aec-admin-dashboard',
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


add_action(
    'admin_enqueue_scripts',
    'aec_admin_panel_assets',
    50
);
