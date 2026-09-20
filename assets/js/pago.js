/* Pantalla de cobro propia – Checkout API de Mercado Pago.
 * Los datos de la tarjeta se capturan en Secure Fields (iframes de Mercado Pago)
 * y se convierten en un token de un solo uso; nunca llegan a nuestro servidor. */
(function () {
    'use strict';
    var cfg = window.PAY;
    var alertBox = document.getElementById('pay-alert');
    var processing = document.getElementById('processing-modal');
    var processingText = document.getElementById('processing-text');

    function showAlert(msg, type) {
        alertBox.className = 'pay-alert alert alert-' + (type || 'error');
        alertBox.textContent = msg;
        alertBox.hidden = false;
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    function hideAlert() { alertBox.hidden = true; }
    function busy(on, text) {
        processing.hidden = !on;
        if (text) processingText.textContent = text;
        document.querySelectorAll('.pay-btn').forEach(function (b) { b.disabled = on; });
    }
    function deviceId() { return window.MP_DEVICE_SESSION_ID || ''; }

    function post(data) {
        data.append('_csrf', cfg.csrf);
        data.append('ref', cfg.ref);
        data.append('device_id', deviceId());
        return fetch(cfg.process, { method: 'POST', body: data, credentials: 'same-origin' })
            .then(function (r) { return r.json().catch(function () { return { ok: false, message: 'Respuesta inválida del servidor.' }; }); });
    }

    function handleResult(res) {
        if (res.redirect && (res.ok || res.status === 'pending')) {
            busy(true, res.status === 'approved' ? '¡Pago aprobado! Redirigiendo…' : 'Redirigiendo…');
            window.location.href = res.redirect;
            return;
        }
        if (res.status === 'challenge' && res.three_ds) {
            busy(false);
            openChallenge(res.three_ds.url, res.three_ds.creq);
            return;
        }
        busy(false);
        showAlert(res.message || 'No pudimos procesar el pago.');
    }

    // ---------------------------------------------------------------------
    // Pestañas de medio de pago
    // ---------------------------------------------------------------------
    var tabs = document.querySelectorAll('.method-tab');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            document.querySelectorAll('.pay-form').forEach(function (f) {
                f.hidden = f.getAttribute('data-panel') !== tab.getAttribute('data-method');
            });
            hideAlert();
        });
    });

    if (typeof MercadoPago === 'undefined') {
        showAlert('No se pudo cargar el módulo de pagos. Revisa tu conexión y recarga la página.');
        return;
    }
    var mp = new MercadoPago(cfg.publicKey, { locale: 'es-CO' });

    // ---------------------------------------------------------------------
    // Tarjeta: Secure Fields
    // ---------------------------------------------------------------------
    var style = { fontSize: '15px', color: '#1B1036', placeholderColor: '#9A93B5' };
    var cardNumber = mp.fields.create('cardNumber', { placeholder: '1234 5678 9012 3456', style: style }).mount('mp-card-number');
    var expiration = mp.fields.create('expirationDate', { placeholder: 'MM/AA', style: style }).mount('mp-expiration');
    var cvv = mp.fields.create('securityCode', { placeholder: '123', style: style }).mount('mp-cvv');

    var fieldBox = { cardNumber: 'mp-card-number', expirationDate: 'mp-expiration', securityCode: 'mp-cvv' };
    var fieldMsg = {
        cardNumber: 'Número de tarjeta inválido.',
        expirationDate: 'Fecha de vencimiento inválida.',
        securityCode: 'Código de seguridad inválido.'
    };
    [[cardNumber, 'cardNumber'], [expiration, 'expirationDate'], [cvv, 'securityCode']].forEach(function (pair) {
        var el = document.getElementById(fieldBox[pair[1]]);
        var err = document.querySelector('[data-error="' + pair[1] + '"]');
        pair[0].on('focus', function () { el.classList.add('focused'); });
        pair[0].on('blur', function () { el.classList.remove('focused'); });
        pair[0].on('validityChange', function (ev) {
            var invalid = ev.errorMessages && ev.errorMessages.length > 0;
            el.classList.toggle('invalid', invalid);
            err.textContent = invalid ? fieldMsg[pair[1]] : '';
        });
    });

    var state = { bin: null, pm: null };
    var brandImg = document.getElementById('card-brand');
    var cpNumber = document.getElementById('cp-number');
    var issuerWrap = document.getElementById('issuer-wrap');
    var issuerSel = document.getElementById('issuer');
    var instSel = document.getElementById('installments');
    var instWrap = document.getElementById('installments-wrap');

    function resetCard() {
        state.bin = null; state.pm = null;
        brandImg.hidden = true;
        cpNumber.textContent = '•••• •••• •••• ••••';
        issuerWrap.hidden = true; issuerSel.innerHTML = '';
        instSel.innerHTML = '<option value="1">Escribe el número de la tarjeta</option>';
        instSel.disabled = true;
    }

    function fillSelect(sel, items, valueKey, textFn) {
        sel.innerHTML = '';
        items.forEach(function (it) {
            var o = document.createElement('option');
            o.value = it[valueKey];
            o.textContent = textFn(it);
            sel.appendChild(o);
        });
    }

    function loadInstallments() {
        if (!state.pm) return;
        if (state.pm.payment_type_id !== 'credit_card') {
            fillSelect(instSel, [{ installments: 1 }], 'installments', function () { return '1 pago (débito)'; });
            instSel.disabled = true;
            return;
        }
        instSel.disabled = true;
        instSel.innerHTML = '<option>Cargando cuotas…</option>';
        var params = { amount: cfg.amount, bin: state.bin, paymentTypeId: 'credit_card' };
        mp.getInstallments(params).then(function (res) {
            var costs = (res && res[0] && res[0].payer_costs) || [{ installments: 1, recommended_message: '1 cuota' }];
            fillSelect(instSel, costs, 'installments', function (c) { return c.recommended_message || (c.installments + ' cuotas'); });
            instSel.disabled = false;
        }).catch(function () {
            fillSelect(instSel, [{ installments: 1 }], 'installments', function () { return '1 cuota'; });
            instSel.disabled = false;
        });
    }

    cardNumber.on('binChange', function (ev) {
        var bin = ev && ev.bin;
        if (!bin) { resetCard(); return; }
        if (bin === state.bin) return;
        state.bin = bin;
        cpNumber.textContent = bin.slice(0, 4) + ' ' + bin.slice(4, 6) + '•• •••• ••••';
        mp.getPaymentMethods({ bin: bin }).then(function (res) {
            var pm = res && res.results && res.results[0];
            if (!pm) { showAlert('No reconocemos esta tarjeta. Revisa el número o usa otra.'); return; }
            hideAlert();
            state.pm = pm;
            if (pm.secure_thumbnail || pm.thumbnail) {
                brandImg.src = pm.secure_thumbnail || pm.thumbnail;
                brandImg.alt = pm.name || pm.id;
                brandImg.hidden = false;
            }
            var settings = pm.settings && pm.settings[0];
            if (settings) {
                if (settings.card_number) cardNumber.update({ settings: settings.card_number });
                if (settings.security_code) cvv.update({ settings: settings.security_code });
            }
            var needsIssuer = (pm.additional_info_needed || []).indexOf('issuer_id') !== -1;
            if (needsIssuer) {
                mp.getIssuers({ paymentMethodId: pm.id, bin: bin }).then(function (issuers) {
                    fillSelect(issuerSel, issuers || [], 'id', function (i) { return i.name; });
                    issuerWrap.hidden = !(issuers && issuers.length > 1);
                });
            } else if (pm.issuer) {
                fillSelect(issuerSel, [pm.issuer], 'id', function (i) { return i.name; });
                issuerWrap.hidden = true;
            }
            loadInstallments();
        }).catch(function () {
            showAlert('No pudimos validar la tarjeta. Inténtalo de nuevo.');
        });
    });

    var holder = document.getElementById('cardholder');
    holder.addEventListener('input', function () {
        document.getElementById('cp-name').textContent = holder.value.toUpperCase() || 'NOMBRE DEL TITULAR';
    });

    document.getElementById('form-card').addEventListener('submit', function (e) {
        e.preventDefault();
        hideAlert();
        var form = e.target;
        if (!state.pm) { showAlert('Escribe un número de tarjeta válido.'); return; }
        if (holder.value.trim().length < 3) { showAlert('Escribe el nombre del titular.'); holder.focus(); return; }
        var docNumber = form.doc_number.value.trim();
        if (docNumber.length < 5) { showAlert('Escribe el documento del titular.'); form.doc_number.focus(); return; }

        busy(true, 'Procesando tu pago…');
        mp.fields.createCardToken({
            cardholderName: holder.value.trim(),
            identificationType: form.doc_type.value,
            identificationNumber: docNumber
        }).then(function (token) {
            var data = new FormData();
            data.append('method', 'card');
            data.append('token', token.id);
            data.append('payment_method_id', state.pm.id);
            data.append('issuer_id', issuerSel.value || '');
            data.append('installments', instSel.value || '1');
            data.append('cardholder', holder.value.trim());
            data.append('doc_type', form.doc_type.value);
            data.append('doc_number', docNumber);
            data.append('email', form.email.value.trim());
            return post(data);
        }).then(function (res) {
            if (res) handleResult(res);
        }).catch(function (err) {
            busy(false);
            var msg = 'Revisa los datos de la tarjeta.';
            if (Array.isArray(err) && err[0] && err[0].field) {
                msg = fieldMsg[err[0].field] || msg;
            }
            showAlert(msg);
        });
    });

    // ---------------------------------------------------------------------
    // 3-D Secure: el banco pide confirmar la compra dentro de un iframe
    // ---------------------------------------------------------------------
    var modal = document.getElementById('threeds-modal');
    function openChallenge(url, creq) {
        modal.hidden = false;
        var f = document.createElement('form');
        f.method = 'POST';
        f.action = url;
        f.target = 'threeds-frame';
        var i = document.createElement('input');
        i.type = 'hidden'; i.name = 'creq'; i.value = creq;
        f.appendChild(i);
        document.body.appendChild(f);
        f.submit();
        f.remove();
        pollChallenge(0);
    }
    function pollChallenge(n) {
        if (n > 100) {
            modal.hidden = true;
            showAlert('La verificación tardó demasiado. Si confirmaste la compra, revisa tus pedidos en unos minutos.', 'warning');
            return;
        }
        setTimeout(function () {
            fetch(cfg.status + '?flash=1&ref=' + encodeURIComponent(cfg.ref), { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.status === 'approved' && d.redirect) {
                        modal.hidden = true; busy(true, '¡Pago aprobado! Redirigiendo…');
                        window.location.href = d.redirect;
                    } else if (d.status === 'rejected' || d.status === 'cancelled') {
                        modal.hidden = true; showAlert(d.message || 'El pago fue rechazado.');
                    } else if (d.detail && d.detail !== 'pending_challenge') {
                        modal.hidden = true;
                        window.location.href = cfg.process.replace('procesar.php', 'resultado.php') + '?ref=' + encodeURIComponent(cfg.ref);
                    } else {
                        pollChallenge(n + 1);
                    }
                })
                .catch(function () { pollChallenge(n + 1); });
        }, 3000);
    }

    // ---------------------------------------------------------------------
    // PSE y Efecty
    // ---------------------------------------------------------------------
    ['pse', 'efecty'].forEach(function (method) {
        var form = document.getElementById('form-' + method);
        if (!form) return;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            hideAlert();
            if (!form.checkValidity()) { form.reportValidity(); return; }
            busy(true, method === 'pse' ? 'Conectando con tu banco…' : 'Generando tu cupón…');
            var data = new FormData(form);
            data.append('method', method);
            post(data).then(handleResult).catch(function () {
                busy(false);
                showAlert('No pudimos conectar con el servidor. Inténtalo de nuevo.');
            });
        });
    });
})();
