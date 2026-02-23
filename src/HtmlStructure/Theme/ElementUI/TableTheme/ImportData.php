<?php

namespace Sc\Util\HtmlStructure\Theme\ElementUI\TableTheme;

use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\VueComponents\Temporary;
use Sc\Util\HtmlStructure\Html\StaticResource;
use Sc\Util\HtmlStructure\Table;

class ImportData
{

    private Temporary      $template;
    private readonly array $importColumns;

    public function __construct(private readonly Table $table)
    {
        $this->importColumns = $this->importColumnsHandle($table->getImportColumns());
        $this->template = Temporary::create("excel-import");
        Html::js()->vue->addComponents($this->template);
    }

    public function render(): Temporary
    {
        Html::js()->load('/js/xlsx.full.min.js');
        Html::js()->load(StaticResource::AXIOS);
        Html::js()->vue->set('import_column_info', $this->importColumns);
        Html::js()->vue->set('import_column_map', $this->importExcelTitleMap());
        Html::js()->vue->set('import_loading', false);
        Html::js()->vue->set('import_file_list', []);
        Html::js()->vue->set('import_preview_data', []);
        Html::js()->vue->set('import_result', null);
        Html::js()->vue->set('import_url', $this->table->getImportUrl());

        $this->downloadImportTemplateMethod();
        $this->importFileChangeMethod();
        $this->removeFileMethod();
        $this->submitMethod();

        $this->template->setContent($this->el());

        return $this->template;
    }

    private function importExcelTitleMap(bool $isPure = false): array
    {
        $map = [];
        foreach ($this->importColumns as $prop => $fieldInfo) {
            if (is_string($fieldInfo)) {
                $map[$prop] = $fieldInfo;
            }elseif (is_array($fieldInfo)){
                $title = $fieldInfo['title'] ?? $prop;
                if (isset($fieldInfo['options']) && !$isPure){
                    $title .= "；可选项：";
                    $title .= implode(",", array_column($fieldInfo['options'], 'label'));
                }

                $map[$prop] = $title;
            }
        }

        return array_flip($map);
    }

