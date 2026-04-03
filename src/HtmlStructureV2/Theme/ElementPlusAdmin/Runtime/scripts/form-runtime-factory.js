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
          getRemoteOptionsMap,
          getSelectOptionsMap,
          getLinkagesMap,
          getUploadsMap
        }) => {
          const {
            extractPayload,
            ensureSuccess,
            getByPath,
            hasReadyDependencies,
            isBlank,
            isSameValue,
            makeRequest,
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
            getOptionState: 'getManagedOptionState',
            getOptionLoadingState: 'getManagedOptionLoadingState',
            getOptionLoadedState: 'getManagedOptionLoadedState',
            getUploadFileState: 'getManagedUploadFileState',
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
                return Promise.resolve(true);
              }

              try {
                const result = formRef.validate();
                if (result && typeof result.then === 'function') {
                  return result.then(() => true).catch(() => false);
                }
              } catch (error) {
                return Promise.resolve(false);
              }

              return Promise.resolve(true);
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
            [names.getFieldOptions](scope, fieldName){
              const remoteConfig = (getRemoteOptionsMap(scope, this) || {})[fieldName];
              if (remoteConfig) {
                return this[names.getOptionState](scope)[fieldName] || [];
              }

              const optionsMap = getSelectOptionsMap(scope, this) || {};
              return (optionsMap[fieldName] || []).map((item, index) => normalizeOption(item, {}, index));
            },
            [names.getLinkageConfig](scope, fieldName){
              return (getLinkagesMap(scope, this) || {})[fieldName] || null;
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
              this[names.getOptionState](scope)[fieldName] = [];
              this[names.getOptionLoadingState](scope)[fieldName] = false;
              this[names.getOptionLoadedState](scope)[fieldName] = false;

              if (!clearValue) {
                return;
              }

              const model = this[names.getFormModel](scope);
              const currentValue = getByPath(model, fieldName);
              setByPath(model, fieldName, Array.isArray(currentValue) ? [] : '');
            },
            [names.registerFormDependencies](scope){
              const configMap = getRemoteOptionsMap(scope, this) || {};

              Object.keys(configMap).forEach((fieldName) => {
                const fieldCfg = configMap[fieldName] || {};
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
            },
            [names.reloadDependentFieldOptions](scope, fieldName, clearValue = true){
              this[names.resetRemoteFieldState](scope, fieldName, clearValue);

              return this[names.loadFormFieldOptions](scope, fieldName, true);
            },
            [names.initializeFormOptions](scope, force = false){
              const configMap = getRemoteOptionsMap(scope, this) || {};

              Object.keys(configMap).forEach((fieldName) => {
                this[names.loadFormFieldOptions](scope, fieldName, force);
              });
            },
            [names.loadFormFieldOptions](scope, fieldName, force = false){
              const fieldCfg = (getRemoteOptionsMap(scope, this) || {})[fieldName];
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
              if (loadingState[fieldName]) {
                return Promise.resolve(this[names.getOptionState](scope)[fieldName] || []);
              }
              if (!force && loadedState[fieldName]) {
                return Promise.resolve(this[names.getOptionState](scope)[fieldName] || []);
              }

              const requestToken = this[names.nextRemoteRequestToken](scope, fieldName);
              loadingState[fieldName] = true;

              return makeRequest({
                method: fieldCfg.method || 'get',
                url: fieldCfg.url,
                query: Object.assign({}, resolveDynamicParams(fieldCfg.params || {}, model))
              })
                .then((response) => {
                  if (!this[names.isLatestRemoteRequestToken](scope, fieldName, requestToken)) {
                    return this[names.getOptionState](scope)[fieldName] || [];
                  }

                  const payload = ensureSuccess(extractPayload(response), '选项加载失败');
                  const options = pickRows(payload).map((item, index) => normalizeOption(item, fieldCfg, index));
                  this[names.getOptionState](scope)[fieldName] = options;
                  loadedState[fieldName] = true;

                  return options;
                })
                .catch((error) => {
                  if (!this[names.isLatestRemoteRequestToken](scope, fieldName, requestToken)) {
                    return [];
                  }

                  loadedState[fieldName] = false;
                  const message = error?.message || resolveMessage(error?.response?.data, '选项加载失败');
                  ElementPlus.ElMessage.error(message);
                  return [];
                })
                .finally(() => {
                  if (this[names.isLatestRemoteRequestToken](scope, fieldName, requestToken)) {
                    loadingState[fieldName] = false;
                  }
                });
            },
            [names.initializeUploadFiles](scope){
              const uploadConfigs = getUploadsMap(scope, this) || {};
              const model = this[names.getFormModel](scope);
              const state = this[names.getUploadFileState](scope);

              Object.keys(uploadConfigs).forEach((fieldName) => {
                state[fieldName] = normalizeUploadFiles(getByPath(model, fieldName), uploadConfigs[fieldName] || {});
              });
            },
            [names.handleUploadSuccess](scope, fieldName, response, uploadFile, uploadFiles){
              const fieldCfg = (getUploadsMap(scope, this) || {})[fieldName] || {};

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

                this[names.getUploadFileState](scope)[fieldName] = nextFiles;
                syncUploadModelValue(this[names.getFormModel](scope), fieldName, fieldCfg, nextFiles);
                ElementPlus.ElMessage.success(resolveMessage(payload, '上传成功'));
              } catch (error) {
                const nextFiles = normalizeUploadFiles(
                  (uploadFiles || []).filter((file) => file.uid !== uploadFile.uid),
                  fieldCfg
                );
                this[names.getUploadFileState](scope)[fieldName] = nextFiles;
                syncUploadModelValue(this[names.getFormModel](scope), fieldName, fieldCfg, nextFiles);
                ElementPlus.ElMessage.error(error?.message || '上传失败');
              }
            },
            [names.handleUploadRemove](scope, fieldName, uploadFile, uploadFiles){
              const fieldCfg = (getUploadsMap(scope, this) || {})[fieldName] || {};
              const nextFiles = normalizeUploadFiles(uploadFiles || [], fieldCfg);
              this[names.getUploadFileState](scope)[fieldName] = nextFiles;
              syncUploadModelValue(this[names.getFormModel](scope), fieldName, fieldCfg, nextFiles);
            },
            [names.handleUploadExceed](scope, fieldName, files, uploadFiles){
              const fieldCfg = (getUploadsMap(scope, this) || {})[fieldName] || {};
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
