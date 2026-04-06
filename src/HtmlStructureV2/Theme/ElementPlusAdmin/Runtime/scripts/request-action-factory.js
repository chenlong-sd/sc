        globalThis.__SC_V2_CREATE_REQUEST_ACTION_METHODS__ = ({ cfg = {}, getBaseContext = () => ({}) } = {}) => {
          const {
            emitConfiguredEvent,
            ensureSuccess,
            extractPayload,
            isEventCanceled,
            isObject,
            makeRequest,
            resolveContextValue,
            resolveMessage,
          } = globalThis.__SC_V2_RUNTIME_HELPERS__;
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
              if (!resolvedActionConfig?.request?.url || !resolvedActionConfig?.key) {
                return Promise.resolve(null);
              }

              const actionLoading = this.ensureActionLoadingStore();
              const context = this.buildActionContext(resolvedActionConfig, row);

              const perform = () => {
                let loadingInstance = null;
                const request = {
                  method: resolvedActionConfig.request.method || 'post',
                  url: resolveContextValue(resolvedActionConfig.request.url, context),
                  query: resolveContextValue(resolvedActionConfig.request.query || {}, context),
                };

                context.request = request;

                return emitConfiguredEvent(resolvedActionConfig, 'before', context)
                  .then((results) => {
                    if (isEventCanceled(results)) {
                      return null;
                    }

                    actionLoading[resolvedActionConfig.key] = true;

                    if (resolvedActionConfig.loadingText) {
                      loadingInstance = ElementPlus.ElLoading.service({
                        lock: true,
                        text: resolvedActionConfig.loadingText,
                        background: 'rgba(255,255,255,0.35)',
                      });
                    }

                    return makeRequest(request)
                      .then((response) => {
                        const payload = ensureSuccess(
                          extractPayload(response),
                          resolvedActionConfig.errorMessage || '操作失败'
                        );

                        context.response = response;
                        context.payload = payload;

                        const successMessage = resolvedActionConfig.successMessage ?? resolveMessage(payload, '操作成功');
                        if (successMessage) {
                          ElementPlus.ElMessage.success(successMessage);
                        }

                        return emitConfiguredEvent(resolvedActionConfig, 'success', context)
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
                          resolvedActionConfig.errorMessage || '操作失败'
                        );

                        if (message) {
                          ElementPlus.ElMessage.error(message);
                        }

                        return emitConfiguredEvent(resolvedActionConfig, 'fail', context)
                          .then(() => null);
                      })
                      .finally(() => {
                        actionLoading[resolvedActionConfig.key] = false;
                        if (loadingInstance && typeof loadingInstance.close === 'function') {
                          loadingInstance.close();
                        }

                        return emitConfiguredEvent(resolvedActionConfig, 'finally', context);
                      });
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
