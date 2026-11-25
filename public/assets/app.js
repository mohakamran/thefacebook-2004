/* thefacebook-2004 — small progressive-enhancement script:
   mobile nav toggle, client-side form validation, confirm prompts, toggled panels, copy button. */
(function () {
  'use strict';

  // ---- mobile nav toggle ----
  var toggle = document.getElementById('nav-toggle');
  var nav = document.getElementById('nav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.textContent = open ? 'close' : 'menu';
    });
  }

  // ---- client-side validation (the server validates again) ----
  function isEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }

  var rules = {
    login: function (f) {
      var errs = [];
      if (!isEmail(f.email.value.trim())) errs.push('Please enter your email address.');
      if (!f.password.value) errs.push('Please enter your password.');
      return errs;
    },
    register: function (f) {
      var errs = [];
      var domain = f.getAttribute('data-domain') || '';
      var email = f.email.value.trim().toLowerCase();
      if (f.elements['name'].value.trim().length < 2) errs.push('Please enter your full name.');
      if (!f.status.value) errs.push('Please choose your status.');
      if (!isEmail(email)) errs.push('Please enter a valid email address.');
      else if (domain && email.slice(-(domain.length + 1)) !== '@' + domain) errs.push('You must register with your @' + domain + ' email address.');
      if (f.password.value.length < 6) errs.push('Your password must be at least 6 characters.');
      if (!f.terms.checked) errs.push('You must agree to the Terms of Use.');
      return errs;
    },
    edit: function (f) {
      var errs = [];
      var y = f.class_year.value.trim();
      if (y && !/^(19|20)\d\d$/.test(y)) errs.push('Year should look like 2006.');
      var file = f.picture && f.picture.files && f.picture.files[0];
      if (file) {
        if (file.size > 2 * 1024 * 1024) errs.push('Pictures must be smaller than 2 MB.');
        if (!/^image\/(jpeg|png|gif)$/.test(file.type)) errs.push('Pictures must be JPG, PNG or GIF images.');
      }
      return errs;
    },
    message: function (f) {
      var errs = [];
      if (f.to && f.to.tagName === 'SELECT' && !f.to.value) errs.push('Please choose who to send the message to.');
      if (!f.body.value.trim()) errs.push('Please write a message.');
      return errs;
    }
  };

  document.querySelectorAll('form[data-validate]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      var rule = rules[form.getAttribute('data-validate')];
      if (!rule) return;
      var errs = rule(form);
      if (!errs.length) return;
      ev.preventDefault();
      // The sidebar login has no error box of its own; use the page's, or fall back to alert().
      var box = form.closest('#sidebar') ? null : document.querySelector('.js-errors');
      if (box) {
        box.innerHTML = '';
        errs.forEach(function (m) { var d = document.createElement('div'); d.textContent = m; box.appendChild(d); });
        box.hidden = false;
        box.scrollIntoView({ block: 'nearest' });
      } else {
        alert(errs.join('\n'));
      }
    });
  });

  // ---- confirm before destructive actions ----
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!confirm(form.getAttribute('data-confirm'))) ev.preventDefault();
    });
  });

  // ---- toggled panels (e.g. "Message All Members") ----
  document.querySelectorAll('[data-toggle]').forEach(function (link) {
    link.addEventListener('click', function (ev) {
      var panel = document.getElementById(link.getAttribute('data-toggle'));
      if (!panel) return;
      ev.preventDefault();
      panel.classList.toggle('open');
      if (panel.classList.contains('open')) {
        var first = panel.querySelector('input:not([type=hidden]), textarea');
        if (first) first.focus();
      }
    });
  });

  // ---- copy-to-clipboard ----
  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var el = document.getElementById(btn.getAttribute('data-copy'));
      if (!el) return;
      el.select();
      var done = function () { btn.textContent = 'Copied!'; };
      if (navigator.clipboard) navigator.clipboard.writeText(el.value).then(done, function () { document.execCommand('copy'); done(); });
      else { document.execCommand('copy'); done(); }
    });
  });
})();
