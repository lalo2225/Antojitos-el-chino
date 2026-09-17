<?php

require_once '/var/www/html/wp-load.php';

if (!class_exists('WooCommerce')) {
    exit("ERROR: WooCommerce no está activo.\n");
}


/**
 * Obtiene o crea una categoría.
 */
function aec_obtener_categoria($nombre, $slug, $parent = 0)
{
    $term = get_term_by('slug', $slug, 'product_cat');

    if ($term) {

        // Actualizamos el padre por si ya existía.
        wp_update_term(
            $term->term_id,
            'product_cat',
            [
                'parent' => $parent,
            ]
        );

        return (int) $term->term_id;
    }

    $resultado = wp_insert_term(
        $nombre,
        'product_cat',
        [
            'slug'   => $slug,
            'parent' => $parent,
        ]
    );

    if (is_wp_error($resultado)) {
        echo "ERROR categoría {$nombre}: "
            . $resultado->get_error_message()
            . "\n";

        return 0;
    }

    echo "CATEGORÍA CREADA: {$nombre}\n";

    return (int) $resultado['term_id'];
}


/**
 * Crea o actualiza un producto base.
 */
function aec_crear_producto_base(
    $nombre,
    $sku,
    $categoria_id,
    $precio,
    $descripcion
) {
    $producto_id = wc_get_product_id_by_sku($sku);

    if ($producto_id) {
        $producto = wc_get_product($producto_id);
        $accion = 'ACTUALIZADO';
    } else {
        $producto = new WC_Product_Simple();
        $producto->set_sku($sku);
        $accion = 'CREADO';
    }

    $producto->set_name($nombre);
    $producto->set_regular_price((string) $precio);
    $producto->set_price((string) $precio);
    $producto->set_description($descripcion);
    $producto->set_short_description($descripcion);

    $producto->set_category_ids([
        $categoria_id,
    ]);

    $producto->set_status('publish');
    $producto->set_catalog_visibility('visible');
    $producto->set_manage_stock(false);
    $producto->set_stock_status('instock');

    $producto_id = $producto->save();

    update_post_meta(
        $producto_id,
        '_aec_configurable',
        'yes'
    );

    echo "{$accion}: {$nombre} - \${$precio}\n";
}


/**
 * 1. Categorías principales.
 */

$comida_id = aec_obtener_categoria(
    'Comida',
    'comida'
);

$bebidas_id = aec_obtener_categoria(
    'Bebidas',
    'bebidas'
);


/**
 * 2. Categorías secundarias.
 */

$categorias_comida = [
    'tacos'       => 'Tacos',
    'quesadillas' => 'Quesadillas',
    'pambazos'    => 'Pambazos',
    'volcanes'    => 'Volcanes',
    'tlacoyos'    => 'Tlacoyos',
    'gorditas'    => 'Gorditas',
    'sopes'       => 'Sopes',
    'huaraches'   => 'Huaraches',
];

$categorias_bebidas = [
    'aguas-frescas' => 'Aguas Frescas',
    'refrescos'     => 'Refrescos',
];

$ids = [];

foreach ($categorias_comida as $slug => $nombre) {

    $ids[$slug] = aec_obtener_categoria(
        $nombre,
        $slug,
        $comida_id
    );
}

foreach ($categorias_bebidas as $slug => $nombre) {

    $ids[$slug] = aec_obtener_categoria(
        $nombre,
        $slug,
        $bebidas_id
    );
}


/**
 * 3. Mandar productos anteriores a la papelera.
 *
 * Únicamente afecta los SKU que nosotros generamos
 * en la primera versión del catálogo.
 */

$prefijos_antiguos = [
    'TAC-',
    'QUE-',
    'PAM-',
    'VOL-',
    'TLA-',
    'GOR-',
    'SOP-',
    'HUA-',
    'AGU-',
    'BEB-',
];

$productos_antiguos = wc_get_products([
    'limit'  => -1,
    'status' => [
        'publish',
        'draft',
        'private',
    ],
]);

foreach ($productos_antiguos as $producto) {

    $sku = $producto->get_sku();

    if (!$sku) {
        continue;
    }

    foreach ($prefijos_antiguos as $prefijo) {

        if (str_starts_with($sku, $prefijo)) {

            wp_trash_post(
                $producto->get_id()
            );

            echo "A PAPELERA: "
                . $producto->get_name()
                . "\n";

            break;
        }
    }
}


/**
 * 4. Crear los nuevos productos base.
 */

aec_crear_producto_base(
    'Taco',
    'AEC-TACO',
    $ids['tacos'],
    30,
    'Preparado normalmente con papa, nopal y cebolla. Elige tu carne, combina si quieres y personaliza la preparación.'
);

aec_crear_producto_base(
    'Quesadilla',
    'AEC-QUESADILLA',
    $ids['quesadillas'],
    30,
    'Preparada normalmente con lechuga, queso y crema. Elige tu guisado, combina si quieres y personaliza la preparación.'
);

aec_crear_producto_base(
    'Pambazo',
    'AEC-PAMBAZO',
    $ids['pambazos'],
    40,
    'Preparado normalmente con lechuga, queso y crema. Elige tu relleno y personaliza la preparación.'
);

aec_crear_producto_base(
    'Volcán',
    'AEC-VOLCAN',
    $ids['volcanes'],
    40,
    'Preparado normalmente con nopal, quelite, lechuga, queso, crema y papas. Elige carne o guisado y personalízalo.'
);

aec_crear_producto_base(
    'Tlacoyo',
    'AEC-TLACOYO',
    $ids['tlacoyos'],
    30,
    'Elige frijol, haba o requesón. Acompáñalo con nopal o quelite y decide si lo quieres con queso.'
);

aec_crear_producto_base(
    'Gordita',
    'AEC-GORDITA',
    $ids['gorditas'],
    30,
    'Elige chicharrón o pollo. Acompáñala con nopal o quelite y decide si la quieres con queso.'
);

aec_crear_producto_base(
    'Sope',
    'AEC-SOPE',
    $ids['sopes'],
    40,
    'Preparado normalmente con nopal, quelite, lechuga, queso, crema y papas. Elige carne o guisado y personalízalo.'
);

aec_crear_producto_base(
    'Huarache',
    'AEC-HUARACHE',
    $ids['huaraches'],
    130,
    'Preparado normalmente con nopal, quelite, lechuga, queso, crema y papas. Elige carne o guisado y personalízalo.'
);

aec_crear_producto_base(
    'Agua fresca',
    'AEC-AGUA-FRESCA',
    $ids['aguas-frescas'],
    30,
    'Elige entre horchata, jamaica o tamarindo y selecciona tamaño de medio litro o un litro.'
);

aec_crear_producto_base(
    'Refresco',
    'AEC-REFRESCO',
    $ids['refrescos'],
    30,
    'Elige tu bebida favorita para acompañar tus antojitos.'
);

echo "\nMIGRACIÓN TERMINADA.\n";
