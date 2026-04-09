        globalThis.__SC_V2_BOOT_LIST__ = (state, cfg) => {
          const {
            buildFormsContext,
            buildManagedDialogRuntimeState,
            buildTableStates,
            clone,
            emitConfiguredEvent,
            extractPayload,
            ensureSuccess,
            getConfigState,
            initializeConfiguredForms,
            isEventCanceled,
            isBlank,
            makeRequest,
            postDialogHostMessage,
            pickRows,
            registerElementPlusIcons,
            registerScV2Components,
            resolveMessage,
          } = globalThis.__SC_V2_RUNTIME_HELPERS__;
          const forms = cfg.forms || {};
          const lists = cfg.lists || {};
          const tables = cfg.tables || {};

          const createColumnDisplayMethods = globalThis.__SC_V2_CREATE_COLUMN_DISPLAY_METHODS__;
          const createRequestActionMethods = globalThis.__SC_V2_CREATE_REQUEST_ACTION_METHODS__;
          const createListFormMethods = globalThis.__SC_V2_CREATE_LIST_FORM_METHODS__;
          const createListFilterMethods = globalThis.__SC_V2_CREATE_LIST_FILTER_METHODS__;
          const createListTableMethods = globalThis.__SC_V2_CREATE_LIST_TABLE_METHODS__;
          const createListDialogMethods = globalThis.__SC_V2_CREATE_LIST_DIALOG_METHODS__;

          const pickTotal = (payload, depth = 0) => {
            if (depth > 4 || payload === null || typeof payload !== 'object' || Array.isArray(payload)) {
              return null;
            }

            const directKeys = ['total', 'count'];
            for (const key of directKeys) {
              if (typeof payload[key] === 'number') return payload[key];
            }

            const nestedKeys = ['data', 'result', 'payload'];
            for (const key of nestedKeys) {
              if (payload[key] !== undefined) {
                const total = pickTotal(payload[key], depth + 1);
                if (typeof total === 'number') return total;
              }
            }

            return null;
          };
          const normalizeSearchValue = (value, type) => {
            if (type === 'BETWEEN' && Array.isArray(value)) return value;
            return value;
          };
          const buildSearchQuery = (model, schema) => {
            const search = {};
            const searchType = {};

            Object.keys(schema || {}).forEach((key) => {
              const value = model[key];
              if (isBlank(value)) return;

              const meta = schema[key] || {};
              const type = String(meta.type || '=').toUpperCase();
              const targetKey = typeof meta.field === 'string' && meta.field !== ''
                ? meta.field
                : key;

              search[targetKey] = normalizeSearchValue(value, type);
              if (type !== '=') {
                searchType[targetKey] = type.toLowerCase();
              }
            });

            if (Object.keys(search).length === 0) {
              return {};
            }

            const payload = {
              s: search,
            };
            if (Object.keys(searchType).length > 0) {
              payload.t = searchType;
            }

            return {
              search: payload
            };
          };
          const compareValues = (left, right, order) => {
            const modifier = order === 'descending' ? -1 : 1;
            if (left === right) return 0;
            if (left === null || left === undefined) return -1 * modifier;
            if (right === null || right === undefined) return 1 * modifier;
            if (typeof left === 'number' && typeof right === 'number') {
              return (left - right) * modifier;
            }
            return String(left).localeCompare(String(right), 'zh-CN') * modifier;
          };
          const applyLocalSearch = (rows, model, schema) => {
            return rows.filter((row) => {
              return Object.keys(schema || {}).every((key) => {
                const value = model[key];
                if (isBlank(value)) return true;

                const meta = schema[key] || {};
                const type = String(meta.type || '=').toUpperCase();
                const rowValue = row[key];
                if (type === 'LIKE') {
                  return String(rowValue ?? '').includes(String(value));
                }
                if (type === 'LIKE_RIGHT') {
                  return String(rowValue ?? '').startsWith(String(value));
                }
                if (type === 'IN') {
                  return Array.isArray(value) ? value.includes(rowValue) : false;
                }
                if (type === 'BETWEEN') {
                  return Array.isArray(value) && value.length === 2
                    ? rowValue >= value[0] && rowValue <= value[1]
                    : true;
                }
                return String(rowValue ?? '') === String(value);
              });
            });
          };
          const resolveListKey = (listKey = null) => {
            if (typeof listKey === 'string' && listKey !== '') {
              return lists[listKey] ? listKey : '';
            }

            if (typeof cfg.primaryList === 'string' && cfg.primaryList !== '' && lists[cfg.primaryList]) {
              return cfg.primaryList;
            }

            return Object.keys(lists)[0] || '';
          };
          const getListConfig = (listKey = null) => {
            const resolvedKey = resolveListKey(listKey);
            if (!resolvedKey) {
              return null;
            }

            return lists[resolvedKey] || null;
          };
          const getFilterScope = (listKey = null) => {
            return getListConfig(listKey)?.filterScope || null;
          };
          const getFilterFormConfig = (listKey = null) => {
            const scope = getFilterScope(listKey);
            return scope ? (forms[scope] || null) : null;
          };
          const getFiltersForList = (vm, listKey = null) => {
            const scope = getFilterScope(listKey);
            if (!scope) {
              return {};
            }

            return getConfigState(vm, forms[scope] || {}, 'modelVar', 'modelPath') || {};
          };
          const resolveListTableKey = (listKey = null) => {
            return getListConfig(listKey)?.tableKey || null;
          };
          const resolveTableListKey = (tableKey = null) => {
            if (typeof tableKey === 'string' && tableKey !== '' && tables[tableKey]?.listKey) {
              return tables[tableKey].listKey;
            }

            const primaryTableKey = resolveListTableKey();
            if (typeof primaryTableKey === 'string' && primaryTableKey !== '') {
              return tables[primaryTableKey]?.listKey || null;
            }

            const primaryListKey = resolveListKey();
            return primaryListKey || null;
          };
          const resolveFiltersForTable = (vm, tableKey = null) => {
            const listKey = resolveTableListKey(tableKey);
            return listKey ? getFiltersForList(vm, listKey) : {};
          };
          const resolveActionTableKey = (actionConfig = null) => {
            if (typeof actionConfig?.tableKey === 'string' && actionConfig.tableKey !== '') {
              return actionConfig.tableKey;
            }

            if (typeof actionConfig?.listKey === 'string' && actionConfig.listKey !== '') {
              return resolveListTableKey(actionConfig.listKey);
            }

            return null;
          };
          const buildListEventContext = (vm, listKey = null, overrides = {}) => {
            const resolvedListKey = resolveListKey(listKey);
            const listCfg = getListConfig(resolvedListKey);
            const tableKey = resolveListTableKey(resolvedListKey);

            return Object.assign({
              listKey: resolvedListKey,
              listConfig: listCfg || {},
              tableKey,
              filters: getFiltersForList(vm, resolvedListKey),
              vm
            }, overrides);
          };

          const app = Vue.createApp({
            data(){
              return Object.assign({
                actionLoading: {},
                tableConfigs: tables,
                tableStates: buildTableStates(tables),
                ...buildManagedDialogRuntimeState(cfg.dialogs, forms),
              }, state || {});
            },
            created(){
              initializeConfiguredForms(forms, {
                initializeArrayGroups: (scope) => this.initializeFormArrayGroups(scope),
              });
            },
            mounted(){
              this.ensureDialogMessageBridge();
              initializeConfiguredForms(forms, {
                registerDependencies: (scope) => this.registerFormDependencies(scope),
                initializeOptions: (scope) => this.initializeFormOptions(scope),
                initializeUploads: (scope) => this.initializeUploadFiles(scope),
              });
              this.initializeTables();
            },
            methods: Object.assign(
              {},
              createColumnDisplayMethods(),
              createRequestActionMethods({
                cfg,
                getBaseContext: (vm, actionConfig) => ({
                  forms: buildFormsContext(vm, forms),
                  filters: resolveFiltersForTable(vm, resolveActionTableKey(actionConfig)),
                  dialogs: vm.dialogForms || {},
                  selection: typeof vm.getTableSelection === 'function'
                    ? vm.getTableSelection(resolveActionTableKey(actionConfig))
                    : [],
                })
              }),
              createListFormMethods({ cfg }),
              {
                validateSimpleForm(scope){
                  return this.validateForm(scope);
                },
                clearSimpleFormValidate(scope){
                  return this.clearFormValidate(scope);
                },
                notifyDialogHost(payload = {}){
                  return postDialogHostMessage(payload);
                },
                closeHostDialog(dialogKey = null){
                  const payload = { action: 'close' };
                  if (typeof dialogKey === 'string' && dialogKey !== '') {
                    payload.dialogKey = dialogKey;
                  }

                  return this.notifyDialogHost(payload);
                },
                reloadHostTable(tableKey = null, dialogKey = null){
                  const payload = { action: 'reloadTable' };
                  if (typeof tableKey === 'string' && tableKey !== '') {
                    payload.tableKey = tableKey;
                  }
                  if (typeof dialogKey === 'string' && dialogKey !== '') {
                    payload.dialogKey = dialogKey;
                  }

                  return this.notifyDialogHost(payload);
                },
                openHostDialog(target, row = null, tableKey = null){
                  if (typeof target !== 'string' || target === '') {
                    return false;
                  }

                  const payload = { action: 'openDialog', target };
                  if (row !== null && row !== undefined) {
                    payload.row = row;
                  }
                  if (typeof tableKey === 'string' && tableKey !== '') {
                    payload.tableKey = tableKey;
                  }

                  return this.notifyDialogHost(payload);
                },
                setHostDialogTitle(title, dialogKey = null){
                  const payload = {
                    action: 'setTitle',
                    title: title ?? '',
                  };
                  if (typeof dialogKey === 'string' && dialogKey !== '') {
                    payload.dialogKey = dialogKey;
                  }

                  return this.notifyDialogHost(payload);
                },
                setHostDialogFullscreen(value = true, dialogKey = null){
                  const payload = {
                    action: 'setFullscreen',
                    value: value !== false,
                  };
                  if (typeof dialogKey === 'string' && dialogKey !== '') {
                    payload.dialogKey = dialogKey;
                  }

                  return this.notifyDialogHost(payload);
                },
                toggleHostDialogFullscreen(dialogKey = null){
                  const payload = { action: 'toggleFullscreen' };
                  if (typeof dialogKey === 'string' && dialogKey !== '') {
                    payload.dialogKey = dialogKey;
                  }

                  return this.notifyDialogHost(payload);
                },
                refreshHostDialogIframe(dialogKey = null){
                  const payload = { action: 'refreshIframe' };
                  if (typeof dialogKey === 'string' && dialogKey !== '') {
                    payload.dialogKey = dialogKey;
                  }

                  return this.notifyDialogHost(payload);
                },
                setSimpleFormPathValue(scope, fieldName, value){
                  return this.setFormPathValue(scope, fieldName, value);
                },
                setSimpleFormModel(scope, values = {}){
                  return this.setFormModel(scope, values);
                },
                initializeSimpleFormModel(scope, values = {}){
                  return this.initializeFormModel(scope, values);
                },
                withSimpleDependencyResetSuspended(scope, callback){
                  return this.withDependencyResetSuspended(scope, callback);
                },
                initializeSimpleFormOptions(scope, force = false){
                  return this.initializeFormOptions(scope, force);
                },
                loadSimpleFormFieldOptions(scope, fieldName, force = false){
                  return this.loadFormFieldOptions(scope, fieldName, force);
                },
                initializeSimpleUploadFiles(scope){
                  return this.initializeUploadFiles(scope);
                },
                setSimpleUploadFileList(...args){
                  return this.setUploadFileList(...args);
                },
                handleSimpleUploadSuccess(...args){
                  return this.handleUploadSuccess(...args);
                },
                handleSimpleUploadRemove(...args){
                  return this.handleUploadRemove(...args);
                },
                handleSimpleUploadExceed(...args){
                  return this.handleUploadExceed(...args);
                },
                handleSimpleUploadPreview(...args){
                  return this.handleUploadPreview(...args);
                },
                applySimpleFormLinkage(...args){
                  return this.applyFormLinkage(...args);
                },
                resolveListKey(listKey = null){
                  return resolveListKey(listKey);
                },
                resolveListTableKey(listKey = null){
                  return resolveListTableKey(listKey);
                },
                getListConfig(listKey = null){
                  return getListConfig(listKey);
                },
                reloadList(listKey = null){
                  const resolvedListKey = resolveListKey(listKey);
                  const listCfg = getListConfig(resolvedListKey);
                  const tableKey = resolveListTableKey(resolvedListKey);
                  if (!tableKey) {
                    return Promise.resolve([]);
                  }

                  return emitConfiguredEvent(
                    listCfg || {},
                    'reload',
                    buildListEventContext(this, resolvedListKey)
                  ).then((results) => {
                    if (isEventCanceled(results)) {
                      return [];
                    }

                    return this.reloadTable(tableKey);
                  });
                },
                deleteListSelection(listKey = null, confirmText = '确认删除当前选中数据？', actionConfig = null, actionContext = null){
                  const tableKey = resolveListTableKey(listKey);
                  if (!tableKey) {
                    return Promise.resolve(null);
                  }

                  return this.deleteTableSelection(tableKey, confirmText, actionConfig, actionContext);
                }
              },
              createListFilterMethods({
                cfg,
                clone,
                getFilterFormConfig,
                getFilterScope,
                resolveListKey
              }),
              createListTableMethods({
                applyLocalSearch,
                buildSearchQuery,
                clone,
                compareValues,
                cfg,
                ensureSuccess,
                extractPayload,
                getSearchModel: (vm, tableKey) => resolveFiltersForTable(vm, tableKey),
                makeRequest,
                pickRows,
                pickTotal,
                resolveMessage
              }),
              createListDialogMethods({
                buildFormsContext: (vm) => buildFormsContext(vm, forms),
                clone,
                cfg,
                ensureSuccess,
                extractPayload,
                resolveFiltersForTable,
                resolveMessage
              })
            )
          });
          registerElementPlusIcons(app);
          registerScV2Components(app);
          app.use(ElementPlus, { locale: ElementPlusLocaleZhCn });
          app.mount('#app');
        };
