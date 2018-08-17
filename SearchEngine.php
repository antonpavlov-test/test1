<?php
namespace AntonPavlov\Test;


/**
 * Класс, моделирующий бинарный поиск по (возможно) большому текстовому файлу
 *
 * Формат файла - ключ1\tзначение1\x0Aключ2\tзначение2\x0A...ключN\tзначениеN\x0A
 * Ограничений на длину ключа или значения нет
 * Функция на файле размером 10Гб с записями длиной до 4000 байт должна отрабатывать любой запрос менее чем за 5 секунд
 * Объем используемой памяти не должен зависеть от размера файла, только от максимального размера записи
 *
 * @package AntonPavlov\Test
 *
 * @author Anton Pavlov <mail@antonpavlov.ru>
 *
 */
class SearchEngine
{

	/**
	 * Выполняет бинарный поиск значения по ключу в текстовом файле
	 * Результат: если найдено: значение, соответствующее ключу, если не найдено: undef
	 * 
	 * @param string $fileName, $keyToFind - имя файла, значение ключа
	 *
	 * @return string
	 */
	public function searchFile($fileName, $keyToFind)
	{
		$timeStart = microtime(true);

		if (!file_exists($fileName)) {
			throw new \Exception('Файл '.$fileName.' не найден');
		}
		
		$file = new \SplFileObject($fileName, 'rb');

		$start = 0;
		
		$end = $this->getBigFileSize('http://'.$_SERVER['SERVER_NAME'].'/'.$fileName);
		if ($end == 0) {
			$end = filesize($fileName);
		}
		
		if (($end > PHP_INT_MAX) || ($end < 0)) {
			throw new \Exception('Сожалеем, скрипт оттестирован только на файлах до 2 Гб.<br><br>В отведённое на разработку время не получилось заставить fseek перемещать указатель более, чем на '.PHP_INT_MAX.' байт.');
		}

		$lastString = '';
		$stringToAnalyze = '1';
		
		$exitCounter = 0;
		while ($exitCounter < 30) {
			$file->fseek(0, SEEK_SET);
			
			if (($lastString == $stringToAnalyze) || (($start == $position) && ($start == $end))) {
				$exitCounter++;
			}
			$lastString = $stringToAnalyze;
			$position = round($start + ($end - $start) / 2, 0);

			$file->fseek($position, SEEK_SET);

			if ($position > 0) {
				$k = 1;
				while ($file->fgetc() != "\n") {
					$file->fseek($position + $k, SEEK_SET);
					$k++;
				}
			}

			$stringToAnalyze = $file->current();

			list($keyFromFile, $value) = explode("\t", $stringToAnalyze);

			if ($keyFromFile == $keyToFind) {
				return $value;
			}
			
			if ($keyToFind < $keyFromFile) {
				$end = $position - 1;
				$end = ($end < 0) ? 0 : $end;
			}
			
			if ($keyToFind > $keyFromFile) {
				$start = $position + 1;
				$start = ($start < 0) ? 0 : $start;
			}
			
			if ($start > $end) {
				$start = $end;
			}
			
			if ($position > $end) {
				$position = $end;
			}

		}

		return 'undef';
	}
	
	/**
	 * Возвращает корректный размер файла даже если его размер больше 2 Гб
	 * 
	 * @param string $fileName - имя файла
	 *
	 * @return int
	 */

	private function getBigFileSize($fileName)
	{
		$result = 0;
		$curl = curl_init($fileName);
		curl_setopt($curl, CURLOPT_NOBODY, true);
		curl_setopt($curl, CURLOPT_HEADER, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$data = curl_exec($curl);
		curl_close($curl);

		if ($data && preg_match( "/Content-Length: (\d+)/", $data, $matches)) {
			$result = (float)$matches[1];
		}

		return $result;
	}

}