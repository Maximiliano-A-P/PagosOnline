/*
 * Desplegable de sugerencias para los buscadores de los listados.
 *
 * Mismo comportamiento que el autocompletado del nombre de cliente en
 * invoices/create: mientras se escribe aparece una lista debajo del
 * campo con lo que coincide; se navega con flechas, Enter elige,
 * Escape cierra.
 *
 * Diferencias con "create": al elegir una sugerencia se completa el
 * campo y se envía el formulario (así el listado queda filtrado), y
 * si se aprieta Enter sin elegir nada se envía lo escrito, igual que
 * el botón "Buscar".
 *
 * Uso:
 *   searchDropdown({
 *       inputId: 'search',
 *       listId: 'searchList',
 *       sources: [{
 *           url: '/ruta/json',                       // devuelve [] de objetos
 *           value:  function (item) { return ... },  // texto que queda en el campo
 *           render: function (item, li) { ... },     // cómo se dibuja cada sugerencia
 *       }],
 *   });
 *
 * Opciones opcionales:
 *   onPick: function (item) { ... }  // si se pasa, al elegir NO se envía
 *                                    // el formulario; se llama a esta función.
 *   onType: function () { ... }      // se llama cada vez que se escribe.
 *
 * Cada llamada crea su propio estado (temporizador, contador de pedidos,
 * AbortController, lista): nada se comparte entre buscadores, y una
 * respuesta vieja nunca pisa a una más nueva.
 */
(function () {
    'use strict';

    window.searchDropdown = function (options) {

        const input = document.getElementById(options.inputId);
        const list  = document.getElementById(options.listId);

        if (!input || !list) {
            return;
        }

        const form    = input.form;
        const sources = options.sources || [];
        const delay   = options.delay || 250;

        let timer      = null;
        let controller = null;
        let seq        = 0;
        let entries    = [];
        let active     = -1;

        function close() {
            list.hidden = true;
            list.innerHTML = '';
            entries = [];
            active = -1;
        }

        function cancelPending() {
            clearTimeout(timer);
            seq++;

            if (controller) {
                controller.abort();
                controller = null;
            }
        }

        function highlight() {
            Array.from(list.children).forEach(function (li, i) {
                li.classList.toggle('is-active', i === active);
            });

            if (active >= 0 && list.children[active]) {
                list.children[active].scrollIntoView({ block: 'nearest' });
            }
        }

        function pick(entry) {
            cancelPending();

            input.value = entry.source.value(entry.item);
            close();

            // Con onPick no se envía el formulario: lo maneja quien llama.
            if (typeof options.onPick === 'function') {
                options.onPick(entry.item);
                return;
            }

            if (form) {
                form.submit();
            }
        }

        function paint() {
            list.innerHTML = '';

            entries.forEach(function (entry) {
                const li = document.createElement('li');
                li.className = 'ac-item';
                entry.source.render(entry.item, li);

                // mousedown (no click): evita que el blur cierre la
                // lista antes de registrar la elección.
                li.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    pick(entry);
                });

                list.appendChild(li);
            });

            list.hidden = entries.length === 0;
        }

        async function fetchSource(source, term, signal) {
            try {
                const response = await fetch(
                    source.url + '?q=' + encodeURIComponent(term),
                    {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        signal: signal,
                    }
                );

                if (!response.ok) {
                    return [];
                }

                const data = await response.json();

                return Array.isArray(data)
                    ? data.map(function (item) {
                        return { source: source, item: item };
                    })
                    : [];

            } catch (error) {
                if (error.name === 'AbortError') {
                    throw error;
                }

                return [];
            }
        }

        async function search() {
            const term = input.value.trim();

            if (term === '') {
                close();
                return;
            }

            if (controller) {
                controller.abort();
            }

            controller = new AbortController();
            const mySeq = ++seq;

            try {
                const groups = await Promise.all(
                    sources.map(function (source) {
                        return fetchSource(source, term, controller.signal);
                    })
                );

                // Respuesta vieja: se descarta.
                if (mySeq !== seq) {
                    return;
                }

                entries = [].concat.apply([], groups);
                active = -1;
                paint();

            } catch (error) {
                if (error.name !== 'AbortError' && mySeq === seq) {
                    close();
                }
            }
        }

        input.addEventListener('input', function () {
            if (typeof options.onType === 'function') {
                options.onType();
            }

            clearTimeout(timer);
            timer = setTimeout(search, delay);
        });

        input.addEventListener('keydown', function (e) {
            if (list.hidden) {
                return;
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                active = (active + 1) % entries.length;
                highlight();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                active = (active - 1 + entries.length) % entries.length;
                highlight();
            } else if (e.key === 'Enter' && active >= 0) {
                e.preventDefault();
                pick(entries[active]);
            } else if (e.key === 'Escape') {
                cancelPending();
                close();
            }
        });

        input.addEventListener('blur', function () {
            cancelPending();
            close();
        });

        // Si se envía el formulario con el botón o con Enter sin elegir
        // nada, se cancela lo pendiente y se envía lo escrito.
        if (form) {
            form.addEventListener('submit', function () {
                cancelPending();
                close();
            });
        }
    };
})();