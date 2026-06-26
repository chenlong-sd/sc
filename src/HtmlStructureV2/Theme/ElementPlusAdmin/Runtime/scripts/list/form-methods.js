        globalThis.__SC_V2_CREATE_LIST_FORM_METHODS__ = ({ cfg }) => {
          const createManagedFormMethods = globalThis.__SC_V2_CREATE_MANAGED_FORM_METHODS__;
          const { getConfigState } = globalThis.__SC_V2_RUNTIME_HELPERS__;
          const conditionalValidation = globalThis.__SC_V2_CONDITIONAL_VALIDATION__;
          const getScopedFormConfig = (scope) => cfg?.forms?.[scope] || null;

          // 包装获取规则的方法，自动处理条件验证
          const getFormRules = (vm, scope) => {
            const formConfig = getScopedFormConfig(scope);
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
            tokenStoreKey: '__remoteRequestTokens',
            dependencyLockStoreKey: '__dependencyResetLocks',
            methodNames: {
              getFormRef: 'getFormRef',
              validateForm: 'validateForm',
              clearFormValidate: 'clearFormValidate',
              getFormModel: 'getFormModel',
              cloneSubmitModel: 'cloneFormSubmitModel',
              setFormModel: 'setFormModel',
              initializeFormModel: 'initializeFormModel',
              setFormPathValue: 'setFormPathValue',
              getOptionState: 'getOptionState',
              getOptionLoadingState: 'getOptionLoadingState',
              getOptionLoadedState: 'getOptionLoadedState',
              getUploadFileState: 'getUploadFileState',
              getPickerState: 'getPickerState',
              initializePickerState: 'initializePickerState',
              setUploadFileList: 'setUploadFileList',
              setFieldOptions: 'setFieldOptions',
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
              handleUploadBefore: 'handleUploadBefore',
              handleUploadSuccess: 'handleUploadSuccess',
              handleUploadError: 'handleUploadError',
              handleUploadRemove: 'handleUploadRemove',
              handleUploadExceed: 'handleUploadExceed',
              handleUploadProgress: 'handleUploadProgress',
              handleUploadPreview: 'handleUploadPreview'
            },
            getFormConfig: (scope) => getScopedFormConfig(scope),
            getRefName: (scope) => getScopedFormConfig(scope)?.ref || null,
            getFormModel: (vm, scope) => getConfigState(vm, getScopedFormConfig(scope), 'modelVar', 'modelPath'),
            getFormRules: getFormRules,
            getOptionState: (vm, scope) => getConfigState(vm, getScopedFormConfig(scope), 'optionStateVar', 'optionStatePath', true),
            getOptionLoadingState: (vm, scope) => getConfigState(vm, getScopedFormConfig(scope), 'optionLoadingVar', 'optionLoadingPath', true),
            getOptionLoadedState: (vm, scope) => getConfigState(vm, getScopedFormConfig(scope), 'optionLoadedVar', 'optionLoadedPath', true),
            getUploadFileState: (vm, scope) => getConfigState(vm, getScopedFormConfig(scope), 'uploadFilesVar', 'uploadFilesPath', true),
            getPickerState: (vm, scope) => getConfigState(vm, getScopedFormConfig(scope), 'pickerStateVar', 'pickerStatePath', true),
            getFormEvents: (scope) => getScopedFormConfig(scope)?.events || {},
            getArrayGroups: (scope) => getScopedFormConfig(scope)?.arrayGroups || [],
            getRemoteOptionsMap: (scope) => getScopedFormConfig(scope)?.remoteOptions || {},
            getRemoteOptionPaths: (scope) => getScopedFormConfig(scope)?.remoteOptionPaths || [],
            getSelectOptionsMap: (scope) => getScopedFormConfig(scope)?.selectOptions || {},
            getPickersMap: (scope) => getScopedFormConfig(scope)?.pickers || {},
            getPickerPaths: (scope) => getScopedFormConfig(scope)?.pickerPaths || [],
            getLinkagesMap: (scope) => getScopedFormConfig(scope)?.linkages || {},
            getUploadsMap: (scope) => getScopedFormConfig(scope)?.uploads || {},
            getUploadPaths: (scope) => getScopedFormConfig(scope)?.uploadPaths || []
          });
        };
