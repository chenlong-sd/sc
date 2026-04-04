        (function(cfg){
          const {
            buildFlagState,
            buildFormsContext,
            buildManagedDialogRuntimeState,
            buildOptionState,
            buildUploadFileState,
            clone,
            extractPayload,
            ensureSuccess,
            initializeConfiguredForms,
            isBlank,
            makeRequest,
            pickRows,
            resolveMessage,
          } = globalThis.__SC_V2_RUNTIME_HELPERS__;
          const forms = cfg.forms || {};
          const filterConfig = forms.filter || {};

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
            const searchField = {};

            Object.keys(schema || {}).forEach((key) => {
              const value = model[key];
              if (isBlank(value)) return;

              const meta = schema[key] || {};
              search[key] = normalizeSearchValue(value, meta.type || '=');
              searchType[key] = meta.type || '=';
              if (meta.field) {
                searchField[key] = meta.field;
              }
            });

            if (Object.keys(search).length === 0) {
              return {};
            }

            return {
              search: {
                search,
                searchType,
                searchField,
              }
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
          const app = Vue.createApp({
            data(){
              return {
                filterModel: clone(filterConfig.defaults || {}),
                filterInitial: clone(filterConfig.defaults || {}),
                filterRules: filterConfig.rules || {},
                filterOptions: buildOptionState(filterConfig.remoteOptions),
                filterOptionLoading: buildFlagState(filterConfig.remoteOptions),
                filterOptionLoaded: buildFlagState(filterConfig.remoteOptions),
                filterUploadFiles: buildUploadFileState(filterConfig.uploads, filterConfig.defaults),
                ...buildManagedDialogRuntimeState(cfg.dialogs, forms),
                actionLoading: {},
                tableRows: clone(cfg.initialRows),
                tableAllRows: clone(cfg.initialRows),
                tableSelection: [],
                tableTotal: Array.isArray(cfg.initialRows) ? cfg.initialRows.length : 0,
                tablePage: 1,
                tablePageSize: cfg.pagination?.pageSize || 20,
                tableSort: {
                  field: '',
                  order: null
                },
                tableLoading: false
              };
            },
            mounted(){
              this.ensureDialogMessageBridge();
              initializeConfiguredForms(forms, {
                registerDependencies: (scope) => this.registerFormDependencies(scope),
                initializeOptions: (scope) => this.initializeFormOptions(scope),
                initializeUploads: (scope) => this.initializeUploadFiles(scope),
              });

              if (cfg.list && cfg.list.type === 'remote') {
                this.loadTableData();
                return;
              }
              this.applyClientTableState();
            },
            methods: Object.assign(
              {},
              createRequestActionMethods({
                getBaseContext: (vm) => ({
                  forms: buildFormsContext(vm, forms),
                  filters: vm.filterModel || {},
                  dialogs: vm.dialogForms || {},
                  selection: Array.isArray(vm.tableSelection) ? vm.tableSelection : [],
                })
              }),
              createListFormMethods({ cfg }),
              createListFilterMethods({
                clone
              }),
              createListTableMethods({
                applyLocalSearch,
                buildSearchQuery,
                clone,
                compareValues,
                cfg,
                ensureSuccess,
                extractPayload,
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
                resolveMessage
              })
            )
          });
          app.use(ElementPlus, { locale: ElementPlusLocaleZhCn });
          app.mount('#app');
        })(__SC_V2_CONFIG__);
