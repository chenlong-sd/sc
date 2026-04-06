        globalThis.__SC_V2_CREATE_SIMPLE_FORM_METHODS__ = ({ cfg }) => {
          const createManagedFormMethods = globalThis.__SC_V2_CREATE_MANAGED_FORM_METHODS__;
          const { getConfigState } = globalThis.__SC_V2_RUNTIME_HELPERS__;
          const getFormConfig = (scope) => cfg?.forms?.[scope] || null;

          return createManagedFormMethods({
            tokenStoreKey: '__simpleRemoteRequestTokens',
            methodNames: {
              getFormRef: 'getSimpleFormRef',
              validateForm: 'validateSimpleForm',
              clearFormValidate: 'clearSimpleFormValidate',
              getFormModel: 'getSimpleFormModel',
              setFormPathValue: 'setSimpleFormPathValue',
              getOptionState: 'getSimpleOptionState',
              getOptionLoadingState: 'getSimpleOptionLoadingState',
              getOptionLoadedState: 'getSimpleOptionLoadedState',
              getUploadFileState: 'getSimpleUploadFileState',
              setUploadFileList: 'setSimpleUploadFileList',
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
            getFormModel: (vm, scope) => getConfigState(vm, getFormConfig(scope), 'modelVar', 'modelPath'),
            getOptionState: (vm, scope) => getConfigState(vm, getFormConfig(scope), 'optionStateVar', 'optionStatePath', true),
            getOptionLoadingState: (vm, scope) => getConfigState(vm, getFormConfig(scope), 'optionLoadingVar', 'optionLoadingPath', true),
            getOptionLoadedState: (vm, scope) => getConfigState(vm, getFormConfig(scope), 'optionLoadedVar', 'optionLoadedPath', true),
            getUploadFileState: (vm, scope) => getConfigState(vm, getFormConfig(scope), 'uploadFilesVar', 'uploadFilesPath', true),
            getFormEvents: (scope) => getFormConfig(scope)?.events || {},
            getArrayGroups: (scope) => getFormConfig(scope)?.arrayGroups || [],
            getRemoteOptionsMap: (scope) => getFormConfig(scope)?.remoteOptions || {},
            getRemoteOptionPaths: (scope) => getFormConfig(scope)?.remoteOptionPaths || [],
            getSelectOptionsMap: (scope) => getFormConfig(scope)?.selectOptions || {},
            getLinkagesMap: (scope) => getFormConfig(scope)?.linkages || {},
            getUploadsMap: (scope) => getFormConfig(scope)?.uploads || {},
            getUploadPaths: (scope) => getFormConfig(scope)?.uploadPaths || []
          });
        };
