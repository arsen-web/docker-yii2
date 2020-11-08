<?php

namespace console\excel\reader\base;

use Generator;
use InvalidArgumentException;
use console\excel\storage\StorageCsvFile;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Throwable;

abstract class BaseExcelReader
{
    public const TYPE_XLSX = '.xlsx';
    public const TYPE_CSV = '.csv';

    /**
     * @var string
     */
    protected $prefixFileName = 'excelyator';

    /**
     * @var array
     */
    protected $ignoredFileNameInDir;

    /**
     * @var array
     */
    protected $columnsMap;

    /**
     * @var array
     */
    protected $fileCollection = [];

    /**
     * @var array
     */
    protected $storageCollection = [];

    public function __construct()
    {
        $this->setIgnoredFileNameInDir();
        $this->setColumnsMap();
    }

    /**
     * @return void
     */
    protected function beforeRead(): void
    {
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
                $isHeaders = true;
                $lastIndex = 0;

                while(($row = fgetcsv($fhi, 0, ";")) !== false) {
                    if($isHeaders) {
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

                        $isHeaders = false;
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
     * @return void
     */
    protected function afterRead(): void
    {
        $this->dropTempFile();
    }

    /**
     * @param string $filePath
     * @return self
     */
    public function setPathToXlsxFile(string $filePath): self
    {
        $this->validateFilePath($filePath);

        if(!preg_match('/\.xlsx$/', $filePath)) {
            throw new \yii\base\InvalidArgumentException("Файл `{$filePath}` не сооветствует типу .xlsx");
        }

        $xlsxReader = new Xlsx();
        $xlsxSpreadsheetReader = $xlsxReader->load($filePath);

        $csvWriter = new Csv($xlsxSpreadsheetReader);
        $csvWriter->setDelimiter(';');
        $filePath = $this->changeTypeFile($filePath, static::TYPE_CSV);
        $filePath = $this->addPrefix($filePath);
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
        $this->validateFilePath($filePath);

        if(!preg_match('/\.csv$/', $filePath)) {
            throw new InvalidArgumentException("Файл `{$filePath}` не сооветствует типу .csv");
        }

        $filePath = $this->addPrefix($filePath);

        $this->fileCollection[] = $filePath;

        return $this;
    }

    /**
     * @param string $filePath
     * @return $this
     */
    public function setPathToFile(string $filePath): self
    {
        $this->validateFilePath($filePath);

        $type = $this->getTypeFile($filePath);

        switch($type) {
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
        $this->validateDirectoryPath($directoryPath);

        $dh = opendir($directoryPath);
        while($fileName = readdir($dh)) {
            if(in_array($fileName, $this->ignoredFileNameInDir)) {
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
     * @param array $headers
     * @return array
     */
    protected function generateIndexes(array $headers): array
    {
        $indexes = [];
        foreach($headers as $headerIndex => $header) {
            $header = mb_strtolower(trim($header, ' '));
            foreach($this->columnsMap as $column => $options) {
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
    protected function getIgnoredFileNameInDir(): array
    {
        return ['.', '..', '.gitkeep', '.DS_Store'];
    }

    /**
     * @return void
     */
    protected function setIgnoredFileNameInDir(): void
    {
        $this->ignoredFileNameInDir = $this->getIgnoredFileNameInDir();
    }

    /**
     * @return void
     */
    protected function setColumnsMap(): void
    {
        $this->columnsMap = $this->getColumnsMap();
    }

    /**
     * @param string $pathToDirectory
     */
    protected function validateDirectoryPath(string $pathToDirectory): void
    {
        if(empty($pathToDirectory)) {
            throw new InvalidArgumentException('Директория не указана.');
        }

        if(!file_exists($pathToDirectory)) {
            throw new InvalidArgumentException("Директория `{$pathToDirectory}` не существует.");
        }

        if(!is_dir($pathToDirectory)) {
            throw new InvalidArgumentException('Вы указали не директорию.');
        }
    }

    /**
     * @param string $pathToFile
     */
    protected function validateFilePath(string $pathToFile): void
    {
        if(empty($pathToFile)) {
            throw new InvalidArgumentException('Файл не указан.');
        }

        if(!file_exists($pathToFile)) {
            throw new InvalidArgumentException("Файл `{$pathToFile}` не существует.");
        }

        if(!is_readable($pathToFile)) {
            throw new InvalidArgumentException("Не удалось открыть файл `{$pathToFile}` для чтения.");
        }

        if(!is_file($pathToFile)) {
            throw new InvalidArgumentException('Вы указали не файл.');
        }
    }

    /**
     * @param string $fileName
     * @param string $type
     * @return string
     */
    protected function changeTypeFile(string $fileName, string $type): string
    {
        $fileName = substr($fileName, 0, strrpos($fileName, '.'));

        return "{$fileName}.{$type}";
    }

    /**
     * @param string $fileName
     * @return string
     */
    protected function getTypeFile(string $fileName): string
    {
        return substr($fileName, strrpos($fileName, '.'));
    }

    /**
     * @param string $fileName
     * @return string
     */
    protected function addPrefix(string $fileName): string
    {
        $fileName = explode('/', $fileName);
        $fileName[] = "{$this->prefixFileName}_" . array_pop($fileName);
        $fileName = implode('/', $fileName);

        return $fileName;
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
}
