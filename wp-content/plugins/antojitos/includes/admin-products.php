<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * =========================================================
 * ANTOJITOS EL CHINO
 * ADMINISTRACIÓN OPERATIVA DE PRODUCTOS
 * =========================================================
 *
 * Crea una vista simplificada del menú.
 *
 * WooCommerce continúa siendo la fuente real
 * de productos, precios, imágenes y categorías.
 */


/**
 * =========================================================
 * PERMISOS
 * =========================================================
 */

function aec_admin_products_can_access()
{
    return current_user_can(
        'manage_woocommerce'
    );
}


/**
 * =========================================================
 * URL
 * =========================================================
 */

function aec_admin_products_url()
{
    return admin_url(
        'admin.php?page=aec-menu-products'
    );
}


/**
 * =========================================================
 * REGISTRAR PÁGINA INTERNA
 * =========================================================
 *
 * No agregamos otro elemento al menú porque ya existe
 * "Menú / Productos" dentro de Antojitos el Chino.
 */

function aec_admin_products_register_page()
{
    add_submenu_page(
        null,
        'Menú / Productos',
        'Menú / Productos',
        'manage_woocommerce',
        'aec-menu-products',
        'aec_admin_products_page'
    );
}


add_action(
    'admin_menu',
    'aec_admin_products_register_page',
    60
);


/**
 * =========================================================
 * REDIRECCIÓN DESDE PRODUCTOS DE WOOCOMMERCE
 * =========================================================
 *
 * En modo operativo:
 *
 * Productos de WooCommerce
 *        ↓
 * Pantalla propia de Antojitos
 *
 * En modo técnico no hacemos nada.
 */

function aec_admin_products_redirect_native_list()
{
    if (
        !is_admin()
        ||
        wp_doing_ajax()
        ||
        !aec_admin_products_can_access()
    ) {
        return;
    }


    if (
        function_exists(
            'aec_admin_experience_is_operation_mode'
        )
        &&
        !aec_admin_experience_is_operation_mode()
    ) {
        return;
    }


    global $pagenow;


    if (
        $pagenow !== 'edit.php'
    ) {
        return;
    }


    $post_type =
        isset($_GET['post_type'])
            ? sanitize_key(
                wp_unslash(
                    $_GET['post_type']
                )
            )
            : '';


    if (
        $post_type !== 'product'
    ) {
        return;
    }


    wp_safe_redirect(
        aec_admin_products_url()
    );


    exit;
}


add_action(
    'admin_init',
    'aec_admin_products_redirect_native_list',
    30
);


/**
 * =========================================================
 * OBTENER PRODUCTOS
 * =========================================================
 */

function aec_admin_products_get_products()
{
    if (
        !function_exists(
            'wc_get_products'
        )
    ) {
        return [];
    }


    $products =
        wc_get_products(
            [
                'limit' =>
                    -1,

                'status' =>
                    [
                        'publish',
                        'draft',
                        'pending',
                        'private',
                    ],

                'orderby' =>
                    'menu_order',

                'order' =>
                    'ASC',

                'return' =>
                    'objects',
            ]
        );


    return
        is_array($products)
            ? $products
            : [];
}


/**
 * =========================================================
 * TIPO: COMIDA / BEBIDA
 * =========================================================
 */

function aec_admin_products_type(
    $product_id
) {
    $terms =
        wp_get_post_terms(
            $product_id,
            'product_cat',
            [
                'fields' =>
                    'ids',
            ]
        );


    if (
        is_wp_error($terms)
        ||
        empty($terms)
    ) {
        return 'producto';
    }


    $all_ids = [];


    foreach (
        $terms as
        $term_id
    ) {

        $all_ids[] =
            (int) $term_id;


        $ancestors =
            get_ancestors(
                $term_id,
                'product_cat',
                'taxonomy'
            );


        foreach (
            $ancestors as
            $ancestor_id
        ) {

            $all_ids[] =
                (int) $ancestor_id;
        }
    }


    $all_ids =
        array_unique(
            $all_ids
        );


    foreach (
        $all_ids as
        $term_id
    ) {

        $term =
            get_term(
                $term_id,
                'product_cat'
            );


        if (
            !$term
            ||
            is_wp_error($term)
        ) {
            continue;
        }


        $slug =
            sanitize_key(
                $term->slug
            );


        if (
            in_array(
                $slug,
                [
                    'comida',
                    'alimentos',
                    'alimento',
                ],
                true
            )
        ) {
            return 'comida';
        }


        if (
            in_array(
                $slug,
                [
                    'bebidas',
                    'bebida',
                ],
                true
            )
        ) {
            return 'bebida';
        }
    }


    return 'producto';
}


