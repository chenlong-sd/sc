<?php

namespace Sc\Util\HtmlStructure\Theme\ElementUI\TableTheme;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\TextCharacters;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js;
use Sc\Util\HtmlStructure\Html\Js\Axios;
use Sc\Util\HtmlStructure\Html\Js\JsCode;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\JsService;
use Sc\Util\HtmlStructure\Table;
use Sc\Util\HtmlStructure\Table\Column;
use Sc\Util\ScTool;

class ExportData
{

    public function exportDataGet(Table $table, string $dataVarName, array $query): JsFunc
    {
        Html::js()->load('/js/xlsx.full.min.js');

        $titles  = $showMap = $useKeys = [];
        $formatCode = Js::switch("titlesMap[keys[i]]");
        $columns = $table->getColumns(true);
        $columns = array_filter($columns, fn(Column $column) => $column->getAttr('prop') && $column->getExportExcel()['allow']);
        $sortIndex = 0;
        $columns = array_map(function(Column $column) use (&$sortIndex){
            if ($column->getExportExcel()['sort'] === null) {
                $column->importExcel(true, $sortIndex++);
            }
            return $column;
        }, $columns);
        usort($columns, fn(Column $a, Column $b) => $a->getExportExcel()['sort'] - $b->getExportExcel()['sort']);

        foreach ($columns as $column) {
            $titles[$column->getAttr('prop')] = $column->getAttr("label");
            if ($column->getShow() && in_array($column->getShow()['type'], ['switch', 'tag', 'mapping'])) {
                $options = $column->getShow()['config']['options'];
                $showMap[$column->getAttr('prop')] = count($options) == count($options, COUNT_RECURSIVE)
                    ? $options
                    : array_column($options, 'label', 'value');

                $showMap[$column->getAttr('prop')] = array_map(fn($v) => $v instanceof AbstractHtmlElement ? $v->getContent() : (string)$v, $showMap[$column->getAttr('prop')]);
            }elseif ($column->getFormat()){
                $formatCode->case($column->getAttr('label'), Js::code(
                    $this->exportFormatHandle($column->getFormat(), $useKeys, $column->getAttr('prop'))
                ));
            }
        }
        $formatCode->default(Js::code("row.push(data[j][keys[i]])"));

        $query['is_export'] = 1;

        Html::js()->vue->addMethod("{$dataVarName}excelWrite", JsFunc::anonymous(['data'])->code(
            Js::let("titlesMap", $titles),
            Js::let("showMap", $showMap),
            Js::let("keys", "@Object.keys(titlesMap)"),
            Js::let('exportData', []),
            Js::for("let j = 0; j < data.length; j++")->then(
                Js::let("row", []),
                Js::for("let i = 0; i < keys.length; i++")->then(
                    Js::if("showMap.hasOwnProperty(keys[i])")->then(
                        Js::code("row.push(showMap[keys[i]][data[j][keys[i]]])"),
                    )->else(
                        Js::code($useKeys ? "let { ". implode(', ', array_unique($useKeys)) ." } = data[j];" : ""),
                        $formatCode->toCode()
                    )
                ),
                Js::code('exportData.push(row)')
            ),
            Js::let("worksheet", "@XLSX.utils.json_to_sheet(exportData)"),
            Js::let("workbook", "@XLSX.utils.book_new()"),
            Js::code('XLSX.utils.book_append_sheet(workbook, worksheet, "Sheet1")'),
            Js::code('XLSX.utils.sheet_add_aoa(worksheet, [Object.values(titlesMap)], { origin: "A1" })'),
            Js::code("XLSX.writeFile(workbook, '{$table->getExcelFilename()}.xlsx', { compression: true })")
        ));

        return JsFunc::anonymous()->code(
            Js::let("loading", JsService::loading()),
            Js::let('query', '@{}'),
            Js::if("this.{$dataVarName}Selection.length > 0")->then(
                Js::code("this.{$dataVarName}excelWrite(this.{$dataVarName}Selection)"),
                Js::code("loading.close();"),
                Js::return()
            ),
            Axios::get(
                url: "@this.{$dataVarName}Url()",
                query: $query
            )->then(JsFunc::arrow(["{ data }"], Js::code(
                Js::if('data.code === 200')->then(
                    Js::if("data.data.data.length <= 0")->then(
                        Js::return()
                    ),
                    Js::code("this.{$dataVarName}excelWrite(data.data.data)")
                )->else(
                    Js::code("this.\$message.warning(data.msg)")
                ),
                Js::code("loading.close();"),
            )))
        );
    }

