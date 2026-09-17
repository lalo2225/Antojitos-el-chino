<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * =========================================================
 * ANTOJITOS EL CHINO
 * VALIDACIÓN DE TRANSFERENCIAS
 * =========================================================
 *
 * RECOGER + TRANSFERENCIA:
 *
 * 1. Cliente ve datos bancarios.
 * 2. Debe subir comprobante.
 * 3. Pedido queda "En espera".
 * 4. Administrador valida comprobante.
 * 5. Pedido cambia a "Confirmado".
 * 6. Entra a Pantalla de pedidos.
 *
 *
 * COMER AQUÍ + TRANSFERENCIA:
 *
 * 1. No requiere comprobante previo.
 * 2. Pedido entra directamente a "Confirmado".
 */


/**
 * =========================================================
 * CONSTANTES
 * =========================================================
 */

define(
    'AEC_TRANSFER_MAX_FILE_SIZE',
    5 * 1024 * 1024
);


/**
 * =========================================================
 * DATOS BANCARIOS DE WOOCOMMERCE
 * =========================================================
 */

function aec_transfer_get_bank_details()
{
    $accounts =
        get_option(
            'woocommerce_bacs_accounts',
            []
        );


    $settings =
        get_option(
            'woocommerce_bacs_settings',
            []
        );


    $result = [

        'account_name' =>
            '',

        'bank_name' =>
            '',

        'account_number' =>
            '',

        'instructions' =>
            '',

    ];


    if (
        is_array($accounts) &&
        !empty($accounts)
    ) {

        foreach (
            $accounts as
            $account
        ) {

            if (
                !is_array($account)
            ) {
                continue;
            }


            $account_number =
                isset(
                    $account['account_number']
                )
                    ? sanitize_text_field(
                        $account['account_number']
                    )
                    : '';


            /**
             * Utilizamos la primera cuenta
             * que realmente tenga número.
             */

            if (
                $account_number === ''
            ) {
                continue;
            }


            $result['account_name'] =
                isset(
                    $account['account_name']
                )
                    ? sanitize_text_field(
                        $account['account_name']
                    )
                    : '';


            $result['bank_name'] =
                isset(
                    $account['bank_name']
                )
                    ? sanitize_text_field(
                        $account['bank_name']
                    )
                    : '';


            $result['account_number'] =
                $account_number;


            break;
        }
    }


    if (
        is_array($settings) &&
        !empty(
            $settings['instructions']
        )
    ) {

        $result['instructions'] =
            sanitize_textarea_field(
                $settings['instructions']
            );
    }


    return $result;
}


/**
 * =========================================================
 * ¿HAY DATOS BANCARIOS?
 * =========================================================
 */

function aec_transfer_bank_is_ready()
{
    $details =
        aec_transfer_get_bank_details();


    return
        !empty(
            $details['account_number']
        );
}


/**
 * =========================================================
 * OBTENER MODALIDAD DEL PEDIDO
 * =========================================================
 */

function aec_transfer_order_type(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return '';
    }


    /**
     * Reutilizar helper existente.
     */

    if (
        function_exists(
            'aec_admin_order_tipo_codigo'
        )
    ) {

        return
            aec_admin_order_tipo_codigo(
                $order
            );
    }


    /**
     * Fallback Checkout Fields.
     */

    try {

        if (
            class_exists(
                '\Automattic\WooCommerce\Blocks\Package'
            ) &&
            class_exists(
                '\Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields'
            )
        ) {

            $checkout_fields =
                \Automattic\WooCommerce\Blocks\Package
                    ::container()
                    ->get(
                        \Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class
                    );


            $value =
                $checkout_fields
                    ->get_field_from_object(
                        'antojitos-el-chino/tipo-pedido',
                        $order,
                        'other'
                    );


            if (
                is_scalar($value)
            ) {

                return sanitize_text_field(
                    (string) $value
                );
            }
        }

    } catch (Throwable $e) {

        /**
         * Continuar con fallback.
         */
    }


    return sanitize_text_field(
        (string)
        $order->get_meta(
            '_wc_other/antojitos-el-chino/tipo-pedido',
            true
        )
    );
}