/**
 * =========================================================
 * NOMBRE DEL TIPO
 * =========================================================
 */

function aec_admin_products_type_name(
    $type
) {
    if (
        $type === 'comida'
    ) {
        return 'Comida';
    }


    if (
        $type === 'bebida'
    ) {
        return 'Bebida';
    }


    return 'Producto';
}


/**
 * =========================================================
 * CATEGORÍAS
 * =========================================================
 */

function aec_admin_products_categories(
    $product_id
) {
    $terms =
        wp_get_post_terms(
            $product_id,
            'product_cat',
            [
                'fields' =>
                    'names',
            ]
        );


    if (
        is_wp_error($terms)
        ||
        empty($terms)
    ) {
        return 'Sin categoría';
    }


    return implode(
        ' · ',
        $terms
    );
}


/**
 * =========================================================
 * ESTADO
 * =========================================================
 */

function aec_admin_products_status_name(
    $status
) {
    $labels = [

        'publish' =>
            'Publicado',

        'draft' =>
            'Borrador',

        'pending' =>
            'Pendiente',

        'private' =>
            'Privado',

    ];


    return
        $labels[$status]
        ??
        ucfirst(
            (string) $status
        );
}


/**
 * =========================================================
 * CONTADORES
 * =========================================================
 */

function aec_admin_products_counts(
    $products
) {
    $counts = [

        'total' =>
            0,

        'published' =>
            0,

        'food' =>
            0,

        'drinks' =>
            0,

    ];


    foreach (
        $products as
        $product
    ) {

        if (
            !($product instanceof WC_Product)
        ) {
            continue;
        }


        $counts['total']++;


        if (
            $product->get_status()
            === 'publish'
        ) {

            $counts['published']++;
        }


        $type =
            aec_admin_products_type(
                $product->get_id()
            );


        if (
            $type === 'comida'
        ) {

            $counts['food']++;

        } elseif (
            $type === 'bebida'
        ) {

            $counts['drinks']++;
        }
    }


    return $counts;
}


/**
 * =========================================================
 * RENDER PÁGINA
 * =========================================================
 */

