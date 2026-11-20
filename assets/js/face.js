/* Face verification: camera capture + three liveness steps, then upload. */
(function () {
  var panel = document.getElementById('face-panel');
  if (!panel || panel.dataset.enabled !== '1') return;

  var startBtn = document.getElementById('face-start');
  var consent = document.getElementById('face-consent');
  var video = document.getElementById('face-video');
  var circle = document.getElementById('face-circle');
  var label = document.getElementById('face-label');
  var status = document.getElementById('face-status');
  var csrf = document.querySelector('meta[name="csrf"]').content;
  var steps = [
    'Look straight at the camera',
    'Slowly turn your head to the left',
    'Slowly turn your head to the right'
  ];
  var running = false;

  function say(msg, kind) {
    status.textContent = msg;
    status.className = 'face-caption' + (kind ? ' ' + kind : '');
  }
  function wait(ms) { return new Promise(function (r) { setTimeout(r, ms); }); }

  function capture() {
    var w = video.videoWidth || 640, h = video.videoHeight || 480;
    var c = document.createElement('canvas');
    c.width = w; c.height = h;
    c.getContext('2d').drawImage(video, 0, 0, w, h);
    return new Promise(function (resolve) { c.toBlob(resolve, 'image/jpeg', 0.85); });
  }

  startBtn.addEventListener('click', async function () {
    if (running) return;
    if (!consent.checked) { say('Please tick the consent box first.', 'bad'); return; }
    if (!window.confirm('Start face verification? Your camera will capture three frames for profile authentication.')) return;
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      say('Camera unavailable in this browser. Use Need help? or keep browsing as a guest.', 'bad'); return;
    }
    running = true; startBtn.disabled = true;
    var stream;
    try {
      say('Requesting camera access…');
      stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } }, audio: false });
    } catch (e) {
      say('Camera unavailable or access was denied. Allow camera access and retry, or contact admin support.', 'bad');
      running = false; startBtn.disabled = false; return;
    }
    video.srcObject = stream;
    await video.play().catch(function () {});
    circle.classList.add('live'); label.hidden = true;

    try {
      var blobs = [];
      for (var i = 0; i < steps.length; i++) {
        say(steps[i] + '…');
        await wait(2600);
        blobs.push(await capture());
        say('Captured ' + (i + 1) + ' of 3');
        await wait(400);
      }
      say('Verifying…');
      var fd = new FormData();
      fd.append('csrf', csrf);
      fd.append('consent', '1');
      blobs.forEach(function (b, idx) { fd.append('frame' + (idx + 1), b, 'frame' + (idx + 1) + '.jpg'); });
      var res = await fetch(panel.dataset.endpoint, { method: 'POST', body: fd, headers: { 'X-CSRF-Token': csrf, 'Accept': 'application/json' } });
      var data = await res.json().catch(function () { return { ok: false, message: 'Unexpected response.' }; });
      if (data.ok) {
        say(data.message + ' Redirecting…', 'good');
        setTimeout(function () { window.location.href = panel.dataset.next; }, 1200);
        return;
      }
      say(data.message || 'Verification was unsuccessful. Please retry or contact admin support.', 'bad');
    } catch (e) {
      say('Something went wrong. Please retry or contact admin support.', 'bad');
    } finally {
      stream.getTracks().forEach(function (t) { t.stop(); });
      video.srcObject = null; circle.classList.remove('live'); label.hidden = false;
      running = false; startBtn.disabled = false;
    }
  });
})();
