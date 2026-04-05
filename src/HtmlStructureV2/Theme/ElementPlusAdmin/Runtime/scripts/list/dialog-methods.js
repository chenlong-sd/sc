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

                return {
                  forms: buildFormsContext(vm),
                  filters: resolveFiltersForTable(vm, tableKey),
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
                clearFormValidate: 'clearFormValidate',
                validateForm: 'validateForm',
              }
            }),
            {
              deleteRow(row, confirmText = '确认删除当前记录？'){
                const primaryListKey = cfg.primaryList || Object.keys(cfg.lists || {})[0] || '';
                const tableKey = cfg.lists?.[primaryListKey]?.tableKey || null;

                return this.deleteTableRow(tableKey, row, confirmText);
              }
            }
          );
        };
