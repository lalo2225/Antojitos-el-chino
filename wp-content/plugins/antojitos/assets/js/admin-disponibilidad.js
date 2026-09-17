document.addEventListener('DOMContentLoaded', function () {

    const form =
        document.getElementById(
            'aec-availability-form'
        );

    if (!form) {
        return;
    }


    const items =
        Array.from(
            document.querySelectorAll(
                '[data-availability-item]'
            )
        );


    const groups =
        Array.from(
            document.querySelectorAll(
                '[data-group]'
            )
        );


    const search =
        document.getElementById(
            'aec-availability-search'
        );


    const filters =
        Array.from(
            document.querySelectorAll(
                '.aec-availability-filter'
            )
        );


    const empty =
        document.getElementById(
            'aec-availability-empty'
        );


    const availableCount =
        document.getElementById(
            'aec-availability-count-available'
        );


    const soldoutCount =
        document.getElementById(
            'aec-availability-count-soldout'
        );


    const saveStatus =
        document.getElementById(
            'aec-availability-save-status'
        );


    let currentFilter =
        'all';


    let dirty =
        false;


    /**
     * =====================================================
     * NORMALIZAR TEXTO
     * =====================================================
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
     * =====================================================
     * CAMBIOS PENDIENTES
     * =====================================================
     */

    function markDirty() {

        dirty = true;


        if (!saveStatus) {
            return;
        }


        saveStatus.textContent =
            'Cambios sin guardar';


        saveStatus.classList.add(
            'has-changes'
        );
    }


    /**
     * =====================================================
     * CONTADORES
     * =====================================================
     */

    function updateCounters() {

        let available =
            0;


        let soldout =
            0;


        items.forEach(
            function (item) {

                if (
                    item.dataset.state
                    === 'agotado'
                ) {

                    soldout++;

                } else {

                    available++;
                }
            }
        );


        if (availableCount) {

            availableCount.textContent =
                String(available);
        }


        if (soldoutCount) {

            soldoutCount.textContent =
                String(soldout);
        }
    }


    /**
     * =====================================================
     * ACTUALIZAR VISUALMENTE UN ITEM
     * =====================================================
     */

    function renderItem(
        item,
        state
    ) {

        const input =
            item.querySelector(
                '[data-state-input]'
            );


        const button =
            item.querySelector(
                '[data-state-toggle]'
            );


        const label =
            item.querySelector(
                '[data-state-label]'
            );


        const description =
            item.querySelector(
                '[data-state-description]'
            );


        const soldout =
            state === 'agotado';


        item.dataset.state =
            state;


        item.classList.toggle(
            'is-soldout',
            soldout
        );


        item.classList.toggle(
            'is-available',
            !soldout
        );


        if (input) {

            input.value =
                state;
        }


        if (button) {

            button.classList.toggle(
                'is-soldout',
                soldout
            );


            button.classList.toggle(
                'is-available',
                !soldout
            );


            button.setAttribute(
                'aria-pressed',
                soldout
                    ? 'true'
                    : 'false'
            );
        }


        if (label) {

            label.textContent =
                soldout
                    ? 'Agotado'
                    : 'Disponible';
        }


        if (description) {

            description.textContent =
                soldout
                    ? 'No se ofrecerá al cliente'
                    : 'Disponible para pedidos';
        }
    }


    /**
     * =====================================================
     * CAMBIAR ESTADO
     * =====================================================
     */

    items.forEach(
        function (item) {

            const button =
                item.querySelector(
                    '[data-state-toggle]'
                );


            if (!button) {
                return;
            }


            button.addEventListener(
                'click',
                function () {

                    const newState =
                        item.dataset.state
                            === 'agotado'
                            ? 'disponible'
                            : 'agotado';


                    renderItem(
                        item,
                        newState
                    );


                    markDirty();

                    updateCounters();

                    applyFilters();
                }
            );
        }
    );


    /**
     * =====================================================
     * FILTRAR
     * =====================================================
     */

    function applyFilters() {

        const query =
            normalizeText(
                search
                    ? search.value
                    : ''
            );


        let visibleItems =
            0;


        items.forEach(
            function (item) {

                const name =
                    normalizeText(
                        item.dataset.name
                    );


                const state =
                    item.dataset.state;


                const matchesSearch =
                    query === ''
                    ||
                    name.includes(
                        query
                    );


                const matchesState =
                    currentFilter === 'all'
                    ||
                    state === currentFilter;


                const visible =
                    matchesSearch
                    &&
                    matchesState;


                item.hidden =
                    !visible;


                if (visible) {

                    visibleItems++;
                }
            }
        );


        groups.forEach(
            function (group) {

                const groupItems =
                    Array.from(
                        group.querySelectorAll(
                            '[data-availability-item]'
                        )
                    );


                const hasVisible =
                    groupItems.some(
                        function (item) {

                            return !item.hidden;
                        }
                    );


                group.hidden =
                    !hasVisible;
            }
        );


        if (empty) {

            empty.hidden =
                visibleItems !== 0;
        }
    }


    /**
     * =====================================================
     * BUSCADOR
     * =====================================================
     */

    if (search) {

        search.addEventListener(
            'input',
            applyFilters
        );
    }


    /**
     * =====================================================
     * FILTROS
     * =====================================================
     */

    filters.forEach(
        function (button) {

            button.addEventListener(
                'click',
                function () {

                    currentFilter =
                        button.dataset.filter
                        ||
                        'all';


                    filters.forEach(
                        function (filterButton) {

                            filterButton
                                .classList
                                .remove(
                                    'is-active'
                                );
                        }
                    );


                    button.classList.add(
                        'is-active'
                    );


                    applyFilters();
                }
            );
        }
    );


    /**
     * =====================================================
     * ADVERTENCIA AL SALIR
     * =====================================================
     */

    form.addEventListener(
        'submit',
        function () {

            dirty = false;
        }
    );


    window.addEventListener(
        'beforeunload',
        function (event) {

            if (!dirty) {
                return;
            }


            event.preventDefault();

            event.returnValue = '';
        }
    );


    /**
     * ESTADO INICIAL
     */

    updateCounters();

    applyFilters();

});