/**
 * =========================================================
 * DIRECTORIO PRIVADO
 * =========================================================
 */

function aec_transfer_private_base_dir()
{
    $uploads =
        wp_upload_dir();


    return trailingslashit(
        $uploads['basedir']
    )
    .
    'aec-private-comprobantes';
}


/**
 * =========================================================
 * PREPARAR DIRECTORIO PRIVADO
 * =========================================================
 */

function aec_transfer_prepare_private_directory(
    $directory
) {
    if (
        !is_dir($directory)
    ) {

        wp_mkdir_p(
            $directory
        );
    }


    $base =
        aec_transfer_private_base_dir();


    if (
        !is_dir($base)
    ) {

        wp_mkdir_p(
            $base
        );
    }


    /**
     * Apache 2.4 + compatibilidad anterior.
     */

    $htaccess =
        trailingslashit($base)
        .
        '.htaccess';


    if (
        !file_exists($htaccess)
    ) {

        file_put_contents(
            $htaccess,
            "Options -Indexes\n"
            . "Require all denied\n"
            . "Deny from all\n"
        );
    }


    $index =
        trailingslashit($base)
        .
        'index.php';


    if (
        !file_exists($index)
    ) {

        file_put_contents(
            $index,
            "<?php\n"
            . "http_response_code(403);\n"
            . "exit;\n"
        );
    }
}


/**
 * =========================================================
 * CONVERTIR RUTA RELATIVA A ARCHIVO SEGURO
 * =========================================================
 */

function aec_transfer_resolve_file(
    $relative_path
) {
    if (
        !is_string($relative_path) ||
        $relative_path === ''
    ) {
        return false;
    }


    $uploads =
        wp_upload_dir();


    $private_base =
        realpath(
            aec_transfer_private_base_dir()
        );


    if (
        !$private_base
    ) {
        return false;
    }


    $candidate =
        trailingslashit(
            $uploads['basedir']
        )
        .
        ltrim(
            $relative_path,
            '/\\'
        );


    $real =
        realpath(
            $candidate
        );


    if (
        !$real
    ) {
        return false;
    }


    $private_base =
        wp_normalize_path(
            $private_base
        );


    $real =
        wp_normalize_path(
            $real
        );


    if (
        strpos(
            $real,
            trailingslashit(
                $private_base
            )
        ) !== 0
    ) {

        return false;
    }


    return $real;
}


/**
 * =========================================================
 * OBTENER COMPROBANTE DE LA SESIÓN
 * =========================================================
 */

function aec_transfer_get_session_proof()
{
    if (
        !function_exists('WC') ||
        !WC()->session
    ) {
        return false;
    }


    $proof =
        WC()->session->get(
            'aec_transfer_proof'
        );


    if (
        !is_array($proof)
    ) {
        return false;
    }


    $required = [

        'relative_path',

        'original_name',

        'mime',

        'size',

        'cart_hash',

        'uploaded_at',

    ];


    foreach (
        $required as
        $key
    ) {

        if (
            !isset(
                $proof[$key]
            )
        ) {

            return false;
        }
    }


    /**
     * Máximo 4 horas desde la carga.
     */

    if (
        (time() - (int) $proof['uploaded_at'])
        >
        (4 * HOUR_IN_SECONDS)
    ) {

        return false;
    }


    /**
     * Debe corresponder al carrito actual.
     */

    if (
        WC()->cart &&
        $proof['cart_hash']
        !== WC()->cart->get_cart_hash()
    ) {

        return false;
    }


    /**
     * El archivo debe existir.
     */

    if (
        !aec_transfer_resolve_file(
            $proof['relative_path']
        )
    ) {

        return false;
    }


    return $proof;
}


