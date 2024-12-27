<?php

namespace Sc\Util\Tool\Excel;

use Psr\Http\Message\ResponseInterface;
use Vtiful\Kernel\Excel;

interface ExcelInterface
{
    public function setColumnsKey(array $keys): static;

    public function setData(array $data, int $startRowNumber = 1): void;

    public function enableNumber(int $startNumber = 1): static;

    public function setRowData(int $row, array $data): void;

    public function merge(string $range, string $data): static;

    public function alignCenter(string|int $range, float $cellWidth): static;

    public function insertTexts(string $cell, string|array $data, string $format = null, $formatHandle = null): static;

    public function headers(array $headers): void;

    /**
     * @param string $filename
     * @param ResponseInterface|null $response
     * @return void
     */
    public function download(string $filename, ResponseInterface $response = null): void;


    public function getExcelHandle(): \PhpOffice\PhpSpreadsheet\Spreadsheet|Excel;

    public function save();

    public function getData(string $filepath, string $sheetName = null): array;
}