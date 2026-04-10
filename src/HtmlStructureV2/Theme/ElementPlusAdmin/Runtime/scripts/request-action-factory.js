        globalThis.__SC_V2_CREATE_REQUEST_ACTION_METHODS__ = ({ cfg = {}, getBaseContext = () => ({}) } = {}) => {
          const {
            clone,
            emitConfiguredEvent,
            ensureSuccess,
            extractPayload,
            isEventCanceled,
            isObject,
            makeRequest,
            postDialogHostMessage,
            readPageQuery,
            resolveContextValue,
            resolvePageMode,
            resolveMessage,
          } = globalThis.__SC_V2_RUNTIME_HELPERS__;
          const dialogFormScopePrefix = 'dialog:';
          const knownFormScopes = () => Object.keys(cfg?.forms || {});
          const normalizeFormScope = (scope) => {
            const normalized = typeof scope === 'string' ? scope.trim() : '';
            return normalized !== '' ? normalized : null;
          };
          const cloneRequestValue = (value) => {
            try {
              return JSON.parse(JSON.stringify(value ?? {}));
            } catch (error) {
              return clone(value ?? {});
            }
          };
          const confirmAction = (confirmText, executor) => {
            if (!confirmText) {
              return Promise.resolve().then(() => executor());
            }

            return ElementPlus.ElMessageBox.confirm(confirmText, '提示', {
              type: 'warning',
              lockScroll: false
            })
              .then(() => executor())
              .catch((error) => {
                if (error === 'cancel' || error === 'close') {
                  return null;
                }

                const message = error?.message || '操作失败';
                if (message) {
                  ElementPlus.ElMessage.error(message);
                }

                return null;
              });
          };

          return {
            resolveActionConfig(actionConfig){
              if (typeof actionConfig === 'string' && actionConfig !== '') {
                return cfg?.actions?.[actionConfig] || null;
              }

              return isObject(actionConfig) ? actionConfig : null;
            },
            resolvePageEventHandlers(handlers){
              if (typeof handlers === 'string' && handlers !== '') {
                return Array.isArray(cfg?.pageEvents?.[handlers]) ? cfg.pageEvents[handlers] : [];
              }

              return handlers;
            },
            ensureActionLoadingStore(){
              if (!isObject(this.actionLoading)) {
                this.actionLoading = {};
              }

              return this.actionLoading;
            },
            buildActionContext(actionConfig, row = null){
              const explicitActionFormScope = normalizeFormScope(actionConfig.formScope || null);
              const resolveActionTableKey = () => {
                if (actionConfig.tableKey) {
                  return actionConfig.tableKey;
                }

                if (actionConfig.listKey && typeof this.resolveListTableKey === 'function') {
                  return this.resolveListTableKey(actionConfig.listKey);
                }

                return null;
              };

              const resolveActionDialogContext = () => {
                const dialogKey = actionConfig.contextDialogKey || null;
                if (!dialogKey || typeof this.buildDialogContext !== 'function') {
                  return null;
                }

                const dialogContext = this.buildDialogContext(dialogKey);
                return isObject(dialogContext) ? dialogContext : null;
              };

              const sourceDialogContext = resolveActionDialogContext();
              const activeDialogKey = typeof sourceDialogContext?.dialogKey === 'string' && sourceDialogContext.dialogKey !== ''
                ? sourceDialogContext.dialogKey
                : (typeof actionConfig.contextDialogKey === 'string' && actionConfig.contextDialogKey !== ''
                  ? actionConfig.contextDialogKey
                  : null);
              const resolvedRow = row ?? sourceDialogContext?.row ?? null;
              const resolvedTableKey = resolveActionTableKey() || sourceDialogContext?.tableKey || null;
              const effectiveActionConfig = Object.assign({}, actionConfig, {
                tableKey: resolvedTableKey,
              });
              const baseContext = getBaseContext(this, effectiveActionConfig, resolvedRow) || {};
              const pageQuery = typeof this.getPageQuery === 'function'
                ? cloneRequestValue(this.getPageQuery())
                : cloneRequestValue(readPageQuery());
              const normalizeModeQueryKey = (queryKey) => {
                const normalized = typeof queryKey === 'string' ? queryKey.trim() : '';
                return normalized !== '' ? normalized : 'id';
              };
              const resolveRuntimePageMode = (queryKey = null) => {
                if (typeof this.resolvePageMode === 'function') {
                  return this.resolvePageMode(normalizeModeQueryKey(queryKey));
                }

                return resolvePageMode(pageQuery, normalizeModeQueryKey(queryKey));
              };
              const notifyHost = (payload = {}) => {
                if (typeof this.notifyDialogHost === 'function') {
                  return this.notifyDialogHost(payload);
                }

                return postDialogHostMessage(payload);
              };
              const context = Object.assign({
                action: effectiveActionConfig,
                tableKey: resolvedTableKey,
                listKey: actionConfig.listKey || null,
                row: resolvedRow,
                filters: {},
                forms: {},
                dialogs: {},
                selection: [],
                formScope: explicitActionFormScope,
                query: pageQuery,
                mode: resolveRuntimePageMode(),
                page: {
                  query: pageQuery,
                  mode: resolveRuntimePageMode(),
                  formScope: explicitActionFormScope,
                },
                vm: this,
                reloadTable: (tableKey = resolvedTableKey) => {
                  if (!tableKey) {
                    return undefined;
                  }

                  if (typeof this.reloadTable === 'function') {
                    return this.reloadTable(tableKey);
                  }
                  if (typeof this.loadTableData === 'function') {
                    return this.loadTableData(tableKey);
                  }

                  return undefined;
                },
                reloadList: (listKey = actionConfig.listKey || null) => {
                  if (typeof this.reloadList === 'function') {
                    return this.reloadList(listKey);
                  }

                  const tableKey = listKey && typeof this.resolveListTableKey === 'function'
                    ? this.resolveListTableKey(listKey)
                    : resolvedTableKey;

                  if (!tableKey) {
                    return undefined;
                  }

                  if (typeof this.reloadTable === 'function') {
                    return this.reloadTable(tableKey);
                  }
                  if (typeof this.loadTableData === 'function') {
                    return this.loadTableData(tableKey);
                  }

                  return undefined;
                },
                closeDialog: (dialogKey) => typeof this.closeDialog === 'function' ? this.closeDialog(dialogKey) : undefined,
                openDialog: (dialogKey, data = null, tableKey = resolvedTableKey) => {
                  if (typeof this.openDialog !== 'function') {
                    return undefined;
                  }

                  return this.openDialog(dialogKey, data, tableKey);
                },
                reloadPage: () => window.location.reload(),
                notifyDialogHost: (payload = {}) => notifyHost(payload),
                closeHostDialog: (dialogKey = null) => {
                  if (typeof this.closeHostDialog === 'function') {
                    return this.closeHostDialog(dialogKey);
                  }

                  const payload = { action: 'close' };
                  if (typeof dialogKey === 'string' && dialogKey !== '') {
                    payload.dialogKey = dialogKey;
                  }

                  return notifyHost(payload);
                },
                reloadHostTable: (tableKey = resolvedTableKey, dialogKey = null) => {
                  if (typeof this.reloadHostTable === 'function') {
                    return this.reloadHostTable(tableKey, dialogKey);
                  }

                  const payload = { action: 'reloadTable' };
                  if (typeof tableKey === 'string' && tableKey !== '') {
                    payload.tableKey = tableKey;
                  }
                  if (typeof dialogKey === 'string' && dialogKey !== '') {
                    payload.dialogKey = dialogKey;
                  }

                  return notifyHost(payload);
                },
                openHostDialog: (dialogKey, data = null, tableKey = resolvedTableKey) => {
                  if (typeof this.openHostDialog === 'function') {
                    return this.openHostDialog(dialogKey, data, tableKey);
                  }
                  if (typeof dialogKey !== 'string' || dialogKey === '') {
                    return false;
                  }

                  const payload = { action: 'openDialog', target: dialogKey };
                  if (data !== null && data !== undefined) {
                    payload.row = data;
                  }
                  if (typeof tableKey === 'string' && tableKey !== '') {
                    payload.tableKey = tableKey;
                  }

                  return notifyHost(payload);
                },
                setHostDialogTitle: (title, dialogKey = null) => {
                  if (typeof this.setHostDialogTitle === 'function') {
                    return this.setHostDialogTitle(title, dialogKey);
                  }

                  const payload = {
                    action: 'setTitle',
                    title: title ?? '',
                  };
                  if (typeof dialogKey === 'string' && dialogKey !== '') {
                    payload.dialogKey = dialogKey;
                  }

                  return notifyHost(payload);
                },
                setHostDialogFullscreen: (value = true, dialogKey = null) => {
                  if (typeof this.setHostDialogFullscreen === 'function') {
                    return this.setHostDialogFullscreen(value, dialogKey);
                  }

                  const payload = {
                    action: 'setFullscreen',
                    value: value !== false,
                  };
                  if (typeof dialogKey === 'string' && dialogKey !== '') {
                    payload.dialogKey = dialogKey;
                  }

                  return notifyHost(payload);
                },
                toggleHostDialogFullscreen: (dialogKey = null) => {
                  if (typeof this.toggleHostDialogFullscreen === 'function') {
                    return this.toggleHostDialogFullscreen(dialogKey);
                  }

                  const payload = { action: 'toggleFullscreen' };
                  if (typeof dialogKey === 'string' && dialogKey !== '') {
                    payload.dialogKey = dialogKey;
                  }

                  return notifyHost(payload);
                },
                refreshHostDialogIframe: (dialogKey = null) => {
                  if (typeof this.refreshHostDialogIframe === 'function') {
                    return this.refreshHostDialogIframe(dialogKey);
                  }

                  const payload = { action: 'refreshIframe' };
                  if (typeof dialogKey === 'string' && dialogKey !== '') {
                    payload.dialogKey = dialogKey;
                  }

                  return notifyHost(payload);
                },
              }, sourceDialogContext || {}, baseContext);
              const resolveImplicitFormScope = () => {
                if (explicitActionFormScope && cfg?.forms?.[explicitActionFormScope]) {
                  return explicitActionFormScope;
                }

                if (activeDialogKey) {
                  const dialogScope = dialogFormScopePrefix + activeDialogKey;
                  if (cfg?.forms?.[dialogScope]) {
                    return dialogScope;
                  }
                }

                const pageScopes = knownFormScopes().filter((scope) => !String(scope || '').startsWith(dialogFormScopePrefix));
                if (pageScopes.length === 1) {
                  return pageScopes[0];
                }

                const allScopes = knownFormScopes();
                if (allScopes.length === 1) {
                  return allScopes[0];
                }

                return null;
              };
              const ensureKnownFormScope = (scope, purpose = 'request action form') => {
                const normalizedScope = normalizeFormScope(scope);
                if (!normalizedScope) {
                  return null;
                }

                if (cfg?.forms?.[normalizedScope]) {
                  return normalizedScope;
                }

                const actionLabel = effectiveActionConfig?.label || effectiveActionConfig?.key || 'request action';
                throw new Error(`Request action [${actionLabel}] references unknown form scope [${normalizedScope}] for ${purpose}.`);
              };
              const resolveContextFormScope = (requestedScope = null, purpose = 'request action form') => {
                const explicitScope = ensureKnownFormScope(requestedScope, purpose);
                if (explicitScope) {
                  return explicitScope;
                }

                const implicitScope = resolveImplicitFormScope();
                if (implicitScope) {
                  return implicitScope;
                }

                const actionLabel = effectiveActionConfig?.label || effectiveActionConfig?.key || 'request action';
                throw new Error(
                  `Request action [${actionLabel}] cannot resolve form scope automatically; please call "validateForm('...')" or "payloadFromForm('...')" with an explicit form key.`
                );
              };
              const syncContextMode = (mode, scope = null) => {
                const resolvedMode = mode === 'edit' ? 'edit' : 'create';
                context.mode = resolvedMode;
                context.page = Object.assign({}, context.page || {}, {
                  query: pageQuery,
                  mode: resolvedMode,
                  formScope: scope || null,
                });

                return resolvedMode;
              };
              const getRuntimeFormModel = (scope) => {
                if (typeof this.getFormModel === 'function') {
                  return this.getFormModel(scope) || {};
                }
                if (typeof this.getSimpleFormModel === 'function') {
                  return this.getSimpleFormModel(scope) || {};
                }

                throw new Error('Current runtime does not expose public getFormModel() support.');
              };
              const validateRuntimeForm = (scope) => {
                if (typeof this.validateForm === 'function') {
                  return Promise.resolve(this.validateForm(scope));
                }
                if (typeof this.validateSimpleForm === 'function') {
                  return Promise.resolve(this.validateSimpleForm(scope));
                }

                throw new Error('Current runtime does not expose public validateForm() support.');
              };
              const setRuntimeFormModel = (scope, values) => {
                if (typeof this.setFormModel === 'function') {
                  return this.setFormModel(scope, values);
                }
                if (typeof this.setSimpleFormModel === 'function') {
                  return this.setSimpleFormModel(scope, values);
                }

                throw new Error('Current runtime does not expose public setFormModel() support.');
              };
              const initializeRuntimeFormModel = (scope, values) => {
                if (typeof this.initializeFormModel === 'function') {
                  return this.initializeFormModel(scope, values);
                }
                if (typeof this.initializeSimpleFormModel === 'function') {
                  return this.initializeSimpleFormModel(scope, values);
                }

                throw new Error('Current runtime does not expose public initializeFormModel() support.');
              };
              const resolveFormModelSetArgs = (arg1 = null, arg2 = undefined) => {
                if (typeof arg1 === 'string') {
                  return { scope: arg1, values: arg2 };
                }
                if (typeof arg2 === 'string') {
                  return { scope: arg2, values: arg1 };
                }

                return { scope: null, values: arg1 };
              };

              context.resolveFormScope = (scope = null) => resolveContextFormScope(scope, 'request action form');
              context.getPageQuery = () => cloneRequestValue(pageQuery);
              context.resolvePageMode = (queryKey = null) => syncContextMode(
                resolveRuntimePageMode(queryKey),
                context.page?.formScope || null
              );
              context.resolveFormMode = (scope = null) => {
                const resolvedScope = resolveContextFormScope(scope, 'page form mode');
                context.formScope = resolvedScope;

                const formConfig = cfg?.forms?.[resolvedScope] || {};
                return syncContextMode(
                  resolveRuntimePageMode(formConfig?.modeQueryKey || null),
                  resolvedScope
                );
              };
              context.getFormModel = (scope = null) => {
                const resolvedScope = resolveContextFormScope(scope, 'form model');
                context.formScope = resolvedScope;
                context.resolveFormMode(resolvedScope);

                return getRuntimeFormModel(resolvedScope);
              };
              context.cloneFormModel = (scope = null) => cloneRequestValue(context.getFormModel(scope));
              context.setFormModel = (arg1 = null, arg2 = undefined) => {
                const { scope, values } = resolveFormModelSetArgs(arg1, arg2);
                const resolvedScope = resolveContextFormScope(scope, 'form model update');
                context.formScope = resolvedScope;
                context.resolveFormMode(resolvedScope);
                const nextModel = setRuntimeFormModel(resolvedScope, values);
                context.form = nextModel || {};
                context.model = context.form;

                return context.form;
              };
              context.initializeFormModel = (arg1 = null, arg2 = undefined) => {
                const { scope, values } = resolveFormModelSetArgs(arg1, arg2);
                const resolvedScope = resolveContextFormScope(scope, 'form model initialization');
                context.formScope = resolvedScope;
                context.resolveFormMode(resolvedScope);
                const nextModel = initializeRuntimeFormModel(resolvedScope, values);
                context.form = nextModel || {};
                context.model = context.form;

                return context.form;
              };
              context.resetForm = (scope = null) => {
                const resolvedScope = resolveContextFormScope(scope, 'form reset');
                context.formScope = resolvedScope;
                context.resolveFormMode(resolvedScope);

                let nextModel = null;
                if (typeof this.resetForm === 'function') {
                  nextModel = this.resetForm(resolvedScope);
                } else if (typeof this.resetSimpleForm === 'function') {
                  nextModel = this.resetSimpleForm(resolvedScope);
                } else {
                  throw new Error('Current runtime does not expose public resetForm() support.');
                }

                context.form = nextModel || {};
                context.model = context.form;

                return context.form;
              };
              context.validateForm = (scope = null) => {
                const resolvedScope = resolveContextFormScope(scope, 'form validation');
                context.formScope = resolvedScope;
                context.resolveFormMode(resolvedScope);

                return validateRuntimeForm(resolvedScope).then((valid) => valid !== false);
              };
              context.loadFormData = (scope = null, force = false) => {
                const resolvedScope = resolveContextFormScope(scope, 'form load');
                context.formScope = resolvedScope;
                context.resolveFormMode(resolvedScope);

                if (typeof this.loadFormData === 'function') {
                  return this.loadFormData(resolvedScope, force);
                }

                throw new Error('Current runtime does not expose public loadFormData() support.');
              };

              const implicitScope = resolveImplicitFormScope();
              if (implicitScope) {
                context.page.formScope = implicitScope;
                syncContextMode(
                  resolveRuntimePageMode((cfg?.forms?.[implicitScope] || {})?.modeQueryKey || null),
                  implicitScope
                );
              }

              if (actionConfig.dialogTarget && context.dialogs?.[actionConfig.dialogTarget] !== undefined) {
                context.dialogKey = actionConfig.dialogTarget;
                context.dialog = context.dialogs[actionConfig.dialogTarget];
              }

              return context;
            },
            buildPageEventContext(overrides = {}){
              const normalizedOverrides = isObject(overrides) ? overrides : {};
              const actionConfig = {
                tableKey: normalizedOverrides.tableKey || null,
                listKey: normalizedOverrides.listKey || null,
                dialogTarget: normalizedOverrides.dialogKey || normalizedOverrides.dialogTarget || null,
              };
              const context = this.buildActionContext(actionConfig, normalizedOverrides.row || null);

              return Object.assign(context, normalizedOverrides);
            },
            runPageEventHandlers(handlers, overrides = {}){
              const resolvedHandlers = this.resolvePageEventHandlers(handlers);
              const queue = Array.isArray(resolvedHandlers)
                ? resolvedHandlers.filter(Boolean)
                : (resolvedHandlers ? [resolvedHandlers] : []);
              if (queue.length === 0) {
                return Promise.resolve([]);
              }

              const context = this.buildPageEventContext(overrides);

              return emitConfiguredEvent({ events: { trigger: queue } }, 'trigger', context)
                .catch((error) => {
                  const message = error?.message || '事件执行失败';
                  if (message) {
                    ElementPlus.ElMessage.error(message);
                  }

                  return null;
                });
            },
            runPageEvent(handler, overrides = {}){
              return this.runPageEventHandlers(handler ? [handler] : [], overrides);
            },
            runAction(actionConfig, row = null, executor = null){
              const resolvedActionConfig = this.resolveActionConfig(actionConfig);
              if (!resolvedActionConfig?.key) {
                return Promise.resolve(null);
              }

              const context = this.buildActionContext(resolvedActionConfig, row);

              return emitConfiguredEvent(resolvedActionConfig, 'click', context)
                .then((results) => {
                  if (isEventCanceled(results)) {
                    return null;
                  }

                  return confirmAction(resolvedActionConfig.confirmText, () => {
                    if (typeof executor !== 'function') {
                      return null;
                    }

                    return executor(context);
                  });
                })
                .catch((error) => {
                  const message = error?.message || '操作失败';
                  if (message) {
                    ElementPlus.ElMessage.error(message);
                  }

                  return null;
                });
            },
            runRequestAction(actionConfig, row = null){
              const resolvedActionConfig = this.resolveActionConfig(actionConfig);
              if (!resolvedActionConfig?.key) {
                return Promise.resolve(null);
              }

              const actionLoading = this.ensureActionLoadingStore();
              const context = this.buildActionContext(resolvedActionConfig, row);
              const resolveActionFormConfig = () => {
                return isObject(resolvedActionConfig.form) ? resolvedActionConfig.form : {};
              };
              const shouldValidateForm = () => resolveActionFormConfig().validate === true;
              const usesFormPayload = () => resolveActionFormConfig().payloadSource === 'form';
              const usesFormSubmitFlow = () => shouldValidateForm() || usesFormPayload();
              const resolveConfiguredFormScope = (fieldName) => {
                const formConfig = resolveActionFormConfig();
                return context.resolveFormScope(formConfig?.[fieldName] || null);
              };
              const resolveSubmitFormScope = () => {
                if (!usesFormSubmitFlow()) {
                  return null;
                }

                const formConfig = resolveActionFormConfig();
                return context.resolveFormScope(formConfig?.payloadScope || formConfig?.validateScope || null);
              };
              const getRuntimeFormConfig = (scope) => {
                return scope && isObject(cfg?.forms?.[scope]) ? cfg.forms[scope] : {};
              };
              let cachedSubmitFormState = undefined;
              const resolveSubmitFormState = () => {
                if (cachedSubmitFormState !== undefined) {
                  return cachedSubmitFormState;
                }

                const scope = resolveSubmitFormScope();
                const formConfig = getRuntimeFormConfig(scope);
                cachedSubmitFormState = {
                  scope,
                  formConfig,
                  submitConfig: isObject(formConfig?.submit) ? formConfig.submit : {},
                };

                return cachedSubmitFormState;
              };
              const buildSubmitFormContext = (scope, overrides = {}) => {
                if (!scope) {
                  return Object.assign({}, context, overrides || {});
                }

                const formConfig = getRuntimeFormConfig(scope);
                const mode = context.resolveFormMode(scope);
                const model = cloneRequestValue(context.getFormModel(scope));
                const currentPageQuery = typeof context.getPageQuery === 'function'
                  ? cloneRequestValue(context.getPageQuery())
                  : cloneRequestValue(context.page?.query || {});

                return Object.assign({}, context, {
                  scope,
                  formScope: scope,
                  mode,
                  form: model,
                  model,
                  formConfig,
                  page: Object.assign({}, context.page || {}, {
                    query: currentPageQuery,
                    mode,
                    formScope: scope,
                  }),
                }, overrides || {});
              };
              const emitSubmitFormEvent = (scope, eventName, overrides = {}) => {
                if (!scope) {
                  return Promise.resolve([]);
                }

                return emitConfiguredEvent(
                  { events: getRuntimeFormConfig(scope)?.events || {} },
                  eventName,
                  buildSubmitFormContext(scope, overrides)
                );
              };
              const saveConfig = isObject(resolvedActionConfig?.save) ? resolvedActionConfig.save : {};
              const hasSaveUrls = !!(saveConfig.createUrl || saveConfig.updateUrl);
              let submitFormState = null;
              try {
                submitFormState = resolveSubmitFormState();
              } catch (error) {
                const message = error?.message || '操作失败';
                if (message) {
                  ElementPlus.ElMessage.error(message);
                }

                return Promise.resolve(null);
              }
              const hasFormSaveUrls = !!(submitFormState.submitConfig.createUrl || submitFormState.submitConfig.updateUrl);
              if ((!resolvedActionConfig?.request?.url && !hasSaveUrls && !hasFormSaveUrls)) {
                if (usesFormSubmitFlow()) {
                  ElementPlus.ElMessage.error('当前表单未配置保存地址，请先调用 Form::saveUrls()。');
                }

                return Promise.resolve(null);
              }
              const resolveSaveMode = () => {
                const saveConfig = isObject(resolvedActionConfig.save) ? resolvedActionConfig.save : {};
                if (saveConfig.modeQueryKey) {
                  return context.resolvePageMode(saveConfig.modeQueryKey);
                }

                const submitFormState = resolveSubmitFormState();
                if (submitFormState.scope) {
                  return context.resolveFormMode(submitFormState.scope);
                }

                return context.resolvePageMode();
              };
              const resolveRequestUrl = () => {
                const saveConfig = isObject(resolvedActionConfig.save) ? resolvedActionConfig.save : {};
                if (saveConfig.createUrl || saveConfig.updateUrl) {
                  const mode = resolveSaveMode();
                  const candidateUrl = mode === 'edit'
                    ? (saveConfig.updateUrl || saveConfig.createUrl || '')
                    : (saveConfig.createUrl || saveConfig.updateUrl || '');

                  return resolveContextValue(candidateUrl, context);
                }

                if (resolvedActionConfig.request.url) {
                  return resolveContextValue(resolvedActionConfig.request.url, context);
                }

                const submitFormState = resolveSubmitFormState();
                const submitConfig = submitFormState.submitConfig || {};
                if (submitConfig.createUrl || submitConfig.updateUrl) {
                  const mode = resolveSaveMode();
                  const candidateUrl = mode === 'edit'
                    ? (submitConfig.updateUrl || submitConfig.createUrl || '')
                    : (submitConfig.createUrl || submitConfig.updateUrl || '');

                  return resolveContextValue(candidateUrl, context);
                }

                return null;
              };
              const resolveRequestMethod = () => {
                if (saveConfig.createUrl || saveConfig.updateUrl || resolvedActionConfig.request.url) {
                  return resolvedActionConfig.request.method || 'post';
                }

                const submitFormState = resolveSubmitFormState();
                return submitFormState.submitConfig?.method || 'post';
              };
              const resolveLoadingText = () => {
                if (resolvedActionConfig.loadingText !== null && resolvedActionConfig.loadingText !== undefined) {
                  return resolvedActionConfig.loadingText;
                }

                const submitFormState = resolveSubmitFormState();
                if (submitFormState.submitConfig?.loadingText) {
                  return submitFormState.submitConfig.loadingText;
                }

                if (usesFormSubmitFlow() || hasSaveUrls || hasFormSaveUrls) {
                  return '请稍后...';
                }

                return null;
              };
              const resolveSuccessMessage = (payload) => {
                const submitFormState = resolveSubmitFormState();
                return resolveMessage(
                  payload,
                  resolvedActionConfig.successMessage
                  ?? submitFormState.submitConfig?.successMessage
                  ?? '操作成功'
                );
              };
              const resolveErrorMessage = (responsePayload = null) => {
                const submitFormState = resolveSubmitFormState();
                return resolveMessage(
                  responsePayload,
                  resolvedActionConfig.errorMessage
                  ?? submitFormState.submitConfig?.errorMessage
                  ?? '操作失败'
                );
              };
              const validateConfiguredForm = () => {
                if (!shouldValidateForm()) {
                  return Promise.resolve(true);
                }

                return context.validateForm(resolveConfiguredFormScope('validateScope'));
              };
              const resolveRequestPayload = () => {
                if (usesFormPayload()) {
                  return context.cloneFormModel(resolveConfiguredFormScope('payloadScope'));
                }

                return resolveContextValue(resolvedActionConfig.request.query || {}, context);
              };

              const perform = () => {
                if (actionLoading[resolvedActionConfig.key]) {
                  return Promise.resolve(null);
                }

                let loadingInstance = null;
                actionLoading[resolvedActionConfig.key] = true;
                const loadingText = resolveLoadingText();
                if (loadingText) {
                  loadingInstance = ElementPlus.ElLoading.service({
                    lock: true,
                    text: loadingText,
                    background: 'rgba(255,255,255,0.35)',
                  });
                }

                return validateConfiguredForm()
                  .then((results) => {
                    if (results === false) {
                      return null;
                    }

                    const requestUrl = resolveRequestUrl();
                    if (typeof requestUrl !== 'string' || requestUrl === '') {
                      throw new Error('请求地址不能为空');
                    }

                    const request = {
                      method: resolveRequestMethod(),
                      url: requestUrl,
                      query: resolveRequestPayload(),
                    };

                    context.request = request;
                    const submitFormState = resolveSubmitFormState();
                    if (submitFormState.scope) {
                      context.formScope = submitFormState.scope;
                      context.formConfig = submitFormState.formConfig;
                      context.resolveFormMode(submitFormState.scope);
                      context.form = context.getFormModel(submitFormState.scope);
                      context.model = context.form;
                    }

                    return emitSubmitFormEvent(submitFormState.scope, 'submitBefore', {
                      request,
                      payload: request.query,
                    }).then((submitBeforeResults) => {
                      if (isEventCanceled(submitBeforeResults)) {
                        return null;
                      }

                      return emitConfiguredEvent(resolvedActionConfig, 'before', context);
                    })
                      .then((beforeResults) => {
                        if (isEventCanceled(beforeResults)) {
                          return null;
                        }

                        return makeRequest(request)
                          .then((response) => {
                            const payload = ensureSuccess(
                              extractPayload(response),
                              resolveErrorMessage(extractPayload(response))
                            );

                            context.response = response;
                            context.payload = payload;

                            const successMessage = resolveSuccessMessage(payload);
                            if (successMessage) {
                              ElementPlus.ElMessage.success(successMessage);
                            }

                            return emitSubmitFormEvent(submitFormState.scope, 'submitSuccess', {
                              request,
                              response,
                              payload,
                            }).then(() => emitConfiguredEvent(resolvedActionConfig, 'success', context))
                              .then(() => {
                                if (resolvedActionConfig.closeDialog && resolvedActionConfig.dialogTarget) {
                                  context.closeDialog(resolvedActionConfig.dialogTarget);
                                }
                                if (resolvedActionConfig.reloadTable) {
                                  if (resolvedActionConfig.listKey && !resolvedActionConfig.tableKey) {
                                    context.reloadList();
                                  } else {
                                    context.reloadTable();
                                  }
                                }
                                if (resolvedActionConfig.reloadPage) {
                                  context.reloadPage();
                                }

                                return payload;
                              });
                          })
                          .catch((error) => {
                            context.error = error;
                            const message = error?.message || resolveMessage(
                              error?.response?.data,
                              resolveErrorMessage(error?.response?.data)
                            );

                            if (message) {
                              ElementPlus.ElMessage.error(message);
                            }

                            return emitSubmitFormEvent(submitFormState.scope, 'submitFail', {
                              request,
                              error,
                            })
                              .then(() => emitConfiguredEvent(resolvedActionConfig, 'fail', context))
                              .then(() => null);
                          })
                          .finally(() => {
                            return emitSubmitFormEvent(submitFormState.scope, 'submitFinally', {
                              request,
                              response: context.response,
                              payload: context.payload,
                              error: context.error,
                            }).then(() => emitConfiguredEvent(resolvedActionConfig, 'finally', context));
                          });
                      });
                  })
                  .catch((error) => {
                    const message = error?.message || '操作失败';
                    if (message) {
                      ElementPlus.ElMessage.error(message);
                    }

                    return null;
                  })
                  .finally(() => {
                    actionLoading[resolvedActionConfig.key] = false;
                    if (loadingInstance && typeof loadingInstance.close === 'function') {
                      loadingInstance.close();
                    }
                  });
              };

              return emitConfiguredEvent(resolvedActionConfig, 'click', context)
                .then((results) => {
                  if (isEventCanceled(results)) {
                    return null;
                  }

                  return confirmAction(resolvedActionConfig.confirmText, perform);
                })
                .catch((error) => {
                  const message = error?.message || '操作失败';
                  if (message) {
                    ElementPlus.ElMessage.error(message);
                  }

                  return null;
                });
            }
          };
        };
