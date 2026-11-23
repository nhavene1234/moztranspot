/* MozTransport — JavaScript simples, sem dependências obrigatórias. */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var fmt = function (n) { return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' MT'; };
  var pad = function (n) { return (n < 10 ? '0' : '') + n; };
  var esc = function (t) { var d = document.createElement('div'); d.textContent = (t === null || t === undefined) ? '' : String(t); return d.innerHTML; };

  /* confirmações */
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f.dataset && f.dataset.confirm && !window.confirm(f.dataset.confirm)) { e.preventDefault(); return; }
    if (f.dataset && f.dataset.processing !== undefined) {
      var o = $('#processando'); if (o) { o.classList.add('open'); }
    }
  });

  /* modais */
  document.addEventListener('click', function (e) {
    var a = e.target.closest('[data-modal-open]');
    if (a) {
      var m = document.getElementById(a.dataset.modalOpen);
      if (m) {
        m.classList.add('open');
        if (a.dataset.fill) { fillModal(m, JSON.parse(a.dataset.fill), a.dataset.action); }
        else if (m.dataset.reset) { resetModal(m); }
      }
      e.preventDefault(); return;
    }
    var c = e.target.closest('[data-modal-close]');
    if (c) { var mm = c.closest('.modal-bg'); if (mm) { mm.classList.remove('open'); } e.preventDefault(); return; }
    if (e.target.classList && e.target.classList.contains('modal-bg')) { e.target.classList.remove('open'); }
    var mb = e.target.closest('[data-menu]'); if (mb) { $('.sidebar').classList.toggle('open'); }
  });
  function fillModal(m, dados, action) {
    var f = $('form', m); if (!f) { return; }
    if (action) { f.action = action; }
    var met = $('input[name=_method]', f); if (met) { met.disabled = !dados._put; }
    Object.keys(dados).forEach(function (k) { var i = f.elements[k]; if (i) { i.value = dados[k]; } });
    var t = $('[data-title]', m); if (t && dados._titulo) { t.textContent = dados._titulo; }
  }
  function resetModal(m) {
    var f = $('form', m); if (!f) { return; }
    f.reset(); if (f.dataset.createAction) { f.action = f.dataset.createAction; }
    var met = $('input[name=_method]', f); if (met) { met.disabled = true; }
    var t = $('[data-title]', m); if (t && t.dataset.title) { t.textContent = t.dataset.title; }
  }
  $$('.modal-bg[data-autoopen]').forEach(function (m) { m.classList.add('open'); });

  /* mostrar/ocultar palavra-passe */
  $$('[data-toggle-pass]').forEach(function (b) {
    b.addEventListener('click', function () {
      var i = b.parentNode.querySelector('input'); var v = i.type === 'password';
      i.type = v ? 'text' : 'password'; b.textContent = v ? 'Ocultar' : 'Mostrar';
    });
  });

  /* força da palavra-passe */
  $$('[data-strength]').forEach(function (inp) {
    var alvo = $(inp.dataset.strength);
    inp.addEventListener('input', function () {
      var v = inp.value, s = 0;
      if (v.length >= 8) { s++; } if (/[a-z]/.test(v) && /[A-Z]/.test(v)) { s++; } if (/\d/.test(v)) { s++; } if (/[^A-Za-z0-9]/.test(v)) { s++; }
      $$('i', alvo).forEach(function (b, i) { b.classList.toggle('on', i < s); });
    });
  });

  /* contagem decrescente */
  $$('[data-countdown]').forEach(function (el) {
    var seg = parseInt(el.dataset.countdown, 10), pre = el.dataset.prefix || '';
    function tick() {
      if (seg <= 0) {
        el.textContent = pre + '00:00';
        if (el.dataset.onend === 'reload') { window.location.reload(); }
        if (el.dataset.enable) { var b = $(el.dataset.enable); if (b) { b.disabled = false; b.classList.remove('disabled'); } }
        return;
      }
      el.textContent = pre + pad(Math.floor(seg / 60)) + ':' + pad(seg % 60); seg--; setTimeout(tick, 1000);
    }
    tick();
  });

  /* mapa de lugares: resumo da selecção */
  $$('[data-seatmap]').forEach(function (map) {
    var preco = parseFloat(map.dataset.preco), multi = map.dataset.multi === '1';
    var lista = $(map.dataset.lista), total = $(map.dataset.total), btn = $(map.dataset.btn);
    function actualizar() {
      var sel = $$('input:checked', map).map(function (i) { return i.dataset.rotulo; });
      if (lista) { lista.textContent = sel.length ? sel.join(', ') : '—'; }
      if (total) { total.textContent = fmt(sel.length * preco); }
      if (btn) { btn.disabled = !sel.length; btn.classList.toggle('disabled', !sel.length); }
    }
    map.addEventListener('change', actualizar); actualizar();
    if (multi) {
      map.addEventListener('change', function (e) {
        if ($$('input:checked', map).length > 5) { e.target.checked = false; alert('No máximo 5 lugares por venda.'); actualizar(); }
      });
    }
  });

  /* lugares em tempo real (bilheteiro) */
  $$('[data-live-seats]').forEach(function (map) {
    var url = map.dataset.liveSeats, info = $(map.dataset.info);
    setInterval(function () {
      fetch(url, { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) {
        $$('.seat', map).forEach(function (s) { s.classList.toggle('occ', d.ocupados.indexOf(parseInt(s.dataset.id, 10)) !== -1); });
        if (info) { info.textContent = (d.total - d.livres) + ' ocupados · ' + d.livres + ' livres'; }
      }).catch(function () {});
    }, 15000);
  });

  /* origem -> destinos */
  $$('select[data-destinos]').forEach(function (orig) {
    var mapa = JSON.parse(orig.dataset.destinos), dest = $(orig.dataset.alvo), actual = dest.dataset.valor || '';
    function pop() {
      var l = mapa[orig.value] || [];
      dest.innerHTML = '';
      var vazio = document.createElement('option'); vazio.value = ''; vazio.textContent = 'Seleccione o destino'; dest.appendChild(vazio);
      l.forEach(function (d) { var o = document.createElement('option'); o.textContent = d; o.value = d; if (d === actual) { o.selected = true; } dest.appendChild(o); });
    }
    orig.addEventListener('change', function () { actual = ''; pop(); }); pop();
  });

  /* auto-submeter */
  $$('[data-autosubmit]').forEach(function (i) { i.addEventListener('change', function () { i.form.submit(); }); });

  /* QR code (usa a biblioteca qrcodejs se estiver carregada) */
  /* QR dos bilhetes: gerado no próprio navegador (biblioteca local, funciona sem internet) */
  var criarQr = function (texto) { if (!window.qrcode) { return null; } var q = window.qrcode(0, 'M'); q.addData(texto); q.make(); return q; };
  $$('[data-qr]').forEach(function (el) {
    var q = criarQr(el.dataset.qr), t = parseInt(el.dataset.size || '170', 10);
    if (q) {
      el.innerHTML = q.createSvgTag({ cellSize: 1, margin: 2, scalable: true });
      var svg = el.querySelector('svg'); if (svg) { svg.setAttribute('width', t); svg.setAttribute('height', t); svg.style.display = 'block'; svg.setAttribute('role', 'img'); svg.setAttribute('aria-label', 'QR code do bilhete'); }
    } else { el.innerHTML = '<div class="mono small muted" style="padding:20px;word-break:break-all">' + esc(el.dataset.qr) + '</div>'; }
  });
  $$('[data-qr-baixar]').forEach(function (b) {
    b.addEventListener('click', function () {
      var q = criarQr(b.dataset.qrBaixar); if (!q) { return; }
      var n = q.getModuleCount(), esc4 = 4, s = 8, W = (n + 2 * esc4) * s, c = document.createElement('canvas'); c.width = W; c.height = W;
      var x = c.getContext('2d'); x.fillStyle = '#fff'; x.fillRect(0, 0, W, W); x.fillStyle = '#000';
      for (var r = 0; r < n; r++) { for (var k = 0; k < n; k++) { if (q.isDark(r, k)) { x.fillRect((k + esc4) * s, (r + esc4) * s, s, s); } } }
      var a = document.createElement('a'); a.download = (b.dataset.ficheiro || 'bilhete') + '.png'; a.href = c.toDataURL('image/png'); document.body.appendChild(a); a.click(); a.remove();
    });
  });

  /* partilhar */
  $$('[data-share]').forEach(function (b) {
    b.addEventListener('click', function () {
      var d = { title: 'Bilhete MozTransport', text: b.dataset.share };
      if (navigator.share) { navigator.share(d).catch(function () {}); }
      else if (navigator.clipboard) { navigator.clipboard.writeText(b.dataset.share).then(function () { alert('Copiado: ' + b.dataset.share); }); }
      else { prompt('Copie o código:', b.dataset.share); }
    });
  });
  $$('[data-print]').forEach(function (b) { b.addEventListener('click', function () { window.print(); }); });

  /* gráfico de linha / área */
  $$('[data-linechart]').forEach(function (el) {
    var labels = JSON.parse(el.dataset.labels), vals = JSON.parse(el.dataset.values), W = 600, H = 190, pl = 6, pb = 20, pt = 10;
    var max = Math.max.apply(null, vals.concat([1])), n = vals.length;
    var x = function (i) { return pl + (n === 1 ? 0 : i * (W - pl * 2) / (n - 1)); };
    var y = function (v) { return pt + (H - pt - pb) * (1 - v / max); };
    var pts = vals.map(function (v, i) { return x(i).toFixed(1) + ',' + y(v).toFixed(1); }).join(' ');
    var svg = '<svg viewBox="0 0 ' + W + ' ' + H + '" preserveAspectRatio="none">';
    [0, .5, 1].forEach(function (g) { var yy = pt + (H - pt - pb) * g; svg += '<line x1="0" x2="' + W + '" y1="' + yy + '" y2="' + yy + '" stroke="#e2e8f0" stroke-dasharray="3 4"/>'; });
    svg += '<polygon points="' + x(0) + ',' + y(0) + ' ' + pts + ' ' + x(n - 1) + ',' + y(0) + '" fill="var(--accent)" opacity=".12"/>';
    svg += '<polyline points="' + pts + '" fill="none" stroke="var(--accent)" stroke-width="2.4" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>';
    var passo = Math.max(1, Math.ceil(n / 6));
    labels.forEach(function (l, i) { if (i % passo === 0) { svg += '<text x="' + x(i) + '" y="' + (H - 4) + '" text-anchor="middle">' + l + '</text>'; } });
    svg += '</svg>'; el.innerHTML = svg;
    el.title = 'Máximo: ' + fmt(max);
  });

  /* scanner de QR do cobrador */
  var sc = $('[data-scanner]');
  if (sc) {
    var video = $('video', sc), btn = $('#btn-camera'), saida = $('#resultado-scan'), url = sc.dataset.scanner, token = $('meta[name=csrf-token]').content, ocupado = false, stream = null;
    var mostrar = function (d) {
      saida.className = 'result ' + (d.ok ? 'ok' : 'no');
      saida.innerHTML = '<h2>' + (d.ok ? 'Bilhete válido' : 'Bilhete recusado') + '</h2><div>' + esc(d.mensagem) + '</div>' + (d.passageiro ? '<div class="small" style="margin-top:6px">' + esc(d.passageiro) + ' · lugar ' + esc(d.lugar) + '</div>' : '');
      saida.classList.remove('hidden'); if (d.ok) { setTimeout(function () { window.location.reload(); }, 1800); }
    };
    var enviar = function (codigo) {
      ocupado = true;
      fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify({ codigo: codigo, origem: 'qr' }) })
        .then(function (r) { if (!r.ok) { throw new Error(r.status); } return r.json(); }).then(mostrar).catch(function () { alert('Erro de ligação ou sessão expirada. Recarregue a página.'); })
        .then(function () { setTimeout(function () { ocupado = false; }, 2500); });
    };
    if (btn) {
      btn.addEventListener('click', function () {
        if (!navigator.mediaDevices || !window.jsQR) { alert('A câmara ou a biblioteca de leitura não está disponível. Use o código manual.'); return; }
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }).then(function (s) {
          stream = s; video.srcObject = s; video.setAttribute('playsinline', true); video.play(); video.classList.remove('hidden'); $('.placeholder', sc).classList.add('hidden');
          var canvas = document.createElement('canvas'), ctx = canvas.getContext('2d');
          (function loop() {
            if (!stream) { return; }
            if (video.readyState === video.HAVE_ENOUGH_DATA && !ocupado) {
              canvas.width = video.videoWidth; canvas.height = video.videoHeight; ctx.drawImage(video, 0, 0);
              var img = ctx.getImageData(0, 0, canvas.width, canvas.height), r = window.jsQR(img.data, img.width, img.height);
              if (r && r.data) { enviar(r.data); }
            }
            requestAnimationFrame(loop);
          })();
        }).catch(function () { alert('Não foi possível aceder à câmara.'); });
      });
    }
  }
})();
