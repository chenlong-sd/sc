        (function(state, cfg){
          const {
            clone,
            extractPayload,
            ensureSuccess,
            getByPath,
            normalizeOption,
            normalizeUploadFiles,
            resolveMessage,
          } = globalThis.__SC_V2_RUNTIME_HELPERS__;

          const createRequestActionMethods = globalThis.__SC_V2_CREATE_REQUEST_ACTION_METHODS__;
          const createSimpleFormMethods = globalThis.__SC_V2_CREATE_SIMPLE_FORM_METHODS__;
          const createSimpleDialogMethods = globalThis.__SC_V2_CREATE_SIMPLE_DIALOG_METHODS__;
          const createSimpleTableMethods = globalThis.__SC_V2_CREATE_SIMPLE_TABLE_METHODS__;
          const buildFormsContext = (vm) => {
            const forms = {};

            Object.keys(cfg?.forms || {}).forEach((scope) => {
              const modelVar = cfg.forms?.[scope]?.modelVar;
              forms[scope] = modelVar ? (vm[modelVar] || {}) : {};
            });

            return forms;
          };
          const buildOptionState = (configs) => {
            const output = {};
            Object.keys(configs || {}).forEach((fieldName) => {
              const fieldCfg = configs[fieldName] || {};
              output[fieldName] = Array.isArray(fieldCfg.initialOptions)
                ? fieldCfg.initialOptions.map((item, index) => normalizeOption(item, fieldCfg, index))
                : [];
            });
            return output;
          };
          const buildFlagState = (configs, initialValue = false) => {
            const output = {};
            Object.keys(configs || {}).forEach((fieldName) => {
              output[fieldName] = initialValue;
            });
            return output;
          };
          const buildUploadFileState = (configs, model) => {
            const output = {};
            Object.keys(configs || {}).forEach((fieldName) => {
              output[fieldName] = normalizeUploadFiles(getByPath(model, fieldName), configs[fieldName] || {});
            });
            return output;
          };
          const buildDialogState = (dialogs, factory) => {
            const output = {};
            Object.keys(dialogs || {}).forEach((dialogKey) => {
              output[dialogKey] = factory(dialogs[dialogKey] || {}, dialogKey);
            });
            return output;
          };
          const buildDialogTitleState = (dialogs) => {
            const output = {};
            Object.keys(dialogs || {}).forEach((dialogKey) => {
              output[dialogKey] = dialogs[dialogKey]?.title || '';
            });
            return output;
          };

          const app = Vue.createApp({
            data(){
              return Object.assign({
                actionLoading: {},
                tableSelection: [],
                dialogForms: buildDialogState(cfg.dialogs, (dialogCfg) => clone(dialogCfg.defaults || {})),
                dialogInitials: buildDialogState(cfg.dialogs, (dialogCfg) => clone(dialogCfg.defaults || {})),
                dialogRules: buildDialogState(cfg.dialogs, (dialogCfg) => dialogCfg.rules || {}),
                dialogOptions: buildDialogState(cfg.dialogs, (dialogCfg) => buildOptionState(dialogCfg.remoteOptions || {})),
                dialogOptionLoading: buildDialogState(cfg.dialogs, (dialogCfg) => buildFlagState(dialogCfg.remoteOptions || {})),
                dialogOptionLoaded: buildDialogState(cfg.dialogs, (dialogCfg) => buildFlagState(dialogCfg.remoteOptions || {})),
                dialogUploadFiles: buildDialogState(cfg.dialogs, (dialogCfg) => buildUploadFileState(dialogCfg.uploads || {}, dialogCfg.defaults || {})),
                dialogVisible: buildDialogState(cfg.dialogs, () => false),
                dialogMode: buildDialogState(cfg.dialogs, () => 'create'),
                dialogRows: buildDialogState(cfg.dialogs, () => null),
                dialogLoading: buildDialogState(cfg.dialogs, () => false),
                dialogSubmitting: buildDialogState(cfg.dialogs, () => false),
                dialogTitles: buildDialogTitleState(cfg.dialogs),
                dialogIframeUrls: buildDialogState(cfg.dialogs, () => ''),
              }, state || {});
            },
            mounted(){
              Object.keys(cfg?.forms || {}).forEach((scope) => {
                this.registerSimpleFormDependencies(scope);
                this.initializeSimpleFormOptions(scope);
                this.initializeSimpleUploadFiles(scope);
              });

              Object.keys(cfg?.dialogs || {}).forEach((dialogKey) => {
                const scope = 'dialog:' + dialogKey;
                this.registerSimpleFormDependencies(scope);
                this.initializeSimpleUploadFiles(scope);
              });
            },
            methods: Object.assign(
              {},
              createRequestActionMethods({
                getBaseContext: (vm) => ({
                  forms: buildFormsContext(vm),
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
                buildFormsContext,
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