function aec_admin_products_page()
{
    if (
        !aec_admin_products_can_access()
    ) {

        wp_die(
            'No tienes permisos para acceder a esta pantalla.'
        );
    }


    $products =
        aec_admin_products_get_products();


    $counts =
        aec_admin_products_counts(
            $products
        );


    ?>

    <div class="wrap aec-products-admin">


        <!-- ===============================================
             CABECERA
             =============================================== -->

        <section class="aec-products-header">

            <div>

                <span class="aec-products-eyebrow">
                    Antojitos el Chino
                </span>


                <h1>
                    Menú / Productos
                </h1>


                <p>
                    Consulta y administra los productos
                    disponibles en el menú.
                </p>

            </div>


            <div class="aec-products-header-actions">

                <a
                    class="
                        aec-products-button
                        is-secondary
                    "
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


                <a
                    class="
                        aec-products-button
                        is-primary
                    "
                    href="<?php
                        echo esc_url(
                            home_url(
                                '/menu/'
                            )
                        );
                    ?>"
                    target="_blank"
                    rel="noopener"
                >
                    Ver menú
                </a>

            </div>

        </section>


        <!-- ===============================================
             RESUMEN
             =============================================== -->

        <section class="aec-products-summary">

            <div class="aec-products-stat">

                <span>
                    Total
                </span>

                <strong>
                    <?php
                    echo esc_html(
                        $counts['total']
                    );
                    ?>
                </strong>

                <small>
                    Productos registrados
                </small>

            </div>


            <div
                class="
                    aec-products-stat
                    is-published
                "
            >

                <span>
                    Publicados
                </span>

                <strong>
                    <?php
                    echo esc_html(
                        $counts['published']
                    );
                    ?>
                </strong>

                <small>
                    Visibles en el sitio
                </small>

            </div>


            <div
                class="
                    aec-products-stat
                    is-food
                "
            >

                <span>
                    Comida
                </span>

                <strong>
                    <?php
                    echo esc_html(
                        $counts['food']
                    );
                    ?>
                </strong>

                <small>
                    Alimentos
                </small>

            </div>


            <div
                class="
                    aec-products-stat
                    is-drinks
                "
            >

                <span>
                    Bebidas
                </span>

                <strong>
                    <?php
                    echo esc_html(
                        $counts['drinks']
                    );
                    ?>
                </strong>

                <small>
                    Bebidas del menú
                </small>

            </div>

        </section>


        <!-- ===============================================
             HERRAMIENTAS
             =============================================== -->

        <section class="aec-products-toolbar">

            <div class="aec-products-search">

                <span
                    class="
                        dashicons
                        dashicons-search
                    "
                ></span>


                <input
                    type="search"
                    id="aec-products-search"
                    placeholder="Buscar producto..."
                    autocomplete="off"
                >

            </div>


            <div
                class="aec-products-filters"
                role="group"
                aria-label="Filtrar productos"
            >

                <button
                    type="button"
                    class="
                        aec-products-filter
                        is-active
                    "
                    data-product-filter="all"
                >
                    Todos
                </button>


                <button
                    type="button"
                    class="aec-products-filter"
                    data-product-filter="comida"
                >
                    Comida
                </button>


                <button
                    type="button"
                    class="aec-products-filter"
                    data-product-filter="bebida"
                >
                    Bebidas
                </button>

            </div>

        </section>


        <!-- ===============================================
             PRODUCTOS
             =============================================== -->

        <?php
        if (
            empty($products)
        ):
        ?>

            <section class="aec-products-empty">

                <span
                    class="
                        dashicons
                        dashicons-food
                    "
                ></span>


                <h2>
                    No hay productos registrados
                </h2>


                <p>
                    Todavía no existen productos
                    disponibles para mostrar.
                </p>

            </section>

        <?php else: ?>

            <section
                id="aec-products-list"
                class="aec-products-list"
            >

                <?php
                foreach (
                    $products as
                    $product
                ):

                    if (
                        !($product instanceof WC_Product)
                    ) {
                        continue;
                    }


                    $product_id =
                        $product->get_id();


                    $name =
                        $product->get_name();


                    $sku =
                        $product->get_sku();


                    $status =
                        $product->get_status();


                    $status_name =
                        aec_admin_products_status_name(
                            $status
                        );


                    $type =
                        aec_admin_products_type(
                            $product_id
                        );


                    $type_name =
                        aec_admin_products_type_name(
                            $type
                        );


                    $categories =
                        aec_admin_products_categories(
                            $product_id
                        );


                    $price =
                        $product->get_price_html();


                    $edit_url =
                        get_edit_post_link(
                            $product_id,
                            'raw'
                        );


                    $view_url =
                        get_permalink(
                            $product_id
                        );


                    $search_value =
                        strtolower(
                            remove_accents(
                                $name
                                .
                                ' '
                                .
                                $sku
                                .
                                ' '
                                .
                                $categories
                            )
                        );

                ?>

                    <article
                        class="aec-product-row"
                        data-product-row
                        data-product-type="<?php
                            echo esc_attr(
                                $type
                            );
                        ?>"
                        data-product-search="<?php
                            echo esc_attr(
                                $search_value
                            );
                        ?>"
                    >


                        <!-- IMAGEN -->

                        <div class="aec-product-image">

                            <?php
                            echo wp_kses_post(
                                $product->get_image(
                                    'woocommerce_thumbnail',
                                    [
                                        'class' =>
                                            'aec-product-image-file',

                                        'loading' =>
                                            'lazy',
                                    ]
                                )
                            );
                            ?>

                        </div>


                        <!-- INFORMACIÓN -->

                        <div class="aec-product-main">

                            <div class="aec-product-name-row">

                                <strong>

                                    <?php
                                    echo esc_html(
                                        $name
                                    );
                                    ?>

                                </strong>


                                <span
                                    class="
                                        aec-product-status
                                        status-<?php
                                        echo esc_attr(
                                            sanitize_html_class(
                                                $status
                                            )
                                        );
                                        ?>
                                    "
                                >

                                    <?php
                                    echo esc_html(
                                        $status_name
                                    );
                                    ?>

                                </span>

                            </div>


                            <span class="aec-product-categories">

                                <?php
                                echo esc_html(
                                    $categories
                                );
                                ?>

                            </span>


                            <?php
                            if (
                                $sku !== ''
                            ):
                            ?>

                                <small>

                                    SKU:
                                    <?php
                                    echo esc_html(
                                        $sku
                                    );
                                    ?>

                                </small>

                            <?php endif; ?>

                        </div>


                        <!-- TIPO -->

                        <div class="aec-product-type">

                            <span>
                                Tipo
                            </span>

                            <strong>

                                <?php
                                echo esc_html(
                                    $type_name
                                );
                                ?>

                            </strong>

                        </div>


                        <!-- PRECIO -->

                        <div class="aec-product-price">

                            <span>
                                Precio
                            </span>


                            <?php
                            if (
                                $price !== ''
                            ):
                            ?>

                                <strong>

                                    <?php
                                    echo wp_kses_post(
                                        $price
                                    );
                                    ?>

                                </strong>

                            <?php else: ?>

                                <strong class="has-no-price">
                                    Sin precio
                                </strong>

                            <?php endif; ?>

                        </div>


                        <!-- ACCIONES -->

                        <div class="aec-product-actions">


                            <?php
                            if (
                                $view_url
                            ):
                            ?>

                                <a
                                    class="
                                        aec-product-action
                                        is-view
                                    "
                                    href="<?php
                                        echo esc_url(
                                            $view_url
                                        );
                                    ?>"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    Ver
                                </a>

                            <?php endif; ?>


                            <?php
                            if (
                                $edit_url
                            ):
                            ?>

                                <a
                                    class="
                                        aec-product-action
                                        is-edit
                                    "
                                    href="<?php
                                        echo esc_url(
                                            $edit_url
                                        );
                                    ?>"
                                >
                                    Editar producto
                                </a>

                            <?php endif; ?>


                        </div>

                    </article>

                <?php endforeach; ?>

            </section>


            <section
                id="aec-products-no-results"
                class="aec-products-empty"
                hidden
            >

                <span
                    class="
                        dashicons
                        dashicons-search
                    "
                ></span>


                <h2>
                    No encontramos productos
                </h2>


                <p>
                    Prueba con otro nombre o cambia el filtro.
                </p>

            </section>

        <?php endif; ?>

    </div>

    <?php
}


