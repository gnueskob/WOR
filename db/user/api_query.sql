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
INSERT INTO `user` (
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
  `war_victory`,
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

##########################################
-- 건물 정보 (building Infomation)
##########################################

-- API: POST /building
-- 새로운 건물 생성
INSERT INTO `building` (
  `user_id`,
  `territory_id`,
  `tile_id`,
  `building_id`,

  `is_constructing`,
  `is_deploying`,
  `is_upgrading`,
  `finish_time`,
  `upgrade`,
  `manpower`,
  `last_update`
) VALUE (
  {user_id},
  {territory_id},
  {tile_id},
  {building_id},

  TRUE,
  FALSE,
  FALSE,
  {finish_time},
  0,
  0,
  NOW()
);

-- API: PUT /building
-- 건물 업그레이드
-- action_type : 0
UPDATE `building`
SET `is_upgrading` = TRUE,
    `finish_time` = {finish_time}
WHERE `building_pk_id` = {building_pk_id};

-- 건물 철거 (인력 회수 및 건물 삭제)
-- action_type : 1
SELECT `manpower`
FROM `building`
WHERE `building_pk_id` = {building_pk_id};

DELETE FROM `building`
WHERE `building_pk_id` = {building_pk_id};

-- API: PUT /building/manpower
-- 건물 인구 배치
-- action_type : 0
UPDATE `building`
SET `is_deploying` = TRUE,
    `manpower` = {manpower},
    `finish_time` = {finish_time},
    `last_update` = NOW()
WHERE `building_pk_id` = {building_pk_id};

-- 건물 인구 배치 취소
-- action_type : 1
SELECT `manpower`
FROM `building`
WHERE `building_pk_id` = {building_pk_id};

UPDATE `building`
SET `manpower` = 0,
    `last_update` = NOW()
WHERE `building_pk_id` = {building_pk_id};

-- no API
-- 건물 건축 완료
UPDATE `building`
SET `is_constructing` = FALSE,
    `last_update` = NOW()
WHERE `building_pk_id` = {building_pk_id};

-- no API
-- 건물 업그레이드 완료
UPDATE `building`
SET `is_upgrading` = FALSE,
    `last_update` = NOW()
WHERE `building_pk_id` = {building_pk_id};

-- no API
-- 건물 인구 배치 완료
UPDATE `building`
SET `is_deploying` = FALSE,
    `last_update` = NOW()
WHERE `building_pk_id` = {building_pk_id};

##########################################
-- 무기 정보 (weapon Infomation)
##########################################

-- API: POST /weapon
-- 무기 생산
INSERT INTO `weapon` (
  `user_id`,
  `weapon_id`,
  `upgrade`,
  `is_upgrading`,
  `is_creating`,
  `finish_time`,
  `last_update`
) VALUE (
  {user_id},
  {weapon_id},
  0,
  FALSE,
  TRUE,
  {finish_time},
  NOW()
);

-- API: PUT /weapon
-- 무기 업그레이드
-- action_type : 0
UPDATE `weapon`
SET `is_upgrading` = TRUE,
    `finish_time` = {finish_time},
    `last_update` = NOW()
WHERE `weapon_pk_id` = {weapon_pk_id};

-- 무기 삭제
-- action_type : 1
DELETE FROM `weapon`
WHERE `weapon_pk_id` = {weapon_pk_id};

-- no API
-- 무기 생산 완료
UPDATE `weapon`
SET `is_creating` = FALSE,
    `last_update` = NOW()
WHERE `weapon_pk_id` = {weapon_pk_id};

-- 무기 업그레이드 완료
UPDATE `weapon`
SET `is_upgrading` = FALSE,
    `upgrade` = `upgrade` + 1,
    `last_update` = NOW()
WHERE `weapon_pk_id` = {weapon_pk_id};

##########################################
-- 탐사 정보 (exploration Infomation)
##########################################

-- API: POST /exploration
-- 영내 탐사
INSERT INTO `exploration_in_territory` (
  `user_id`,
  `tile_id`,
  `finish_time`,
  `last_update`
) VALUE (
  {user_id},
  {tile_id},
  {finish_time},
  NOW()
);

-- API: POST /exploration/territory
-- 다른 영토 탐사
INSERT INTO `exploration_out_of_territory` (
  `user_id`,
  `territory_id`,
  `finish_time`,
  `last_update`
) VALUE (
  {user_id},
  {territory_id},
  {finish_time},
  NOW()
);

-- API: GET /exploration/territory/{territory_id}
-- 해당 영토 정보
-- TODO: 정확히 어떤 정보를 넘겨줄 것인지?
-- 1. 해당 영토의 타입 확인
-- 2. 타입별로 정보 반환
--  2-1. 유저 점령지 영토일 경우 - 해당 영토 유저 스펙?
--  2-2. 유저 점령지인데, 비어있는 영토일 경우 - ?
--  2-3. 특수 지역일 경우 - 보스 젠 상태? / 점령 유저?
SELECT *
FROM `user`
WHERE `territory_id` = {territory_id};

SELECT *
FROM `boss`
WHERE `territory_id` = {territory_id}
  AND `is_active` = TRUE;

SELECT *
FROM `occupation`
WHERE `territory_id` = {territory_id}
  AND `finish_time` >= NOW();

##########################################
-- 전쟁 (war)
##########################################

-- API: POST /war
-- 전쟁 출전
INSERT INTO `war` (
  `user_id`,
  `territory_id`,
  `is_victory`,
  `penanlty_time`,
  `manpower`,
  `resource`,
  `finish_time`,
  `last_update`
) VALUE (
  {user_id},
  {territory_id},
  NULL,
  NULL,
  {manpower},
  {resource},
  {finish_time},
  NOW()
);

-- API: PUT /war
-- 출전 취소
SELECT `manpower`, `resource`
FROM `war`
WHERE `war_id` = {war_id}

-- no API
-- 전쟁 시뮬레이션
-- TODO: 해당 war 레코드를 읽음으로써 패배 진영의 패널티 여부 결정
UPDATE `war`
SET `is_victory` = `attack` > {target_defense},
    `penalty_time` = NOW() + {penalty_time}
WHERE `war_id` = {war_id}

##########################################
-- 동맹 (alliance)
##########################################

-- API: POST /alliance
-- 다른 유저에게 동맹 요청
INSERT INTO `alliance` (
  `is_accepted`,
  `req_user_id`,
  `res_user_id`,
  `created_date`,
  `last_update`
) VALUE (
  FALSE,
  {req_user_id},
  {res_user_id},
  NOW(),
  NOW()
);

-- API: PUT /alliance
-- 다른 유저의 동맹 요청 수락 / 거절
UPDATE `alliance`
SET `is_accepted` = {respond}
WHERE `alliance_id` = {alliance_id};

-- API: GET /mail/{user_id}
-- 동맹으로부터 우편 확인
SELECT *
FROM `mail`
WHERE `to_user_id` = {user_id};

-- API: POST /mail
-- 다른 유저에게 자원 우편 보내기
SELECT `tactical_resource_amount`,
       `food_resource_amount`,
       `luxury_resource_amount`
FROM `user`
WHERE `user_id` = {user_id};

UPDATE `user`
SET `tactical_resource_amount` = `tactical_resource_amount` - {tactical_resource},
    `food_resource_amount` = `food_resource_amount` - {food_resource},
    `luxury_resource_amount` = `luxury_resource_amount` - {luxury_resource}
WHERE `user_id` = {user_id};

INSERT INTO `mail` (
  `from_user_id`,
  `to_user_id`,
  `tactical_resource`,
  `food_resource`,
  `luxury_resource`,
  `is_accepted`,
  `created_date`,
  `last_update`
) VALUE (
  {from_user_id},
  {to_user_id},
  {tactical_resource},
  {food_resource},
  {luxury_resource},
  FALSE,
  NOW(),
  NOW()
);

-- API: PUT /mail
-- 보낸 우편 받기
SELECT `tactical_resource`,
       `food_resource`,
       `luxury_resource`
FROM `mail`
WHERE `to_user_id` = {user_id};

UPDATE `user`
SET `tactical_resource_amount` = `tactical_resource_amount` + {tactical_resource},
    `food_resource_amount` = `food_resource_amount` + {food_resource},
    `luxury_resource_amount` = `luxury_resource_amount` + {luxury_resource}
WHERE `user_id` = {user_id};

UPDATE `mail`
SET `is_accepted` = TRUE
WHERE `mail_id` = {mail_id};

##########################################
-- 레이드 (raid)
##########################################

-- API: POST /raid
-- 특수지역 보스 레이드 신청

-- API: PUT /raid
-- 점령지가 된 특수 지역으로 점령전 신청

##########################################
-- 결산 (calculation)
##########################################

-- API: PUT /calculation
-- 단위 시간 별 정산