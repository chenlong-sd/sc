        globalThis.__SC_V2_CREATE_SIMPLE_FORM_METHODS__ = ({ cfg }) => {
          const createManagedFormMethods = globalThis.__SC_V2_CREATE_MANAGED_FORM_METHODS__;
          const getFormConfig = (scope) => cfg?.forms?.[scope] || null;

          return createManagedFormMethods({
            tokenStoreKey: '__simpleRemoteRequestTokens',
            methodNames: {
              getFormRef: 'getSimpleFormRef',
              validateForm: 'validateSimpleForm',
              clearFormValidate: 'clearSimpleFormValidate',
              getFormModel: 'getSimpleFormModel',
              getOptionState: 'getSimpleOptionState',
              getOptionLoadingState: 'getSimpleOptionLoadingState',
              getOptionLoadedState: 'getSimpleOptionLoadedState',
              getUploadFileState: 'getSimpleUploadFileState',
              getFieldOptions: 'getSimpleFieldOptions',
              getLinkageConfig: 'getSimpleLinkageConfig',
              clearLinkageTargets: 'clearSimpleLinkageTargets',
              applyFormLinkage: 'applySimpleFormLinkage',
              nextRemoteRequestToken: 'nextSimpleRemoteRequestToken',
              isLatestRemoteRequestToken: 'isLatestSimpleRemoteRequestToken',
              setDependencyResetSuspended: 'setSimpleDependencyResetSuspended',
              isDependencyResetSuspended: 'isSimpleDependencyResetSuspended',
              withDependencyResetSuspended: 'withSimpleDependencyResetSuspended',
              resetRemoteFieldState: 'resetSimpleRemoteFieldState',
              registerFormDependencies: 'registerSimpleFormDependencies',
              reloadDependentFieldOptions: 'reloadSimpleDependentFieldOptions',
              initializeFormOptions: 'initializeSimpleFormOptions',
              loadFormFieldOptions: 'loadSimpleFormFieldOptions',
              initializeUploadFiles: 'initializeSimpleUploadFiles',
              handleUploadSuccess: 'handleSimpleUploadSuccess',
              handleUploadRemove: 'handleSimpleUploadRemove',
              handleUploadExceed: 'handleSimpleUploadExceed',
              handleUploadPreview: 'handleSimpleUploadPreview'
            },
            getRefName: (scope) => getFormConfig(scope)?.ref || null,
            getFormModel: (vm, scope) => {
              const varName = getFormConfig(scope)?.modelVar;
              return varName ? (vm[varName] || {}) : {};
            },
            getOptionState: (vm, scope) => {
              const varName = getFormConfig(scope)?.optionStateVar;
              if (!varName) return {};
              vm[varName] ??= {};
              return vm[varName];
            },
            getOptionLoadingState: (vm, scope) => {
              const varName = getFormConfig(scope)?.optionLoadingVar;
              if (!varName) return {};
              vm[varName] ??= {};
              return vm[varName];
            },
            getOptionLoadedState: (vm, scope) => {
              const varName = getFormConfig(scope)?.optionLoadedVar;
              if (!varName) return {};
              vm[varName] ??= {};
              return vm[varName];
            },
            getUploadFileState: (vm, scope) => {
              const varName = getFormConfig(scope)?.uploadFilesVar;
              if (!varName) return {};
              vm[varName] ??= {};
              return vm[varName];
            },
            getRemoteOptionsMap: (scope) => getFormConfig(scope)?.remoteOptions || {},
            getSelectOptionsMap: (scope) => getFormConfig(scope)?.selectOptions || {},
            getLinkagesMap: (scope) => getFormConfig(scope)?.linkages || {},
            getUploadsMap: (scope) => getFormConfig(scope)?.uploads || {}
          });
        };
