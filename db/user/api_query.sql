-- Queries for WOR API
-- made by GNUES

##########################################
-- 로그인 (Login)
##########################################

-- API: PUT /user/login
SELECT *
FROM `user`
WHERE `hive_uid` = {hive_uid}
  AND `hive_id` = {hive_id}

-- API: POST /user/register

-- 신규 유저에게 주어지는 영토가 빈 영토인지 확인
SELECT *
FROM `user`
WHERE `territory_id` = {selected_territory_id};

-- 중복된 이름인지 확인
SELECT *
FROM `user`
WHERE `name` = {input_name};

-- 실제 유저 정보 추가
INSERT INTO `user`(
  `hive_id`,
  `hive_uid`,

  `last_visit`,
  `register_date`,
  `last_update`,

  `territory_id`,
  `name`,

  `castle_level`,
  `auto_generate_manpower`,
  `manpower_amount`,
  `tactical_resource_amount`,
  `food_resource_amount`,
  `luxury_resource_amount`,

  `war_requset`,
  `war_win`,
  `war_defeated`,
  `despoil_defense_success`,
  `despoil_defense_fail`,
  `boss1_kill_count`,
  `boss2_kill_count`,
  `boss3_kill_count`)
VALUE (
  {hive_id},
  {hive_uid},

  NOW(),
  NOW(),
  NOW(),

  {selected_territory_id},
  {user_name},

  1,
  TRUE,
  {initial_manpower},
  {initial_tactical_resource_amount},
  {initial_food_resource_amount},
  {initial_luxury_resource_amount},

  0,
  0,
  0,
  0,
  0,
  0,
  0,
  0
);

##########################################
-- 유저 정보 (User Infomation)
##########################################

-- API: GET /user/tiles/{userId}
-- 유저 영토의 영내 맵 탐사 현황 정보
SELECT *
FROM `exploration_in_territory`
WHERE `user_id` = {user_id};

-- API: GET /user/territory/{userId}
-- 유저 영토 탐사 현황 정보
SELECT *
FROM `exploration_out_of_territory`
WHERE `user_id` = {user_id};

-- API: GET /user/status/{userId}
-- 유저 스펙 상태 정보
SELECT *
FROM `user`
WHERE `user_id` = {user_id};

-- API: GET /user/building/{userId}
-- 유저 건물 현황 정보
-- TODO: 건물 타입별로 정렬기능 추가?
SELECT *
FROM `buliding`
WHERE `user_id` = {user_id};

-- API: GET /user/weapon/{userId}
-- 유저 무기 생산, 업그레이드 정보
-- TODO: 무기 타입별, 업그레이드 레벨별 정렬 기능 추가?
SELECT *
FROM `weapon`
WHERE `user_id` = {user_id};

-- API: GET /user/war/{userId}
-- 유저의 전쟁 현황 정보
-- TODO: 전쟁 날짜별, 승리, 패배 별 필터링 or 정렬 기능 추가?
SELECT *
FROM `war`
WHERE `user_id` = {user_id};

-- API: GET /user/alliance/{userId}/{action_type}
-- 유저의 동맹 정보
-- TODO: 유저 동맹 날짜, 수락 별 필터링 or 정렬 기능 추가?
SELECT
  `alliance_id`,
  `res_user_id` AS `user_id`,
  `created_date`,
  `last_update`
FROM `alliance`
WHERE `req_user_id` = {user_id}
UNION
SELECT
  `alliance_id`,
  `req_user_id` AS `user_id`,
  `created_date`,
  `last_update`
FROM `alliance`
WHERE `res_user_id` = {user_id};
