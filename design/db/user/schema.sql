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
                     buf,
                     weapon,
                     exploration_in_territory,
                     exploration_out_of_territory,
                     war,
                     occupation,
                     alliance;

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
  * unit_time:      유저에게 적용된 단위 시간

  ##### 게임 내 유저 정보 ######
  # 영토
  * territory_id:   영토 id (기획)
  * name:           영주 이름
  * castle_level:   성 레벨
  * is_upgrading:   현재 업그레이드 진행 여부 - TRUE : 업그레이드 진행 중, FALSE : 업그레이드 완료
  * upgrade_finish_time:        성 업그레이드 완료 시간

  # 자원 / 인구
  * auto_generate_manpower:     인구 자동 생산 flag
  * manpower_amount:            총 인구
  * appended_manpower:          버프로 인해 추가된 인구
  * tactical_resource_amount:   총 전략 자원
  * food_resource_amount:       총 식량 자원
  * luxury_resource_amount:     총 사치 자원

  # 유저 스펙
  * attack_point:   현재 공격력
  * defence_point:  현재 방어력
  * loyalty:        현재 충성도

  ##### 통계적 수치 #####
  # 선전포고
  * war_request:    선전포고 횟수
  * war_victory:    전쟁 승리 횟수
  * war_defeated:   전쟁 패배 횟수

  # 전쟁 방어
  * despoil_defense:    전쟁 방어 성공 횟수
  * despoil_fail:       전쟁 방어 실패 횟수

  # 보스 처치
  * boss{id}_kill_count:  보스 처치 횟수 (기획)
*/
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
  territory_id  BIGINT        NULL,
  name          VARCHAR(30)   NULL,
  castle_level  BIGINT        NOT NULL      DEFAULT 1,
  upgrade_time         DATETIME      NOT NULL,

  -- resource / manpower amount
  penalty_finish_time         DATETIME      NULL,
  auto_generate_manpower      TINYINT       NOT NULL    DEFAULT TRUE,
  manpower             BIGINT        NOT NULL    DEFAULT 0,
  manpower_used        BIGINT        NOT NULL    DEFAULT 0,
  appended_manpower    BIGINT        NOT NULL    DEFAULT 0,
  tactical_resource    BIGINT        NOT NULL    DEFAULT 0,
  food_resource        BIGINT        NOT NULL    DEFAULT 0,
  luxury_resource      BIGINT        NOT NULL    DEFAULT 0,

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

CREATE TABLE user_castle_upgrade (
  id            BIGINT      NOT NULL,
  user_id       BIGINT      NOT NULL,
  from_level    BIGINT      NOT NULL,
  to_level      BIGINT      NOT NULL,
  upgrade_finish_time   DATETIME    NOT NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX idx_user_id (user_id),
  INDEX idx_time (finish_time)
);

/** 건물 정보 테이블
  * @desc: 각 유저별로 사용하는 건물 정보
  * building_pk_id:       건물 id (AUTO_INC) [PK]
  * user_id:              유저 id
  * territory_id:         속한 영토 id (기획)
  * tile_id:              위치한 타일 id (기획)
  * building_id:          건물 종류 (기획)

  * create_finish_time:       설치 완료 시간
  * deploy_finish_time:       인구 배치 완료 시간
  * upgrade_finish_time:      업그레이드 완료 시간
  * is_upgrading:         현재 업그레이드 진행 여부 - TRUE : 업그레이드 진행 중, FALSE : 업그레이드 완료
  * upgrade:              건물 업그레이드 수준
  * manpower:             건물에 투입된 인력
*/
CREATE TABLE building (
  building_id     BIGINT        NOT NULL      AUTO_INCREMENT,
  user_id         BIGINT        NOT NULL,
  territory_id    BIGINT        NOT NULL,
  tile_id         BIGINT        NOT NULL,
  building_type   BIGINT        NOT NULL,

  create_time    DATETIME   NULL,
  deploy_time    DATETIME   NULL,
  upgrade_time   DATETIME   NULL,
  upgrade         BIGINT        NOT NULL      DEFAULT 1,
  manpower        BIGINT        NOT NULL      DEFAULT 0,
  last_update     DATETIME      NULL,
  PRIMARY KEY (building_id),
  INDEX idx_user_deployed (user_id, deploy_finish_time),
  UNIQUE KEY uk_building_tile (user_id, tile_id)
);

