<?php

namespace console\controllers;

use console\excel\reader\ExcelReader;
use yii\console\Controller;

class HelloController extends Controller
{
    public function actionIndex()
    {
        $excelReader = (new ExcelReader())->setPathToDirectory('console/excel/files')->read();
        $t = 1;
    }
}
