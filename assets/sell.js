/* Seller workspace: photo previews + the AI selling assistant. */
(function () {
  'use strict';
  var form = document.getElementById('sell-form');
  if (!form) return;

  var csrf = document.querySelector('meta[name="csrf"]').content;
  var endpoint = form.dataset.endpoint;
  var listingId = parseInt(form.dataset.listing || '0', 10);
  var existing = parseInt(form.dataset.existing || '0', 10);
  var maxPhotos = parseInt(form.dataset.max || '8', 10);
  var $ = function (id) { return document.getElementById(id); };

  var input = $('photo-input');
  var grid = $('photo-grid');
  var msg = $('photo-msg');
  var files = [];

  /* ---------- photo previews ---------- */
  function syncInput() {
    var dt = new DataTransfer();
    files.forEach(function (f) { dt.items.add(f); });
    input.files = dt.files;
  }
  function renderNew() {
    grid.querySelectorAll('.tile.new').forEach(function (t) { t.remove(); });
    var anchor = grid.querySelector('[data-empty]');
    files.forEach(function (f, i) {
      var tile = document.createElement('div');
      tile.className = 'tile new';
      var ph = document.createElement('div'); ph.className = 'photo';
      var img = document.createElement('img'); img.alt = f.name; img.src = URL.createObjectURL(f);
      ph.appendChild(img);
      var meta = document.createElement('div'); meta.className = 'tile-meta';
      var n = document.createElement('span'); n.textContent = 'PHOTO ' + (existing + i + 1);
      var c = document.createElement('span'); c.className = 'muted'; c.textContent = (existing + i === 0) ? 'Cover photo' : 'New';
      var rm = document.createElement('button'); rm.type = 'button'; rm.className = 'link-btn'; rm.textContent = 'Remove';
      rm.addEventListener('click', function () { files.splice(i, 1); syncInput(); renderNew(); });
      meta.appendChild(n); meta.appendChild(c); meta.appendChild(rm);
      tile.appendChild(ph); tile.appendChild(meta);
      grid.insertBefore(tile, anchor);
    });
    // Show only as many empty placeholders as needed to reach three tiles.
    var empties = grid.querySelectorAll('[data-empty]');
    var needed = Math.max(0, 3 - existing - files.length);
    empties.forEach(function (e, i) { e.hidden = i >= needed; });
    runCategorize();
  }
  input.addEventListener('change', function () {
    var picked = Array.prototype.slice.call(input.files);
    var room = maxPhotos - existing - files.length;
    var bad = [];
    picked.forEach(function (f) {
      if (!/^image\/(jpeg|png)$/.test(f.type)) { bad.push(f.name + ' is not a JPG or PNG.'); return; }
      if (f.size > 5 * 1024 * 1024) { bad.push(f.name + ' is larger than 5 MB.'); return; }
      if (room <= 0) { bad.push('Only ' + maxPhotos + ' photos are allowed.'); return; }
      files.push(f); room--;
    });
    msg.hidden = bad.length === 0;
    msg.textContent = bad.join(' ');
    syncInput(); renderNew();
  });
  form.querySelectorAll('input[name="remove[]"]').forEach(function (remove) {
    remove.addEventListener('change', function () {
      if (remove.checked && !window.confirm('Remove this photo from the listing when you save?')) {
        remove.checked = false;
      }
    });
  });
  form.querySelectorAll('input[name^="replace["]').forEach(function (replace) {
    replace.addEventListener('change', function () {
      if (replace.files.length && !window.confirm('Replace this saved photo when you save the listing?')) {
        replace.value = '';
      }
    });
  });

  /* ---------- AI assistant ---------- */
  function payload(action) {
    return {
      action: action,
      listing_id: listingId,
      title: $('title').value,
      description: $('description').value,
      category_id: $('category_id').value,
      item_condition: $('item_condition').value,
      town_id: $('town_id').value,
      filenames: files.map(function (f) { return f.name; })
    };
  }
  function call(action) {
    return fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf, 'Accept': 'application/json' },
      body: JSON.stringify(payload(action))
    }).then(function (r) { return r.json(); });
  }
  function debounce(fn, ms) { var t; return function () { clearTimeout(t); t = setTimeout(fn, ms); }; }
  function peso(n) { return '₱' + Number(n).toLocaleString('en-PH'); }
  function markStep2() {
    var s = document.querySelector('#stepper [data-step="2"]');
    if (s) s.classList.add('active');
  }

  // 01 categorize
  var catCard = $('ai-cat'), catOut = catCard.querySelector('[data-out]'), catApply = catCard.querySelector('[data-apply]');
  var suggestedCat = null;
  var runCategorize = debounce(function () {
    if (!$('title').value.trim() && !$('description').value.trim() && files.length === 0) return;
    call('categorize').then(function (d) {
      if (d.found) {
        suggestedCat = d.category_id;
        catOut.innerHTML = '';
        var a = document.createElement('span'); a.textContent = 'Detected: ' + d.detected;
        var b = document.createElement('span'); b.textContent = 'Suggested category: ' + d.category_label;
        catOut.appendChild(a); catOut.appendChild(document.createElement('br')); catOut.appendChild(b);
        catApply.hidden = false;
      } else {
        suggestedCat = null; catApply.hidden = true; catOut.textContent = d.message || 'No suggestion yet.';
      }
    }).catch(function () {});
  }, 600);
  catApply.addEventListener('click', function () {
    if (suggestedCat) { $('category_id').value = String(suggestedCat); markStep2(); runPrice(); }
  });
  $('title').addEventListener('input', runCategorize);

  // 02 write
  var wCard = $('ai-write'), wOut = wCard.querySelector('[data-out]'), wTips = wCard.querySelector('[data-tips]');
  var wApply = wCard.querySelector('[data-apply]'), wRun = wCard.querySelector('[data-run]');
  var generated = null;
  wRun.addEventListener('click', function () {
    wRun.disabled = true;
    call('write').then(function (d) {
      wRun.disabled = false;
      wOut.hidden = false; wTips.innerHTML = '';
      if (!d.ok) { generated = null; wApply.hidden = true; wOut.textContent = d.message; return; }
      generated = d;
      wOut.textContent = 'Preview: ' + d.title + ' — ' + d.description;
      (d.tips || []).forEach(function (t) { var li = document.createElement('li'); li.textContent = t; wTips.appendChild(li); });
      wApply.hidden = false;
    }).catch(function () { wRun.disabled = false; wOut.hidden = false; wOut.textContent = 'Could not reach the assistant. Try again.'; });
  });
  wApply.addEventListener('click', function () {
    if (!generated) return;
    $('title').value = generated.title; $('description').value = generated.description;
    markStep2(); runCategorize(); runPrice();
  });

  // 03 price
  var pCard = $('ai-price'), pRange = pCard.querySelector('[data-range]'), pBasis = pCard.querySelector('[data-basis]'), pApply = pCard.querySelector('[data-apply]');
  var suggestedPrice = null;
  var runPrice = debounce(function () {
    if (!$('category_id').value) {
      suggestedPrice = null; pApply.hidden = true; pRange.textContent = '—';
      pBasis.textContent = 'Choose a category and add a title to see comparable local listings.'; return;
    }
    call('price').then(function (d) {
      if (d.ok) {
        suggestedPrice = d.suggested;
        pRange.textContent = peso(d.low) + ' – ' + peso(d.high);
        pBasis.textContent = 'Based on ' + d.count + ' comparable local listings (' + d.basis + '). Final price is up to you.';
        pApply.textContent = 'Use ' + peso(d.suggested); pApply.hidden = false;
      } else {
        suggestedPrice = null; pApply.hidden = true; pRange.textContent = '—'; pBasis.textContent = d.message;
      }
    }).catch(function () {});
  }, 600);
  pApply.addEventListener('click', function () { if (suggestedPrice) { $('price').value = String(suggestedPrice); markStep2(); } });
  $('category_id').addEventListener('change', runPrice);
  $('title').addEventListener('input', runPrice);

  // initial suggestions for an existing draft
  runCategorize(); runPrice();
})();
