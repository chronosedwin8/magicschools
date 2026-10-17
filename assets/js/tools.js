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
