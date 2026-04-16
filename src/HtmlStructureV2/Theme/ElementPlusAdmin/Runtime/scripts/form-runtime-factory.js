        globalThis.__SC_V2_CREATE_MANAGED_FORM_METHODS__ = ({
          methodNames = {},
          tokenStoreKey = '__managedRemoteRequestTokens',
          dependencyLockStoreKey = null,
          getRefName,
          getFormConfig = () => null,
          getFormModel,
          getOptionState,
          getOptionLoadingState,
          getOptionLoadedState,
          getUploadFileState,
          getPickerState,
          getFormEvents,
          getArrayGroups,
          getRemoteOptionsMap,
          getRemoteOptionPaths,
          getSelectOptionsMap,
          getPickersMap,
          getPickerPaths,
          getLinkagesMap,
          getUploadsMap,
          getUploadPaths
        }) => {
          const {
            buildOptionState,
            buildPickerState,
            clone,
            emitConfiguredEvent,
            ensureFormArrayGroupState,
            extractPayload,
            ensureSuccess,
            getByPath,
            hasReadyDependencies,
            initializeFormModelBySchema,
            isEventCanceled,
            isBlank,
            isObject,
            isSameValue,
            makeRequest,
            normalizeArrayGroupRow,
            normalizeDependencies,
            normalizeOption,
            normalizePickerItems,
            normalizeUploadFiles,
            pickRows,
            resolveDialogKeyFromScope,
            resolveDynamicParams,
            resolveLinkageTemplate,
            resolvePickerDisplayTemplate,
            resolveMessage,
            resolveUploadValue,
            setConfigState,
            setByPath,
            syncUploadModelValue
          } = globalThis.__SC_V2_RUNTIME_HELPERS__;

          const names = Object.assign({
            getFormRef: 'getManagedFormRef',
            validateForm: 'validateManagedForm',
            clearFormValidate: 'clearManagedFormValidate',
            getFormModel: 'getManagedFormModel',
            setFormModel: 'setManagedFormModel',
            initializeFormModel: 'initializeManagedFormModel',
            getFormPathStateValue: 'getFormPathStateValue',
            setFormPathValue: 'setManagedFormPathValue',
            getFormArrayGroupConfig: 'getFormArrayGroupConfig',
            ensureFormArrayGroupState: 'ensureFormArrayGroupState',
            initializeFormArrayGroups: 'initializeFormArrayGroups',
            syncFormArrayGroupRemoteState: 'syncFormArrayGroupRemoteState',
            initializeArrayGroupRemoteOptions: 'initializeArrayGroupRemoteOptions',
            getFormArrayRows: 'getFormArrayRows',
            joinFormArrayFieldPath: 'joinFormArrayFieldPath',
            addFormArrayRow: 'addFormArrayRow',
            removeFormArrayRow: 'removeFormArrayRow',
            reorderFormArrayRow: 'reorderFormArrayRow',
            moveFormArrayRow: 'moveFormArrayRow',
            ensureFormTableSortableStore: 'ensureFormTableSortableStore',
            destroyFormTableSortables: 'destroyFormTableSortables',
            refreshFormTableSortables: 'refreshFormTableSortables',
            syncFormTableSortables: 'syncFormTableSortables',
            getOptionState: 'getManagedOptionState',
            getOptionLoadingState: 'getManagedOptionLoadingState',
            getOptionLoadedState: 'getManagedOptionLoadedState',
            getUploadFileState: 'getManagedUploadFileState',
            getPickerState: 'getManagedPickerState',
            initializePickerState: 'initializeManagedPickerState',
            setUploadFileList: 'setManagedUploadFileList',
            setFieldOptions: 'setManagedFieldOptions',
            getFieldOptions: 'getManagedFieldOptions',
            getLinkageConfig: 'getManagedLinkageConfig',
            clearLinkageTargets: 'clearManagedLinkageTargets',
            applyFormLinkage: 'applyManagedFormLinkage',
            getPickerItems: 'getPickerItems',
            openPickerDialog: 'openPickerDialog',
            applyPickerDialogSelection: 'applyPickerDialogSelection',
            removePickerItem: 'removePickerItem',
            clearPickerField: 'clearPickerField',
            resolvePickerItemDisplay: 'resolvePickerItemDisplay',
            nextRemoteRequestToken: 'nextManagedRemoteRequestToken',
            isLatestRemoteRequestToken: 'isLatestManagedRemoteRequestToken',
            setDependencyResetSuspended: 'setManagedDependencyResetSuspended',
            isDependencyResetSuspended: 'isManagedDependencyResetSuspended',
            withDependencyResetSuspended: 'withManagedDependencyResetSuspended',
            resetRemoteFieldState: 'resetManagedRemoteFieldState',
            registerFormDependencies: 'registerManagedFormDependencies',
            reloadDependentFieldOptions: 'reloadManagedDependentFieldOptions',
            initializeFormOptions: 'initializeManagedFormOptions',
            loadFormFieldOptions: 'loadManagedFormFieldOptions',
            initializeUploadFiles: 'initializeManagedUploadFiles',
            handleUploadBefore: 'handleManagedUploadBefore',
            handleUploadSuccess: 'handleManagedUploadSuccess',
            handleUploadError: 'handleManagedUploadError',
            handleUploadRemove: 'handleManagedUploadRemove',
            handleUploadExceed: 'handleManagedUploadExceed',
            handleUploadPreview: 'handleManagedUploadPreview'
          }, methodNames);
          const uploadNoticeStoreKey = '__scV2UploadNoticeStore';
          const getUploadNoticeStore = (vm) => {
            if (!isObject(vm[uploadNoticeStoreKey])) {
              vm[uploadNoticeStoreKey] = {};
            }

            return vm[uploadNoticeStoreKey];
          };
          const openUploadNotice = (vm, uploadFile) => {
            const uid = uploadFile?.uid;
            if (!uid) {
              return;
            }

            const store = getUploadNoticeStore(vm);
            if (typeof store[uid]?.close === 'function') {
              store[uid].close();
            }

            if (typeof ElementPlus.ElNotification === 'function') {
              store[uid] = ElementPlus.ElNotification({
                title: '提示',
                message: '文件上传中,请稍后...',
                type: 'warning',
                duration: 0,
                showClose: false
              });
            }
          };
          const closeUploadNotice = (vm, uploadFile) => {
            const uid = uploadFile?.uid;
            if (!uid) {
              return;
            }

            const store = getUploadNoticeStore(vm);
            if (typeof store[uid]?.close === 'function') {
              store[uid].close();
            }
            delete store[uid];
          };
          const buildFormEventContext = (vm, scope, overrides = {}) => {
            const model = getFormModel(vm, scope) || {};

            return Object.assign({
              scope,
              model,
              form: model,
              formConfig: {
                events: getFormEvents(scope, vm) || {}
              },
              vm
            }, overrides);
          };
          const emitFormEvent = (vm, scope, eventName, overrides = {}) => {
            return emitConfiguredEvent(
              { events: getFormEvents(scope, vm) || {} },
              eventName,
              buildFormEventContext(vm, scope, overrides)
            );
          };
          const buildNextFormModel = (vm, scope, values) => {
            const formCfg = (typeof getFormConfig === 'function' ? getFormConfig(scope, vm) : null) || {};
            const nextModel = Object.assign({}, clone(formCfg?.defaults || {}));
            if (isObject(values)) {
              Object.assign(nextModel, clone(values));
            }

            return {
              formCfg,
              nextModel
            };
          };
          const buildInitializedFormModel = (vm, scope, values) => {
            const formCfg = (typeof getFormConfig === 'function' ? getFormConfig(scope, vm) : null) || {};

            return {
              formCfg,
              nextModel: initializeFormModelBySchema(
                formCfg?.defaults || {},
                values,
                formCfg?.arrayGroups || []
              )
            };
          };
          const applyResolvedFormModel = (vm, scope, formCfg, nextModel) => {
            const applyModel = () => setConfigState(vm, formCfg, 'modelVar', 'modelPath', nextModel);

            if (typeof vm[names.withDependencyResetSuspended] === 'function') {
              vm[names.withDependencyResetSuspended](scope, applyModel);
            } else {
              applyModel();
            }

            if (typeof vm[names.initializeFormArrayGroups] === 'function') {
              vm[names.initializeFormArrayGroups](scope);
            }
            if (typeof vm[names.initializeFormOptions] === 'function') {
              vm[names.initializeFormOptions](scope, true);
            }
            if (typeof vm[names.initializeUploadFiles] === 'function') {
              vm[names.initializeUploadFiles](scope);
            }
            if (typeof vm[names.clearFormValidate] === 'function') {
              vm[names.clearFormValidate](scope);
            }

            return vm[names.getFormModel](scope) || {};
          };
          const syncFormStructureValidation = (vm, scope) => {
            Vue.nextTick(() => {
              if (typeof vm[names.clearFormValidate] === 'function') {
                vm[names.clearFormValidate](scope);
              }
              if (typeof vm[names.refreshFormTableSortables] === 'function') {
                vm[names.refreshFormTableSortables](scope);
              }
            });
          };
          const buildFormTableSortableKey = (scope, arrayPath) => {
            return [scope, arrayPath]
              .map((segment) => String(segment || '').trim())
              .join('::');
          };
          const queryScopedFormTableElements = (scope = null) => {
            const elements = Array.from(document.querySelectorAll('.sc-v2-form-table__table[data-sc-form-table="1"]'));
            const targetScope = typeof scope === 'string' ? scope.trim() : '';
            if (targetScope === '') {
              return elements;
            }

            return elements.filter((element) => String(element?.dataset?.scFormScope || '').trim() === targetScope);
          };
          const getFormTableBodyElement = (element) => {
            return element?.querySelector?.('.el-table__body-wrapper tbody') || null;
          };
          const contextualizeArrayRowFieldPath = (arrayPath, rowIndex, fieldPath) => {
            return [arrayPath, rowIndex, fieldPath]
              .filter((segment) => segment !== null && segment !== undefined && segment !== '')
              .join('.');
          };
          const joinConcretePath = (basePath, path) => {
            return [basePath, path]
              .filter((segment) => segment !== null && segment !== undefined && segment !== '')
              .join('.');
          };
          const resolveConcreteArrayGroupConfig = (groupConfigs, concreteArrayPath, parentRowPath = '') => {
            const targetPath = String(concreteArrayPath || '').trim();
            if (targetPath === '') {
              return null;
            }

            for (const groupCfg of (Array.isArray(groupConfigs) ? groupConfigs : [])) {
              const relativePath = typeof groupCfg?.path === 'string' ? groupCfg.path : '';
              if (relativePath === '') {
                continue;
              }

              const candidatePath = joinConcretePath(parentRowPath, relativePath);
              if (candidatePath === targetPath) {
                return Object.assign({}, groupCfg, {
                  path: candidatePath
                });
              }

              const childGroups = Array.isArray(groupCfg?.rowArrayGroups) ? groupCfg.rowArrayGroups : [];
              if (childGroups.length === 0 || !targetPath.startsWith(candidatePath + '.')) {
                continue;
              }

              const remainingPath = targetPath.slice(candidatePath.length + 1);
              const nextSegment = remainingPath.split('.')[0];
              if (!/^\d+$/.test(nextSegment || '')) {
                continue;
              }

              const childMatch = resolveConcreteArrayGroupConfig(
                childGroups,
                targetPath,
                joinConcretePath(candidatePath, nextSegment)
              );
              if (childMatch) {
                return childMatch;
              }
            }

            return null;
          };
          const resolveArrayGroupFieldContextRecursive = (groupConfigs, fieldName, parentRowPath = '') => {
            const targetField = String(fieldName || '').trim();
            if (targetField === '') {
              return null;
            }

            for (const groupCfg of (Array.isArray(groupConfigs) ? groupConfigs : [])) {
              const relativePath = typeof groupCfg?.path === 'string' ? groupCfg.path : '';
              if (relativePath === '') {
                continue;
              }

              const arrayPath = joinConcretePath(parentRowPath, relativePath);
              if (!targetField.startsWith(arrayPath + '.')) {
                continue;
              }

              const remainingSegments = targetField.slice(arrayPath.length + 1)
                .split('.')
                .filter((segment) => segment !== '');
              const rowIndexSegment = remainingSegments[0] || '';
              if (!/^\d+$/.test(rowIndexSegment)) {
                continue;
              }

              const rowIndex = Number(rowIndexSegment);
              const rowFieldPath = remainingSegments.slice(1).join('.');
              const childGroups = Array.isArray(groupCfg?.rowArrayGroups) ? groupCfg.rowArrayGroups : [];
              if (childGroups.length > 0 && rowFieldPath !== '') {
                const childContext = resolveArrayGroupFieldContextRecursive(
                  childGroups,
                  targetField,
                  joinConcretePath(arrayPath, rowIndexSegment)
                );
                if (childContext) {
                  return childContext;
                }
              }

              return {
                groupConfig: groupCfg,
                arrayPath,
                rowIndex,
                rowFieldPath
              };
            }

            return null;
          };
          const resolveArrayGroupFieldContext = (vm, scope, fieldName) => {
            return resolveArrayGroupFieldContextRecursive(getArrayGroups(scope, vm) || [], fieldName);
          };
          const readFormArrayRows = (model, arrayPath) => {
            const rows = getByPath(model, arrayPath);
            return Array.isArray(rows) ? rows : [];
          };
          const visitConcreteArrayGroupInstances = (
            vm,
            scope,
            visitor,
            groupConfigs = getArrayGroups(scope, vm) || [],
            parentArrayPath = null,
            parentRowIndex = null
          ) => {
            (Array.isArray(groupConfigs) ? groupConfigs : []).forEach((groupCfg) => {
              const relativePath = typeof groupCfg?.path === 'string' ? groupCfg.path : '';
              if (relativePath === '') {
                return;
              }

              const concreteArrayPath = parentArrayPath === null
                ? relativePath
                : contextualizeArrayRowFieldPath(parentArrayPath, parentRowIndex, relativePath);
              const rows = vm[names.getFormArrayRows](scope, concreteArrayPath);

              visitor(groupCfg, concreteArrayPath, rows);

              const childGroups = Array.isArray(groupCfg?.rowArrayGroups) ? groupCfg.rowArrayGroups : [];
              if (childGroups.length === 0) {
                return;
              }

              rows.forEach((_, rowIndex) => {
                visitConcreteArrayGroupInstances(
                  vm,
                  scope,
                  visitor,
                  childGroups,
                  concreteArrayPath,
                  rowIndex
                );
              });
            });
          };
          const collectConcreteFieldPathsByGroupConfig = (vm, scope, targetGroupCfg, rowFieldPath) => {
            const fieldPaths = [];
            if (!targetGroupCfg || typeof rowFieldPath !== 'string' || rowFieldPath === '') {
              return fieldPaths;
            }

            visitConcreteArrayGroupInstances(vm, scope, (groupCfg, concreteArrayPath, rows) => {
              if (groupCfg !== targetGroupCfg) {
                return;
              }

              rows.forEach((_, rowIndex) => {
                fieldPaths.push(contextualizeArrayRowFieldPath(concreteArrayPath, rowIndex, rowFieldPath));
              });
            });

            return fieldPaths;
          };
          const resolveScopedFieldConfig = (vm, scope, fieldName, directMap, arrayGroupConfigKey) => {
            const direct = getByPath(directMap || {}, fieldName);
            if (direct !== undefined) {
              return direct;
            }

            const context = resolveArrayGroupFieldContext(vm, scope, fieldName);
            if (!context?.rowFieldPath) {
              return undefined;
            }

            return getByPath(context.groupConfig?.[arrayGroupConfigKey] || {}, context.rowFieldPath);
          };
          const resolvePickerFieldConfig = (vm, scope, fieldName) => {
            return resolveScopedFieldConfig(
              vm,
              scope,
              fieldName,
              getPickersMap(scope, vm) || {},
              'rowPickers'
            ) || null;
          };
          const ensurePickerBindingStore = (vm) => {
            if (!vm || typeof vm !== 'object') {
              return {};
            }

            if (!vm.__pickerDialogBindings || typeof vm.__pickerDialogBindings !== 'object') {
              vm.__pickerDialogBindings = {};
            }

            return vm.__pickerDialogBindings;
          };
          const syncPickerModelValue = (model, fieldName, fieldCfg, items) => {
            const normalized = normalizePickerItems(items, fieldCfg || {});
            const values = normalized.map((item) => item?.__pickerValue);
            setByPath(
              model,
              fieldName,
              fieldCfg?.multiple === false
                ? (values[0] ?? null)
                : values
            );

            return normalized;
          };
          const contextualizeArrayRowModelTokens = (value, arrayPath, rowIndex) => {
            if (Array.isArray(value)) {
              return value.map((item) => contextualizeArrayRowModelTokens(item, arrayPath, rowIndex));
            }
            if (value && typeof value === 'object') {
              return Object.keys(value).reduce((output, key) => {
                output[key] = contextualizeArrayRowModelTokens(value[key], arrayPath, rowIndex);
                return output;
              }, {});
            }
            if (typeof value !== 'string' || !value.startsWith('@')) {
              return value;
            }

            return '@' + contextualizeArrayRowFieldPath(arrayPath, rowIndex, value.slice(1));
          };
          const contextualizeArrayRowDependencies = (dependencies, arrayPath, rowIndex) => {
            return normalizeDependencies({ dependencies })
              .map((path) => contextualizeArrayRowFieldPath(arrayPath, rowIndex, path))
              .filter((path) => path !== '');
          };
          const buildArrayRowRemoteFieldConfig = (groupCfg, rowFieldPath, arrayPath, rowIndex) => {
            const fieldCfg = getByPath(groupCfg?.rowRemoteOptions || {}, rowFieldPath);
            if (!fieldCfg?.url) {
              return null;
            }

            return Object.assign({}, fieldCfg, {
              dependencies: contextualizeArrayRowDependencies(fieldCfg.dependencies || [], arrayPath, rowIndex),
              params: contextualizeArrayRowModelTokens(clone(fieldCfg.params || {}), arrayPath, rowIndex)
            });
          };
          const resolveRemoteFieldConfig = (vm, scope, fieldName) => {
            const direct = getByPath(getRemoteOptionsMap(scope, vm) || {}, fieldName);
            if (direct?.url) {
              return direct;
            }

            const context = resolveArrayGroupFieldContext(vm, scope, fieldName);
            if (!context?.rowFieldPath) {
              return null;
            }

            return buildArrayRowRemoteFieldConfig(
              context.groupConfig,
              context.rowFieldPath,
              context.arrayPath,
              context.rowIndex
            );
          };
          const resolveOptionFieldConfig = (vm, scope, fieldName) => {
            const remoteConfig = resolveRemoteFieldConfig(vm, scope, fieldName);
            if (remoteConfig) {
              return remoteConfig;
            }

            const staticConfig = resolveScopedFieldConfig(
              vm,
              scope,
              fieldName,
              getSelectOptionsMap(scope, vm) || {},
              'rowSelectOptions'
            );

            return isObject(staticConfig) ? staticConfig : {};
          };
          const contextualizeArrayRowLinkageTemplate = (template, arrayPath, rowIndex) => {
            if (typeof template !== 'string') {
              return template;
            }

            return template.replace(/@model\.([A-Za-z0-9_.$-]+)/g, (_, path) => {
              return '@model.' + [arrayPath, rowIndex, path]
                .filter((segment) => segment !== null && segment !== undefined && segment !== '')
                .join('.');
            });
          };
          const buildResolvedLinkageConfig = (vm, scope, fieldName) => {
            const direct = getByPath(getLinkagesMap(scope, vm) || {}, fieldName);
            if (direct !== undefined && direct !== null) {
              return direct;
            }

            const context = resolveArrayGroupFieldContext(vm, scope, fieldName);
            if (!context?.rowFieldPath) {
              return null;
            }

            const linkageConfig = getByPath(context.groupConfig?.rowLinkages || {}, context.rowFieldPath);
            if (!linkageConfig?.updates) {
              return linkageConfig || null;
            }

            const updates = {};
            Object.keys(linkageConfig.updates || {}).forEach((targetField) => {
              updates[vm[names.joinFormArrayFieldPath](context.arrayPath, context.rowIndex, targetField)] = contextualizeArrayRowLinkageTemplate(
                linkageConfig.updates[targetField],
                context.arrayPath,
                context.rowIndex
              );
            });

            return Object.assign({}, linkageConfig, { updates });
          };
          const rebuildUploadFileState = (vm, scope) => {
            const model = vm[names.getFormModel](scope);
            const nextState = {};
            const uploadConfigs = getUploadsMap(scope, vm) || {};
            const uploadPaths = getUploadPaths(scope, vm) || [];

            uploadPaths.forEach((fieldName) => {
              const fieldCfg = getByPath(uploadConfigs, fieldName) || {};
              const normalizedFiles = normalizeUploadFiles(getByPath(model, fieldName), fieldCfg);
              setByPath(
                nextState,
                fieldName,
                normalizedFiles
              );
              syncUploadModelValue(model, fieldName, fieldCfg, normalizedFiles);
            });

            visitConcreteArrayGroupInstances(vm, scope, (groupCfg, concreteArrayPath, rows) => {
              const rowUploadPaths = Array.isArray(groupCfg?.rowUploadPaths) ? groupCfg.rowUploadPaths : [];
              const rowUploads = groupCfg?.rowUploads || {};

              rows.forEach((_, rowIndex) => {
                rowUploadPaths.forEach((rowFieldPath) => {
                  const fieldName = contextualizeArrayRowFieldPath(concreteArrayPath, rowIndex, rowFieldPath);
                  const fieldCfg = getByPath(rowUploads, rowFieldPath) || {};
                  const normalizedFiles = normalizeUploadFiles(getByPath(model, fieldName), fieldCfg);
                  setByPath(
                    nextState,
                    fieldName,
                    normalizedFiles
                  );
                  syncUploadModelValue(model, fieldName, fieldCfg, normalizedFiles);
                });
              });
            });

            const state = vm[names.getUploadFileState](scope);
            Object.keys(state || {}).forEach((key) => {
              delete state[key];
            });
            Object.assign(state, nextState);

            return state;
          };
          const rebuildPickerState = (vm, scope) => {
            const nextState = buildPickerState(
              getPickersMap(scope, vm) || {},
              getPickerPaths(scope, vm) || []
            );

            visitConcreteArrayGroupInstances(vm, scope, (groupCfg, concreteArrayPath, rows) => {
              setByPath(
                nextState,
                concreteArrayPath,
                (Array.isArray(rows) ? rows : []).map(() => clone(groupCfg?.defaultPickerRow || {}))
              );
            });

            const state = vm[names.getPickerState](scope);
            Object.keys(state || {}).forEach((key) => {
              delete state[key];
            });
            Object.assign(state, nextState);

            return state;
          };
          const syncArrayGroupPickerRows = (vm, scope, arrayPath, callback) => {
            const pickerState = vm[names.getPickerState](scope);
            const pickerRows = getByPath(pickerState, arrayPath);
            const nextRows = Array.isArray(pickerRows) ? pickerRows : [];

            callback(nextRows);
            setByPath(pickerState, arrayPath, nextRows);

            return nextRows;
          };
          const rebuildArrayGroupRemoteOptionState = (vm, scope, arrayPath = null) => {
            const optionState = vm[names.getOptionState](scope);
            const loadingState = vm[names.getOptionLoadingState](scope);
            const loadedState = vm[names.getOptionLoadedState](scope);

            const rootGroups = arrayPath === null
              ? (getArrayGroups(scope, vm) || [])
              : [vm[names.getFormArrayGroupConfig](scope, arrayPath)].filter(Boolean);
            if (rootGroups.length === 0) {
              return;
            }

            rootGroups.forEach((groupCfg) => {
              const rootPath = typeof groupCfg?.path === 'string' ? groupCfg.path : '';
              if (rootPath === '') {
                return;
              }

              setByPath(optionState, rootPath, []);
              setByPath(loadingState, rootPath, []);
              setByPath(loadedState, rootPath, []);
            });

            visitConcreteArrayGroupInstances(vm, scope, (groupCfg, concreteArrayPath, rows) => {
              const rowSelectOptions = isObject(groupCfg?.rowSelectOptions) ? groupCfg.rowSelectOptions : {};
              const rowRemoteOptionPaths = Array.isArray(groupCfg?.rowRemoteOptionPaths) ? groupCfg.rowRemoteOptionPaths : [];
              if (rowRemoteOptionPaths.length === 0 && Object.keys(rowSelectOptions).length === 0) {
                return;
              }

              rows.forEach((_, rowIndex) => {
                const rowOptionState = buildOptionState(
                  rowSelectOptions,
                  rowRemoteOptionPaths.reduce((configs, rowFieldPath) => {
                    const fieldCfg = buildArrayRowRemoteFieldConfig(groupCfg, rowFieldPath, concreteArrayPath, rowIndex);
                    if (fieldCfg) {
                      setByPath(configs, rowFieldPath, fieldCfg);
                    }
                    return configs;
                  }, {}),
                  rowRemoteOptionPaths
                );

                setByPath(
                  optionState,
                  contextualizeArrayRowFieldPath(concreteArrayPath, rowIndex, ''),
                  rowOptionState
                );

                rowRemoteOptionPaths.forEach((rowFieldPath) => {
                  const fieldName = contextualizeArrayRowFieldPath(concreteArrayPath, rowIndex, rowFieldPath);
                  setByPath(loadingState, fieldName, false);
                  setByPath(loadedState, fieldName, false);
                });
              });
            }, rootGroups);
          };

          return {
            [names.getFormRef](scope){
              const refName = getRefName(scope, this);
              if (!refName) return null;

              const formRef = this.$refs[refName];
              return Array.isArray(formRef) ? formRef[0] : formRef;
            },
            [names.validateForm](scope){
              const formRef = this[names.getFormRef](scope);
              if (!formRef || typeof formRef.validate !== 'function') {
                return emitFormEvent(this, scope, 'validateSuccess', { skipped: true })
                  .then(() => true);
              }

              try {
                const result = formRef.validate();
                if (result && typeof result.then === 'function') {
                  return result
                    .then(() => emitFormEvent(this, scope, 'validateSuccess').then(() => true))
                    .catch((error) => emitFormEvent(this, scope, 'validateFail', { error }).then(() => false));
                }
              } catch (error) {
                return emitFormEvent(this, scope, 'validateFail', { error }).then(() => false);
              }

              return emitFormEvent(this, scope, 'validateSuccess').then(() => true);
            },
            [names.clearFormValidate](scope){
              const formRef = this[names.getFormRef](scope);
              if (formRef && typeof formRef.clearValidate === 'function') {
                formRef.clearValidate();
              }
            },
            [names.getFormModel](scope){
              return getFormModel(this, scope) || {};
            },
            [names.setFormModel](scope, values = {}){
              const { formCfg, nextModel } = buildNextFormModel(this, scope, values);
              return applyResolvedFormModel(this, scope, formCfg, nextModel);
            },
            [names.initializeFormModel](scope, values = {}){
              const { formCfg, nextModel } = buildInitializedFormModel(this, scope, values);
              return applyResolvedFormModel(this, scope, formCfg, nextModel);
            },
            [names.setFormPathValue](scope, fieldName, value){
              const model = this[names.getFormModel](scope);
              setByPath(model, fieldName, value);

              return value;
            },
            [names.getFormPathStateValue](state, fieldName, fallback = null){
              const value = getByPath(state || {}, fieldName);
              return value === undefined ? fallback : value;
            },
            [names.getFormArrayGroupConfig](scope, arrayPath){
              return resolveConcreteArrayGroupConfig(getArrayGroups(scope, this) || [], arrayPath);
            },
            [names.ensureFormArrayGroupState](scope, arrayPath){
              const model = this[names.getFormModel](scope);
              return ensureFormArrayGroupState(model, Object.assign({
                path: arrayPath
              }, this[names.getFormArrayGroupConfig](scope, arrayPath) || {}));
            },
            [names.initializeFormArrayGroups](scope){
              const model = this[names.getFormModel](scope);
              (getArrayGroups(scope, this) || []).forEach((groupCfg) => {
                ensureFormArrayGroupState(model, groupCfg || {});
              });
              rebuildArrayGroupRemoteOptionState(this, scope);
              rebuildPickerState(this, scope);
              this[names.syncFormTableSortables](scope);

              return model;
            },
            [names.syncFormArrayGroupRemoteState](scope, arrayPath){
              rebuildArrayGroupRemoteOptionState(this, scope, arrayPath ?? null);
            },
            [names.initializeArrayGroupRemoteOptions](scope, arrayPath, force = false){
              const rootGroups = arrayPath === null || arrayPath === undefined
                ? (getArrayGroups(scope, this) || [])
                : [this[names.getFormArrayGroupConfig](scope, arrayPath)].filter(Boolean);
              if (rootGroups.length === 0) {
                return;
              }

              visitConcreteArrayGroupInstances(this, scope, (groupCfg, concreteArrayPath, rows) => {
                const rowRemoteOptionPaths = Array.isArray(groupCfg?.rowRemoteOptionPaths) ? groupCfg.rowRemoteOptionPaths : [];
                rows.forEach((_, rowIndex) => {
                  rowRemoteOptionPaths.forEach((rowFieldPath) => {
                    this[names.loadFormFieldOptions](
                      scope,
                      contextualizeArrayRowFieldPath(concreteArrayPath, rowIndex, rowFieldPath),
                      force
                    );
                  });
                });
              }, rootGroups);
            },
            [names.getFormArrayRows](scope, arrayPath){
              const model = this[names.getFormModel](scope);
              return readFormArrayRows(model, arrayPath);
            },
            [names.ensureFormTableSortableStore](){
              if (!isObject(this.__scV2FormTableSortables)) {
                this.__scV2FormTableSortables = {};
              }

              return this.__scV2FormTableSortables;
            },
            [names.destroyFormTableSortables](scope = null){
              const targetScope = typeof scope === 'string' ? scope.trim() : '';
              const store = this[names.ensureFormTableSortableStore]();
              Object.keys(store).forEach((key) => {
                if (targetScope !== '' && !key.startsWith(targetScope + '::')) {
                  return;
                }

                const sortable = store[key];
                if (sortable && typeof sortable.destroy === 'function') {
                  sortable.destroy();
                }
                delete store[key];
              });

              return store;
            },
            [names.refreshFormTableSortables](scope = null){
              const targetScope = typeof scope === 'string' ? scope.trim() : '';
              this[names.destroyFormTableSortables](targetScope);

              if (typeof Sortable !== 'function') {
                return [];
              }

              const created = [];
              queryScopedFormTableElements(targetScope).forEach((element) => {
                if (String(element?.dataset?.scFormTableSortable || '') !== '1') {
                  return;
                }

                const tableScope = String(element?.dataset?.scFormScope || '').trim();
                const arrayPath = String(element?.dataset?.scFormArrayPath || '').trim();
                if (tableScope === '' || arrayPath === '') {
                  return;
                }

                const rows = this[names.getFormArrayRows](tableScope, arrayPath);
                if (!Array.isArray(rows) || rows.length <= 1) {
                  return;
                }

                const tbody = getFormTableBodyElement(element);
                if (!tbody) {
                  return;
                }

                const sortable = new Sortable(tbody, {
                  animation: 150,
                  handle: '.sc-v2-table-drag-handle',
                  onEnd: (event) => {
                    const oldIndex = Number(event?.oldIndex);
                    const newIndex = Number(event?.newIndex);
                    if (!Number.isInteger(oldIndex) || !Number.isInteger(newIndex) || oldIndex === newIndex) {
                      return;
                    }

                    this[names.reorderFormArrayRow](tableScope, arrayPath, oldIndex, newIndex);
                  },
                });

                this[names.ensureFormTableSortableStore]()[buildFormTableSortableKey(tableScope, arrayPath)] = sortable;
                created.push(sortable);
              });

              return created;
            },
            [names.syncFormTableSortables](scope = null){
              const targetScope = typeof scope === 'string' ? scope.trim() : '';
              if (typeof this.$nextTick === 'function') {
                return this.$nextTick().then(() => this[names.refreshFormTableSortables](targetScope));
              }

              return Promise.resolve(this[names.refreshFormTableSortables](targetScope));
            },
            [names.joinFormArrayFieldPath](arrayPath, rowIndex, fieldName){
              return [arrayPath, rowIndex, fieldName]
                .filter((segment) => segment !== null && segment !== undefined && segment !== '')
                .join('.');
            },
            [names.addFormArrayRow](scope, arrayPath){
              const groupCfg = this[names.getFormArrayGroupConfig](scope, arrayPath) || {};
              const rows = this[names.getFormArrayRows](scope, arrayPath);
              const maxRows = groupCfg?.maxRows ?? null;
              if (maxRows !== null && maxRows !== undefined && Number(maxRows) > 0 && rows.length >= Number(maxRows)) {
                ElementPlus.ElMessage.warning('已达到最大行数限制');
                return rows;
              }

              const row = normalizeArrayGroupRow(clone(groupCfg?.defaultRow || {}), groupCfg || {});
              rows.push(row);
              syncArrayGroupPickerRows(this, scope, arrayPath, (pickerRows) => {
                pickerRows.push(clone(groupCfg?.defaultPickerRow || {}));
              });
              rebuildUploadFileState(this, scope);
              this[names.syncFormArrayGroupRemoteState](scope, arrayPath);
              this[names.initializeArrayGroupRemoteOptions](scope, arrayPath);
              syncFormStructureValidation(this, scope);
              emitFormEvent(this, scope, 'arrayRowAdd', {
                arrayPath,
                groupConfig: groupCfg,
                row,
                rowIndex: rows.length - 1,
                rows
              });
              return rows;
            },
            [names.removeFormArrayRow](scope, arrayPath, rowIndex){
              const groupCfg = this[names.getFormArrayGroupConfig](scope, arrayPath) || {};
              const rows = this[names.getFormArrayRows](scope, arrayPath);
              if (!Array.isArray(rows) || rows.length === 0) {
                return rows;
              }

              const minRows = groupCfg?.minRows ?? 0;
              if (Number(minRows) > 0 && rows.length <= Number(minRows)) {
                ElementPlus.ElMessage.warning('已达到最小行数限制');
                return rows;
              }

              const index = Number(rowIndex);
              if (!Number.isInteger(index) || index < 0 || index >= rows.length) {
                return rows;
              }

              const [row] = rows.splice(index, 1);
              syncArrayGroupPickerRows(this, scope, arrayPath, (pickerRows) => {
                if (index >= 0 && index < pickerRows.length) {
                  pickerRows.splice(index, 1);
                }
              });
              rebuildUploadFileState(this, scope);
              this[names.syncFormArrayGroupRemoteState](scope, arrayPath);
              this[names.initializeArrayGroupRemoteOptions](scope, arrayPath);
              syncFormStructureValidation(this, scope);
              emitFormEvent(this, scope, 'arrayRowRemove', {
                arrayPath,
                groupConfig: groupCfg,
                row: row || null,
                rowIndex: index,
                rows
              });
              return rows;
            },
            [names.reorderFormArrayRow](scope, arrayPath, fromIndex, toIndex){
              const groupCfg = this[names.getFormArrayGroupConfig](scope, arrayPath) || {};
              const rows = this[names.getFormArrayRows](scope, arrayPath);
              if (!Array.isArray(rows) || rows.length <= 1) {
                return rows;
              }

              const sourceIndex = Number(fromIndex);
              const targetIndex = Number(toIndex);
              if (!Number.isInteger(sourceIndex) || !Number.isInteger(targetIndex)) {
                return rows;
              }
              if (sourceIndex < 0 || sourceIndex >= rows.length || targetIndex < 0 || targetIndex >= rows.length) {
                return rows;
              }
              if (sourceIndex === targetIndex) {
                return rows;
              }

              const [row] = rows.splice(sourceIndex, 1);
              rows.splice(targetIndex, 0, row);
              syncArrayGroupPickerRows(this, scope, arrayPath, (pickerRows) => {
                if (sourceIndex < 0 || sourceIndex >= pickerRows.length) {
                  return;
                }

                const [pickerRow] = pickerRows.splice(sourceIndex, 1);
                pickerRows.splice(targetIndex, 0, pickerRow);
              });
              rebuildUploadFileState(this, scope);
              this[names.syncFormArrayGroupRemoteState](scope, arrayPath);
              this[names.initializeArrayGroupRemoteOptions](scope, arrayPath);
              syncFormStructureValidation(this, scope);
              emitFormEvent(this, scope, 'arrayRowMove', {
                arrayPath,
                groupConfig: groupCfg,
                row,
                fromIndex: sourceIndex,
                toIndex: targetIndex,
                direction: targetIndex > sourceIndex ? 'down' : 'up',
                rows
              });

              return rows;
            },
            [names.moveFormArrayRow](scope, arrayPath, rowIndex, direction = 'up'){
              const rows = this[names.getFormArrayRows](scope, arrayPath);
              if (!Array.isArray(rows) || rows.length <= 1) {
                return rows;
              }

              const index = Number(rowIndex);
              if (!Number.isInteger(index) || index < 0 || index >= rows.length) {
                return rows;
              }

              const targetIndex = direction === 'down' ? index + 1 : index - 1;
              return this[names.reorderFormArrayRow](scope, arrayPath, index, targetIndex);
            },
            [names.getOptionState](scope){
              return getOptionState(this, scope) || {};
            },
            [names.getOptionLoadingState](scope){
              return getOptionLoadingState(this, scope) || {};
            },
            [names.getOptionLoadedState](scope){
              return getOptionLoadedState(this, scope) || {};
            },
            [names.getUploadFileState](scope){
              return getUploadFileState(this, scope) || {};
            },
            [names.getPickerState](scope){
              return getPickerState(this, scope) || {};
            },
            [names.initializePickerState](scope){
              return rebuildPickerState(this, scope);
            },
            [names.setUploadFileList](scope, fieldName, uploadFiles){
              const fieldCfg = resolveScopedFieldConfig(
                this,
                scope,
                fieldName,
                getUploadsMap(scope, this) || {},
                'rowUploads'
              ) || {};
              const nextFiles = normalizeUploadFiles(uploadFiles || [], fieldCfg);
              setByPath(this[names.getUploadFileState](scope), fieldName, nextFiles);

              return nextFiles;
            },
            [names.setFieldOptions](scope, fieldName, options = []){
              const fieldCfg = resolveOptionFieldConfig(this, scope, fieldName);
              const nextOptions = (Array.isArray(options) ? options : [])
                .map((item, index) => normalizeOption(item, fieldCfg, index));

              setByPath(this[names.getOptionState](scope), fieldName, nextOptions);
              setByPath(this[names.getOptionLoadingState](scope), fieldName, false);
              setByPath(this[names.getOptionLoadedState](scope), fieldName, true);

              return nextOptions;
            },
            [names.getFieldOptions](scope, fieldName){
              const stateOptions = getByPath(this[names.getOptionState](scope), fieldName);
              if (Array.isArray(stateOptions)) {
                return stateOptions;
              }

              const options = resolveScopedFieldConfig(
                this,
                scope,
                fieldName,
                getSelectOptionsMap(scope, this) || {},
                'rowSelectOptions'
              ) || [];

              return Array.isArray(options) ? clone(options) : [];
            },
            [names.getLinkageConfig](scope, fieldName){
              return buildResolvedLinkageConfig(this, scope, fieldName);
            },
            [names.clearLinkageTargets](scope, fieldName){
              const linkCfg = this[names.getLinkageConfig](scope, fieldName);
              if (!linkCfg?.updates) {
                return;
              }

              const model = this[names.getFormModel](scope);
              Object.keys(linkCfg.updates).forEach((targetField) => {
                const currentValue = getByPath(model, targetField);
                setByPath(model, targetField, Array.isArray(currentValue) ? [] : '');
              });
            },
            [names.applyFormLinkage](scope, fieldName, value){
              const linkCfg = this[names.getLinkageConfig](scope, fieldName);
              if (!linkCfg?.updates) {
                return;
              }

              const model = this[names.getFormModel](scope);
              const currentValue = value ?? getByPath(model, fieldName);
              if (isBlank(currentValue)) {
                if (linkCfg.clearOnEmpty !== false) {
                  this[names.clearLinkageTargets](scope, fieldName);
                }
                return;
              }

              const option = this[names.getFieldOptions](scope, fieldName)
                .find((item) => isSameValue(item?.value, currentValue));
              if (!option) {
                return;
              }

              const context = {
                scope,
                fieldName,
                value: currentValue,
                option,
                model
              };

              Object.keys(linkCfg.updates).forEach((targetField) => {
                setByPath(model, targetField, resolveLinkageTemplate(linkCfg.updates[targetField], context));
              });
            },
            [names.getPickerItems](scope, fieldName){
              const fieldCfg = resolvePickerFieldConfig(this, scope, fieldName);
              if (!fieldCfg) {
                return [];
              }

              return normalizePickerItems(
                getByPath(this[names.getPickerState](scope), fieldName) || [],
                fieldCfg
              );
            },
            [names.openPickerDialog](scope, fieldName, dialogKey = null){
              const fieldCfg = resolvePickerFieldConfig(this, scope, fieldName);
              if (!fieldCfg) {
                ElementPlus.ElMessage.error('选择器字段配置不存在');
                return false;
              }

              const resolvedDialogKey = typeof dialogKey === 'string' && dialogKey !== ''
                ? dialogKey
                : (fieldCfg.dialogKey || '');
              if (resolvedDialogKey === '') {
                ElementPlus.ElMessage.error('未配置选择弹窗');
                return false;
              }

              if (typeof this.openDialog !== 'function') {
                ElementPlus.ElMessage.error('当前页面未启用弹窗运行时');
                return false;
              }

              ensurePickerBindingStore(this)[resolvedDialogKey] = {
                scope,
                fieldName,
              };

              const parentDialogKey = resolveDialogKeyFromScope(scope);
              const activeTableKey = parentDialogKey && typeof this.getActiveDialogTableKey === 'function'
                ? this.getActiveDialogTableKey(parentDialogKey)
                : null;

              this.openDialog(resolvedDialogKey, null, activeTableKey || null);

              return true;
            },
            [names.applyPickerDialogSelection](dialogKey){
              const resolvedDialogKey = String(dialogKey || '').trim();
              if (resolvedDialogKey === '') {
                ElementPlus.ElMessage.error('选择器弹窗标识无效');
                return false;
              }

              const binding = ensurePickerBindingStore(this)[resolvedDialogKey] || null;
              if (!binding?.scope || !binding?.fieldName) {
                ElementPlus.ElMessage.error('未找到选择器回填目标');
                return false;
              }

              const fieldCfg = resolvePickerFieldConfig(this, binding.scope, binding.fieldName);
              if (!fieldCfg) {
                ElementPlus.ElMessage.error('选择器字段配置不存在');
                return false;
              }

              const dialogIframeRef = this.getDialogBodyRefs?.(resolvedDialogKey)?.iframe || null;
              const iframeWindow = dialogIframeRef?.contentWindow || null;
              if (!iframeWindow) {
                ElementPlus.ElMessage.error('选择页面尚未加载完成');
                return false;
              }

              const selectionPath = String(fieldCfg.selectionPath || '__scV2Selection').trim() || '__scV2Selection';
              let rawSelection = getByPath(iframeWindow, selectionPath);
              if (rawSelection === undefined && selectionPath === '__scV2Selection') {
                rawSelection = getByPath(iframeWindow, 'selection');
              }
              const items = normalizePickerItems(rawSelection, fieldCfg);
              if (items.length <= 0) {
                ElementPlus.ElMessage.error('请选择数据');
                return false;
              }

              setByPath(this[names.getPickerState](binding.scope), binding.fieldName, items);
              syncPickerModelValue(
                this[names.getFormModel](binding.scope),
                binding.fieldName,
                fieldCfg,
                items
              );

              delete ensurePickerBindingStore(this)[resolvedDialogKey];
              if (typeof this.closeDialog === 'function') {
                this.closeDialog(resolvedDialogKey);
              }

              return true;
            },
            [names.removePickerItem](scope, fieldName, value){
              const fieldCfg = resolvePickerFieldConfig(this, scope, fieldName);
              if (!fieldCfg) {
                return [];
              }

              const items = normalizePickerItems(
                getByPath(this[names.getPickerState](scope), fieldName) || [],
                fieldCfg
              ).filter((item) => !isSameValue(item?.__pickerValue, value));

              setByPath(this[names.getPickerState](scope), fieldName, items);
              syncPickerModelValue(this[names.getFormModel](scope), fieldName, fieldCfg, items);

              return items;
            },
            [names.clearPickerField](scope, fieldName){
              const fieldCfg = resolvePickerFieldConfig(this, scope, fieldName);
              if (!fieldCfg) {
                return [];
              }

              setByPath(this[names.getPickerState](scope), fieldName, []);
              syncPickerModelValue(this[names.getFormModel](scope), fieldName, fieldCfg, []);

              return [];
            },
            [names.resolvePickerItemDisplay](scope, fieldName, item){
              const fieldCfg = resolvePickerFieldConfig(this, scope, fieldName) || {};
              const normalizedItem = normalizePickerItems([item], fieldCfg)[0] || null;
              if (!normalizedItem) {
                return '';
              }

              return normalizedItem.__pickerDisplay
                || resolvePickerDisplayTemplate(
                  fieldCfg.displayTemplate || '@label',
                  normalizedItem,
                  normalizedItem.__pickerLabel,
                  normalizedItem.__pickerValue
                );
            },
            [names.nextRemoteRequestToken](scope, fieldName){
              this[tokenStoreKey] ??= {};
              const key = scope + ':' + fieldName;
              const token = (this[tokenStoreKey][key] || 0) + 1;
              this[tokenStoreKey][key] = token;

              return token;
            },
            [names.isLatestRemoteRequestToken](scope, fieldName, token){
              const key = scope + ':' + fieldName;

              return (this[tokenStoreKey]?.[key] || 0) === token;
            },
            [names.setDependencyResetSuspended](scope, suspended = true){
              if (!dependencyLockStoreKey) {
                return;
              }

              this[dependencyLockStoreKey] ??= {};
              this[dependencyLockStoreKey][scope] = suspended;
            },
            [names.isDependencyResetSuspended](scope){
              if (!dependencyLockStoreKey) {
                return false;
              }

              return this[dependencyLockStoreKey]?.[scope] === true;
            },
            [names.withDependencyResetSuspended](scope, callback){
              if (!dependencyLockStoreKey) {
                callback();
                return;
              }

              this[names.setDependencyResetSuspended](scope, true);

              try {
                callback();
              } finally {
                Vue.nextTick(() => {
                  this[names.setDependencyResetSuspended](scope, false);
                });
              }
            },
            [names.resetRemoteFieldState](scope, fieldName, clearValue = false){
              this[names.nextRemoteRequestToken](scope, fieldName);
              setByPath(this[names.getOptionState](scope), fieldName, []);
              setByPath(this[names.getOptionLoadingState](scope), fieldName, false);
              setByPath(this[names.getOptionLoadedState](scope), fieldName, false);

              if (!clearValue) {
                return;
              }

              const model = this[names.getFormModel](scope);
              const currentValue = getByPath(model, fieldName);
              setByPath(model, fieldName, Array.isArray(currentValue) ? [] : '');
            },
            [names.registerFormDependencies](scope){
              const configMap = getRemoteOptionsMap(scope, this) || {};
              const fieldPaths = getRemoteOptionPaths(scope, this) || [];

              fieldPaths.forEach((fieldName) => {
                const fieldCfg = getByPath(configMap, fieldName) || {};
                const dependencies = normalizeDependencies(fieldCfg);
                if (dependencies.length === 0) {
                  return;
                }

                this.$watch(
                  () => JSON.stringify(dependencies.map((path) => getByPath(this[names.getFormModel](scope), path))),
                  () => {
                    const shouldClear = fieldCfg.clearOnChange !== false && !this[names.isDependencyResetSuspended](scope);
                    this[names.reloadDependentFieldOptions](scope, fieldName, shouldClear);
                  }
                );
              });

              const registerArrayGroupDependencyWatches = (groupConfigs = []) => {
                (Array.isArray(groupConfigs) ? groupConfigs : []).forEach((groupCfg) => {
                  const rowRemoteOptionPaths = Array.isArray(groupCfg?.rowRemoteOptionPaths) ? groupCfg.rowRemoteOptionPaths : [];

                  rowRemoteOptionPaths.forEach((rowFieldPath) => {
                    const fieldCfg = getByPath(groupCfg?.rowRemoteOptions || {}, rowFieldPath) || {};
                    const dependencies = normalizeDependencies(fieldCfg);
                    if (dependencies.length === 0) {
                      return;
                    }

                    this.$watch(
                      () => JSON.stringify(
                        collectConcreteFieldPathsByGroupConfig(this, scope, groupCfg, rowFieldPath)
                          .map((fieldName) => {
                            const context = resolveArrayGroupFieldContext(this, scope, fieldName);
                            if (!context) {
                              return [];
                            }

                            return contextualizeArrayRowDependencies(
                              dependencies,
                              context.arrayPath,
                              context.rowIndex
                            ).map((path) => getByPath(this[names.getFormModel](scope), path));
                          })
                      ),
                      () => {
                        const shouldClear = fieldCfg.clearOnChange !== false && !this[names.isDependencyResetSuspended](scope);
                        collectConcreteFieldPathsByGroupConfig(this, scope, groupCfg, rowFieldPath)
                          .forEach((fieldName) => {
                            this[names.reloadDependentFieldOptions](scope, fieldName, shouldClear);
                          });
                      }
                    );
                  });

                  registerArrayGroupDependencyWatches(groupCfg?.rowArrayGroups || []);
                });
              };

              registerArrayGroupDependencyWatches(getArrayGroups(scope, this) || []);
            },
            [names.reloadDependentFieldOptions](scope, fieldName, clearValue = true){
              this[names.resetRemoteFieldState](scope, fieldName, clearValue);

              return this[names.loadFormFieldOptions](scope, fieldName, true);
            },
            [names.initializeFormOptions](scope, force = false){
              (getRemoteOptionPaths(scope, this) || []).forEach((fieldName) => {
                this[names.loadFormFieldOptions](scope, fieldName, force);
              });

              (getArrayGroups(scope, this) || []).forEach((groupCfg) => {
                const arrayPath = typeof groupCfg?.path === 'string' ? groupCfg.path : '';
                if (arrayPath === '') {
                  return;
                }

                this[names.initializeArrayGroupRemoteOptions](scope, arrayPath, force);
              });
            },
            [names.loadFormFieldOptions](scope, fieldName, force = false){
              const fieldCfg = resolveRemoteFieldConfig(this, scope, fieldName);
              if (!fieldCfg?.url) {
                return Promise.resolve([]);
              }

              const model = this[names.getFormModel](scope);
              if (!hasReadyDependencies(fieldCfg, model)) {
                this[names.resetRemoteFieldState](scope, fieldName);
                return Promise.resolve([]);
              }

              const loadingState = this[names.getOptionLoadingState](scope);
              const loadedState = this[names.getOptionLoadedState](scope);
              if (getByPath(loadingState, fieldName)) {
                return Promise.resolve(getByPath(this[names.getOptionState](scope), fieldName) || []);
              }
              if (!force && getByPath(loadedState, fieldName)) {
                return Promise.resolve(getByPath(this[names.getOptionState](scope), fieldName) || []);
              }

              const requestToken = this[names.nextRemoteRequestToken](scope, fieldName);
              setByPath(loadingState, fieldName, true);

              return makeRequest({
                method: fieldCfg.method || 'get',
                url: fieldCfg.url,
                query: Object.assign({}, resolveDynamicParams(fieldCfg.params || {}, model))
              })
                .then((response) => {
                  if (!this[names.isLatestRemoteRequestToken](scope, fieldName, requestToken)) {
                    return getByPath(this[names.getOptionState](scope), fieldName) || [];
                  }

                  const payload = ensureSuccess(extractPayload(response), '选项加载失败');
                  const options = pickRows(payload).map((item, index) => normalizeOption(item, fieldCfg, index));
                  setByPath(this[names.getOptionState](scope), fieldName, options);
                  setByPath(loadedState, fieldName, true);

                  return emitFormEvent(this, scope, 'optionsLoaded', {
                    fieldName,
                    fieldConfig: fieldCfg,
                    response,
                    payload,
                    options
                  }).then((results) => {
                    if (isEventCanceled(results)) {
                      return [];
                    }

                    return options;
                  });
                })
                .catch((error) => {
                  if (!this[names.isLatestRemoteRequestToken](scope, fieldName, requestToken)) {
                    return [];
                  }

                  setByPath(loadedState, fieldName, false);
                  const message = error?.message || resolveMessage(error?.response?.data, '选项加载失败');
                  ElementPlus.ElMessage.error(message);
                  return emitFormEvent(this, scope, 'optionsLoadFail', {
                    fieldName,
                    fieldConfig: fieldCfg,
                    error
                  }).then(() => []);
                })
                .finally(() => {
                  if (this[names.isLatestRemoteRequestToken](scope, fieldName, requestToken)) {
                    setByPath(loadingState, fieldName, false);
                  }
                });
            },
            [names.initializeUploadFiles](scope){
              rebuildUploadFileState(this, scope);
            },
            [names.handleUploadBefore](scope, fieldName, uploadRawFile){
              openUploadNotice(this, uploadRawFile);
              return true;
            },
            [names.handleUploadSuccess](scope, fieldName, response, uploadFile, uploadFiles){
              const fieldCfg = resolveScopedFieldConfig(
                this,
                scope,
                fieldName,
                getUploadsMap(scope, this) || {},
                'rowUploads'
              ) || {};
              const currentUploadFiles = getByPath(this[names.getUploadFileState](scope), fieldName) || [];
              const sourceUploadFiles = (Array.isArray(uploadFiles) && uploadFiles.length > 0
                ? uploadFiles
                : currentUploadFiles.concat(uploadFile ? [uploadFile] : [])
              ).filter((file, index, files) => {
                if (!file || file.uid === undefined || file.uid === null || file.uid === '') {
                  return true;
                }

                return files.findIndex((candidate) => candidate?.uid === file.uid) === index;
              });
              closeUploadNotice(this, uploadFile);

              try {
                const payload = ensureSuccess(response, '上传失败');
                const storedValue = resolveUploadValue(payload, fieldCfg);
                if (isBlank(storedValue)) {
                  throw new Error(resolveMessage(payload, '上传返回数据无效'));
                }

                const nextFiles = normalizeUploadFiles(
                  sourceUploadFiles.map((file) => {
                    if (file.uid === uploadFile.uid) {
                      return Object.assign({}, file, {
                        name: uploadFile.name || file.name,
                        status: uploadFile.status || 'success',
                        url: typeof storedValue === 'string' ? storedValue : (file.url || ''),
                        response: response,
                        responseValue: storedValue
                      });
                    }

                    return file;
                  }),
                  fieldCfg
                );

                setByPath(this[names.getUploadFileState](scope), fieldName, nextFiles);
                syncUploadModelValue(this[names.getFormModel](scope), fieldName, fieldCfg, nextFiles);
                ElementPlus.ElMessage.success((uploadFile?.name || '文件') + ' 上传成功');
                emitFormEvent(this, scope, 'uploadSuccess', {
                  fieldName,
                  fieldConfig: fieldCfg,
                  response,
                  payload,
                  uploadFile,
                  uploadFiles: nextFiles
                });
              } catch (error) {
                const nextFiles = normalizeUploadFiles(
                  (uploadFiles || []).filter((file) => file.uid !== uploadFile.uid),
                  fieldCfg
                );
                setByPath(this[names.getUploadFileState](scope), fieldName, nextFiles);
                syncUploadModelValue(this[names.getFormModel](scope), fieldName, fieldCfg, nextFiles);
                ElementPlus.ElMessage.error((uploadFile?.name || '文件') + ' ' + (error?.message || '上传失败'));
                emitFormEvent(this, scope, 'uploadFail', {
                  fieldName,
                  fieldConfig: fieldCfg,
                  error,
                  response,
                  uploadFile,
                  uploadFiles: nextFiles
                });
              }
            },
            [names.handleUploadError](scope, fieldName, error, uploadFile, uploadFiles){
              const fieldCfg = resolveScopedFieldConfig(
                this,
                scope,
                fieldName,
                getUploadsMap(scope, this) || {},
                'rowUploads'
              ) || {};
              closeUploadNotice(this, uploadFile);
              const nextFiles = normalizeUploadFiles(
                (uploadFiles || []).filter((file) => file.uid !== uploadFile.uid),
                fieldCfg
              );
              setByPath(this[names.getUploadFileState](scope), fieldName, nextFiles);
              syncUploadModelValue(this[names.getFormModel](scope), fieldName, fieldCfg, nextFiles);
              const message = resolveMessage(error, '上传失败');
              ElementPlus.ElMessage.error((uploadFile?.name || '文件') + ' ' + message);
              emitFormEvent(this, scope, 'uploadFail', {
                fieldName,
                fieldConfig: fieldCfg,
                error,
                uploadFile,
                uploadFiles: nextFiles
              });
            },
            [names.handleUploadRemove](scope, fieldName, uploadFile, uploadFiles){
              const fieldCfg = resolveScopedFieldConfig(
                this,
                scope,
                fieldName,
                getUploadsMap(scope, this) || {},
                'rowUploads'
              ) || {};
              closeUploadNotice(this, uploadFile);
              const nextFiles = normalizeUploadFiles(uploadFiles || [], fieldCfg);
              setByPath(this[names.getUploadFileState](scope), fieldName, nextFiles);
              syncUploadModelValue(this[names.getFormModel](scope), fieldName, fieldCfg, nextFiles);
            },
            [names.handleUploadExceed](scope, fieldName, files, uploadFiles){
              const fieldCfg = resolveScopedFieldConfig(
                this,
                scope,
                fieldName,
                getUploadsMap(scope, this) || {},
                'rowUploads'
              ) || {};
              const limit = fieldCfg.limit || 1;
              ElementPlus.ElMessage.error('最多只能上传 ' + limit + ' 个文件');
            },
            [names.handleUploadPreview](uploadFile){
              const url = uploadFile?.url
                || uploadFile?.responseValue
                || resolveUploadValue(uploadFile?.response || uploadFile, {});

              if (isBlank(url)) {
                return;
              }

              window.open(String(url), '_blank');
            }
          };
        };
