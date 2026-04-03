        globalThis.__SC_V2_CREATE_MANAGED_DIALOG_METHODS__ = ({
          clone,
          cfg,
          ensureSuccess,
          extractPayload,
          resolveMessage,
          getBaseContext = () => ({}),
          formMethodNames = {}
        }) => {
          const {
            buildUrlWithQuery,
            callHook,
            getByPath,
            isObject,
            makeRequest,
            resolveContextValue,
            resolveTitleTemplate,
          } = globalThis.__SC_V2_RUNTIME_HELPERS__;

          const names = Object.assign({
            withDependencyResetSuspended: 'withDependencyResetSuspended',
            initializeFormOptions: 'initializeFormOptions',
            initializeUploadFiles: 'initializeUploadFiles',
            clearFormValidate: 'clearFormValidate',
            validateForm: 'validateForm',
          }, formMethodNames || {});

          return {
            ensureDialogRequestStore(){
              if (!isObject(this.__dialogRequestTokens)) {
                this.__dialogRequestTokens = {};
              }

              return this.__dialogRequestTokens;
            },
            nextDialogRequestToken(dialogKey){
              const store = this.ensureDialogRequestStore();
              store[dialogKey] = (store[dialogKey] || 0) + 1;

              return store[dialogKey];
            },
            isLatestDialogRequestToken(dialogKey, token){
              return this.ensureDialogRequestStore()[dialogKey] === token;
            },
            ensureDialogCloseContextStore(){
              if (!isObject(this.__dialogCloseContexts)) {
                this.__dialogCloseContexts = {};
              }

              return this.__dialogCloseContexts;
            },
            stashDialogCloseContext(dialogKey, context){
              this.ensureDialogCloseContextStore()[dialogKey] = context;

              return context;
            },
            consumeDialogCloseContext(dialogKey){
              const store = this.ensureDialogCloseContextStore();
              const context = store[dialogKey] || null;
              delete store[dialogKey];

              return context;
            },
            ensureDialogClosingStore(){
              if (!isObject(this.__dialogClosingStates)) {
                this.__dialogClosingStates = {};
              }

              return this.__dialogClosingStates;
            },
            buildDialogContext(dialogKey, row = undefined, overrides = {}){
              const dialogCfg = cfg.dialogs?.[dialogKey] || {};
              const activeRow = row === undefined
                ? (this.dialogRows?.[dialogKey] || null)
                : (row || null);
              const baseContext = getBaseContext(this, dialogKey, activeRow) || {};

              return Object.assign({
                row: activeRow,
                mode: this.dialogMode?.[dialogKey] || (activeRow ? 'edit' : 'create'),
                dialogKey,
                dialogConfig: dialogCfg,
                dialog: this.dialogForms?.[dialogKey] || {},
                dialogs: this.dialogForms || {},
                dialogLoading: this.dialogLoading?.[dialogKey] || false,
                vm: this,
                reloadTable: () => undefined,
                closeDialog: (target = dialogKey) => this.closeDialog(target),
                openDialog: (target, data = null) => this.openDialog(target, data),
              }, baseContext, overrides);
            },
            resolveDialogTitle(dialogCfg, context){
              return resolveTitleTemplate(dialogCfg.titleTemplate || dialogCfg.title || '', context);
            },
            resolveDialogIframeUrl(dialogCfg, context){
              if (dialogCfg.type !== 'iframe' || !dialogCfg.iframe?.url) {
                return '';
              }

              return buildUrlWithQuery(dialogCfg.iframe.url, dialogCfg.iframe.query || {}, context);
            },
            syncDialogRuntimeState(dialogKey, row = undefined){
              const dialogCfg = cfg.dialogs?.[dialogKey] || {};
              const context = this.buildDialogContext(dialogKey, row);
              this.dialogTitles[dialogKey] = this.resolveDialogTitle(dialogCfg, context);
              this.dialogIframeUrls[dialogKey] = this.resolveDialogIframeUrl(dialogCfg, context);

              return context;
            },
            resetDialogFormState(dialogKey, row = undefined, data = null){
              const dialogCfg = cfg.dialogs?.[dialogKey];
              if (!dialogCfg) {
                return {};
              }

              const scope = 'dialog:' + dialogKey;
              const activeRow = row === undefined
                ? (this.dialogRows?.[dialogKey] || null)
                : (row || null);
              const formData = Object.assign(
                clone(this.dialogInitials?.[dialogKey] || {}),
                activeRow ? clone(activeRow) : {}
              );

              if (isObject(data)) {
                Object.assign(formData, clone(data));
              }

              if (typeof this[names.withDependencyResetSuspended] === 'function') {
                this[names.withDependencyResetSuspended](scope, () => {
                  this.dialogForms[dialogKey] = formData;
                });
              } else {
                this.dialogForms[dialogKey] = formData;
              }

              if (dialogCfg.type === 'form') {
                if (typeof this[names.initializeFormOptions] === 'function') {
                  this[names.initializeFormOptions](scope, true);
                }
                if (typeof this[names.initializeUploadFiles] === 'function') {
                  this[names.initializeUploadFiles](scope);
                }
              }

              return formData;
            },
            shouldLoadDialog(dialogCfg, mode){
              if (!dialogCfg?.load?.url) {
                return false;
              }

              const when = String(dialogCfg.load.when || 'edit').toLowerCase();
              if (when === 'always') {
                return true;
              }

              if (when === 'create') {
                return mode === 'create';
              }

              return mode === 'edit';
            },
            resolveDialogLoadRequest(dialogKey){
              const dialogCfg = cfg.dialogs?.[dialogKey];
              if (!dialogCfg?.load?.url) {
                return null;
              }

              const context = this.buildDialogContext(dialogKey);
              const url = resolveContextValue(dialogCfg.load.url, context);
              if (typeof url !== 'string' || url === '') {
                return null;
              }

              return {
                method: dialogCfg.load.method || 'get',
                url,
                query: resolveContextValue(dialogCfg.load.payload || {}, context),
              };
            },
            extractDialogLoadData(dialogCfg, payload){
              const candidates = [];

              if (dialogCfg?.load?.dataPath) {
                candidates.push(getByPath(payload, dialogCfg.load.dataPath));
              } else {
                if (isObject(payload)) {
                  candidates.push(payload.data, payload.result, payload.payload);
                  if (isObject(payload.data)) {
                    candidates.push(payload.data.data, payload.data.result, payload.data.payload);
                  }
                }
                candidates.push(payload);
              }

              for (const item of candidates) {
                if (isObject(item)) {
                  return item;
                }
              }

              return {};
            },
            loadDialogData(dialogKey, requestToken){
              const dialogCfg = cfg.dialogs?.[dialogKey];
              if (!dialogCfg) {
                return Promise.resolve(null);
              }

              const request = this.resolveDialogLoadRequest(dialogKey);
              if (!request) {
                return Promise.resolve(this.syncDialogRuntimeState(dialogKey));
              }

              this.dialogLoading[dialogKey] = true;

              return makeRequest(request)
                .then((response) => {
                  const payload = ensureSuccess(extractPayload(response), '数据加载失败');
                  if (!this.isLatestDialogRequestToken(dialogKey, requestToken) || !this.dialogVisible?.[dialogKey]) {
                    return null;
                  }

                  const data = this.extractDialogLoadData(dialogCfg, payload);
                  this.resetDialogFormState(dialogKey, undefined, data);
                  const context = this.syncDialogRuntimeState(dialogKey);

                  return Object.assign({}, context, {
                    response,
                    payload,
                    dialog: this.dialogForms?.[dialogKey] || {},
                  });
                })
                .catch((error) => {
                  if (this.isLatestDialogRequestToken(dialogKey, requestToken)) {
                    const message = error?.message || resolveMessage(error?.response?.data, '数据加载失败');
                    ElementPlus.ElMessage.error(message);
                  }

                  return this.buildDialogContext(dialogKey);
                })
                .finally(() => {
                  if (this.isLatestDialogRequestToken(dialogKey, requestToken)) {
                    this.dialogLoading[dialogKey] = false;
                  }
                });
            },
            resolveDialogSubmitUrl(dialogKey){
              const dialogCfg = cfg.dialogs?.[dialogKey];
              if (!dialogCfg) {
                return '';
              }

              const mode = this.dialogMode?.[dialogKey] || 'create';
              const url = mode === 'edit'
                ? (dialogCfg.updateUrl || dialogCfg.saveUrl || '')
                : (dialogCfg.createUrl || dialogCfg.saveUrl || '');

              const resolved = resolveContextValue(url, this.buildDialogContext(dialogKey));

              return typeof resolved === 'string' ? resolved : '';
            },
            openDialog(dialogKey, row){
              const dialogCfg = cfg.dialogs?.[dialogKey];
              if (!dialogCfg) {
                return Promise.resolve(null);
              }

              const scope = 'dialog:' + dialogKey;
              const sourceRow = row || null;
              const mode = sourceRow ? 'edit' : 'create';
              const initialContext = this.buildDialogContext(dialogKey, sourceRow, {
                mode,
                row: sourceRow,
              });

              return callHook(dialogCfg.beforeOpen, initialContext)
                .then((result) => {
                  if (result === false) {
                    return null;
                  }

                  const requestToken = this.nextDialogRequestToken(dialogKey);
                  this.consumeDialogCloseContext(dialogKey);
                  this.ensureDialogClosingStore()[dialogKey] = false;
                  this.dialogMode[dialogKey] = mode;
                  this.dialogRows[dialogKey] = sourceRow ? clone(sourceRow) : null;
                  this.dialogSubmitting[dialogKey] = false;
                  this.dialogLoading[dialogKey] = false;
                  this.resetDialogFormState(dialogKey, sourceRow);
                  this.dialogVisible[dialogKey] = true;

                  const openedContext = this.syncDialogRuntimeState(dialogKey, sourceRow);
                  const loadPromise = this.shouldLoadDialog(dialogCfg, mode)
                    ? this.loadDialogData(dialogKey, requestToken)
                    : Promise.resolve(openedContext);

                  return loadPromise.then((context) => Vue.nextTick(() => {
                    if (!this.isLatestDialogRequestToken(dialogKey, requestToken) || !this.dialogVisible?.[dialogKey]) {
                      return null;
                    }

                    if (typeof this[names.clearFormValidate] === 'function') {
                      this[names.clearFormValidate](scope);
                    }

                    return callHook(dialogCfg.afterOpen, context || this.buildDialogContext(dialogKey));
                  }));
                })
                .catch((error) => {
                  const message = error?.message || '弹窗打开失败';
                  ElementPlus.ElMessage.error(message);

                  return null;
                });
            },
            requestDialogClose(dialogKey, done = null){
              const dialogCfg = cfg.dialogs?.[dialogKey];
              if (!dialogCfg) {
                return Promise.resolve(null);
              }

              const closingStore = this.ensureDialogClosingStore();
              if (closingStore[dialogKey]) {
                return Promise.resolve(null);
              }

              closingStore[dialogKey] = true;
              const closingDialog = clone(this.dialogForms?.[dialogKey] || {});
              const closingContext = this.buildDialogContext(dialogKey, undefined, {
                row: this.dialogRows?.[dialogKey] || null,
                dialog: closingDialog
              });

              return callHook(dialogCfg.beforeClose, closingContext)
                .then((result) => {
                  if (result === false) {
                    closingStore[dialogKey] = false;
                    return null;
                  }

                  this.stashDialogCloseContext(dialogKey, closingContext);
                  if (typeof done === 'function') {
                    done();
                  } else {
                    this.dialogVisible[dialogKey] = false;
                  }

                  return null;
                })
                .catch((error) => {
                  closingStore[dialogKey] = false;
                  const message = error?.message || '弹窗关闭失败';
                  ElementPlus.ElMessage.error(message);

                  return null;
                });
            },
            handleDialogBeforeClose(dialogKey, done){
              return this.requestDialogClose(dialogKey, done);
            },
            handleDialogClosed(dialogKey){
              const dialogCfg = cfg.dialogs?.[dialogKey];
              if (!dialogCfg) {
                return Promise.resolve(null);
              }

              const scope = 'dialog:' + dialogKey;
              const closingContext = this.consumeDialogCloseContext(dialogKey) || this.buildDialogContext(dialogKey);

              this.ensureDialogClosingStore()[dialogKey] = false;
              this.nextDialogRequestToken(dialogKey);
              if (typeof this[names.clearFormValidate] === 'function') {
                this[names.clearFormValidate](scope);
              }
              this.dialogVisible[dialogKey] = false;
              this.dialogSubmitting[dialogKey] = false;
              this.dialogLoading[dialogKey] = false;
              this.dialogTitles[dialogKey] = dialogCfg.title || '';
              this.dialogIframeUrls[dialogKey] = '';
              this.dialogRows[dialogKey] = null;

              if (typeof this[names.withDependencyResetSuspended] === 'function') {
                this[names.withDependencyResetSuspended](scope, () => {
                  this.dialogForms[dialogKey] = clone(this.dialogInitials?.[dialogKey] || {});
                  if (dialogCfg.type === 'form' && typeof this[names.initializeUploadFiles] === 'function') {
                    this[names.initializeUploadFiles](scope);
                  }
                });
              } else {
                this.dialogForms[dialogKey] = clone(this.dialogInitials?.[dialogKey] || {});
                if (dialogCfg.type === 'form' && typeof this[names.initializeUploadFiles] === 'function') {
                  this[names.initializeUploadFiles](scope);
                }
              }

              return Vue.nextTick(() => callHook(dialogCfg.afterClose, closingContext))
                .catch((error) => {
                  const message = error?.message || '弹窗关闭回调执行失败';
                  ElementPlus.ElMessage.error(message);

                  return null;
                });
            },
            closeDialog(dialogKey){
              return this.requestDialogClose(dialogKey);
            },
            submitDialog(dialogKey){
              const dialogCfg = cfg.dialogs?.[dialogKey];
              if (!dialogCfg || dialogCfg.type !== 'form') {
                return;
              }

              if (this.dialogLoading?.[dialogKey]) {
                return;
              }

              const scope = 'dialog:' + dialogKey;
              const validate = typeof this[names.validateForm] === 'function'
                ? this[names.validateForm](scope)
                : Promise.resolve(true);

              validate.then((valid) => {
                if (!valid) return;

                const submitUrl = this.resolveDialogSubmitUrl(dialogKey);
                if (!submitUrl) {
                  this.closeDialog(dialogKey);
                  return;
                }

                this.dialogSubmitting[dialogKey] = true;
                makeRequest({
                  method: 'post',
                  url: submitUrl,
                  query: this.dialogForms[dialogKey] || {}
                })
                  .then((response) => {
                    const payload = ensureSuccess(extractPayload(response), '保存失败');
                    ElementPlus.ElMessage.success(resolveMessage(payload, '保存成功'));
                    this.closeDialog(dialogKey);
                    const context = this.buildDialogContext(dialogKey);
                    if (typeof context.reloadTable === 'function') {
                      context.reloadTable();
                    }
                  })
                  .catch((error) => {
                    const message = error?.message || resolveMessage(error?.response?.data, '保存失败');
                    ElementPlus.ElMessage.error(message);
                  })
                  .finally(() => {
                    this.dialogSubmitting[dialogKey] = false;
                  });
              });
            }
          };
        };
