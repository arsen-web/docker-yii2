<?php

namespace console\excel\helpers;

use InvalidArgumentException;

class FileHelper
{
    /**
     * @param string $fileName
     * @return string
     */
    public static function getTypeFile(string $fileName): string
    {
        return substr($fileName, strrpos($fileName, '.'));
    }

    /**
     * @param string $fileName
     * @param $prefix
     * @return string
     */
    public static function addPrefix(string $fileName, $prefix): string
    {
        $fileName = explode('/', $fileName);
        $fileName[] = "{$prefix}_" . array_pop($fileName);
        $fileName = implode('/', $fileName);

        return $fileName;
    }

    /**
     * @param string $fileName
     * @param string $type
     * @param string $prefix
     * @return string
     */
    public static function changeTypeFile(string $fileName, string $type, string $prefix = ''): string
    {
        $fileName = substr($fileName, 0, strrpos($fileName, '.'));
        $fileName = "{$fileName}.{$type}";

        if(!empty($prefix)) {
            return static::addPrefix($fileName, $prefix);
        }

        return $fileName;
    }

    /**
     * @param string $pathToFile
     */
    public static function validateFilePath(string $pathToFile): void
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
     * @param string $pathToDirectory
     */
    public static function validateDirectoryPath(string $pathToDirectory): void
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
}
