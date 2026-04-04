        (function(state, cfg){
          const {
            buildFormsContext,
            buildManagedDialogRuntimeState,
            clone,
            extractPayload,
            ensureSuccess,
            initializeConfiguredForms,
            resolveMessage,
          } = globalThis.__SC_V2_RUNTIME_HELPERS__;
          const forms = cfg.forms || {};

          const createRequestActionMethods = globalThis.__SC_V2_CREATE_REQUEST_ACTION_METHODS__;
          const createSimpleFormMethods = globalThis.__SC_V2_CREATE_SIMPLE_FORM_METHODS__;
          const createSimpleDialogMethods = globalThis.__SC_V2_CREATE_SIMPLE_DIALOG_METHODS__;
          const createSimpleTableMethods = globalThis.__SC_V2_CREATE_SIMPLE_TABLE_METHODS__;
          const app = Vue.createApp({
            data(){
              return Object.assign({
                actionLoading: {},
                tableSelection: [],
                ...buildManagedDialogRuntimeState(cfg.dialogs, forms),
              }, state || {});
            },
            mounted(){
              this.ensureDialogMessageBridge();
              initializeConfiguredForms(forms, {
                registerDependencies: (scope) => this.registerSimpleFormDependencies(scope),
                initializeOptions: (scope) => this.initializeSimpleFormOptions(scope),
                initializeUploads: (scope) => this.initializeSimpleUploadFiles(scope),
              });
            },
            methods: Object.assign(
              {},
              createRequestActionMethods({
                getBaseContext: (vm) => ({
                  forms: buildFormsContext(vm, forms),
                  selection: Array.isArray(vm.tableSelection) ? vm.tableSelection : [],
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
              createSimpleTableMethods()
            )
          });
          app.use(ElementPlus, { locale: ElementPlusLocaleZhCn });
          app.mount('#app');
        })(__SC_V2_STATE__, __SC_V2_CONFIG__);
