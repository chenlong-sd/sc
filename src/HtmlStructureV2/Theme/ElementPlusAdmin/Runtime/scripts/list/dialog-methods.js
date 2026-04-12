        globalThis.__SC_V2_CREATE_LIST_DIALOG_METHODS__ = ({
          buildFormsContext,
          clone,
          cfg,
          ensureSuccess,
          extractPayload,
          resolveFiltersForTable,
          resolveMessage
        }) => {
          const createManagedDialogMethods = globalThis.__SC_V2_CREATE_MANAGED_DIALOG_METHODS__;
          const { readPageLocation, resolvePageMode } = globalThis.__SC_V2_RUNTIME_HELPERS__;

          return Object.assign(
            {},
            createManagedDialogMethods({
              clone,
              cfg,
              ensureSuccess,
              extractPayload,
              resolveMessage,
              getBaseContext: (vm, dialogKey, row, activeTableKey) => {
                const tableKey = activeTableKey
                  || (typeof vm.getActiveDialogTableKey === 'function'
                    ? (vm.getActiveDialogTableKey(dialogKey) || null)
                    : null);
                const location = readPageLocation();
                const query = location.query || {};

                return {
                  forms: buildFormsContext(vm),
                  filters: resolveFiltersForTable(vm, tableKey),
                  query,
                  page: Object.assign({}, location, {
                    query,
                    mode: resolvePageMode(query),
                    formScope: null,
                  }),
                  selection: typeof vm.getTableSelection === 'function' ? vm.getTableSelection(tableKey) : [],
                  reloadTable: (target = tableKey) => {
                    if (!target) {
                      return undefined;
                    }

                    return typeof vm.reloadTable === 'function' ? vm.reloadTable(target) : undefined;
                  },
                };
              },
              formMethodNames: {
                withDependencyResetSuspended: 'withDependencyResetSuspended',
                initializeFormOptions: 'initializeFormOptions',
                initializeUploadFiles: 'initializeUploadFiles',
                initializePickerState: 'initializePickerState',
                clearFormValidate: 'clearFormValidate',
                validateForm: 'validateForm',
              }
            }),
            {
              deleteSelection(confirmText = '确认删除当前选中数据？'){
                const primaryListKey = cfg.primaryList || Object.keys(cfg.lists || {})[0] || '';
                return this.deleteListSelection(primaryListKey, confirmText);
              }
            }
          );
        };
