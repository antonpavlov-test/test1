<?php
namespace AntonPavlov\Test;

require_once 'SearchEngine.php';
use AntonPavlov\Test\SearchEngine;

$timeStart = microtime(true);

$fileToAnalyze = new SearchEngine();

$fileName = 'middlefile.txt';
$key = 'aaaaalql';

// выполняем поиск
try {
	echo 'Выполняем поиск ключа "'.$key.'" в файле "'.$fileName.'"<br><br>';
	echo 'Результат:<br>';
	echo $result = $fileToAnalyze->searchFile($fileName, $key);
	echo '<br><br>--------<br><br>всего: '.round((microtime(true) - $timeStart), 2).' с';
} catch (\Exception $e) {
	echo $e->getMessage();
}
