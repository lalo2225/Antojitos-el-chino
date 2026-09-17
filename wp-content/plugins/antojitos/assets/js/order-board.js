document.addEventListener('DOMContentLoaded', function () {

    'use strict';


    const board =
        document.getElementById(
            'aec-order-board'
        );


    const updated =
        document.getElementById(
            'aec-board-updated'
        );


    const refreshButton =
        document.getElementById(
            'aec-board-refresh'
        );


    const fullscreenButton =
        document.getElementById(
            'aec-board-fullscreen'
        );


    const errorBox =
        document.getElementById(
            'aec-board-error'
        );


    const actions =
        document.querySelector(
            '.aec-board-topbar-actions'
        );


    if (
        !board ||
        typeof AECOrderBoard === 'undefined'
    ) {
        return;
    }


    let refreshing = false;

    let soundEnabled = false;

    let audioContext = null;


    const originalTitle =
        document.title;


    /**
     * =====================================================
     * IDENTIFICAR PEDIDOS DEL TABLERO
     * =====================================================
     *
     * Utilizamos el número visible del pedido como
     * identificador dentro de la pantalla.
     */

    function getOrderKeys(container) {

        const keys =
            new Set();


        container
            .querySelectorAll(
                '.aec-board-order-number'
            )
            .forEach(
                function (element) {

                    const key =
                        element.textContent
                            .trim()
                            .replace(
                                /\s+/g,
                                ' '
                            );


                    if (key) {

                        keys.add(
                            key
                        );
                    }
                }
            );


        return keys;
    }


    /**
     * Pedidos que ya estaban en pantalla
     * cuando se abrió el tablero.
     *
     * No deben generar sonido.
     */

    let knownOrders =
        getOrderKeys(
            board
        );


    /**
     * =====================================================
     * CONTADOR DE PEDIDOS ACTIVOS
     * =====================================================
     */

    function updateActiveCount() {

        const count =
            board.querySelectorAll(
                '.aec-board-card'
            ).length;


        document.title =
            count > 0
                ? '(' + count + ') ' + originalTitle
                : originalTitle;
    }


    /**
     * =====================================================
     * CREAR BOTÓN DE SONIDO
     * =====================================================
     */

    const soundButton =
        document.createElement(
            'button'
        );


    soundButton.type =
        'button';


    soundButton.id =
        'aec-board-sound';


    soundButton.className =
        'button aec-board-sound-button';


    soundButton.textContent =
        'Activar sonido';


    if (actions) {

        /**
         * Lo colocamos antes del botón
         * de pantalla completa.
         */

        if (fullscreenButton) {

            actions.insertBefore(
                soundButton,
                fullscreenButton
            );

        } else {

            actions.appendChild(
                soundButton
            );
        }
    }


    /**
     * =====================================================
     * AUDIO
     * =====================================================
     */

    function getAudioContext() {

        if (audioContext) {

            return audioContext;
        }


        const AudioContext =
            window.AudioContext
            ||
            window.webkitAudioContext;


        if (!AudioContext) {

            return null;
        }


        audioContext =
            new AudioContext();


        return audioContext;
    }


    /**
     * Aviso corto.
     *
     * No cargamos archivos MP3 externos.
     * El sonido se genera directamente
     * en el navegador.
     */

    async function playNewOrderSound() {

        if (!soundEnabled) {
            return;
        }


        const context =
            getAudioContext();


        if (!context) {
            return;
        }


        try {

            if (
                context.state === 'suspended'
            ) {

                await context.resume();
            }


            const now =
                context.currentTime;


            /**
             * Primer tono.
             */

            const oscillator1 =
                context.createOscillator();


            const gain1 =
                context.createGain();


            oscillator1.type =
                'sine';


            oscillator1.frequency.setValueAtTime(
                780,
                now
            );


            gain1.gain.setValueAtTime(
                0.0001,
                now
            );


            gain1.gain.exponentialRampToValueAtTime(
                0.16,
                now + 0.02
            );


            gain1.gain.exponentialRampToValueAtTime(
                0.0001,
                now + 0.22
            );


            oscillator1.connect(
                gain1
            );


            gain1.connect(
                context.destination
            );


            oscillator1.start(
                now
            );


            oscillator1.stop(
                now + 0.24
            );


            /**
             * Segundo tono.
             */

            const oscillator2 =
                context.createOscillator();


            const gain2 =
                context.createGain();


            oscillator2.type =
                'sine';


            oscillator2.frequency.setValueAtTime(
                980,
                now + 0.24
            );


            gain2.gain.setValueAtTime(
                0.0001,
                now + 0.24
            );


            gain2.gain.exponentialRampToValueAtTime(
                0.18,
                now + 0.27
            );


            gain2.gain.exponentialRampToValueAtTime(
                0.0001,
                now + 0.52
            );


            oscillator2.connect(
                gain2
            );


            gain2.connect(
                context.destination
            );


            oscillator2.start(
                now + 0.24
            );


            oscillator2.stop(
                now + 0.54
            );

        } catch (error) {

            console.error(
                'No se pudo reproducir el aviso:',
                error
            );
        }
    }


    /**
     * =====================================================
     * ACTIVAR / DESACTIVAR SONIDO
     * =====================================================
     */

    soundButton.addEventListener(
        'click',
        async function () {

            soundEnabled =
                !soundEnabled;


            if (soundEnabled) {

                const context =
                    getAudioContext();


                if (context) {

                    try {

                        await context.resume();

                    } catch (error) {

                        console.error(
                            error
                        );
                    }
                }


                soundButton.textContent =
                    'Sonido activado';


                soundButton.classList.add(
                    'is-active'
                );


                /**
                 * Tono de prueba.
                 */

                await playNewOrderSound();

            } else {

                soundButton.textContent =
                    'Activar sonido';


                soundButton.classList.remove(
                    'is-active'
                );
            }
        }
    );


    /**
     * =====================================================
     * RESALTAR PEDIDOS NUEVOS
     * =====================================================
     */

    function highlightNewOrders(
        newOrders
    ) {

        if (
            !newOrders ||
            newOrders.length === 0
        ) {
            return;
        }


        board
            .querySelectorAll(
                '.aec-board-card'
            )
            .forEach(
                function (card) {

                    const orderElement =
                        card.querySelector(
                            '.aec-board-order-number'
                        );


                    if (!orderElement) {
                        return;
                    }


                    const key =
                        orderElement.textContent
                            .trim()
                            .replace(
                                /\s+/g,
                                ' '
                            );


                    if (
                        !newOrders.includes(
                            key
                        )
                    ) {
                        return;
                    }


                    card.classList.add(
                        'is-new'
                    );


                    /**
                     * Etiqueta NUEVO.
                     */

                    const header =
                        card.querySelector(
                            '.aec-board-card-header'
                        );


                    if (
                        header &&
                        !card.querySelector(
                            '.aec-board-new-label'
                        )
                    ) {

                        const label =
                            document.createElement(
                                'span'
                            );


                        label.className =
                            'aec-board-new-label';


                        label.textContent =
                            'Nuevo';


                        header.appendChild(
                            label
                        );
                    }


                    /**
                     * El resaltado temporal desaparece.
                     */

                    window.setTimeout(
                        function () {

                            card.classList.remove(
                                'is-new'
                            );


                            const label =
                                card.querySelector(
                                    '.aec-board-new-label'
                                );


                            if (label) {

                                label.remove();
                            }

                        },
                        12000
                    );
                }
            );
    }


    /**
     * =====================================================
     * ACTUALIZAR TABLERO
     * =====================================================
     */

    async function refreshBoard() {

        if (refreshing) {
            return;
        }


        /**
         * No hacer peticiones si la pestaña
         * está completamente oculta.
         */

        if (
            document.visibilityState
            === 'hidden'
        ) {
            return;
        }


        refreshing =
            true;


        board.classList.add(
            'is-refreshing'
        );


        if (refreshButton) {

            refreshButton.disabled =
                true;
        }


        const data =
            new FormData();


        data.append(
            'action',
            'aec_order_board_refresh'
        );


        data.append(
            'nonce',
            AECOrderBoard.nonce
        );


        try {

            const response =
                await fetch(
                    AECOrderBoard.ajaxUrl,
                    {
                        method:
                            'POST',

                        credentials:
                            'same-origin',

                        body:
                            data
                    }
                );


            if (!response.ok) {

                throw new Error(
                    'HTTP '
                    + response.status
                );
            }


            const result =
                await response.json();


            if (
                !result.success ||
                !result.data ||
                typeof result.data.html
                    !== 'string'
            ) {

                throw new Error(
                    'Respuesta inválida'
                );
            }


            /**
             * Analizar primero el HTML nuevo
             * SIN reemplazar todavía la pantalla.
             */

            const template =
                document.createElement(
                    'template'
                );


            template.innerHTML =
                result.data.html.trim();


            const incomingOrders =
                getOrderKeys(
                    template.content
                );


            const newOrders =
                [];


            incomingOrders.forEach(
                function (orderKey) {

                    if (
                        !knownOrders.has(
                            orderKey
                        )
                    ) {

                        newOrders.push(
                            orderKey
                        );
                    }
                }
            );


            /**
             * Ahora sí actualizar visualmente.
             */

            board.innerHTML =
                result.data.html;


            /**
             * Actualizamos la memoria.
             */

            knownOrders =
                incomingOrders;


            /**
             * Pedido nuevo.
             */

            if (
                newOrders.length > 0
            ) {

                highlightNewOrders(
                    newOrders
                );


                await playNewOrderSound();
            }


            updateActiveCount();


            if (
                updated &&
                result.data.updated
            ) {

                updated.textContent =
                    'Actualizado '
                    + result.data.updated;
            }


            if (errorBox) {

                errorBox.hidden =
                    true;
            }

        } catch (error) {

            console.error(
                'Antojitos - Error actualizando pedidos:',
                error
            );


            if (errorBox) {

                errorBox.hidden =
                    false;
            }


            if (updated) {

                updated.textContent =
                    'Error de actualización';
            }

        } finally {

            refreshing =
                false;


            board.classList.remove(
                'is-refreshing'
            );


            if (refreshButton) {

                refreshButton.disabled =
                    false;
            }
        }
    }


    /**
     * =====================================================
     * ACTUALIZACIÓN MANUAL
     * =====================================================
     */

    if (refreshButton) {

        refreshButton.addEventListener(
            'click',
            function () {

                refreshBoard();
            }
        );
    }


    /**
     * =====================================================
     * PANTALLA COMPLETA
     * =====================================================
     */

    if (fullscreenButton) {

        fullscreenButton.addEventListener(
            'click',
            async function () {

                try {

                    if (
                        !document.fullscreenElement
                    ) {

                        await document
                            .documentElement
                            .requestFullscreen();

                    } else {

                        await document
                            .exitFullscreen();
                    }

                } catch (error) {

                    console.error(
                        'No se pudo activar pantalla completa:',
                        error
                    );
                }
            }
        );


        document.addEventListener(
            'fullscreenchange',
            function () {

                fullscreenButton.textContent =
                    document.fullscreenElement
                        ? 'Salir de pantalla completa'
                        : 'Pantalla completa';
            }
        );
    }


    /**
     * =====================================================
     * ACTUALIZACIÓN AUTOMÁTICA
     * =====================================================
     */

    const interval =
        Number(
            AECOrderBoard.interval
        )
        ||
        15000;


    window.setInterval(
        refreshBoard,
        interval
    );


    /**
     * Actualizar cuando el usuario regresa
     * a la pestaña.
     */

    document.addEventListener(
        'visibilitychange',
        function () {

            if (
                document.visibilityState
                === 'visible'
            ) {

                refreshBoard();
            }
        }
    );


    /**
     * Estado inicial.
     */

    updateActiveCount();


    refreshBoard();

});
