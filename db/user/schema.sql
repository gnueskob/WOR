-- Sample basic training db schema
-- made by gnues

-- Create WOR db
DROP DATABASE IF EXISTS `world_of_renaissance`;
CREATE DATABASE IF NOT EXISTS `world_of_renaissance`;
USE `world_of_renaissance`;

SELECT 'CREATEING DATABASE STRUCTURE' as 'INFO';

-- Drop alreay exists table
DROP TABLE IF EXISTS `user`,
                     `buliding`;


/** 유저 정보 테이블
  * user_id:        유저 id
  * hive_uid:       하이브 uid
  * name:           영주 이름
  * territory_id:   영토 id
  * last_visit:     마지막 접속 일자
  * register_date:  가입 일자
  * country:        유저 geoip
  * lang:           유저가 사용하는 언어 (not OS lang)
  * device_name:    유저 단말기 모델명
  * app_version:    클라 버전
*/
CREATE TABLE `user` (
  -- `hive_id`       VARCHAR(30)   NOT NULL,
  `user_id`       BIGINT        NOT NULL      AUTO_INCREMENT,
  `hive_uid`      BIGINT        NOT NULL,
  `territory_id`  BIGINT        NOT NULL,
  `name`          VARCHAR(30)   NOT NULL,
  `last_visit`    DATE          NOT NULL,
  `register_date` DATE          NOT NULL,
  -- `country`       CHAR(2)       NOT NULL,
  -- `lang`          CHAR(5)       NOT NULL,
  -- `os_version`    VARCHAR(20)   NOT NULL,
  -- `device_name`   VARCHAR(20)   NOT NULL,
  -- `app_version`   VARCHAR(10)   NOT NULL,
  `last_update`   DATE          NULL,
  PRIMARY KEY (`user_id`)
  -- TODO: 키 설정하기
) COLLATE='utf8_unicode_ci' ENGINE=InnoDB;

/** 건물 정보 테이블
  * @desc: 각 유저별로 사용하는 건물 정보
  * user_id:              유저 id
  * building_id:          건물 id (AUTO_INC)
  * territory_id:         속한 영토 id
  * locdation_x:          영내 건물 위치
  * locdation_Y:          영내 건물 위치
  * type:                 건물 타입
  * upgrade:              건물 업그레이드 수준
  * manpower:             건물에 투입된 인력
*/
CREATE TABLE `buliding` (
  `building_id`   BIGINT        NOT NULL      AUTO_INCREMENT,
  `user_id`       BIGINT        NOT NULL,
  `territory_id`  BIGINT        NOT NULL,
  `location_x`    TINYINT       NOT NULL,
  `location_y`    TINYINT       NOT NULL,
  `type`          BIGINT        NOT NULL,
  `upgrade`       BIGINT        NOT NULL,
  `manpower`      BIGINT        NOT NULL,
  `last_update`   DATE          NULL,
  PRIMARY KEY (`building_id`),
  INDEX `idx_user_building` (`user_id`)
  -- TODO: 키 설정하기
) COLLATE='utf8_unicode_ci' ENGINE=InnoDB;

/** 유저 게임 정보
  *
*/
CREATE TABLE `user_game_info` (
  `user_id`                     BIGINT        NOT NULL      AUTO_INCREMENT,
  `territory_id`                BIGINT        NOT NULL,

  -- resource amount
  `tactical_resource_amount`    BIGINT        NOT NULL,
  `food_resource_amount`        BIGINT        NOT NULL,
  `luxury_resource_amount`      BIGINT        NOT NULL,

  -- value of each user territory
  `attack_point`                BIGINT        NOT NULL,
  `defence_point`               BIGINT        NOT NULL,
  `loyality`                    BIGINT        NOT NULL,

  -- statistical info
  `war_requset`                 BIGINT        NOT NULL,
  `war_win`                     BIGINT        NOT NULL,
  `war_defeated`                BIGINT        NOT NULL,
  -- TODO: 로그성 지표 수치 추가
) COLLATE='utf8_unicode_ci' ENGINE=InnoDB;

/** 유저 자원 획득 정보 테이블
  * @desc: 각 유저가 단위 시간당 획득할 수 있는 자원 정보
*/
CREATE TABLE `resource` (
  `resource_id`   BIGINT        NOT NULL      AUTO_INCREMENT,
  `user_id`       BIGINT        NOT NULL,
  `type`          BIGINT        NOT NULL,
  `condition`     TINYINT       NOT NULL,
  `last_update`   DATE          NULL,
  -- TODO: 키 설정하기
) COLLATE='utf8_unicode_ci' ENGINE=InnoDB;

/** 유저 영내 탐사 정보 테이블 구현
  * @desc:  유저가 자신의 영토를 탐사할 때의 정보
*/
CREATE TABLE `exploration_in_territory` (
  `explore_id`        BIGINT        NOT NULL    AUTO_INCREMENT,
  `user_id`           BIGINT        NOT NULL,
  `territory_id`      BIGINT        NOT NULL,
  `location_x`        TINYINT       NOT NULL,
  `location_y`        TINYINT       NOT NULL,
  PRIMARY KEY (`explore_id`)
  INDEX `idx_user_explore` (`user_id`)
) COLLATE='utf8_unicode_ci' ENGINE=InnoDB;

/** 유저 영외 탐사 정보 테이블 구현
  * @desc:  유저가 다른 유저의 영토를 탐사할 때의 정보
*/
CREATE TABLE `exploration_out_of_territory` (
  `explore_id`        BIGINT        NOT NULL    AUTO_INCREMENT,
  `user_id`           BIGINT        NOT NULL,
  `location_x`        TINYINT       NOT NULL,
  `location_y`        TINYINT       NOT NULL,
) COLLATE='utf8_unicode_ci' ENGINE=InnoDB;

/** 전쟁(출전) 정보
  * TODO: 테이블 구현
*/
CREATE TABLE `war` (
  `last_update`   DATE          NULL
  -- TODO: 키 설정하기
) COLLATE='utf8_unicode_ci' ENGINE=InnoDB;

/** TODO: 동맹 정보 테이블 구현
  *
*/

/** TODO: 정산?
  *
*/