CREATE TABLE building_upgrade (
  upgrade_id    BIGINT      NOT NULL      AUTO_INCREMENT,
  building_id   BIGINT      NOT NULL,
  user_id       BIGINT      NOT NULL,
  from_level    BIGINT      NOT NULL,
  to_level      BIGINT      NOT NULL,
  done          TINYINT     NULL,
  upgrade_finish_time   DATETIME    NOT NULL,
  PRIMARY KEY (upgrade_id),
  UNIQUE INDEX idx_building_id (building_id),
  INDEX idx_time (upgrade_finish_time)
);

CREATE TABLE building_deploy (
  deploy_id     BIGINT      NOT NULL      AUTO_INCREMENT,
  building_id   BIGINT      NOT NULL,
  user_id       BIGINT      NOT NULL,
  deploy_finish_time   DATETIME    NOT NULL,
  PRIMARY KEY (deploy_id),
  UNIQUE INDEX idx_building (building_id),
  INDEX idx_time (deploy_finish_time)
);

CREATE TABLE building_create (
  crate_id      BIGINT      NOT NULL      AUTO_INCREMENT,
  building_id   BIGINT      NOT NULL,
  user_id       BIGINT      NOT NULL,
  create_finish_time   DATETIME    NOT NULL,
  PRIMARY KEY (crate_id),
  UNIQUE INDEX idx_building_id (building_id),
  INDEX idx_time (create_finish_time)
);

/** 유저 버프 정보
  * @desc: 게임 내 버프 정보
  * buf_pk_id:        버프 id (AUTO_INC) [PK]
  * user_id:          버프 사용 유저 id
  * raid_id:          레이드를 통해 얻은 버프일 경우 레이드 id
  * buf_id:           버프 타입 (기획)
  * finish_time:      버프 종료 시간
*/
CREATE TABLE buf (
  buf_id        BIGINT    NOT NULL      AUTO_INCREMENT,
  user_id       BIGINT    NOT NULL,
  buf_type      BIGINT    NOT NULL,
  finish_time   DATETIME  NOT NULL,
  last_update   DATETIME  NULL,
  PRIMARY KEY (buf_id),
  INDEX idx_user_buf (user_id, finish_time)
);

/** 유저 무기 정보 테이블
  * @desc: 각 유저가 생산하고 업그레이드 하는 무기의 정보
  * weapon_pk_id:     무기 id (AUTO_INC) [PK]
  * user_id:          유저 id
  * weapon_id:        무기 타입 (기획)
  * upgrade:          무기 업그레이드 단계
  * is_upgrading:     현재 업그레이드 진행 여부 - TRUE : 업그레이드 진행 중, FALSE : 업그레이드 완료
  * upgrade_finish_time:      무기 업그레이드 완료 시간
  * create_finish_time:       무기 생산 완료 시간
*/
CREATE TABLE weapon (
  weapon_id     BIGINT        NOT NULL      AUTO_INCREMENT,
  user_id       BIGINT        NOT NULL,
  weapon_type   BIGINT        NOT NULL,
  upgrade       TINYINT       NOT NULL      DEFAULT 1,
  upgrade_time  DATETIME      NULL,
  create_time   DATETIME      NULL,
  last_update   DATETIME      NULL,
  PRIMARY KEY (weapon_id),
  UNIQUE INDEX uk_user_weaopn (user_id, weapon_type),
  INDEX idx_user_weapon_created (user_id, create_time)
);

CREATE TABLE weapon_upgrade (
  upgrade_id          BIGINT      NOT NULL,
  weapon_id   BIGINT      NOT NULL,
  user_id     BIGINT      NOT NULL,
  from_level  BIGINT      NOT NULL,
  to_level    BIGINT      NOT NULL,
  upgrade_finish_time BIGINT      NOT NULL,

  PRIMARY KEY (id),
  UNIQUE INDEX idx_weapon (weapon_id),
  INDEX idx_time (finish_time)
);

