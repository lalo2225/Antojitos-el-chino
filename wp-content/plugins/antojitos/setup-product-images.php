<?php

require_once '/var/www/html/wp-load.php';

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';


/**
 * =========================================================
 * CONFIGURACIÓN
 * =========================================================
 */

$directorio_imagenes =
    get_theme_file_path(
        '/assets/images/'
    );


$productos = [

    'AEC-TACO' => [
        'archivo' => 'taco.webp',
        'alt' =>
            'Taco preparado con tortilla de maíz azul',
    ],

    'AEC-QUESADILLA' => [
        'archivo' => 'quesadilla.webp',
        'alt' =>
            'Quesadilla preparada con maíz azul',
    ],

    'AEC-PAMBAZO' => [
        'archivo' => 'pambazo.webp',
        'alt' =>
            'Pambazo de Antojitos el Chino',
    ],

    'AEC-VOLCAN' => [
        'archivo' => 'volcan.webp',
        'alt' =>
            'Volcán preparado con tortilla de maíz azul dorada',
    ],

    'AEC-TLACOYO' => [
        'archivo' => 'tlacoyo.webp',
        'alt' =>
            'Tlacoyo preparado con masa de maíz azul',
    ],

    'AEC-GORDITA' => [
        'archivo' => 'gordita.webp',
        'alt' =>
            'Gordita preparada con masa de maíz azul',
    ],

    'AEC-SOPE' => [
        'archivo' => 'sope.webp',
        'alt' =>
            'Sope preparado con masa de maíz azul',
    ],

    'AEC-HUARACHE' => [
        'archivo' => 'huarache.webp',
        'alt' =>
            'Huarache preparado con masa de maíz azul',
    ],

    'AEC-AGUA-FRESCA' => [
        'archivo' => 'agua-fresca.webp',
        'alt' =>
            'Agua fresca de Antojitos el Chino',
    ],

    'AEC-REFRESCO' => [
        'archivo' => 'refresco.webp',
        'alt' =>
            'Refresco para acompañar el pedido',
    ],
];


/**
 * =========================================================
 * BUSCAR IMAGEN IMPORTADA PREVIAMENTE
 * =========================================================
 */

function aec_buscar_imagen_catalogo(
    $archivo
) {
    $adjuntos = get_posts([
        'post_type' =>
            'attachment',

        'post_status' =>
            'inherit',

        'posts_per_page' =>
            1,

        'meta_key' =>
            '_aec_catalog_image',

        'meta_value' =>
            $archivo,

        'fields' =>
            'ids',
    ]);


    if (empty($adjuntos)) {
        return 0;
    }


    return (int) $adjuntos[0];
}


/**
 * =========================================================
 * IMPORTAR IMAGEN A WORDPRESS
 * =========================================================
 */

function aec_importar_imagen(
    $ruta,
    $archivo,
    $alt
) {
    /*
     * Si ya la importamos anteriormente,
     * reutilizamos el mismo adjunto.
     */
    $existente =
        aec_buscar_imagen_catalogo(
            $archivo
        );


    if ($existente) {

        update_post_meta(
            $existente,
            '_wp_attachment_image_alt',
            $alt
        );

        return $existente;
    }


    if (!file_exists($ruta)) {

        echo "ERROR: No existe {$archivo}"
            . PHP_EOL;

        return 0;
    }


    $contenido =
        file_get_contents($ruta);


    if ($contenido === false) {

        echo "ERROR leyendo {$archivo}"
            . PHP_EOL;

        return 0;
    }


    /*
     * WordPress copia la imagen desde
     * nuestro tema hacia uploads.
     */
    $subida =
        wp_upload_bits(
            $archivo,
            null,
            $contenido
        );


    if (!empty($subida['error'])) {

        echo "ERROR subiendo {$archivo}: "
            . $subida['error']
            . PHP_EOL;

        return 0;
    }


    $tipo =
        wp_check_filetype(
            $subida['file']
        );


    $attachment_id =
        wp_insert_attachment(
            [
                'post_mime_type' =>
                    $tipo['type'],

                'post_title' =>
                    pathinfo(
                        $archivo,
                        PATHINFO_FILENAME
                    ),

                'post_content' =>
                    '',

                'post_status' =>
                    'inherit',
            ],
            $subida['file']
        );


    if (
        is_wp_error(
            $attachment_id
        )
    ) {

        echo "ERROR creando adjunto {$archivo}: "
            . $attachment_id
                ->get_error_message()
            . PHP_EOL;

        return 0;
    }


    /*
     * Generar tamaños de imagen
     * que utilizará WordPress/WooCommerce.
     */
    $metadata =
        wp_generate_attachment_metadata(
            $attachment_id,
            $subida['file']
        );


    wp_update_attachment_metadata(
        $attachment_id,
        $metadata
    );


    update_post_meta(
        $attachment_id,
        '_wp_attachment_image_alt',
        $alt
    );


    /*
     * Esta marca permite que el script
     * sea idempotente.
     */
    update_post_meta(
        $attachment_id,
        '_aec_catalog_image',
        $archivo
    );


    return (int) $attachment_id;
}


/**
 * =========================================================
 * ASIGNAR IMÁGENES A LOS PRODUCTOS
 * =========================================================
 */

echo PHP_EOL;
echo "=== IMÁGENES DEL CATÁLOGO ===";
echo PHP_EOL;


foreach (
    $productos
    as $sku => $config
) {

    $product_id =
        wc_get_product_id_by_sku(
            $sku
        );


    if (!$product_id) {

        echo "ERROR: Producto no encontrado: {$sku}";
        echo PHP_EOL;

        continue;
    }


    $archivo =
        $config['archivo'];


    $ruta =
        $directorio_imagenes
        . $archivo;


    echo PHP_EOL;
    echo "Procesando {$sku}...";
    echo PHP_EOL;


    $attachment_id =
        aec_importar_imagen(
            $ruta,
            $archivo,
            $config['alt']
        );


    if (!$attachment_id) {
        continue;
    }


    set_post_thumbnail(
        $product_id,
        $attachment_id
    );


    $producto =
        wc_get_product(
            $product_id
        );


    echo "OK: "
        . $producto->get_name()
        . " -> "
        . $archivo
        . PHP_EOL;
}


echo PHP_EOL;
echo "IMÁGENES ASIGNADAS.";
echo PHP_EOL;
