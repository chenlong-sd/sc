(() => {
  if (globalThis.__SC_V2_HOST_URL_DIALOG__) {
    return;
  }

  const defaultWidth = '1000px';
  const defaultHeight = '70vh';
  let dialogVm = null;

  const normalizeString = (value, fallback = '') => {
    const normalized = typeof value === 'string' ? value.trim() : '';
    return normalized !== '' ? normalized : fallback;
  };

  const buildUrlWithQuery = (url, query) => {
    const parsedUrl = new URL(url, window.location.href);
    const entries = query && typeof query === 'object' ? Object.entries(query) : [];

    entries.forEach(([key, value]) => {
      if (value === null || value === undefined || value === '') {
        return;
      }

      if (Array.isArray(value)) {
        parsedUrl.searchParams.delete(key);
        value.forEach((item) => {
          if (item !== null && item !== undefined && item !== '') {
            parsedUrl.searchParams.append(key, String(item));
          }
        });
        return;
      }

      parsedUrl.searchParams.set(key, String(value));
    });

    return parsedUrl.toString();
  };

  const ensureDialog = () => {
    if (dialogVm) {
      return dialogVm;
    }
    if (!globalThis.Vue?.createApp || !globalThis.ElementPlus || !document.body) {
      return null;
    }

    const mountNode = document.createElement('div');
    mountNode.className = 'sc-v2-host-url-dialog-runtime';
    document.body.appendChild(mountNode);

    const app = Vue.createApp({
      data() {
        return {
          visible: false,
          title: '',
          width: defaultWidth,
          height: defaultHeight,
          iframeUrl: '',
        };
      },
      methods: {
        open(dialog = {}) {
          const url = normalizeString(dialog?.url);
          if (url === '') {
            return false;
          }

          this.title = normalizeString(dialog?.title);
          this.width = normalizeString(dialog?.width, defaultWidth);
          this.height = normalizeString(dialog?.height, defaultHeight);
          this.iframeUrl = buildUrlWithQuery(url, dialog?.query);
          this.visible = true;

          return true;
        },
        handleClosed() {
          this.iframeUrl = '';
        },
      },
      template: `
        <el-dialog
          v-model="visible"
          :title="title"
          :width="width"
          append-to-body
          destroy-on-close
          class="sc-v2-host-url-dialog"
          @closed="handleClosed"
        >
          <iframe
            v-if="iframeUrl"
            :src="iframeUrl"
            :style="{ height }"
            class="sc-v2-host-url-dialog__iframe"
          ></iframe>
        </el-dialog>
      `,
    });

    app.use(ElementPlus, globalThis.ElementPlusLocaleZhCn
      ? { locale: globalThis.ElementPlusLocaleZhCn }
      : {});
    dialogVm = app.mount(mountNode);

    return dialogVm;
  };

  const styleId = 'sc-v2-host-url-dialog-style';
  if (!document.getElementById(styleId)) {
    const style = document.createElement('style');
    style.id = styleId;
    style.textContent = `
      .sc-v2-host-url-dialog .el-dialog__body { padding: 0; overflow: hidden; }
      .sc-v2-host-url-dialog__iframe { display: block; width: 100%; border: 0; }
    `;
    document.head?.appendChild(style);
  }

  globalThis.__SC_V2_HOST_URL_DIALOG__ = {
    open(dialog = {}) {
      const vm = ensureDialog();
      return vm ? vm.open(dialog) : false;
    },
  };
})();
