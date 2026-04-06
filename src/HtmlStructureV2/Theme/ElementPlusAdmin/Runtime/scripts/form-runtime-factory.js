        globalThis.__SC_V2_CREATE_MANAGED_FORM_METHODS__ = ({
          methodNames = {},
          tokenStoreKey = '__managedRemoteRequestTokens',
          dependencyLockStoreKey = null,
          getRefName,
          getFormModel,
          getOptionState,
          getOptionLoadingState,
          getOptionLoadedState,
          getUploadFileState,
          getFormEvents,
          getArrayGroups,
          getRemoteOptionsMap,
          getRemoteOptionPaths,
          getSelectOptionsMap,
          getLinkagesMap,
          getUploadsMap,
          getUploadPaths
        }) => {
          const {
            clone,
            emitConfiguredEvent,
            ensureFormArrayGroupState,
            extractPayload,
            ensureSuccess,
            getByPath,
            hasReadyDependencies,
            isEventCanceled,
            isBlank,
            isSameValue,
            makeRequest,
            normalizeArrayGroupRow,
            normalizeDependencies,
            normalizeOption,
            normalizeUploadFiles,
            pickRows,
            resolveDynamicParams,
            resolveLinkageTemplate,
            resolveMessage,
            resolveUploadValue,
            setByPath,
            syncUploadModelValue
          } = globalThis.__SC_V2_RUNTIME_HELPERS__;

          const names = Object.assign({
            getFormRef: 'getManagedFormRef',
            validateForm: 'validateManagedForm',
            clearFormValidate: 'clearManagedFormValidate',
            getFormModel: 'getManagedFormModel',
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
            moveFormArrayRow: 'moveFormArrayRow',
            getOptionState: 'getManagedOptionState',
            getOptionLoadingState: 'getManagedOptionLoadingState',
            getOptionLoadedState: 'getManagedOptionLoadedState',
            getUploadFileState: 'getManagedUploadFileState',
            setUploadFileList: 'setManagedUploadFileList',
            getFieldOptions: 'getManagedFieldOptions',
            getLinkageConfig: 'getManagedLinkageConfig',
            clearLinkageTargets: 'clearManagedLinkageTargets',
            applyFormLinkage: 'applyManagedFormLinkage',
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
            handleUploadSuccess: 'handleManagedUploadSuccess',
            handleUploadRemove: 'handleManagedUploadRemove',
            handleUploadExceed: 'handleManagedUploadExceed',
            handleUploadPreview: 'handleManagedUploadPreview'
          }, methodNames);
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
          const syncFormStructureValidation = (vm, scope) => {
            Vue.nextTick(() => {
              if (typeof vm[names.clearFormValidate] === 'function') {
                vm[names.clearFormValidate](scope);
              }
            });
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
              setByPath(
                nextState,
                fieldName,
                normalizeUploadFiles(getByPath(model, fieldName), getByPath(uploadConfigs, fieldName) || {})
              );
            });

            visitConcreteArrayGroupInstances(vm, scope, (groupCfg, concreteArrayPath, rows) => {
              const rowUploadPaths = Array.isArray(groupCfg?.rowUploadPaths) ? groupCfg.rowUploadPaths : [];
              const rowUploads = groupCfg?.rowUploads || {};

              rows.forEach((_, rowIndex) => {
                rowUploadPaths.forEach((rowFieldPath) => {
                  const fieldName = contextualizeArrayRowFieldPath(concreteArrayPath, rowIndex, rowFieldPath);
                  setByPath(
                    nextState,
                    fieldName,
                    normalizeUploadFiles(getByPath(model, fieldName), getByPath(rowUploads, rowFieldPath) || {})
                  );
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
              const rowRemoteOptionPaths = Array.isArray(groupCfg?.rowRemoteOptionPaths) ? groupCfg.rowRemoteOptionPaths : [];
              if (rowRemoteOptionPaths.length === 0) {
                return;
              }

              rows.forEach((_, rowIndex) => {
                rowRemoteOptionPaths.forEach((rowFieldPath) => {
                  const fieldName = contextualizeArrayRowFieldPath(concreteArrayPath, rowIndex, rowFieldPath);
                  const fieldCfg = buildArrayRowRemoteFieldConfig(groupCfg, rowFieldPath, concreteArrayPath, rowIndex) || {};
                  const initialOptions = Array.isArray(fieldCfg.initialOptions)
                    ? fieldCfg.initialOptions.map((item, index) => normalizeOption(item, fieldCfg, index))
                    : [];

                  setByPath(optionState, fieldName, initialOptions);
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
            [names.moveFormArrayRow](scope, arrayPath, rowIndex, direction = 'up'){
              const groupCfg = this[names.getFormArrayGroupConfig](scope, arrayPath) || {};
              const rows = this[names.getFormArrayRows](scope, arrayPath);
              if (!Array.isArray(rows) || rows.length <= 1) {
                return rows;
              }

              const index = Number(rowIndex);
              if (!Number.isInteger(index) || index < 0 || index >= rows.length) {
                return rows;
              }

              const targetIndex = direction === 'down' ? index + 1 : index - 1;
              if (targetIndex < 0 || targetIndex >= rows.length) {
                return rows;
              }

              const [row] = rows.splice(index, 1);
              rows.splice(targetIndex, 0, row);
              rebuildUploadFileState(this, scope);
              this[names.syncFormArrayGroupRemoteState](scope, arrayPath);
              this[names.initializeArrayGroupRemoteOptions](scope, arrayPath);
              syncFormStructureValidation(this, scope);
              emitFormEvent(this, scope, 'arrayRowMove', {
                arrayPath,
                groupConfig: groupCfg,
                row,
                fromIndex: index,
                toIndex: targetIndex,
                direction,
                rows
              });
              return rows;
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
            [names.getFieldOptions](scope, fieldName){
              const remoteConfig = resolveRemoteFieldConfig(this, scope, fieldName);
              if (remoteConfig) {
                return getByPath(this[names.getOptionState](scope), fieldName) || [];
              }

              const options = resolveScopedFieldConfig(
                this,
                scope,
                fieldName,
                getSelectOptionsMap(scope, this) || {},
                'rowSelectOptions'
              ) || [];

              return options.map((item, index) => normalizeOption(item, {}, index));
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
            [names.handleUploadSuccess](scope, fieldName, response, uploadFile, uploadFiles){
              const fieldCfg = resolveScopedFieldConfig(
                this,
                scope,
                fieldName,
                getUploadsMap(scope, this) || {},
                'rowUploads'
              ) || {};

              try {
                const payload = ensureSuccess(response, '上传失败');
                const storedValue = resolveUploadValue(payload, fieldCfg);
                if (isBlank(storedValue)) {
                  throw new Error(resolveMessage(payload, '上传返回数据无效'));
                }

                const nextFiles = normalizeUploadFiles(
                  (uploadFiles || []).map((file) => {
                    if (file.uid === uploadFile.uid) {
                      return Object.assign({}, file, {
                        url: typeof storedValue === 'string' ? storedValue : (file.url || ''),
                        responseValue: storedValue
                      });
                    }

                    return file;
                  }),
                  fieldCfg
                );

                setByPath(this[names.getUploadFileState](scope), fieldName, nextFiles);
                syncUploadModelValue(this[names.getFormModel](scope), fieldName, fieldCfg, nextFiles);
                ElementPlus.ElMessage.success(resolveMessage(payload, '上传成功'));
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
                ElementPlus.ElMessage.error(error?.message || '上传失败');
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
            [names.handleUploadRemove](scope, fieldName, uploadFile, uploadFiles){
              const fieldCfg = resolveScopedFieldConfig(
                this,
                scope,
                fieldName,
                getUploadsMap(scope, this) || {},
                'rowUploads'
              ) || {};
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
