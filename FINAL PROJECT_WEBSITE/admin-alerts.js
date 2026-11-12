
//34
(function () {
  'use strict';

  var script = document.currentScript;
  if (!script || !script.dataset.endpoint) return;

  var ENDPOINT = script.dataset.endpoint;
  var BASE = script.dataset.base || '';
  var POLL_VISIBLE = 20000;
  var POLL_HIDDEN = 45000;
  var MAX_TOASTS = 4;

  var K_POS = 'bl_alerts_pos';
  var K_SOUND = 'bl_alerts_sound';
  var K_DESK = 'bl_alerts_desktop';
  var K_ASKED = 'bl_alerts_asked';
  var K_PENDING_REPORT = 'bl_alerts_pending_report_v3';

  function lsGet(key) {
    try { return window.localStorage.getItem(key); }
    catch (e) { return null; }
  }

  function lsSet(key, value) {
    try { window.localStorage.setItem(key, value); }
    catch (e) {}
  }

  function lsRemove(key) {
    try { window.localStorage.removeItem(key); }
    catch (e) {}
  }

  function soundOn() {
    return lsGet(K_SOUND) !== '0';
  }

  function desktopOn() {
    return lsGet(K_DESK) !== '0';
  }

  function readPos() {
    try { return JSON.parse(lsGet(K_POS) || 'null'); }
    catch (e) { return null; }
  }

  function readPendingReport() {
    try {
      return JSON.parse(lsGet(K_PENDING_REPORT) || 'null');
    } catch (e) {
      return null;
    }
  }

  function savePendingReport(report) {
    lsSet(K_PENDING_REPORT, JSON.stringify(report));
  }

  function clearPendingReport() {
    lsRemove(K_PENDING_REPORT);
  }

  var stopped = false;
  var busy = false;
  var timer = null;
  var lastPoll = 0;
  var asked = false;

  var audioCtx = null;
  var masterGain = null;
  var compressor = null;

  var reportLoopTimer = null;
  var activeReport = null;

  var pendingReportSound = false;
  var pendingMessageSound = false;

  //35

  function el(tag, cls, text) {
    var node = document.createElement(tag);

    if (cls) node.className = cls;
    if (text != null) node.textContent = text;

    return node;
  }

  var ICONS = {
    report: '<path d="M12 3 2 20h20z"/><path d="M12 10v4M12 17.5v.5"/>',
    message: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.5"/>',
    bell: '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 8 3 8H3s3-1 3-8z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
    x: '<path d="M6 6l12 12M18 6 6 18"/>'
  };

  function icon(name) {
    var svg = document.createElementNS(
      'http://www.w3.org/2000/svg',
      'svg'
    );

    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', '1.8');
    svg.setAttribute('stroke-linecap', 'round');
    svg.setAttribute('stroke-linejoin', 'round');
    svg.setAttribute('aria-hidden', 'true');

    svg.innerHTML = ICONS[name];

    return svg;
  }

  //36

  //37
  //38

  function ensureAudioContext() {
    if (audioCtx) return audioCtx;

    var AudioContextClass =
      window.AudioContext || window.webkitAudioContext;

    if (!AudioContextClass) return null;

    try {
      audioCtx = new AudioContextClass();

      masterGain = audioCtx.createGain();
      compressor = audioCtx.createDynamicsCompressor();

      //39
      masterGain.gain.value = 0.92;

      //40
      compressor.threshold.value = -8;
      compressor.knee.value = 10;
      compressor.ratio.value = 12;
      compressor.attack.value = 0.003;
      compressor.release.value = 0.16;

      masterGain.connect(compressor);
      compressor.connect(audioCtx.destination);

      audioCtx.addEventListener('statechange', function () {
        if (audioCtx && audioCtx.state === 'running') {
          flushPendingSounds();
        }
      });

      return audioCtx;

    } catch (e) {
      audioCtx = null;
      masterGain = null;
      compressor = null;

      return null;
    }
  }

  function flushPendingSounds() {
    if (!soundOn()) return;
    if (!audioCtx || audioCtx.state !== 'running') return;

    if (pendingReportSound && activeReport) {
      pendingReportSound = false;
      playReportAlarm();
    }

    if (pendingMessageSound) {
      pendingMessageSound = false;
      playMessageTone();
    }
  }

  function unlockAudio() {
    var ctx = ensureAudioContext();

    if (!ctx) return;

    try {
      if (
        ctx.state === 'suspended' ||
        ctx.state === 'interrupted'
      ) {
        var resumeResult = ctx.resume();

        if (resumeResult && typeof resumeResult.then === 'function') {
          resumeResult
            .then(flushPendingSounds)
            .catch(function () {});
        }

      } else if (ctx.state === 'running') {
        flushPendingSounds();
      }

    } catch (e) {}
  }

  //41
  [
    'pointerdown',
    'mousedown',
    'touchstart',
    'keydown',
    'click'
  ].forEach(function (eventName) {
    window.addEventListener(
      eventName,
      unlockAudio,
      true
    );
  });

  //42

  function playPattern(notes) {
    if (!soundOn()) return false;

    var ctx = ensureAudioContext();

    if (
      !ctx ||
      ctx.state !== 'running' ||
      !masterGain
    ) {
      return false;
    }

    var startAt = ctx.currentTime + 0.025;

    notes.forEach(function (note) {
      var oscillator = ctx.createOscillator();
      var gain = ctx.createGain();

      var frequency = note.frequency;
      var offset = note.offset;
      var duration = note.duration;
      var volume = note.volume || 0.58;

      oscillator.type = note.type || 'square';

      oscillator.frequency.setValueAtTime(
        frequency,
        startAt + offset
      );

      if (note.endFrequency) {
        oscillator.frequency.exponentialRampToValueAtTime(
          note.endFrequency,
          startAt + offset + duration
        );
      }

      gain.gain.setValueAtTime(
        0.0001,
        startAt + offset
      );

      gain.gain.exponentialRampToValueAtTime(
        volume,
        startAt + offset + 0.012
      );

      gain.gain.setValueAtTime(
        volume,
        startAt + offset + Math.max(0.025, duration - 0.035)
      );

      gain.gain.exponentialRampToValueAtTime(
        0.0001,
        startAt + offset + duration
      );

      oscillator.connect(gain);
      gain.connect(masterGain);

      oscillator.start(startAt + offset);

      oscillator.stop(
        startAt + offset + duration + 0.015
      );
    });

    return true;
  }

  //43

  function playReportAlarm() {
    if (!soundOn()) return;

    var played = playPattern([
      {
        frequency: 980,
        endFrequency: 1180,
        offset: 0,
        duration: 0.19,
        volume: 0.62,
        type: 'sawtooth'
      },
      {
        frequency: 1450,
        endFrequency: 1120,
        offset: 0.22,
        duration: 0.19,
        volume: 0.62,
        type: 'sawtooth'
      },
      {
        frequency: 980,
        endFrequency: 1250,
        offset: 0.44,
        duration: 0.19,
        volume: 0.62,
        type: 'sawtooth'
      },
      {
        frequency: 1450,
        endFrequency: 1180,
        offset: 0.66,
        duration: 0.28,
        volume: 0.64,
        type: 'sawtooth'
      }
    ]);

    if (!played) {
      pendingReportSound = true;
    }
  }

  //44

  function playMessageTone() {
    if (!soundOn()) return;

    var played = playPattern([
      {
        frequency: 1040,
        offset: 0,
        duration: 0.28,
        volume: 0.52,
        type: 'triangle'
      }
    ]);

    if (!played) {
      pendingMessageSound = true;
    }
  }

  function playTestAlarm() {
    playPattern([
      {
        frequency: 980,
        endFrequency: 1180,
        offset: 0,
        duration: 0.19,
        volume: 0.62,
        type: 'sawtooth'
      },
      {
        frequency: 1450,
        endFrequency: 1120,
        offset: 0.22,
        duration: 0.19,
        volume: 0.62,
        type: 'sawtooth'
      },
      {
        frequency: 980,
        endFrequency: 1250,
        offset: 0.44,
        duration: 0.19,
        volume: 0.62,
        type: 'sawtooth'
      },
      {
        frequency: 1450,
        endFrequency: 1180,
        offset: 0.66,
        duration: 0.28,
        volume: 0.64,
        type: 'sawtooth'
      }
    ]);
  }

  //45

  function startReportLoop() {
    if (
      !soundOn() ||
      !activeReport ||
      reportLoopTimer
    ) return;

    playReportAlarm();

    reportLoopTimer = window.setInterval(function () {
      if (!soundOn() || !activeReport) {
        stopReportLoop();
        return;
      }

      playReportAlarm();

    }, 1700);
  }

  function stopReportLoop() {
    if (reportLoopTimer) {
      window.clearInterval(reportLoopTimer);
      reportLoopTimer = null;
    }

    pendingReportSound = false;
  }

  //46

  function currentPageHasOpenedReport(report) {
    if (!report || !report.reportId || !report.url) {
      return false;
    }

    try {
      var target = new URL(
        report.url,
        window.location.href
      );

      var current = new URL(
        window.location.href
      );

      var targetId = target.searchParams.get('id');
      var currentId = current.searchParams.get('id');

      return (
        current.pathname.replace(/\/+$/, '') ===
        target.pathname.replace(/\/+$/, '') &&
        currentId !== null &&
        targetId !== null &&
        currentId === targetId
      );

    } catch (e) {
      return false;
    }
  }

  //47

  var savedReport = readPendingReport();

  if (
    savedReport &&
    currentPageHasOpenedReport(savedReport)
  ) {
    clearPendingReport();
    savedReport = null;
  }

  //48

  function desktop(title, body, url, tag) {
    if (
      !('Notification' in window) ||
      Notification.permission !== 'granted' ||
      !desktopOn()
    ) return;

    if (!document.hidden && document.hasFocus()) {
      return;
    }

    try {
      var notification = new Notification(title, {
        body: body,
        tag: tag,
        icon: BASE + '/assets/images/favicon.png'
      });

      notification.onclick = function () {
        window.focus();
        window.location.href = url;
        notification.close();
      };

    } catch (e) {}
  }

  //49

  var stack = el('div', 'alert-stack');

  stack.setAttribute('role', 'region');
  stack.setAttribute('aria-label', 'Notifications');
  stack.setAttribute('aria-live', 'polite');

  document.body.appendChild(stack);

  function removeToast(node) {
    if (!node || !node.parentNode || node._removed) {
      return;
    }

    node._removed = true;
    node.classList.add('is-leaving');

    window.setTimeout(function () {
      if (node.parentNode) {
        node.parentNode.removeChild(node);
      }
    }, 200);
  }

  function makeToast(options) {
    var toast = el(
      'div',
      'alert-toast is-' + options.kind
    );

    //50
    if (options.persistent) {
      toast.style.opacity = '1';
      toast.style.visibility = 'visible';
      toast.style.animation = 'none';
      toast.style.transition = 'none';
    }

    var iconWrap = el('span', 'alert-toast-icon');
    iconWrap.appendChild(icon(options.kind));

    var body = el('div', 'alert-toast-body');

    var heading = el(
      'strong',
      '',
      options.title
    );

    var detail = el(
      'span',
      '',
      options.text
    );

    body.appendChild(heading);
    body.appendChild(detail);

    toast.appendChild(iconWrap);
    toast.appendChild(body);

    var openLink = null;

    if (options.action) {
      var actionButton = el(
        'button',
        'alert-toast-open',
        options.label || 'Continue'
      );

      actionButton.type = 'button';

      actionButton.addEventListener('click', function (event) {
        event.preventDefault();

        options.action();
        removeToast(toast);
      });

      toast.appendChild(actionButton);

    } else if (options.url) {
      openLink = el(
        'a',
        'alert-toast-open',
        options.label || 'Open'
      );

      openLink.href = options.url;

      openLink.setAttribute(
        'aria-label',
        options.label || 'Open notification'
      );

      toast.appendChild(openLink);
    }

    //51
    //52

    if (!options.persistent) {
      var closeButton = el('button', 'alert-toast-close');

      closeButton.type = 'button';

      closeButton.setAttribute(
        'aria-label',
        'Dismiss notification'
      );

      closeButton.appendChild(icon('x'));

      closeButton.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        removeToast(toast);

        if (typeof options.onClose === 'function') {
          options.onClose();
        }
      });

      toast.appendChild(closeButton);
    }

    stack.appendChild(toast);

    return {
      node: toast,
      heading: heading,
      detail: detail,
      link: openLink
    };
  }

  var reportToast = null;

  //53

  function renderPendingReport(report) {
    if (!report) return;

    activeReport = report;

    if (
      reportToast &&
      reportToast.node &&
      reportToast.node.parentNode
    ) {
      reportToast.heading.textContent = report.title;
      reportToast.detail.textContent = report.text;
      reportToast.link.href = report.url;
      reportToast.link.textContent = report.label;

    } else {
      while (stack.children.length >= MAX_TOASTS) {
        var first = stack.firstChild;

        if (first === (reportToast && reportToast.node)) {
          break;
        }

        stack.removeChild(first);
      }

      reportToast = makeToast({
        kind: 'report',
        title: report.title,
        text: report.text,
        url: report.url,
        label: report.label,
        persistent: true
      });
    }

    startReportLoop();
  }

  function showToast(options) {
    while (stack.children.length >= MAX_TOASTS) {
      var removable = null;

      for (var i = 0; i < stack.children.length; i++) {
        if (
          stack.children[i] !==
          (reportToast && reportToast.node)
        ) {
          removable = stack.children[i];
          break;
        }
      }

      if (!removable) break;

      stack.removeChild(removable);
    }

    return makeToast(options);
  }

  //54

  function announceReport(block) {
    var items = block.items || [];
    var count = block.count || 0;

    if (!count || !items.length) return false;

    var first = items[0];

    var reportId = String(first.id);

    var label = first.code || 'New report';

    if (first.category) {
      label += ' · ' + first.category;
    }

    //55
    var url =
      BASE +
      '/admin/reports/view.php?id=' +
      encodeURIComponent(reportId);

    var report = {
      reportId: reportId,

      title: count === 1
        ? 'New report'
        : count + ' new reports',

      text: label,
      url: url,

      label: count === 1
        ? 'Open report'
        : 'Open latest report',

      updatedAt: Date.now()
    };

    //56
    savePendingReport(report);

    renderPendingReport(report);

    desktop(
      report.title,
      report.text,
      report.url,
      'bl-report'
    );

    return true;
  }

  //57

  function announceMessage(block) {
    var items = block.items || [];
    var count = block.count || 0;

    if (!count || !items.length) return false;

    var first = items[0];

    var title = count === 1
      ? 'New message'
      : count + ' new messages';

    var text = first.subject || 'New message received';

    var url = BASE + (
      count === 1
        ? '/admin/messages/view.php?id=' +
          encodeURIComponent(first.id)
        : '/admin/messages/index.php'
    );

    showToast({
      kind: 'message',
      title: title,
      text: text,
      url: url,
      label: count === 1 ? 'Open' : 'View all',
      persistent: false
    });

    //58
    pendingMessageSound = true;

    if (audioCtx && audioCtx.state === 'running') {
      flushPendingSounds();
    }

    desktop(title, text, url, 'bl-message');

    return true;
  }

  //59
  if (savedReport) {
    renderPendingReport(savedReport);
  }

  //60

  window.addEventListener('storage', function (event) {
    if (
      event.key === K_PENDING_REPORT &&
      !event.newValue
    ) {
      activeReport = null;

      stopReportLoop();

      if (reportToast && reportToast.node) {
        removeToast(reportToast.node);
      }

      reportToast = null;
    }
  });

  //61

  var bell = el('button', 'alert-bell');

  bell.type = 'button';

  bell.setAttribute('aria-label', 'Alert settings');
  bell.setAttribute('aria-expanded', 'false');
  bell.setAttribute('aria-controls', 'alertPanel');

  bell.appendChild(icon('bell'));

  var panel = el('div', 'alert-panel');

  panel.id = 'alertPanel';
  panel.hidden = true;

  panel.appendChild(
    el('strong', 'alert-panel-title', 'Alert settings')
  );

  function settingsRow(labelText) {
    var label = el('label', 'alert-row');

    var checkbox = document.createElement('input');
    checkbox.type = 'checkbox';

    label.appendChild(checkbox);
    label.appendChild(el('span', '', labelText));

    panel.appendChild(label);

    return checkbox;
  }

  var soundBox = settingsRow(
    'Play a sound for new items'
  );

  var deskBox = settingsRow(
    'Desktop notifications'
  );

  var note = el('small', 'alert-note');

  panel.appendChild(note);

  var testButton = el(
    'button',
    'alert-test',
    'Play test sound'
  );

  testButton.type = 'button';

  panel.appendChild(testButton);

  document.body.appendChild(panel);
  document.body.appendChild(bell);

  function refreshPanel() {
    var supported = 'Notification' in window;

    var permission = supported
      ? Notification.permission
      : 'unsupported';

    soundBox.checked = soundOn();

    deskBox.disabled =
      !supported || permission === 'denied';

    deskBox.checked =
      supported &&
      permission === 'granted' &&
      desktopOn();

    note.textContent =
      permission === 'unsupported'
        ? 'Desktop notifications are not supported by this browser.'
        : permission === 'denied'
          ? 'Blocked. Allow notifications for this site in your browser settings.'
          : permission === 'default'
            ? 'Tick the box, then choose Allow when your browser asks.'
            : 'On. Shown when this tab is in the background.';
  }

  bell.addEventListener('click', function () {
    panel.hidden = !panel.hidden;

    bell.setAttribute(
      'aria-expanded',
      panel.hidden ? 'false' : 'true'
    );

    refreshPanel();
  });

  document.addEventListener('click', function (event) {
    if (
      !panel.hidden &&
      !panel.contains(event.target) &&
      !bell.contains(event.target)
    ) {
      panel.hidden = true;

      bell.setAttribute(
        'aria-expanded',
        'false'
      );
    }
  });

  soundBox.addEventListener('change', function () {
    lsSet(
      K_SOUND,
      soundBox.checked ? '1' : '0'
    );

    if (soundBox.checked) {
      startReportLoop();
    } else {
      stopReportLoop();
      pendingMessageSound = false;
    }
  });

  deskBox.addEventListener('change', function () {
    if (!deskBox.checked) {
      lsSet(K_DESK, '0');
      refreshPanel();
      return;
    }

    if (Notification.permission === 'granted') {
      lsSet(K_DESK, '1');
      refreshPanel();
      return;
    }

    Notification.requestPermission().then(function (permission) {
      lsSet(
        K_DESK,
        permission === 'granted' ? '1' : '0'
      );

      refreshPanel();
    });
  });

  testButton.addEventListener('click', function () {
    var ctx = ensureAudioContext();

    if (!ctx) return;

    if (ctx.state !== 'running') {
      ctx.resume()
        .then(function () {
          playTestAlarm();
        })
        .catch(function () {});
    } else {
      playTestAlarm();
    }
  });

  refreshPanel();

  //62

  function maybeAskDesktop() {
    if (asked) return;

    asked = true;

    if (
      !('Notification' in window) ||
      Notification.permission !== 'default' ||
      lsGet(K_ASKED) === '1'
    ) return;

    showToast({
      kind: 'info',
      title: 'Turn on desktop alerts?',
      text: 'Get notified even when this tab is in the background.',
      persistent: false,
      label: 'Turn on',

      action: function () {
        lsSet(K_ASKED, '1');

        if ('Notification' in window) {
          Notification.requestPermission().then(function (permission) {
            lsSet(
              K_DESK,
              permission === 'granted' ? '1' : '0'
            );

            refreshPanel();
          });
        }
      },

      onClose: function () {
        lsSet(K_ASKED, '1');
      }
    });
  }

  //63

  function handle(data) {
    var pos = readPos();

    var fresh = !pos || pos.uid !== data.uid;

    var previous = fresh ? null : pos;

    var next = {
      uid: data.uid,

      r: data.reports
        ? data.reports.latest
        : (pos ? pos.r : 0),

      m: data.messages
        ? data.messages.latest
        : (pos ? pos.m : 0)
    };

    if (!fresh) {
      if (
        data.reports &&
        data.reports.latest > (previous.r || 0) &&
        data.reports.count
      ) {
        announceReport(data.reports);
      }

      if (
        data.messages &&
        data.messages.latest > (previous.m || 0) &&
        data.messages.count
      ) {
        announceMessage(data.messages);
      }
    }

    lsSet(K_POS, JSON.stringify(next));

    maybeAskDesktop();
  }

  function poll() {
    if (stopped || busy) return;

    busy = true;

    lastPoll = Date.now();

    var pos = readPos();

    var url = ENDPOINT + (
      pos
        ? '?r=' + encodeURIComponent(pos.r || 0) +
          '&m=' + encodeURIComponent(pos.m || 0)
        : ''
    );

    fetch(url, {
      credentials: 'same-origin',
      cache: 'no-store',
      headers: {
        'Accept': 'application/json'
      }
    })
      .then(function (response) {
        if (
          response.status === 401 ||
          response.status === 403
        ) {
          stopped = true;
          return null;
        }

        if (!response.ok) {
          throw new Error('Notification feed error');
        }

        return response.json();
      })
      .then(function (data) {
        if (data && data.ok) {
          handle(data);
        }
      })
      .catch(function () {
        //64
      })
      .then(function () {
        busy = false;
        schedule();
      });
  }

  function schedule() {
    window.clearTimeout(timer);

    if (!stopped) {
      timer = window.setTimeout(
        poll,
        document.hidden
          ? POLL_HIDDEN
          : POLL_VISIBLE
      );
    }
  }

  document.addEventListener('visibilitychange', function () {
    if (
      !document.hidden &&
      !stopped &&
      Date.now() - lastPoll > 5000
    ) {
      window.clearTimeout(timer);
      poll();
    }
  });

  //65
  ensureAudioContext();

  window.setTimeout(poll, 1200);

})();