CREATE TABLE weapon_create (
  create_id          BIGINT      NOT NULL,
  weapon_id   BIGINT      NOT NULL,
  user_id     BIGINT      NOT NULL,
  create_finish_time BIGINT      NOT NULL,

  PRIMARY KEY (id),
  UNIQUE INDEX idx_weapon (weapon_id),
  INDEX idx_time (finish_time)
)

/** 유저 영내 탐사 정보 테이블 구현
  * @desc: 유저가 자신의 영토를 탐사할 때의 정보
  * explore_id:     영내 탐사 id (AUTO_INC) [PK]
  * user_id:        유저 id
  * tile_id:        타일 id (기획)
  * finish_time:    탐사 완료 시간
*/
CREATE TABLE tile (
  explore_id        BIGINT        NOT NULL    AUTO_INCREMENT,
  user_id           BIGINT        NOT NULL,
  tile_id           BIGINT        NOT NULL,
  explore_time       DATETIME      NOT NULL,
  last_update       DATETIME      NULL,
  PRIMARY KEY (explore_id),
  UNIQUE INDEX idx_user_explore (user_id, tile_id)
);

CREATE TABLE tile_explore (
  explore_id        BIGINT        NOT NULL    AUTO_INCREMENT,
  tile_id           BIGINT        NOT NULL,
  explore_finish_time       DATETIME      NOT NULL,
  PRIMARY KEY (explore_id),
  UNIQUE KEY uk_tile (tile_id)
);

/** 유저 영외 탐사 정보 테이블 구현
  * @desc: 유저가 다른 유저의 영토를 탐사할 때의 정보
  * explore_id:     영토 탐사 id (AUTO_INC) [PK]
  * user_id:        유저 id
  * territory_id:   영토 id (기획)
  * finish_time:    탐사 완료 시간
*/
CREATE TABLE territory (
  explore_id        BIGINT        NOT NULL    AUTO_INCREMENT,
  user_id           BIGINT        NOT NULL,
  territory_id      BIGINT        NOT NULL,
  explore_time       DATETIME      NOT NULL,
  last_update       DATETIME      NULL,
  PRIMARY KEY (explore_id),
  UNIQUE INDEX idx_user_explore (user_id, territory_id)
);

CREATE TABLE tile_explore (
  explore_id        BIGINT        NOT NULL    AUTO_INCREMENT,
  territory_id           BIGINT        NOT NULL,
  explore_finish_time       DATETIME      NOT NULL,
  PRIMARY KEY (explore_id),
  UNIQUE KEY uk_tile (territory_id)
);

/** 전쟁(출전) / 보스 레이드 출전 정보
  * @desc: 다른 영토(유저)에 선전포고할 때의 정보
  * war_id:       전쟁 id (AUTO_INC) [PK]
  * user_id:      유저 id
  * raid_id:      레이드 id
  * territory_id: 영토 id (기획)
  * is_victory:   해당 전쟁 승리 여부
  * penalty_finish_time:    전쟁 신청 후 일정 시간동안 재 전쟁 요청 금지
  * attack:         선전포고 당시 공격력
  * manpower:       선전포고 당시 병영 인력
  * food_resource:  선전포고 당시 사용한 군량
  * finish_time:    출전 완료 시간
*/
CREATE TABLE war (
  war_id        BIGINT        NOT NULL        AUTO_INCREMENT,
  user_id       BIGINT        NOT NULL,
  territory_id  BIGINT        NOT NULL,
  attack        BIGINT        NOT NULL,
  manpower      BIGINT        NOT NULL,
  food_resource BIGINT        NOT NULL,
  finish_time   DATETIME      NOT NULL,
  last_update   DATETIME      NOT NULL,
  PRIMARY KEY (war_id),
  INDEX idx_user_war (user_id, finish_time),
  INDEX idx_territory (territory_id, finish_time),
  INDEX idx_raid (raid_id, finish_time)
);

CREATE TABLE war_end (

)

