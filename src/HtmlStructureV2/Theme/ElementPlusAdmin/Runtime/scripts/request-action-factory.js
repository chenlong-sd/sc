        globalThis.__SC_V2_CREATE_REQUEST_ACTION_METHODS__ = ({ getBaseContext = () => ({}) } = {}) => {
          const {
            ensureSuccess,
            extractPayload,
            getByPath,
            isObject,
            makeRequest,
            resolveMessage,
          } = globalThis.__SC_V2_RUNTIME_HELPERS__;

          const callHook = (hook, context) => {
            if (typeof hook !== 'function') {
              return Promise.resolve(undefined);
            }

            try {
              return Promise.resolve(hook(context));
            } catch (error) {
              return Promise.reject(error);
            }
          };

          const resolveToken = (token, context) => {
            const path = String(token || '').replace(/^@/, '');
            if (path === '') {
              return undefined;
            }

            return getByPath(context, path);
          };

          const resolveActionValue = (value, context) => {
            if (typeof value === 'function') {
              return resolveActionValue(value(context), context);
            }

            if (Array.isArray(value)) {
              return value.map((item) => resolveActionValue(item, context));
            }

            if (isObject(value)) {
              const output = {};
              Object.keys(value).forEach((key) => {
                output[key] = resolveActionValue(value[key], context);
              });
              return output;
            }

            if (typeof value === 'string') {
              if (/^@[\w.]+$/.test(value)) {
                return resolveToken(value, context);
              }

              if (!value.includes('@')) {
                return value;
              }

              return value.replace(/@[\w.]+/g, (token) => {
                const resolved = resolveToken(token, context);
                return resolved === null || resolved === undefined ? '' : String(resolved);
              });
            }

            return value;
          };

          return {
            ensureActionLoadingStore(){
              if (!isObject(this.actionLoading)) {
                this.actionLoading = {};
              }

              return this.actionLoading;
            },
            runRequestAction(actionConfig, row = null){
              if (!actionConfig?.request?.url || !actionConfig?.key) {
                return Promise.resolve(null);
              }

              const actionLoading = this.ensureActionLoadingStore();
              const baseContext = getBaseContext(this, actionConfig, row) || {};
              const context = Object.assign({
                action: actionConfig,
                row: row || null,
                filters: {},
                forms: {},
                dialogs: {},
                selection: [],
                vm: this,
                reloadTable: () => typeof this.loadTableData === 'function' ? this.loadTableData() : undefined,
                closeDialog: (dialogKey) => typeof this.closeDialog === 'function' ? this.closeDialog(dialogKey) : undefined,
                reloadPage: () => window.location.reload(),
              }, baseContext);

              if (actionConfig.dialogTarget && context.dialogs?.[actionConfig.dialogTarget] !== undefined) {
                context.dialogKey = actionConfig.dialogTarget;
                context.dialog = context.dialogs[actionConfig.dialogTarget];
              }

              const perform = () => {
                let loadingInstance = null;

                return callHook(actionConfig.request.before, context)
                  .then((result) => {
                    if (result === false) {
                      return null;
                    }

                    const request = {
                      method: actionConfig.request.method || 'post',
                      url: resolveActionValue(actionConfig.request.url, context),
                      query: resolveActionValue(actionConfig.request.query || {}, context),
                    };

                    context.request = request;
                    actionLoading[actionConfig.key] = true;

                    if (actionConfig.loadingText) {
                      loadingInstance = ElementPlus.ElLoading.service({
                        lock: true,
                        text: actionConfig.loadingText,
                        background: 'rgba(255,255,255,0.35)',
                      });
                    }

                    return makeRequest(request)
                      .then((response) => {
                        const payload = ensureSuccess(
                          extractPayload(response),
                          actionConfig.errorMessage || '操作失败'
                        );

                        context.response = response;
                        context.payload = payload;

                        const successMessage = actionConfig.successMessage ?? resolveMessage(payload, '操作成功');
                        if (successMessage) {
                          ElementPlus.ElMessage.success(successMessage);
                        }

                        return callHook(actionConfig.request.afterSuccess, context)
                          .then(() => {
                            if (actionConfig.closeDialog && actionConfig.dialogTarget) {
                              context.closeDialog(actionConfig.dialogTarget);
                            }
                            if (actionConfig.reloadTable) {
                              context.reloadTable();
                            }
                            if (actionConfig.reloadPage) {
                              context.reloadPage();
                            }

                            return payload;
                          });
                      })
                      .catch((error) => {
                        context.error = error;
                        const message = error?.message || resolveMessage(
                          error?.response?.data,
                          actionConfig.errorMessage || '操作失败'
                        );

                        if (message) {
                          ElementPlus.ElMessage.error(message);
                        }

                        return callHook(actionConfig.request.afterFail, context)
                          .then(() => null);
                      })
                      .finally(() => {
                        actionLoading[actionConfig.key] = false;
                        if (loadingInstance && typeof loadingInstance.close === 'function') {
                          loadingInstance.close();
                        }

                        return callHook(actionConfig.request.afterFinally, context);
                      });
                  });
              };

              if (!actionConfig.confirmText) {
                return perform();
              }

              return ElementPlus.ElMessageBox.confirm(actionConfig.confirmText, '提示', {
                type: 'warning'
              })
                .then(() => perform())
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
            }
          };
        };