    private function downloadImportTemplateMethod(): void
    {
        Html::js()->vue->addMethod('downloadImportTemplate', [], <<<'JS'
            let headers = Object.keys(this.import_column_map);
            let ws = XLSX.utils.aoa_to_sheet([headers]);
            let colWidths = headers.map(h => ({ wch: Math.max(h.length * 2.5, 12) }));
            ws['!cols'] = colWidths;
            let wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'sheet1');
            XLSX.writeFile(wb, '导入模板.xlsx');
        JS);
    }


    private function importFileChangeMethod(): void
    {
        Html::js()->vue->addMethod('importFileChange', ['file', 'fileList'], <<<'JS'
            this.import_file_list = fileList;
            this.import_result = null;
            this.import_preview_data = [];
            if (fileList.length === 0) return;
            let lastFile = fileList[fileList.length - 1];
            let reader = new FileReader();
            reader.onload = (e) => {
                try {
                    let wb = XLSX.read(e.target.result, { type: 'array',cellDates: true });
                    let ws = wb.Sheets[wb.SheetNames[0]];
                    let jsonData = XLSX.utils.sheet_to_json(ws, { defval: '' });
                    let headerMap = {};
                    for (let title in this.import_column_map) {
                       headerMap[title.split('；')[0]] = this.import_column_map[title];
                    }
                    let mapped = jsonData.map((row, idx) => {
                        for (let key in row) {
                            let val = row[key];
                    
                            // 1. 情况 A：已经是 Date 对象（Excel 格式正确的情况）
                            // 2. 情况 B：是字符串但长得像日期（手动输入的文本情况）
                            let dateObj = null;
                    
                            if (val instanceof Date) {
                                dateObj = val;
                            } else if (typeof val === 'string' && val.trim() !== '') {
                                // 尝试解析字符串，看是否能转成合法日期
                                const timestamp = Date.parse(val.replace(/-/g, '/')); 
                                if (!isNaN(timestamp)) {
                                    dateObj = new Date(timestamp);
                                }
                            }
                    
                            // 如果识别到了日期，进行智能格式化
                            if (dateObj) {
                                const Y = dateObj.getFullYear();
                                const M = String(dateObj.getMonth() + 1).padStart(2, '0');
                                const D = String(dateObj.getDate()).padStart(2, '0');
                                const hh = String(dateObj.getHours()).padStart(2, '0');
                                const mm = String(dateObj.getMinutes()).padStart(2, '0');
                                const ss = String(dateObj.getSeconds()).padStart(2, '0');
                    
                                // 核心判断：如果时分秒全为 0，则视为纯日期
                                const isPureDate = hh === '00' && mm === '00' && ss === '00';
                    
                                row[key] = isPureDate 
                                    ? `${Y}-${M}-${D}` 
                                    : `${Y}-${M}-${D} ${hh}:${mm}:${ss}`;
                            }
                        }
                        let obj = { _row: idx + 2 };
                        Object.keys(row).forEach(rawH => {
                            let baseH = rawH.split('；')[0];
                            if (headerMap[baseH] !== undefined) {
                                obj[headerMap[baseH]] = row[rawH] !== undefined ? row[rawH] : '';
                            }
                        });
                        return obj;
                    });
                    this.import_preview_data = mapped;
                } catch (err) {
                    this.$message.error('文件解析失败: ' + err.message);
                    this.import_preview_data = [];
                }
            };
            reader.readAsArrayBuffer(lastFile.raw);
        JS);
    }

    private function removeFileMethod(): void
    {
        Html::js()->vue->addMethod('importFileRemove', ['file', 'fileList'], <<<'JS'
            this.import_file_list = fileList;
            if (fileList.length === 0) {
                this.import_preview_data = [];
            }
        JS);
    }


    private function submitMethod(): void
    {
        Html::js()->vue->addMethod("refreshTableData", JsFunc::anonymous()->code(
            "VueApp.{$this->table->getId()}GetData()"
        ));

        Html::js()->vue->addMethod('submitImport', [], <<<'JS'
            if (this.import_preview_data.length === 0) {
                this.$message.warning('没有可导入的数据');
                return;
            }
            this.import_loading = true;
            this.import_result = null;
            axios({
                url: this.import_url,
                method: 'post',
                data: { rows: this.import_preview_data, import_column_info: this.import_column_info}
            }).then(({ data }) => {
                if (data.code === 200) {
                    this.$message.success(data.msg || '导入成功');
                    this.import_result = data.data || { success_count: 0, fail_count: 0, errors: [] };
                    this.import_file_list = [];
                    this.import_preview_data = [];
                    this.refreshTableData();
                } else {
                    this.$message.error(data.msg || '导入失败');
                    this.import_result = data.data || null;
                }
            }).catch(error => {
                this.$message.error('导入请求失败');
                console.log(error)
            }).finally(() => {
                this.import_loading = false;
            });
        JS);
    }


    private function previewColumnsMake()
    {
        $previewColumns = h([
            h('el-table-column', ['prop' => '_row', 'label' => '行号', 'width' => '60']),
        ]);
        foreach ($this->importColumns as $field => $label) {
            $label = is_array($label) ? ($label['title'] ?? $field) : $label;
            $previewColumns->append(
                h('el-table-column', ['prop' => $field, 'label' => $label, 'min-width' => '100'])
            );
        }
        return $previewColumns;
    }

    private function el()
    {
        return h([
            h('div', ['style' => 'display: flex; gap: 12px; align-items: flex-start;'])->append(
                h('el-upload', [
                    'drag' => true,
                    'accept' => '.xlsx,.xls',
                    'action' => '#',
                    ':auto-upload' => 'false',
                    ':limit' => '1',
                    ':file-list' => 'import_file_list',
                    ':on-change' => 'importFileChange',
                    ':on-remove' => 'importFileRemove',
                    'style' => 'flex: 1;',
                ])->append(
                    h('div', ['style' => 'padding: 8px 0;'])->append(
                        h('div', '将文件拖到此处，或点击选择', ['style' => 'color: #606266; font-size: 13px;']),
                        h('div', '支持 .xlsx / .xls', ['style' => 'color: #909399; font-size: 12px; margin-top: 2px;']),
                        h('hr', ['style' => 'width:50%;margin:auto;']),
                        h('div', '下载的模版标题不要随意更改，否则将无法导入', ['style' => 'color: rgb(225 74 38); font-size: 12px; margin-top: 2px;']),
                        h('div', '有选项的标题，可删除分号以后的选项文字，如：“性别；可选项：男，女” => “性别”', ['style' => 'color: rgb(67 175 81); font-size: 12px; margin-top: 2px;']),
                    )
                ),
                h('div', ['style' => 'display: flex; flex-direction: column; gap: 8px; padding-top: 4px; min-width: 90px;'])->append(
                    h('el-button', '下载模板', [
                        'size' => 'small',
                        'style' => 'width: 100%;',
                        '@click' => 'downloadImportTemplate',
                    ]),
                    h('el-button', '开始导入', [
                        'type' => 'primary',
                        'size' => 'small',
                        'style' => 'width: 100%; margin-left: 0;',
                        '@click' => 'submitImport',
                        ':loading' => 'import_loading',
                        ':disabled' => 'import_preview_data.length === 0',
                    ])
                )
            ),
            h('div', ['v-if' => 'import_preview_data.length > 0', 'style' => 'margin-top: 12px;'])->append(
                h('div', '{{ "数据预览（共 " + import_preview_data.length + " 条）" }}', [
                    'style' => 'margin-bottom: 6px; color: #606266; font-size: 13px;',
                ]),
                h('el-table', [
                    ':data' => 'import_preview_data',
                    'size' => 'small',
                    'max-height' => '260',
                    ':border' => 'true',
                ])->append(
                    $this->previewColumnsMake()
                )
            ),
            h('el-alert', [
                'v-if' => 'import_result',
                ':title' => "'导入完成：成功 ' + (import_result.success_count || 0) + ' 条，失败 ' + (import_result.fail_count || 0) + ' 条'",
                ':type' => "import_result.fail_count > 0 ? 'warning' : 'success'",
                ':closable' => 'false',
                'show-icon' => true,
                'style' => 'margin-top: 12px;',
            ]),
            h('div', [
                'v-if' => 'import_result && import_result.errors && import_result.errors.length > 0',
                'style' => 'margin-top: 8px; max-height: 160px; overflow-y: auto;',
            ])->append(
                h('el-table', [':data' => 'import_result.errors', 'size' => 'small'])->append(
                    h('el-table-column', ['prop' => 'row', 'label' => '行号', 'width' => '60']),
                    h('el-table-column', ['prop' => 'message', 'label' => '错误信息'])
                )
            ),
        ]);
    }

    private function importColumnsHandle(array $importColumns): array
    {
        foreach ($importColumns as &$fieldInfo) {
            if (is_array($fieldInfo) && isset($fieldInfo['options']) && !is_array(current($fieldInfo['options']))){
                $fieldInfo['options'] = array_map(function ($value, $label) {
                    return ['label' => $label, 'value' => $value];
                }, array_keys($fieldInfo['options']), $fieldInfo['options']);
            }
        }

        return $importColumns;
    }
}