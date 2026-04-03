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
            getBaseContext: (vm) => ({
              forms: buildFormsContext(vm),
              selection: Array.isArray(vm.tableSelection) ? vm.tableSelection : [],
            }),
            formMethodNames: {
              withDependencyResetSuspended: 'withSimpleDependencyResetSuspended',
              initializeFormOptions: 'initializeSimpleFormOptions',
              initializeUploadFiles: 'initializeSimpleUploadFiles',
              clearFormValidate: 'clearSimpleFormValidate',
              validateForm: 'validateSimpleForm',
            }
          });
        };
