-- Sample basic training db schema
-- made by gnues

-- Create WOR db
DROP DATABASE IF EXISTS `world_of_renaissance`;
CREATE DATABASE IF NOT EXISTS `world_of_renaissance`;
USE `world_of_renaissance`;

SELECT 'CREATEING DATABASE STRUCTURE' as 'INFO';

-- Drop alreay exists table
DROP TABLE IF EXISTS `user`,
                     `building`,
                     `resource`,
                     `buf`,
                     `weapon`,
                     `exploration_in_territory`,
                     `exploration_out_of_territory`,
                     `war`,
                     `occupation`,
                     `alliance`;

/** 유저 정보 테이블
  * user_id:        유저 id
  * hive_id:        하이브 id
  * hive_uid:       하이브 uid
  * last_visit:     마지막 접속 일자
  * register_date:  가입 일자

  * country:        유저 geoip
  * lang:           유저가 사용하는 언어 (not OS lang)
  * device_name:    유저 단말기 모델명
  * app_version:    클라 버전

  ##### 게임 내 유저 정보 ######
  # 영토
  * territory_id:   영토 id (기획)
  * name:           영주 이름
  * castle_level:   성 레벨

  # 자원 / 인구
  * auto_generate_manpower:     인구 자동 생산 flag
  * manpower_amount:            총 인구
  * tactical_resource_amount:   총 전략 자원
  * food_resource_amount:       총 식량 자원
  * luxury_resource_amount:     총 사치 자원

  # 유저 스펙
  * attack_point:   현재 공격력
  * defence_point:  현재 방어력
  * loyality:       현재 충성도

  ##### 통계적 수치 #####
  # 선전포고
  * war_requset:    선전포고 횟수
  * war_win:        전쟁 승리 횟수
  * war_defeated:   전쟁 패배 횟수

  # 전쟁 방어
  * despoil_defense:    전쟁 방어 성공 횟수
  * despoil_fail:       전쟁 방어 실패 횟수

  # 보스 처치
  * boss{id}_kill_count:  보스 처치 횟수 (기획)
*/
CREATE TABLE `user` (
  `user_id`       BIGINT        NOT NULL      AUTO_INCREMENT,
  `hive_id`       VARCHAR(30)   NOT NULL,
  `hive_uid`      BIGINT        NOT NULL,
  `last_visit`    DATE          NOT NULL,
  `register_date` DATE          NOT NULL,
  -- `country`       CHAR(2)       NOT NULL,
  -- `lang`          CHAR(5)       NOT NULL,
  -- `os_version`    VARCHAR(20)   NOT NULL,
  -- `device_name`   VARCHAR(20)   NOT NULL,
  -- `app_version`   VARCHAR(10)   NOT NULL,
  `last_update`   DATE          NULL,

  -- game infos
  -- user map info
  `territory_id`  BIGINT        NOT NULL,
  `name`          VARCHAR(30)   NOT NULL,
  `castle_level`  BIGINT        NOT NULL,

  -- resource / manpower amount
  `auto_generate_manpower`      TINYINT     NOT NULL,
  `manpower_amount`             BIGINT        NOT NULL,
  `tactical_resource_amount`    BIGINT        NOT NULL,
  `food_resource_amount`        BIGINT        NOT NULL,
  `luxury_resource_amount`      BIGINT        NOT NULL,

  -- value of each user territory
  -- `attack_point`                BIGINT        NOT NULL,
  -- `defence_point`               BIGINT        NOT NULL,
  -- `loyality`                    BIGINT        NOT NULL,

  -- statistical info
  `war_requset`                 BIGINT        NOT NULL,
  `war_win`                     BIGINT        NOT NULL,
  `war_defeated`                BIGINT        NOT NULL,

  `despoil_defense_success`     BIGINT        NOT NULL,
  `despoil_defense_fail`        BIGINT        NOT NULL,

  `boss1_kill_count`            BIGINT        NOT NULL,
  `boss2_kill_count`            BIGINT        NOT NULL,
  `boss3_kill_count`            BIGINT        NOT NULL,

  PRIMARY KEY (`user_id`),
  UNIQUE INDEX `uk_hive` (`hive_id`, `hive_uid`),
  UNIQUE INDEX `uk_territory` (`territory_id`),
  UNIQUE INDEX `uk_name` (`name`)
) COLLATE='utf8_unicode_ci' ENGINE=InnoDB;