/**
 * =========================================================
 * MANTENER MARCADO EL MENÚ DE ANTOJITOS
 * =========================================================
 */

function aec_admin_products_parent_file(
    $parent_file
) {
    $page =
        isset($_GET['page'])
            ? sanitize_key(
                wp_unslash(
                    $_GET['page']
                )
            )
            : '';


    if (
        $page === 'aec-menu-products'
    ) {
        return 'aec-admin';
    }


    return $parent_file;
}


add_filter(
    'parent_file',
    'aec_admin_products_parent_file'
);


/**
 * =========================================================
 * ASSETS
 * =========================================================
 */

function aec_admin_products_assets()
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
        $page !== 'aec-menu-products'
    ) {
        return;
    }


    $css_relative =
        'assets/css/admin-products.css';


    $js_relative =
        'assets/js/admin-products.js';


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
            'aec-admin-products',
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
            'aec-admin-products',
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
    'aec_admin_products_assets',
    50
);


/**
 * =========================================================
 * LIMPIAR AVISOS GENÉRICOS EN ESTA PÁGINA
 * =========================================================
 */

function aec_admin_products_clean_notices()
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
        $page !== 'aec-menu-products'
    ) {
        return;
    }


    ?>

    <style>

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
        > .update-nag {
            display: none !important;
        }

    </style>

    <?php
}


add_action(
    'admin_head',
    'aec_admin_products_clean_notices',
    100
);
