/* Herramientas de IA: espera del resultado y copia del documento */
(function () {
    'use strict';

    // Mientras el modelo escribe, se consulta el estado hasta que termina
    var box = document.querySelector('[data-poll]');
    if (box) {
        var url = box.getAttribute('data-poll');
        var intentos = 0;
        var consultar = function () {
            intentos++;
            fetch(url, { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.status !== 'pending' && d.redirect) { window.location.href = d.redirect; return; }
                    if (intentos < 90) { setTimeout(consultar, intentos < 10 ? 2000 : 4000); }
                })
                .catch(function () { if (intentos < 90) setTimeout(consultar, 5000); });
        };
        setTimeout(consultar, 2500);
    }

    // Copiar el documento como texto plano
    var btn = document.querySelector('[data-copy-doc]');
    var doc = document.getElementById('doc');
    if (btn && doc) {
        btn.addEventListener('click', function () {
            var texto = doc.innerText.trim();
            var listo = function () {
                var antes = btn.textContent;
                btn.textContent = '¡Copiado!';
                setTimeout(function () { btn.textContent = antes; }, 1600);
            };
            if (navigator.clipboard) { navigator.clipboard.writeText(texto).then(listo); }
            else {
                var ta = document.createElement('textarea');
                ta.value = texto; document.body.appendChild(ta); ta.select();
                document.execCommand('copy'); ta.remove(); listo();
            }
        });
    }
})();

/**
 * Ayuda de IA campo por campo.
 *
 * Cada campo de texto libre tiene dos botones: redactar a partir de unos
 * apuntes del docente o pulir lo que ya escribió. El texto anterior se guarda
 * para que "Deshacer" siempre devuelva lo que había, que es lo que da
 * confianza para probar.
 */
(function () {
    var form = document.querySelector('.tool-form[data-assist]');
    if (!form) return;

    var URL_ASISTIR = form.dataset.assist;
    var CSRF = form.dataset.csrf;
    var TOOL = (form.querySelector('input[name="h"]') || {}).value || '';

    form.querySelectorAll('.field-ai').forEach(function (caja) {
        var nombre = caja.dataset.field;
        var campo = form.querySelector('[name="' + nombre + '"]');
        if (!campo) return;

        var estado = caja.querySelector('.ai-state');
        var undo = caja.querySelector('.ai-undo');
        var notas = caja.querySelector('.ai-notes');
        var notasInput = caja.querySelector('.ai-notes-input');
        var chips = caja.querySelectorAll('.ai-chip');
        var anterior = null;

        function ocupado(si, mensaje) {
            chips.forEach(function (c) { c.disabled = si; });
            estado.textContent = mensaje || '';
            estado.className = 'ai-state' + (mensaje ? ' is-on' : '');
        }

        function aviso(texto, error) {
            estado.textContent = texto;
            estado.className = 'ai-state is-on' + (error ? ' is-error' : '');
            if (!error) setTimeout(function () { if (estado.textContent === texto) ocupado(false, ''); }, 4000);
        }

        // Los demás campos viajan como contexto: así la ayuda habla del mismo estudiante.
        function datos(modo, texto) {
            var fd = new FormData(form);
            fd.set('csrf', CSRF);
            fd.set('h', TOOL);
            fd.set('campo', nombre);
            fd.set('modo', modo);
            fd.set('texto', texto);
            return fd;
        }

        function pedir(modo, texto) {
            ocupado(true, modo === 'crear' ? 'Redactando…' : 'Puliendo el texto…');
            fetch(URL_ASISTIR, { method: 'POST', body: datos(modo, texto) })
                .then(function (r) { return r.json().catch(function () { return { ok: false, error: 'Respuesta inesperada del servidor.' }; }); })
                .then(function (r) {
                    ocupado(false, '');
                    if (!r || !r.ok) { aviso((r && r.error) || 'No pudimos generar la ayuda.', true); return; }
                    anterior = campo.value;
                    campo.value = r.texto;
                    campo.dispatchEvent(new Event('input', { bubbles: true }));
                    undo.hidden = false;
                    notas.hidden = true;
                    aviso('Listo. Revísalo y ajústalo a tu caso.');
                })
                .catch(function () { ocupado(false, ''); aviso('No hubo conexión con el servidor.', true); });
        }

        caja.querySelectorAll('[data-ai]').forEach(function (boton) {
            boton.addEventListener('click', function () {
                var modo = boton.dataset.ai;
                if (modo === 'mejorar') {
                    if (!campo.value.trim()) { aviso('Escribe algo primero y lo pulo.', true); campo.focus(); return; }
                    pedir('mejorar', campo.value);
                    return;
                }
                // Redactar: se piden apuntes, con lo ya escrito como punto de partida.
                notas.hidden = !notas.hidden;
                if (!notas.hidden) {
                    if (!notasInput.value.trim() && campo.value.trim()) notasInput.value = campo.value.trim();
                    notasInput.focus();
                }
            });
        });

        caja.querySelector('.ai-notes-go').addEventListener('click', function () {
            var apuntes = notasInput.value.trim();
            if (!apuntes) { notasInput.focus(); return; }
            pedir('crear', apuntes);
        });
        caja.querySelector('.ai-notes-cancel').addEventListener('click', function () { notas.hidden = true; });
        notasInput.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter' && (ev.ctrlKey || ev.metaKey)) caja.querySelector('.ai-notes-go').click();
        });

        undo.addEventListener('click', function () {
            if (anterior === null) return;
            campo.value = anterior;
            anterior = null;
            undo.hidden = true;
            aviso('Volvimos a tu texto.');
        });
    });
})();
