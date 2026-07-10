        globalThis.__SC_V2_CREATE_SIMPLE_FORM_METHODS__ = ({ cfg }) => {
          const createManagedFormMethods = globalThis.__SC_V2_CREATE_MANAGED_FORM_METHODS__;
          const { getConfigState, getByPath } = globalThis.__SC_V2_RUNTIME_HELPERS__;
          const conditionalValidation = globalThis.__SC_V2_CONDITIONAL_VALIDATION__;
          const getFormConfig = (scope) => cfg?.forms?.[scope] || null;
          const buildConditionalFieldContexts = (vm, scope) => {
            const formConfig = getFormConfig(scope) || {};
            const model = getConfigState(vm, formConfig, 'modelVar', 'modelPath') || {};
            const state = typeof vm?.getState === 'function' ? (vm.getState() || {}) : (vm?.pageState || {});
            const fieldMetas = formConfig?.fieldMetas || {};

            const getFieldOptions = (fieldName) => {
              if (typeof vm?.getSimpleFieldOptions === 'function') {
                return vm.getSimpleFieldOptions(scope, fieldName);
              }

              return [];
            };
            const getFieldConfig = (fieldName) => {
              if (typeof vm?.getSimpleFieldConfig === 'function') {
                return vm.getSimpleFieldConfig(scope, fieldName);
              }

              return {};
            };
            const getFieldOptionLoading = (fieldName) => {
              if (typeof vm?.getSimpleFieldOptionLoading === 'function') {
                return vm.getSimpleFieldOptionLoading(scope, fieldName);
              }

              return false;
            };
            const getFieldOptionLoaded = (fieldName) => {
              if (typeof vm?.getSimpleFieldOptionLoaded === 'function') {
                return vm.getSimpleFieldOptionLoaded(scope, fieldName);
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
            const formConfig = getFormConfig(scope);
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

          globalThis.__SC_V2_BUILD_SIMPLE_FIELD_CONDITIONAL_CONTEXTS__ = buildConditionalFieldContexts;

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
              getFieldConfig: 'getSimpleFieldConfig',
              getFieldOptionLoading: 'getSimpleFieldOptionLoading',
              getFieldOptionLoaded: 'getSimpleFieldOptionLoaded',
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
