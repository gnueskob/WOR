-- Sample basic training db schema
-- made by gnues

-- Create WOR db
DROP DATABASE IF EXISTS world_of_renaissance;
CREATE DATABASE IF NOT EXISTS world_of_renaissance;
USE world_of_renaissance;

SELECT 'CREATING DATABASE STRUCTURE' as 'INFO';

-- Drop already exists table
DROP TABLE IF EXISTS user,
                     building,
                     resource,
                     buff,
                     weapon,
                     exploration_in_territory,
                     exploration_out_of_territory,
                     war,
                     alliance;

CREATE TABLE user_platform (
  user_id       BIGINT        NOT NULL      AUTO_INCREMENT,
  hive_id       VARCHAR(30)   NOT NULL,
  hive_uid      BIGINT        NOT NULL,
  register_date DATETIME      NOT NULL,
  country       CHAR(2)       NOT NULL,
  lang          CHAR(5)       NOT NULL,
  os_version    VARCHAR(20)   NOT NULL,
  device_name   VARCHAR(20)   NOT NULL,
  app_version   VARCHAR(10)   NOT NULL,
  PRIMARY KEY (user_id),
  UNIQUE INDEX uk_hive (hive_id, hive_uid),
  UNIQUE INDEX uk_hive_uid (hive_uid)
);

CREATE TABLE user_info (
  user_id       BIGINT        NOT NULL,

  -- game infos
  last_visit    DATETIME      NOT NULL,

  -- user map info
  territory_id          BIGINT        NULL,
  name                  VARCHAR(30)   NULL,
  castle_level          BIGINT        NOT NULL      DEFAULT 1,
  castle_to_level       BIGINT        NOT NULL      DEFAULT 1,
  upgrade_time          DATETIME      NOT NULL,

  -- resource / manpower amount
  penalty_finish_time         DATETIME      NULL,
  auto_generate_manpower      TINYINT       NOT NULL    DEFAULT TRUE,
  manpower             BIGINT        NOT NULL    DEFAULT 0,
  appended_manpower    BIGINT        NOT NULL    DEFAULT 0,
  tactical_resource    BIGINT        NOT NULL    DEFAULT 0,
  food_resource        BIGINT        NOT NULL    DEFAULT 0,
  luxury_resource      BIGINT        NOT NULL    DEFAULT 0,

  friend_attack         BIGINT        NOT NULL    DEFAULT 0,

  PRIMARY KEY (user_id),
  UNIQUE INDEX uk_territory (territory_id),
  UNIQUE INDEX uk_name (name),
  INDEX idx_last_update (last_update)
);

CREATE TABLE user_statistics (
  user_id       BIGINT        NOT NULL

  -- statistical info
  war_requset                 BIGINT        NOT NULL    DEFAULT 0,
  war_victory                 BIGINT        NOT NULL    DEFAULT 0,
  war_defeated                BIGINT        NOT NULL    DEFAULT 0,

  despoil_defense_success     BIGINT        NOT NULL    DEFAULT 0,
  despoil_defense_fail        BIGINT        NOT NULL    DEFAULT 0,

  boss1_kill_count            BIGINT        NOT NULL    DEFAULT 0,
  boss2_kill_count            BIGINT        NOT NULL    DEFAULT 0,
  boss3_kill_count            BIGINT        NOT NULL    DEFAULT 0,

  PRIMARY KEY (user_id)
);

CREATE TABLE building (
  building_id     BIGINT        NOT NULL      AUTO_INCREMENT,
  user_id         BIGINT        NOT NULL,
  territory_id    BIGINT        NOT NULL,
  tile_id         BIGINT        NOT NULL,
  building_type   BIGINT        NOT NULL,
  resource_type   BIGINT        NOT NULL,

  create_time     DATETIME   NULL,
  deploy_time     DATETIME   NULL,
  upgrade_time    DATETIME   NULL,
  level           BIGINT        NOT NULL      DEFAULT 1,
  to_levl         BIGINT        NOT NULL      DEFAULT 1,
  manpower        BIGINT        NOT NULL      DEFAULT 0,
  last_update     DATETIME      NULL,
  PRIMARY KEY (building_id),
  INDEX idx_user_deployed (user_id, building_type, deploy_time),
  UNIQUE KEY uk_building_tile (tile_id)
);

CREATE TABLE buff (
  buff_id        BIGINT    NOT NULL      AUTO_INCREMENT,
  user_id       BIGINT    NOT NULL,
  buff_type      BIGINT    NOT NULL,
  finish_time   DATETIME  NOT NULL,
  PRIMARY KEY (buff_id),
  UNIQUE KEY (user_id, buff_type),
  INDEX idx_user_buff (user_id, finish_time)
);

