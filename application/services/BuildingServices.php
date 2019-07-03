<?php

namespace lsb\App\services;

use lsb\App\models\BuildingDAO;
use Exception;
use lsb\Libs\Plan;
use lsb\Libs\Timezone;

class BuildingServices
{
    /**
     * @param int $userId
     * @return int
     * @throws Exception
     */
    public static function getUsedManpower(int $userId)
    {
        Plan::getDataAll(PLAN_BUILDING);

        $usedManpower = 0;
        $buildings = BuildingDAO::getBuildings($userId);
        foreach ($buildings as $building) {
            if ($building->isCreating()) {
                // 건물 생성 중일 시 필요한 인력
                list($createManpower) = Plan::getBuildingManpower($building->buildingType);
                $usedManpower += $createManpower;
            } else {
                // 건물 생성이 완료되었을 시 건물에 배치된 인력
                $usedManpower += $building->manpower;
            }
        }
        return $usedManpower;
    }

    /**
     * @param int $userId
     * @param string $lastVisit
     * @return array
     * @throws Exception
     */
    public static function generateResources(int $userId, string $lastVisit)
    {
        $buildings = BuildingDAO::getBuildings($userId);

        Plan::getDataAll(PLAN_RESOURCE);

        $tactical = 0;
        $food = 0;
        $luxury = 0;

        $lastTime = Timezone::getTimestampFromString($lastVisit);
        $unitTime = Plan::getUnitTime();
        $now = Timezone::getNowUTCTimestamp();

        foreach ($buildings as $building) {
            if ($building->resourceType === 0) {
                continue;
            }

            $resourceClass = Plan::getResourceClass($building->resourceType);
            $ppu = Plan::getResourcePPU($building->resourceType);

            if ($building->isDeployed()) {
                $elapsedUnitTime = (int) (($now - $lastTime) / $unitTime);
            } elseif ($building->isDeploying()) {
                $deployedTime = Timezone::getTimestampFromString($building->deployTime);
                $elapsedUnitTime = (int) (($now - $deployedTime) / $unitTime);
            } else {
                continue;
            }

            if ($resourceClass === PLAN_RESOURCE_CLASS_FOOD) {
                $food += $ppu * $elapsedUnitTime;
            } elseif ($resourceClass === PLAN_RESOURCE_CLASS_TACTICAL) {
                $tactical += $ppu * $elapsedUnitTime;
            } elseif ($resourceClass === PLAN_RESOURCE_CLASS_LUXURY) {
                $luxury += $ppu * $elapsedUnitTime;
            }
        }

        return [$tactical, $food, $luxury];
    }

    /**
     * @param int $userId
     * @return array
     * @throws Exception
     */
    public static function getArmyManpowerAndAttack(int $userId)
    {
        $buildings = BuildingDAO::getDeployedBuildings($userId);

        $totalManpower = 0;
        $totalAttack = 0;

        foreach ($buildings as $building) {
            if ($building->buildingType !== Plan::BUILDING_ID_ARMY) {
                continue;
            }
            $totalAttack += $building->manpower * $building->currentLevel;
            $totalManpower += $building->manpower;
        }

        return [$totalManpower, $totalAttack];
    }

    /**
     * @param int $userId
     * @return float|int|mixed
     * @throws Exception
     */
    public static function getDefensePower(int $userId)
    {
        $buildings = BuildingDAO::getDeployedBuildings($userId);

        $totalDefense = 0;

        foreach ($buildings as $building) {
            if ($building->buildingType !== Plan::BUILDING_ID_TOWER) {
                continue;
            }
            $totalDefense += Plan::getBuildingDefense(Plan::BUILDING_ID_TOWER, $building->currentLevel);
        }

        return $totalDefense;
    }
}
