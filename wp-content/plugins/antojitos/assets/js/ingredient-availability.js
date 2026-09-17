document.addEventListener(
    'DOMContentLoaded',
    function () {

        'use strict';


        if (
            typeof AECIngredientAvailability
                === 'undefined'
            ||
            !Array.isArray(
                AECIngredientAvailability.fields
            )
        ) {
            return;
        }


        const fields =
            AECIngredientAvailability.fields;


        const configurator =
            document.querySelector(
                '.aec-configurador'
            );


        if (!configurator) {
            return;
        }


        const blockers = [];


        /**
         * =================================================
         * MARCAR SELECT
         * =================================================
         */

        function processSelect(
            field
        ) {
            const select =
                document.querySelector(
                    'select[name="'
                    +
                    field.name
                    +
                    '"]'
                );


            if (!select) {
                return;
            }


            let availableCount =
                0;


            Object.entries(
                field.options
            ).forEach(
                function (
                    [value, info]
                ) {

                    const option =
                        Array.from(
                            select.options
                        ).find(
                            function (item) {

                                return (
                                    item.value
                                    === value
                                );
                            }
                        );


                    if (!option) {
                        return;
                    }


                    if (
                        info.available
                    ) {

                        availableCount++;

                        return;
                    }


                    option.disabled =
                        true;


                    if (
                        !option.dataset
                            .aecAgotado
                    ) {

                        option.textContent =
                            option.textContent
                            +
                            ' — AGOTADO';


                        option.dataset
                            .aecAgotado =
                            '1';
                    }


                    if (
                        select.value
                        === value
                    ) {

                        select.value =
                            '';
                    }
                }
            );


            if (
                field.required
                &&
                availableCount === 0
            ) {

                blockers.push(
                    field.label
                );
            }
        }


        /**
         * =================================================
         * RADIO / CHECKBOX
         * =================================================
         */

        function processInputs(
            field
        ) {
            const inputs =
                document.querySelectorAll(
                    'input[name="'
                    +
                    field.name
                    +
                    '"]'
                );


            if (
                !inputs.length
            ) {
                return;
            }


            let availableCount =
                0;


            inputs.forEach(
                function (input) {

                    const info =
                        field.options[
                            input.value
                        ];


                    if (!info) {
                        return;
                    }


                    if (
                        info.available
                    ) {

                        availableCount++;

                        return;
                    }


                    input.disabled =
                        true;


                    input.checked =
                        false;


                    const label =
                        input.closest(
                            'label'
                        );


                    if (label) {

                        label.classList.add(
                            'aec-option-unavailable'
                        );


                        if (
                            !label.querySelector(
                                '.aec-option-stock-label'
                            )
                        ) {

                            const badge =
                                document.createElement(
                                    'small'
                                );


                            badge.className =
                                'aec-option-stock-label';


                            badge.textContent =
                                'Agotado';


                            label.appendChild(
                                badge
                            );
                        }
                    }
                }
            );


            if (
                field.required
                &&
                availableCount === 0
            ) {

                blockers.push(
                    field.label
                );
            }
        }


        /**
         * =================================================
         * PROCESAR CAMPOS
         * =================================================
         */

        fields.forEach(
            function (field) {

                if (
                    field.type
                    === 'select'
                ) {

                    processSelect(
                        field
                    );

                    return;
                }


                processInputs(
                    field
                );
            }
        );


        /**
         * =================================================
         * BLOQUEAR PRODUCTO SOLO CUANDO NO EXISTA
         * NINGUNA OPCIÓN OBLIGATORIA DISPONIBLE
         * =================================================
         */

        const addButton =
            document.querySelector(
                '.single_add_to_cart_button'
            );


        if (
            blockers.length > 0
        ) {

            const warning =
                document.createElement(
                    'div'
                );


            warning.className =
                'aec-availability-warning';


            warning.innerHTML =
                '<strong>No hay opciones disponibles en este momento.</strong>'
                +
                '<span>'
                +
                blockers.join(', ')
                +
                '</span>';


            configurator.appendChild(
                warning
            );


            if (addButton) {

                addButton.disabled =
                    true;


                addButton.setAttribute(
                    'aria-disabled',
                    'true'
                );


                addButton.dataset
                    .aecAvailabilityDisabled =
                    '1';
            }

        } else if (
            addButton
            &&
            addButton.dataset
                .aecAvailabilityDisabled
                === '1'
        ) {

            addButton.disabled =
                false;


            addButton.removeAttribute(
                'aria-disabled'
            );


            delete addButton.dataset
                .aecAvailabilityDisabled;
        }

    }
);
