        globalThis.__SC_V2_CREATE_LIST_DIALOG_METHODS__ = ({
          buildFormsContext,
          clone,
          cfg,
          ensureSuccess,
          extractPayload,
          resolveMessage
        }) => {
          const createManagedDialogMethods = globalThis.__SC_V2_CREATE_MANAGED_DIALOG_METHODS__;

          return Object.assign(
            {},
            createManagedDialogMethods({
              clone,
              cfg,
              ensureSuccess,
              extractPayload,
              resolveMessage,
              getBaseContext: (vm) => ({
                forms: buildFormsContext(vm),
                filters: vm.filterModel || {},
                selection: Array.isArray(vm.tableSelection) ? vm.tableSelection : [],
                reloadTable: () => typeof vm.loadTableData === 'function' ? vm.loadTableData() : undefined,
              }),
              formMethodNames: {
                withDependencyResetSuspended: 'withDependencyResetSuspended',
                initializeFormOptions: 'initializeFormOptions',
                initializeUploadFiles: 'initializeUploadFiles',
                clearFormValidate: 'clearFormValidate',
                validateForm: 'validateForm',
              }
            }),
            {
              deleteRow(row, confirmText = '确认删除当前记录？'){
                if (!cfg.deleteUrl || !row) return;
                ElementPlus.ElMessageBox.confirm(confirmText, '提示', { type: 'warning' })
                  .then(() => {
                    const payload = (cfg.deleteKey && row[cfg.deleteKey] !== undefined)
                      ? { [cfg.deleteKey]: row[cfg.deleteKey] }
                      : row;
                    return axios.post(cfg.deleteUrl, payload);
                  })
                  .then((response) => {
                    const payload = ensureSuccess(extractPayload(response), '删除失败');
                    if ((cfg.pagination?.enabled !== false) && this.tableRows.length <= 1 && this.tablePage > 1) {
                      this.tablePage -= 1;
                    }
                    ElementPlus.ElMessage.success(resolveMessage(payload, '删除成功'));
                    this.loadTableData();
                  })
                  .catch((error) => {
                    if (error === 'cancel') return;
                    const message = error?.message || resolveMessage(error?.response?.data, '删除失败');
                    ElementPlus.ElMessage.error(message);
                  });
              }
            }
          );
        };
