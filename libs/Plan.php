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
define('PLAN_BOSS', 'boss');
define('PLAN_BUFF', 'buff');
define('PLAN_TROPHY', 'trophy');
define('PLAN_WEAPON', 'weapon');
define('PLAN_UNIT', 'unit');
define('PLAN_ETC', 'etc');

// deprecated
define('PLAN_BUILDING_NEED_RESOURCE', 'index_building_resource');
define('PLAN_BOSS_BUF', 'index_boss_buff');
define('PLAN_UPG_CASTLE', 'upgrade_castle');
define('PLAN_UPG_DEF_TOWER', 'upgrade_defense_tower');
define('PLAN_UPG_ARMY', 'upgrade_army');
define('PLAN_UPG_WEAPON', 'upgrade_weapon');


// Map
define('PLAN_TILE_TYPE_NOT_USED', 0);
define('PLAN_TILE_TYPE_NORMAL', 1);
define('PLAN_TILE_TYPE_RESOURCE', 2);

define('PLAN_TERRITORY_TYPE_NOT_USED', 0);
define('PLAN_TERRITORY_TYPE_NORMAL', 1);
define('PLAN_TERRITORY_TYPE_BOSS', 2);

// Resource
define('PLAN_RESOURCE_CLASS_FOOD', 1);
define('PLAN_RESOURCE_CLASS_TACTICAL', 2);
define('PLAN_RESOURCE_CLASS_LUXURY', 3);

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

// Boss
define('PLAN_BOSS_ID_SEOLMOON', 1);
define('PLAN_BOSS_ID_SEORYEON', 2);
define('PLAN_BOSS_ID_SAMSEUNG', 3);

// Trophy
define('PLAN_TROPHY_ID_PORRIDGE', 1);
define('PLAN_TROPHY_ID_SILK', 2);
define('PLAN_TROPHY_ID_BOW', 3);

// Buff
define('PLAN_BUFF_ID_LOYALTY', 1);
define('PLAN_BUFF_ID_TABOO', 2);
define('PLAN_BUFF_ID_FLOWER', 3);

