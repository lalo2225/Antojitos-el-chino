/**
 * Antojitos el Chino
 *
 * Checkout:
 * bloque para regresar al menu antes de finalizar el pedido.
 *
 * El carrito de WooCommerce se conserva al navegar nuevamente
 * hacia /menu/.
 */

(function () {
    'use strict';

    const CARD_ID = 'aec-checkout-more-menu';


    /**
     * Crear el bloque visual.
     */
    function createCard() {

        const card = document.createElement('section');

        card.id = CARD_ID;
        card.className = 'aec-checkout-more-menu';

        card.setAttribute(
            'aria-labelledby',
            'aec-checkout-more-menu-title'
        );


        card.innerHTML = `
            <div class="aec-checkout-more-menu__content">

                <div class="aec-checkout-more-menu__text">

                    <span class="aec-checkout-more-menu__eyebrow">
                        Antes de pagar
                    </span>

                    <h2
                        id="aec-checkout-more-menu-title"
                        class="aec-checkout-more-menu__title"
                    >
                        ¿Se te antoja algo más?
                    </h2>

                    <p class="aec-checkout-more-menu__description">
                        Puedes volver al menú y agregar más productos.
                        Lo que ya tienes en tu pedido se conservará.
                    </p>

                </div>

                <a
                    class="aec-checkout-more-menu__button"
                    href="/menu/"
                >
                    <span aria-hidden="true">←</span>
                    Volver al menú
                </a>

            </div>
        `;

        return card;
    }


    /**
     * Buscar la sección de pago e insertar el bloque
     * inmediatamente antes.
     *
     * WooCommerce Blocks puede reconstruir partes del checkout,
     * por lo que esta función puede ejecutarse varias veces.
     */
    function mountCard() {

        if (
            !document.body ||
            !document.body.classList.contains(
                'woocommerce-checkout'
            )
        ) {
            return;
        }


        const paymentBlock =
            document.querySelector(
                '.wp-block-woocommerce-checkout-payment-block'
            ) ||
            document.querySelector(
                '.wc-block-checkout__payment-method'
            );


        if (!paymentBlock || !paymentBlock.parentNode) {
            return;
        }


        let card =
            document.getElementById(CARD_ID);


        if (!card) {
            card = createCard();
        }


        /**
         * Si WooCommerce reconstruyó el checkout,
         * volvemos a colocar el bloque en su posición.
         */
        if (
            paymentBlock.previousElementSibling !== card
        ) {
            paymentBlock.parentNode.insertBefore(
                card,
                paymentBlock
            );
        }
    }


    /**
     * Evitar ejecutar varias actualizaciones
     * dentro del mismo frame.
     */
    let updatePending = false;


    function scheduleMount() {

        if (updatePending) {
            return;
        }


        updatePending = true;


        window.requestAnimationFrame(
            function () {

                updatePending = false;

                mountCard();

            }
        );
    }


    /**
     * Inicio.
     */
    function init() {

        mountCard();


        /**
         * WooCommerce Checkout Blocks utiliza React y puede
         * reconstruir nodos cuando cambia:
         *
         * - modalidad
         * - método de pago
         * - información del checkout
         *
         * Observamos esos cambios para conservar nuestro bloque.
         */
        const observer =
            new MutationObserver(
                scheduleMount
            );


        observer.observe(
            document.body,
            {
                childList: true,
                subtree: true
            }
        );
    }


    if (
        document.readyState === 'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            init
        );

    } else {

        init();

    }

})();
