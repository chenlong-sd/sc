        globalThis.__SC_V2_CREATE_LIST_FORM_METHODS__ = ({ cfg }) => {
          const createManagedFormMethods = globalThis.__SC_V2_CREATE_MANAGED_FORM_METHODS__;
          const { getConfigState } = globalThis.__SC_V2_RUNTIME_HELPERS__;
          const conditionalValidation = globalThis.__SC_V2_CONDITIONAL_VALIDATION__;
          const getScopedFormConfig = (scope) => cfg?.forms?.[scope] || null;
          const buildConditionalFieldContexts = (vm, scope) => {
            const formConfig = getScopedFormConfig(scope) || {};
            const model = getConfigState(vm, formConfig, 'modelVar', 'modelPath') || {};
            const state = typeof vm?.getState === 'function' ? (vm.getState() || {}) : (vm?.pageState || {});
            const fieldMetas = formConfig?.fieldMetas || {};
            const getByPath = globalThis.__SC_V2_RUNTIME_HELPERS__?.getByPath;

            const getFieldOptions = (fieldName) => {
              if (typeof vm?.getFieldOptions === 'function') {
                return vm.getFieldOptions(scope, fieldName);
              }

              return [];
            };
            const getFieldConfig = (fieldName) => {
              if (typeof vm?.getFieldConfig === 'function') {
                return vm.getFieldConfig(scope, fieldName);
              }

              return {};
            };
            const getFieldOptionLoading = (fieldName) => {
              if (typeof vm?.getFieldOptionLoading === 'function') {
                return vm.getFieldOptionLoading(scope, fieldName);
              }

              return false;
            };
            const getFieldOptionLoaded = (fieldName) => {
              if (typeof vm?.getFieldOptionLoaded === 'function') {
                return vm.getFieldOptionLoaded(scope, fieldName);
              }

              return false;
            };

            return Object.keys(formConfig?.rules || {}).reduce((contexts, fieldName) => {
              contexts[fieldName] = () => {
                const fieldMeta = fieldMetas[fieldName] || {};
                const parentPath = String(fieldName || '').split('.').slice(0, -1).join('.');
                const localModel = parentPath === '' || typeof getByPath !== 'function'
                  ? model
                  : (getByPath(model, parentPath) || model);

                return {
                  model: localModel,
                  form: model,
                  state,
                  pageState: state,
                  scope,
                  fieldName,
                  vm,
                  options: getFieldOptions(fieldName),
                  fieldConfig: getFieldConfig(fieldName),
                  optionLoading: getFieldOptionLoading(fieldName),
                  optionLoaded: getFieldOptionLoaded(fieldName),
                  field: fieldMeta,
                };
              };

              return contexts;
            }, {});
          };

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
                () => model,
                () => buildConditionalFieldContexts(vm, scope)
              );
            }

            return rawRules;
          };

          globalThis.__SC_V2_BUILD_LIST_FIELD_CONDITIONAL_CONTEXTS__ = buildConditionalFieldContexts;

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
              getFieldConfig: 'getFieldConfig',
              getFieldOptionLoading: 'getFieldOptionLoading',
              getFieldOptionLoaded: 'getFieldOptionLoaded',
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
            getOptionSourcesMap: (scope) => getScopedFormConfig(scope)?.optionSources || {},
            getPickersMap: (scope) => getScopedFormConfig(scope)?.pickers || {},
            getPickerPaths: (scope) => getScopedFormConfig(scope)?.pickerPaths || [],
            getLinkagesMap: (scope) => getScopedFormConfig(scope)?.linkages || {},
            getUploadsMap: (scope) => getScopedFormConfig(scope)?.uploads || {},
            getUploadPaths: (scope) => getScopedFormConfig(scope)?.uploadPaths || []
          });
        };