/** 건물 정보 테이블
  * @desc: 각 유저별로 사용하는 건물 정보
  * building_pk_id:       건물 id (AUTO_INC) [PK]
  * user_id:              유저 id
  * territory_id:         속한 영토 id (기획)
  * tile_id:              위치한 타일 id (기획)
  * building_id:          건물 타입 (기획)

  * is_constructing:      현재 건설중 여부
  * is_upgrading:         현재 업그레이드 중 여부
  * finish_time:          건축 / 업그레이드 완료 시간
  * upgrade:              건물 업그레이드 수준
  * manpower:             건물에 투입된 인력
*/
CREATE TABLE `building` (
  `building_pk_id`  BIGINT        NOT NULL      AUTO_INCREMENT,
  `user_id`         BIGINT        NOT NULL,
  `territory_id`    BIGINT        NOT NULL,
  `tile_id`         BIGINT        NOT NULL,
  `building_id`     BIGINT        NOT NULL,

  `is_constructing` TINYINT       NOT NULL,
  `is_deploying`    TINYINT       NOT NULL,
  `is_upgrading`    TINYINT       NOT NULL,
  `finish_time`     DATE          NOT NULL,
  `upgrade`         BIGINT        NOT NULL,
  `manpower`        BIGINT        NOT NULL,
  `last_update`     DATE          NULL,
  PRIMARY KEY (`building_pk_id`),
  INDEX `idx_user_building` (`user_id`)
) COLLATE='utf8_unicode_ci' ENGINE=InnoDB;

/** 유저 자원 획득 정보 테이블
  * @desc: 각 유저가 단위 시간당 획득할 수 있는 자원 정보
  * resource_pk_id:       자원 id (AUTO_INC) [PK]
  * user_id:              유저 id
  * resource_id:          자원 타입 (기획)

  * condition:            현재 자원 사용 가능여부
*/
CREATE TABLE `resource` (
  `resource_pk_id`  BIGINT        NOT NULL      AUTO_INCREMENT,
  `user_id`         BIGINT        NOT NULL,
  `resource_id`     BIGINT        NOT NULL,
  `condition`       TINYINT       NOT NULL,
  `last_update`     DATE          NULL,
  PRIMARY KEY (`resource_pk_id`),
  INDEX `idx_user_resource` (`user_id`)
) COLLATE='utf8_unicode_ci' ENGINE=InnoDB;

/** 유저 버프 정보
  * @desc: 게임 내 버프 정보
  * buf_pk_id:        버프 id (AUTO_INC) [PK]
  * user_id:          버프 사용 유저 id
  * buf_id:           버프 타입 (기획)
  * is_active:        버프 활성화 여부
  * finish_time:      버프 종료 시간
*/
CREATE TABLE `buf` (
  `buf_pk_id`     BIGINT    NOT NULL      AUTO_INCREMENT,
  `user_id`       BIGINT    NOT NULL,
  `buf_id`        BIGINT    NOT NULL,
  `is_active`     TINYINT   NOT NULL,
  `finish_time`   DATE      NOT NULL,
  `last_update`   DATE      NULL,
  PRIMARY KEY (`buf_pk_id`),
  INDEX `idx_user_buf` (`user_id`,`is_active`)
) COLLATE='utf8_unicode_ci' ENGINE=InnoDB;

