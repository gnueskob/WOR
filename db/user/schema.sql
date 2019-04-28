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
) COLLATE='utf8_unicode_ci' ENGINE=InnoDB;

/** 건물 정보 테이블
  * building_id:          건물 id (AUTO_INC)
  * territory_id:         속한 영토 id
  * type:                 성
  * upgrade:              건물 업그레이드 수준
*/
CREATE TABLE `buliding` (
  `building_id`   BIGINT        NOT NULL      AUTO_INCREMENT,
  `territory_id`  BIGINT        NOT NULL,
  `type`          BIGINT        NOT NULL,
  `upgrade`       BIGINT        NOT NULL,
  `last_update`   DATE          NULL,
  -- TODO: 키 설정하기
) COLLATE='utf8_unicode_ci' ENGINE=InnoDB;

/** TODO: 건물 별 인구 배치 테이블 구현
  *
*/

/** 자원 정보 테이블
  * 타입별 자원 양, 효율성
*/
CREATE TABLE `resource` (
  `resource_id`   BIGINT        NOT NULL      AUTO_INCREMENT,
  `user_id`       BIGINT        NOT NULL,
  `type`          BIGINT        NOT NULL,
  `ppu`           BIGINT        NOT NULL,
  `amount`        BIGINT        NOT NULL,
  `last_update`   DATE          NULL,
  -- TODO: 키 설정하기
) COLLATE='utf8_unicode_ci' ENGINE=InnoDB;

/** TODO: 탐사 정보 테이블 구현
  *
*/

/** 전쟁(출전) 정보
  * TODO: 테이블 구현
*/
CREATE TABLE `war` (
  `last_update`   DATE          NULL
) COLLATE='utf8_unicode_ci' ENGINE=InnoDB;

/** TODO: 동맹 정보 테이블 구현
  *
*/

/** TODO: 정산?
  *
*/