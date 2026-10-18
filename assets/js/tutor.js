/**
 * Reloj y corte por inactividad del tutor con avatar.
 *
 * Es la segunda de las tres capas que cierran una conversación:
 *   1. Tavus corta por duración máxima y por ausencia del participante.
 *   2. Esto: avisa 30 s antes del límite y cierra al llegar.
 *   3. El barrido del servidor cierra lo que quede vivo sin latidos.
 *
 * Lo que aquí se mide es la interacción con la página. Mientras el foco está
 * dentro de la videollamada no llegan eventos del ratón, así que el aviso con
 * el botón "Sigo aquí" es lo que evita cortarle la palabra a quien sí está
 * hablando. El silencio dentro de la llamada lo detecta Tavus.
 */
(function () {
    var box = document.getElementById('tutor-live');
    if (!box) return;

    var API = box.dataset.api;
    var ID = box.dataset.id;
    var CSRF = box.dataset.csrf;
    var IDLE_LIMIT = parseInt(box.dataset.idleLimit, 10) || 120;
    var WARN_AT = Math.max(20, IDLE_LIMIT - 30);     // aviso 30 s antes de cerrar
    var BEAT_EVERY = 15;                             // segundos entre latidos

    var remaining = parseInt(box.dataset.remaining, 10) || 0;
    var lastHuman = Date.now();
    var closing = false;
    var tick = 0;

    var clock = document.getElementById('tutor-clock');
    var idleBox = document.getElementById('tutor-idle');
    var idleCount = document.getElementById('tutor-idle-count');
    var stay = document.getElementById('tutor-stay');

    function mmss(s) {
        s = Math.max(0, Math.floor(s));
        return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
    }

    function interacted() {
        lastHuman = Date.now();
        if (idleBox && !idleBox.hidden) idleBox.hidden = true;
    }

    ['mousemove', 'mousedown', 'keydown', 'touchstart', 'wheel', 'click'].forEach(function (ev) {
        document.addEventListener(ev, interacted, { passive: true });
    });
    // Pasar el foco al marco de la llamada cuenta como interacción.
    window.addEventListener('blur', interacted);
    window.addEventListener('focus', interacted);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') interacted();
    });
    if (stay) stay.addEventListener('click', interacted);

    // Si el docente usa el botón "Terminar", el cierre lo hace el formulario:
    // así el motivo registrado es el suyo y no "cerró la pestaña".
    var endForm = box.querySelector('form.tutor-end');
    if (endForm) endForm.addEventListener('submit', function () { closing = true; });

    function send(data, useBeacon) {
        var body = new URLSearchParams(Object.assign({ id: ID, csrf: CSRF }, data));
        if (useBeacon && navigator.sendBeacon) {
            navigator.sendBeacon(API, body);
            return Promise.resolve(null);
        }
        return fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
            keepalive: true
        }).then(function (r) { return r.ok ? r.json() : null; }).catch(function () { return null; });
    }

    function finish(message) {
        if (closing) return;
        closing = true;
        if (idleBox) idleBox.hidden = true;
        var frame = box.querySelector('.tutor-frame');
        if (frame) {
            frame.innerHTML = '<div class="tutor-closed"><strong>' + message + '</strong>' +
                '<p class="small">Recargando para mostrarte el resumen…</p></div>';
        }
        setTimeout(function () { window.location.reload(); }, 1800);
    }

    function apply(state) {
        if (!state) return;
        if (typeof state.remaining === 'number' && state.remaining >= 0) remaining = state.remaining;
        if (state.closed) {
            finish(state.reason === 'inactividad'
                ? 'Cerramos la conversación por inactividad.'
                : 'La conversación terminó.');
        }
    }

    setInterval(function () {
        if (closing) return;
        tick++;
        var idle = Math.floor((Date.now() - lastHuman) / 1000);

        remaining = Math.max(0, remaining - 1);
        if (clock) clock.textContent = mmss(remaining);

        // Aviso antes de cortar: 30 s con cuenta atrás visible.
        if (idleBox) {
            if (idle >= WARN_AT && idle < IDLE_LIMIT) {
                idleBox.hidden = false;
                if (idleCount) idleCount.textContent = String(IDLE_LIMIT - idle);
            } else if (idle < WARN_AT && !idleBox.hidden) {
                idleBox.hidden = true;
            }
        }

        if (idle >= IDLE_LIMIT) {
            send({ action: 'end', reason: 'inactividad' }).then(apply);
            finish('Cerramos la conversación por inactividad.');
            return;
        }
        if (remaining <= 0) {
            send({ action: 'end', reason: 'tope_tiempo' }).then(apply);
            finish('Se agotó el tiempo de la conversación.');
            return;
        }
        if (tick % BEAT_EVERY === 0) {
            send({ action: 'beat', idle: idle }).then(apply);
        }
    }, 1000);

    // Si se cierra la pestaña, no dejamos la conversación cobrándose sola.
    window.addEventListener('pagehide', function () {
        if (!closing) send({ action: 'end', reason: 'cerro_pestana' }, true);
    });
})();
