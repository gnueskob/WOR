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
define('PLAN_BUFF', 'buff');
define('PLAN_BOSS_BUF', 'index_boss_buff');
define('PLAN_UPG_CASTLE', 'upgrade_castle');
define('PLAN_UPG_DEF_TOWER', 'upgrade_defense_tower');
define('PLAN_UPG_ARMY', 'upgrade_army');
define('PLAN_WEAPON', 'weapon');
define('PLAN_UPG_WEAPON', 'upgrade_weapon');
define('PLAN_UNIT', 'unit');
define('PLAN_ETC', 'etc');

// Resource
define('PLAN_RESOURCE_ID_IRON', 1);
define('PLAN_RESOURCE_ID_COPPER', 2);
define('PLAN_RESOURCE_ID_COAL', 3);
define('PLAN_RESOURCE_ID_STONE', 4);
define('PLAN_RESOURCE_ID_WOOD', 5);
define('PLAN_RESOURCE_ID_RICE', 6);
define('PLAN_RESOURCE_ID_BARLEY', 7);
define('PLAN_RESOURCE_ID_WHEAT', 8);
define('PLAN_RESOURCE_ID_POTATO', 9);
define('PLAN_RESOURCE_ID_CORN', 10);
define('PLAN_RESOURCE_ID_GOLD', 11);
define('PLAN_RESOURCE_ID_SILVER', 12);
define('PLAN_RESOURCE_ID_PEARL', 13);
define('PLAN_RESOURCE_ID_SPICE', 14);
define('PLAN_RESOURCE_ID_TEA', 15);

// Building
define('PLAN_BUILDING_ID_CASTLE', 0);
define('PLAN_BUILDING_ID_MINE', 1);
define('PLAN_BUILDING_ID_FARM', 2);
define('PLAN_BUILDING_ID_TOWER', 3);
define('PLAN_BUILDING_ID_ARMY', 4);
define('PLAN_BUILDING_ID_CRAFT_SHOP', 5);
define('PLAN_BUILDING_ID_GRANARY', 6);
define('PLAN_BUILDING_ID_FACTORY', 7);
define('PLAN_BUILDING_ID_MUSEUM', 8);

// Boss
define('PLAN_BOSS_ID_SEOLMOON', 1);
define('PLAN_BOSS_ID_SEORYEON', 2);
define('PLAN_BOSS_ID_SAMSEUNG', 3);

// Trophy
define('PLAN_TROPHY_ID_PORRIDGE', 1);
define('PLAN_TROPHY_ID_SILK', 2);
define('PLAN_TROPHY_ID_BOW', 3);

// Buff
define('PLAN_BUF_ID_LOYALTY', 1);
define('PLAN_BUF_ID_TABOO', 2);
define('PLAN_BUF_ID_FLOWER', 3);

// Weapon
define('PLAN_WEAPON_ID_BOW', 1);
define('PLAN_WEAPON_ID_KNIFE', 2);

// Unit
define('UNIT_TIME', 'unit_time');
define('WAR_PENALTY_TIME', 'war_penalty_time');
define('TILE_H', 'tile_height_num');
define('TILE_W', 'tile_width_num');
define('TERRITORY_H', 'territory_height_num');
define('TERRITORY_W', 'territory_width_num');
define('TILE_EXPLORE_UNIT_TIME', 'tile_explore_time_coeff');
define('TERRITORY_EXPLORE_UNIT_TIME', 'territory_explore_time_coeff');
define('TERRITORY_EXPLORE_MANPOWER', 'territory_explore_manpower');
define('WAR_PREPARE_TIME', 'war_time');
define('WAR_UNIT_TIME', 'war_time_coeff');
define('WAR_UNIT_RESOURCE', 'war_resource_coeff');

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