    /**
     * @param AbstractHtmlElement|string $format
     * @param $useKeys
     * @param string $prop
     * @param string $saveVar
     * @return JsCode
     */
    public function exportFormatHandle($format, &$useKeys, string $prop, string $saveVar = 'row'): JsCode
    {
        $code    = Js::code();
        /** @var Js\JsIf $if */
        $if = null;

        $format = El::fictitious()->append($format);
        $format->eachChildren(function (AbstractHtmlElement $element) use ($code, $prop, &$if, &$useKeys, $saveVar){
            if ($element instanceof TextCharacters) {
                $currentCode = $this->exportDataParamHandle($element->getText(), $useKeys);
                $currentCode = preg_replace_callback('/@(\w+)/', function ($matches){
                    return Html::js()->vue->hasVar($matches[1]) ? "this.{$matches[1]}" : $matches[1];
                }, $currentCode);
                $if and $code->then($if);
                $code->then(JsFunc::call("$saveVar.push", strtr($currentCode, ['@' => ''])));
                $if = null;
                return;
            }

            $saveVarTmp = ScTool::random("{$prop}_D_")->get();
            $currentCode = $this->exportFormatHandle(preg_replace('/<(b|span|text|el-text)>([^<]+)?<\/(b|span|text|el-text)>/', '$2', $element->getContent()), $useKeys, $prop, $saveVarTmp);
            $currentCode = trim($currentCode);
            if(!trim($currentCode)){
                return;
            }

            $code->then(Js::let($saveVarTmp, []));
            if (!$element->getAttr('v-for')) {
                $code->then($currentCode);
                $currentCode = "@$saveVarTmp.join()";
            }

            if ($element->getAttr('v-if')) {
                $if and $code->then($if);
                $where = $this->exportFormatWhereHandle($element->getAttr('v-if'), $useKeys);
                $if = Js::if($where)->then(
                    JsFunc::call("$saveVar.push", $currentCode)
                );
            }elseif ($element->getAttr('v-else-if')) {
                $where = $this->exportFormatWhereHandle($element->getAttr('v-else-if'), $useKeys);
                $if->elseIf($where)->then(
                    JsFunc::call("$saveVar.push", $currentCode)
                );
            }elseif ($element->hasAttr('v-else')){
                $if->else(JsFunc::call("$saveVar.push", $currentCode));
            }elseif ($element->getAttr('v-for')){
                if(str_starts_with(trim($element->getAttr('v-for')), '(')){
                    preg_match('/\(\s*(\w+)\s*,\s*(\w+)\)\s*in\s*(@?)(\w+)/', $element->getAttr('v-for'), $match);
                    empty($match[3]) and $useKeys[] = $match[4];
                    $forVarName = empty($match[3]) ? $match[4] : "this.$match[4]";
                }else{
                    preg_match('/\s*(\w+)\s*in\s*(@?)(\S+)/', $element->getAttr('v-for'), $match);
                    if(empty($match[2])){
                        if (preg_match('/^\w+$/', $match[3])) {
                            $forVarName = $useKeys[] = $match[3];
                        }else{
                            preg_match('/^\w+/', strtr($match[3], ['scope.row.' => '']), $match1);
                            $useKeys[] = $match1[0];
                            $forVarName = strtr($match[3], ['scope.row.' => '']);
                        }
                    }else{
                        $forVarName = "this.$match[3]";
                    }
                }

                $for = Js::for("let index in $forVarName")->then(Js::let($match[1], "@{$forVarName}[index]"));
                $for->then($currentCode);
                $code->then($for)
                    ->then("$saveVar.push($saveVarTmp.join('；'));");
            } else {
                $if and $code->then($if);
                $code->then(JsFunc::call("$saveVar.push", $currentCode));
                $if = null;
            }
        });

        $if and $code->then($if);

        return $code;
    }

    public function exportDataParamHandle(string $format, &$useKeys): string
    {
        $format = strtr(strip_tags(strtr($format, ['<=' => '=<'])), ['=<' => '<=']);
        $format = preg_replace('/^\{\{/', '" + (', $format);
        $format = preg_replace('/}}$/', ') + "', $format);
        $format = strtr($format, ['{{' => '" + (', '}}' => ') + "', "\r" => '', "\n" => '']);

        preg_replace_callback('/\((.*)\)/', function ($match) use (&$useKeys) {
            preg_match_all('/(((?<!@|\w|\.|\[])[a-zA-Z]\w*).*?)+/', $match[1], $useKey);
            $useKeys = array_merge($useKeys, array_unique($useKey[0]));
        }, $format);

        $format = strtr($format, ['@item' => 'item']);

        return $this->globalVarHandle($format);
    }

    /**
     * @param string $where
     * @param        $useKeys
     *
     * @return string
     */
    public function exportFormatWhereHandle(string $where, &$useKeys): string
    {
        preg_match_all('/(((?<!@|\w|\.|\[])[a-zA-Z]\w*).*?)+/', $where, $useKey);
        if ($useKey) {
            $useKeys = array_merge($useKeys, array_unique($useKey[0]));
        }

        $where = strtr($where, ['@item' => 'item']);

        return $this->globalVarHandle($where, true);
    }


    /**
     * @param string $format
     * @param bool   $isWhere
     *
     * @return string
     */
    private function globalVarHandle(string $format, $isWhere = false): string
    {
        if (preg_match_all('/@(\w+)/', $format, $vars)) {
            foreach ($vars[1] as $var) {
                if (Html::js()->vue->hasVar($var)) {
                    $format = strtr($format, ['@' . $var => 'this.' . $var]);
                }
                if ($isWhere){
                    $format = strtr($format, ['@' => '']);
                }
            }
        }
        return $format;
    }
}