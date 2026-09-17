document.addEventListener('DOMContentLoaded', function () {

    'use strict';


    if (
        typeof AECTransferProof === 'undefined'
    ) {
        return;
    }


    let uploaded =
        Boolean(
            AECTransferProof.hasProof
        );


    let uploading =
        false;


    /**
     * =====================================================
     * DETECTAR MODALIDAD
     * =====================================================
     *
     * Buscamos directamente el select que contiene
     * las opciones conocidas:
     *
     * - comer_aqui
     * - recoger
     *
     * Esto evita depender del ID interno que WooCommerce
     * pueda generar para Additional Checkout Fields.
     */

    function getOrderType() {

        const selects =
            document.querySelectorAll(
                'select'
            );


        for (
            const select of selects
        ) {

            const values =
                Array.from(
                    select.options || []
                )
                .map(
                    function (option) {

                        return option.value;
                    }
                );


            if (
                values.includes(
                    'comer_aqui'
                )
                &&
                values.includes(
                    'recoger'
                )
            ) {

                return select.value || '';
            }
        }


        /**
         * Fallback por atributos.
         */

        const field =
            document.querySelector(
                'select[name*="tipo-pedido"], '
                +
                'select[id*="tipo-pedido"], '
                +
                'select[name*="tipo_pedido"], '
                +
                'select[id*="tipo_pedido"]'
            );


        return
            field
                ? field.value
                : '';
    }


    /**
     * =====================================================
     * DETECTAR MÉTODO DE PAGO
     * =====================================================
     */

    function getPaymentMethod() {

        /**
         * WooCommerce Blocks Payment Store.
         */
        try {

            if (
                window.wp &&
                wp.data
            ) {

                const store =
                    wp.data.select(
                        'wc/store/payment'
                    );


                if (
                    store &&
                    typeof store.getActivePaymentMethod
                        === 'function'
                ) {

                    const payment =
                        store.getActivePaymentMethod();


                    if (payment) {

                        return payment;
                    }
                }
            }

        } catch (error) {

            console.debug(
                'AEC payment store fallback:',
                error
            );
        }


        /**
         * Checkout Blocks.
         */

        const blockPayment =
            document.querySelector(
                'input[name="radio-control-wc-payment-method-options"]:checked'
            );


        if (blockPayment) {

            return blockPayment.value || '';
        }


        /**
         * Checkout clásico.
         */

        const classicPayment =
            document.querySelector(
                'input[name="payment_method"]:checked'
            );


        if (classicPayment) {

            return classicPayment.value || '';
        }


        return '';
    }


    /**
     * =====================================================
     * CONDICIÓN
     * =====================================================
     */

    function shouldShow() {

        const type =
            getOrderType();


        const payment =
            getPaymentMethod();


        return (
            type === 'recoger'
            &&
            payment === 'bacs'
        );
    }


    /**
     * =====================================================
     * CREAR PANEL
     * =====================================================
     */

    const panel =
        document.createElement(
            'section'
        );


    panel.id =
        'aec-transfer-proof-panel';


    panel.className =
        'aec-transfer-proof-panel';


    panel.hidden =
        true;


    panel.innerHTML = `

        <div class="aec-transfer-proof-heading">

            <span class="aec-transfer-proof-eyebrow">
                Pago por transferencia
            </span>

            <h3>
                Realiza tu transferencia
            </h3>

            <p>
                Para pedidos para recoger,
                necesitamos validar tu pago antes
                de comenzar a prepararlo.
            </p>

        </div>


        <div class="aec-transfer-bank-data">

            <div>
                <span>Banco</span>
                <strong id="aec-transfer-bank"></strong>
            </div>

            <div>
                <span>Beneficiario</span>
                <strong id="aec-transfer-name"></strong>
            </div>

            <div class="aec-transfer-account">
                <span>Cuenta / CLABE</span>
                <strong id="aec-transfer-account"></strong>
            </div>

        </div>


        <p
            id="aec-transfer-instructions"
            class="aec-transfer-instructions"
            hidden
        ></p>


        <div
            id="aec-transfer-bank-error"
            class="aec-transfer-error"
            hidden
        >
            Los datos bancarios todavía no están
            configurados. Selecciona otro método de pago.
        </div>


        <div class="aec-transfer-upload">

            <label for="aec-transfer-file">

                Comprobante de pago
                <strong>*</strong>

            </label>


            <input
                type="file"
                id="aec-transfer-file"
                accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"
            >


            <small>
                JPG, PNG o PDF. Máximo 5 MB.
            </small>


            <div
                id="aec-transfer-status"
                class="aec-transfer-status"
                aria-live="polite"
            ></div>

        </div>

    `;


    /**
     * Lo ponemos temporalmente en body.
     * Después mountPanel() lo mueve al checkout.
     */

    document.body.appendChild(
        panel
    );


    const bank =
        panel.querySelector(
            '#aec-transfer-bank'
        );


    const accountName =
        panel.querySelector(
            '#aec-transfer-name'
        );


    const account =
        panel.querySelector(
            '#aec-transfer-account'
        );


    const instructions =
        panel.querySelector(
            '#aec-transfer-instructions'
        );


    const bankError =
        panel.querySelector(
            '#aec-transfer-bank-error'
        );


    const fileInput =
        panel.querySelector(
            '#aec-transfer-file'
        );


    const status =
        panel.querySelector(
            '#aec-transfer-status'
        );


    /**
     * =====================================================
     * DATOS BANCARIOS
     * =====================================================
     */

    bank.textContent =
        AECTransferProof.bankName
        ||
        '—';


    accountName.textContent =
        AECTransferProof.accountName
        ||
        '—';


    account.textContent =
        AECTransferProof.accountNumber
        ||
        '—';


    if (
        AECTransferProof.instructions
    ) {

        instructions.textContent =
            AECTransferProof.instructions;


        instructions.hidden =
            false;
    }


    if (
        !AECTransferProof.bankReady
    ) {

        bankError.hidden =
            false;


        fileInput.disabled =
            true;
    }


    /**
     * =====================================================
     * COMPROBANTE YA CARGADO
     * =====================================================
     */

    if (uploaded) {

        status.textContent =
            'Comprobante cargado correctamente.';


        status.className =
            'aec-transfer-status is-success';
    }


    /**
     * =====================================================
     * LOCALIZAR BLOQUE DE PAGO
     * =====================================================
     */

    function findPaymentBlock() {

        return (
            document.querySelector(
                '.wp-block-woocommerce-checkout-payment-block'
            )
            ||
            document.querySelector(
                '.wc-block-checkout__payment-method'
            )
            ||
            document.querySelector(
                '.wc-block-components-payment-methods'
            )
            ||
            document.querySelector(
                '[class*="checkout-payment"]'
            )
        );
    }


    /**
     * =====================================================
     * MONTAR PANEL
     * =====================================================
     */

    function mountPanel() {

        const payment =
            findPaymentBlock();


        if (
            payment &&
            payment.parentNode
        ) {

            /**
             * Si todavía no está justo después del
             * bloque de pago, lo movemos.
             */

            if (
                payment.nextElementSibling
                !== panel
            ) {

                payment.insertAdjacentElement(
                    'afterend',
                    panel
                );
            }


            return true;
        }


        /**
         * Fallback dentro de la columna principal.
         */

        const main =
            document.querySelector(
                '.wc-block-checkout__main'
            );


        if (main) {

            main.appendChild(
                panel
            );


            return true;
        }


        return false;
    }


    /**
     * =====================================================
     * ACTUALIZAR VISIBILIDAD
     * =====================================================
     */

    function sync() {

        mountPanel();


        const show =
            shouldShow();


        panel.hidden =
            !show;


        /**
         * Clases para diagnóstico visual.
         */

        panel.dataset.orderType =
            getOrderType();


        panel.dataset.paymentMethod =
            getPaymentMethod();
    }


    /**
     * =====================================================
     * SUBIR COMPROBANTE
     * =====================================================
     */

    async function uploadFile(
        file
    ) {

        if (
            !file ||
            uploading
        ) {
            return;
        }


        if (
            file.size >
            Number(
                AECTransferProof.maxSize
            )
        ) {

            status.textContent =
                'El archivo supera los 5 MB.';


            status.className =
                'aec-transfer-status is-error';


            fileInput.value =
                '';


            return;
        }


        uploading =
            true;


        uploaded =
            false;


        fileInput.disabled =
            true;


        status.textContent =
            'Cargando comprobante...';


        status.className =
            'aec-transfer-status is-loading';


        const data =
            new FormData();


        data.append(
            'action',
            'aec_transfer_upload'
        );


        data.append(
            'nonce',
            AECTransferProof.nonce
        );


        data.append(
            'proof',
            file
        );


        try {

            const response =
                await fetch(
                    AECTransferProof.ajaxUrl,
                    {
                        method:
                            'POST',

                        credentials:
                            'same-origin',

                        body:
                            data
                    }
                );


            let result;


            try {

                result =
                    await response.json();

            } catch (error) {

                throw new Error(
                    'El servidor devolvió una respuesta inválida.'
                );
            }


            if (
                !response.ok ||
                !result.success
            ) {

                let message =
                    'No fue posible cargar el comprobante.';


                if (
                    result &&
                    result.data &&
                    result.data.message
                ) {

                    message =
                        result.data.message;
                }


                throw new Error(
                    message
                );
            }


            uploaded =
                true;


            status.textContent =
                'Comprobante cargado: '
                +
                result.data.name;


            status.className =
                'aec-transfer-status is-success';


        } catch (error) {

            uploaded =
                false;


            status.textContent =
                error.message
                ||
                'No fue posible cargar el comprobante.';


            status.className =
                'aec-transfer-status is-error';


            fileInput.value =
                '';


        } finally {

            uploading =
                false;


            if (
                AECTransferProof.bankReady
            ) {

                fileInput.disabled =
                    false;
            }
        }
    }


    /**
     * =====================================================
     * ARCHIVO SELECCIONADO
     * =====================================================
     */

    fileInput.addEventListener(
        'change',
        function () {

            const file =
                fileInput.files
                &&
                fileInput.files.length
                    ? fileInput.files[0]
                    : null;


            uploadFile(
                file
            );
        }
    );


    /**
     * =====================================================
     * CAMBIO DIRECTO EN SELECT
     * =====================================================
     */

    document.addEventListener(
        'change',
        function (event) {

            const target =
                event.target;


            if (!target) {
                return;
            }


            if (
                target.matches(
                    'select'
                )
                ||
                target.matches(
                    'input[type="radio"]'
                )
            ) {

                window.setTimeout(
                    sync,
                    50
                );
            }
        }
    );


    /**
     * =====================================================
     * ESCUCHAR STORE DE WOOCOMMERCE
     * =====================================================
     */

    let lastState =
        '';


    if (
        window.wp &&
        wp.data &&
        typeof wp.data.subscribe
            === 'function'
    ) {

        wp.data.subscribe(
            function () {

                const currentState =
                    getOrderType()
                    +
                    '|'
                    +
                    getPaymentMethod();


                if (
                    currentState ===
                    lastState
                ) {

                    return;
                }


                lastState =
                    currentState;


                sync();
            }
        );
    }


    /**
     * =====================================================
     * WOOCOMMERCE USA REACT
     * =====================================================
     *
     * Algunas partes del checkout se reconstruyen.
     * Observamos esos cambios y volvemos a colocar
     * nuestro panel cuando sea necesario.
     */

    let observerTimer =
        null;


    const observer =
        new MutationObserver(
            function () {

                window.clearTimeout(
                    observerTimer
                );


                observerTimer =
                    window.setTimeout(
                        function () {

                            sync();

                        },
                        80
                    );
            }
        );


    observer.observe(
        document.body,
        {
            childList:
                true,

            subtree:
                true
        }
    );


    /**
     * =====================================================
     * ESTADO INICIAL
     * =====================================================
     */

    window.setTimeout(
        sync,
        300
    );


    window.setTimeout(
        sync,
        1000
    );


    window.setTimeout(
        sync,
        2000
    );

});
