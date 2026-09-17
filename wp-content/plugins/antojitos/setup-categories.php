<?php

require_once '/var/www/html/wp-load.php';

$categorias = [
    [
        'nombre' => 'Tacos',
        'slug' => 'tacos',
        'descripcion' => 'Tacos preparados al momento con diferentes opciones de carne.',
    ],
    [
        'nombre' => 'Quesadillas',
        'slug' => 'quesadillas',
        'descripcion' => 'Quesadillas con diferentes opciones de guisado.',
    ],
    [
        'nombre' => 'Pambazos',
        'slug' => 'pambazos',
        'descripcion' => 'Pambazos preparados al momento.',
    ],
    [
        'nombre' => 'Volcanes',
        'slug' => 'volcanes',
        'descripcion' => 'Volcanes con opción de guisado o carne.',
    ],
    [
        'nombre' => 'Tlacoyos',
        'slug' => 'tlacoyos',
        'descripcion' => 'Tlacoyos preparados al momento.',
    ],
    [
        'nombre' => 'Gorditas',
        'slug' => 'gorditas',
        'descripcion' => 'Gorditas con diferentes opciones.',
    ],
    [
        'nombre' => 'Sopes',
        'slug' => 'sopes',
        'descripcion' => 'Sopes preparados con diferentes opciones de guisado o carne.',
    ],
    [
        'nombre' => 'Huaraches',
        'slug' => 'huaraches',
        'descripcion' => 'Huaraches preparados al momento.',
    ],
    [
        'nombre' => 'Aguas Frescas',
        'slug' => 'aguas-frescas',
        'descripcion' => 'Aguas frescas para acompañar tus antojitos.',
    ],
    [
        'nombre' => 'Bebidas',
        'slug' => 'bebidas',
        'descripcion' => 'Bebidas para acompañar tu pedido.',
    ],
];

foreach ($categorias as $categoria) {

    if (term_exists($categoria['slug'], 'product_cat')) {
        echo "Ya existe: {$categoria['nombre']}\n";
        continue;
    }

    $resultado = wp_insert_term(
        $categoria['nombre'],
        'product_cat',
        [
            'slug' => $categoria['slug'],
            'description' => $categoria['descripcion'],
        ]
    );

    if (is_wp_error($resultado)) {
        echo "ERROR {$categoria['nombre']}: {$resultado->get_error_message()}\n";
    } else {
        echo "CREADA: {$categoria['nombre']}\n";
    }
}

echo "Proceso terminado.\n";
