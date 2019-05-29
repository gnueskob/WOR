<?php

namespace lsb\App\services;

use lsb\App\models\UserModel;

class UserServices
{
    public static function isValidUser(string $hiveId, int $hiveUid): bool
    {
        $res = UserModel::getUserWithHive($hiveId, $hiveUid);
    }
}
