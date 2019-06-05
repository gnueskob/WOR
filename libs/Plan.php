<?php

namespace lsb\Libs;

use Redis as Rds;
use Exception;
use lsb\Config\Config;

define('REDIS', 'redis');
define('APCU', 'apcu');

define('PLAN_TERRITORY', 'territory');
define('PLAN_TILE', 'tile');
define('PLAN_RESOURCE', 'resource');
define('PLAN_BUILDING', 'building');
define('PLAN_BUILDING_NEED_RESOURCE', 'index_building_resource');
define('PLAN_BOSS', 'boss');
define('PLAN_BUF', 'buf');
define('PLAN_BOSS_BUF', 'index_boss_buf');
define('PLAN_UPG_CASTLE', 'upgrade_castle');
define('PLAN_UPG_DEF_TOWER', 'upgrade_defense_tower');
define('PLAN_UPG_ARMY', 'upgrade_army');
define('PLAN_WEAPON', 'weapon');
define('PLAN_UPG_WEAPON', 'upgrade_weapon');
define('PLAN_UNIT', 'unit');
define('ETC', 'etc');

class Plan
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

    public static function getData(string $keyTag, string $key)
    {
        $driver = Config::getInstance()->getConfig('plan')['driver'];
        switch ($driver) {
            default:
            case REDIS:
                try {
                    $redis = Redis::getInstance()->getRedis();
                    $res = $redis->hGet($keyTag, $key);
                    $res = json_decode($res);
                } catch (Exception $e) {
                    $res = [];
                }
                break;
            case APCU:
                $res = apcu_fetch($keyTag)[$key];
                break;
        }
        return $res;
    }

    public static function getDataAll(string $keyTag)
    {
        $driver = Config::getInstance()->getConfig('plan')['driver'];
        switch ($driver) {
            default:
            case REDIS:
                try {
                    $redis = Redis::getInstance()->getRedis();
                    $res = $redis->hGetAll($keyTag);
                    $data = [];
                    $tempKey = null;
                    foreach ($res as $idx => $value) {
                        if ($idx % 2 === 1) {
                            $tempKey = $value;
                        } else {
                            $data[$tempKey] = json_decode($value);
                        }
                    }
                    return $data;
                } catch (Exception $e) {
                    $res = [];
                }
                break;
            case APCU:
                $res = apcu_fetch($keyTag);
                break;
        }
        return $res;
    }
}
