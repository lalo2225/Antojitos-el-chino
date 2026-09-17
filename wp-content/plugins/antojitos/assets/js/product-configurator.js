document.addEventListener(
    'DOMContentLoaded',
    function () {

        /**
         * ==========================================
         * OPCIÓN 1 / OPCIÓN 2
         * ==========================================
         *
         * Evita seleccionar exactamente
         * la misma opción dos veces.
         */

        const opcion1 =
            document.querySelector(
                'select[name="aec_opcion_1"]'
            );

        const opcion2 =
            document.querySelector(
                'select[name="aec_opcion_2"]'
            );


        if (opcion1 && opcion2) {

            function actualizarOpciones() {

                const seleccionada =
                    opcion1.value;


                Array.from(
                    opcion2.options
                ).forEach(
                    function (option) {

                        if (!option.value) {
                            return;
                        }

                        option.disabled =
                            option.value
                            === seleccionada;
                    }
                );


                /*
                 * Si previamente estaba elegida
                 * la misma opción, la quitamos.
                 */
                if (
                    opcion2.value === seleccionada
                ) {
                    opcion2.value = '';
                }
            }


            opcion1.addEventListener(
                'change',
                actualizarOpciones
            );


            actualizarOpciones();
        }


        /**
         * ==========================================
         * PRECIO DEL AGUA FRESCA
         * ==========================================
         */

        const tamanos =
            document.querySelectorAll(
                'input[name="aec_tamano"]'
            );

        const precioPreview =
            document.getElementById(
                'aec-price-preview'
            );


        if (
            tamanos.length &&
            precioPreview
        ) {

            tamanos.forEach(
                function (radio) {

                    radio.addEventListener(
                        'change',
                        function () {

                            const precio =
                                this.dataset.price;

                            precioPreview.textContent =
                                'Precio: $'
                                + precio
                                + ' MXN';
                        }
                    );
                }
            );
        }

    }
);