/**
 * =========================================================
 * ¿YA HAY COMPROBANTE VÁLIDO?
 * =========================================================
 */

function aec_transfer_session_has_proof()
{
    return
        is_array(
            aec_transfer_get_session_proof()
        );
}


/**
 * =========================================================
 * AJAX: SUBIR COMPROBANTE
 * =========================================================
 */

function aec_transfer_upload_proof()
{
    check_ajax_referer(
        'aec_transfer_upload',
        'nonce'
    );


    if (
        !function_exists('WC') ||
        !WC()->session ||
        !WC()->cart
    ) {

        wp_send_json_error(
            [
                'message' =>
                    'No fue posible iniciar la sesión del pedido.',
            ],
            400
        );
    }


    if (
        empty($_FILES['proof']) ||
        !isset(
            $_FILES['proof']['tmp_name']
        )
    ) {

        wp_send_json_error(
            [
                'message' =>
                    'Selecciona un comprobante.',
            ],
            400
        );
    }


    $file =
        $_FILES['proof'];


    if (
        !empty($file['error'])
    ) {

        wp_send_json_error(
            [
                'message' =>
                    'No se pudo cargar el archivo.',
            ],
            400
        );
    }


    if (
        (int) $file['size']
        >
        AEC_TRANSFER_MAX_FILE_SIZE
    ) {

        wp_send_json_error(
            [
                'message' =>
                    'El comprobante no puede superar 5 MB.',
            ],
            400
        );
    }


    $allowed_mimes = [

        'jpg|jpeg' =>
            'image/jpeg',

        'png' =>
            'image/png',

        'pdf' =>
            'application/pdf',

    ];


    $checked =
        wp_check_filetype_and_ext(
            $file['tmp_name'],
            $file['name'],
            $allowed_mimes
        );


    if (
        empty($checked['ext']) ||
        empty($checked['type'])
    ) {

        wp_send_json_error(
            [
                'message' =>
                    'Formato no permitido. Usa JPG, PNG o PDF.',
            ],
            400
        );
    }


    $uploads =
        wp_upload_dir();


    $year =
        wp_date('Y');


    $month =
        wp_date('m');


    $relative_directory =
        'aec-private-comprobantes'
        . '/'
        . $year
        . '/'
        . $month;


    $absolute_directory =
        trailingslashit(
            $uploads['basedir']
        )
        .
        $relative_directory;


    aec_transfer_prepare_private_directory(
        $absolute_directory
    );


    $filename =
        'aec-'
        .
        wp_generate_uuid4()
        .
        '.'
        .
        sanitize_key(
            $checked['ext']
        );


    $destination =
        trailingslashit(
            $absolute_directory
        )
        .
        $filename;


    /**
     * Eliminar comprobante anterior
     * de esta sesión si existiera.
     */

    $old_proof =
        WC()->session->get(
            'aec_transfer_proof'
        );


    if (
        is_array($old_proof) &&
        !empty(
            $old_proof['relative_path']
        )
    ) {

        $old_file =
            aec_transfer_resolve_file(
                $old_proof['relative_path']
            );


        if (
            $old_file &&
            is_file($old_file)
        ) {

            @unlink(
                $old_file
            );
        }
    }


    if (
        !move_uploaded_file(
            $file['tmp_name'],
            $destination
        )
    ) {

        wp_send_json_error(
            [
                'message' =>
                    'No fue posible guardar el comprobante.',
            ],
            500
        );
    }


    @chmod(
        $destination,
        0640
    );


    $relative_path =
        $relative_directory
        .
        '/'
        .
        $filename;


    $proof = [

        'relative_path' =>
            $relative_path,

        'original_name' =>
            sanitize_file_name(
                $file['name']
            ),

        'mime' =>
            sanitize_mime_type(
                $checked['type']
            ),

        'size' =>
            (int) $file['size'],

        'cart_hash' =>
            WC()->cart->get_cart_hash(),

        'uploaded_at' =>
            time(),

    ];


    WC()->session->set(
        'aec_transfer_proof',
        $proof
    );


    wp_send_json_success(
        [
            'name' =>
                $proof['original_name'],

            'message' =>
                'Comprobante cargado correctamente.',
        ]
    );
}


