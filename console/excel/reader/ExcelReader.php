<?php

namespace console\excel\reader;

use console\excel\reader\base\BaseExcelReader;

class ExcelReader extends BaseExcelReader
{
    /**
     * {@inheritDoc}
     */
    protected function getColumnsMap(): array
    {
        return [
            'operator' => [
                'Саплаер',
                'operator',
                'Оператор',
                'Операто',
            ],
            'gid' => [
                'gid',
                'гид',
                'Гид',
            ],
            'number_campaign' => [
                'Номер кампании',
                '№ кампании',
                '№ компании',
            ],
            'video_id' => [
                'ID ролика',
                'ID  ролика',
            ],
        ];
    }
}
