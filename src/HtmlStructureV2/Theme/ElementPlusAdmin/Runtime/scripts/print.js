(function (global) {
  'use strict';

  var HIDE_SELECTOR = '.sc-v2-page__header, .sc-v2-actions, .xndg-print-actions';
  var PRINT_STYLE = [
    'html,body{background:#fff!important;margin:0!important;padding:0!important;width:100%!important;box-sizing:border-box!important;overflow:visible!important;}',
    'body,body *{visibility:visible!important;box-sizing:border-box!important;}',
    'body{padding:12px!important;}',
    HIDE_SELECTOR + '{display:none!important;}',
    '.sc-v2-page{width:100%!important;max-width:100%!important;margin:0!important;padding:0!important;overflow:visible!important;}',
    '.el-card{box-shadow:none!important;}',
    '.xndg-print-el-table{width:100%!important;border-collapse:collapse!important;table-layout:auto!important;margin:0!important;}',
    '.xndg-print-el-table th,.xndg-print-el-table td{border:1px solid #e5e7eb!important;padding:4px 6px!important;white-space:normal!important;word-break:break-word!important;overflow-wrap:anywhere!important;line-height:1.4!important;font-weight:normal!important;color:inherit!important;}',
    '.xndg-print-el-table th{background:#f7f7f7!important;font-weight:600!important;}',
    '@page{size:A4 portrait;margin:10mm;}'
  ].join('');

  function normalizeElementPlusTables(printWindow, printDocument) {
    var createCell = function (sourceCell, tagName) {
      var cell = printDocument.createElement(tagName);
      var content = sourceCell.querySelector('.cell') || sourceCell;
      cell.innerHTML = content.innerHTML;
      if (sourceCell.colSpan > 1) {
        cell.colSpan = sourceCell.colSpan;
      }
      if (sourceCell.rowSpan > 1) {
        cell.rowSpan = sourceCell.rowSpan;
      }
      var align = printWindow.getComputedStyle(sourceCell).textAlign;
      if (align) {
        cell.style.textAlign = align;
      }

      return cell;
    };

    Array.prototype.forEach.call(printDocument.querySelectorAll('.el-table'), function (tableRoot) {
      var headerTable = tableRoot.querySelector('.el-table__header');
      var bodyTable = tableRoot.querySelector('.el-table__body');
      if (!headerTable && !bodyTable) {
        return;
      }

      var nativeTable = printDocument.createElement('table');
      nativeTable.className = 'xndg-print-el-table';
      var columnCount = 0;
      var bodyRowCount = 0;

      if (headerTable) {
        var thead = printDocument.createElement('thead');
        Array.prototype.forEach.call(headerTable.querySelectorAll('thead tr'), function (row) {
          var tr = printDocument.createElement('tr');
          Array.prototype.forEach.call(row.children, function (cell) {
            tr.appendChild(createCell(cell, 'th'));
          });
          columnCount = Math.max(columnCount, row.children.length);
          thead.appendChild(tr);
        });
        nativeTable.appendChild(thead);
      }

      if (bodyTable) {
        var tbody = printDocument.createElement('tbody');
        Array.prototype.forEach.call(bodyTable.querySelectorAll('tbody tr'), function (row) {
          var tr = printDocument.createElement('tr');
          Array.prototype.forEach.call(row.children, function (cell) {
            tr.appendChild(createCell(cell, 'td'));
          });
          columnCount = Math.max(columnCount, row.children.length);
          bodyRowCount++;
          tbody.appendChild(tr);
        });

        if (bodyRowCount === 0) {
          var emptyText = tableRoot.querySelector('.el-table__empty-text');
          if (emptyText) {
            var emptyRow = printDocument.createElement('tr');
            var emptyCell = printDocument.createElement('td');
            emptyCell.colSpan = Math.max(columnCount, 1);
            emptyCell.textContent = emptyText.textContent || '暂无数据';
            emptyRow.appendChild(emptyCell);
            tbody.appendChild(emptyRow);
          }
        }
        nativeTable.appendChild(tbody);
      }

      if (tableRoot.parentNode) {
        tableRoot.parentNode.replaceChild(nativeTable, tableRoot);
      }
    });
  }

  function fitContentToWidth(printWindow, printDocument) {
    var availableWidth = Number(printWindow.innerWidth || printDocument.documentElement.clientWidth || 0);
    if (availableWidth <= 0) {
      return;
    }

    var contentWidth = Math.max(
      Number(printDocument.documentElement.scrollWidth || 0),
      Number(printDocument.body.scrollWidth || 0)
    );
    if (contentWidth <= availableWidth) {
      return;
    }

    var scale = availableWidth / contentWidth;
    printDocument.body.style.zoom = String(scale < 0.35 ? 0.35 : scale);
  }

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
    iframe.style.cssText = 'position:fixed;width:190mm;height:277mm;left:-9999px;top:-9999px;border:0;';
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

        normalizeElementPlusTables(iframeWindow, printDocument);
        fitContentToWidth(iframeWindow, printDocument);
        iframeWindow.onafterprint = cleanup;
        iframeWindow.focus();
        iframeWindow.print();
        window.setTimeout(cleanup, 60000);
      }, 200);
    });
  };
})(window);