add_action(
    'wp_ajax_aec_transfer_upload',
    'aec_transfer_upload_proof'
);


add_action(
    'wp_ajax_nopriv_aec_transfer_upload',
    'aec_transfer_upload_proof'
);


/**
 * =========================================================
 * VALIDACIÓN EN CHECKOUT BLOCK
 * =========================================================
 *
 * WooCommerce ya persistió:
 *
 * - modalidad
 * - método de pago
 *
 * antes de ejecutar este hook.
 */

function aec_transfer_validate_checkout(
    $order,
    $request
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return;
    }


    /**
     * Solo Transferencia bancaria.
     */

    if (
        $order->get_payment_method()
        !== 'bacs'
    ) {
        return;
    }


    /**
     * Solo Recoger.
     */

    if (
        aec_transfer_order_type(
            $order
        ) !== 'recoger'
    ) {
        return;
    }


    if (
        !aec_transfer_bank_is_ready()
    ) {

        aec_transfer_throw_checkout_error(
            'Los datos bancarios no están configurados. '
            . 'Selecciona otro método de pago.'
        );
    }


    $proof =
        aec_transfer_get_session_proof();


    if (
        !$proof
    ) {

        aec_transfer_throw_checkout_error(
            'Para recoger y pagar por transferencia '
            . 'debes subir tu comprobante de pago.'
        );
    }


    /**
     * Guardar información privada en el pedido.
     */

    $order->update_meta_data(
        '_aec_transfer_proof_file',
        $proof['relative_path']
    );


    $order->update_meta_data(
        '_aec_transfer_proof_name',
        $proof['original_name']
    );


    $order->update_meta_data(
        '_aec_transfer_proof_mime',
        $proof['mime']
    );


    $order->update_meta_data(
        '_aec_transfer_proof_size',
        $proof['size']
    );


    $order->update_meta_data(
        '_aec_transfer_proof_uploaded_at',
        current_time(
            'mysql'
        )
    );


    $order->update_meta_data(
        '_aec_transfer_validation_status',
        'pending'
    );
}


add_action(
    'woocommerce_store_api_checkout_update_order_from_request',
    'aec_transfer_validate_checkout',
    50,
    2
);


/**
 * =========================================================
 * GENERAR ERROR PARA STORE API
 * =========================================================
 */

function aec_transfer_throw_checkout_error(
    $message
) {
    if (
        class_exists(
            '\Automattic\WooCommerce\StoreApi\Exceptions\RouteException'
        )
    ) {

        throw new
            \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
                'aec_transfer_validation_error',
                $message,
                400
            );
    }


    throw new Exception(
        $message
    );
}


/**
 * =========================================================
 * ESTADO INICIAL DE TRANSFERENCIA
 * =========================================================
 *
 * Recoger:
 *      En espera.
 *
 * Comer aquí:
 *      Confirmado.
 */

function aec_transfer_bacs_order_status(
    $status,
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return $status;
    }


    $type =
        aec_transfer_order_type(
            $order
        );


    /**
     * Recoger:
     *
     * esperar validación manual.
     */

    if (
        $type === 'recoger'
    ) {

        return 'on-hold';
    }


    /**
     * Comer aquí:
     *
     * no requerimos comprobante previo.
     */

    if (
        $type === 'comer_aqui'
    ) {

        if (
            function_exists(
                'aec_order_entry_confirmed_status'
            )
        ) {

            $confirmed =
                aec_order_entry_confirmed_status();


            if (
                $confirmed !== ''
            ) {

                return $confirmed;
            }
        }


        if (
            function_exists(
                'aec_workflow_status_slug_by_label'
            )
        ) {

            $confirmed =
                aec_workflow_status_slug_by_label(
                    'Confirmado'
                );


            if (
                $confirmed !== ''
            ) {

                return $confirmed;
            }
        }
    }


    return $status;
}


