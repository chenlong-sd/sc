        globalThis.__SC_V2_CREATE_SIMPLE_FORM_METHODS__ = ({ cfg }) => {
          const createManagedFormMethods = globalThis.__SC_V2_CREATE_MANAGED_FORM_METHODS__;
          const { getConfigState } = globalThis.__SC_V2_RUNTIME_HELPERS__;
          const conditionalValidation = globalThis.__SC_V2_CONDITIONAL_VALIDATION__;
          const getFormConfig = (scope) => cfg?.forms?.[scope] || null;

          // 包装获取规则的方法，自动处理条件验证
          const getFormRules = (vm, scope) => {
            const formConfig = getFormConfig(scope);
            if (!formConfig) return null;

            const rawRules = getConfigState(vm, formConfig, 'rulesVar', 'rulesPath');
            const model = getConfigState(vm, formConfig, 'modelVar', 'modelPath');

            // 如果有条件验证支持，则处理规则
            if (conditionalValidation && rawRules && model) {
              return conditionalValidation.createConditionalRules(
                () => rawRules,
                () => model
              );
            }

            return rawRules;
          };

          return createManagedFormMethods({
            tokenStoreKey: '__simpleRemoteRequestTokens',
            methodNames: {
              getFormRef: 'getSimpleFormRef',
              validateForm: 'validateSimpleForm',
              clearFormValidate: 'clearSimpleFormValidate',
              getFormModel: 'getSimpleFormModel',
              cloneSubmitModel: 'cloneSimpleFormSubmitModel',
              setFormModel: 'setSimpleFormModel',
              initializeFormModel: 'initializeSimpleFormModel',
              setFormPathValue: 'setSimpleFormPathValue',
              getOptionState: 'getSimpleOptionState',
              getOptionLoadingState: 'getSimpleOptionLoadingState',
              getOptionLoadedState: 'getSimpleOptionLoadedState',
              getUploadFileState: 'getSimpleUploadFileState',
              getPickerState: 'getSimplePickerState',
              initializePickerState: 'initializeSimplePickerState',
              setUploadFileList: 'setSimpleUploadFileList',
              setFieldOptions: 'setSimpleFieldOptions',
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
              handleUploadBefore: 'handleSimpleUploadBefore',
              handleUploadSuccess: 'handleSimpleUploadSuccess',
              handleUploadError: 'handleSimpleUploadError',
              handleUploadRemove: 'handleSimpleUploadRemove',
              handleUploadExceed: 'handleSimpleUploadExceed',
              handleUploadProgress: 'handleSimpleUploadProgress',
              handleUploadPreview: 'handleSimpleUploadPreview'
            },
            getFormConfig: (scope) => getFormConfig(scope),
            getRefName: (scope) => getFormConfig(scope)?.ref || null,
            getFormModel: (vm, scope) => getConfigState(vm, getFormConfig(scope), 'modelVar', 'modelPath'),
            getFormRules: getFormRules,
            getOptionState: (vm, scope) => getConfigState(vm, getFormConfig(scope), 'optionStateVar', 'optionStatePath', true),
            getOptionLoadingState: (vm, scope) => getConfigState(vm, getFormConfig(scope), 'optionLoadingVar', 'optionLoadingPath', true),
            getOptionLoadedState: (vm, scope) => getConfigState(vm, getFormConfig(scope), 'optionLoadedVar', 'optionLoadedPath', true),
            getUploadFileState: (vm, scope) => getConfigState(vm, getFormConfig(scope), 'uploadFilesVar', 'uploadFilesPath', true),
            getPickerState: (vm, scope) => getConfigState(vm, getFormConfig(scope), 'pickerStateVar', 'pickerStatePath', true),
            getFormEvents: (scope) => getFormConfig(scope)?.events || {},
            getArrayGroups: (scope) => getFormConfig(scope)?.arrayGroups || [],
            getRemoteOptionsMap: (scope) => getFormConfig(scope)?.remoteOptions || {},
            getRemoteOptionPaths: (scope) => getFormConfig(scope)?.remoteOptionPaths || [],
            getSelectOptionsMap: (scope) => getFormConfig(scope)?.selectOptions || {},
            getOptionSourcesMap: (scope) => getFormConfig(scope)?.optionSources || {},
            getPickersMap: (scope) => getFormConfig(scope)?.pickers || {},
            getPickerPaths: (scope) => getFormConfig(scope)?.pickerPaths || [],
            getLinkagesMap: (scope) => getFormConfig(scope)?.linkages || {},
            getUploadsMap: (scope) => getFormConfig(scope)?.uploads || {},
            getUploadPaths: (scope) => getFormConfig(scope)?.uploadPaths || []
          });
        };
