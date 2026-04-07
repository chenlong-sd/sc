        globalThis.__SC_V2_BOOT_SIMPLE__ = (state, cfg) => {
          const {
            buildFormsContext,
            buildManagedDialogRuntimeState,
            buildTableStates,
            clone,
            extractPayload,
            ensureSuccess,
            initializeConfiguredForms,
            makeRequest,
            pickRows,
            registerElementPlusIcons,
            registerScV2Components,
            resolveMessage,
          } = globalThis.__SC_V2_RUNTIME_HELPERS__;
          const forms = cfg.forms || {};

          const createColumnDisplayMethods = globalThis.__SC_V2_CREATE_COLUMN_DISPLAY_METHODS__;
          const createRequestActionMethods = globalThis.__SC_V2_CREATE_REQUEST_ACTION_METHODS__;
          const createSimpleFormMethods = globalThis.__SC_V2_CREATE_SIMPLE_FORM_METHODS__;
          const createSimpleDialogMethods = globalThis.__SC_V2_CREATE_SIMPLE_DIALOG_METHODS__;
          const createSimpleTableMethods = globalThis.__SC_V2_CREATE_SIMPLE_TABLE_METHODS__;
          const app = Vue.createApp({
            data(){
              return Object.assign({
                actionLoading: {},
                tableConfigs: cfg.tables || {},
                tableStates: buildTableStates(cfg.tables),
                ...buildManagedDialogRuntimeState(cfg.dialogs, forms),
              }, state || {});
            },
            created(){
              initializeConfiguredForms(forms, {
                initializeArrayGroups: (scope) => this.initializeFormArrayGroups(scope),
              });
            },
            mounted(){
              this.ensureDialogMessageBridge();
              initializeConfiguredForms(forms, {
                registerDependencies: (scope) => this.registerSimpleFormDependencies(scope),
                initializeOptions: (scope) => this.initializeSimpleFormOptions(scope),
                initializeUploads: (scope) => this.initializeSimpleUploadFiles(scope),
              });
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
                  selection: typeof vm.getTableSelection === 'function'
                    ? vm.getTableSelection(actionConfig?.tableKey || null)
                    : [],
                })
              }),
              createSimpleFormMethods({ cfg }),
              {
                validateForm(scope){
                  return this.validateSimpleForm(scope);
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
                handleUploadSuccess(...args){
                  return this.handleSimpleUploadSuccess(...args);
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
          app.mount('#app');
        };
