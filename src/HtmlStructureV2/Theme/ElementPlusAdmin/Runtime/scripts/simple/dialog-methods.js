        globalThis.__SC_V2_CREATE_SIMPLE_DIALOG_METHODS__ = ({
          buildFormsContext,
          clone,
          cfg,
          ensureSuccess,
          extractPayload,
          resolveMessage
        }) => {
          const createManagedDialogMethods = globalThis.__SC_V2_CREATE_MANAGED_DIALOG_METHODS__;

          return createManagedDialogMethods({
            clone,
            cfg,
            ensureSuccess,
            extractPayload,
            resolveMessage,
            getBaseContext: (vm, dialogKey, row, activeTableKey) => {
              const tableKey = activeTableKey
                || (typeof vm.getActiveDialogTableKey === 'function'
                  ? vm.getActiveDialogTableKey(dialogKey)
                  : null);

              return {
                forms: buildFormsContext(vm),
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
              withDependencyResetSuspended: 'withSimpleDependencyResetSuspended',
              initializeFormOptions: 'initializeSimpleFormOptions',
              initializeUploadFiles: 'initializeSimpleUploadFiles',
              clearFormValidate: 'clearSimpleFormValidate',
              validateForm: 'validateSimpleForm',
            }
          });
        };
