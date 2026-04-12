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
            readPageLocation,
            readPageQuery,
            registerElementPlusIcons,
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
          const normalizeImportCellValue = (value) => {
            if (value instanceof Date && !Number.isNaN(value.getTime())) {
              const Y = value.getFullYear();
              const M = String(value.getMonth() + 1).padStart(2, '0');
              const D = String(value.getDate()).padStart(2, '0');
              const hh = String(value.getHours()).padStart(2, '0');
              const mm = String(value.getMinutes()).padStart(2, '0');
              const ss = String(value.getSeconds()).padStart(2, '0');

              return hh === '00' && mm === '00' && ss === '00'
                ? `${Y}-${M}-${D}`
                : `${Y}-${M}-${D} ${hh}:${mm}:${ss}`;
            }

            return value ?? '';
          };
          const isImportEmptyRow = (row = []) => {
            if (!Array.isArray(row)) {
              return true;
            }

            return row.every((cell) => {
              if (cell === null || cell === undefined) {
                return true;
              }

              return String(cell).trim() === '';
            });
          };
          const normalizeImportColumns = (columns) => {
            if (!isObject(columns)) {
              return {};
            }

            const normalized = {};
            Object.keys(columns).forEach((field) => {
              const info = columns[field];
              if (typeof info === 'string') {
                normalized[field] = { title: info };
                return;
              }

              if (isObject(info)) {
                normalized[field] = Object.assign({}, info, {
                  title: typeof info.title === 'string' && info.title.trim() !== ''
                    ? info.title.trim()
                    : field,
                });
                return;
              }

              normalized[field] = { title: field };
            });

            return normalized;
          };
          const normalizeImportOptionValue = (value) => {
            if (typeof value !== 'string') {
              return value;
            }

            const normalized = value.trim();
            if (/^-?\d+$/.test(normalized)) {
              return Number.parseInt(normalized, 10);
            }
            if (/^-?\d+\.\d+$/.test(normalized)) {
              return Number.parseFloat(normalized);
            }

            return value;
          };
          const normalizeImportOptionsForPayload = (options) => {
            if (Array.isArray(options)) {
              return options.map((item) => isObject(item) ? Object.assign({}, item) : item);
            }

            if (!isObject(options)) {
              return options;
            }

            return Object.keys(options).map((value) => ({
              label: options[value],
              value: normalizeImportOptionValue(value),
            }));
          };
          const normalizeImportColumnsForPayload = (columns) => {
            if (!isObject(columns)) {
              return {};
            }

            const normalized = {};
            Object.keys(columns).forEach((field) => {
              const info = columns[field];
              if (typeof info === 'string') {
                normalized[field] = info;
                return;
              }

              if (isObject(info)) {
                const normalizedInfo = Object.assign({}, info);
                if (Object.prototype.hasOwnProperty.call(normalizedInfo, 'options')) {
                  normalizedInfo.options = normalizeImportOptionsForPayload(normalizedInfo.options);
                }
                normalized[field] = normalizedInfo;
                return;
              }

              normalized[field] = field;
            });

            return normalized;
          };
          const normalizeImportTitle = (title) => {
            const normalized = String(title ?? '').trim();
            if (normalized === '') {
              return '';
            }

            const baseTitle = normalized.split('；')[0];
            return String(baseTitle ?? normalized).trim();
          };
          const buildImportFieldMap = (columns) => {
            const map = {};
            const normalizedColumns = normalizeImportColumns(columns);

            Object.keys(normalizedColumns).forEach((field) => {
              const info = normalizedColumns[field] || {};
              const title = normalizeImportTitle(info.title || field);
              if (title !== '') {
                map[title] = field;
              }
            });

            return map;
          };
          const pickImportFile = (accept = '.xlsx,.xls,.csv') => {
            return new Promise((resolve) => {
              const input = document.createElement('input');
              input.type = 'file';
              input.accept = typeof accept === 'string' && accept.trim() !== ''
                ? accept
                : '.xlsx,.xls,.csv';
              input.style.display = 'none';

              let settled = false;
              const cleanup = () => {
                window.removeEventListener('focus', handleFocus, true);
                input.removeEventListener('change', handleChange);
                input.removeEventListener('cancel', handleCancel);
                if (input.parentNode) {
                  input.parentNode.removeChild(input);
                }
              };
              const finalize = (file = null) => {
                if (settled) {
                  return;
                }

                settled = true;
                cleanup();
                resolve(file || null);
              };
              const handleChange = () => finalize(input.files?.[0] || null);
              const handleCancel = () => finalize(null);
              const handleFocus = () => {
                window.setTimeout(() => {
                  if (!settled && (!input.files || input.files.length === 0)) {
                    finalize(null);
                  }
                }, 300);
              };

              input.addEventListener('change', handleChange);
              input.addEventListener('cancel', handleCancel);
              window.addEventListener('focus', handleFocus, true);
              document.body.appendChild(input);
              input.click();
            });
          };
          const parseImportFile = async (file, importConfig = {}) => {
            const xlsx = globalThis.XLSX || (typeof window !== 'undefined' ? window?.XLSX : null);
            if (!xlsx?.read || !xlsx?.utils) {
              throw new Error('当前页面未加载 Excel 解析库');
            }

            const buffer = await file.arrayBuffer();
            const workbook = xlsx.read(buffer, {
              type: 'array',
              cellDates: true,
            });
            const sheetNames = Array.isArray(workbook?.SheetNames) ? workbook.SheetNames : [];
            const firstSheetName = sheetNames[0] || '';
            if (firstSheetName === '') {
              throw new Error('Excel 文件中没有可读取的工作表');
            }

            const worksheet = workbook?.Sheets?.[firstSheetName];
            if (!worksheet) {
              throw new Error('Excel 文件解析失败');
            }

            const rawHeaderRow = Number(importConfig?.headerRow || 1);
            const headerRow = Number.isFinite(rawHeaderRow) && rawHeaderRow > 0
              ? Math.floor(rawHeaderRow)
              : 1;
            const aoa = xlsx.utils.sheet_to_json(worksheet, {
              header: 1,
              defval: '',
              raw: false,
              blankrows: false,
            });
            const normalizedRows = Array.isArray(aoa)
              ? aoa.map((row) => Array.isArray(row) ? row.map((cell) => normalizeImportCellValue(cell)) : [])
              : [];
            const headerIndex = Math.max(0, headerRow - 1);
            const headers = Array.isArray(normalizedRows[headerIndex])
              ? normalizedRows[headerIndex].map((cell, index) => {
                const title = normalizeImportTitle(cell);
                return title !== '' ? title : `column_${index + 1}`;
              })
              : [];

            if (headers.length === 0) {
              throw new Error('未识别到导入表头');
            }

            const bodyRows = normalizedRows
              .slice(headerIndex + 1)
              .filter((row) => !isImportEmptyRow(row));
            const importColumns = normalizeImportColumns(importConfig?.columns || {});
            const importColumnPayload = normalizeImportColumnsForPayload(importConfig?.columns || {});
            const titleFieldMap = buildImportFieldMap(importColumns);
            const hasColumnMapping = Object.keys(titleFieldMap).length > 0;
            const rows = bodyRows.map((cells, index) => {
              const row = {
                _row: headerIndex + index + 2,
              };

              headers.forEach((header, index) => {
                const value = cells[index] ?? '';
                const field = hasColumnMapping ? titleFieldMap[normalizeImportTitle(header)] : header;
                if (!field) {
                  return;
                }

                row[field] = value;
              });

              return row;
            }).filter((row) => Object.keys(row || {}).length > 0);

            return {
              rows,
              headers,
              file,
              fileName: file?.name || '',
              fileSize: Number(file?.size || 0),
              fileType: file?.type || '',
              sheetName: firstSheetName,
              columns: importColumnPayload,
            };
          };
          const parseImportJsonText = (jsonText, importConfig = {}) => {
            const normalizedText = typeof jsonText === 'string' ? jsonText.trim() : '';
            if (normalizedText === '') {
              throw new Error('请输入 JSON 数据');
            }

            let parsed;
            try {
              parsed = JSON.parse(normalizedText);
            } catch (error) {
              throw new Error(`JSON 解析失败: ${error?.message || '格式错误'}`);
            }

            if (!Array.isArray(parsed)) {
              throw new Error('JSON 数据必须是数组格式');
            }
            if (parsed.length === 0) {
              throw new Error('JSON 数据为空');
            }

            const importColumns = normalizeImportColumns(importConfig?.columns || {});
            const importColumnPayload = normalizeImportColumnsForPayload(importConfig?.columns || {});
            const fields = Object.keys(importColumns);
            const rows = parsed.map((item, index) => {
              const source = isObject(item) ? item : {};
              const row = {
                _row: index + 1,
              };

              if (fields.length > 0) {
                fields.forEach((field) => {
                  row[field] = source[field] !== undefined ? normalizeImportCellValue(source[field]) : '';
                });
                return row;
              }

              Object.keys(source).forEach((field) => {
                row[field] = normalizeImportCellValue(source[field]);
              });

              return row;
            });
            const headers = fields.length > 0
              ? fields.map((field) => importColumns[field]?.title || field)
              : Object.keys(rows[0] || {}).filter((field) => field !== '_row');

            return {
              rows,
              headers,
              file: null,
              fileName: 'json',
              fileSize: normalizedText.length,
              fileType: 'application/json',
              sheetName: '',
              columns: importColumnPayload,
            };
          };
          const normalizeImportResultErrors = (errors) => {
            if (!Array.isArray(errors)) {
              return [];
            }

            return errors.map((item, index) => {
              if (isObject(item)) {
                const row = item.row ?? item._row ?? item.line ?? item.index ?? index + 1;
                const message = item.message ?? item.msg ?? item.error ?? JSON.stringify(item);
                return { row, message };
              }

              return {
                row: index + 1,
                message: item == null ? '' : String(item),
              };
            });
          };
          const normalizeImportResultPayload = (payload, ok = false) => {
            let candidate = null;
            if (isObject(payload?.data)) {
              candidate = payload.data;
            } else if (
              isObject(payload)
              && (
                Object.prototype.hasOwnProperty.call(payload, 'success_count')
                || Object.prototype.hasOwnProperty.call(payload, 'fail_count')
                || Array.isArray(payload.errors)
              )
            ) {
              candidate = payload;
            }

            if (!isObject(candidate)) {
              return ok
                ? { success_count: 0, fail_count: 0, errors: [] }
                : null;
            }

            return {
              success_count: Number(candidate.success_count ?? candidate.successCount ?? 0),
              fail_count: Number(candidate.fail_count ?? candidate.failCount ?? 0),
              errors: normalizeImportResultErrors(
                candidate.errors
                ?? candidate.error_rows
                ?? candidate.fail_rows
                ?? []
              ),
            };
          };
          const buildImportPreviewColumns = (importConfig = {}, rows = []) => {
            const columns = normalizeImportColumns(importConfig?.columns || {});
            const fields = Object.keys(columns);
            const output = [
              { prop: '_row', label: '行号', width: 72 },
            ];

            if (fields.length > 0) {
              fields.forEach((field) => {
                output.push({
                  prop: field,
                  label: columns[field]?.title || field,
                });
              });

              return output;
            }

            const firstRow = Array.isArray(rows) && rows.length > 0 && isObject(rows[0]) ? rows[0] : {};
            Object.keys(firstRow)
              .filter((field) => field !== '_row')
              .forEach((field) => {
                output.push({
                  prop: field,
                  label: field,
                });
              });

            return output;
          };
          const normalizeImportTemplateFileName = (fileName = null, fallbackTitle = '导入模板') => {
            let normalized = typeof fileName === 'string' ? fileName.trim() : '';
            if (normalized === '') {
              normalized = String(fallbackTitle || '').trim();
            }
            if (normalized === '') {
              normalized = '导入模板';
            }

            normalized = normalized
              .replace(/列表|添加|编辑/g, '')
              .replace(/[\\/:*?"<>|]/g, ' ')
              .replace(/\s+/g, ' ')
              .trim();

            if (normalized === '') {
              normalized = '导入模板';
            }
            if (!/\.xlsx$/i.test(normalized)) {
              normalized += '.xlsx';
            }

            return normalized;
          };
          const downloadImportTemplate = (importConfig = {}, fallbackTitle = '导入') => {
            const xlsx = globalThis.XLSX || (typeof window !== 'undefined' ? window?.XLSX : null);
            if (!xlsx?.utils || typeof xlsx.writeFile !== 'function') {
              throw new Error('当前页面未加载 Excel 导出库');
            }

            const columns = normalizeImportColumns(importConfig?.columns || {});
            const headers = Object.keys(columns).map((field) => columns[field]?.title || field);
            const worksheet = xlsx.utils.aoa_to_sheet([headers]);
            worksheet['!cols'] = headers.map((header) => ({
              wch: Math.max(String(header || '').length * 2.5, 12),
            }));

            const workbook = xlsx.utils.book_new();
            xlsx.utils.book_append_sheet(workbook, worksheet, 'sheet1');
            xlsx.writeFile(
              workbook,
              normalizeImportTemplateFileName(importConfig?.templateFileName, fallbackTitle || document.title || '导入模板')
            );
          };
          const buildImportAiPromptText = (importConfig = {}) => {
            if (typeof importConfig?.aiPromptText === 'string' && importConfig.aiPromptText.trim() !== '') {
              return importConfig.aiPromptText.trim();
            }

            const columns = normalizeImportColumnsForPayload(importConfig?.columns || {});
            const fields = Object.keys(columns);
            if (fields.length === 0) {
              return '';
            }

            const lines = ['请生成10条测试数据，返回JSON数组，每个元素是一个对象。字段说明如下：', ''];
            const example = {};

            fields.forEach((field) => {
              const info = columns[field];
              if (typeof info === 'string') {
                lines.push(`- ${field}（${info}）`);
                example[field] = `${info}示例`;
                return;
              }

              const title = info?.title || field;
              if (Array.isArray(info?.ai_data) && info.ai_data.length > 0) {
                lines.push(`- ${field}（${title}）：参考数据为 ${JSON.stringify(info.ai_data)}`);
                example[field] = info.ai_data[0] ?? `${title}示例`;
                return;
              }

              if (Array.isArray(info?.options) && info.options.length > 0) {
                const labels = info.options
                  .map((item) => item?.label ?? item?.value ?? '')
                  .filter((item) => item !== '');
                if (labels.length > 0) {
                  lines.push(`- ${field}（${title}）：可选值为 ${labels.join('、')}`);
                  example[field] = labels[0];
                  return;
                }
              }

              lines.push(`- ${field}（${title}）`);
              example[field] = `${title}示例`;
            });

            lines.push('');
            lines.push('返回格式示例：');
            lines.push(JSON.stringify([example], null, 2));
            lines.push('');
            lines.push('请直接返回JSON数组，不要有多余文字。');

            return lines.join('\n');
          };
          const copyTextToClipboard = (text) => {
            const normalizedText = typeof text === 'string' ? text : String(text ?? '');
            if (normalizedText === '') {
              return Promise.resolve(false);
            }

            if (navigator?.clipboard?.writeText) {
              return navigator.clipboard.writeText(normalizedText).then(() => true);
            }

            return new Promise((resolve, reject) => {
              try {
                const textarea = document.createElement('textarea');
                textarea.value = normalizedText;
                textarea.setAttribute('readonly', 'readonly');
                textarea.style.position = 'fixed';
                textarea.style.top = '-9999px';
                textarea.style.left = '-9999px';
                document.body.appendChild(textarea);
                textarea.select();
                const success = document.execCommand('copy');
                document.body.removeChild(textarea);
                if (!success) {
                  reject(new Error('copy failed'));
                  return;
                }

                resolve(true);
              } catch (error) {
                reject(error);
              }
            });
          };
          const openImportDialog = ({
            actionLabel = '导入',
            importConfig = {},
            submitAction = async () => null,
          } = {}) => {
            if (!globalThis.Vue?.createApp) {
              ElementPlus.ElMessage.error('当前页面未加载 Vue 运行时');
              return Promise.resolve(null);
            }

            const mountNode = document.createElement('div');
            document.body.appendChild(mountNode);

            return new Promise((resolve) => {
              let vueApp = null;
              let settled = false;
              const dialogTitle = (() => {
                const configuredTitle = typeof importConfig?.dialogTitle === 'string'
                  ? importConfig.dialogTitle.trim()
                  : '';
                return configuredTitle !== '' ? configuredTitle : (String(actionLabel || '导入').trim() || '导入');
              })();
              const cleanup = (value = null) => {
                if (settled) {
                  return;
                }

                settled = true;
                try {
                  if (vueApp && typeof vueApp.unmount === 'function') {
                    vueApp.unmount();
                  }
                } catch (error) {
                }

                if (mountNode.parentNode) {
                  mountNode.parentNode.removeChild(mountNode);
                }

                resolve(value);
              };
              const dialogApp = Vue.createApp({
                data() {
                  return {
                    visible: true,
                    activeMode: 'excel',
                    excelFileList: [],
                    previewRows: [],
                    previewMeta: {},
                    currentImportData: null,
                    jsonText: '',
                    submitting: false,
                    result: null,
                    lastSubmitOutcome: null,
                  };
                },
                computed: {
                  previewColumns() {
                    return buildImportPreviewColumns(importConfig, this.previewRows);
                  },
                  previewCount() {
                    return Array.isArray(this.previewRows) ? this.previewRows.length : 0;
                  },
                  previewLabel() {
                    const count = this.previewCount;
                    const fileName = this.previewMeta?.fileName ? `，来源：${this.previewMeta.fileName}` : '';
                    const sheetName = this.previewMeta?.sheetName ? ` / ${this.previewMeta.sheetName}` : '';
                    return `数据预览（共 ${count} 条${fileName}${sheetName}）`;
                  },
                  allowJsonImport() {
                    return importConfig?.jsonEnabled !== false;
                  },
                  aiPromptText() {
                    return buildImportAiPromptText(importConfig);
                  },
                  showAiPromptButton() {
                    return importConfig?.aiPromptEnabled !== false && this.aiPromptText !== '';
                  },
                  resultErrors() {
                    return Array.isArray(this.result?.errors) ? this.result.errors : [];
                  },
                  resultTitle() {
                    if (!isObject(this.result)) {
                      return '';
                    }

                    return `导入完成：成功 ${this.result.success_count || 0} 条，失败 ${this.result.fail_count || 0} 条`;
                  },
                  resultType() {
                    return Number(this.result?.fail_count || 0) > 0 ? 'warning' : 'success';
                  },
                  resolvedTemplateFileName() {
                    return normalizeImportTemplateFileName(
                      importConfig?.templateFileName,
                      dialogTitle || document.title || '导入模板'
                    );
                  },
                },
                watch: {
                  activeMode(newValue, oldValue) {
                    if (newValue !== oldValue) {
                      this.resetImportState();
                    }
                  },
                },
                methods: {
                  handleClosed() {
                    cleanup(this.lastSubmitOutcome);
                  },
                  closeDialog() {
                    this.visible = false;
                  },
                  resetImportState() {
                    this.previewRows = [];
                    this.previewMeta = {};
                    this.currentImportData = null;
                    this.result = null;
                    this.excelFileList = [];
                    this.jsonText = '';
                  },
                  handleExcelFileRemove(file, fileList) {
                    this.excelFileList = Array.isArray(fileList) ? fileList.slice(-1) : [];
                    if (this.excelFileList.length === 0) {
                      this.previewRows = [];
                      this.previewMeta = {};
                      this.currentImportData = null;
                      this.result = null;
                    }
                  },
                  handleExcelFileChange(file, fileList) {
                    const currentFile = Array.isArray(fileList) && fileList.length > 0
                      ? fileList[fileList.length - 1]
                      : file;

                    this.excelFileList = currentFile ? [currentFile] : [];
                    this.previewRows = [];
                    this.previewMeta = {};
                    this.currentImportData = null;
                    this.result = null;

                    const rawFile = currentFile?.raw || currentFile;
                    if (!rawFile) {
                      return;
                    }

                    parseImportFile(rawFile, importConfig)
                      .then((importData) => {
                        this.currentImportData = importData;
                        this.previewRows = Array.isArray(importData?.rows) ? importData.rows : [];
                        this.previewMeta = {
                          fileName: importData?.fileName || rawFile?.name || '',
                          sheetName: importData?.sheetName || '',
                          headers: Array.isArray(importData?.headers) ? importData.headers : [],
                        };

                        if (this.previewRows.length === 0) {
                          ElementPlus.ElMessage.warning('导入文件中没有可提交的数据');
                        }
                      })
                      .catch((error) => {
                        this.previewRows = [];
                        this.previewMeta = {};
                        this.currentImportData = null;
                        const message = error?.message || '文件解析失败';
                        if (message) {
                          ElementPlus.ElMessage.error(message);
                        }
                      });
                  },
                  handleDownloadTemplate() {
                    try {
                      downloadImportTemplate(importConfig, dialogTitle || document.title || '导入模板');
                    } catch (error) {
                      const message = error?.message || '模板下载失败';
                      if (message) {
                        ElementPlus.ElMessage.error(message);
                      }
                    }
                  },
                  handleCopyAiPrompt() {
                    const text = this.aiPromptText;
                    if (!text) {
                      ElementPlus.ElMessage.warning('当前导入列尚未生成可复制的 AI 提示词');
                      return;
                    }

                    copyTextToClipboard(text)
                      .then(() => {
                        ElementPlus.ElMessage.success('AI提示词已复制到剪贴板');
                      })
                      .catch(() => {
                        ElementPlus.ElMessage.error('AI提示词复制失败');
                      });
                  },
                  handleParseJson() {
                    try {
                      const importData = parseImportJsonText(this.jsonText, importConfig);
                      this.currentImportData = importData;
                      this.previewRows = Array.isArray(importData?.rows) ? importData.rows : [];
                      this.previewMeta = {
                        fileName: 'json',
                        sheetName: '',
                        headers: Array.isArray(importData?.headers) ? importData.headers : [],
                      };
                      this.result = null;
                    } catch (error) {
                      this.previewRows = [];
                      this.previewMeta = {};
                      this.currentImportData = null;
                      const message = error?.message || 'JSON 解析失败';
                      if (message) {
                        ElementPlus.ElMessage.error(message);
                      }
                    }
                  },
                  handleSubmitImport() {
                    if (!Array.isArray(this.currentImportData?.rows) || this.currentImportData.rows.length === 0) {
                      ElementPlus.ElMessage.warning('没有可导入的数据');
                      return Promise.resolve(null);
                    }

                    this.submitting = true;
                    return Promise.resolve(submitAction(this.currentImportData))
                      .then((outcome) => {
                        if (!outcome) {
                          return null;
                        }

                        this.lastSubmitOutcome = outcome;
                        this.result = isObject(outcome.result) ? outcome.result : null;

                        if (outcome.ok) {
                          this.previewRows = [];
                          this.previewMeta = {};
                          this.currentImportData = null;
                          this.excelFileList = [];
                          this.jsonText = '';
                        }

                        return outcome;
                      })
                      .finally(() => {
                        this.submitting = false;
                      });
                  },
                },
                template: `
                  <el-dialog
                    v-model="visible"
                    :title="dialogTitle"
                    width="920px"
                    append-to-body
                    destroy-on-close
                    :close-on-click-modal="false"
                    :lock-scroll="false"
                    @closed="handleClosed"
                  >
                    <el-tabs v-model="activeMode" class="sc-v2-import-dialog__tabs">
                      <el-tab-pane label="Excel导入" name="excel">
                        <div style="display:flex;gap:12px;align-items:flex-start;">
                          <el-upload
                            drag
                            action="#"
                            :auto-upload="false"
                            :show-file-list="true"
                            :file-list="excelFileList"
                            :accept="importConfig.accept || '.xlsx,.xls,.csv'"
                            :on-change="handleExcelFileChange"
                            :on-remove="handleExcelFileRemove"
                            style="flex:1;"
                          >
                            <div style="padding:10px 0;">
                              <div style="color:#606266;font-size:13px;">将文件拖到此处，或点击选择</div>
                              <div style="color:#909399;font-size:12px;margin-top:4px;">支持 .xlsx / .xls / .csv</div>
                              <div style="color:#e14a26;font-size:12px;margin-top:8px;">下载的模板标题不要随意更改，否则将无法导入</div>
                            </div>
                          </el-upload>
                          <div style="display:flex;flex-direction:column;gap:8px;min-width:120px;">
                            <el-button size="small" @click="handleDownloadTemplate">下载模板</el-button>
                            <el-text size="small" type="info">模板文件：{{ resolvedTemplateFileName }}</el-text>
                          </div>
                        </div>
                      </el-tab-pane>
                      <el-tab-pane v-if="allowJsonImport" label="JSON导入" name="json">
                        <el-input
                          v-model="jsonText"
                          type="textarea"
                          :rows="8"
                          placeholder="请输入 JSON 数组，例如：[{&quot;name&quot;:&quot;张三&quot;}]"
                        />
                        <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap;">
                          <el-button v-if="showAiPromptButton" size="small" @click="handleCopyAiPrompt">复制AI提示词</el-button>
                          <el-button size="small" @click="handleParseJson">解析JSON</el-button>
                        </div>
                      </el-tab-pane>
                    </el-tabs>

                    <div v-if="previewCount > 0" style="margin-top:16px;">
                      <div style="margin-bottom:8px;color:#606266;font-size:13px;">{{ previewLabel }}</div>
                      <el-table
                        :data="previewRows"
                        size="small"
                        max-height="260"
                        border
                      >
                        <el-table-column
                          v-for="column in previewColumns"
                          :key="column.prop"
                          :prop="column.prop"
                          :label="column.label"
                          :width="column.width"
                          min-width="120"
                          show-overflow-tooltip
                        />
                      </el-table>
                    </div>

                    <el-alert
                      v-if="result"
                      :title="resultTitle"
                      :type="resultType"
                      :closable="false"
                      show-icon
                      style="margin-top:16px;"
                    />

                    <div v-if="resultErrors.length > 0" style="margin-top:8px;max-height:180px;overflow-y:auto;">
                      <el-table :data="resultErrors" size="small">
                        <el-table-column prop="row" label="行号" width="72" />
                        <el-table-column prop="message" label="错误信息" min-width="200" show-overflow-tooltip />
                      </el-table>
                    </div>

                    <template #footer>
                      <div style="display:flex;justify-content:flex-end;gap:8px;">
                        <el-button :disabled="submitting" @click="closeDialog">关闭</el-button>
                        <el-button
                          type="primary"
                          :loading="submitting"
                          :disabled="previewCount === 0"
                          @click="handleSubmitImport"
                        >
                          开始导入
                        </el-button>
                      </div>
                    </template>
                  </el-dialog>
                `,
                setup() {
                  return {
                    dialogTitle,
                    importConfig,
                  };
                },
              });

              dialogApp.use(ElementPlus);
              registerElementPlusIcons(dialogApp);
              vueApp = dialogApp;
              dialogApp.mount(mountNode);
            });
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
              const pageLocation = readPageLocation();
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
                import: null,
                formScope: explicitActionFormScope,
                query: pageQuery,
                mode: resolveRuntimePageMode(),
                page: {
                  url: pageLocation.url || '',
                  href: pageLocation.href || '',
                  path: pageLocation.path || '',
                  pathname: pageLocation.pathname || '',
                  search: pageLocation.search || '',
                  hash: pageLocation.hash || '',
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
              const resolveImportConfig = () => {
                return isObject(resolvedActionConfig.import) ? resolvedActionConfig.import : {};
              };
              const usesImportFlow = () => resolveImportConfig().enabled === true;
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

                if (usesImportFlow()) {
                  return '正在导入，请稍后...';
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
              const buildDefaultImportPayload = () => {
                if (!usesImportFlow() || !isObject(context.import)) {
                  return {};
                }

                const importConfig = resolveImportConfig();
                const payload = {};
                const rowsKey = typeof importConfig.rowsKey === 'string' && importConfig.rowsKey.trim() !== ''
                  ? importConfig.rowsKey.trim()
                  : 'rows';
                payload[rowsKey] = Array.isArray(context.import.rows)
                  ? cloneRequestValue(context.import.rows)
                  : [];

                const columnInfoKey = typeof importConfig.columnInfoKey === 'string' && importConfig.columnInfoKey.trim() !== ''
                  ? importConfig.columnInfoKey.trim()
                  : null;
                if (columnInfoKey) {
                  payload[columnInfoKey] = cloneRequestValue(context.import.columns || {});
                }

                return payload;
              };
              const resolveRequestPayload = () => {
                const payload = usesFormPayload()
                  ? context.cloneFormModel(resolveConfiguredFormScope('payloadScope'))
                  : resolveContextValue(resolvedActionConfig.request.query || {}, context);

                if (!usesImportFlow()) {
                  return payload;
                }

                const isDefaultEmptyCustomPayload = Array.isArray(resolvedActionConfig.request?.query)
                  && resolvedActionConfig.request.query.length === 0;
                if (isObject(payload)) {
                  return Object.assign({}, buildDefaultImportPayload(), payload);
                }
                if (payload === null || payload === undefined || isDefaultEmptyCustomPayload) {
                  return buildDefaultImportPayload();
                }

                return payload;
              };
              const perform = ({ importData = null, suppressGlobalLoading = false } = {}) => {
                if (actionLoading[resolvedActionConfig.key]) {
                  return Promise.resolve(null);
                }

                context.import = usesImportFlow() && isObject(importData) ? importData : null;
                context.response = null;
                context.payload = null;
                context.error = null;

                let loadingInstance = null;
                actionLoading[resolvedActionConfig.key] = true;
                const loadingText = suppressGlobalLoading ? null : resolveLoadingText();
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
                            context.response = response;
                            const responsePayload = extractPayload(response);
                            let payload = null;
                            try {
                              payload = ensureSuccess(
                                responsePayload,
                                resolveErrorMessage(responsePayload)
                              );
                            } catch (error) {
                              error.response = response;
                              error.responsePayload = responsePayload;
                              throw error;
                            }

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
                            const failurePayload = error?.responsePayload ?? extractPayload(error?.response);
                            const message = error?.message || resolveMessage(
                              failurePayload,
                              resolveErrorMessage(failurePayload)
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
              const submitImportFromDialog = (importData) => {
                return confirmAction(resolvedActionConfig.confirmText, () => {
                  return perform({
                    importData,
                    suppressGlobalLoading: true,
                  }).then((payload) => {
                    const responsePayload = context?.error?.responsePayload ?? extractPayload(context?.response);
                    const ok = payload !== null && !context.error;

                    if (!ok && !context.error && !context.response) {
                      return null;
                    }

                    const normalizedPayload = payload ?? responsePayload ?? null;
                    return {
                      ok,
                      payload: normalizedPayload,
                      result: normalizeImportResultPayload(normalizedPayload, ok),
                      error: context.error,
                    };
                  });
                });
              };

              return emitConfiguredEvent(resolvedActionConfig, 'click', context)
                .then((results) => {
                  if (isEventCanceled(results)) {
                    return null;
                  }

                  if (usesImportFlow()) {
                    return openImportDialog({
                      actionLabel: resolvedActionConfig.label || '导入',
                      importConfig: resolveImportConfig(),
                      submitAction: submitImportFromDialog,
                    });
                  }

                  return confirmAction(resolvedActionConfig.confirmText, () => perform());
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
