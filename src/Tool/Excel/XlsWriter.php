<?php

namespace Sc\Util\Tool\Excel;

use Hyperf\HttpServer\Contract\ResponseInterface;
use Vtiful\Kernel\Excel;
use Vtiful\Kernel\Format;

/**
 * 使用扩展 xlswriter
 *
 * Class XlsWriter
 */
class XlsWriter extends Excel implements ExcelInterface
{
    private static array $chars = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];

    private array $columnKeys = [];

    /**
     * @var null|int
     */
    private ?int $startNumber = null;

    public function __construct(array|string $config)
    {
        if (is_string($config)) {
            $config = ['path' => $config];
        }

        parent::__construct($config);
    }

    /**
     * 设置列的key值
     *
     * @param array $keys
     *
     * @return $this
     */
    public function setColumnsKey(array $keys): static
    {
        $this->columnKeys = $keys;

        return $this;
    }

    /**
     * @return $this
     */
    public function enableNumber(int $startNumber = 1): static
    {
        $this->startNumber = $startNumber;

        return $this;
    }

    /**
     * @param int   $row
     * @param array $data
     *
     * @return void
     */
    public function setRowData(int $row, array $data): void
    {
        $column = 0;
        if ($this->startNumber !== null) {
            $this->insertText($row, $column++, $this->startNumber++);
        }
        if ($this->columnKeys) {
            foreach ($this->columnKeys as $key) {
                $this->insertText($row, $column++, $data[$key] ?? '');
            }
            return;
        }

        foreach ($data as $datum) {
            $this->insertText($row, $column++, $datum);
        }
    }

    /**
     * @param array $data
     * @param int   $startRowNumber
     *
     * @return void
     */
    public function setData(array $data, int $startRowNumber = 1): void
    {
        foreach ($data as $datum) {
            $this->setRowData($startRowNumber, $datum);
            $startRowNumber++;
        }
    }

    /**
     * @param string $range
     * @param string $data
     *
     * @return $this
     */
    public function merge(string $range, string $data): static
    {
        return $this->mergeCells($range, $data);
    }


    /**
     * @param string|int $range     范围 A1:B1 或 指定前 N 列
     * @param float  $cellWidth 10
     *
     * @return $this
     */
    public function alignCenter(string|int $range, float $cellWidth): static
    {
        $format = new Format($this->getHandle());
        $align  = $format->align(Format::FORMAT_ALIGN_CENTER);

        if (is_int($range)) {
            $range = sprintf("A1:%s1", self::columnTag($range));
        }
        $this->setColumn($range, $cellWidth, $align->toResource());

        return $this;
    }

    /**
     * @param string       $cell 'A1'
     * @param string|array $data 为数组的时候， 以 cell 为起点，依次往后面列写入
     * @param string|null  $format
     * @param              $formatHandle
     *
     * @return static
     */
    public function insertTexts(string $cell, string|array $data, string $format = null, $formatHandle = null): static
    {
        preg_match('/^([A-Z]+)(\d+)/', $cell, $match);

        $row    = $match[2] - 1;
        $A_Z    = self::$chars;
        $count  = count($A_Z);
        $column = 0;

        foreach (array_reverse(str_split($match[1])) as $index => $letter) {
            $column += $index * $count + array_search($letter, $A_Z);
        }

        $data = is_array($data) ? $data : [$data];

        foreach ($data as $datum) {
            $this->insertText($row, $column++, $datum, $format, $formatHandle);
        }

        return $this;
    }

    /**
     * @param array $headers
     *                     [
     *                          ['title' => '批次号', 'rowNumber' => 2,],
     *                          ['title' => '路线', 'rowNumber' => 2,],
     *                          ['title' => '线路总明细情况', 'columnNumber' => 7, 'children' => ['计划装车', '装车件数', '在车件数', '卸车件数', '实际计划内卸车', '漏扫装车', '系统无编码',]],
     *                     ]
     *
     * @return void
     */
    public function headers(array $headers): void
    {
        if (count($headers) === count($headers, COUNT_RECURSIVE)) {
            $headers = array_map(fn($title) => ['title' => $title], $headers);
        }

        $currentColumn = 0;
        $currentRow    = 1;
        foreach ($headers as $header) {
            $columnNumber = $header['columnNumber'] ?? 1;
            $rowNumber    = $header['rowNumber'] ?? 1;

            $range = sprintf("%s%d:%s%d",
                self::columnTag($currentColumn),
                $currentRow,
                self::columnTag($currentColumn + $columnNumber - 1),
                $currentRow + $rowNumber - 1);

            $this->merge($range, $header['title']);

            if (!empty($header['children'])) {
                $this->childrenHeader($header['children'], $currentRow + $rowNumber, $currentColumn);
            }

            $currentColumn += $columnNumber;
        }
    }

    /**
     * @param array $headers
     * @param int   $currentRow
     * @param int   $currentColumn
     *
     * @return void
     */
    private function childrenHeader(array $headers, int $currentRow, int $currentColumn): void
    {
        foreach ($headers as $header) {
            if (is_string($header)) {
                $columnNumber = $rowNumber = 1;
                $title        = $header;
            } else {
                $columnNumber = $header['columnNumber'] ?? 1;
                $rowNumber    = $header['rowNumber'] ?? 1;
                $title        = $header['title'];
            }

            $range = sprintf("%s%d:%s%d",
                self::columnTag($currentColumn),
                $currentRow,
                self::columnTag($currentColumn + $columnNumber - 1),
                $currentRow + $rowNumber - 1);

            $this->merge($range, $title);

            if (!empty($header['children'])) {
                $this->childrenHeader($header['children'], $currentRow + $rowNumber, $currentColumn);
            }

            $currentColumn += $columnNumber;
        }
    }

    /**
     * @param int  $columnIndex 列索引值
     *
     * @return string
     */
    public static function columnTag(int $columnIndex): string
    {
        return \Sc\Util\Tool\Excel::columnTag($columnIndex);
    }

    /**
     * @param string $filename
     * @param ResponseInterface $response
     * @return void
     */
    public function download(string $filename, $response = null): void
    {
        $filepath = $this->output();

        if ($response) {
            if ($response instanceof ResponseInterface) {
                $response->download($filepath, $filename);
            }
            if (is_callable($response)) {
                $response($filepath);
            }

            return;
        }else{
            header('Content-Disposition: attachment; filename=' . $filename);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');

            @ob_end_flush();
            @ob_implicit_flush();

            $fd = fopen($filepath, 'r');

            while (!feof($fd)) {
                echo fread($fd, 8192);
            }

            fclose($fd);
        }


        @unlink($filepath);
    }

    public function getExcelHandle(): \PhpOffice\PhpSpreadsheet\Spreadsheet|Excel
    {
       return $this;
    }

    public function save(): void
    {
        $filepath = $this->output();
    }

    public function getData(string $filepath, string $sheetName = null): array
    {
        return $this->openFile($filepath)->openSheet($sheetName)->getSheetData();
    }
}