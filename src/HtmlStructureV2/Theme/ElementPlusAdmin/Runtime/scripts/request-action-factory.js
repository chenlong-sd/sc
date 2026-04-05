        globalThis.__SC_V2_CREATE_REQUEST_ACTION_METHODS__ = ({ getBaseContext = () => ({}) } = {}) => {
          const {
            emitConfiguredEvent,
            ensureSuccess,
            extractPayload,
            getByPath,
            isEventCanceled,
            isObject,
            makeRequest,
            resolveMessage,
          } = globalThis.__SC_V2_RUNTIME_HELPERS__;

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
          const confirmAction = (confirmText, executor) => {
            if (!confirmText) {
              return Promise.resolve().then(() => executor());
            }

            return ElementPlus.ElMessageBox.confirm(confirmText, '提示', {
              type: 'warning'
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
            ensureActionLoadingStore(){
              if (!isObject(this.actionLoading)) {
                this.actionLoading = {};
              }

              return this.actionLoading;
            },
            buildActionContext(actionConfig, row = null){
              const resolveActionTableKey = () => {
                if (actionConfig.tableKey) {
                  return actionConfig.tableKey;
                }

                if (actionConfig.listKey && typeof this.resolveListTableKey === 'function') {
                  return this.resolveListTableKey(actionConfig.listKey);
                }

                return null;
              };

              const resolvedTableKey = resolveActionTableKey();
              const baseContext = getBaseContext(this, actionConfig, row) || {};
              const context = Object.assign({
                action: actionConfig,
                tableKey: resolvedTableKey,
                listKey: actionConfig.listKey || null,
                row: row || null,
                filters: {},
                forms: {},
                dialogs: {},
                selection: [],
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
                reloadPage: () => window.location.reload(),
              }, baseContext);

              if (actionConfig.dialogTarget && context.dialogs?.[actionConfig.dialogTarget] !== undefined) {
                context.dialogKey = actionConfig.dialogTarget;
                context.dialog = context.dialogs[actionConfig.dialogTarget];
              }

              return context;
            },
            runAction(actionConfig, row = null, executor = null){
              if (!actionConfig?.key) {
                return Promise.resolve(null);
              }

              const context = this.buildActionContext(actionConfig, row);

              return emitConfiguredEvent(actionConfig, 'click', context)
                .then((results) => {
                  if (isEventCanceled(results)) {
                    return null;
                  }

                  return confirmAction(actionConfig.confirmText, () => {
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
              if (!actionConfig?.request?.url || !actionConfig?.key) {
                return Promise.resolve(null);
              }

              const actionLoading = this.ensureActionLoadingStore();
              const context = this.buildActionContext(actionConfig, row);

              const perform = () => {
                let loadingInstance = null;
                const request = {
                  method: actionConfig.request.method || 'post',
                  url: resolveActionValue(actionConfig.request.url, context),
                  query: resolveActionValue(actionConfig.request.query || {}, context),
                };

                context.request = request;

                return emitConfiguredEvent(actionConfig, 'before', context)
                  .then((results) => {
                    if (isEventCanceled(results)) {
                      return null;
                    }

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

                        return emitConfiguredEvent(actionConfig, 'success', context)
                          .then(() => {
                            if (actionConfig.closeDialog && actionConfig.dialogTarget) {
                              context.closeDialog(actionConfig.dialogTarget);
                            }
                            if (actionConfig.reloadTable) {
                              if (actionConfig.listKey && !actionConfig.tableKey) {
                                context.reloadList();
                              } else {
                                context.reloadTable();
                              }
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

                        return emitConfiguredEvent(actionConfig, 'fail', context)
                          .then(() => null);
                      })
                      .finally(() => {
                        actionLoading[actionConfig.key] = false;
                        if (loadingInstance && typeof loadingInstance.close === 'function') {
                          loadingInstance.close();
                        }

                        return emitConfiguredEvent(actionConfig, 'finally', context);
                      });
                  });
              };

              return emitConfiguredEvent(actionConfig, 'click', context)
                .then((results) => {
                  if (isEventCanceled(results)) {
                    return null;
                  }

                  return confirmAction(actionConfig.confirmText, perform);
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
