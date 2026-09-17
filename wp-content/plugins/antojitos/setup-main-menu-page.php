<?php

require_once '/var/www/html/wp-load.php';


$contenido = '
<!-- wp:group {"className":"aec-menu-main","layout":{"type":"constrained"}} -->
<div class="wp-block-group aec-menu-main">

    <!-- wp:paragraph {"className":"aec-menu-eyebrow"} -->
    <p class="aec-menu-eyebrow">
        ANTOJITOS EL CHINO
    </p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":1} -->
    <h1 class="wp-block-heading">
        ¿Qué se te antoja?
    </h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"className":"aec-menu-description"} -->
    <p class="aec-menu-description">
        Elige una categoría para comenzar tu pedido.
    </p>
    <!-- /wp:paragraph -->


    <!-- wp:columns {"className":"aec-menu-selector-grid"} -->
    <div class="wp-block-columns aec-menu-selector-grid">


        <!-- COMIDA -->
        <!-- wp:column {"className":"aec-menu-choice aec-menu-food"} -->
        <div class="wp-block-column aec-menu-choice aec-menu-food">

            <!-- wp:paragraph {"className":"aec-menu-label"} -->
            <p class="aec-menu-label">
                NUESTROS ANTOJITOS
            </p>
            <!-- /wp:paragraph -->

            <!-- wp:heading {"level":2} -->
            <h2 class="wp-block-heading">
                Comida
            </h2>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p>
                Tacos, quesadillas, pambazos, volcanes,
                tlacoyos, gorditas, sopes y huaraches.
            </p>
            <!-- /wp:paragraph -->

            <!-- wp:buttons -->
            <div class="wp-block-buttons">

                <!-- wp:button -->
                <div class="wp-block-button">
                    <a
                        class="wp-block-button__link wp-element-button"
                        href="/comida/"
                    >
                        Ver comida
                    </a>
                </div>
                <!-- /wp:button -->

            </div>
            <!-- /wp:buttons -->

        </div>
        <!-- /wp:column -->


        <!-- BEBIDAS -->
        <!-- wp:column {"className":"aec-menu-choice aec-menu-drinks"} -->
        <div class="wp-block-column aec-menu-choice aec-menu-drinks">

            <!-- wp:paragraph {"className":"aec-menu-label"} -->
            <p class="aec-menu-label">
                PARA ACOMPAÑAR
            </p>
            <!-- /wp:paragraph -->

            <!-- wp:heading {"level":2} -->
            <h2 class="wp-block-heading">
                Bebidas
            </h2>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p>
                Aguas frescas y refrescos para acompañar tu pedido.
            </p>
            <!-- /wp:paragraph -->

            <!-- wp:buttons -->
            <div class="wp-block-buttons">

                <!-- wp:button -->
                <div class="wp-block-button">
                    <a
                        class="wp-block-button__link wp-element-button"
                        href="/bebidas/"
                    >
                        Ver bebidas
                    </a>
                </div>
                <!-- /wp:button -->

            </div>
            <!-- /wp:buttons -->

        </div>
        <!-- /wp:column -->


    </div>
    <!-- /wp:columns -->

</div>
<!-- /wp:group -->
';


$pagina = get_page_by_path('menu');

$datos = [
    'post_title'   => 'Menú',
    'post_name'    => 'menu',
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

    echo "Página Menú actualizada."
        . PHP_EOL;

} else {

    $resultado = wp_insert_post(
        $datos,
        true
    );

    echo "Página Menú creada."
        . PHP_EOL;
}


if (is_wp_error($resultado)) {

    echo "ERROR: "
        . $resultado->get_error_message()
        . PHP_EOL;

    exit(1);
}


echo "URL: "
    . home_url('/menu/')
    . PHP_EOL;
