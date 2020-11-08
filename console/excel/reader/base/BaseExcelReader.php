<?php

namespace console\excel\reader\base;

use console\excel\helpers\FileHelper;
use Generator;
use InvalidArgumentException;
use console\excel\storage\StorageCsvFile;
use LogicException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Throwable;

abstract class BaseExcelReader
{
    /**
     * @var string
     */
    public const TYPE_XLSX = '.xlsx';

    /**
     * @var string
     */
    public const TYPE_CSV = '.csv';

    /**
     * @var string
     */
    protected $prefixFileName = 'excelyator';

    /**
     * @var array
     */
    protected $ignoreFileNamesInDir;

    /**
     * @var array
     */
    protected $fileCollection = [];

    /**
     * @var array
     */
    protected $storageCollection = [];

    /**
     * @var bool
     */
    protected static $isReadHeaders = true;

    public function __construct()
    {
        $this->setIgnoreFileNamesInDir();
    }

    /**
     * @return BaseExcelReader
     * @throws Throwable
     */
    public function read(): self
    {
        try {
            foreach(static::generator($this->fileCollection) as $fileName) {
                $storage = new StorageCsvFile();

                $fhi = fopen($fileName, 'r');

                $storage->setFileName($fileName);
                $lastIndex = 0;

                while(($row = fgetcsv($fhi, 0, ';')) !== false) {
                    if(static::$isReadHeaders) {
                        foreach($row as $index => $col) {
                            if(!empty($col)) {
                                $lastIndex = $index + 1;
                                continue;
                            }
                            break;
                        }

                        $row = array_slice($row, 0, $lastIndex);
                        $storage->setHeaders($row);

                        $indexes = $this->generateIndexes($row);

                        $storage->setRowsIdx($indexes);

                        static::$isReadHeaders = false;
                        continue;
                    }
                    $row = array_slice($row, 0, $lastIndex);

                    $isAddRow = false;
                    foreach(static::generator($row) as $col) {
                        if(!empty($col)) {
                            $isAddRow = true;
                        }
                    }

                    if($isAddRow) {
                        $storage->addRow($row);
                    }
                }

                $this->storageCollection[] = $storage;
            }

            $this->afterRead();
        } catch(Throwable $e) {
            $this->dropTempFile();

            throw $e;
        }

        return $this;
    }

    /**
     * @param string $filePath
     * @return self
     */
    public function setPathToXlsxFile(string $filePath): self
    {
        FileHelper::validateFilePath($filePath);

        if(!preg_match('/\.xlsx$/', $filePath)) {
            throw new \yii\base\InvalidArgumentException("Файл `{$filePath}` не сооветствует типу .xlsx");
        }

        $xlsxReader = new Xlsx();
        $xlsxSpreadsheetReader = $xlsxReader->load($filePath);

        $csvWriter = new Csv($xlsxSpreadsheetReader);
        $csvWriter->setDelimiter(';');
        $filePath = FileHelper::changeTypeFile($filePath, static::TYPE_CSV, $this->prefixFileName);
        $csvWriter->save($filePath);

        $this->fileCollection[] = $filePath;

        return $this;
    }

    /**
     * @param string $filePath
     * @return self
     */
    public function setPathToCsvFile(string $filePath): self
    {
        FileHelper::validateFilePath($filePath);

        if(!preg_match('/\.csv$/', $filePath)) {
            throw new InvalidArgumentException("Файл `{$filePath}` не сооветствует типу .csv");
        }

        $filePath = FileHelper::addPrefix($filePath, $this->prefixFileName);

        $this->fileCollection[] = $filePath;

        return $this;
    }

    /**
     * @param string $filePath
     * @return $this
     */
    public function setPathToFile(string $filePath): self
    {
        FileHelper::validateFilePath($filePath);

        switch(FileHelper::getTypeFile($filePath)) {
            case self::TYPE_XLSX:
                $this->setPathToXlsxFile($filePath);
                break;
            case self::TYPE_CSV:
                $this->setPathToCsvFile($filePath);
                break;
            default:
                throw new InvalidArgumentException("Файл `{$filePath}` не поддерживается");
        }

        return $this;
    }

    /**
     * @param string $directoryPath
     * @return $this
     */
    public function setPathToDirectory(string $directoryPath): self
    {
        FileHelper::validateDirectoryPath($directoryPath);

        $dh = opendir($directoryPath);
        while($fileName = readdir($dh)) {
            if(in_array($fileName, $this->ignoreFileNamesInDir)) {
                continue;
            }

            $this->setPathToFile("{$directoryPath}/{$fileName}");
        }

        return $this;
    }

    /**
     * @return array
     */
    abstract protected function getColumnsMap(): array;

    /**
     * @return void
     */
    protected function beforeRead(): void
    {
    }

    /**
     * @return void
     */
    protected function afterRead(): void
    {
        $this->dropTempFile();
    }

    /**
     * @param array $headers
     * @return array
     */
    protected function generateIndexes(array $headers): array
    {
        $columnsMap = $this->getColumnsMap();

        if(empty($columnsMap)){
            throw new LogicException('Маппинг колонок для записи индексов пустой');
        }

        $indexes = [];
        foreach($headers as $headerIndex => $header) {
            $header = mb_strtolower(trim($header, ' '));

            foreach($columnsMap as $column => $options) {

                foreach($options as $option) {
                    $option = mb_strtolower(trim($option, ' '));

                    if($header === $option) {
                        $indexes[$column] = $headerIndex;
                    }

                }

            }
        }

        return $indexes;
    }

    /**
     * @return array
     */
    protected function getIgnoreFileNamesInDir(): array
    {
        return ['.', '..', '.gitkeep', '.DS_Store'];
    }

    /**
     * @return void
     */
    protected function setIgnoreFileNamesInDir(): void
    {
        $this->ignoreFileNamesInDir = $this->getIgnoreFileNamesInDir();
    }

    /**
     * @return void
     */
    protected function dropTempFile(): void
    {
        /**
         * @var StorageCsvFile $storage
         */
        foreach(static::generator($this->fileCollection) as $file) {
            unlink($file);
        }
    }

    /**
     * @param array $array
     * @return Generator
     */
    protected static function generator(array $array): Generator
    {
        if(is_iterable($array)) {
            foreach($array as $item) {
                yield $item;
            }
        }
    }
}
