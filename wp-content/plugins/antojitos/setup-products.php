<?php

require_once '/var/www/html/wp-load.php';

if (!class_exists('WooCommerce')) {
    exit("ERROR: WooCommerce no está activo.\n");
}

function crear_producto($nombre, $sku, $categoria_slug, $precio)
{
    $categoria = get_term_by('slug', $categoria_slug, 'product_cat');

    if (!$categoria) {
        echo "ERROR: No existe la categoría {$categoria_slug}\n";
        return;
    }

    $producto_id = wc_get_product_id_by_sku($sku);

    if ($producto_id) {
        $producto = new WC_Product_Simple($producto_id);
        $accion = 'ACTUALIZADO';
    } else {
        $producto = new WC_Product_Simple();
        $producto->set_sku($sku);
        $accion = 'CREADO';
    }

    $producto->set_name($nombre);
    $producto->set_regular_price((string) $precio);
    $producto->set_price((string) $precio);
    $producto->set_category_ids([$categoria->term_id]);

    $producto->set_status('publish');
    $producto->set_catalog_visibility('visible');
    $producto->set_manage_stock(false);
    $producto->set_stock_status('instock');

    $producto->save();

    echo "{$accion}: {$nombre} - \${$precio}\n";
}

$productos = [

    // TACOS - $30
    ['Taco de arrachera', 'TAC-ARR', 'tacos', 30],
    ['Taco de bistec', 'TAC-BIS', 'tacos', 30],
    ['Taco de aguja norteña', 'TAC-AGU', 'tacos', 30],
    ['Taco de cecina natural', 'TAC-CEC-NAT', 'tacos', 30],
    ['Taco de cecina adobada', 'TAC-CEC-ADO', 'tacos', 30],
    ['Taco de longaniza', 'TAC-LON', 'tacos', 30],
    ['Taco de longaniza verde', 'TAC-LON-VER', 'tacos', 30],
    ['Taco de pollo', 'TAC-POL', 'tacos', 30],
    ['Taco de pechuga empanizada', 'TAC-PEC', 'tacos', 30],
    ['Taco campechano', 'TAC-CAM', 'tacos', 30],

    // QUESADILLAS - $30
    ['Quesadilla de queso', 'QUE-QUE', 'quesadillas', 30],
    ['Quesadilla de pollo', 'QUE-POL', 'quesadillas', 30],
    ['Quesadilla de papa', 'QUE-PAP', 'quesadillas', 30],
    ['Quesadilla de carne', 'QUE-CAR', 'quesadillas', 30],
    ['Quesadilla de chicharrón', 'QUE-CHI', 'quesadillas', 30],
    ['Quesadilla de picadillo', 'QUE-PIC', 'quesadillas', 30],
    ['Quesadilla de huitlacoche', 'QUE-HUI', 'quesadillas', 30],
    ['Quesadilla de champiñones', 'QUE-CHA', 'quesadillas', 30],
    ['Quesadilla de setas', 'QUE-SET', 'quesadillas', 30],
    ['Quesadilla de sesos', 'QUE-SES', 'quesadillas', 30],
    ['Quesadilla de pancita', 'QUE-PAN', 'quesadillas', 30],
    ['Quesadilla de flor de calabaza', 'QUE-FLO', 'quesadillas', 30],

    // PAMBAZOS - $40
    ['Pambazo de papa con chorizo', 'PAM-PAP', 'pambazos', 40],
    ['Pambazo de pollo', 'PAM-POL', 'pambazos', 40],
    ['Pambazo de carne', 'PAM-CAR', 'pambazos', 40],

    // VOLCANES - $40
    ['Volcán con guisado', 'VOL-GUI', 'volcanes', 40],
    ['Volcán con carne', 'VOL-CAR', 'volcanes', 40],

    // TLACOYOS - $30
    ['Tlacoyo de frijol', 'TLA-FRI', 'tlacoyos', 30],
    ['Tlacoyo de haba', 'TLA-HAB', 'tlacoyos', 30],
    ['Tlacoyo de requesón', 'TLA-REQ', 'tlacoyos', 30],

    // GORDITAS - $30
    ['Gordita de chicharrón', 'GOR-CHI', 'gorditas', 30],
    ['Gordita de pollo', 'GOR-POL', 'gorditas', 30],

    // SOPES - $40
    ['Sope con guisado', 'SOP-GUI', 'sopes', 40],
    ['Sope con carne', 'SOP-CAR', 'sopes', 40],

    // HUARACHES - $130
    ['Huarache con guisado', 'HUA-GUI', 'huaraches', 130],
    ['Huarache con carne', 'HUA-CAR', 'huaraches', 130],

    // AGUAS FRESCAS
    ['Agua de horchata 1/2 L', 'AGU-HOR-05', 'aguas-frescas', 30],
    ['Agua de horchata 1 L', 'AGU-HOR-1', 'aguas-frescas', 40],

    ['Agua de jamaica 1/2 L', 'AGU-JAM-05', 'aguas-frescas', 30],
    ['Agua de jamaica 1 L', 'AGU-JAM-1', 'aguas-frescas', 40],

    ['Agua de tamarindo 1/2 L', 'AGU-TAM-05', 'aguas-frescas', 30],
    ['Agua de tamarindo 1 L', 'AGU-TAM-1', 'aguas-frescas', 40],

    // REFRESCOS - $30
    ['Boing Mango', 'BEB-BOI-MAN', 'bebidas', 30],
    ['Boing Guayaba', 'BEB-BOI-GUA', 'bebidas', 30],
    ['Coca-Cola', 'BEB-COC', 'bebidas', 30],
    ['Coca-Cola Sin Azúcar', 'BEB-COC-ZER', 'bebidas', 30],
    ['Sidral Mundet', 'BEB-SID', 'bebidas', 30],
];

foreach ($productos as $producto) {
    crear_producto(
        $producto[0],
        $producto[1],
        $producto[2],
        $producto[3]
    );
}

echo "\nProceso terminado.\n";
