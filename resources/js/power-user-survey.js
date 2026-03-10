/* global window, document, localStorage, crypto, fetch, grecaptcha */
(function () {
  function cfg() {
    var c = (window.PowerUserSurvey && window.PowerUserSurvey.config) ? window.PowerUserSurvey.config : null;
    if (!c) return null;

    // Backward-compatible key mapping for older payload shapes.
    if (!c.surveyBaseUrl && c.baseUrl) c.surveyBaseUrl = c.baseUrl;
    if (!c.surveyApiKey && c.apiKey) c.surveyApiKey = c.apiKey;

    return c;
  }

  function injectCssOnce() {
    if (document.getElementById('pus-css')) return;

    var style = document.createElement('style');
    style.id = 'pus-css';
    style.type = 'text/css';
    style.appendChild(document.createTextNode(`
      :root{
        --pus-green:#4A8075;
        --pus-green-2:#2f6f64;
        --pus-border:#e6e6e6;
        --pus-selected-bg:#e9f3f1;
        --pus-selected-border:#76a79e;
        --pus-text:#222;
        --pus-muted:#333;
      }
      .pus-overlay{
        position:fixed; inset:0;
        background:rgba(0,0,0,.55);
        display:flex;
        align-items:center;
        justify-content:center;
        z-index:99999;
      }
      .pus-card{
        position:relative;
        width:640px;
        max-width:92vw;
        background:#fff;
        border-radius:10px;
        box-shadow:0 20px 60px rgba(0,0,0,.35);
        overflow:hidden;
        font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
        color:var(--pus-text);
      }
      .pus-body{ padding:22px; }
      .pus-title{
        font-weight:700;
        font-size:18px;
        margin:0 0 10px 0;
      }
      .pus-copy{
        margin:0;
        font-size:15px;
        font-weight:400;
        line-height:1.45;
        color:var(--pus-text);
      }
      .pus-footer{
        padding:16px 22px;
        display:flex;
        gap:12px;
      }
      .pus-footer.pus-footer-single{ justify-content:flex-end; }
      .pus-footer.pus-footer-dual{ justify-content:space-between; }
      .pus-btn{
        border-radius:6px;
        height:44px;
        padding:0 18px;
        font-weight:700;
        font-size:13px;
        cursor:pointer;
        border:1px solid transparent;
        letter-spacing:.3px;
      }
      .pus-btn-primary{
        background:var(--pus-green);
        border-color:var(--pus-green);
        color:#fff;
      }
      .pus-btn-primary:hover{ background:var(--pus-green-2); border-color:var(--pus-green-2); }
      .pus-btn-outline{
        background:#fff;
        color:var(--pus-green);
        border-color:var(--pus-border);
      }
      .pus-btn-outline:hover{ border-color:var(--pus-selected-border); }
      .pus-btn-full{ width:100%; }
      .pus-list{ margin-top:10px; display:flex; flex-direction:column; gap:5px; font-size:16px; font-weight:400; }
      .pus-option{
        border:1px solid var(--pus-border);
        border-radius:6px;
        padding:14px;
        display:flex;
        align-items:center;
        gap:10px;
        cursor:pointer;
        background:#fff;
      }
      .pus-option input{ margin:0; }
      .pus-option.selected{
        background:var(--pus-selected-bg);
        border-color:var(--pus-selected-border);
      }
      .pus-input{
        width:100%;
        height:44px;
        border:1px solid var(--pus-border);
        border-radius:6px;
        padding:0 12px;
        font-size:16px;
        font-weight:400;
        outline:none;
        box-sizing:border-box;
      }
      .pus-input:focus{ border-color:var(--pus-selected-border); box-shadow:0 0 0 3px rgba(118,167,158,.18); }
      .pus-offer-box{
        margin-top:14px;
        border:1px solid var(--pus-border);
        background:var(--pus-selected-bg);
        border-radius:8px;
        padding:14px;
      }
      .pus-price-row{
        display:flex;
        justify-content: center;
        align-items:baseline;
        gap:10px;
      }
      .pus-was{
        text-decoration:line-through;
        color:#777;
        font-size:16px;
        font-weight:400;
      }
      .pus-now{
        font-weight:800;
        font-size:26px;
        color:var(--pus-green);
      }
      .pus-sub{
        margin-top:4px;
        color:var(--pus-muted);
        font-size:16px;
        font-weight:400;
        text-align:center;
      }
      .pus-recaptcha-wrap{ margin-top: 14px; display:flex; justify-content:center; }
      .pus-error{ margin-top:10px; font-size:12px; color:#b00020; display:none; }
      .pus-mt-2{ margin-top:8px; }
      @media(max-width:520px){
        .pus-footer{ flex-direction:column; }
        .pus-btn{ width:100%; }
      }
    `));
    document.head.appendChild(style);
  }

  function injectThemeOnce(c) {
    if (document.getElementById('pus-theme')) return;

    var t = (c && c.theme) ? c.theme : {};
    var primary = t.primary || '#4A8075';
    var hover = t.primary_hover || '#2f6f64';
    var selBg = t.selected_bg || '#e9f3f1';
    var selBorder = t.selected_border || '#76a79e';

    var style = document.createElement('style');
    style.id = 'pus-theme';
    style.type = 'text/css';
    style.appendChild(document.createTextNode(`
      :root{
        --pus-green:${primary};
        --pus-green-2:${hover};
        --pus-selected-bg:${selBg};
        --pus-selected-border:${selBorder};
      }
    `));
    document.head.appendChild(style);
  }

  function getDeviceId(key) {
    var id = localStorage.getItem(key);
    if (id && /^[0-9a-f]{40,128}$/.test(id)) return id;

    var arr = new Uint8Array(32);
    crypto.getRandomValues(arr);
    id = Array.from(arr).map(function (b) { return b.toString(16).padStart(2, '0'); }).join('');
    localStorage.setItem(key, id);
    return id;
  }

  function httpJson(url, method, headers, body) {
    return fetch(url, {
      method: method,
      headers: headers,
      body: body ? JSON.stringify(body) : undefined,
      credentials: 'same-origin'
    }).then(function (r) {
      if (!r.ok) return null;
      return r.json().catch(function () { return {}; });
    }).catch(function () { return null; });
  }

  function surveyCall(c, path, method, body) {
    var baseUrl = (c && c.surveyBaseUrl) ? String(c.surveyBaseUrl).replace(/\/$/, '') : '';
    if (!baseUrl) return Promise.resolve(null);

    return fetch(baseUrl + path, {
      method: method,
      headers: { 'Content-Type': 'application/json', 'X-Api-Key': c.surveyApiKey },
      body: body ? JSON.stringify(body) : undefined,
      credentials: 'omit'
    }).then(function (r) {
      if (!r.ok) return null;
      return r.json().catch(function () { return {}; });
    }).catch(function () { return null; });
  }

  function completed(c) { return localStorage.getItem(c.storage.completed) === '1'; }
  function setCompleted(c, v) { localStorage.setItem(c.storage.completed, v ? '1' : '0'); }
  function getRedirect(c) { return localStorage.getItem(c.storage.redirect_url) || ''; }
  function setRedirect(c, url) { if (url) localStorage.setItem(c.storage.redirect_url, url); }

  function progressKey(c) {
    return (c && c.storage && c.storage.progress) ? c.storage.progress : '_pus_progress';
  }

  function readProgress(c) {
    try {
      var raw = localStorage.getItem(progressKey(c));
      if (!raw) return {};
      var parsed = JSON.parse(raw);
      return (parsed && typeof parsed === 'object') ? parsed : {};
    } catch (e) {
      return {};
    }
  }

  function writeProgress(c, patch) {
    var current = readProgress(c);
    var next = Object.assign({}, current, patch || {});
    localStorage.setItem(progressKey(c), JSON.stringify(next));
  }

  function clearProgress(c) {
    localStorage.removeItem(progressKey(c));
  }

  function el(tag, attrs, children) {
    var n = document.createElement(tag);
    attrs = attrs || {};
    Object.keys(attrs).forEach(function (k) {
      if (k === 'class') n.className = attrs[k];
      else if (k === 'text') n.textContent = attrs[k];
      else if (k.startsWith('on') && typeof attrs[k] === 'function') n.addEventListener(k.slice(2), attrs[k]);
      else n.setAttribute(k, attrs[k]);
    });
    (children || []).forEach(function (c) { n.appendChild(c); });
    return n;
  }

  function mountOverlay() {
    var overlay = el('div', { class: 'pus-overlay' });
    var card = el('div', { class: 'pus-card' });
    var body = el('div', { class: 'pus-body' });
    var footer = el('div', { class: 'pus-footer pus-footer-single' });

    card.appendChild(body);
    card.appendChild(footer);
    overlay.appendChild(card);
    document.body.appendChild(overlay);

    function setFooterSingle(btn) {
      footer.className = 'pus-footer pus-footer-single';
      footer.innerHTML = '';
      footer.appendChild(btn);
    }
    function setFooterDual(btnLeft, btnRight) {
      footer.className = 'pus-footer pus-footer-dual';
      footer.innerHTML = '';
      footer.appendChild(btnLeft);
      footer.appendChild(btnRight);
    }
    return { overlay: overlay, body: body, setFooterSingle: setFooterSingle, setFooterDual: setFooterDual };
  }

  function showCaptcha(c) {
    var ui = mountOverlay();
    ui.body.innerHTML = '';
    ui.body.appendChild(el('div', { class: 'pus-title', text: 'Please verify you are human' }));
    ui.body.appendChild(el('p', { class: 'pus-copy', text: 'To continue, complete the CAPTCHA challenge.' }));

    var err = el('div', { class: 'pus-error', text: 'Captcha failed. Please try again.' });
    ui.body.appendChild(err);

    if (c.recaptchaEnabled && c.recaptchaSiteKey) {
      if (!document.getElementById('recaptcha-api')) {
        var s = document.createElement('script');
        s.id = 'recaptcha-api';
        s.src = 'https://www.google.com/recaptcha/api.js';
        s.async = true;
        s.defer = true;
        document.head.appendChild(s);
      }

      var wrap = el('div', { class: 'pus-recaptcha-wrap' });
      var box = el('div', { class: 'g-recaptcha', 'data-sitekey': c.recaptchaSiteKey });
      wrap.appendChild(box);
      ui.body.appendChild(wrap);

      var btn = el('button', { class: 'pus-btn pus-btn-primary pus-btn-full', text: 'CONTINUE', onclick: function () {
        err.style.display = 'none';

        var tokenEl = document.querySelector('textarea[name="g-recaptcha-response"]');
        var token = tokenEl ? (tokenEl.value || '').trim() : '';

        if (!token) {
          err.textContent = 'Please complete the CAPTCHA.';
          err.style.display = 'block';
          return;
        }

        httpJson('/power-user-survey/recaptcha/verify', 'POST', {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }, { token: token }).then(function (data) {
          if (data && data.ok) {
            window.location.href = c.redirectTo || '/';
            return;
          }
          err.textContent = 'Captcha failed. Please try again.';
          err.style.display = 'block';
          if (typeof grecaptcha !== 'undefined') {
            try { grecaptcha.reset(); } catch (e) {}
          }
        });
      }});
      ui.setFooterSingle(btn);
      return;
    }

    ui.body.appendChild(el('p', { class: 'pus-copy', text: 'Captcha is not configured. Please contact support.' }));
    ui.setFooterSingle(el('button', { class: 'pus-btn pus-btn-primary pus-btn-full', text: 'GO BACK', onclick: function () {
      window.location.href = c.redirectTo || '/';
    }}));
  }

  function showSurvey(c) {
    var currentScreen = 1;
    var initTracked = false;
    var suppressUnloadClose = false;
    var unloadTracked = false;
    var ui = mountOverlay();

    function closeSurvey(screen) {
      suppressUnloadClose = true;
      surveyCall(c, '/v1/survey/' + c.__deviceId + '/close', 'PATCH', { screen: screen || currentScreen });
      ui.overlay.remove();
    }

    function sendCloseOnUnload() {
      if (suppressUnloadClose || unloadTracked) return;
      unloadTracked = true;

      // Best-effort close tracking when user leaves/reloads while modal is open.
      fetch(c.surveyBaseUrl.replace(/\/$/, '') + '/v1/survey/' + c.__deviceId + '/close', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-Api-Key': c.surveyApiKey },
        body: JSON.stringify({ screen: currentScreen }),
        credentials: 'omit',
        keepalive: true
      }).catch(function () {});
    }

    window.addEventListener('pagehide', sendCloseOnUnload);
    window.addEventListener('beforeunload', sendCloseOnUnload);

    function specialOfferUrl(email) {
      var base = (c.specialOfferUrl && String(c.specialOfferUrl).trim())
        || 'https://www.peoplefinders.com/join?utm_source=usphonebook&utm_campaign=pow&utm_medium=rate_limit_modal';

      base = base.replace(/([?&])email=[^&]*/i, '').replace(/[?&]$/, '');

      var hash = '';
      var hashIndex = base.indexOf('#');
      if (hashIndex >= 0) {
        hash = base.slice(hashIndex);
        base = base.slice(0, hashIndex);
      }

      var sep = base.indexOf('?') === -1 ? '?' : '&';
      return base + sep + 'email=' + encodeURIComponent(email || '') + hash;
    }

    var appName = (c.appName && String(c.appName).trim()) || 'This App';

    function step1() {
      currentScreen = 1;
      ui.body.innerHTML = '';
      ui.body.appendChild(el('div', { class: 'pus-title', text: 'Looks like you are getting a lot of use out of ' + appName + '.' }));
      ui.body.appendChild(el('p', { class: 'pus-copy', text: "We're always looking to understand what our most active users need." }));
      ui.body.appendChild(el('p', { class: 'pus-copy', text: 'Mind answering a couple quick questions?' }));

      if (!initTracked) {
        initTracked = true;
        surveyCall(c, '/v1/survey', 'POST', {
          deviceId: c.__deviceId,
          siteId: c.siteId,
          ip: c.ip || undefined,
          userAgent: navigator.userAgent
        });
      }

      ui.setFooterSingle(el('button', { class: 'pus-btn pus-btn-primary pus-btn-full', text: 'CONTINUE', onclick: step2 }));
    }

    function radioList(options, name, selectedValue, onSelect) {
      var wrap = el('div', { class: 'pus-list' });
      options.forEach(function (o) {
        var input = el('input', { type: 'radio', name: name, value: o.value });
        var label = el('div', { text: o.label });
        var row = el('label', { class: 'pus-option' }, [input, label]);

        if (selectedValue && selectedValue === o.value) {
          input.checked = true;
          row.classList.add('selected');
        }

        input.addEventListener('change', function () {
          Array.prototype.forEach.call(wrap.querySelectorAll('.pus-option'), function (r) { r.classList.remove('selected'); });
          row.classList.add('selected');
          onSelect(o.value);
        });

        wrap.appendChild(row);
      });
      return wrap;
    }

    function step2() {
      currentScreen = 2;
      ui.body.innerHTML = '';
      ui.body.appendChild(el('div', { class: 'pus-title', text: 'Which best describes you?' }));

      var progress = readProgress(c);
      var state = {
        segment: progress.segment || null,
        segmentOther: progress.segmentOther || ''
      };

      var list = radioList([
        { value: 'individual', label: 'Individual/Personal Use' },
        { value: 'real_estate', label: 'Real Estate' },
        { value: 'business_marketing', label: 'Business/Marketing' },
        { value: 'legal_investigations', label: 'Legal/Investigations' },
        { value: 'other', label: 'Other' }
      ], 'pus-segment', state.segment, function (v) {
        state.segment = v;
        otherWrap.style.display = (v === 'other') ? 'block' : 'none';
        writeProgress(c, { segment: state.segment, segmentOther: state.segmentOther });
      });

      var otherWrap = el('div', { style: (state.segment === 'other') ? 'display:block;margin-top:10px;' : 'display:none;margin-top:10px;' });
      var otherInput = el('input', { class: 'pus-input', type: 'text', placeholder: 'Please specify' });
      otherInput.value = state.segmentOther;
      otherInput.addEventListener('input', function (e) {
        state.segmentOther = e.target.value || '';
        writeProgress(c, { segment: state.segment, segmentOther: state.segmentOther });
      });
      otherWrap.appendChild(otherInput);

      ui.body.appendChild(list);
      ui.body.appendChild(otherWrap);
      var err = el('div', { class: 'pus-error', text: 'Please select one option to continue.' });
      ui.body.appendChild(err);

      ui.setFooterSingle(el('button', { class: 'pus-btn pus-btn-primary pus-btn-full', text: 'CONTINUE', onclick: function () {
        if (!state.segment) {
          err.style.display = 'block';
          return;
        }
        err.style.display = 'none';
        var payload = { segment: state.segment };
        if (state.segment === 'other' && state.segmentOther) payload.segmentOther = state.segmentOther;

        surveyCall(c, '/v1/survey/' + c.__deviceId + '/segment', 'PATCH', payload).then(function () {
          step3();
        });
      }}));
    }

    function step3() {
      currentScreen = 3;
      ui.body.innerHTML = '';
      ui.body.appendChild(el('div', { class: 'pus-title', text: 'What type of information is most useful to you?' }));

      var progress = readProgress(c);
      var state = {
        interest: progress.interest || null,
        interestOther: progress.interestOther || ''
      };

      var list = radioList([
        { value: 'contact_info', label: 'Contact Info' },
        { value: 'background_court', label: 'Background and Court Records' },
        { value: 'property_assets', label: 'Property and Assets' },
        { value: 'family_associates', label: 'Family and Associates' },
        { value: 'other', label: 'Other' }
      ], 'pus-interest', state.interest, function (v) {
        state.interest = v;
        otherWrap.style.display = (v === 'other') ? 'block' : 'none';
        writeProgress(c, { interest: state.interest, interestOther: state.interestOther });
      });

      var otherWrap = el('div', { style: (state.interest === 'other') ? 'display:block;margin-top:10px;' : 'display:none;margin-top:10px;' });
      var otherInput = el('input', { class: 'pus-input', type: 'text', placeholder: 'Please specify' });
      otherInput.value = state.interestOther;
      otherInput.addEventListener('input', function (e) {
        state.interestOther = e.target.value || '';
        writeProgress(c, { interest: state.interest, interestOther: state.interestOther });
      });
      otherWrap.appendChild(otherInput);

      ui.body.appendChild(list);
      ui.body.appendChild(otherWrap);
      var err = el('div', { class: 'pus-error', text: 'Please select one option to continue.' });
      ui.body.appendChild(err);

      ui.setFooterSingle(el('button', { class: 'pus-btn pus-btn-primary pus-btn-full', text: 'CONTINUE', onclick: function () {
        if (!state.interest) {
          err.style.display = 'block';
          return;
        }
        err.style.display = 'none';
        var payload = { interest: state.interest };
        if (state.interest === 'other' && state.interestOther) payload.interestOther = state.interestOther;

        surveyCall(c, '/v1/survey/' + c.__deviceId + '/interest', 'PATCH', payload).then(function () {
          step4();
        });
      }}));
    }

    function step4() {
      currentScreen = 4;
      ui.body.innerHTML = '';
      ui.body.appendChild(el('div', { class: 'pus-title', text: "Thanks — that's helpful." }));
      ui.body.appendChild(el('p', { class: 'pus-copy', text: 'Curious what an ad-free experience with unlimited searches and more comprehensive data might look like?' }));
      ui.body.appendChild(el('p', { class: 'pus-mt-2 pus-copy', text: "Drop your email if you'd like to learn more" }));

      var email = el('input', { class: 'pus-input', type: 'email', placeholder: 'you@example.com' });
      ui.body.appendChild(el('div', { style: 'margin-top:12px;' }, [email]));

      var noThanks = el('button', { class: 'pus-btn pus-btn-outline', text: 'NO THANKS', onclick: function () {
        closeSurvey(4);
      }});

      var submit = el('button', { class: 'pus-btn pus-btn-primary', text: 'SUBMIT', onclick: function () {
        var e = (email.value || '').trim();
        if (!e) return;

        surveyCall(c, '/v1/survey/' + c.__deviceId + '/email', 'PATCH', { email: e }).then(function () {
          setCompleted(c, true);
          clearProgress(c);
          suppressUnloadClose = true;
          window.location.href = specialOfferUrl(e);
        });
      }});

      ui.setFooterDual(noThanks, submit);
    }

    function step5() {
      currentScreen = 5;
      ui.body.innerHTML = '';
      ui.body.appendChild(el('div', { class: 'pus-title', text: 'Claim your limited time offer!' }));
      ui.body.appendChild(el('p', { class: 'pus-copy', text: 'As a power user you qualify for a special Peoplefinders.com membership — deeper data, no ads, no search limits.' }));

      var box = el('div', { class: 'pus-offer-box' });
      var row = el('div', { class: 'pus-price-row' });
      var priceWas = (c.priceWas && String(c.priceWas).trim()) || '$24.95';
      var priceNow = (c.priceNow && String(c.priceNow).trim()) || '$9.95';
      row.appendChild(el('div', { class: 'pus-was', text: priceWas }));
      row.appendChild(el('div', { class: 'pus-now', text: priceNow }));
      box.appendChild(row);
      box.appendChild(el('div', { class: 'pus-sub', text: 'for your first month' }));
      ui.body.appendChild(box);

      var maybe = el('button', { class: 'pus-btn pus-btn-outline', text: 'MAYBE LATER', onclick: function () {
        closeSurvey(5);
      }});

      var claim = el('button', { class: 'pus-btn pus-btn-primary', text: 'CLAIM OFFER', onclick: function () {
        var url = c.joinUrl || getRedirect(c) || '/';
        suppressUnloadClose = true;
        window.open(url, '_blank', 'noopener');
        surveyCall(c, '/v1/survey/' + c.__deviceId + '/offer', 'PATCH', { accepted: true }).then(function () {
          ui.overlay.remove();
        });
      }});

      ui.setFooterDual(maybe, claim);
    }

    if (completed(c) && c.forceStep5IfCompleted) {
      step5();
      return;
    }

    step1();
  }

  function initSurvey() {
    var c = cfg();
    if (!c || !c.enabled) return;

    injectCssOnce();
    injectThemeOnce(c);

    c.__deviceId = getDeviceId(c.storage.device_id);

    if (c.mode === 'captcha') showCaptcha(c);
    else if (c.mode === 'survey') showSurvey(c);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSurvey);
  } else {
    initSurvey();
  }
})();
