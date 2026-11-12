//66
document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var input = document.getElementById(btn.dataset.togglePassword);
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.textContent = show ? 'Hide' : 'Show';
  });
});
var toast = document.querySelector('.toast');
if (toast) setTimeout(function () { toast.remove(); }, 5000);
//67
document.querySelectorAll('form[data-confirm]').forEach(function (form) {
  form.addEventListener('submit', function (e) { if (!confirm(form.dataset.confirm)) e.preventDefault(); });
});
//68
var navToggle = document.getElementById('navToggle'), siteNav = document.getElementById('siteNav');
if (navToggle && siteNav) navToggle.addEventListener('click', function () {
  var open = siteNav.classList.toggle('is-open');
  navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
});

//69
(function () {
  var form = document.getElementById('reportForm');
  if (!form || !window.DataTransfer) return;
  var photos = document.getElementById('photosInput'), camera = document.getElementById('cameraInput'),
      grid = document.getElementById('previewGrid'), drop = document.getElementById('dropzone'),
      skip = document.getElementById('skipPhoto'), submit = document.getElementById('reportSubmit'),
      MAX = 5, dt = new DataTransfer();

  //70
  if (window.matchMedia('(pointer: coarse)').matches && window.innerWidth <= 900) form.classList.add('is-mobile');

  //71
  function compress(file) {
    return new Promise(function (resolve) {
      var done = function (blob) { resolve(blob ? new File([blob], 'photo-' + Date.now() + '.jpg', { type: 'image/jpeg' }) : file); };
      var draw = function (src, w, h) {
        var scale = Math.min(1, 1600 / Math.max(w, h)), c = document.createElement('canvas');
        c.width = Math.round(w * scale); c.height = Math.round(h * scale);
        c.getContext('2d').drawImage(src, 0, 0, c.width, c.height);
        c.toBlob(done, 'image/jpeg', 0.82);
      };
      if (window.createImageBitmap) {
        createImageBitmap(file, { imageOrientation: 'from-image' }).then(function (b) { draw(b, b.width, b.height); }, function () { done(null); });
      } else {
        var img = new Image(); img.onload = function () { draw(img, img.naturalWidth, img.naturalHeight); }; img.onerror = function () { done(null); };
        img.src = URL.createObjectURL(file);
      }
    });
  }

  function render() {
    photos.files = dt.files;
    grid.innerHTML = '';
    Array.prototype.forEach.call(dt.files, function (f, i) {
      var item = document.createElement('div'), img = document.createElement('img'), btn = document.createElement('button');
      item.className = 'preview-item'; img.src = URL.createObjectURL(f); img.alt = 'Photo ' + (i + 1);
      btn.type = 'button'; btn.className = 'preview-remove'; btn.textContent = '\u00d7'; btn.setAttribute('aria-label', 'Remove photo ' + (i + 1));
      btn.addEventListener('click', function () {
        var next = new DataTransfer();
        Array.prototype.forEach.call(dt.files, function (x, j) { if (j !== i) next.items.add(x); });
        dt = next; render();
      });
      item.appendChild(img); item.appendChild(btn); grid.appendChild(item);
    });
    form.classList.toggle('has-photo', dt.files.length > 0);
  }

  function add(list) {
    var picked = Array.prototype.filter.call(list, function (f) { return /^image\//.test(f.type); }).slice(0, MAX - dt.files.length);
    Promise.all(picked.map(compress)).then(function (files) {
      files.forEach(function (f) { if (dt.files.length < MAX) dt.items.add(f); });
      render();
    });
  }

  camera.addEventListener('change', function () { add(camera.files); camera.value = ''; });
  photos.addEventListener('change', function () { var l = Array.prototype.slice.call(photos.files); photos.files = dt.files; add(l); });
  if (skip) skip.addEventListener('click', function () { form.classList.add('skipped'); });
  if (drop) {
    ['dragenter', 'dragover'].forEach(function (t) { drop.addEventListener(t, function (e) { e.preventDefault(); drop.classList.add('is-over'); }); });
    ['dragleave', 'drop'].forEach(function (t) { drop.addEventListener(t, function (e) { e.preventDefault(); drop.classList.remove('is-over'); }); });
    drop.addEventListener('drop', function (e) { add(e.dataTransfer.files); });
  }
  form.addEventListener('submit', function () { submit.disabled = true; submit.textContent = 'Sending...'; });
  if (form.querySelector('[name=category]:checked')) form.classList.add('skipped'); //72
})();