/** 유저 무기 정보 테이블
  * @desc: 각 유저가 생산하고 업그레이드 하는 무기의 정보
  * weapon_pk_id:     무기 id (AUTO_INC) [PK]
  * user_id:          유저 id
  * weapon_id:        무기 타입 (기획)
  * upgrade:          무기 업그레이드 단계
  * is_upgrading:     무기 업그레이드 상태
  * finish_time:      생산 / 업그레이드 완료 시간
*/
CREATE TABLE `weapon` (
  `weapon_pk_id`  BIGINT        NOT NULL      AUTO_INCREMENT,
  `user_id`       BIGINT        NOT NULL,
  `weapon_id`     BIGINT        NOT NULL,
  `upgrade`       TINYINT       NOT NULL,
  `is_upgrading`  TINYINT       NOT NULL,
  `finish_time`   DATE          NOT NULL,
  `last_update`   DATE          NULL,
  PRIMARY KEY (`weapon_pk_id`),
  INDEX `idx_user_weapon` (`user_id`)
) COLLATE='utf8_unicode_ci' ENGINE=InnoDB;

/** 유저 영내 탐사 정보 테이블 구현
  * @desc:  유저가 자신의 영토를 탐사할 때의 정보
  * explore_id:     영내 탐사 id (AUTO_INC) [PK]
  * user_id:        유저 id
  * tile_id:        타일 id (기획)
  * finish_time:    탐사 완료 시간
*/
CREATE TABLE `exploration_in_territory` (
  `explore_id`        BIGINT        NOT NULL    AUTO_INCREMENT,
  `user_id`           BIGINT        NOT NULL,
  `tile_id`           BIGINT        NOT NULL,
  `finish_time`       DATE          NOT NULL,
  `last_update`       DATE          NULL,
  PRIMARY KEY (`explore_id`),
  INDEX `idx_user_explore` (`user_id`)
) COLLATE='utf8_unicode_ci' ENGINE=InnoDB;

/** 유저 영외 탐사 정보 테이블 구현
  * @desc: 유저가 다른 유저의 영토를 탐사할 때의 정보
  * explore_id:     영토 탐사 id (AUTO_INC) [PK]
  * user_id:        유저 id
  * territory_id:   영토 id (기획)
  * finish_time:    탐사 완료 시간
*/
CREATE TABLE `exploration_out_of_territory` (
  `explore_id`        BIGINT        NOT NULL    AUTO_INCREMENT,
  `user_id`           BIGINT        NOT NULL,
  `territory_id`      BIGINT        NOT NULL,
  `finish_time`       DATE          NOT NULL,
  PRIMARY KEY (`explore_id`),
  INDEX `idx_user_explore` (`user_id`)
) COLLATE='utf8_unicode_ci' ENGINE=InnoDB;

/** 전쟁(출전) / 보스 레이드 출전 정보
  * @desc: 다른 영토(유저)에 선전포고할 때의 정보
  * war_id:       전쟁 id (AUTO_INC) [PK]
  * user_id:      유저 id
  * territory_id: 영토 id (기획)
  * is_defeated:  해당 전쟁 패배 여부
  * penalty_time: 전쟁 신청 후 일정 시간동안 재 전쟁 요청 금지
  * manpower:     선전포고 당시 병영 인력
  * resource:     선전포고 당시 사용한 군량
  * finish_time:  출전 완료 시간
*/
CREATE TABLE `war` (
  `war_id`        BIGINT        NOT NULL        AUTO_INCREMENT,
  `user_id`       BIGINT        NOT NULL,
  `territory_id`  BIGINT        NOT NULL,
  `is_defeated`   TINYINT       NOT NULL,
  `penanlty_time` DATE          NOT NULL,
  `manpower`      BIGINT        NOT NULL,
  `resource`      BIGINT        NOT NULL,
  `finish_time`   DATE          NOT NULL,
  `last_update`   DATE          NULL,
  PRIMARY KEY (`war_id`),
  INDEX `idx_user_war` (`user_id`, `territory_id`),
  INDEX `idx_territory` (`territory_id`)
) COLLATE='utf8_unicode_ci' ENGINE=InnoDB;

