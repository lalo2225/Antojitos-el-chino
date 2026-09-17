<?php

require_once '/var/www/html/wp-load.php';


/**
 * Crea o actualiza una página.
 */
function aec_crear_pagina_menu(
    $titulo,
    $slug,
    $contenido
) {
    $pagina = get_page_by_path($slug);

    $datos = [
        'post_title'   => $titulo,
        'post_name'    => $slug,
        'post_content' => $contenido,
        'post_status'  => 'publish',
        'post_type'    => 'page',
    ];

    if ($pagina) {

        $datos['ID'] = $pagina->ID;

        $resultado = wp_update_post(
            $datos,
            true
        );

        if (is_wp_error($resultado)) {
            echo "ERROR actualizando {$titulo}: ";
            echo $resultado->get_error_message();
            echo PHP_EOL;
            return;
        }

        echo "ACTUALIZADA: {$titulo}";
        echo PHP_EOL;
        return;
    }

    $resultado = wp_insert_post(
        $datos,
        true
    );

    if (is_wp_error($resultado)) {
        echo "ERROR creando {$titulo}: ";
        echo $resultado->get_error_message();
        echo PHP_EOL;
        return;
    }

    echo "CREADA: {$titulo}";
    echo PHP_EOL;
}


/**
 * =========================================================
 * COMIDA
 * =========================================================
 */

$contenido_comida = '

<!-- wp:group {"className":"aec-category-hero aec-category-food","layout":{"type":"constrained"}} -->
<div class="wp-block-group aec-category-hero aec-category-food">

    <!-- wp:paragraph {"className":"aec-category-eyebrow"} -->
    <p class="aec-category-eyebrow">
        MENÚ · COMIDA
    </p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":1} -->
    <h1 class="wp-block-heading">
        Elige tu antojito
    </h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>
        Selecciona tu favorito, personaliza cómo va preparado
        y agrega cualquier indicación especial.
    </p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons -->
    <div class="wp-block-buttons">

        <!-- wp:button {"className":"is-style-outline"} -->
        <div class="wp-block-button is-style-outline">
            <a
                class="wp-block-button__link wp-element-button"
                href="/menu/"
            >
                ← Volver al menú
            </a>
        </div>
        <!-- /wp:button -->

    </div>
    <!-- /wp:buttons -->

</div>
<!-- /wp:group -->


<!-- wp:group {"className":"aec-products-section","layout":{"type":"constrained"}} -->
<div class="wp-block-group aec-products-section">

    <!-- wp:shortcode -->
    [products category="tacos,quesadillas,pambazos,volcanes,tlacoyos,gorditas,sopes,huaraches" cat_operator="IN" columns="4" limit="-1" orderby="menu_order" order="ASC"]
    <!-- /wp:shortcode -->

</div>
<!-- /wp:group -->
';


aec_crear_pagina_menu(
    'Comida',
    'comida',
    $contenido_comida
);


/**
 * =========================================================
 * BEBIDAS
 * =========================================================
 */

$contenido_bebidas = '

<!-- wp:group {"className":"aec-category-hero aec-category-drinks","layout":{"type":"constrained"}} -->
<div class="wp-block-group aec-category-hero aec-category-drinks">

    <!-- wp:paragraph {"className":"aec-category-eyebrow"} -->
    <p class="aec-category-eyebrow">
        MENÚ · BEBIDAS
    </p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":1} -->
    <h1 class="wp-block-heading">
        Algo para acompañar
    </h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>
        Elige entre aguas frescas y refrescos
        para completar tu pedido.
    </p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons -->
    <div class="wp-block-buttons">

        <!-- wp:button {"className":"is-style-outline"} -->
        <div class="wp-block-button is-style-outline">
            <a
                class="wp-block-button__link wp-element-button"
                href="/menu/"
            >
                ← Volver al menú
            </a>
        </div>
        <!-- /wp:button -->

    </div>
    <!-- /wp:buttons -->

</div>
<!-- /wp:group -->


<!-- wp:group {"className":"aec-products-section","layout":{"type":"constrained"}} -->
<div class="wp-block-group aec-products-section">

    <!-- wp:shortcode -->
    [products category="aguas-frescas,refrescos" cat_operator="IN" columns="3" limit="-1" orderby="menu_order" order="ASC"]
    <!-- /wp:shortcode -->

</div>
<!-- /wp:group -->
';


aec_crear_pagina_menu(
    'Bebidas',
    'bebidas',
    $contenido_bebidas
);


echo PHP_EOL;
echo "PÁGINAS COMIDA Y BEBIDAS ACTUALIZADAS.";
echo PHP_EOL;
