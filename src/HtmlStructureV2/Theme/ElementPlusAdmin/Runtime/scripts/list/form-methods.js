        globalThis.__SC_V2_CREATE_LIST_FORM_METHODS__ = ({ cfg }) => {
          const createManagedFormMethods = globalThis.__SC_V2_CREATE_MANAGED_FORM_METHODS__;
          const { getConfigState } = globalThis.__SC_V2_RUNTIME_HELPERS__;
          const getScopedFormConfig = (scope) => cfg?.forms?.[scope] || null;

          return createManagedFormMethods({
            tokenStoreKey: '__remoteRequestTokens',
            dependencyLockStoreKey: '__dependencyResetLocks',
            methodNames: {
              getFormRef: 'getFormRef',
              validateForm: 'validateForm',
              clearFormValidate: 'clearFormValidate',
              getFormModel: 'getFormModel',
              setFormPathValue: 'setFormPathValue',
              getOptionState: 'getOptionState',
              getOptionLoadingState: 'getOptionLoadingState',
              getOptionLoadedState: 'getOptionLoadedState',
              getUploadFileState: 'getUploadFileState',
              setUploadFileList: 'setUploadFileList',
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
            getRefName: (scope) => getScopedFormConfig(scope)?.ref || null,
            getFormModel: (vm, scope) => getConfigState(vm, getScopedFormConfig(scope), 'modelVar', 'modelPath'),
            getOptionState: (vm, scope) => getConfigState(vm, getScopedFormConfig(scope), 'optionStateVar', 'optionStatePath', true),
            getOptionLoadingState: (vm, scope) => getConfigState(vm, getScopedFormConfig(scope), 'optionLoadingVar', 'optionLoadingPath', true),
            getOptionLoadedState: (vm, scope) => getConfigState(vm, getScopedFormConfig(scope), 'optionLoadedVar', 'optionLoadedPath', true),
            getUploadFileState: (vm, scope) => getConfigState(vm, getScopedFormConfig(scope), 'uploadFilesVar', 'uploadFilesPath', true),
            getFormEvents: (scope) => getScopedFormConfig(scope)?.events || {},
            getArrayGroups: (scope) => getScopedFormConfig(scope)?.arrayGroups || [],
            getRemoteOptionsMap: (scope) => getScopedFormConfig(scope)?.remoteOptions || {},
            getRemoteOptionPaths: (scope) => getScopedFormConfig(scope)?.remoteOptionPaths || [],
            getSelectOptionsMap: (scope) => getScopedFormConfig(scope)?.selectOptions || {},
            getLinkagesMap: (scope) => getScopedFormConfig(scope)?.linkages || {},
            getUploadsMap: (scope) => getScopedFormConfig(scope)?.uploads || {},
            getUploadPaths: (scope) => getScopedFormConfig(scope)?.uploadPaths || []
          });
        };