define('PLAN_BUFF_TYPE_TROPHY', 0);
define('PLAN_BUFF_TYPE_RESOURCE', 1);
define('PLAN_BUFF_TYPE_RESOURCE_MANPOWER', 2);

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

    /***********************************************************/

    private static $cache = [];

    public static function getData(string $keyTag, string $key)
    {
        if (isset(static::$cache[$keyTag . $key])) {
            return static::$cache[$keyTag . $key];
        }
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
        static::$cache[$keyTag . $key] = $res;
        return $res;
    }

    public static function getDataAll(string $keyTag)
    {
        if (isset(static::$cache[$keyTag])) {
            return static::$cache[$keyTag];
        }
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
        foreach ($res as $key => $value) {
            static::$cache[$keyTag . $key] = $value;
        }
        static::$cache[$keyTag] = $res;
        return $res;
    }

    /************************************************************/

    // Building
    public const BUILDING_ID_CASTLE = 0;
    public const BUILDING_ID_MINE = 1;
    public const BUILDING_ID_FARM = 2;
    public const BUILDING_ID_TOWER = 3;
    public const BUILDING_ID_ARMY = 4;
    public const BUILDING_ID_CRAFT_SHOP = 5;
    public const BUILDING_ID_GRANARY = 6;
    public const BUILDING_ID_FACTORY = 7;
    public const BUILDING_ID_MUSEUM = 8;

    // BUILDING PLAN DATA

    public static function getBuilding(int $buildingType)
    {
        $plan = Plan::getData(PLAN_BUILDING, $buildingType);

        return [
            $plan['building_type'],
            $plan['name'],
            $plan['class'],
            $plan['class_name'],

            $plan['need_tactical_resource'],    // 4
            $plan['need_food_resource'],
            $plan['need_luxury_resource'],

            $plan['create_manpower'],              // 7
            $plan['deploy_min_manpower'],
            $plan['deploy_max_manpower'],

            $plan['create_unit_time'],          // 10
            $plan['upgrade_unit_time'],
            $plan['deploy_unit_time'],

            $plan['defense'],                   // 13

            $plan['feature_tactical_resource'], // 14
            $plan['feature_food_resource'],
            $plan['feature_loyality'],

            $plan['manpower_unit_time'],        // 17
            $plan['loyalty_bound'],

            $plan['upgradable'],                // 19
            $plan['max_level'],

            $plan['tactical_resource_upgrade_ratio'],   // 21
            $plan['food_resource_upgrade_ratio'],
            $plan['luxury_resource_upgrade_ratio'],

            $plan['upgrade_unit_time_upgrade_ratio'],   // 24

            $plan['max_manpower_upgrade_ratio'],        // 25
            $plan['defense_upgrade_ratio'],

            $plan['manpower_unit_time_upgrade_ratio'],        // 27
            $plan['loyalty_bound_upgrade_ratio']
        ];
    }

    public static function getBuildingCreateResources(int $buildingType)
    {
        return array_slice(static::getBuilding($buildingType), 4, 3);
    }

    public static function getBuildingUnitTime(int $buildingType, int $level = 1)
    {
        list($createUnitTime,
            $upgradeUnitTime,
            $deployUnitTime) = array_slice(static::getBuilding($buildingType), 10, 3);
        $upgradeRatio = static::getBuilding($buildingType)[24];
        $upgradeUnitTime += $upgradeRatio * $upgradeUnitTime * ($level - 1);
        return [$createUnitTime, $upgradeUnitTime, $deployUnitTime];
    }

    public static function getBuildingManpower(int $buildingType, int $level = 1)
    {
        list($createManpower,
            $deployMinManpower,
            $deployMaxManpower) = array_slice(static::getBuilding($buildingType), 7, 3);
        $upgradeRatio = static::getBuilding($buildingType)[25];
        $deployMaxManpower += $upgradeRatio * $deployMaxManpower * ($level - 1);
        return [$createManpower, $deployMinManpower, $deployMaxManpower];
    }

    public static function getBuildingFeature(int $buildingType)
    {
        return array_slice(static::getBuilding($buildingType), 14, 3);
    }

    public static function getBuildingUpgradeStatus(int $buildingType)
    {
        return array_slice(static::getBuilding($buildingType), 19, 2);
    }

    public static function getBuildingDefense(int $buildingType, int $level = 1)
    {
        $defense = static::getBuilding($buildingType)[13];
        $upgradeRatio = static::getBuilding($buildingType)[26];
        $defense += $upgradeRatio * $defense * ($level - 1);
        return $defense;
    }

    public static function getBuildingUpgradeResources(int $buildingType, int $level = 1)
    {
        list($tactical, $food, $luxury) = static::getBuildingCreateResources($buildingType);
        list($tRatio, $fRatio, $lRatio) = array_slice(static::getBuilding($buildingType), 21, 3);
        return [
            $tactical * $tRatio * $level,
            $food * $fRatio * $level,
            $luxury * $lRatio * $level
        ];
    }

    public static function getCastleFeature(int $level = 1)
    {
        list($manpowerUnitTime, $loyaltyBound) = array_slice(static::getBuilding(PLAN_BUILDING_ID_CASTLE), 17, 2);
        list($mutRatio, $lbRatio) = array_slice(static::getBuilding(PLAN_BUILDING_ID_CASTLE), 25, 2);
        $manpowerUnitTime -= $mutRatio * $manpowerUnitTime * ($level - 1);
        $loyaltyBound += $lbRatio * $loyaltyBound * ($level - 1);
        return [$manpowerUnitTime, $loyaltyBound];
    }

    /*****************************************************************/

    // WEAPON PLAN DATA

    public static function getWeapon(int $weaponType)
    {
        $plan = Plan::getData(PLAN_WEAPON, $weaponType);
        return [
            $plan['weapon_id'],
            $plan['name'],
            $plan['attack'],

            $plan['need_tactical_resource'],    // 3
            $plan['need_food_resource'],
            $plan['need_luxury_resource'],

            $plan['create_unit_time'],          // 6
            $plan['upgrade_unit_time'],

            $plan['attack_upgrade_ratio'],      // 8

            $plan['max_level'],                 // 9

            $plan['tactical_resource_upgrade_ratio'],   // 10
            $plan['food_resource_upgrade_ratio'],
            $plan['luxury_resource_upgrade_ratio'],

            $plan['upgrade_unit_time_upgrade_ratio']    // 13
        ];
    }

    public static function getWeaponCreateResources(int $weaponType)
    {
        return array_slice(static::getWeapon($weaponType), 3, 3);
    }

    public static function getWeaponUnitTime(int $weaponType, int $level = 1)
    {
        list($createUnitTime, $upgradeUnitTime) = array_slice(static::getWeapon($weaponType), 6, 2);
        $upgradeRatio = static::getWeapon($weaponType)[13];
        $upgradeUnitTime += $upgradeRatio * $upgradeUnitTime * ($level - 1);
        return [$createUnitTime, $upgradeUnitTime];
    }

    public static function getWeaponUpgradeResources(int $weaponType, int $level)
    {
        list($tactical, $food, $luxury) = static::getWeaponCreateResources($weaponType);
        list($tRatio, $fRatio, $lRatio) = array_slice(static::getWeapon($weaponType), 10, 3);
        return [
            $tactical * $tRatio * $level,
            $food * $fRatio * $level,
            $luxury * $lRatio * $level
        ];
    }

    public static function getWeaponAttack(int $weaponType, int $level = 1)
    {
        $attack = static::getWeapon($weaponType)[2];
        $ratio = static::getWeapon($weaponType)[8];
        $attack += $ratio * $attack * ($level - 1);
        return $attack;
    }

    /*****************************************************************/

    // TILE PLAN DATA

    public static function getTile(int $tileId)
    {
        $plan = Plan::getData(PLAN_TILE, $tileId);
        return [
            $plan['tile_id'],               // 0
            $plan['territory_id'],

            $plan['location_x'],            // 2
            $plan['location_y'],

            $plan['class'],                  // 4
            $plan['class_name'],

            $plan['resource_type'],           // 6
            $plan['resource']
        ];
    }

    public static function getTileLocation(int $tileId)
    {
        return array_slice(static::getTile($tileId), 2, 2);
    }

    public static function getTileClass(int $tileId)
    {
        return array_slice(static::getTile($tileId), 4, 2);
    }

    public static function getTileResourceType(int $tileId)
    {
        return array_slice(static::getTile($tileId), 6, 2);
    }

    /*****************************************************************/

    // TERRITORY PLAN DATA

    public static function getTerritory(int $territoryId)
    {
        $plan = Plan::getData(PLAN_TERRITORY, $territoryId);
        return [
            $plan['territory_id'],          // 0

            $plan['location_x'],            // 1
            $plan['location_y'],

            $plan['class'],                  // 3
            $plan['class_name']
        ];
    }

    public static function getTerritoryLocation(int $territoryId)
    {
        return array_slice(static::getTerritory($territoryId), 1, 2);
    }

    public static function getTerritoryClass(int $territoryId)
    {
        return array_slice(static::getTerritory($territoryId), 3, 2);
    }

    /*****************************************************************/

    // RESOURCE PLAN DATA

    public static function getResource(int $resourceType)
    {
        $plan = Plan::getData(PLAN_RESOURCE, $resourceType);
        return [
            $plan['resource_type'],                   // 0
            $plan['name'],

            $plan['production_per_unit_time'],      // 2

            $plan['class'],                         // 3
            $plan['class_name'],

            $plan['building_type'],                 // 5
            $plan['building_name'],
        ];
    }

    public static function getResourcePPU(int $resourceType)
    {
        return static::getResource($resourceType)[2];
    }

    public static function getResourceClass(int $resourceType)
    {
        return array_slice(static::getResource($resourceType), 3, 2);
    }

    public static function getResourceNeededBuilding(int $resourceType)
    {
        return array_slice(static::getResource($resourceType), 5, 2);
    }

    /*****************************************************************/

    // BUFF PLAN DATA

    public static function getBuff(int $buffType)
    {
        $plan = Plan::getData(PLAN_BUFF, $buffType);
        return [
            $plan['buff_id'],           // 0
            $plan['name'],

            $plan['class'],              // 2
            $plan['class_name'],

            $plan['default_finish_time'],   // 4

            $plan['need_tactical_resource'],    // 5
            $plan['need_food_resource'],
            $plan['need_luxury_resource'],

            $plan['inc_atk_ratio'],             // 8
            $plan['inc_dfs_ratio'],
            $plan['inc_manpower_ratio'],
            $plan['inc_loyalty_ratio'],
        ];
    }

    public static function getBuffClass(int $buffType)
    {
        return array_slice(static::getBuff($buffType), 2, 2);
    }

    public static function getBuffFinishUnitTime(int $buffType)
    {
        return static::getBuff($buffType)[4];
    }

    public static function getBuffResources(int $buffType)
    {
        return array_slice(static::getBuff($buffType), 5, 3);
    }

    public static function getBuffPower(int $buffType)
    {
        return array_slice(static::getBuff($buffType), 8, 4);
    }

    /*****************************************************************/

    // BOSS PLAN DATA

    public static function getBoss(int $bossType)
    {
        $plan = Plan::getData(PLAN_BOSS, $bossType);
        return [
            $plan['boss_type'],           // 0
            $plan['name'],
            $plan['description'],

            $plan['hit_point'],           // 3

            $plan['active_unit_time'],    // 4
            $plan['gen_time'],

            $plan['buf_type'],            // 6
            $plan['buf_name'],

            $plan['trophy_type'],         // 8
            $plan['trophy_name']
        ];
    }

    public static function getBossHP(int $bossType)
    {
        return static::getBoss($bossType)[3];
    }

    public static function getBossUnitTime(int $bossType)
    {
        return array_slice(static::getBoss($bossType), 4, 2);
    }

    public static function getBossBuff(int $bossType)
    {
        return array_slice(static::getBoss($bossType), 6, 2);
    }

    public static function getBossTrophy(int $bossType)
    {
        return array_slice(static::getBoss($bossType), 8, 2);
    }

    /*****************************************************************/

    // TROPHY PLAN DATA

    public static function getTrophy(int $trophyType)
    {
        $plan = Plan::getData(PLAN_TROPHY, $trophyType);
        return [
            $plan['$trophy_type'],        // 0
            $plan['name'],

            $plan['tactical_resource'],    // 2
            $plan['food_resource'],
            $plan['luxury_resource'],
        ];
    }

    public static function getTrophyRewardResources(int $trophyType)
    {
        return array_slice(static::getTrophy($trophyType), 2, 3);
    }

    /*****************************************************************/

    // UNIT PLAN DATA

    public static function getUnit()
    {
        $plan = Plan::getDataAll(PLAN_UNIT);

        return [
            // 0
            [
                $plan['unit_time']['value'],
                $plan['unit_time']['id'],
                $plan['unit_time']['description']
            ],
            [
                $plan['war_penalty_time']['value'],
                $plan['war_penalty_time']['id'],
                $plan['war_penalty_time']['description']
            ],

            // 2
            [
                $plan['tile_height_num']['value'],
                $plan['tile_height_num']['id'],
                $plan['tile_height_num']['description']
            ],
            [
                $plan['tile_width_num']['value'],
                $plan['tile_width_num']['id'],
                $plan['tile_width_num']['description']
            ],

            // 4
            [
                $plan['territory_height_num']['value'],
                $plan['territory_height_num']['id'],
                $plan['territory_height_num']['description']
            ],
            [
                $plan['territory_width_num']['value'],
                $plan['territory_width_num']['id'],
                $plan['territory_width_num']['description']
            ],

            // 6
            [
                $plan['tile_explore_time_coeff']['value'],
                $plan['tile_explore_time_coeff']['id'],
                $plan['tile_explore_time_coeff']['description']
            ],
            [
                $plan['territory_explore_time_coeff']['value'],
                $plan['territory_explore_time_coeff']['id'],
                $plan['territory_explore_time_coeff']['description']
            ],
            [
                $plan['territory_explore_manpower']['value'],
                $plan['territory_explore_manpower']['id'],
                $plan['territory_explore_manpower']['description']
            ],

            // 9
            [
                $plan['war_time']['value'],
                $plan['war_time']['id'],
                $plan['war_time']['description']
            ],
            [
                $plan['war_time_coeff']['value'],
                $plan['war_time_coeff']['id'],
                $plan['war_time_coeff']['description']
            ],
            [
                $plan['war_resource_coeff']['value'],
                $plan['war_resource_coeff']['id'],
                $plan['war_resource_coeff']['description']
            ],
            [
                $plan['war_default_manpower_ratio']['value'],
                $plan['war_default_manpower_ratio']['id'],
                $plan['war_default_manpower_ratio']['description']
            ]
        ];
    }

    public static function getUnitTime()
    {
        list($unitTime) = static::getUnit()[0];
        return $unitTime;
    }

    public static function getUnitWarPenaltyTime()
    {
        list($warPenaltyUnitTime) = static::getUnit()[1];
        return $warPenaltyUnitTime;
    }

    public static function getUnitTileMaxSize()
    {
        list($tileH) = static::getUnit()[2];
        list($tileW) = static::getUnit()[3];
        return [$tileH, $tileW];
    }

    public static function getUnitTerritoryMaxSize()
    {
        list($territoryH) = static::getUnit()[4];
        list($territoryW) = static::getUnit()[5];
        return [$territoryH, $territoryW];
    }

    public static function getUnitExplore()
    {
        list($tileExploreUnitTimeCoefficient) = static::getUnit()[6];
        list($territoryExploreUnitTimeCoefficient) = static::getUnit()[7];
        list($territoryExploreManpower) = static::getUnit()[8];
        return [
            $tileExploreUnitTimeCoefficient,
            $territoryExploreUnitTimeCoefficient,
            $territoryExploreManpower
        ];
    }

    public static function getUnitWar()
    {
        list($warPrepareUnitTime) = static::getUnit()[9];
        list($warMoveUnitTimeCoefficient) = static::getUnit()[10];
        list($warResourceCoefficient) = static::getUnit()[11];
        list($warDefaultManpower) = static::getUnit()[12];
        return [
            $warPrepareUnitTime,
            $warMoveUnitTimeCoefficient,
            $warResourceCoefficient,
            $warDefaultManpower
        ];
    }

    /*****************************************************************/
}