add_filter(
    'woocommerce_bacs_process_payment_order_status',
    'aec_transfer_bacs_order_status',
    50,
    2
);


/**
 * =========================================================
 * LIMPIAR SESIÓN DESPUÉS DE CREAR PEDIDO RECOGER
 * =========================================================
 */

function aec_transfer_clear_session_after_order(
    $order_id,
    $order = false
) {
    if (
        !($order instanceof WC_Order)
    ) {

        $order =
            wc_get_order(
                $order_id
            );
    }


    if (
        !($order instanceof WC_Order)
    ) {
        return;
    }


    if (
        $order->get_payment_method()
        !== 'bacs'
    ) {
        return;
    }


    if (
        aec_transfer_order_type(
            $order
        ) !== 'recoger'
    ) {
        return;
    }


    if (
        $order->get_meta(
            '_aec_transfer_proof_file',
            true
        ) === ''
    ) {
        return;
    }


    if (
        function_exists('WC') &&
        WC()->session
    ) {

        WC()->session->__unset(
            'aec_transfer_proof'
        );
    }
}


add_action(
    'woocommerce_order_status_on-hold',
    'aec_transfer_clear_session_after_order',
    50,
    2
);


/**
 * =========================================================
 * ASSETS DEL CHECKOUT
 * =========================================================
 */

function aec_transfer_checkout_assets()
{
    if (
        !function_exists('is_checkout') ||
        !is_checkout()
    ) {
        return;
    }


    if (
        function_exists(
            'is_order_received_page'
        ) &&
        is_order_received_page()
    ) {
        return;
    }


    wp_enqueue_style(
        'aec-transfer-proof',
        AEC_PLUGIN_URL
        .
        'assets/css/transfer-proof.css',
        [],
        '1.1.0'
    );


    wp_enqueue_script(
        'aec-transfer-proof',
        AEC_PLUGIN_URL
        .
        'assets/js/transfer-proof.js',
        [
            'wp-data',
        ],
        '1.1.0',
        true
    );


    $details =
        aec_transfer_get_bank_details();


    wp_localize_script(
        'aec-transfer-proof',
        'AECTransferProof',
        [

            'ajaxUrl' =>
                admin_url(
                    'admin-ajax.php'
                ),

            'nonce' =>
                wp_create_nonce(
                    'aec_transfer_upload'
                ),

            'bankReady' =>
                aec_transfer_bank_is_ready(),

            'bankName' =>
                $details['bank_name'],

            'accountName' =>
                $details['account_name'],

            'accountNumber' =>
                $details['account_number'],

            'instructions' =>
                $details['instructions'],

            'hasProof' =>
                aec_transfer_session_has_proof(),

            'maxSize' =>
                AEC_TRANSFER_MAX_FILE_SIZE,

        ]
    );
}


add_action(
    'wp_enqueue_scripts',
    'aec_transfer_checkout_assets',
    40
);


/**
 * =========================================================
 * PANEL EN ADMINISTRACIÓN DEL PEDIDO
 * =========================================================
 */