/** 점령 정보
  * @desc: 유저
  * occupation_id:    점령 id (AUTO_INC) [PK]
  * user_id:          유저 id
  * territory_id:     영토 id (기획)
  * finish_time:      점령 유지 완료 시간
*/
CREATE TABLE `occupation` (
  `occupation_id` BIGINT      NOT NULL      AUTO_INCREMENT,
  `user_id`       BIGINT      NOT NULL,
  `territory_id`  BIGINT      NOT NULL,
  `finish_time`   DATE        NOT NULL,
  PRIMARY KEY (`occupation_Id`),
  INDEX `idx_user_occupation` (`user_id`),
  INDEX `idx_territory` (`territory_id`)
) COLLATE='utf8_unicode_ci' ENGINE=InnoDB;

/** 동맹 정보 테이블 구현
  * @desc: 유저 상호간 동맹 정보
  * alliance_id:      동맹 id (AUTO_INC) [PK]
  * is_accepted:      동맹 수락 여부
  * req_user_id:      동맹 요청을 보낸 유저 id
  * res_user_id:      동맹 요청을 받은 유저 id
  * created_date:     동맹 요청 생성 시간
*/
CREATE TABLE `alliance` (
  `alliance_id`     BIGINT      NOT NULL      AUTO_INCREMENT,
  `is_accepted`     TINYINT     NOT NULL,
  `req_user_id`     BIGINT      NOT NULL,
  `res_user_id`     BIGINT      NOT NULL,
  `created_date`    DATE        NOT NULL,
  `last_update`     DATE        NOT NULL,
  PRIMARY KEY (`alliance_id`),
  INDEX `idx_req_user` (`req_user_id`, `is_accepted`, `res_user_id`),
  INDEX `idx_res_user` (`res_user_id`, `is_accepted`, `req_user_id`)
) COLLATE='utf8_unicode_ci' ENGIEN=InnoDB;

/** 동맹 유저간 자원 공유용 우편 테이블
  * @desc: 서로 동맹인 유저 끼리 우편을 주고 받을 수 있기 위한 정보
  * mail_id:              우편 id (AUTO_INC) [PK]
  * from_user_id:         우편 발신 유저 id
  * to_user_id:           우편 송신 유저 id
  * tactical_resource:    전략 자원 수량
  * food_resource:        식량 자원 수량
  * luxury_resource:      사치 자원 수량
*/
CREATE TABLE `mail` (
  `mail_id`             BIGINT      NOT NULL    AUTO_INCREMENT,
  `from_user_id`        BIGINT      NOT NULL,
  `to_user_id`          BIGINT      NOT NULL,
  `tactical_resource`   BIGINT      NOT NULL,
  `food_resource`       BIGINT      NOT NULL,
  `luxury_resource`     BIGINT      NOT NULL,
  `last_update`         DATE        NOT NULL,
  PRIMARY KEY (`mail_id`),
  INDEX `idx_to_user` (`to_user_id`)
) COLLATE='utf8_unicode_ci' ENGIEN=InnoDB;

/** 레이드 보스 몬스터 정보
  * @desc: 레이드 보스 몬스터 현황 정보
  * boss_pk_id:       보스 id (AUTO_INC) [PK]
  * boss_id:          보스 타입 (기획)
  * territory_id:     영토 id (기획)
  * hit_point:        보스 체력
  * is_active:        전투 상태 유무
  * limit_time:       보스 광폭화 시간
  * dead_time:        보스 처치된 시간
*/
CREATE TABLE `boss` (
  `boss_pk_id`        BIGINT      NOT NULL      AUTO_INCREMENT,
  `boss_id`           BIGINT      NOT NULL,
  `territory_id`      BIGINT      NOT NULL,
  `hit_point`         BIGINT      NOT NULL,
  `is_active`         TINYINT     NOT NULL,
  `limit_time`        DATE        NOT NULL,
  `dead_time`         DATE        NOT NULL,
  `last_update`       DATE        NOT NULL,
  PRIMARY KEY (`boss_pk_id`),
  INDEX `idx_territory` (`territory_id`, `is_active`)
) COLLATE='utf8_unicode_ci' ENGIEN=InnoDB;