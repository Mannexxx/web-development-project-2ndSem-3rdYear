/* Small, dependency-free helpers used across pages. */
(function () {
  'use strict';

  // Filters: submit the form as soon as a select changes.
  document.querySelectorAll('select[data-autosubmit]').forEach(function (sel) {
    sel.addEventListener('change', function () { sel.form && sel.form.submit(); });
  });

  // Mobile sort select mirrors the (hidden on mobile) desktop one.
  var mirror = document.querySelector('[data-mirror-sort]');
  if (mirror) {
    mirror.addEventListener('change', function () {
      var main = document.querySelector('select[name="sort"][data-autosubmit]');
      if (main) { main.value = mirror.value; main.form.submit(); }
    });
  }

  // Mobile "Filters" toggle.
  var toggle = document.querySelector('[data-toggle-filters]');
  var selects = document.getElementById('filter-selects');
  if (toggle && selects) {
    toggle.addEventListener('click', function () {
      var open = selects.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    // Keep it open when a filter is active so people can see why results changed.
    if (Array.prototype.some.call(selects.querySelectorAll('select'), function (s) { return s.value !== ''; })) {
      selects.classList.add('open');
    }
  }

  // Product gallery thumbnails.
  document.querySelectorAll('[data-gallery]').forEach(function (g) {
    var main = g.querySelector('[data-gallery-main]');
    g.querySelectorAll('.thumb').forEach(function (t) {
      t.addEventListener('click', function () {
        if (main) { main.src = t.dataset.src; }
        g.querySelectorAll('.thumb').forEach(function (o) { o.classList.remove('active'); });
        t.classList.add('active');
      });
    });
  });

  // Share button (product page, mobile).
  document.querySelectorAll('[data-share]').forEach(function (b) {
    b.addEventListener('click', function () {
      var data = { title: document.title, url: window.location.href };
      if (navigator.share) { navigator.share(data).catch(function () {}); return; }
      if (navigator.clipboard) {
        navigator.clipboard.writeText(data.url).then(function () { b.textContent = 'Link copied'; setTimeout(function () { b.textContent = 'Share'; }, 1800); });
      }
    });
  });

  // File input label shows the chosen file names.
  document.querySelectorAll('[data-file-input]').forEach(function (input) {
    var label = input.closest('label') && input.closest('label').querySelector('[data-file-label]');
    if (!label) return;
    var original = label.textContent;
    input.addEventListener('change', function () {
      var names = Array.prototype.map.call(input.files, function (f) { return f.name; });
      label.textContent = names.length ? names.join(', ') : original;
    });
  });

  // Confirm before destructive buttons.
  document.querySelectorAll('[data-confirm-click]').forEach(function (b) {
    b.addEventListener('click', function (e) {
      if (!window.confirm(b.dataset.confirmClick)) { e.preventDefault(); }
    });
  });

  // Confirm dialogs for risky forms.
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.dataset.confirm)) { e.preventDefault(); }
    });
  });
})();
