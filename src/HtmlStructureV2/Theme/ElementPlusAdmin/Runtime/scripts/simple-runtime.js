        globalThis.__SC_V2_BOOT_SIMPLE__ = (state, cfg) => {
          const {
            buildFormsContext,
            buildManagedDialogRuntimeState,
            buildTableStates,
            clone,
            extractLoadData,
            extractPayload,
            ensureSuccess,
            getConfigState,
            initializeConfiguredForms,
            isObject,
            makeRequest,
            postDialogHostMessage,
            pickRows,
            readPageLocation,
            readPageQuery,
            registerElementPlusIcons,
            registerScV2Components,
            resolveContextValue,
            resolveMessage,
            resolvePageMode,
            setConfigState,
          } = globalThis.__SC_V2_RUNTIME_HELPERS__;
          const forms = cfg.forms || {};
          const dialogFormScopePrefix = 'dialog:';
          const defaultModeQueryKey = 'id';
          const initialPageQuery = readPageQuery();
          const knownFormScopes = () => Object.keys(forms || {});
          const pageFormScopes = () => knownFormScopes().filter((formScope) => !String(formScope || '').startsWith(dialogFormScopePrefix));
          const normalizeFormScope = (scope) => {
            const normalized = typeof scope === 'string' ? scope.trim() : '';
            return normalized !== '' ? normalized : null;
          };
          const normalizeModeQueryKey = (queryKey) => {
            const normalized = typeof queryKey === 'string' ? queryKey.trim() : '';
            return normalized !== '' ? normalized : defaultModeQueryKey;
          };
          const getFormConfig = (scope) => forms?.[scope] || {};
          const resolvePublicFormScope = (scope = null) => {
            const explicitScope = normalizeFormScope(scope);
            if (explicitScope) {
              if (forms?.[explicitScope]) {
                return explicitScope;
              }

              throw new Error(`Unknown public form scope [${explicitScope}] requested by "__SC_V2_PAGE__.submit()".`);
            }

            const pageScopes = knownFormScopes().filter((formScope) => !String(formScope || '').startsWith(dialogFormScopePrefix));
            if (pageScopes.length === 1) {
              return pageScopes[0];
            }

            const allScopes = knownFormScopes();
            if (allScopes.length === 1) {
              return allScopes[0];
            }

            throw new Error(
              'Current V2 page cannot resolve a public form scope automatically; please call "__SC_V2_PAGE__.submit(\'...\')" / "__SC_V2_PAGE__.cloneFormModel(\'...\')" / "__SC_V2_PAGE__.setFormModel(\'...\', {...})" / "__SC_V2_PAGE__.initializeFormModel(\'...\', {...})" / "__SC_V2_PAGE__.resetForm(\'...\')" with an explicit form key.'
            );
          };
          const createPublicPageApi = (vm) => {
            const resolveFormModelSetArgs = (arg1 = null, arg2 = undefined) => {
              if (typeof arg1 === 'string') {
                return { scope: arg1, values: arg2 };
              }
              if (typeof arg2 === 'string') {
                return { scope: arg2, values: arg1 };
              }

              return { scope: null, values: arg1 };
            };
            const getPageQuery = () => clone(vm.getPageQuery ? vm.getPageQuery() : initialPageQuery);
            const getFormModel = (scope = null) => {
              const resolvedScope = resolvePublicFormScope(scope);
              return vm.getFormModel(resolvedScope) || {};
            };
            const cloneFormModel = (scope = null) => clone(getFormModel(scope));
            const setFormModel = (arg1 = null, arg2 = undefined) => {
              const { scope, values } = resolveFormModelSetArgs(arg1, arg2);
              const resolvedScope = resolvePublicFormScope(scope);
              if (typeof vm.setFormModel === 'function') {
                return vm.setFormModel(resolvedScope, values);
              }
              if (typeof vm.setSimpleFormModel === 'function') {
                return vm.setSimpleFormModel(resolvedScope, values);
              }

              throw new Error('Current runtime does not expose public setFormModel() support.');
            };
            const initializeFormModel = (arg1 = null, arg2 = undefined) => {
              const { scope, values } = resolveFormModelSetArgs(arg1, arg2);
              const resolvedScope = resolvePublicFormScope(scope);
              if (typeof vm.initializeFormModel === 'function') {
                return vm.initializeFormModel(resolvedScope, values);
              }
              if (typeof vm.initializeSimpleFormModel === 'function') {
                return vm.initializeSimpleFormModel(resolvedScope, values);
              }

              throw new Error('Current runtime does not expose public initializeFormModel() support.');
            };
            const resetForm = (scope = null) => {
              const resolvedScope = resolvePublicFormScope(scope);
              if (typeof vm.resetForm === 'function') {
                return vm.resetForm(resolvedScope);
              }
              if (typeof vm.resetSimpleForm === 'function') {
                return vm.resetSimpleForm(resolvedScope);
              }

              throw new Error('Current runtime does not expose public resetForm() support.');
            };
            const validateForm = (scope = null) => {
              const resolvedScope = resolvePublicFormScope(scope);
              return Promise.resolve(vm.validateForm(resolvedScope)).then((valid) => valid !== false);
            };
            const submit = (scope = null) => validateForm(scope).then((valid) => {
              if (valid === false) {
                return null;
              }

              return cloneFormModel(scope);
            });

            return {
              vm,
              get query(){
                return getPageQuery();
              },
              get mode(){
                try {
                  return vm.resolveFormMode();
                } catch (error) {
                  return vm.resolvePageMode();
                }
              },
              getPageQuery,
              resolvePageMode: (queryKey = null) => vm.resolvePageMode(queryKey),
              resolveFormScope: (scope = null) => resolvePublicFormScope(scope),
              resolveFormMode: (scope = null) => vm.resolveFormMode(resolvePublicFormScope(scope)),
              validateForm,
              getFormModel,
              cloneFormModel,
              setFormModel,
              initializeFormModel,
              resetForm,
              loadFormData: (scope = null, force = false) => vm.loadFormData(resolvePublicFormScope(scope), force),
              submit,
              notifyDialogHost: (...args) => vm.notifyDialogHost(...args),
              closeHostDialog: (...args) => vm.closeHostDialog(...args),
              reloadHostTable: (...args) => vm.reloadHostTable(...args),
              openHostDialog: (...args) => vm.openHostDialog(...args),
              setHostDialogTitle: (...args) => vm.setHostDialogTitle(...args),
              setHostDialogFullscreen: (...args) => vm.setHostDialogFullscreen(...args),
              toggleHostDialogFullscreen: (...args) => vm.toggleHostDialogFullscreen(...args),
              refreshHostDialogIframe: (...args) => vm.refreshHostDialogIframe(...args),
            };
          };

          const createColumnDisplayMethods = globalThis.__SC_V2_CREATE_COLUMN_DISPLAY_METHODS__;
          const createRequestActionMethods = globalThis.__SC_V2_CREATE_REQUEST_ACTION_METHODS__;
          const createSimpleFormMethods = globalThis.__SC_V2_CREATE_SIMPLE_FORM_METHODS__;
          const createSimpleDialogMethods = globalThis.__SC_V2_CREATE_SIMPLE_DIALOG_METHODS__;
          const createSimpleTableMethods = globalThis.__SC_V2_CREATE_SIMPLE_TABLE_METHODS__;
          const buildCurrentPageContext = (vm) => {
            const location = readPageLocation();
            const query = typeof vm?.getPageQuery === 'function'
              ? vm.getPageQuery()
              : clone(initialPageQuery);
            const mode = typeof vm?.resolvePageMode === 'function'
              ? vm.resolvePageMode()
              : resolvePageMode(query);

            return Object.assign({}, location, {
              query,
              mode,
              formScope: null,
            });
          };
          const app = Vue.createApp({
            data(){
              return Object.assign({
                actionLoading: {},
                pageQuery: clone(initialPageQuery),
                tableConfigs: cfg.tables || {},
                tableStates: buildTableStates(cfg.tables),
                ...buildManagedDialogRuntimeState(cfg.dialogs, forms),
              }, state || {});
            },
            created(){
              initializeConfiguredForms(forms, {
                initializeArrayGroups: (scope) => this.initializeFormArrayGroups(scope),
              });
              this.initializeSimpleFormInitialSnapshots();
            },
            mounted(){
              this.ensureDialogMessageBridge();
              initializeConfiguredForms(forms, {
                registerDependencies: (scope) => this.registerSimpleFormDependencies(scope),
                initializeOptions: (scope) => {
                  if (String(scope || '').startsWith(dialogFormScopePrefix)) {
                    this.initializeSimpleFormOptions(scope);
                  }
                },
                initializeUploads: (scope) => {
                  if (String(scope || '').startsWith(dialogFormScopePrefix)) {
                    this.initializeSimpleUploadFiles(scope);
                  }
                },
              });
              this.initializePageForms();
              this.initializeTables();
            },
            methods: Object.assign(
              {},
              createColumnDisplayMethods(),
              createRequestActionMethods({
                cfg,
                getBaseContext: (vm, actionConfig) => ({
                  forms: buildFormsContext(vm, forms),
                  dialogs: vm.dialogForms || {},
                  query: typeof vm.getPageQuery === 'function' ? vm.getPageQuery() : clone(initialPageQuery),
                  page: buildCurrentPageContext(vm),
                  selection: typeof vm.getTableSelection === 'function'
                    ? vm.getTableSelection(actionConfig?.tableKey || null)
                    : [],
                })
              }),
              createSimpleFormMethods({ cfg }),
              {
                getPageQuery(){
                  return clone(this.pageQuery || initialPageQuery);
                },
                resolvePageMode(queryKey = defaultModeQueryKey){
                  return resolvePageMode(
                    this.getPageQuery(),
                    normalizeModeQueryKey(queryKey)
                  );
                },
                resolveFormMode(scope = null){
                  const resolvedScope = resolvePublicFormScope(scope);
                  return this.resolvePageMode(getFormConfig(resolvedScope)?.modeQueryKey || defaultModeQueryKey);
                },
                ensureSimpleFormLoadTokenStore(){
                  if (!isObject(this.__simpleFormLoadTokens)) {
                    this.__simpleFormLoadTokens = {};
                  }

                  return this.__simpleFormLoadTokens;
                },
                nextSimpleFormLoadToken(scope){
                  const resolvedScope = resolvePublicFormScope(scope);
                  const store = this.ensureSimpleFormLoadTokenStore();
                  store[resolvedScope] = (store[resolvedScope] || 0) + 1;

                  return store[resolvedScope];
                },
                isLatestSimpleFormLoadToken(scope, token){
                  const resolvedScope = resolvePublicFormScope(scope);
                  return (this.ensureSimpleFormLoadTokenStore()[resolvedScope] || 0) === token;
                },
                ensureSimpleFormInitialStore(){
                  if (!isObject(this.__simpleFormInitials)) {
                    this.__simpleFormInitials = {};
                  }

                  return this.__simpleFormInitials;
                },
                buildSimpleFormInitialSnapshot(scope){
                  const resolvedScope = resolvePublicFormScope(scope);
                  const formCfg = getFormConfig(resolvedScope) || {};

                  return clone(formCfg.initialData || formCfg.defaults || {});
                },
                setSimpleFormInitialSnapshot(scope, values = undefined){
                  const resolvedScope = resolvePublicFormScope(scope);
                  const store = this.ensureSimpleFormInitialStore();
                  store[resolvedScope] = values === undefined
                    ? clone(this.getFormModel(resolvedScope) || this.buildSimpleFormInitialSnapshot(resolvedScope))
                    : clone(values || {});

                  return clone(store[resolvedScope]);
                },
                getSimpleFormInitialSnapshot(scope){
                  const resolvedScope = resolvePublicFormScope(scope);
                  const store = this.ensureSimpleFormInitialStore();
                  if (store[resolvedScope] === undefined) {
                    store[resolvedScope] = this.buildSimpleFormInitialSnapshot(resolvedScope);
                  }

                  return clone(store[resolvedScope] || {});
                },
                initializeSimpleFormInitialSnapshots(){
                  knownFormScopes().forEach((scope) => {
                    this.setSimpleFormInitialSnapshot(scope);
                  });

                  return this.ensureSimpleFormInitialStore();
                },
                buildPageFormLoadContext(scope, overrides = {}){
                  const resolvedScope = resolvePublicFormScope(scope);
                  const query = this.getPageQuery();
                  const mode = this.resolveFormMode(resolvedScope);
                  const form = this.getFormModel(resolvedScope) || {};
                  const context = Object.assign({
                    scope: resolvedScope,
                    formScope: resolvedScope,
                    form,
                    model: form,
                    mode,
                    query,
                    page: {
                      query,
                      mode,
                      formScope: resolvedScope,
                    },
                    forms: buildFormsContext(this, forms),
                    dialogs: this.dialogForms || {},
                    selection: [],
                    vm: this,
                    getPageQuery: () => this.getPageQuery(),
                    resolvePageMode: (queryKey = null) => this.resolvePageMode(
                      queryKey || getFormConfig(resolvedScope)?.modeQueryKey || defaultModeQueryKey
                    ),
                    resolveFormMode: (requestedScope = resolvedScope) => this.resolveFormMode(requestedScope),
                    loadFormData: (requestedScope = resolvedScope, force = false) => this.loadFormData(requestedScope, force),
                    resetForm: (requestedScope = resolvedScope) => this.resetForm(requestedScope),
                    reloadPage: () => window.location.reload(),
                    notifyDialogHost: (payload = {}) => this.notifyDialogHost(payload),
                    closeHostDialog: (...args) => this.closeHostDialog(...args),
                    reloadHostTable: (...args) => this.reloadHostTable(...args),
                    openHostDialog: (...args) => this.openHostDialog(...args),
                    setHostDialogTitle: (...args) => this.setHostDialogTitle(...args),
                    setHostDialogFullscreen: (...args) => this.setHostDialogFullscreen(...args),
                    toggleHostDialogFullscreen: (...args) => this.toggleHostDialogFullscreen(...args),
                    refreshHostDialogIframe: (...args) => this.refreshHostDialogIframe(...args),
                  }, overrides || {});

                  context.page = Object.assign({}, context.page || {}, {
                    query,
                    mode: context.mode ?? mode,
                    formScope: context.formScope ?? resolvedScope,
                  });

                  return context;
                },
                shouldLoadForm(scope, force = false){
                  const resolvedScope = resolvePublicFormScope(scope);
                  const loadCfg = isObject(getFormConfig(resolvedScope)?.load)
                    ? getFormConfig(resolvedScope).load
                    : {};
                  if (!loadCfg?.url) {
                    return false;
                  }

                  if (force === true) {
                    return true;
                  }

                  const when = String(loadCfg.when || 'edit').toLowerCase();
                  if (when === 'always') {
                    return true;
                  }
                  if (when === 'create') {
                    return this.resolveFormMode(resolvedScope) === 'create';
                  }

                  return this.resolveFormMode(resolvedScope) === 'edit';
                },
                resolveFormLoadRequest(scope){
                  const resolvedScope = resolvePublicFormScope(scope);
                  const formCfg = getFormConfig(resolvedScope) || {};
                  const loadCfg = isObject(formCfg.load) ? formCfg.load : {};
                  if (!loadCfg?.url) {
                    return null;
                  }

                  const context = this.buildPageFormLoadContext(resolvedScope);
                  const url = resolveContextValue(loadCfg.url, context);
                  if (typeof url !== 'string' || url === '') {
                    return null;
                  }

                  let query = loadCfg.payload || {};
                  if (loadCfg.useModeQueryPayload) {
                    const modeQueryKey = normalizeModeQueryKey(formCfg.modeQueryKey);
                    query = {
                      [modeQueryKey]: context.query?.[modeQueryKey],
                    };
                  }

                  return {
                    method: loadCfg.method || 'get',
                    url,
                    query: resolveContextValue(query || {}, context),
                  };
                },
                preparePageForm(scope, forceOptions = false){
                  const resolvedScope = resolvePublicFormScope(scope);
                  if (typeof this.initializeFormArrayGroups === 'function') {
                    this.initializeFormArrayGroups(resolvedScope);
                  }
                  if (typeof this.initializeFormOptions === 'function') {
                    this.initializeFormOptions(resolvedScope, forceOptions);
                  }
                  if (typeof this.initializeUploadFiles === 'function') {
                    this.initializeUploadFiles(resolvedScope);
                  }
                  if (typeof this.clearFormValidate === 'function') {
                    this.clearFormValidate(resolvedScope);
                  }

                  return this.getFormModel(resolvedScope) || {};
                },
                loadFormData(scope, force = false){
                  const resolvedScope = resolvePublicFormScope(scope);
                  const formCfg = getFormConfig(resolvedScope) || {};
                  if (!this.shouldLoadForm(resolvedScope, force)) {
                    this.preparePageForm(resolvedScope, force);
                    return Promise.resolve(this.buildPageFormLoadContext(resolvedScope));
                  }

                  const request = this.resolveFormLoadRequest(resolvedScope);
                  if (!request) {
                    this.preparePageForm(resolvedScope, force);
                    return Promise.resolve(this.buildPageFormLoadContext(resolvedScope));
                  }

                  const requestToken = this.nextSimpleFormLoadToken(resolvedScope);

                  return makeRequest(request)
                    .then((response) => {
                      const payload = ensureSuccess(extractPayload(response), '数据加载失败');
                      if (!this.isLatestSimpleFormLoadToken(resolvedScope, requestToken)) {
                        return null;
                      }

                      const data = extractLoadData(payload, formCfg?.load?.dataPath || null);
                      const nextModel = Object.assign({}, clone(formCfg.defaults || {}));
                      if (isObject(data)) {
                        Object.assign(nextModel, clone(data));
                      }

                      const applyModel = () => setConfigState(this, formCfg, 'modelVar', 'modelPath', nextModel);
                      if (typeof this.withDependencyResetSuspended === 'function') {
                        this.withDependencyResetSuspended(resolvedScope, applyModel);
                      } else {
                        applyModel();
                      }

                      const currentForm = this.preparePageForm(resolvedScope, true);
                      this.setSimpleFormInitialSnapshot(resolvedScope, currentForm);

                      return Object.assign(this.buildPageFormLoadContext(resolvedScope), {
                        response,
                        payload,
                        form: currentForm,
                        model: currentForm,
                      });
                    })
                    .catch((error) => {
                      const message = error?.message || resolveMessage(error?.response?.data, '数据加载失败');
                      if (message) {
                        ElementPlus.ElMessage.error(message);
                      }

                      return this.buildPageFormLoadContext(resolvedScope, { error });
                    });
                },
                initializePageForms(){
                  const scopes = pageFormScopes();
                  if (scopes.length === 0) {
                    return Promise.resolve([]);
                  }

                  return scopes.reduce(
                    (promise, scope) => promise.then((results) => {
                      return Promise.resolve(this.loadFormData(scope)).then((result) => {
                        results.push(result);
                        return results;
                      });
                    }),
                    Promise.resolve([])
                  );
                },
                validateForm(scope){
                  return this.validateSimpleForm(scope);
                },
                getFormModel(scope){
                  return this.getSimpleFormModel(scope);
                },
                setFormModel(scope, values = {}){
                  return this.setSimpleFormModel(scope, values);
                },
                initializeFormModel(scope, values = {}){
                  return this.initializeSimpleFormModel(scope, values);
                },
                resetSimpleForm(scope){
                  const resolvedScope = resolvePublicFormScope(scope);
                  const initialSnapshot = this.getSimpleFormInitialSnapshot(resolvedScope);

                  if (typeof this.initializeSimpleFormModel === 'function') {
                    return this.initializeSimpleFormModel(resolvedScope, initialSnapshot);
                  }
                  if (typeof this.setSimpleFormModel === 'function') {
                    return this.setSimpleFormModel(resolvedScope, initialSnapshot);
                  }

                  throw new Error('Current runtime does not expose resetSimpleForm() support.');
                },
                resetForm(scope){
                  return this.resetSimpleForm(scope);
                },
                notifyDialogHost(payload = {}){
                  return postDialogHostMessage(payload);
                },
                closeHostDialog(dialogKey = null){
                  const payload = { action: 'close' };
                  if (typeof dialogKey === 'string' && dialogKey !== '') {
                    payload.dialogKey = dialogKey;
                  }

                  return this.notifyDialogHost(payload);
                },
                reloadHostTable(tableKey = null, dialogKey = null){
                  const payload = { action: 'reloadTable' };
                  if (typeof tableKey === 'string' && tableKey !== '') {
                    payload.tableKey = tableKey;
                  }
                  if (typeof dialogKey === 'string' && dialogKey !== '') {
                    payload.dialogKey = dialogKey;
                  }

                  return this.notifyDialogHost(payload);
                },
                openHostDialog(target, row = null, tableKey = null){
                  if (typeof target !== 'string' || target === '') {
                    return false;
                  }

                  const payload = { action: 'openDialog', target };
                  if (row !== null && row !== undefined) {
                    payload.row = row;
                  }
                  if (typeof tableKey === 'string' && tableKey !== '') {
                    payload.tableKey = tableKey;
                  }

                  return this.notifyDialogHost(payload);
                },
                setHostDialogTitle(title, dialogKey = null){
                  const payload = {
                    action: 'setTitle',
                    title: title ?? '',
                  };
                  if (typeof dialogKey === 'string' && dialogKey !== '') {
                    payload.dialogKey = dialogKey;
                  }

                  return this.notifyDialogHost(payload);
                },
                setHostDialogFullscreen(value = true, dialogKey = null){
                  const payload = {
                    action: 'setFullscreen',
                    value: value !== false,
                  };
                  if (typeof dialogKey === 'string' && dialogKey !== '') {
                    payload.dialogKey = dialogKey;
                  }

                  return this.notifyDialogHost(payload);
                },
                toggleHostDialogFullscreen(dialogKey = null){
                  const payload = { action: 'toggleFullscreen' };
                  if (typeof dialogKey === 'string' && dialogKey !== '') {
                    payload.dialogKey = dialogKey;
                  }

                  return this.notifyDialogHost(payload);
                },
                refreshHostDialogIframe(dialogKey = null){
                  const payload = { action: 'refreshIframe' };
                  if (typeof dialogKey === 'string' && dialogKey !== '') {
                    payload.dialogKey = dialogKey;
                  }

                  return this.notifyDialogHost(payload);
                },
                clearFormValidate(scope){
                  return this.clearSimpleFormValidate(scope);
                },
                withDependencyResetSuspended(scope, callback){
                  return this.withSimpleDependencyResetSuspended(scope, callback);
                },
                initializeFormOptions(scope, force = false){
                  return this.initializeSimpleFormOptions(scope, force);
                },
                loadFormFieldOptions(scope, fieldName, force = false){
                  return this.loadSimpleFormFieldOptions(scope, fieldName, force);
                },
                initializeUploadFiles(scope){
                  return this.initializeSimpleUploadFiles(scope);
                },
                getPickerState(...args){
                  return this.getSimplePickerState(...args);
                },
                initializePickerState(...args){
                  return this.initializeSimplePickerState(...args);
                },
                setUploadFileList(...args){
                  return this.setSimpleUploadFileList(...args);
                },
                handleUploadBefore(...args){
                  return this.handleSimpleUploadBefore(...args);
                },
                handleUploadSuccess(...args){
                  return this.handleSimpleUploadSuccess(...args);
                },
                handleUploadError(...args){
                  return this.handleSimpleUploadError(...args);
                },
                handleUploadRemove(...args){
                  return this.handleSimpleUploadRemove(...args);
                },
                handleUploadExceed(...args){
                  return this.handleSimpleUploadExceed(...args);
                },
                handleUploadPreview(...args){
                  return this.handleSimpleUploadPreview(...args);
                },
                applyFormLinkage(...args){
                  return this.applySimpleFormLinkage(...args);
                }
              },
              createSimpleDialogMethods({
                buildFormsContext: (vm) => buildFormsContext(vm, forms),
                clone,
                cfg,
                ensureSuccess,
                extractPayload,
                resolveMessage
              }),
              createSimpleTableMethods({
                cfg,
                clone,
                ensureSuccess,
                extractPayload,
                makeRequest,
                pickRows,
                resolveMessage
              })
            )
          });
          registerElementPlusIcons(app);
          registerScV2Components(app);
          app.use(ElementPlus, { locale: ElementPlusLocaleZhCn });
          const vm = app.mount('#app');
          const pageApi = createPublicPageApi(vm);
          const currentVueApp = globalThis.VueApp && typeof globalThis.VueApp === 'object'
            ? globalThis.VueApp
            : {};

          globalThis.__SC_V2_PAGE__ = pageApi;
          globalThis.VueApp = Object.assign(currentVueApp, pageApi, { submit: pageApi.submit });

          return vm;
        };
