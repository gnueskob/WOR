<?php

namespace lsb\Libs;

use Redis as Rds;
use Exception;
use lsb\Config\Config;

define('REDIS', 'redis');
define('APCU', 'apcu');

class CSVParser
{
    private $driver;

    /**
     * @var Rds $pipe : for redis pipeline commands
     */
    private $pipe;

    /**
     * @var array $csvData : for apcu data
     */
    private $csvData;

    public function __construct()
    {
        $conf = Config::getInstance()->getConfig('plan');
        $this->driver = $conf['driver'];
    }

    public function saveCSV(string $file, $keyIndex, $keyTag): bool
    {
        $handle = fopen($file, 'r');

        $keyStr = [];
        // save key value
        if (($data = fgetcsv($handle)) === false) {
            // there is no data
            return false;
        } else {
            // remove BOM of CSV file
            $data[0] = preg_replace("/^\x{feff}/u", '', $data[0]);
            foreach ($data as $idx => $value) {
                $keyStr[$idx] = $value;
            }
        }

        $this->init();
        $index = null;
        $chunkData = [];
        while (($data = fgetcsv($handle)) !== false) {
            if ($index !== $data[$keyIndex] && isset($index)) {
                $chunkData = count($chunkData) === 1 ? $chunkData[0] : $chunkData;
                $this->appendCSVData($keyTag, $index, $chunkData);
                unset($chunkData);
                $chunkData = [];
            }

            $index = $data[$keyIndex];
            $arr = [];
            foreach ($keyStr as $idx => $value) {
                $arr[$value] = $data[$idx];
            }
            array_push($chunkData, $arr);
            unset($arr);
        }

        $chunkData = count($chunkData) === 1 ? $chunkData[0] : $chunkData;
        $this->appendCSVData($keyTag, $index, $chunkData);
        unset($chunkData);

        return $this->saveData($keyTag);
    }

    private function init()
    {
        switch ($this->driver) {
            default:
            case REDIS:
                $redis = Redis::getInstance()->getRedis();
                $this->pipe = $redis->multi(Rds::PIPELINE);
                break;
            case APCU:
                $this->csvData = [];
                break;
        }
    }

    private function appendCSVData(string $keyTag, string $key, array $data)
    {
        switch ($this->driver) {
            default:
            case REDIS:
                $this->pipe->hSet($keyTag, $key, json_encode($data));
                break;
            case APCU:
                $this->csvData[$key] = $data;
                break;
        }
    }

    private function saveData(string $keyTag): bool
    {
        switch ($this->driver) {
            default:
            case REDIS:
                try {
                    $this->pipe->exec();
                    $res = true;
                } catch (Exception $e) {
                    $res = false;
                }
                break;
            case APCU:
                $res = apcu_store($keyTag, $this->csvData);
                break;
        }
        return $res;
    }
}