function aec_transfer_admin_order_box(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return;
    }


    if (
        $order->get_payment_method()
        !== 'bacs'
    ) {
        return;
    }


    $type =
        aec_transfer_order_type(
            $order
        );


    ?>

    <div class="aec-transfer-admin-box">

        <h3>
            Transferencia bancaria
        </h3>


        <?php
        if (
            $type === 'comer_aqui'
        ):
        ?>

            <p>
                <strong>
                    Comer aquí
                </strong>
            </p>

            <p>
                Este pedido no requiere
                comprobante previo.
            </p>

        <?php

            return;

        endif;


        if (
            $type !== 'recoger'
        ):
        ?>

            <p>
                Modalidad no identificada.
            </p>

        <?php

            return;

        endif;


        $proof_file =
            $order->get_meta(
                '_aec_transfer_proof_file',
                true
            );


        $proof_name =
            $order->get_meta(
                '_aec_transfer_proof_name',
                true
            );


        $validation_status =
            $order->get_meta(
                '_aec_transfer_validation_status',
                true
            );


        if (
            $validation_status === ''
        ) {

            $validation_status =
                'pending';
        }


        ?>


        <div class="aec-transfer-admin-grid">

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
                    Estado del comprobante
                </span>

                <strong>

                    <?php

                    if (
                        $validation_status ===
                        'validated'
                    ) {

                        echo 'Validado';

                    } else {

                        echo 'Pendiente de validación';
                    }

                    ?>

                </strong>

            </div>

        </div>


        <?php
        if (
            $proof_file !== ''
        ):
        ?>

            <p>

                <a
                    class="button"
                    target="_blank"
                    rel="noopener"
                    href="<?php
                        echo esc_url(
                            aec_transfer_download_url(
                                $order
                            )
                        );
                    ?>"
                >

                    Ver comprobante

                    <?php
                    if (
                        $proof_name !== ''
                    ):
                    ?>

                        —
                        <?php
                        echo esc_html(
                            $proof_name
                        );
                        ?>

                    <?php endif; ?>

                </a>

            </p>

        <?php else: ?>

            <p>
                <strong>
                    No existe comprobante asociado.
                </strong>
            </p>

        <?php endif; ?>


        <?php
        if (
            $validation_status !==
            'validated'
            &&
            $proof_file !== ''
        ):
        ?>

            <p>

                <a
                    class="
                        button
                        button-primary
                    "
                    href="<?php
                        echo esc_url(
                            aec_transfer_validate_url(
                                $order
                            )
                        );
                    ?>"
                >
                    Validar transferencia
                </a>

            </p>

        <?php endif; ?>


        <?php
        if (
            $validation_status ===
            'validated'
        ):
        ?>

            <div class="aec-transfer-admin-success">

                Transferencia validada.

                <?php

                $validated_at =
                    $order->get_meta(
                        '_aec_transfer_validated_at',
                        true
                    );


                if (
                    $validated_at !== ''
                ) {

                    echo '<br>';

                    echo esc_html(
                        $validated_at
                    );
                }

                ?>

            </div>

        <?php endif; ?>

    </div>

    <?php
}


add_action(
    'woocommerce_admin_order_data_after_order_details',
    'aec_transfer_admin_order_box',
    25
);


/**
 * =========================================================
 * URL DESCARGA SEGURA
 * =========================================================
 */

function aec_transfer_download_url(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return '';
    }


    $url =
        add_query_arg(
            [
                'action' =>
                    'aec_transfer_download',

                'order_id' =>
                    $order->get_id(),

            ],
            admin_url(
                'admin-post.php'
            )
        );


    return wp_nonce_url(
        $url,
        'aec_transfer_download_'
        .
        $order->get_id()
    );
}


/**
 * =========================================================
 * DESCARGAR / VER COMPROBANTE
 * =========================================================
 */

