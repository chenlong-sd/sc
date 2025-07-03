<?php

namespace Sc\Util\Tool\Excel;

use Psr\Http\Message\ResponseInterface;
use Vtiful\Kernel\Excel;

interface ExcelInterface
{
    /**
     * 设置每一列的key
     *
     * @param array $keys 列key，最终的数据会以key的顺序排列
     * @return $this
     */
    public function setColumnsKey(array $keys): static;

    /**
     * 设置数据
     *
     * @param array $data 数据，二维数组
     * @param int $startRowNumber
     * @return void
     */
    public function setData(array $data, int $startRowNumber = 1): void;

    /**
     * 启用数字列
     *
     * @param int $startNumber
     * @return $this
     */
    public function enableNumber(int $startNumber = 1): static;

    /**
     * 设置行数据
     *
     * @param int $row 行号
     * @param array $data
     * @return void
     */
    public function setRowData(int $row, array $data): void;

    /**
     * 合并数据
     *
     * @param string $range 范围 A1:B1
     * @param string $data
     * @return $this
     */
    public function merge(string $range, string $data): static;

    /**
     * 指定范围的对齐方式并设置宽度
     *
     * @param string|int $range 范围 A1:B1 或 指定前 N 列
     * @param float $cellWidth
     * @return $this
     */
    public function alignCenter(string|int $range, float $cellWidth): static;

    /**
     * 添加文本数据
     *
     * @param string $cell 'A1'
     * @param string|array $data 为数组的时候， 以 cell 为起点，依次往后面列写入
     * @param string|null $format
     * @param $formatHandle
     * @return $this
     */
    public function insertTexts(string $cell, string|array $data, string $format = null, $formatHandle = null): static;

    /**
     * 设置头部
     *
     * @param array $headers
     *                      [
     *                           ['title' => '批次号', 'rowNumber' => 2,],
     *                           ['title' => '路线', 'rowNumber' => 2,],
     *                           ['title' => '线路总明细情况', 'columnNumber' => 7, 'children' => ['计划装车', '装车件数', '在车件数', '卸车件数', '实际计划内卸车', '漏扫装车', '系统无编码',]],
     *                      ]
     *   或 ['批次号', '路线', ....]
     * @return void
     */
    public function headers(array $headers): void;

    /**
     * 下载文件
     *
     * @param string|null $filename
     * @param mixed|null $response
     * @return void
     */
    public function download(string $filename = null, mixed $response = null): void;

    /**
     * 获取Excel句柄
     *
     * @return \PhpOffice\PhpSpreadsheet\Spreadsheet|Excel
     */
    public function getExcelHandle(): \PhpOffice\PhpSpreadsheet\Spreadsheet|Excel;

    /**
     * 保存数据到文件
     *
     * @return mixed
     */
    public function save();

    /**
     * 获取数据
     *
     * @param string|null $filepath
     * @param string|null $sheetName
     * @return array
     */
    public function getData(string $filepath = null, string $sheetName = null): array;
}