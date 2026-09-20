(function () {
    'use strict';
    document.documentElement.classList.remove('no-js');

    // Header con sombra al hacer scroll
    var header = document.querySelector('.site-header');
    if (header) {
        var onScroll = function () { header.classList.toggle('scrolled', window.scrollY > 8); };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    // Menú móvil
    var toggle = document.querySelector('.nav-toggle');
    if (toggle) {
        toggle.addEventListener('click', function () {
            var open = document.body.classList.toggle('nav-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.querySelectorAll('.nav-menu a').forEach(function (a) {
            a.addEventListener('click', function () {
                document.body.classList.remove('nav-open');
                toggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

    // Dropdown (click en móvil / teclado)
    document.querySelectorAll('.has-dropdown > .nav-link').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var li = btn.parentElement;
            var open = li.classList.toggle('open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });
    document.addEventListener('click', function (e) {
        document.querySelectorAll('.has-dropdown.open').forEach(function (li) {
            if (!li.contains(e.target)) li.classList.remove('open');
        });
    });

    // Acordeones
    document.querySelectorAll('[data-accordion]').forEach(function (acc) {
        acc.querySelectorAll('.acc-head').forEach(function (head) {
            head.setAttribute('aria-expanded', head.parentElement.classList.contains('open') ? 'true' : 'false');
            head.addEventListener('click', function () {
                var item = head.parentElement;
                var wasOpen = item.classList.contains('open');
                acc.querySelectorAll('.acc-item').forEach(function (i) {
                    i.classList.remove('open');
                    i.querySelector('.acc-head').setAttribute('aria-expanded', 'false');
                });
                if (!wasOpen) {
                    item.classList.add('open');
                    head.setAttribute('aria-expanded', 'true');
                }
            });
        });
    });

    // Filtro de herramientas
    var tabs = document.querySelectorAll('.tool-tabs .tab');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            var f = tab.getAttribute('data-filter');
            document.querySelectorAll('.tool-card').forEach(function (card) {
                card.classList.toggle('hide', f !== 'all' && card.getAttribute('data-cat') !== f);
            });
        });
    });

    // Animaciones de aparición
    var reveals = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) {
                    en.target.classList.add('in');
                    io.unobserve(en.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        reveals.forEach(function (el) { io.observe(el); });
    } else {
        reveals.forEach(function (el) { el.classList.add('in'); });
    }

    // Contadores
    var counters = document.querySelectorAll('[data-count]');
    if (counters.length && 'IntersectionObserver' in window) {
        var co = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (!en.isIntersecting) return;
                var el = en.target, target = parseInt(el.getAttribute('data-count'), 10), start = null;
                var step = function (ts) {
                    if (!start) start = ts;
                    var p = Math.min((ts - start) / 1200, 1);
                    el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3)));
                    if (p < 1) requestAnimationFrame(step);
                };
                requestAnimationFrame(step);
                co.unobserve(el);
            });
        }, { threshold: 0.5 });
        counters.forEach(function (c) { co.observe(c); });
    } else {
        counters.forEach(function (c) { c.textContent = c.getAttribute('data-count'); });
    }

    // Confirmaciones
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('submit', function (e) {
            if (!window.confirm(el.getAttribute('data-confirm'))) e.preventDefault();
        });
    });

    // Copiar al portapapeles
    document.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var text = btn.getAttribute('data-copy');
            var done = function () {
                var old = btn.textContent;
                btn.textContent = '¡Copiado!';
                setTimeout(function () { btn.textContent = old; }, 1500);
            };
            if (navigator.clipboard) navigator.clipboard.writeText(text).then(done);
            else {
                var ta = document.createElement('textarea');
                ta.value = text; document.body.appendChild(ta); ta.select();
                document.execCommand('copy'); ta.remove(); done();
            }
        });
    });

    // Resumen dinámico en checkout
    var planInputs = document.querySelectorAll('input[name="plan"]');
    if (planInputs.length) {
        var update = function () {
            var sel = document.querySelector('input[name="plan"]:checked');
            if (!sel) return;
            ['name', 'price', 'seats', 'months'].forEach(function (k) {
                document.querySelectorAll('[data-sum="' + k + '"]').forEach(function (n) {
                    n.textContent = sel.getAttribute('data-' + k);
                });
            });
        };
        planInputs.forEach(function (i) { i.addEventListener('change', update); });
        update();
    }

    // Pestañas del portal (agregar individual / masivo)
    document.querySelectorAll('[data-tabs]').forEach(function (wrap) {
        var btns = wrap.querySelectorAll('[data-tab]');
        btns.forEach(function (b) {
            b.addEventListener('click', function () {
                btns.forEach(function (x) { x.classList.remove('active'); });
                b.classList.add('active');
                wrap.querySelectorAll('[data-panel]').forEach(function (p) {
                    p.hidden = p.getAttribute('data-panel') !== b.getAttribute('data-tab');
                });
            });
        });
    });

    // Buscador de miembros
    var memberSearch = document.getElementById('member-search');
    if (memberSearch) {
        memberSearch.addEventListener('input', function () {
            var q = memberSearch.value.trim().toLowerCase();
            document.querySelectorAll('[data-member]').forEach(function (row) {
                row.hidden = q !== '' && row.getAttribute('data-member').indexOf(q) === -1;
            });
        });
    }
})();