function aec_transfer_download()
{
    $order_id =
        isset($_GET['order_id'])
            ? absint(
                $_GET['order_id']
            )
            : 0;


    if (
        !$order_id
    ) {

        wp_die(
            'Pedido inválido.'
        );
    }


    check_admin_referer(
        'aec_transfer_download_'
        .
        $order_id
    );


    if (
        !current_user_can(
            'manage_woocommerce'
        ) &&
        !current_user_can(
            'edit_shop_orders'
        )
    ) {

        wp_die(
            'No tienes permisos.'
        );
    }


    $order =
        wc_get_order(
            $order_id
        );


    if (
        !($order instanceof WC_Order)
    ) {

        wp_die(
            'Pedido no encontrado.'
        );
    }


    $relative_path =
        $order->get_meta(
            '_aec_transfer_proof_file',
            true
        );


    $file =
        aec_transfer_resolve_file(
            $relative_path
        );


    if (
        !$file ||
        !is_file($file)
    ) {

        wp_die(
            'Comprobante no encontrado.'
        );
    }


    $mime =
        $order->get_meta(
            '_aec_transfer_proof_mime',
            true
        );


    $name =
        $order->get_meta(
            '_aec_transfer_proof_name',
            true
        );


    if (
        $name === ''
    ) {

        $name =
            basename(
                $file
            );
    }


    nocache_headers();


    header(
        'Content-Type: '
        .
        (
            $mime
                ? $mime
                : 'application/octet-stream'
        )
    );


    header(
        'Content-Length: '
        .
        filesize(
            $file
        )
    );


    header(
        'Content-Disposition: inline; filename="'
        .
        rawurlencode(
            $name
        )
        .
        '"'
    );


    readfile(
        $file
    );


    exit;
}


add_action(
    'admin_post_aec_transfer_download',
    'aec_transfer_download'
);


/**
 * =========================================================
 * URL VALIDAR TRANSFERENCIA
 * =========================================================
 */

function aec_transfer_validate_url(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return '';
    }


    $url =
        add_query_arg(
            [
                'action' =>
                    'aec_transfer_validate',

                'order_id' =>
                    $order->get_id(),

            ],
            admin_url(
                'admin-post.php'
            )
        );


    return wp_nonce_url(
        $url,
        'aec_transfer_validate_'
        .
        $order->get_id()
    );
}


/**
 * =========================================================
 * VALIDAR TRANSFERENCIA
 * =========================================================
 */

function aec_transfer_validate_payment()
{
    $order_id =
        isset($_GET['order_id'])
            ? absint(
                $_GET['order_id']
            )
            : 0;


    if (
        !$order_id
    ) {

        wp_die(
            'Pedido inválido.'
        );
    }


    check_admin_referer(
        'aec_transfer_validate_'
        .
        $order_id
    );


    if (
        !current_user_can(
            'manage_woocommerce'
        ) &&
        !current_user_can(
            'edit_shop_orders'
        )
    ) {

        wp_die(
            'No tienes permisos para validar transferencias.'
        );
    }


    $order =
        wc_get_order(
            $order_id
        );


    if (
        !($order instanceof WC_Order)
    ) {

        wp_die(
            'Pedido no encontrado.'
        );
    }


    if (
        $order->get_payment_method()
        !== 'bacs'
    ) {

        wp_die(
            'Este pedido no utiliza transferencia bancaria.'
        );
    }


    if (
        aec_transfer_order_type(
            $order
        ) !== 'recoger'
    ) {

        wp_die(
            'Esta validación solo aplica a pedidos para recoger.'
        );
    }


    $proof =
        $order->get_meta(
            '_aec_transfer_proof_file',
            true
        );


    if (
        !$proof ||
        !aec_transfer_resolve_file(
            $proof
        )
    ) {

        wp_die(
            'El pedido no tiene un comprobante válido.'
        );
    }


    $user =
        wp_get_current_user();


    $validator =
        $user->display_name
            ? $user->display_name
            : $user->user_login;


    $date =
        wp_date(
            'd/m/Y H:i:s'
        );


    $order->update_meta_data(
        '_aec_transfer_validation_status',
        'validated'
    );


    $order->update_meta_data(
        '_aec_transfer_validated_by',
        $user->ID
    );


    $order->update_meta_data(
        '_aec_transfer_validated_at',
        $date
    );


    $order->save();


    $note =
        sprintf(
            'Transferencia validada por %s el %s. '
            . 'Pedido ingresado al flujo operativo.',
            $validator,
            $date
        );


    /**
     * Cambiar obligatoriamente a CONFIRMADO.
     */

    if (
        function_exists(
            'aec_order_entry_set_confirmed'
        )
    ) {

        $result =
            aec_order_entry_set_confirmed(
                $order,
                $note
            );


        if (!$result) {

            wp_die(
                'No fue posible cambiar el pedido a Confirmado.'
            );
        }

    } else {

        $confirmed =
            '';


        if (
            function_exists(
                'aec_workflow_status_slug_by_label'
            )
        ) {

            $confirmed =
                aec_workflow_status_slug_by_label(
                    'Confirmado'
                );
        }


        if (
            $confirmed === ''
        ) {

            wp_die(
                'No se encontró el estado Confirmado.'
            );
        }


        $order->update_status(
            $confirmed,
            $note,
            true
        );
    }


    $redirect =
        aec_transfer_order_edit_url(
            $order
        );


    $redirect =
        add_query_arg(
            'aec_transfer_validated',
            '1',
            $redirect
        );


    wp_safe_redirect(
        $redirect
    );


    exit;
}


