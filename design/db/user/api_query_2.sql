-- 하이브 유저 가져오기
-- 1. 로그인    PUT /user/login
-- 2. 회원가입  POST /user/register
SELECT * FROM `user_platform`
WHERE `hive_id` = :hive_id
  AND `hive_uid` = :hive_Uid;

-- 유저 정보 삽입
-- 트랜잭션
-- 1. 회원가입  POST /user/regiser
INSERT INTO `user_platform`
VALUE (
      :user_id,
      :hive_id,
      :hive_uid,
      :register_date,
      :country,
      :lang,
      :os_version,
      :device_name,
      :app_version
);

INSERT INTO `user_info`
VALUE (
      :user_id,
      :last_update,
      :territory_id,
      :name,
      :castle_level,
      :upgrade_finish_time,
      :auto_generate_manpower,
      :manpower,
      :appended_manpower,
      :tactical_resource,
      :food_resource,
      :luxury_resource
);

INSERT INTO `user_statistics`
VALUE (
      :user_id,
      :war_request,
      :war_victory,
      :war_defeated,
      :despoil_defense_success,
      :despoil_defense_fail,
      :boss1_kill_count,
      :boss2_kill_count,
      :boss3_kill_count
);

-- 유저 이름 갱신
-- 1. 최초 로그인 후 한 번    /user/name/:user_id
UPDATE `user_info`
SET `name` = :name
WHERE `user_id` = :user_id;

-- 유저 영토 위치 갱신
-- 1. 최초 로그인 후 한 번    /user/territory/:user_id
UPDATE `user_info`
SET `territory_id` = :territory_id
WHERE `user_id` = :user_id;

-- 유저 정보 가져오기
-- 1. 로그인 후 유저 정보 렌더링    /user/info/:user_id
SELECT *
FROM user_platform up, user_info ui, user_statistics us
WHERE up.user_id = ui.user_id
  AND up.user_id = us.user_id
  AND up.user_id = :user_id;

-- 유저 타일 정보
-- 1. 로그인 후 유저 정보 랜더링    /user/tile/:user_id
SELECT *
FROM explore_tile
WHERE user_id = :user_id;

-- 유저 영토탐사 정보
-- 1. 로그인 후 유저 정보 랜더링    /user/territory/:user_id
SELECT *
FROM explore_territory
WHERE user_id = :user_id;