CREATE TABLE weapon (
  weapon_id     BIGINT        NOT NULL      AUTO_INCREMENT,
  user_id       BIGINT        NOT NULL,
  weapon_type   BIGINT        NOT NULL,

  create_time   DATETIME      NULL,
  upgrade_time  DATETIME      NULL,
  level         TINYINT       NOT NULL      DEFAULT 1,
  to_level      TINYINT       NOT NULL      DEFAULT 1,
  last_update   DATETIME      NULL,
  PRIMARY KEY (weapon_id),
  UNIQUE INDEX uk_user_weaopn (user_id, weapon_type),
  INDEX idx_user_weapon_created (user_id, create_time)
);

CREATE TABLE tile (
  explore_id        BIGINT        NOT NULL    AUTO_INCREMENT,
  user_id           BIGINT        NOT NULL,
  tile_id           BIGINT        NOT NULL,
  explore_time      DATETIME      NOT NULL,
  PRIMARY KEY (explore_id),
  UNIQUE INDEX idx_user_explore (user_id, tile_id)
);

CREATE TABLE territory (
  explore_id        BIGINT        NOT NULL    AUTO_INCREMENT,
  user_id           BIGINT        NOT NULL,
  territory_id      BIGINT        NOT NULL,
  explore_time      DATETIME      NOT NULL,
  PRIMARY KEY (explore_id),
  UNIQUE INDEX idx_user_explore (user_id, territory_id)
);

CREATE TABLE war (
  war_id          BIGINT        NOT NULL        AUTO_INCREMENT,
  user_id         BIGINT        NOT NULL,
  territory_id    BIGINT        NOT NULL,
  attack          BIGINT        NOT NULL,
  friend_attack   BIGINT        NOT NULL,
  manpower        BIGINT        NOT NULL,
  food_resource   BIGINT        NOT NULL,
  target_defense  BIGINT      NOT NULL,
  prepare_time    DATETIME      NOT NULL,
  finish_time     DATETIME      NOT NULL,
  PRIMARY KEY (war_id),
  UNIQUE KEY (user_id),
  UNIQUE KEY (territory_id)
);

CREATE TABLE raid (
  raid_id         BIGINT        NOT NULL        AUTO_INCREMENT,
  boss_id         BIGINT        NOT NULL,
  user_id         BIGINT        NOT NULL,
  territory_id    BIGINT        NOT NULL,
  boss_type       BIGINT        NOT NULL,
  is_victory      TINYINT       NULL,
  finish_time     DATETIME      NOT NULL,
  PRIMARY KEY (raid_id),
  INDEX idx_boss (boss_id),
  UNIQUE KEY idx_user (user_id, boss_id),
  INDEX idx_territory (territory_id),
  INDEX idx_finish_time (finish_time)
);

CREATE TABLE raid_boss (
  boss_id         BIGINT        NOT NULL      AUTO_INCREMENT,
  user_id         BIGINT        NULL,
  territory_id    BIGINT        NOT NULL,
  hit_point       BIGINT        NOT NULL,
  boss_type       BIGINT        NOT NULL,
  finish_time     BIGINT        NULL,
  PRIMARY KEY (boss_id),
  INDEX idx_user (user_id),
  UNIQUE KEY uk_territory (territory_id)
);

CREATE TABLE alliance (
  alliance_id     BIGINT      NOT NULL      AUTO_INCREMENT,
  user_id         BIGINT      NOT NULL,
  friend_id       BIGINT      NOT NULL,
  created_time    DATETIME    NOT NULL,
  PRIMARY KEY (alliance_id),
  UNIQUE INDEX uk_friend (user_id, friend_id)
);

CREATE TABLE alliance_wait (
  alliance_id     BIGINT      NOT NULL      AUTO_INCREMENT,
  user_id         BIGINT      NOT NULL,
  friend_id       BIGINT      NOT NULL,
  created_time    DATETIME    NOT NULL,
  PRIMARY KEY (alliance_id),
  UNIQUE INDEX uk_friend (friend_id, user_id)
);

CREATE TABLE mail (
  mail_id             BIGINT      NOT NULL    AUTO_INCREMENT,
  from_user_id        BIGINT      NOT NULL,
  to_user_id          BIGINT      NOT NULL,
  create_time         DATETIME    NOT NULL,
  text                TEXT        NULL,
  tactical_resource   BIGINT      NOT NULL,
  food_resource       BIGINT      NOT NULL,
  luxury_resource     BIGINT      NOT NULL,
  last_update         DATETIME    NOT NULL,
  PRIMARY KEY (mail_id),
  INDEX idx_to_user (to_user_id)
);