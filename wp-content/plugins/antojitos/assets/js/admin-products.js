(function () {
    'use strict';


    /**
     * =====================================================
     * ANTOJITOS EL CHINO
     * ADMINISTRACIÓN DE PRODUCTOS
     * =====================================================
     */

    function initAECProductsAdmin() {

        /*
         * Evitar inicializar dos veces.
         */
        if (window.AECProductsAdminInitialized) {
            return;
        }


        const rows =
            Array.from(
                document.querySelectorAll(
                    '[data-product-row]'
                )
            );


        /*
         * Si no estamos en nuestra pantalla,
         * no hacer nada.
         */
        if (rows.length === 0) {
            return;
        }


        window.AECProductsAdminInitialized =
            true;


        const search =
            document.getElementById(
                'aec-products-search'
            );


        const filterButtons =
            Array.from(
                document.querySelectorAll(
                    '[data-product-filter]'
                )
            );


        const noResults =
            document.getElementById(
                'aec-products-no-results'
            );


        let activeFilter =
            'all';


        /**
         * =================================================
         * NORMALIZAR TEXTO
         * =================================================
         */

        function normalizeText(value) {

            return String(value || '')
                .normalize('NFD')
                .replace(
                    /[\u0300-\u036f]/g,
                    ''
                )
                .toLowerCase()
                .trim();
        }


        /**
         * =================================================
         * MOSTRAR / OCULTAR FILA
         * =================================================
         */

        function setRowVisible(
            row,
            visible
        ) {

            if (visible) {

                row.hidden =
                    false;

                row.style.removeProperty(
                    'display'
                );

            } else {

                row.hidden =
                    true;

                /*
                 * Forzamos display none para evitar que
                 * cualquier regla CSS de grid sobrescriba
                 * el atributo hidden.
                 */
                row.style.setProperty(
                    'display',
                    'none',
                    'important'
                );
            }
        }


        /**
         * =================================================
         * APLICAR FILTROS
         * =================================================
         */

        function applyFilters() {

            const searchValue =
                normalizeText(
                    search
                        ? search.value
                        : ''
                );


            let visibleCount =
                0;


            rows.forEach(
                function (row) {

                    const rowText =
                        normalizeText(
                            row.getAttribute(
                                'data-product-search'
                            )
                        );


                    const rowType =
                        normalizeText(
                            row.getAttribute(
                                'data-product-type'
                            )
                        );


                    const matchesSearch =
                        searchValue === ''
                        ||
                        rowText.includes(
                            searchValue
                        );


                    const matchesType =
                        activeFilter === 'all'
                        ||
                        rowType === activeFilter;


                    const visible =
                        matchesSearch
                        &&
                        matchesType;


                    setRowVisible(
                        row,
                        visible
                    );


                    if (visible) {

                        visibleCount++;
                    }
                }
            );


            if (noResults) {

                if (
                    visibleCount === 0
                ) {

                    noResults.hidden =
                        false;

                    noResults.style.removeProperty(
                        'display'
                    );

                } else {

                    noResults.hidden =
                        true;

                    noResults.style.setProperty(
                        'display',
                        'none',
                        'important'
                    );
                }
            }
        }


        /**
         * =================================================
         * BUSCADOR
         * =================================================
         */

        if (search) {

            search.addEventListener(
                'input',
                function () {

                    applyFilters();
                }
            );


            search.addEventListener(
                'search',
                function () {

                    applyFilters();
                }
            );
        }


        /**
         * =================================================
         * BOTONES DE FILTRO
         * =================================================
         */

        filterButtons.forEach(
            function (button) {

                button.addEventListener(
                    'click',
                    function (event) {

                        event.preventDefault();


                        activeFilter =
                            normalizeText(
                                button.getAttribute(
                                    'data-product-filter'
                                )
                            )
                            ||
                            'all';


                        filterButtons.forEach(
                            function (item) {

                                item.classList.remove(
                                    'is-active'
                                );


                                item.setAttribute(
                                    'aria-pressed',
                                    'false'
                                );
                            }
                        );


                        button.classList.add(
                            'is-active'
                        );


                        button.setAttribute(
                            'aria-pressed',
                            'true'
                        );


                        applyFilters();
                    }
                );
            }
        );


        /**
         * =================================================
         * ESTADO INICIAL
         * =================================================
         */

        applyFilters();
    }


    /**
     * WordPress normalmente carga el script al final,
     * pero soportamos ambos escenarios.
     */

    if (
        document.readyState === 'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            initAECProductsAdmin
        );

    } else {

        initAECProductsAdmin();
    }

})();
