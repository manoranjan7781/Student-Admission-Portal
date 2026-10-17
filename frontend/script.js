/* =====================================================================
   Client-side validation for the registration form (vanilla JavaScript).
   IMPORTANT: this only improves usability. The PHP server repeats every
   check (register.php) because JavaScript can be bypassed.
   ===================================================================== */
(function () {
  'use strict';

  var form = document.getElementById('reg-form');
  var summary = document.getElementById('form-summary');

  // Limits and patterns - keep identical to php-module/includes/functions.php
  var MAX_IMAGE_BYTES = 2 * 1024 * 1024;
  var IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
  var ROLL_RE  = /^[A-Za-z0-9\-\/]{4,20}$/;
  var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

  /* ---- One validator per field: returns an error string, or '' if valid ---- */
  var validators = {
    name: function (v) {
      v = v.trim();
      if (!v) return 'Please enter your full name.';
      if (v.length < 3) return 'Name must be at least 3 characters.';
      if (!/^\p{L}[\p{L}\p{M} .'\-]*$/u.test(v)) return 'Use letters, spaces, dots, apostrophes or hyphens only.';
      return '';
    },
    roll_number: function (v) {
      v = v.trim();
      if (!v) return 'Please enter your roll number.';
      if (!ROLL_RE.test(v)) return 'Roll number must be 4-20 letters, digits, "-" or "/".';
      return '';
    },
    email: function (v) {
      v = v.trim();
      if (!v) return 'Please enter your email address.';
      if (!EMAIL_RE.test(v)) return 'Enter a valid email such as name@example.com.';
      return '';
    },
    course: function (v) {
      return v ? '' : 'Please choose your course.';
    },
    password: function (v) {
      if (!v) return 'Please create a password.';
      var missing = passwordProblems(v);
      return missing.length ? 'Password needs: ' + missing.join(', ') + '.' : '';
    },
    confirm_password: function (v) {
      if (!v) return 'Please re-enter your password.';
      if (v !== form.elements.password.value) return 'Passwords do not match.';
      return '';
    },
    profile_image: function (v, input) {
      var f = input.files && input.files[0];
      if (!f) return '';                                   // optional field
      if (IMAGE_TYPES.indexOf(f.type) === -1) return 'Photo must be a JPG, PNG or WebP image.';
      if (f.size > MAX_IMAGE_BYTES) return 'Photo is too large (' + (f.size / 1048576).toFixed(1) + ' MB). Maximum is 2 MB.';
      return '';
    }
  };

  /* Returns a list of unmet password rules (also drives the strength meter) */
  function passwordProblems(v) {
    var m = [];
    if (v.length < 8)        m.push('8+ characters');
    if (!/[a-z]/.test(v))    m.push('a lower-case letter');
    if (!/[A-Z]/.test(v))    m.push('an upper-case letter');
    if (!/[0-9]/.test(v))    m.push('a digit');
    if (!/[^A-Za-z0-9]/.test(v)) m.push('a symbol');
    return m;
  }

  /* ---- Show / clear a field's error message ---------------------------------- */
  function setState(input, message) {
    var err = document.getElementById(input.id + '-err');
    if (err) err.textContent = message;
    if (message) {
      input.setAttribute('aria-invalid', 'true');     // read out by screen readers + styled red
      input.classList.remove('is-valid');
    } else {
      input.removeAttribute('aria-invalid');
      if (input.type !== 'file' || (input.files && input.files.length)) input.classList.add('is-valid');
    }
    // Tick the side checklist when the field is valid (and non-empty)
    var li = document.querySelector('#checklist li[data-for="' + input.name + '"]');
    if (li) {
      var filled = input.type === 'file' ? (input.files && input.files.length > 0) : input.value !== '';
      li.classList.toggle('done', !message && filled);
    }
  }

  function validateField(input) {
    var fn = validators[input.name];
    if (!fn) return true;
    var message = fn(input.value, input);
    setState(input, message);
    return message === '';
  }

  /* ---- Live feedback: validate on blur, then re-validate on every keystroke --- */
  Array.prototype.forEach.call(form.querySelectorAll('input, select'), function (input) {
    if (!validators[input.name]) return;
    input.addEventListener('blur', function () { validateField(input); });
    input.addEventListener(input.tagName === 'SELECT' || input.type === 'file' ? 'change' : 'input', function () {
      if (input.hasAttribute('aria-invalid') || input.classList.contains('is-valid') || input.name === 'password') validateField(input);
      if (input.name === 'password' && form.elements.confirm_password.value) validateField(form.elements.confirm_password);
    });
  });

  /* ---- Password strength meter ------------------------------------------------- */
  var bar = document.getElementById('strength-bar');
  var label = document.getElementById('strength-label');
  form.elements.password.addEventListener('input', function () {
    var v = this.value;
    var score = v ? 5 - passwordProblems(v).length : 0;          // 0..5
    var names  = ['-', 'Very weak', 'Weak', 'Fair', 'Good', 'Strong'];
    var colors = ['#b42318', '#b42318', '#c2570c', '#b7791f', '#4d8a3a', '#17724a'];
    bar.style.width = (score * 20) + '%';
    bar.style.background = colors[score];
    label.textContent = names[score];
  });

  /* ---- Show / hide password buttons -------------------------------------------- */
  Array.prototype.forEach.call(document.querySelectorAll('.pw-toggle'), function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.dataset.target);
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.textContent = show ? 'Hide' : 'Show';
      btn.setAttribute('aria-label', (show ? 'Hide ' : 'Show ') + 'password');
    });
  });

  /* ---- CSRF token: fetched from the PHP server so the POST is accepted ------- */
  var csrfReady = false;
  fetch('../php-module/csrf_token.php', { credentials: 'same-origin', cache: 'no-store' })
    .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(function (data) { document.getElementById('csrf_token').value = data.token; csrfReady = true; })
    .catch(function () { csrfReady = false; });   // reported when the user submits

  /* ---- Submit: validate everything; only a fully valid form is sent ------------ */
  form.addEventListener('submit', function (event) {
    var problems = [];
    var firstBad = null;
    Array.prototype.forEach.call(form.querySelectorAll('input, select'), function (input) {
      if (!validators[input.name]) return;
      if (!validateField(input)) {
        problems.push(document.getElementById(input.id + '-err').textContent);
        if (!firstBad) firstBad = input;
      }
    });

    if (!csrfReady && problems.length === 0) {
      problems.push('Cannot reach the PHP server. Open this page through http://localhost/... (not as a file) and make sure the PHP server is running.');
    }

    if (problems.length) {
      event.preventDefault();                        // stop the submission
      summary.hidden = false;
      summary.innerHTML = '<strong>Please fix the following:</strong><ul></ul>';
      var ul = summary.querySelector('ul');
      problems.forEach(function (p) {
        var li = document.createElement('li');
        li.textContent = p;                          // textContent (not innerHTML) -> no XSS
        ul.appendChild(li);
      });
      if (firstBad) firstBad.focus(); else summary.scrollIntoView({ behavior: 'smooth' });
    } else {
      summary.hidden = true;                         // valid: browser now posts the form
    }
  });

  /* Reset button: also clear our custom messages and ticks */
  document.getElementById('reset-btn').addEventListener('click', function () {
    setTimeout(function () {
      summary.hidden = true;
      Array.prototype.forEach.call(form.querySelectorAll('input, select'), function (i) {
        i.removeAttribute('aria-invalid'); i.classList.remove('is-valid');
        var e = document.getElementById(i.id + '-err'); if (e) e.textContent = '';
      });
      Array.prototype.forEach.call(document.querySelectorAll('#checklist li'), function (li) { li.classList.remove('done'); });
      bar.style.width = '0'; label.textContent = '-';
    }, 0);
  });
})();
