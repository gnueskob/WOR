<?php

namespace lsb\App\models;

class UserDAO
{
    public $user_id;
    public $hive_id;
    public $hive_uid;
    public $last_visit;
    public $register_date;
    public $last_update;
    public $unit_time;

    public $territory_id;
    public $name;
    public $castle_level;
    public $is_upgrading;
    public $upgrade_finish_time;

    public $auto_generate_manpower;
    public $appended_manpower;
    public $tactical_resource_amount;
    public $food_resource_amount;
    public $luxury_resource_amount;

    public $war_requset;
    public $war_victory;
    public $war_defeated;

    public $despoil_defense_success;
    public $despoil_defense_fail;

    public $boss1_kill_count;
    public $boss2_kill_count;
    public $boss3_kill_count;
}
