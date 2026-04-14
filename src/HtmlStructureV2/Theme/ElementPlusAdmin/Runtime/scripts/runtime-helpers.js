        globalThis.__SC_V2_RUNTIME_HELPERS__ = globalThis.__SC_V2_RUNTIME_HELPERS__ || (() => {
          const isObject = (value) => value && typeof value === 'object' && !Array.isArray(value);
          const clone = (value) => {
            if (Array.isArray(value)) {
              return value.map((item) => clone(item));
            }
            if (value instanceof RegExp) {
              return new RegExp(value.source, value.flags);
            }
            if (isObject(value)) {
              const output = {};
              Object.keys(value).forEach((key) => {
                output[key] = clone(value[key]);
              });
              return output;
            }
            return value;
          };
          const isBlank = (value) => value === '' || value === null || value === undefined || (Array.isArray(value) && value.length === 0);
          const isColumnDisplayBlank = (value) => isBlank(value);
          const isRowArray = (value) => {
            if (!Array.isArray(value)) return false;
            if (value.length === 0) return true;
            return typeof value[0] === 'object' || Array.isArray(value[0]);
          };
          const getByPath = (source, path) => {
            if (!path) return undefined;
            return String(path)
              .split('.')
              .reduce((current, segment) => current == null ? undefined : current[segment], source);
          };
          const setByPath = (source, path, value) => {
            if ((!isObject(source) && !Array.isArray(source)) || !path) return;

            const segments = String(path).split('.').filter(Boolean);
            if (segments.length === 0) return;

            let current = source;
            segments.slice(0, -1).forEach((segment, index) => {
              const nextSegment = segments[index + 1];
              const shouldUseArray = /^\d+$/.test(String(nextSegment || ''));
              const currentValue = current[segment];
              if (
                currentValue === null
                || currentValue === undefined
                || (typeof currentValue !== 'object')
              ) {
                current[segment] = shouldUseArray ? [] : {};
              }
              current = current[segment];
            });

            current[segments[segments.length - 1]] = value;
          };
          const findArrayGroupConfig = (arrayGroups = [], path = '') => {
            const target = typeof path === 'string' ? path.trim() : '';
            if (target === '') {
              return null;
            }

            return (Array.isArray(arrayGroups) ? arrayGroups : []).find((groupCfg) => {
              return String(groupCfg?.path || '').trim() === target;
            }) || null;
          };
          const rebaseArrayGroupConfigs = (arrayGroups = [], prefix = '') => {
            const normalizedPrefix = typeof prefix === 'string' ? prefix.trim() : '';
            if (normalizedPrefix === '') {
              return Array.isArray(arrayGroups) ? arrayGroups : [];
            }

            const needle = normalizedPrefix + '.';
            return (Array.isArray(arrayGroups) ? arrayGroups : []).reduce((output, groupCfg) => {
              const path = typeof groupCfg?.path === 'string' ? groupCfg.path.trim() : '';
              if (!path.startsWith(needle)) {
                return output;
              }

              output.push(Object.assign({}, groupCfg, {
                path: path.slice(needle.length)
              }));

              return output;
            }, []);
          };
          const initializeFormArrayGroupRowsBySchema = (rows, groupCfg = {}) => {
            const rowDefaults = isObject(groupCfg?.defaultRow) ? groupCfg.defaultRow : {};
            const rowArrayGroups = Array.isArray(groupCfg?.rowArrayGroups) ? groupCfg.rowArrayGroups : [];

            if (!Array.isArray(rows)) {
              return clone(groupCfg?.initialRows || []);
            }

            return rows.map((row) => initializeFormModelBySchema(rowDefaults, row, rowArrayGroups));
          };
          const initializeFormModelBySchema = (defaults = {}, values = {}, arrayGroups = []) => {
            const nextModel = isObject(defaults) ? clone(defaults) : {};
            const source = isObject(values) ? values : {};

            Object.keys(nextModel).forEach((key) => {
              if (!Object.prototype.hasOwnProperty.call(source, key)) {
                return;
              }

              const sourceValue = source[key];
              if (sourceValue === undefined) {
                return;
              }

              const defaultValue = nextModel[key];
              const arrayGroupCfg = findArrayGroupConfig(arrayGroups, key);
              if (arrayGroupCfg) {
                nextModel[key] = initializeFormArrayGroupRowsBySchema(sourceValue, arrayGroupCfg);
                return;
              }

              if (isObject(defaultValue)) {
                nextModel[key] = initializeFormModelBySchema(
                  defaultValue,
                  sourceValue,
                  rebaseArrayGroupConfigs(arrayGroups, key)
                );
                return;
              }

              if (Array.isArray(defaultValue)) {
                nextModel[key] = Array.isArray(sourceValue) ? clone(sourceValue) : clone(defaultValue);
                return;
              }

              nextModel[key] = clone(sourceValue);
            });

            return nextModel;
          };
          const extractPayload = (response) => {
            if (response && typeof response === 'object' && Object.prototype.hasOwnProperty.call(response, 'data')) {
              return response.data;
            }
            return response;
          };
          const extractLoadData = (payload, dataPath = null) => {
            const candidates = [];
            const normalizedPath = typeof dataPath === 'string' ? dataPath.trim() : '';

            if (normalizedPath !== '') {
              candidates.push(getByPath(payload, normalizedPath));
            } else {
              if (isObject(payload)) {
                candidates.push(payload.data, payload.result, payload.payload);
                if (isObject(payload.data)) {
                  candidates.push(payload.data.data, payload.data.result, payload.data.payload);
                }
              }
              candidates.push(payload);
            }

            for (const item of candidates) {
              if (isObject(item)) {
                return item;
              }
            }

            return {};
          };
          const readPageQuery = (search = window.location.search) => {
            const output = {};
            const rawSearch = typeof search === 'string' ? search.trim() : '';
            const params = new URLSearchParams(rawSearch.startsWith('?') ? rawSearch.slice(1) : rawSearch);

            params.forEach((value, key) => {
              if (Object.prototype.hasOwnProperty.call(output, key)) {
                if (Array.isArray(output[key])) {
                  output[key].push(value);
                } else {
                  output[key] = [output[key], value];
                }

                return;
              }

              output[key] = value;
            });

            return output;
          };
          const readPageLocation = (locationLike = null) => {
            const source = locationLike && typeof locationLike === 'object'
              ? locationLike
              : (typeof window !== 'undefined' ? window.location : null);
            const href = typeof source?.href === 'string' ? source.href : '';
            const pathname = typeof source?.pathname === 'string' ? source.pathname : '';
            const search = typeof source?.search === 'string' ? source.search : '';
            const hash = typeof source?.hash === 'string' ? source.hash : '';
            const query = readPageQuery(search);

            return {
              url: href,
              href,
              path: pathname,
              pathname,
              search,
              hash,
              query,
            };
          };
          const resolvePageMode = (query = {}, queryKey = 'id') => {
            const normalizedKey = typeof queryKey === 'string' && queryKey.trim() !== ''
              ? queryKey.trim()
              : 'id';
            const value = getByPath(query || {}, normalizedKey);

            return isBlank(value) ? 'create' : 'edit';
          };
          const resolveMessage = (payload, fallback = '') => {
            if (typeof payload === 'string' && payload !== '') return payload;
            if (!isObject(payload)) return fallback;
            return payload.message || payload.msg || payload.error || fallback;
          };
          const isSuccessPayload = (payload) => {
            if (!isObject(payload)) return true;
            if (typeof payload.success === 'boolean') return payload.success;
            if (payload.code !== undefined) return [0, 200, '0', '200'].includes(payload.code);
            if (payload.status !== undefined) {
              if (typeof payload.status === 'number') {
                return payload.status >= 200 && payload.status < 300;
              }
              return ['success', 'ok'].includes(String(payload.status).toLowerCase());
            }
            return true;
          };
          const ensureSuccess = (payload, fallback) => {
            if (isSuccessPayload(payload)) {
              return payload;
            }
            throw new Error(resolveMessage(payload, fallback));
          };
          const registerElementPlusIcons = (app) => {
            if (!app || typeof app.component !== 'function') {
              return;
            }

            const icons = globalThis.ElementPlusIconsVue;
            if (!icons || typeof icons !== 'object') {
              return;
            }

            Object.entries(icons).forEach(([name, component]) => {
              if (!name || !component) {
                return;
              }

              app.component(name, component);
            });
          };
          const registerScV2Components = (app) => {
            if (!app || typeof app.component !== 'function') {
              return;
            }

            if (!app.component('sc-v2-icon-selector')) {
              app.component('sc-v2-icon-selector', {
                inheritAttrs: false,
                props: {
                  modelValue: {
                    type: String,
                    default: ''
                  }
                },
                emits: ['update:modelValue'],
                data() {
                  return {
                    visible: false,
                    searchKeyword: '',
                  };
                },
                watch: {
                  visible(newValue) {
                    if (!newValue) {
                      this.searchKeyword = '';
                      return;
                    }

                    this.searchKeyword = '';
                    this.scrollResultsToTop();
                    this.focusSearchInput();
                  },
                  filterKeyword(newValue, oldValue) {
                    if (newValue === oldValue || !this.visible) {
                      return;
                    }

                    this.scrollResultsToTop();
                  }
                },
                computed: {
                  availableIcons() {
                    const icons = globalThis.ElementPlusIconsVue;
                    if (!icons || typeof icons !== 'object') {
                      return [];
                    }

                    return Object.keys(icons).sort((left, right) => String(left).localeCompare(String(right), 'zh-CN'));
                  },
                  previewIcon() {
                    const current = String(this.modelValue || '');
                    if (current === '') {
                      return '';
                    }

                    return this.availableIcons.includes(current) ? current : '';
                  },
                  filterKeyword() {
                    return String(this.searchKeyword || '').trim().toLowerCase();
                  },
                  matchedIcons() {
                    if (this.filterKeyword === '') {
                      return this.availableIcons;
                    }

                    return this.availableIcons.filter((iconName) => String(iconName).toLowerCase().includes(this.filterKeyword));
                  },
                  unmatchedIcons() {
                    if (this.filterKeyword === '') {
                      return [];
                    }

                    return this.availableIcons.filter((iconName) => !String(iconName).toLowerCase().includes(this.filterKeyword));
                  },
                  hasMatchedIcons() {
                    return this.matchedIcons.length > 0;
                  },
                  hasUnmatchedIcons() {
                    return this.unmatchedIcons.length > 0;
                  },
                  shouldShowGroupedResult() {
                    return this.filterKeyword !== '';
                  },
                  filteredIcons() {
                    const keyword = this.filterKeyword;
                    if (keyword === '') {
                      return this.availableIcons;
                    }

                    return this.availableIcons.filter((iconName) => String(iconName).toLowerCase().includes(keyword));
                  }
                },
                methods: {
                  normalizeValue(value) {
                    return value == null ? '' : String(value);
                  },
                  focusSearchInput() {
                    this.$nextTick(() => {
                      const input = this.$refs.searchInput;
                      if (input && typeof input.focus === 'function') {
                        input.focus();
                      }
                    });
                  },
                  scrollResultsToTop() {
                    this.$nextTick(() => {
                      const scrollbar = this.$refs.iconScrollbar;
                      if (!scrollbar) {
                        return;
                      }

                      if (typeof scrollbar.setScrollTop === 'function') {
                        scrollbar.setScrollTop(0);
                        return;
                      }

                      const wrap = scrollbar.wrapRef || scrollbar.wrap$ || null;
                      if (wrap && typeof wrap.scrollTop === 'number') {
                        wrap.scrollTop = 0;
                      }
                    });
                  },
                  updateValue(value) {
                    this.$emit('update:modelValue', this.normalizeValue(value));
                  },
                  selectIcon(iconName) {
                    this.updateValue(iconName);
                    this.visible = false;
                  }
                },
                template: `
                  <el-popover
                    v-model:visible="visible"
                    :width="540"
                    trigger="click"
                    placement="bottom-start"
                  >
                    <template #reference>
                      <el-input
                        v-bind="$attrs"
                        :model-value="modelValue"
                        @update:model-value="updateValue"
                      >
                        <template #prefix>
                          <el-icon v-if="previewIcon" class="el-input__icon">
                            <component :is="previewIcon"></component>
                          </el-icon>
                        </template>
                      </el-input>
                    </template>
                    <div class="sc-v2-icon-selector-panel">
                      <el-input
                        ref="searchInput"
                        v-model="searchKeyword"
                        placeholder="搜索图标"
                        clearable
                      ></el-input>
                      <el-scrollbar ref="iconScrollbar" max-height="350px">
                        <div v-if="shouldShowGroupedResult" class="sc-v2-icon-selector-group">
                          <div v-if="hasMatchedIcons" class="sc-v2-icon-selector-group__section">
                            <div class="sc-v2-icon-selector-group__title">匹配结果</div>
                            <div class="sc-v2-icon-selector">
                              <div
                                v-for="iconName in matchedIcons"
                                :key="'matched-' + iconName"
                                :class="['sc-v2-icon-selector__item', { 'is-active': iconName === modelValue }]"
                                @click.stop="selectIcon(iconName)"
                              >
                                <div class="sc-v2-icon-selector__preview">
                                  <el-icon><component :is="iconName"></component></el-icon>
                                </div>
                                <div class="sc-v2-icon-selector__label">{{ iconName }}</div>
                              </div>
                            </div>
                          </div>

                          <div
                            v-if="hasUnmatchedIcons"
                            :class="['sc-v2-icon-selector-group__section', { 'has-divider': hasMatchedIcons }]"
                          >
                            <div class="sc-v2-icon-selector-group__title">其他图标</div>
                            <div class="sc-v2-icon-selector">
                              <div
                                v-for="iconName in unmatchedIcons"
                                :key="'unmatched-' + iconName"
                                :class="['sc-v2-icon-selector__item', 'is-unmatched', { 'is-active': iconName === modelValue }]"
                                @click.stop="selectIcon(iconName)"
                              >
                                <div class="sc-v2-icon-selector__preview">
                                  <el-icon><component :is="iconName"></component></el-icon>
                                </div>
                                <div class="sc-v2-icon-selector__label">{{ iconName }}</div>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div v-else class="sc-v2-icon-selector">
                          <div
                            v-for="iconName in filteredIcons"
                            :key="iconName"
                            :class="['sc-v2-icon-selector__item', { 'is-active': iconName === modelValue }]"
                            @click.stop="selectIcon(iconName)"
                          >
                            <div class="sc-v2-icon-selector__preview">
                              <el-icon><component :is="iconName"></component></el-icon>
                            </div>
                            <div class="sc-v2-icon-selector__label">{{ iconName }}</div>
                          </div>
                        </div>
                      </el-scrollbar>
                    </div>
                  </el-popover>
                `
              });
            }

            if (!app.component('sc-v2-rich-editor')) {
              app.component('sc-v2-rich-editor', {
                inheritAttrs: false,
                props: {
                  modelValue: {
                    type: String,
                    default: ''
                  },
                  placeholder: {
                    type: String,
                    default: ''
                  },
                  config: {
                    type: Object,
                    default: () => ({})
                  },
                  uploadUrl: {
                    type: String,
                    default: ''
                  },
                  disabled: {
                    type: Boolean,
                    default: false
                  }
                },
                emits: ['update:modelValue'],
                data() {
                  return {
                    editor: null,
                    mountRetryTimer: 0,
                    mountId: `sc-v2-rich-editor-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`
                  };
                },
                watch: {
                  modelValue(newValue) {
                    this.syncEditorValue(newValue);
                  },
                  disabled(newValue) {
                    this.syncDisabledState(this.resolveDisabledState(newValue));
                  }
                },
                mounted() {
                  this.tryMountEditor();
                },
                beforeUnmount() {
                  this.clearMountRetry();
                  const mountEl = document.getElementById(this.mountId);
                  if (mountEl) {
                    mountEl.innerHTML = '';
                  }
                  this.editor = null;
                },
                methods: {
                  normalizeValue(value) {
                    return value == null ? '' : String(value);
                  },
                  resolveDisabledState(disabled = this.disabled) {
                    return Boolean(disabled) || (isObject(this.config) && this.config.disabled === true);
                  },
                  clearMountRetry() {
                    if (!this.mountRetryTimer) {
                      return;
                    }

                    globalThis.clearTimeout(this.mountRetryTimer);
                    this.mountRetryTimer = 0;
                  },
                  queueMountRetry() {
                    if (this.mountRetryTimer) {
                      return;
                    }

                    this.mountRetryTimer = globalThis.setTimeout(() => {
                      this.mountRetryTimer = 0;
                      this.tryMountEditor();
                    }, 60);
                  },
                  resolveUploadUrl(response) {
                    const payload = ensureSuccess(extractPayload(response), '上传失败');
                    if (typeof payload === 'string' && payload !== '') {
                      return payload;
                    }

                    return (payload && (payload.link || payload.data || payload.url || payload.fileFullPath)) || '';
                  },
                  async uploadFile(file, { onProgress } = {}) {
                    if (!this.uploadUrl) {
                      throw new Error('上传地址未配置');
                    }

                    const formData = new FormData();
                    formData.append('file', file);

                    const response = await axios.post(this.uploadUrl, formData, {
                      headers: { 'Content-Type': 'multipart/form-data' },
                      onUploadProgress: (event) => {
                        if (typeof onProgress !== 'function' || !event || !event.total) {
                          return;
                        }

                        onProgress(Math.round((event.loaded / event.total) * 100));
                      },
                    });

                    const url = this.resolveUploadUrl(response);
                    if (!url) {
                      throw new Error('上传失败');
                    }

                    return url;
                  },
                  buildEditorOptions() {
                    const options = isObject(this.config) ? clone(this.config) : {};
                    const userOnChange = typeof options.onChange === 'function' ? options.onChange : null;
                    const userOnFocus = typeof options.onFocus === 'function' ? options.onFocus : null;
                    const userOnBlur = typeof options.onBlur === 'function' ? options.onBlur : null;

                    options.placeholder = options.placeholder || this.placeholder || '请输入内容...';
                    options.initialHTML = this.normalizeValue(this.modelValue);
                    options.disabled = this.resolveDisabledState();

                    options.onChange = (payload) => {
                      try {
                        if (typeof userOnChange === 'function') {
                          userOnChange(payload);
                        }
                      } catch (error) {
                        console.warn(error);
                      }

                      const html = this.normalizeValue(
                        payload && Object.prototype.hasOwnProperty.call(payload, 'html')
                          ? payload.html
                          : ''
                      );

                      this.$emit('update:modelValue', html);
                    };

                    options.onFocus = (payload) => {
                      try {
                        if (typeof userOnFocus === 'function') {
                          userOnFocus(payload);
                        }
                      } catch (error) {
                        console.warn(error);
                      }
                    };

                    options.onBlur = (payload) => {
                      try {
                        if (typeof userOnBlur === 'function') {
                          userOnBlur(payload);
                        }
                      } catch (error) {
                        console.warn(error);
                      }
                    };

                    if (this.uploadUrl) {
                      if (typeof options.onImageUpload !== 'function') {
                        options.onImageUpload = (file, hooks = {}) => this.uploadFile(file, hooks);
                      }

                      if (typeof options.onFileUpload !== 'function') {
                        options.onFileUpload = (file, hooks = {}) => this.uploadFile(file, hooks);
                      }
                    }

                    return options;
                  },
                  tryMountEditor() {
                    if (this.editor) {
                      return;
                    }

                    const mountEl = document.getElementById(this.mountId);
                    const Editor = globalThis.SimpleRichEditor;

                    if (!mountEl || typeof Editor !== 'function') {
                      this.queueMountRetry();
                      return;
                    }

                    try {
                      this.editor = new Editor('#' + this.mountId, this.buildEditorOptions()).init();
                      this.syncEditorValue(this.modelValue);
                      this.syncDisabledState(this.resolveDisabledState());
                    } catch (error) {
                      console.warn(error);
                      this.queueMountRetry();
                    }
                  },
                  syncEditorValue(value) {
                    if (
                      !this.editor
                      || typeof this.editor.getHTML !== 'function'
                      || typeof this.editor.setHTML !== 'function'
                    ) {
                      return;
                    }

                    const next = this.normalizeValue(value);

                    try {
                      if (this.normalizeValue(this.editor.getHTML()) !== next) {
                        this.editor.setHTML(next);
                      }
                    } catch (error) {
                      console.warn(error);
                    }
                  },
                  syncDisabledState(disabled) {
                    if (!this.editor) {
                      return;
                    }

                    try {
                      if (typeof this.editor.setDisabled === 'function') {
                        this.editor.setDisabled(Boolean(disabled));
                        return;
                      }

                      if (typeof this.editor.setReadOnly === 'function') {
                        this.editor.setReadOnly(Boolean(disabled));
                      }
                    } catch (error) {
                      console.warn(error);
                    }
                  }
                },
                template: `
                  <div class="sc-v2-rich-editor" v-bind="$attrs">
                    <div :id="mountId" class="sc-v2-rich-editor__mount"></div>
                  </div>
                `
              });
            }
          };
          const isStructuredEventHandler = (handler) => {
            return isObject(handler) && typeof handler.type === 'string' && handler.type !== '';
          };
          const callHook = (hook, context) => {
            if (typeof hook !== 'function') {
              return Promise.resolve(undefined);
            }

            try {
              return Promise.resolve(hook(context));
            } catch (error) {
              return Promise.reject(error);
            }
          };
          const executeStructuredEvent = (handler, context) => {
            const vm = context?.vm;
            const type = String(handler?.type || '');

            if (type === 'openUrl') {
              const url = buildUrlWithQuery(handler.url || '', handler.query || {}, context);
              if (!url) {
                throw new Error('Structured event [openUrl] requires a resolvable url.');
              }

              const target = typeof handler.target === 'string' && handler.target !== ''
                ? handler.target
                : '_self';
              const features = resolveContextValue(handler.features ?? null, context);

              if (target === '_self') {
                window.location.href = url;
                return url;
              }

              window.open(
                url,
                target,
                typeof features === 'string' && features !== '' ? features : undefined
              );

              return url;
            }

            if (type === 'openDialog') {
              const dialogKey = resolveContextValue(handler.dialogKey ?? null, context);
              if (typeof dialogKey !== 'string' || dialogKey === '') {
                throw new Error('Structured event [openDialog] requires dialogKey.');
              }
              if (typeof vm?.openDialog !== 'function') {
                throw new Error('Structured event [openDialog] requires dialog runtime support.');
              }

              const row = resolveContextValue(handler.row ?? context?.row ?? null, context);
              const tableKey = resolveContextValue(handler.tableKey ?? context?.tableKey ?? null, context);

              return vm.openDialog(
                dialogKey,
                row ?? null,
                typeof tableKey === 'string' && tableKey !== '' ? tableKey : null
              );
            }

            if (type === 'closeDialog') {
              const dialogKey = resolveContextValue(handler.dialogKey ?? context?.dialogKey ?? null, context);
              if (typeof dialogKey !== 'string' || dialogKey === '') {
                throw new Error('Structured event [closeDialog] requires dialogKey.');
              }

              if (typeof context?.closeDialog === 'function') {
                return context.closeDialog(dialogKey);
              }
              if (typeof vm?.closeDialog === 'function') {
                return vm.closeDialog(dialogKey);
              }

              throw new Error('Structured event [closeDialog] requires dialog runtime support.');
            }

            if (type === 'reloadTable') {
              const tableKey = resolveContextValue(handler.tableKey ?? context?.tableKey ?? null, context);
              if (typeof tableKey !== 'string' || tableKey === '') {
                throw new Error('Structured event [reloadTable] requires explicit tableKey or runtime table context.');
              }

              if (typeof context?.reloadTable === 'function') {
                return context.reloadTable(tableKey);
              }
              if (typeof vm?.reloadTable === 'function') {
                return vm.reloadTable(tableKey);
              }
              if (typeof vm?.loadTableData === 'function') {
                return vm.loadTableData(tableKey);
              }

              throw new Error('Structured event [reloadTable] requires table runtime support.');
            }

            if (type === 'reloadList') {
              const listKey = resolveContextValue(handler.listKey ?? context?.listKey ?? null, context);
              if (typeof listKey !== 'string' || listKey === '') {
                throw new Error('Structured event [reloadList] requires explicit listKey or runtime list context.');
              }

              if (typeof context?.reloadList === 'function') {
                return context.reloadList(listKey);
              }
              if (typeof vm?.reloadList === 'function') {
                return vm.reloadList(listKey);
              }

              throw new Error('Structured event [reloadList] requires list runtime support.');
            }

            if (type === 'reloadPage') {
              if (typeof context?.reloadPage === 'function') {
                return context.reloadPage();
              }

              window.location.reload();

              return true;
            }

            if (type === 'closeHostDialog') {
              if (typeof context?.closeHostDialog === 'function') {
                return context.closeHostDialog();
              }

              return false;
            }

            if (type === 'reloadHostTable') {
              const tableKey = resolveContextValue(handler.tableKey ?? context?.tableKey ?? null, context);
              if (typeof context?.reloadHostTable === 'function') {
                return context.reloadHostTable(
                  typeof tableKey === 'string' && tableKey !== '' ? tableKey : null
                );
              }

              return false;
            }

            if (type === 'returnTo') {
              const url = resolveContextValue(handler.url ?? '', context);
              const shouldReloadHostTable = handler.reloadHostTable === true;
              const tableKey = resolveContextValue(handler.tableKey ?? context?.tableKey ?? null, context);

              if (shouldReloadHostTable && typeof context?.reloadHostTable === 'function') {
                context.reloadHostTable(typeof tableKey === 'string' && tableKey !== '' ? tableKey : null);
              }

              const closed = typeof context?.closeHostDialog === 'function'
                ? context.closeHostDialog()
                : false;
              if (closed) {
                return true;
              }

              if (typeof url === 'string' && url !== '') {
                window.location.href = url;
                return url;
              }

              return null;
            }

            if (type === 'setFormModel') {
              const values = resolveContextValue(handler.values ?? {}, context);
              const formScope = resolveContextValue(
                handler.formScope ?? context?.formScope ?? context?.scope ?? null,
                context
              );

              if (typeof context?.setFormModel === 'function') {
                if (typeof formScope === 'string' && formScope !== '') {
                  return context.setFormModel(formScope, values);
                }

                return context.setFormModel(values);
              }

              if (typeof vm?.setFormModel === 'function') {
                if (typeof formScope === 'string' && formScope !== '') {
                  return vm.setFormModel(formScope, values);
                }

                throw new Error('Structured event [setFormModel] requires explicit formScope or form-aware runtime context.');
              }

              throw new Error('Structured event [setFormModel] requires public setFormModel() support.');
            }

            if (type === 'initializeFormModel') {
              const values = resolveContextValue(handler.values ?? {}, context);
              const formScope = resolveContextValue(
                handler.formScope ?? context?.formScope ?? context?.scope ?? null,
                context
              );

              if (typeof context?.initializeFormModel === 'function') {
                if (typeof formScope === 'string' && formScope !== '') {
                  return context.initializeFormModel(formScope, values);
                }

                return context.initializeFormModel(values);
              }

              if (typeof vm?.initializeFormModel === 'function') {
                if (typeof formScope === 'string' && formScope !== '') {
                  return vm.initializeFormModel(formScope, values);
                }

                throw new Error('Structured event [initializeFormModel] requires explicit formScope or form-aware runtime context.');
              }

              throw new Error('Structured event [initializeFormModel] requires public initializeFormModel() support.');
            }

            if (type === 'resetForm') {
              const formScope = resolveContextValue(
                handler.formScope ?? context?.formScope ?? context?.scope ?? null,
                context
              );

              if (typeof context?.resetForm === 'function') {
                if (typeof formScope === 'string' && formScope !== '') {
                  return context.resetForm(formScope);
                }

                return context.resetForm();
              }

              if (typeof vm?.resetForm === 'function') {
                if (typeof formScope === 'string' && formScope !== '') {
                  return vm.resetForm(formScope);
                }

                throw new Error('Structured event [resetForm] requires explicit formScope or form-aware runtime context.');
              }

              if (typeof vm?.resetSimpleForm === 'function') {
                if (typeof formScope === 'string' && formScope !== '') {
                  return vm.resetSimpleForm(formScope);
                }

                throw new Error('Structured event [resetForm] requires explicit formScope or form-aware runtime context.');
              }

              throw new Error('Structured event [resetForm] requires public resetForm() support.');
            }

            if (type === 'message') {
              const message = resolveContextValue(handler.message ?? '', context);
              if (message === null || message === undefined || message === '') {
                return null;
              }

              const messageType = typeof handler.messageType === 'string' && handler.messageType !== ''
                ? handler.messageType
                : 'info';
              const emitter = ElementPlus.ElMessage?.[messageType] || ElementPlus.ElMessage;

              return emitter(String(message));
            }

            if (type === 'request') {
              const request = {
                method: resolveContextValue(handler.method || 'post', context) || 'post',
                url: resolveContextValue(handler.url || '', context),
                query: resolveContextValue(handler.query || {}, context),
              };
              if (typeof request.url !== 'string' || request.url === '') {
                throw new Error('Structured event [request] requires a resolvable url.');
              }

              let loadingInstance = null;
              context.request = request;

              if (handler.loadingText) {
                const loadingText = resolveContextValue(handler.loadingText, context);
                if (loadingText) {
                  loadingInstance = ElementPlus.ElLoading.service({
                    lock: true,
                    text: String(loadingText),
                    background: 'rgba(255,255,255,0.35)',
                  });
                }
              }

              return makeRequest(request)
                .then((response) => {
                  const payload = ensureSuccess(
                    extractPayload(response),
                    handler.errorMessage || '失败'
                  );

                  context.response = response;
                  context.payload = payload;

                  const successMessage = handler.successMessage ?? resolveMessage(payload, '成功');
                  if (successMessage) {
                    ElementPlus.ElMessage.success(String(successMessage));
                  }

                  return payload;
                })
                .catch((error) => {
                  context.error = error;
                  const message = error?.message || resolveMessage(
                    error?.response?.data,
                    handler.errorMessage || '失败'
                  );

                  if (message) {
                    ElementPlus.ElMessage.error(String(message));
                  }

                  throw error;
                })
                .finally(() => {
                  if (loadingInstance && typeof loadingInstance.close === 'function') {
                    loadingInstance.close();
                  }
                });
            }

            return callHook(handler, context);
          };
          const callHooks = (hooks, context) => {
            const queue = Array.isArray(hooks)
              ? hooks.filter((hook) => typeof hook === 'function' || isStructuredEventHandler(hook))
              : [];

            return queue.reduce(
              (promise, hook) => promise.then((results) => {
                return Promise.resolve(executeStructuredEvent(hook, context)).then((result) => {
                  results.push(result);
                  return results;
                });
              }),
              Promise.resolve([])
            );
          };
          const emitConfiguredEvent = (config, eventName, context) => {
            return callHooks(config?.events?.[eventName] || [], context);
          };
          const isEventCanceled = (results) => {
            return Array.isArray(results) && results.some((result) => result === false);
          };
          const pickRows = (payload, depth = 0) => {
            if (depth > 4) return [];
            if (isRowArray(payload)) return payload;
            if (!isObject(payload)) return [];

            const directKeys = ['data', 'rows', 'list', 'items', 'records'];
            for (const key of directKeys) {
              if (isRowArray(payload[key])) return payload[key];
            }

            const nestedKeys = ['data', 'result', 'payload'];
            for (const key of nestedKeys) {
              if (payload[key] !== undefined) {
                const found = pickRows(payload[key], depth + 1);
                if (found.length > 0 || isRowArray(payload[key])) return found;
              }
            }

            return [];
          };
          const makeRequest = (request) => {
            const method = (request?.method || 'GET').toLowerCase();
            if (method === 'get') {
              return axios.get(request.url, { params: request.query || {} });
            }
            return axios({ method, url: request.url, data: request.query || {} });
          };
          const normalizeOption = (item, fieldCfg, index) => {
            if (!isObject(item)) {
              return {
                value: item,
                label: item == null ? '' : String(item),
                disabled: false
              };
            }

            const valueField = typeof fieldCfg?.valueField === 'string' && fieldCfg.valueField !== ''
              ? fieldCfg.valueField
              : 'value';
            const labelField = typeof fieldCfg?.labelField === 'string' && fieldCfg.labelField !== ''
              ? fieldCfg.labelField
              : 'label';
            const childrenField = typeof fieldCfg?.childrenField === 'string' && fieldCfg.childrenField !== ''
              ? fieldCfg.childrenField
              : 'children';
            const disabledField = typeof fieldCfg?.disabledField === 'string' && fieldCfg.disabledField !== ''
              ? fieldCfg.disabledField
              : 'disabled';
            const value = item.value !== undefined
              ? item.value
              : getByPath(item, valueField);
            const label = item.label !== undefined
              ? item.label
              : getByPath(item, labelField);
            const disabledValue = getByPath(item, disabledField);
            const normalized = Object.assign({}, item, {
              value: value ?? index,
              label: label ?? String(value ?? ''),
              disabled: item.disabled === true || disabledValue === true || disabledValue === 1 || disabledValue === '1'
            });
            const children = getByPath(item, childrenField);
            if (Array.isArray(children) && children.length > 0) {
              normalized[childrenField] = children.map((child, childIndex) => normalizeOption(child, fieldCfg, childIndex));
            }

            return normalized;
          };
          const resolvePickerDisplayTemplate = (template, item, label, value) => {
            const rawTemplate = typeof template === 'string' && template !== ''
              ? template
              : '@label';
            const replaced = rawTemplate
              .replace(/@item\.([A-Za-z0-9_.$-]+)/g, (_, path) => {
                const resolved = getByPath(item || {}, String(path || '').trim());
                return resolved === null || resolved === undefined ? '' : String(resolved);
              })
              .replace(/@label/g, label === null || label === undefined ? '' : String(label))
              .replace(/@value/g, value === null || value === undefined ? '' : String(value));

            return replaced.trim();
          };
          const normalizePickerItem = (item, fieldCfg, index = 0) => {
            const valueField = fieldCfg?.valueField || 'id';
            const labelField = fieldCfg?.labelField || 'name';
            const row = isObject(item)
              ? clone(item)
              : {
                  [valueField]: item,
                  [labelField]: item === null || item === undefined ? '' : String(item),
                };
            const value = getByPath(row, valueField);
            const label = getByPath(row, labelField) ?? (value === null || value === undefined ? '' : String(value));

            return Object.assign({}, row, {
              __pickerValue: value ?? index,
              __pickerLabel: label === null || label === undefined ? '' : String(label),
              __pickerDisplay: resolvePickerDisplayTemplate(
                fieldCfg?.displayTemplate || '@label',
                row,
                label,
                value
              )
            });
          };
          const normalizePickerItems = (items, fieldCfg = {}) => {
            const rows = Array.isArray(items)
              ? items
              : (isObject(items) ? [items] : []);
            const normalized = [];
            const seen = new Set();

            for (let index = 0; index < rows.length; index += 1) {
              const normalizedItem = normalizePickerItem(rows[index], fieldCfg, index);
              const rawValue = normalizedItem?.__pickerValue;
              const dedupeKey = rawValue !== null && typeof rawValue === 'object'
                ? JSON.stringify(rawValue)
                : String(rawValue);
              if (seen.has(dedupeKey)) {
                continue;
              }

              seen.add(dedupeKey);
              normalized.push(normalizedItem);

              if (fieldCfg?.multiple === false) {
                break;
              }
            }

            return normalized;
          };
          const buildOptionState = (configs, fieldPaths = []) => {
            const state = {};
            (fieldPaths || []).forEach((fieldName) => {
              const fieldCfg = getByPath(configs || {}, fieldName) || {};
              setByPath(
                state,
                fieldName,
                Array.isArray(fieldCfg.initialOptions)
                  ? fieldCfg.initialOptions.map((item, index) => normalizeOption(item, fieldCfg, index))
                  : []
              );
            });
            return state;
          };
          const buildPickerState = (configs, fieldPaths = []) => {
            const state = {};
            (fieldPaths || []).forEach((fieldName) => {
              const fieldCfg = getByPath(configs || {}, fieldName) || {};
              setByPath(
                state,
                fieldName,
                normalizePickerItems(fieldCfg.initialItems || [], fieldCfg)
              );
            });

            return state;
          };
          const buildFlagState = (fieldPaths = [], initialValue = false) => {
            const state = {};
            (fieldPaths || []).forEach((fieldName) => {
              setByPath(state, fieldName, initialValue);
            });
            return state;
          };
          const normalizeDependencies = (fieldCfg) => {
            return Array.from(new Set(
              Array.isArray(fieldCfg?.dependencies)
                ? fieldCfg.dependencies.filter((item) => typeof item === 'string' && item !== '')
                : []
            ));
          };
          const resolveDynamicParams = (params, model) => {
            const query = {};

            Object.keys(params || {}).forEach((key) => {
              const value = params[key];
              if (typeof value === 'string' && value.startsWith('@')) {
                const resolved = getByPath(model, value.slice(1));
                if (!isBlank(resolved)) {
                  query[key] = resolved;
                }
                return;
              }

              if (value !== undefined) {
                query[key] = value;
              }
            });

            return query;
          };
          const dialogScopePrefix = 'dialog:';
          const dialogHostBridgeQueryKey = '__scV2DialogHost';
          const toDialogScope = (dialogKey) => {
            const normalized = String(dialogKey || '').trim();
            return normalized === '' ? dialogScopePrefix : (dialogScopePrefix + normalized);
          };
          const hasDialogHostBridge = () => {
            if (typeof window === 'undefined' || !window.location || typeof URLSearchParams !== 'function') {
              return false;
            }

            try {
              const params = new URLSearchParams(window.location.search || '');
              if (!params.has(dialogHostBridgeQueryKey)) {
                return false;
              }

              const value = String(params.get(dialogHostBridgeQueryKey) || '').trim().toLowerCase();
              return value === '' || !['0', 'false', 'no', 'off'].includes(value);
            } catch (error) {
              return false;
            }
          };
          const postDialogHostMessage = (payload = {}) => {
            if (!isObject(payload)) {
              return false;
            }
            if (!hasDialogHostBridge()) {
              return false;
            }

            const parentWindow = typeof window !== 'undefined' ? window.parent : null;
            if (!parentWindow || parentWindow === window || typeof parentWindow.postMessage !== 'function') {
              return false;
            }

            parentWindow.postMessage({
              __scV2DialogHost: payload
            }, '*');

            return true;
          };
          const buildHostTabIndex = (value) => {
            const source = String(value || '');
            let hash = 0;
            for (let index = 0; index < source.length; index += 1) {
              hash = ((hash << 5) - hash) + source.charCodeAt(index);
              hash |= 0;
            }

            return `sc-tab-${Math.abs(hash)}`;
          };
          const normalizeHostTabTarget = (target, title = '', index = null) => {
            const config = isObject(target)
              ? target
              : { route: target, title, index };
            const route = typeof config?.route === 'string' ? config.route.trim() : '';
            if (route === '') {
              return null;
            }

            const resolvedTitle = typeof config?.title === 'string' && config.title.trim() !== ''
              ? config.title.trim()
              : '新页面';
            const resolvedIndex = typeof config?.index === 'string' && config.index.trim() !== ''
              ? config.index.trim()
              : buildHostTabIndex(route);
            const normalized = {
              index: resolvedIndex,
              title: resolvedTitle,
              route,
            };

            if (typeof config?.icon === 'string' && config.icon.trim() !== '') {
              normalized.icon = config.icon.trim();
            }

            return normalized;
          };
          const buildLegacyMainTabsBridge = (mainTabs) => {
            if (!mainTabs || typeof mainTabs.add !== 'function') {
              return null;
            }

            return {
              open(target) {
                const normalized = normalizeHostTabTarget(target);
                if (!normalized) {
                  return false;
                }

                mainTabs.add(normalized);
                return true;
              }
            };
          };
          const resolveHostTabBridge = () => {
            const candidates = [];
            if (typeof globalThis !== 'undefined') {
              candidates.push(globalThis);
            }
            if (typeof window !== 'undefined') {
              candidates.push(window);
              try {
                if (window.parent) {
                  candidates.push(window.parent);
                }
              } catch (error) {}
              try {
                if (window.top) {
                  candidates.push(window.top);
                }
              } catch (error) {}
            }

            const visited = new Set();
            for (const candidate of candidates) {
              if (!candidate || (typeof candidate !== 'object' && typeof candidate !== 'function') || visited.has(candidate)) {
                continue;
              }

              visited.add(candidate);
              try {
                const bridge = candidate.__SC_V2_HOST_TAB_BRIDGE__;
                if (typeof bridge === 'function') {
                  return {
                    open(target) {
                      const normalized = normalizeHostTabTarget(target);
                      if (!normalized) {
                        return false;
                      }

                      return bridge(normalized);
                    }
                  };
                }
                if (bridge && typeof bridge.open === 'function') {
                  return bridge;
                }
              } catch (error) {}
              try {
                const legacyBridge = buildLegacyMainTabsBridge(candidate.VueApp?.$refs?.['main-tabs']);
                if (legacyBridge) {
                  return legacyBridge;
                }
              } catch (error) {}
            }

            return null;
          };
          const openHostTab = (target, title = '', index = null) => {
            const normalized = normalizeHostTabTarget(target, title, index);
            if (!normalized) {
              return false;
            }

            const bridge = resolveHostTabBridge();
            if (bridge && typeof bridge.open === 'function') {
              try {
                const result = bridge.open(normalized);
                if (result !== false) {
                  return normalized.route;
                }
              } catch (error) {}
            }

            const targetWindow = typeof window !== 'undefined'
              ? window
              : globalThis;
            if (targetWindow && typeof targetWindow.open === 'function') {
              targetWindow.open(normalized.route, '_blank');
            }

            return normalized.route;
          };
          const isDialogScope = (scope) => {
            return typeof scope === 'string' && scope.startsWith(dialogScopePrefix);
          };
          const resolveDialogKeyFromScope = (scope) => {
            if (!isDialogScope(scope)) {
              return null;
            }

            return scope.slice(dialogScopePrefix.length) || null;
          };
          const resolveContextToken = (token, context) => {
            const path = String(token || '').replace(/^@/, '');
            if (path === '') {
              return undefined;
            }

            return getByPath(context, path);
          };
          const tokenPatternSource = '@[A-Za-z0-9_.$:-]+';
          const fullTokenPattern = new RegExp('^' + tokenPatternSource + '$');
          const replaceTokens = (value, resolver) => {
            return String(value).replace(new RegExp(tokenPatternSource, 'g'), resolver);
          };
          const resolveContextValue = (value, context) => {
            if (typeof value === 'function') {
              return resolveContextValue(value(context), context);
            }

            if (Array.isArray(value)) {
              return value.map((item) => resolveContextValue(item, context));
            }

            if (isObject(value)) {
              const output = {};
              Object.keys(value).forEach((key) => {
                output[key] = resolveContextValue(value[key], context);
              });
              return output;
            }

            if (typeof value === 'string') {
              if (fullTokenPattern.test(value)) {
                return resolveContextToken(value, context);
              }

              if (!value.includes('@')) {
                return value;
              }

              return replaceTokens(value, (token) => {
                const resolved = resolveContextToken(token, context);
                return resolved === null || resolved === undefined ? '' : String(resolved);
              });
            }

            return value;
          };
          const resolveTitleTemplate = (template, context) => {
            if (template === null || template === undefined) {
              return '';
            }

            const replaced = String(template).replace(/\{([^{}]+)\}/g, (_, path) => {
              const resolved = getByPath(context?.row || {}, String(path).trim());
              return resolved === null || resolved === undefined ? '' : String(resolved);
            });

            const value = resolveContextValue(replaced, context);
            return value === null || value === undefined ? '' : String(value);
          };
          const buildUrlWithQuery = (url, query, context) => {
            const resolvedUrl = resolveContextValue(url, context);
            if (typeof resolvedUrl !== 'string' || resolvedUrl === '') {
              return '';
            }

            const parsedUrl = new URL(resolvedUrl, window.location.href);
            const resolvedQuery = resolveContextValue(query || {}, context);

            Object.keys(resolvedQuery || {}).forEach((key) => {
              const value = resolvedQuery[key];
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
          const hasReadyDependencies = (fieldCfg, model) => {
            const dependencies = normalizeDependencies(fieldCfg);
            if (dependencies.length === 0) {
              return true;
            }

            return dependencies.every((path) => !isBlank(getByPath(model, path)));
          };
          const isSameValue = (left, right) => {
            if (left === right) return true;
            if (left === null || left === undefined || right === null || right === undefined) {
              return false;
            }

            return String(left) === String(right);
          };
          const findColumnOption = (value, options = []) => {
            if (!Array.isArray(options)) {
              return null;
            }

            return options.find((item) => item && isSameValue(item.value, value)) || null;
          };
          const resolveColumnDisplayValue = (value, separator = ', ') => {
            if (!Array.isArray(value)) {
              return value;
            }

            return value
              .map((item) => item === null || item === undefined ? '' : String(item))
              .filter((item) => item !== '')
              .join(separator);
          };
          const resolveColumnMappingLabel = (value, options = [], separator = ', ') => {
            if (Array.isArray(value)) {
              return value
                .map((item) => findColumnOption(item, options)?.label ?? '')
                .filter((item) => item !== '')
                .join(separator);
            }

            return findColumnOption(value, options)?.label ?? '';
          };
          const resolveColumnTagMeta = (value, options = [], defaultType = 'info') => {
            const option = findColumnOption(value, options);
            if (!option) {
              return null;
            }

            return Object.assign({}, option, {
              label: option.label ?? '',
              type: option.type ?? defaultType
            });
          };
          const isColumnTruthy = (value) => {
            if ([true, 1, '1', 'true', 'yes', 'on'].includes(value)) {
              return true;
            }

            if (value === null || value === undefined) {
              return false;
            }

            return ['true', 'yes', 'on', '1'].includes(String(value).toLowerCase());
          };
          const isColumnFalsy = (value) => {
            if ([false, 0, '0', 'false', 'no', 'off'].includes(value)) {
              return true;
            }

            if (value === null || value === undefined) {
              return false;
            }

            return ['false', 'no', 'off', '0'].includes(String(value).toLowerCase());
          };
          const formatColumnDatetime = (value, format = 'YYYY-MM-DD HH:mm:ss') => {
            if (value === '' || value === null || value === undefined) {
              return '';
            }

            const raw = String(value).trim();
            const isNumeric = typeof value === 'number' || /^-?\d+(\.\d+)?$/.test(raw);
            const normalizedNumber = isNumeric ? Number(value) : NaN;
            const timestamp = isNumeric
              ? (String(Math.trunc(Math.abs(normalizedNumber))).length <= 10 ? normalizedNumber * 1000 : normalizedNumber)
              : NaN;
            const date = isNumeric
              ? new Date(timestamp)
              : new Date(raw.replace('T', ' ').replace(/-/g, '/'));

            if (Number.isNaN(date.getTime())) {
              return raw;
            }

            const pad = (num) => String(num).padStart(2, '0');
            return String(format)
              .replace(/YYYY/g, String(date.getFullYear()))
              .replace(/MM/g, pad(date.getMonth() + 1))
              .replace(/DD/g, pad(date.getDate()))
              .replace(/HH/g, pad(date.getHours()))
              .replace(/mm/g, pad(date.getMinutes()))
              .replace(/ss/g, pad(date.getSeconds()));
          };
          const resolveLinkageToken = (token, context) => {
            const path = String(token || '').replace(/^@/, '');
            if (path === '') return '';
            if (path === 'value') return context.value;
            if (path === 'label') return context.option?.label ?? '';
            if (path.startsWith('model.')) return getByPath(context.model, path.slice(6));
            if (path.startsWith('option.')) return getByPath(context.option, path.slice(7));

            return getByPath(context.option, path);
          };
          const resolveLinkageTemplate = (template, context) => {
            if (typeof template === 'function') {
              return template(context);
            }
            if (template === null || template === undefined) {
              return '';
            }
            if (typeof template !== 'string') {
              return template;
            }
            if (fullTokenPattern.test(template)) {
              return resolveLinkageToken(template, context);
            }

            return replaceTokens(template, (token) => {
              const value = resolveLinkageToken(token, context);
              return value === null || value === undefined ? '' : String(value);
            });
          };
          const extractFileName = (url, fallback = 'file') => {
            if (typeof url !== 'string' || url === '') {
              return fallback;
            }

            const clean = url.split('?')[0].split('#')[0];
            const parts = clean.split('/').filter(Boolean);

            return parts[parts.length - 1] || fallback;
          };
          const resolveUploadValue = (payload, fieldCfg, depth = 0) => {
            if (depth > 4 || payload === null || payload === undefined) {
              return null;
            }
            if (typeof payload === 'string') {
              return payload;
            }
            if (!isObject(payload)) {
              return null;
            }

            if (fieldCfg?.responsePath) {
              const pathValue = getByPath(payload, fieldCfg.responsePath);
              if (!isBlank(pathValue)) {
                return pathValue;
              }
            }

            const directKeys = ['url', 'path', 'value', 'src'];
            for (const key of directKeys) {
              if (typeof payload[key] === 'string' && payload[key] !== '') {
                return payload[key];
              }
            }

            const nestedKeys = ['data', 'result', 'payload'];
            for (const key of nestedKeys) {
              if (payload[key] !== undefined) {
                const resolved = resolveUploadValue(payload[key], fieldCfg, depth + 1);
                if (!isBlank(resolved)) {
                  return resolved;
                }
              }
            }

            return null;
          };
          const normalizeUploadFile = (item, fieldCfg, index) => {
            if (typeof item === 'string') {
              return {
                uid: 'init-' + index,
                name: extractFileName(item, 'file-' + (index + 1)),
                url: item,
                responseValue: item,
                status: 'success'
              };
            }
            if (!isObject(item)) {
              return null;
            }

            const responseValue = item.responseValue
              ?? resolveUploadValue(item.response, fieldCfg)
              ?? resolveUploadValue(item, fieldCfg);
            const url = item.url || item.value || item.src || responseValue;
            const status = item.status || 'success';
            if (isBlank(url)) {
              if (status === 'ready' || status === 'uploading') {
                return Object.assign({}, item, {
                  uid: item.uid || ('file-' + index),
                  name: item.name || ('file-' + (index + 1)),
                  url: '',
                  responseValue: null,
                  status
                });
              }

              return null;
            }

            return Object.assign({}, item, {
              uid: item.uid || ('file-' + index),
              name: item.name || extractFileName(String(url), 'file-' + (index + 1)),
              url,
              responseValue: responseValue || url,
              status
            });
          };
          const normalizeUploadFiles = (value, fieldCfg) => {
            const source = Array.isArray(value)
              ? value
              : (isBlank(value) ? [] : [value]);
            const files = source
              .map((item, index) => normalizeUploadFile(item, fieldCfg, index))
              .filter(Boolean);

            return fieldCfg?.multiple ? files : files.slice(0, 1);
          };
          const serializeUploadFile = (file, index = 0) => {
            if (!isObject(file)) {
              return null;
            }

            const url = file.responseValue || file.url || resolveUploadValue(file.response || file, {});
            if (isBlank(url)) {
              return null;
            }

            return {
              uid: file.uid || ('file-' + index),
              url,
              name: file.name || extractFileName(String(url), 'file-' + (index + 1)),
              status: file.status || 'success'
            };
          };
          const buildUploadFileState = (configs, model, fieldPaths = []) => {
            const state = {};
            (fieldPaths || []).forEach((fieldName) => {
              setByPath(
                state,
                fieldName,
                normalizeUploadFiles(getByPath(model, fieldName), getByPath(configs, fieldName) || {})
              );
            });
            return state;
          };
          const buildArrayGroupConfigMap = (arrayGroups = []) => {
            return (Array.isArray(arrayGroups) ? arrayGroups : []).reduce((map, groupCfg) => {
              const path = typeof groupCfg?.path === 'string' ? groupCfg.path : '';
              if (path !== '') {
                map[path] = groupCfg;
              }

              return map;
            }, {});
          };
          let arrayGroupRowKeySeed = 0;
          const nextArrayGroupRowKey = () => {
            arrayGroupRowKeySeed += 1;
            return 'sc-v2-array-row-' + arrayGroupRowKeySeed;
          };
          const normalizeArrayGroupRow = (row, groupCfg = {}) => {
            const defaultRow = isObject(groupCfg?.defaultRow) ? clone(groupCfg.defaultRow) : {};
            if (isObject(row)) {
              const normalizedRow = Object.assign(defaultRow, clone(row));
              if (
                normalizedRow.__sc_v2_row_key === undefined
                || normalizedRow.__sc_v2_row_key === null
                || normalizedRow.__sc_v2_row_key === ''
              ) {
                normalizedRow.__sc_v2_row_key = nextArrayGroupRowKey();
              }

              return normalizedRow;
            }
            if (Array.isArray(row)) {
              return clone(row);
            }

            defaultRow.__sc_v2_row_key = nextArrayGroupRowKey();

            return defaultRow;
          };
          const normalizeArrayGroupRows = (rows, groupCfg = {}) => {
            const normalizedRows = (Array.isArray(rows) ? rows : [])
              .map((row) => normalizeArrayGroupRow(row, groupCfg));
            const minRows = Math.max(0, Number(groupCfg?.minRows) || 0);

            while (normalizedRows.length < minRows) {
              normalizedRows.push(normalizeArrayGroupRow({}, groupCfg));
            }

            return normalizedRows;
          };
          const isArrayGroupRowReady = (row, groupCfg = {}) => {
            if (!isObject(row)) {
              return false;
            }

            if (
              row.__sc_v2_row_key === undefined
              || row.__sc_v2_row_key === null
              || row.__sc_v2_row_key === ''
            ) {
              return false;
            }

            const defaultRow = isObject(groupCfg?.defaultRow) ? groupCfg.defaultRow : {};
            return Object.keys(defaultRow).every((key) => row[key] !== undefined);
          };
          const isFormArrayGroupStateReady = (rows, groupCfg = {}) => {
            if (!Array.isArray(rows)) {
              return false;
            }

            const minRows = Math.max(0, Number(groupCfg?.minRows) || 0);
            if (rows.length < minRows) {
              return false;
            }

            return rows.every((row) => isArrayGroupRowReady(row, groupCfg));
          };
          const ensureFormArrayGroupState = (model, groupCfg = {}) => {
            const path = typeof groupCfg?.path === 'string' ? groupCfg.path : '';
            if (path === '') {
              return [];
            }

            const currentRows = getByPath(model, path);
            const normalizedRows = isFormArrayGroupStateReady(currentRows, groupCfg)
              ? currentRows
              : normalizeArrayGroupRows(currentRows, groupCfg);

            if (normalizedRows !== currentRows) {
              setByPath(model, path, normalizedRows);
            }

            const childGroups = Array.isArray(groupCfg?.rowArrayGroups) ? groupCfg.rowArrayGroups : [];
            if (childGroups.length > 0) {
              normalizedRows.forEach((_, rowIndex) => {
                childGroups.forEach((childGroupCfg) => {
                  const childPath = [path, rowIndex, childGroupCfg?.path]
                    .filter((segment) => segment !== null && segment !== undefined && segment !== '')
                    .join('.');
                  ensureFormArrayGroupState(model, Object.assign({}, childGroupCfg, {
                    path: childPath
                  }));
                });
              });
            }

            return normalizedRows;
          };
          const buildTableSettingsState = (tableConfig = {}) => {
            const settingsConfig = isObject(tableConfig?.settings) ? tableConfig.settings : {};
            const columns = Array.isArray(settingsConfig?.columns)
              ? settingsConfig.columns
                .filter((item) => isObject(item) && typeof item.key === 'string' && item.key !== '')
                .map((item) => ({
                  key: String(item.key),
                  label: typeof item.label === 'string' && item.label !== '' ? item.label : String(item.key),
                  show: item.show !== false,
                  width: item.width ?? null,
                  fixed: typeof item.fixed === 'string' && item.fixed !== '' ? item.fixed : null,
                  align: typeof item.align === 'string' && item.align !== '' ? item.align : null,
                  export: item.export !== false,
                  exportSort: Number.isFinite(Number(item.exportSort)) ? Number(item.exportSort) : null,
                }))
              : [];

            return {
              enabled: settingsConfig.enabled === true,
              stripe: settingsConfig.stripe !== false,
              border: settingsConfig.border !== false,
              columns
            };
          };
          const buildTableState = (tableConfig = {}) => {
            const initialRows = Array.isArray(tableConfig?.initialRows) ? clone(tableConfig.initialRows) : [];
            const settingsDefault = buildTableSettingsState(tableConfig);
            const quickFilters = {};
            (Array.isArray(tableConfig?.statusToggles?.items) ? tableConfig.statusToggles.items : []).forEach((item) => {
              if (typeof item?.name === 'string' && item.name !== '') {
                quickFilters[item.name] = null;
              }
            });

            return {
              rows: clone(initialRows),
              allRows: clone(initialRows),
              selection: [],
              total: initialRows.length,
              page: 1,
              pageSize: tableConfig?.pagination?.pageSize || 20,
              sort: {
                field: '',
                order: null
              },
              quickFilters,
              loading: false,
              exporting: false,
              maxHeight: Number(tableConfig?.maxHeight) > 0 ? Number(tableConfig.maxHeight) : 0,
              settings: clone(settingsDefault),
              settingsDefault: clone(settingsDefault),
              settingsDraft: clone(settingsDefault),
              settingsTab: 'display',
              settingsVisible: false,
              settingsLoaded: false
            };
          };
          const buildTableStates = (tables = {}) => {
            const state = {};
            Object.keys(tables || {}).forEach((tableKey) => {
              state[tableKey] = buildTableState(tables[tableKey] || {});
            });
            return state;
          };
          const getConfigState = (vm, config, varKey, pathKey, initialize = false, fallback = {}) => {
            const varName = config?.[varKey];
            if (typeof varName === 'string' && varName !== '') {
              if (initialize && (vm[varName] === undefined || vm[varName] === null)) {
                vm[varName] = {};
              }
              return vm[varName] ?? fallback;
            }

            const path = Array.isArray(config?.[pathKey]) ? config[pathKey].filter((segment) => segment !== '') : [];
            if (path.length === 0) {
              return fallback;
            }

            let current = vm;
            for (let index = 0; index < path.length; index += 1) {
              const segment = path[index];
              if (current == null || (typeof current !== 'object' && !Array.isArray(current))) {
                return fallback;
              }

              if (initialize && (current[segment] === undefined || current[segment] === null)) {
                current[segment] = {};
              }

              if (current[segment] === undefined || current[segment] === null) {
                return fallback;
              }

              current = current[segment];
            }

            return current ?? fallback;
          };
          const setConfigState = (vm, config, varKey, pathKey, value) => {
            const varName = config?.[varKey];
            if (typeof varName === 'string' && varName !== '') {
              vm[varName] = value;
              return value;
            }

            const path = Array.isArray(config?.[pathKey]) ? config[pathKey].filter((segment) => segment !== '') : [];
            if (path.length === 0) {
              return value;
            }

            let current = vm;
            for (let index = 0; index < path.length - 1; index += 1) {
              const segment = path[index];
              if (current[segment] === undefined || current[segment] === null || (typeof current[segment] !== 'object' && !Array.isArray(current[segment]))) {
                current[segment] = {};
              }
              current = current[segment];
            }

            current[path[path.length - 1]] = value;
            return value;
          };
          const buildFormsContext = (vm, forms = {}) => {
            const context = {};

            Object.keys(forms || {}).forEach((scope) => {
              context[scope] = getConfigState(vm, forms[scope] || {}, 'modelVar', 'modelPath');
            });

            return context;
          };
          const initializeConfiguredForms = (forms = {}, handlers = {}) => {
            Object.keys(forms || {}).forEach((scope) => {
              const formCfg = forms[scope] || {};

              if (Array.isArray(formCfg.arrayGroups) && formCfg.arrayGroups.length > 0) {
                handlers.initializeArrayGroups?.(scope, formCfg);
              }
              if (formCfg.registerDependenciesOnMount) {
                handlers.registerDependencies?.(scope, formCfg);
              }
              if (formCfg.initializeOptionsOnMount) {
                handlers.initializeOptions?.(scope, formCfg);
              }
              if (formCfg.initializeUploadsOnMount) {
                handlers.initializeUploads?.(scope, formCfg);
              }
            });
          };
          const buildDialogState = (dialogs, factory) => {
            const state = {};
            Object.keys(dialogs || {}).forEach((dialogKey) => {
              state[dialogKey] = factory(dialogs[dialogKey] || {}, dialogKey);
            });
            return state;
          };
          const buildDialogTitleState = (dialogs) => {
            const state = {};
            Object.keys(dialogs || {}).forEach((dialogKey) => {
              state[dialogKey] = dialogs[dialogKey]?.title || '';
            });
            return state;
          };
          const buildManagedDialogRuntimeState = (dialogs = {}, forms = {}) => {
            const getDialogFormConfig = (dialogKey) => forms?.[toDialogScope(dialogKey)] || {};
            const getDialogFormInitialData = (dialogKey) => {
              const formConfig = getDialogFormConfig(dialogKey);
              return clone(formConfig.initialData || formConfig.defaults || {});
            };

            return {
            dialogForms: buildDialogState(dialogs, (_, dialogKey) => getDialogFormInitialData(dialogKey)),
            dialogInitials: buildDialogState(dialogs, (_, dialogKey) => getDialogFormInitialData(dialogKey)),
            dialogRules: buildDialogState(dialogs, (_, dialogKey) => getDialogFormConfig(dialogKey).rules || {}),
            dialogOptions: buildDialogState(dialogs, (_, dialogKey) => buildOptionState(
              getDialogFormConfig(dialogKey).remoteOptions || {},
              getDialogFormConfig(dialogKey).remoteOptionPaths || []
            )),
            dialogOptionLoading: buildDialogState(dialogs, (_, dialogKey) => buildFlagState(
              getDialogFormConfig(dialogKey).remoteOptionPaths || []
            )),
            dialogOptionLoaded: buildDialogState(dialogs, (_, dialogKey) => buildFlagState(
              getDialogFormConfig(dialogKey).remoteOptionPaths || []
            )),
            dialogUploadFiles: buildDialogState(dialogs, (_, dialogKey) => buildUploadFileState(
              getDialogFormConfig(dialogKey).uploads || {},
              getDialogFormInitialData(dialogKey),
              getDialogFormConfig(dialogKey).uploadPaths || []
            )),
            dialogPickerItems: buildDialogState(dialogs, (_, dialogKey) => buildPickerState(
              getDialogFormConfig(dialogKey).pickers || {},
              getDialogFormConfig(dialogKey).pickerPaths || []
            )),
            dialogPickerInitials: buildDialogState(dialogs, (_, dialogKey) => buildPickerState(
              getDialogFormConfig(dialogKey).pickers || {},
              getDialogFormConfig(dialogKey).pickerPaths || []
            )),
            dialogVisible: buildDialogState(dialogs, () => false),
            dialogMode: buildDialogState(dialogs, () => 'create'),
            dialogRows: buildDialogState(dialogs, () => null),
            dialogLoading: buildDialogState(dialogs, () => false),
            dialogSubmitting: buildDialogState(dialogs, () => false),
            dialogTitles: buildDialogTitleState(dialogs),
            dialogIframeUrls: buildDialogState(dialogs, () => ''),
            dialogComponentProps: buildDialogState(dialogs, () => ({})),
            dialogFullscreen: buildDialogState(dialogs, (dialogCfg) => !!dialogCfg.fullscreen),
            dialogTableKeys: buildDialogState(dialogs, () => null),
          };
          };
          const syncUploadModelValue = (model, fieldName, fieldCfg, files) => {
            const normalized = normalizeUploadFiles(files, fieldCfg);
            const storedFiles = normalized
              .map((file, index) => serializeUploadFile(file, index))
              .filter(Boolean);
            const isSingleImage = (fieldCfg?.kind || 'file') === 'image' && !fieldCfg?.multiple;

            setByPath(model, fieldName, isSingleImage ? (storedFiles[0]?.url ?? '') : storedFiles);

            return normalized;
          };

          return {
            dialogScopePrefix,
            isDialogScope,
            resolveDialogKeyFromScope,
            toDialogScope,
            buildDialogState,
            buildDialogTitleState,
            buildArrayGroupConfigMap,
            buildFlagState,
            buildPickerState,
            buildFormsContext,
            buildManagedDialogRuntimeState,
            buildOptionState,
            buildTableState,
            buildTableStates,
            buildUploadFileState,
            buildUrlWithQuery,
            callHook,
            callHooks,
            clone,
            executeStructuredEvent,
            emitConfiguredEvent,
            ensureSuccess,
            extractFileName,
            extractLoadData,
            extractPayload,
            formatColumnDatetime,
            getConfigState,
            getByPath,
            hasReadyDependencies,
            ensureFormArrayGroupState,
            initializeConfiguredForms,
            initializeFormModelBySchema,
            isBlank,
            isColumnDisplayBlank,
            isEventCanceled,
            isColumnFalsy,
            isColumnTruthy,
            isObject,
            isRowArray,
            isSameValue,
            makeRequest,
            normalizeArrayGroupRow,
            normalizeArrayGroupRows,
            normalizeDependencies,
            normalizeOption,
            normalizePickerItem,
            normalizePickerItems,
            registerElementPlusIcons,
            registerScV2Components,
            serializeUploadFile,
            normalizeUploadFile,
            normalizeUploadFiles,
            openHostTab,
            pickRows,
            postDialogHostMessage,
            readPageLocation,
            readPageQuery,
            resolveContextToken,
            resolveContextValue,
            resolveColumnDisplayValue,
            resolveColumnMappingLabel,
            resolveColumnTagMeta,
            resolveDynamicParams,
            resolvePageMode,
            resolveLinkageTemplate,
            resolvePickerDisplayTemplate,
            resolveMessage,
            resolveTitleTemplate,
            resolveUploadValue,
            setConfigState,
            setByPath,
            syncUploadModelValue
          };
        })();
