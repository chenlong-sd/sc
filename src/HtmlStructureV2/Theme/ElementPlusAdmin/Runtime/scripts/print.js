(function (global) {
  'use strict';

  var HIDE_SELECTOR = '.sc-v2-page__header, .sc-v2-actions, .xndg-print-actions';
  var PRINT_STYLE = [
    'html,body{background:#fff!important;margin:0!important;padding:0!important;}',
    'body{padding:12px!important;}',
    HIDE_SELECTOR + '{display:none!important;}',
    '.sc-v2-page{width:auto!important;max-width:none!important;margin:0!important;padding:0!important;}',
    '.el-card{box-shadow:none!important;}',
    '@page{margin:10mm;}'
  ].join('');

  function syncFormValues(source, clone) {
    var formSelector = 'input,textarea,select';
    var liveFields = Array.prototype.slice.call(source.querySelectorAll(formSelector));
    var clonedFields = Array.prototype.slice.call(clone.querySelectorAll(formSelector));

    liveFields.forEach(function (live, index) {
      var copied = clonedFields[index];
      if (!live || !copied) {
        return;
      }

      if (live.tagName === 'INPUT') {
        var type = String(live.type || '').toLowerCase();
        if (type === 'checkbox' || type === 'radio') {
          live.checked ? copied.setAttribute('checked', 'checked') : copied.removeAttribute('checked');
          return;
        }
        copied.setAttribute('value', live.value);
        return;
      }

      if (live.tagName === 'TEXTAREA') {
        copied.textContent = live.value;
        return;
      }

      if (live.tagName === 'SELECT') {
        Array.prototype.forEach.call(live.options, function (option, optionIndex) {
          if (!copied.options[optionIndex]) {
            return;
          }
          if (option.selected) {
            copied.options[optionIndex].setAttribute('selected', 'selected');
          } else {
            copied.options[optionIndex].removeAttribute('selected');
          }
        });
      }
    });
  }

  global.scV2PrintPage = function (selector) {
    selector = typeof selector === 'string' && selector.trim() !== '' ? selector.trim() : '.sc-v2-page';
    var source = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (!source || source.nodeType !== 1) {
      window.alert('未找到可打印的内容：' + selector);
      return;
    }

    var iframe = document.createElement('iframe');
    iframe.setAttribute('aria-hidden', 'true');
    iframe.style.cssText = 'position:fixed;width:0;height:0;left:-9999px;top:-9999px;border:0;';
    document.body.appendChild(iframe);

    var printDocument = iframe.contentDocument || iframe.contentWindow.document;
    printDocument.title = '打印';

    var meta = printDocument.createElement('meta');
    meta.setAttribute('charset', 'utf-8');
    printDocument.head.appendChild(meta);

    var base = printDocument.createElement('base');
    base.href = window.location.origin + '/';
    printDocument.head.appendChild(base);

    Array.prototype.forEach.call(
      document.querySelectorAll('link[rel="stylesheet"], style'),
      function (node) {
        printDocument.head.appendChild(printDocument.importNode(node, true));
      }
    );

    var printStyle = printDocument.createElement('style');
    printStyle.textContent = PRINT_STYLE;
    printDocument.head.appendChild(printStyle);

    printDocument.body.innerHTML = '';
    var clone = printDocument.importNode(source, true);
    syncFormValues(source, clone);
    printDocument.body.appendChild(clone);

    var cleanup = function () {
      if (iframe.parentNode) {
        iframe.parentNode.removeChild(iframe);
      }
    };

    var images = Array.prototype.slice.call(printDocument.images || []);
    var imageReady = images.map(function (image) {
      if (image.complete) {
        return Promise.resolve();
      }
      return new Promise(function (resolve) {
        image.addEventListener('load', resolve, { once: true });
        image.addEventListener('error', resolve, { once: true });
      });
    });

    var links = Array.prototype.slice.call(printDocument.querySelectorAll('link[rel="stylesheet"]'));
    var styleReady = links.map(function (link) {
      if (link.sheet) {
        return Promise.resolve();
      }
      return new Promise(function (resolve) {
        link.addEventListener('load', resolve, { once: true });
        link.addEventListener('error', resolve, { once: true });
      });
    });

    var ready = Promise.all(imageReady.concat(styleReady));
    var timeout = new Promise(function (resolve) {
      window.setTimeout(resolve, 1200);
    });

    Promise.race([ready, timeout]).finally(function () {
      window.setTimeout(function () {
        var iframeWindow = iframe.contentWindow;
        if (!iframeWindow) {
          cleanup();
          return;
        }

        iframeWindow.onafterprint = cleanup;
        iframeWindow.focus();
        iframeWindow.print();
        window.setTimeout(cleanup, 60000);
      }, 200);
    });
  };
})(window);