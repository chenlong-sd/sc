        globalThis.__SC_V2_CREATE_LIST_FILTER_METHODS__ = ({
          cfg,
          clone,
          getFilterFormConfig,
          getFilterScope,
          resolveListKey
        }) => {
          const {
            emitConfiguredEvent,
            isEventCanceled,
            setConfigState
          } = globalThis.__SC_V2_RUNTIME_HELPERS__;
          const buildListEventContext = (vm, listKey, listCfg, overrides = {}) => {
            const scope = getFilterScope(listKey);
            const formCfg = getFilterFormConfig(listKey);

            return Object.assign({
              listKey,
              listConfig: listCfg || {},
              tableKey: listCfg?.tableKey || null,
              filterScope: scope,
              formConfig: formCfg || null,
              filters: scope && vm.getFormModel ? (vm.getFormModel(scope) || {}) : {},
              vm
            }, overrides);
          };

          return {
            submitFilters(listKey = null){
              const resolvedListKey = resolveListKey(listKey);
              const listCfg = cfg?.lists?.[resolvedListKey] || null;
              const scope = getFilterScope(resolvedListKey);

              if (!listCfg?.tableKey) {
                return;
              }

              if (!scope) {
                return emitConfiguredEvent(
                  listCfg || {},
                  'filterSubmit',
                  buildListEventContext(this, resolvedListKey, listCfg)
                ).then((results) => {
                  if (isEventCanceled(results)) {
                    return;
                  }

                  const tableState = typeof this.getTableState === 'function'
                    ? this.getTableState(listCfg.tableKey)
                    : null;
                  if (tableState) {
                    tableState.page = 1;
                  }
                  this.loadTableData(listCfg.tableKey);
                });
              }

              return this.validateForm(scope).then((valid) => {
                if (!valid) return;

                return emitConfiguredEvent(
                  listCfg || {},
                  'filterSubmit',
                  buildListEventContext(this, resolvedListKey, listCfg)
                ).then((results) => {
                  if (isEventCanceled(results)) {
                    return;
                  }

                  const tableState = typeof this.getTableState === 'function'
                    ? this.getTableState(listCfg.tableKey)
                    : null;
                  if (tableState) {
                    tableState.page = 1;
                  }
                  this.loadTableData(listCfg.tableKey);
                });
              });
            },
            resetFilters(listKey = null){
              const resolvedListKey = resolveListKey(listKey);
              const listCfg = cfg?.lists?.[resolvedListKey] || null;
              const scope = getFilterScope(resolvedListKey);
              const formCfg = getFilterFormConfig(resolvedListKey);

              if (!listCfg?.tableKey || !scope || !formCfg) {
                return;
              }

              return emitConfiguredEvent(
                listCfg || {},
                'filterReset',
                buildListEventContext(this, resolvedListKey, listCfg)
              ).then((results) => {
                if (isEventCanceled(results)) {
                  return;
                }

                this.withDependencyResetSuspended(scope, () => {
                  setConfigState(this, formCfg, 'modelVar', 'modelPath', clone(formCfg.defaults || {}));
                  this.initializeFormArrayGroups(scope);
                  this.clearFormValidate(scope);
                  this.initializeFormOptions(scope, true);
                  this.initializeUploadFiles(scope);
                });

                const tableState = typeof this.getTableState === 'function'
                  ? this.getTableState(listCfg.tableKey)
                  : null;
                if (tableState) {
                  tableState.page = 1;
                }
                this.loadTableData(listCfg.tableKey);
              });
            }
          };
        };
