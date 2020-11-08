<?php

namespace console\excel\storage;

use InvalidArgumentException;

class StorageCsvFile
{
    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var array
     */
    protected $headers;

    /**
     * @var array
     */
    protected $rows;

    /**
     * @var array
     */
    protected $rowsIdx;

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    public function addHeader(string $header): void
    {
        $this->headers[] = $header;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setRows(array $rows): void
    {
        $this->rows = $rows;
    }

    public function addRow(array $row): void
    {
        $this->rows[] = $row;
    }

    public function dropRows(): void
    {
        $this->rows = [];
    }

    public function getRows(): array
    {
        return $this->rows;
    }

    public function setRowsIdx(array $indexes): void
    {
        $this->rowsIdx = $indexes;
    }

    public function getRowsIdx(): array
    {
        return $this->rowsIdx;
    }

    public function getRowIdx($index)
    {
        $indexes = $this->getRowsIdx();

        if(isset($indexes[$index])) {
            return $indexes[$index];
        }

        throw new InvalidArgumentException("Индекс `$index` не существует");
    }
}
