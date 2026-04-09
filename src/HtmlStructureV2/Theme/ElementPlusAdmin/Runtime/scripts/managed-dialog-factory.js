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
            emitConfiguredEvent,
            getByPath,
            isEventCanceled,
            isObject,
            makeRequest,
            toDialogScope,
            resolveContextValue,
            resolveTitleTemplate,
          } = globalThis.__SC_V2_RUNTIME_HELPERS__;
          const defaultSubmitLoadingText = '请稍后...';

          const names = Object.assign({
            withDependencyResetSuspended: 'withDependencyResetSuspended',
            initializeFormArrayGroups: 'initializeFormArrayGroups',
            initializeFormOptions: 'initializeFormOptions',
            initializeUploadFiles: 'initializeUploadFiles',
            initializePickerState: 'initializePickerState',
            clearFormValidate: 'clearFormValidate',
            validateForm: 'validateForm',
          }, formMethodNames || {});

          return {
            ensureDialogBodyRefStore(){
              if (!isObject(this.__dialogBodyRefs)) {
                this.__dialogBodyRefs = {};
              }

              return this.__dialogBodyRefs;
            },
            ensureDialogTableKeyStore(){
              if (!isObject(this.dialogTableKeys)) {
                this.dialogTableKeys = {};
              }

              return this.dialogTableKeys;
            },
            ensureDialogIframeWindowStore(){
              if (!(this.__dialogIframeWindows instanceof WeakMap)) {
                this.__dialogIframeWindows = new WeakMap();
              }

              return this.__dialogIframeWindows;
            },
            ensureDialogMessageBridge(){
              if (this.__dialogMessageBridgeBound) {
                return;
              }

              this.__dialogMessageBridgeBound = true;
              this.__dialogMessageBridgeHandler = (event) => this.handleDialogHostMessage(event);
              window.addEventListener('message', this.__dialogMessageBridgeHandler);
            },
            resolveDialogKeyFromMessage(event, payload = {}){
              if (typeof payload?.dialogKey === 'string' && payload.dialogKey !== '') {
                return payload.dialogKey;
              }

              const source = event?.source;
              if (!source || typeof source !== 'object') {
                return '';
              }

              const dialogWindows = this.ensureDialogIframeWindowStore();
              return dialogWindows.get(source) || '';
            },
            getDialogBodyRefs(dialogKey){
              return this.ensureDialogBodyRefStore()[dialogKey] || {};
            },
            getActiveDialogTableKey(dialogKey){
              if (typeof dialogKey !== 'string' || dialogKey === '') {
                return null;
              }

              return this.ensureDialogTableKeyStore()[dialogKey] || null;
            },
            setActiveDialogTableKey(dialogKey, tableKey = null){
              if (typeof dialogKey !== 'string' || dialogKey === '') {
                return null;
              }

              this.ensureDialogTableKeyStore()[dialogKey] = typeof tableKey === 'string' && tableKey !== ''
                ? tableKey
                : null;

              return this.ensureDialogTableKeyStore()[dialogKey];
            },
            setDialogComponentRef(dialogKey, instance){
              const store = this.ensureDialogBodyRefStore();
              store[dialogKey] = store[dialogKey] || {};
              store[dialogKey].component = instance || null;

              return instance || null;
            },
            setDialogIframeRef(dialogKey, iframe){
              const store = this.ensureDialogBodyRefStore();
              store[dialogKey] = store[dialogKey] || {};
              store[dialogKey].iframe = iframe || null;

              if (iframe?.contentWindow && cfg.dialogs?.[dialogKey]?.iframe?.host) {
                this.ensureDialogIframeWindowStore().set(iframe.contentWindow, dialogKey);
              }

              return iframe || null;
            },
            handleDialogIframeLoad(dialogKey, event){
              const iframe = event?.target || this.getDialogBodyRefs(dialogKey)?.iframe || null;
              if (iframe?.contentWindow && cfg.dialogs?.[dialogKey]?.iframe?.host) {
                this.ensureDialogIframeWindowStore().set(iframe.contentWindow, dialogKey);
              }
            },
            setDialogTitle(dialogKey, title){
              if (typeof dialogKey !== 'string' || dialogKey === '') {
                return;
              }

              this.dialogTitles[dialogKey] = title == null ? '' : String(title);
            },
            setDialogFullscreen(dialogKey, value = true){
              if (typeof dialogKey !== 'string' || dialogKey === '') {
                return;
              }

              this.dialogFullscreen[dialogKey] = value === undefined ? true : !!value;
            },
            toggleDialogFullscreen(dialogKey){
              if (typeof dialogKey !== 'string' || dialogKey === '') {
                return;
              }

              this.dialogFullscreen[dialogKey] = !(this.dialogFullscreen?.[dialogKey] || false);
            },
            refreshDialogIframe(dialogKey){
              const iframe = this.getDialogBodyRefs(dialogKey)?.iframe || null;
              const windowRef = iframe?.contentWindow;

              try {
                if (windowRef?.location?.reload) {
                  windowRef.location.reload();
                } else if (iframe?.src) {
                  iframe.src = iframe.src;
                }
              } catch (error) {
                if (iframe?.src) {
                  iframe.src = iframe.src;
                }
              }
            },
            resolveDialogIframeSubmitHandler(dialogKey, iframeWindow){
              const dialogCfg = cfg.dialogs?.[dialogKey];
              const handlerPath = String(dialogCfg?.iframe?.submitHandler || 'VueApp.submit').trim();
              if (handlerPath === '') {
                return {
                  handler: null,
                  owner: iframeWindow || null,
                  path: '',
                };
              }

              const segments = handlerPath.split('.').filter(Boolean);
              let owner = iframeWindow || null;
              for (let index = 0; index < Math.max(segments.length - 1, 0); index += 1) {
                owner = owner?.[segments[index]];
              }

              const methodName = segments[segments.length - 1] || '';
              return {
                handler: methodName ? owner?.[methodName] : null,
                owner: owner || iframeWindow || null,
                path: handlerPath,
              };
            },
            invokeDialogIframeSubmit(dialogKey, context){
              const dialogIframeRef = this.getDialogBodyRefs(dialogKey)?.iframe || null;
              const iframeWindow = dialogIframeRef?.contentWindow || null;
              if (!iframeWindow) {
                return Promise.reject(new Error('子页面尚未加载完成'));
              }

              try {
                const submitHandler = this.resolveDialogIframeSubmitHandler(dialogKey, iframeWindow);
                if (typeof submitHandler?.handler !== 'function') {
                  return Promise.reject(new Error(
                    submitHandler?.path
                      ? `子页面未暴露提交方法：${submitHandler.path}`
                      : '未配置子页面提交方法'
                  ));
                }

                return Promise.resolve(submitHandler.handler.call(
                  submitHandler.owner || iframeWindow,
                  context
                ));
              } catch (error) {
                return Promise.reject(new Error(
                  error?.message || '当前子页面不支持宿主直接提交'
                ));
              }
            },
            buildDialogSubmitContext(dialogKey, submitData, overrides = {}){
              const normalizedSubmitData = submitData === undefined ? null : submitData;
              return this.buildDialogContext(dialogKey, undefined, Object.assign({
                dialog: isObject(normalizedSubmitData) ? normalizedSubmitData : (normalizedSubmitData ?? {}),
                submitData: normalizedSubmitData,
              }, overrides));
            },
            invokeDialogComponentMethod(dialogKey, methodName, context){
              if (typeof methodName !== 'string' || methodName === '') {
                return Promise.resolve(null);
              }

              const instance = this.getDialogBodyRefs(dialogKey)?.component || null;
              const handler = instance?.[methodName];
              if (typeof handler !== 'function') {
                return Promise.resolve(null);
              }

              try {
                return Promise.resolve(handler.call(instance, context));
              } catch (error) {
                return Promise.reject(error);
              }
            },
            handleDialogHostMessage(event){
              const payload = event?.data?.__scV2DialogHost;
              if (!isObject(payload)) {
                return;
              }

              const dialogKey = this.resolveDialogKeyFromMessage(event, payload);
              if (!dialogKey || !cfg.dialogs?.[dialogKey]?.iframe?.host) {
                return;
              }

              const context = this.buildDialogContext(dialogKey, undefined, {
                hostMessage: payload,
                hostEvent: event,
              });

              switch (payload.action) {
                case 'close':
                  this.closeDialog(dialogKey);
                  break;
                case 'reloadTable':
                  if (typeof context.reloadTable === 'function') {
                    context.reloadTable(payload.tableKey || undefined);
                  }
                  break;
                case 'openDialog':
                  this.openDialog(payload.target, payload.row || null, payload.tableKey || context.tableKey || null);
                  break;
                case 'setTitle':
                  this.setDialogTitle(dialogKey, payload.title || '');
                  break;
                case 'setFullscreen':
                  this.setDialogFullscreen(dialogKey, payload.value !== false);
                  break;
                case 'toggleFullscreen':
                  this.toggleDialogFullscreen(dialogKey);
                  break;
                case 'refreshIframe':
                  this.refreshDialogIframe(dialogKey);
                  break;
                default:
                  break;
              }
            },
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
            resolveDialogExtraContext(dialogCfg, baseContext = {}){
              if (!dialogCfg) {
                return {};
              }

              const resolved = resolveContextValue(dialogCfg.context || {}, baseContext);
              return isObject(resolved) ? resolved : {};
            },
            resolveDialogComponentProps(dialogCfg, context){
              if (dialogCfg?.type !== 'component') {
                return {};
              }

              const resolved = resolveContextValue(dialogCfg.component?.props || {}, context);
              return isObject(resolved) ? resolved : {};
            },
            buildDialogContext(dialogKey, row = undefined, overrides = {}){
              const dialogCfg = cfg.dialogs?.[dialogKey] || {};
              const activeRow = row === undefined
                ? (this.dialogRows?.[dialogKey] || null)
                : (row || null);
              const activeTableKey = overrides.tableKey !== undefined
                ? (overrides.tableKey || null)
                : this.getActiveDialogTableKey(dialogKey);
              const baseContext = getBaseContext(this, dialogKey, activeRow, activeTableKey) || {};
              const defaults = {
                row: activeRow,
                mode: this.dialogMode?.[dialogKey] || (activeRow ? 'edit' : 'create'),
                dialogKey,
                tableKey: activeTableKey,
                dialogConfig: dialogCfg,
                dialog: this.dialogForms?.[dialogKey] || {},
                dialogs: this.dialogForms || {},
                dialogLoading: this.dialogLoading?.[dialogKey] || false,
                dialogSubmitting: this.dialogSubmitting?.[dialogKey] || false,
                dialogVisible: this.dialogVisible?.[dialogKey] || false,
                dialogTitle: this.dialogTitles?.[dialogKey] || dialogCfg.title || '',
                dialogFullscreen: this.dialogFullscreen?.[dialogKey] || false,
                dialogComponentProps: this.dialogComponentProps?.[dialogKey] || {},
                dialogComponentRef: this.getDialogBodyRefs(dialogKey)?.component || null,
                dialogIframeRef: this.getDialogBodyRefs(dialogKey)?.iframe || null,
                vm: this,
                reloadTable: (target = activeTableKey) => {
                  if (!target) {
                    return undefined;
                  }

                  if (typeof this.reloadTable === 'function') {
                    return this.reloadTable(target);
                  }
                  if (typeof this.loadTableData === 'function') {
                    return this.loadTableData(target);
                  }

                  return undefined;
                },
                closeDialog: (target = dialogKey) => this.closeDialog(target),
                openDialog: (target, data = null, targetTableKey = activeTableKey) => this.openDialog(target, data, targetTableKey),
                setDialogTitle: (title) => this.setDialogTitle(dialogKey, title),
                setDialogFullscreen: (value = true) => this.setDialogFullscreen(dialogKey, value),
                toggleDialogFullscreen: () => this.toggleDialogFullscreen(dialogKey),
                refreshDialogIframe: () => this.refreshDialogIframe(dialogKey),
              };
              const resolvedDialogContext = this.resolveDialogExtraContext(dialogCfg, Object.assign({}, defaults, baseContext, overrides));

              return Object.assign({
                dialogContext: resolvedDialogContext,
                data: resolvedDialogContext,
              }, defaults, baseContext, resolvedDialogContext, overrides);
            },
            resolveDialogTitle(dialogCfg, context){
              return resolveTitleTemplate(dialogCfg.titleTemplate || dialogCfg.title || '', context);
            },
            resolveDialogIframeUrl(dialogCfg, context){
              if (dialogCfg.type !== 'iframe' || !dialogCfg.iframe?.url) {
                return '';
              }

              const resolvedQuery = resolveContextValue(dialogCfg.iframe.query || {}, context);
              const query = isObject(resolvedQuery) ? Object.assign({}, resolvedQuery) : {};

              if (dialogCfg.iframe?.host) {
                query.__scV2DialogHost = 1;
                query.__scV2DialogKey = context?.dialogKey || '';
              }

              return buildUrlWithQuery(dialogCfg.iframe.url, query, context);
            },
            syncDialogRuntimeState(dialogKey, row = undefined){
              const dialogCfg = cfg.dialogs?.[dialogKey] || {};
              const context = this.buildDialogContext(dialogKey, row);
              this.dialogTitles[dialogKey] = this.resolveDialogTitle(dialogCfg, context);
              this.dialogIframeUrls[dialogKey] = this.resolveDialogIframeUrl(dialogCfg, context);
              this.dialogComponentProps[dialogKey] = this.resolveDialogComponentProps(dialogCfg, context);

              return context;
            },
            resetDialogFormState(dialogKey, row = undefined, data = null){
              const dialogCfg = cfg.dialogs?.[dialogKey];
              if (!dialogCfg) {
                return {};
              }

              const scope = toDialogScope(dialogKey);
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
                  this.dialogPickerItems[dialogKey] = clone(this.dialogPickerInitials?.[dialogKey] || {});
                });
              } else {
                this.dialogForms[dialogKey] = formData;
                this.dialogPickerItems[dialogKey] = clone(this.dialogPickerInitials?.[dialogKey] || {});
              }

              if (dialogCfg.type === 'form') {
                if (typeof this[names.initializeFormArrayGroups] === 'function') {
                  this[names.initializeFormArrayGroups](scope);
                }
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
            resolveDialogSubmitUrl(dialogKey, actionConfig = null, actionContext = null){
              const dialogCfg = cfg.dialogs?.[dialogKey];
              if (!dialogCfg) {
                return '';
              }

              const mode = this.dialogMode?.[dialogKey] || 'create';
              const submitActionCfg = isObject(actionConfig?.submit) ? actionConfig.submit : {};
              const url = mode === 'edit'
                ? (submitActionCfg.updateUrl || submitActionCfg.saveUrl || dialogCfg.updateUrl || dialogCfg.saveUrl || '')
                : (submitActionCfg.createUrl || submitActionCfg.saveUrl || dialogCfg.createUrl || dialogCfg.saveUrl || '');
              const context = this.buildDialogContext(
                dialogKey,
                undefined,
                isObject(actionContext) ? actionContext : {}
              );
              const resolved = resolveContextValue(url, context);

              return typeof resolved === 'string' ? resolved : '';
            },
            openDialog(dialogKey, row, tableKey = null){
              const dialogCfg = cfg.dialogs?.[dialogKey];
              if (!dialogCfg) {
                return Promise.resolve(null);
              }

              const scope = toDialogScope(dialogKey);
              const sourceRow = row || null;
              const sourceTableKey = typeof tableKey === 'string' && tableKey !== '' ? tableKey : null;
              const mode = sourceRow ? 'edit' : 'create';
              const initialContext = this.buildDialogContext(dialogKey, sourceRow, {
                mode,
                row: sourceRow,
                tableKey: sourceTableKey,
              });

              return emitConfiguredEvent(dialogCfg, 'beforeOpen', initialContext)
                .then((results) => {
                  if (isEventCanceled(results)) {
                    return null;
                  }

                  const requestToken = this.nextDialogRequestToken(dialogKey);
                  this.consumeDialogCloseContext(dialogKey);
                  this.ensureDialogClosingStore()[dialogKey] = false;
                  this.dialogMode[dialogKey] = mode;
                  this.setActiveDialogTableKey(dialogKey, sourceTableKey);
                  this.dialogRows[dialogKey] = sourceRow ? clone(sourceRow) : null;
                  this.dialogSubmitting[dialogKey] = false;
                  this.dialogLoading[dialogKey] = false;
                  this.dialogFullscreen[dialogKey] = !!dialogCfg.fullscreen;
                  this.dialogComponentProps[dialogKey] = {};
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

                    const finalContext = context || this.buildDialogContext(dialogKey);
                    return this.invokeDialogComponentMethod(dialogKey, dialogCfg.component?.openMethod, finalContext)
                      .then(() => emitConfiguredEvent(dialogCfg, 'afterOpen', finalContext));
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

              return emitConfiguredEvent(dialogCfg, 'beforeClose', closingContext)
                .then((results) => {
                  if (isEventCanceled(results)) {
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

              const scope = toDialogScope(dialogKey);
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
              this.dialogFullscreen[dialogKey] = !!dialogCfg.fullscreen;
              this.dialogComponentProps[dialogKey] = {};
              this.setActiveDialogTableKey(dialogKey, null);
              this.dialogRows[dialogKey] = null;
              this.setDialogComponentRef(dialogKey, null);
              this.setDialogIframeRef(dialogKey, null);
              if (this.__pickerDialogBindings && typeof this.__pickerDialogBindings === 'object') {
                delete this.__pickerDialogBindings[dialogKey];
              }

              if (typeof this[names.withDependencyResetSuspended] === 'function') {
                this[names.withDependencyResetSuspended](scope, () => {
                  this.dialogForms[dialogKey] = clone(this.dialogInitials?.[dialogKey] || {});
                  this.dialogPickerItems[dialogKey] = clone(this.dialogPickerInitials?.[dialogKey] || {});
                  if (dialogCfg.type === 'form' && typeof this[names.initializePickerState] === 'function') {
                    this[names.initializePickerState](scope);
                  }
                  if (dialogCfg.type === 'form' && typeof this[names.initializeUploadFiles] === 'function') {
                    this[names.initializeUploadFiles](scope);
                  }
                });
              } else {
                this.dialogForms[dialogKey] = clone(this.dialogInitials?.[dialogKey] || {});
                this.dialogPickerItems[dialogKey] = clone(this.dialogPickerInitials?.[dialogKey] || {});
                if (dialogCfg.type === 'form' && typeof this[names.initializePickerState] === 'function') {
                  this[names.initializePickerState](scope);
                }
                if (dialogCfg.type === 'form' && typeof this[names.initializeUploadFiles] === 'function') {
                  this[names.initializeUploadFiles](scope);
                }
              }

              return Vue.nextTick(() => this.invokeDialogComponentMethod(dialogKey, dialogCfg.component?.closeMethod, closingContext)
                .then(() => emitConfiguredEvent(dialogCfg, 'afterClose', closingContext)))
                .catch((error) => {
                  const message = error?.message || '弹窗关闭回调执行失败';
                  ElementPlus.ElMessage.error(message);

                  return null;
                });
            },
            closeDialog(dialogKey){
              return this.requestDialogClose(dialogKey);
            },
            submitDialog(dialogKey, actionConfig = null, actionContext = null){
              const dialogCfg = cfg.dialogs?.[dialogKey];
              if (!dialogCfg || !['form', 'iframe'].includes(dialogCfg.type)) {
                return;
              }

              if (this.dialogLoading?.[dialogKey] || this.dialogSubmitting?.[dialogKey]) {
                return;
              }

              let loadingInstance = null;
              const beginSubmitting = () => {
                if (this.dialogSubmitting?.[dialogKey]) {
                  return false;
                }

                this.dialogSubmitting[dialogKey] = true;
                loadingInstance = ElementPlus.ElLoading.service({
                  lock: true,
                  text: defaultSubmitLoadingText,
                  background: 'rgba(255,255,255,0.35)',
                });

                return true;
              };
              const finishSubmitting = () => {
                this.dialogSubmitting[dialogKey] = false;
                if (loadingInstance && typeof loadingInstance.close === 'function') {
                  loadingInstance.close();
                }
                loadingInstance = null;
              };

              if (dialogCfg.type === 'form') {
                if (!beginSubmitting()) {
                  return;
                }

                const scope = toDialogScope(dialogKey);
                const validate = typeof this[names.validateForm] === 'function'
                  ? this[names.validateForm](scope)
                  : Promise.resolve(true);

                Promise.resolve(validate).then((valid) => {
                  if (!valid) return null;

                  const submitUrl = this.resolveDialogSubmitUrl(dialogKey, actionConfig, actionContext);
                  if (!submitUrl) {
                    this.closeDialog(dialogKey);
                    return null;
                  }

                  const submitData = this.dialogForms?.[dialogKey] || {};
                  return makeRequest({
                    method: 'post',
                    url: submitUrl,
                    query: submitData
                  })
                    .then((response) => {
                      const payload = ensureSuccess(extractPayload(response), '保存失败');
                      ElementPlus.ElMessage.success(resolveMessage(payload, '保存成功'));
                      const context = this.buildDialogSubmitContext(dialogKey, submitData, {
                        response,
                        payload,
                      });

                      return emitConfiguredEvent(dialogCfg, 'submitSuccess', context)
                        .then((results) => {
                          if (isEventCanceled(results)) {
                            return payload;
                          }

                          this.closeDialog(dialogKey);
                          if (typeof context.reloadTable === 'function') {
                            context.reloadTable();
                          }

                          return payload;
                        });
                    })
                    .catch((error) => {
                      const message = error?.message || resolveMessage(error?.response?.data, '保存失败');
                      ElementPlus.ElMessage.error(message);

                      return emitConfiguredEvent(dialogCfg, 'submitFail', this.buildDialogSubmitContext(dialogKey, submitData, {
                        error,
                      }));
                    });
                })
                  .catch((error) => {
                    const message = error?.message || '保存失败';
                    ElementPlus.ElMessage.error(message);

                    return null;
                  })
                  .finally(() => {
                    finishSubmitting();
                  });

                return;
              }

              const submitUrl = this.resolveDialogSubmitUrl(dialogKey, actionConfig, actionContext);
              if (!submitUrl) {
                ElementPlus.ElMessage.error('请先为 iframe 弹窗配置 saveUrl()/createUrl()/updateUrl()');
                return;
              }

              if (!beginSubmitting()) {
                return;
              }
              const submitContext = this.buildDialogContext(dialogKey);
              let submitData = null;

              this.invokeDialogIframeSubmit(dialogKey, submitContext)
                .then((data) => {
                  submitData = data ?? {};
                  return makeRequest({
                    method: 'post',
                    url: submitUrl,
                    query: submitData
                  });
                })
                .then((response) => {
                  const payload = ensureSuccess(extractPayload(response), '保存失败');
                  ElementPlus.ElMessage.success(resolveMessage(payload, '保存成功'));
                  const context = this.buildDialogSubmitContext(dialogKey, submitData, {
                    response,
                    payload,
                  });

                  return emitConfiguredEvent(dialogCfg, 'submitSuccess', context)
                    .then((results) => {
                      if (isEventCanceled(results)) {
                        return payload;
                      }

                      this.closeDialog(dialogKey);
                      if (typeof context.reloadTable === 'function') {
                        context.reloadTable();
                      }

                      return payload;
                    });
                })
                .catch((error) => {
                  const message = error?.message || resolveMessage(error?.response?.data, '保存失败');
                  ElementPlus.ElMessage.error(message);

                  return emitConfiguredEvent(dialogCfg, 'submitFail', this.buildDialogSubmitContext(dialogKey, submitData, {
                    error,
                  }));
                })
                .finally(() => {
                  finishSubmitting();
                });
            }
          };
        };