add_action(
    'admin_post_aec_transfer_validate',
    'aec_transfer_validate_payment'
);


/**
 * =========================================================
 * URL EDITAR PEDIDO
 * =========================================================
 */

function aec_transfer_order_edit_url(
    $order
) {
    if (
        !($order instanceof WC_Order)
    ) {
        return admin_url(
            'admin.php?page=wc-orders'
        );
    }


    if (
        method_exists(
            $order,
            'get_edit_order_url'
        )
    ) {

        return
            $order->get_edit_order_url();
    }


    return admin_url(
        'post.php?post='
        .
        $order->get_id()
        .
        '&action=edit'
    );
}


/**
 * =========================================================
 * AVISO ADMIN
 * =========================================================
 */

function aec_transfer_admin_notice()
{
    if (
        empty(
            $_GET['aec_transfer_validated']
        )
    ) {
        return;
    }


    if (
        sanitize_key(
            wp_unslash(
                $_GET['aec_transfer_validated']
            )
        ) !== '1'
    ) {
        return;
    }


    ?>

    <div
        class="
            notice
            notice-success
            is-dismissible
        "
    >

        <p>
            Transferencia validada.
            El pedido ya está en
            <strong>Confirmado</strong>
            y aparecerá en la Pantalla de pedidos.
        </p>

    </div>

    <?php
}


add_action(
    'admin_notices',
    'aec_transfer_admin_notice'
);


/**
 * =========================================================
 * CSS ADMINISTRATIVO
 * =========================================================
 */

function aec_transfer_admin_styles()
{
    ?>

    <style>

        .aec-transfer-admin-box {
            background: #fffaf1;
            border: 1px solid #e2ddd3;
            border-left: 5px solid #294c73;
            border-radius: 10px;
            box-sizing: border-box;
            clear: both;
            margin-top: 22px;
            padding: 18px;
        }


        .aec-transfer-admin-box h3 {
            margin:
                0
                0
                15px;
        }


        .aec-transfer-admin-grid {
            display: grid;
            gap: 10px;
            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );
            margin-bottom: 15px;
        }


        .aec-transfer-admin-grid > div {
            background: #ffffff;
            border: 1px solid #e7e2da;
            border-radius: 8px;
            padding: 11px;
        }


        .aec-transfer-admin-grid span {
            color: #646970;
            display: block;
            font-size: 11px;
            margin-bottom: 4px;
        }


        .aec-transfer-admin-grid strong {
            color: #1d2327;
            font-size: 14px;
        }


        .aec-transfer-admin-success {
            background: #edf6e9;
            border-radius: 8px;
            color: #315325;
            font-weight: 700;
            margin-top: 12px;
            padding: 12px;
        }


        @media (max-width: 782px) {

            .aec-transfer-admin-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

    <?php
}


add_action(
    'admin_head',
    'aec_transfer_admin_styles'
);
