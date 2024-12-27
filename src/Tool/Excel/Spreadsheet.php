<?php

namespace Sc\Util\Tool\Excel;
use Hyperf\HttpMessage\Stream\SwooleFileStream;
use Hyperf\HttpServer\Contract\ResponseInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Sc\Util\Tool\Excel;

/**
 * 使用 PhpSpreadsheet
 * phpoffice/phpspreadsheet
 */
class Spreadsheet implements ExcelInterface
{
    private string|array                          $filepath;
    private \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet;
    private array                                 $columnKeys = [];
    private ?int                                   $startNumber = null;

    public function __construct(array|string $config)
    {
        $this->filepath = $config;

        $this->spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    }

    public function setColumnsKey(array $keys): static
    {
        $this->columnKeys = $keys;

        return $this;
    }

    public function setData(array $data, int $startRowNumber = 1): void
    {
        foreach ($data as $datum) {
            $this->setRowData($startRowNumber, $datum);
            $startRowNumber++;
        }
    }

    public function enableNumber(int $startNumber = 1): static
    {
        $this->startNumber = $startNumber;

        return $this;
    }

    public function setRowData(int $row, array $data): void
    {
        $column = 0;

        if ($this->startNumber !== null) {
            $this->spreadsheet->getActiveSheet()
                ->setCellValue(Excel::columnTag($column++) . $row, $this->startNumber++);
        }

        if ($this->columnKeys) {
            foreach ($this->columnKeys as $key) {
                $this->spreadsheet->getActiveSheet()
                    ->setCellValue(Excel::columnTag($column++) . $row, $data[$key] ?? '');
            }
            return;
        }

        foreach ($data as $value) {
            $this->spreadsheet->getActiveSheet()
                ->setCellValue(Excel::columnTag($column++) . $row, $value);
        }
    }

    public function merge(string $range, string $data): static
    {
        $this->spreadsheet->getActiveSheet()
            ->mergeCells($range)
            ->setCellValue(explode(":", $range)[0], $data);

        return $this;
    }

    /**
     * @param string|int $range     范围 A1:B1 或 指定前 N 列
     * @param float  $cellWidth 10
     *
     * @return $this
     */
    public function alignCenter(int|string $range, float $cellWidth): static
    {
        if (is_int($range)) {
            $range = sprintf("A1:%s1", Excel::columnTag($range - 1));
        }

        $this->spreadsheet->getActiveSheet()
            ->getStyle($range)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        [$start, $end] = explode(":", $range);
        $start = preg_replace('/\d/', '', $start);
        $end   = preg_replace('/\d/', '', $end);
        for ($i = 0;; $i++) {
            if ($start == Excel::columnTag($i)) {
                $start = null;
            }

            $this->spreadsheet->getActiveSheet()
                ->getColumnDimension(Excel::columnTag($i))
                ->setWidth($cellWidth);

            if ($start == null && $end == Excel::columnTag($i)) {
                break;
            }
        }
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
    public function insertTexts(string $cell, array|string $data, string $format = null, $formatHandle = null): static
    {
        $data = is_array($data) ? array_values($data) : [$data];
        preg_match('/(\w+)(\d+)/', $cell, $matches);

        $column = $matches[1] ?? 'A';
        $row    = $matches[2] ?? 1;

        foreach ($data as $index => $value) {
            $this->spreadsheet->getActiveSheet()->setCellValue($column . ($row + $index), $data);
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
                Excel::columnTag($currentColumn),
                $currentRow,
                Excel::columnTag($currentColumn + $columnNumber - 1),
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
                Excel::columnTag($currentColumn),
                $currentRow,
                Excel::columnTag($currentColumn + $columnNumber - 1),
                $currentRow + $rowNumber - 1);

            $this->merge($range, $title);

            if (!empty($header['children'])) {
                $this->childrenHeader($header['children'], $currentRow + $rowNumber, $currentColumn);
            }

            $currentColumn += $columnNumber;
        }
    }

    /**
     * @param string $filename
     * @param ResponseInterface $response
     * @return void
     */
    public function download(string $filename, $response = null): void
    {
        $this->save();

        if ($response) {
            if ($response instanceof ResponseInterface) {
                $response->download($this->filepath, $filename);
            }
            if (is_callable($response)) {
                $response($this->filepath);
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

            $fd = fopen($this->filepath, 'r');

            while (!feof($fd)) {
                echo fread($fd, 8192);
            }

            fclose($fd);
        }


        @unlink($this->filepath);
    }

    public function getExcelHandle(): \PhpOffice\PhpSpreadsheet\Spreadsheet|\Vtiful\Kernel\Excel
    {
        return $this->spreadsheet;
    }

    public function save(): void
    {
        $xlsx = new Xlsx($this->spreadsheet);
        $xlsx->save($this->filepath);
    }

    public function getData(string $filepath, string $sheetName = null): array
    {
        $this->spreadsheet = IOFactory::load($this->filepath . $filepath);
        return $sheetName
            ? $this->spreadsheet->getSheetByName($sheetName)->toArray()
            : $this->spreadsheet->getActiveSheet()->toArray();
    }
}