/** 레이드 정보
  * @des: 레이드 신청시 생성되는 레이드 정보
  * raid_id:      레이드 id (AUTO_INC) [PK]
  * user_id:      레이드를 신청한 유저 id
  * finish_time:  해당 레이드 정보가 만료되는 시간 (특수지역 출전 만료시간 / 점령전 영토 만료 시간)
*/
CREATE TABLE raid (
  raid_id     BIGINT      NOT NULL        AUTO_INCREMENT,
  user_id     BIGINT      NOT NULL,
  finish_time DATETIME    NOT NULL,
  PRIMARY KEY (raid_id),
  INDEX idx_finish_time (finish_time)
);

/** 점령 정보
  * @desc: 유저 점령지역 정보
  * occupation_id:    점령 id (AUTO_INC) [PK]
  * raid_id:          점령하기 위해 시도했던 레이드 id
  * user_id:          유저 id
  * territory_id:     영토 id (기획)
  * finish_time:      점령 유지 완료 시간
*/
CREATE TABLE occupation (
  occupation_id BIGINT      NOT NULL      AUTO_INCREMENT,
  raid_id       BIGINT      NOT NULL,
  territory_id  BIGINT      NOT NULL,
  user_id       BIGINT      NOT NULL,
  finish_time   DATETIME    NOT NULL,
  last_update   DATETIME    NULL,
  PRIMARY KEY (occupation_Id),
  INDEX idx_raid (raid_id),
  INDEX idx_user_occupation (user_id, finish_time),
  INDEX idx_finish_territory (territory_id, finish_time)
);

/** 동맹 정보 테이블 구현
  * @desc: 유저 상호간 동맹 정보
  * alliance_id:      동맹 id (AUTO_INC) [PK]
  * unique_key:       동맹 중복 신청 방지를 위한 컬럼 (id pair를 정렬하여 ':'으로 연결한 문자열)
  * is_accepted:      동맹 수락 여부
  * req_user_id:      동맹 요청을 보낸 유저 id
  * res_user_id:      동맹 요청을 받은 유저 id
  * created_date:     동맹 요청 생성 시간
*/
CREATE TABLE alliance (
  alliance_id     BIGINT      NOT NULL      AUTO_INCREMENT,
  user_id         BIGINT      NOT NULL,
  friend_id       BIGINT      NOT NULL,
  created_time    DATETIME    NOT NULL,
  last_update     DATETIME    NOT NULL,
  PRIMARY KEY (alliance_id),
  UNIQUE INDEX uk_idx (user_id, friend_id)
);

CREATE TABLE alliance_wait (
  wait_id       BIGINT    NOT NULL    AUTO_INCREMENT,
  user_id       BIGINT    NOT NULL,
  friend_id     BIGINT    NOT NULL,
  created_time  BIGINT    NOT NULL,
  PRIMARY KEY (waid_id),
  UNIQUE INDEX uk_id (user_id, friend_id),
  INDEX idx_friend (friend_id)
);

/** 동맹 유저간 자원 공유용 우편 테이블
  * @desc: 서로 동맹인 유저 끼리 우편을 주고 받을 수 있기 위한 정보
  * mail_id:              우편 id (AUTO_INC) [PK]
  * from_user_id:         우편 발신 유저 id
  * to_user_id:           우편 송신 유저 id
  * tactical_resource:    전략 자원 수량
  * food_resource:        식량 자원 수량
  * luxury_resource:      사치 자원 수량
  * created_date:         보낸 날짜
*/
CREATE TABLE mail (
  mail_id             BIGINT      NOT NULL    AUTO_INCREMENT,
  from_user_id        BIGINT      NOT NULL,
  to_user_id          BIGINT      NOT NULL,
  created_date        DATETIME    NOT NULL,
  last_update         DATETIME    NOT NULL,
  PRIMARY KEY (mail_id),
  INDEX idx_to_user (to_user_id)
);

CREATE TABLE mail_wait (
  wait_id         BIGINT    NOT NULL    AUTO_INCREMENT,
  from_user_id    BIGINT    NOT NULL,
  to_user_id      BIGINT    NOT NULL,
  text                TEXT        NULL,
  tactical_resource   BIGINT      NOT NULL,
  food_resource       BIGINT      NOT NULL,
  luxury_resource     BIGINT      NOT NULL,
  created_time    BIGINT    NOT NULL,
  PRIMARY KEY (waid_id),
  INDEX idx_to_user_id (to_user_id)
);
