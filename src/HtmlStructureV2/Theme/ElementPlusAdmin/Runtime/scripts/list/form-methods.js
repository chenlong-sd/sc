        globalThis.__SC_V2_CREATE_LIST_FORM_METHODS__ = ({ cfg }) => {
          const createManagedFormMethods = globalThis.__SC_V2_CREATE_MANAGED_FORM_METHODS__;
          const dialogScopePrefix = 'dialog:';
          const normalizeDialogKey = (value) => {
            const normalized = String(value || '').replace(/[^a-zA-Z0-9_$]+/g, '_');
            return normalized || 'dialog';
          };
          const resolveDialogKey = (scope) => {
            if (typeof scope !== 'string' || !scope.startsWith(dialogScopePrefix)) {
              return null;
            }

            return scope.slice(dialogScopePrefix.length) || null;
          };
          const getDialogConfig = (scope) => {
            const dialogKey = resolveDialogKey(scope);
            if (!dialogKey) {
              return null;
            }

            return cfg.dialogs?.[dialogKey] || null;
          };

          return createManagedFormMethods({
            tokenStoreKey: '__remoteRequestTokens',
            dependencyLockStoreKey: '__dependencyResetLocks',
            methodNames: {
              getFormRef: 'getFormRef',
              validateForm: 'validateForm',
              clearFormValidate: 'clearFormValidate',
              getFormModel: 'getFormModel',
              getOptionState: 'getOptionState',
              getOptionLoadingState: 'getOptionLoadingState',
              getOptionLoadedState: 'getOptionLoadedState',
              getUploadFileState: 'getUploadFileState',
              getFieldOptions: 'getFieldOptions',
              getLinkageConfig: 'getLinkageConfig',
              clearLinkageTargets: 'clearLinkageTargets',
              applyFormLinkage: 'applyFormLinkage',
              nextRemoteRequestToken: 'nextRemoteRequestToken',
              isLatestRemoteRequestToken: 'isLatestRemoteRequestToken',
              setDependencyResetSuspended: 'setDependencyResetSuspended',
              isDependencyResetSuspended: 'isDependencyResetSuspended',
              withDependencyResetSuspended: 'withDependencyResetSuspended',
              resetRemoteFieldState: 'resetRemoteFieldState',
              registerFormDependencies: 'registerFormDependencies',
              reloadDependentFieldOptions: 'reloadDependentFieldOptions',
              initializeFormOptions: 'initializeFormOptions',
              loadFormFieldOptions: 'loadFormFieldOptions',
              initializeUploadFiles: 'initializeUploadFiles',
              handleUploadSuccess: 'handleUploadSuccess',
              handleUploadRemove: 'handleUploadRemove',
              handleUploadExceed: 'handleUploadExceed',
              handleUploadPreview: 'handleUploadPreview'
            },
            getRefName: (scope) => {
              if (scope === 'filter') {
                return 'filterFormRef';
              }

              const dialogKey = resolveDialogKey(scope);
              return dialogKey ? ('dialogFormRef_' + normalizeDialogKey(dialogKey)) : null;
            },
            getFormModel: (vm, scope) => {
              if (scope === 'filter') {
                return vm.filterModel;
              }

              const dialogKey = resolveDialogKey(scope);
              return dialogKey ? (vm.dialogForms?.[dialogKey] || {}) : {};
            },
            getOptionState: (vm, scope) => {
              if (scope === 'filter') {
                return vm.filterOptions;
              }

              const dialogKey = resolveDialogKey(scope);
              return dialogKey ? (vm.dialogOptions?.[dialogKey] || {}) : {};
            },
            getOptionLoadingState: (vm, scope) => {
              if (scope === 'filter') {
                return vm.filterOptionLoading;
              }

              const dialogKey = resolveDialogKey(scope);
              return dialogKey ? (vm.dialogOptionLoading?.[dialogKey] || {}) : {};
            },
            getOptionLoadedState: (vm, scope) => {
              if (scope === 'filter') {
                return vm.filterOptionLoaded;
              }

              const dialogKey = resolveDialogKey(scope);
              return dialogKey ? (vm.dialogOptionLoaded?.[dialogKey] || {}) : {};
            },
            getUploadFileState: (vm, scope) => {
              if (scope === 'filter') {
                return vm.filterUploadFiles;
              }

              const dialogKey = resolveDialogKey(scope);
              return dialogKey ? (vm.dialogUploadFiles?.[dialogKey] || {}) : {};
            },
            getRemoteOptionsMap: (scope) => scope === 'filter'
              ? (cfg.filterRemoteOptions || {})
              : (getDialogConfig(scope)?.remoteOptions || {}),
            getSelectOptionsMap: (scope) => scope === 'filter'
              ? (cfg.filterSelectOptions || {})
              : (getDialogConfig(scope)?.selectOptions || {}),
            getLinkagesMap: (scope) => scope === 'filter'
              ? (cfg.filterLinkages || {})
              : (getDialogConfig(scope)?.linkages || {}),
            getUploadsMap: (scope) => scope === 'filter'
              ? (cfg.filterUploads || {})
              : (getDialogConfig(scope)?.uploads || {})
          });
        };
