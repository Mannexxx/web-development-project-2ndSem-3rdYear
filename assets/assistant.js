/* AI assistant chat. Messages are inserted with textContent, never as HTML. */
(function () {
  'use strict';
  var chat = document.getElementById('chat');
  if (!chat) return;
  var csrf = document.querySelector('meta[name="csrf"]').content;
  var log = document.getElementById('chat-log');
  var form = document.getElementById('chat-form');
  var input = document.getElementById('chat-input');
  var listingId = parseInt(chat.dataset.listing || '0', 10);
  var busy = false;

  function bubble(who, text, mine) {
    var b = document.createElement('div');
    b.className = 'bubble ' + (mine ? 'bubble-me' : 'bubble-them');
    var w = document.createElement('span'); w.className = 'who'; w.textContent = who;
    b.appendChild(w);
    var t = document.createElement('div'); t.className = 'pre'; t.textContent = text;
    b.appendChild(t);
    log.appendChild(b);
    log.scrollTop = log.scrollHeight;
    return b;
  }

  function ask(message) {
    if (busy || !message.trim()) return;
    busy = true;
    bubble('You', message, true);
    var wait = bubble('Assistant', 'Thinking…', false);
    fetch(chat.dataset.endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf, 'Accept': 'application/json' },
      body: JSON.stringify({ message: message, listing_id: listingId || null })
    }).then(function (r) { return r.json(); }).then(function (d) {
      wait.querySelector('.pre').textContent = d.text || d.message || 'Sorry, something went wrong.';
      (d.listings || []).forEach(function (l) {
        var a = document.createElement('a');
        a.className = 'mini-card'; a.href = l.url;
        var s = document.createElement('strong'); s.textContent = l.title;
        var p = document.createElement('span'); p.textContent = l.price + ' · ' + l.meta;
        a.appendChild(s); a.appendChild(p); wait.appendChild(a);
      });
      (d.links || []).forEach(function (l) {
        var a = document.createElement('a');
        a.className = 'btn btn-sm'; a.href = l.url; a.textContent = l.label;
        wait.appendChild(a);
      });
      log.scrollTop = log.scrollHeight;
    }).catch(function () {
      wait.querySelector('.pre').textContent = 'I could not reach the server. Please try again.';
    }).then(function () { busy = false; });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var v = input.value; input.value = '';
    ask(v);
  });
  document.querySelectorAll('[data-prompt]').forEach(function (b) {
    b.addEventListener('click', function () { ask(b.dataset.prompt); });
  });
  if (chat.dataset.ask) { ask(chat.dataset.ask); }
})();
