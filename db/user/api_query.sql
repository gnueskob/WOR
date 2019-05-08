-- Queries for WOR API
-- made by GNUES

##########################################
## 로그인 (Login)
##########################################

##########################################
-- API: PUT /user/login
##########################################
-- Valid한 유저인지 확인
SELECT `user_id`, `last_visit`
FROM `user`
WHERE `hive_uid` = {hive_uid}
  AND `hive_id` = {hive_id};

-- TODO: 유저 로그인시 만료된 데이터 삭제

-- 이전 접속때 완료 처리 하지 못한 각종 시간 소모 작업들 (Pending 작업) 처리

-- 업그레이드의 경우 user_id 동등비교와 finish_time 범위 비교로 인덱스를 태움
-- 인덱스 효율이 좋지 않겠지만, 유저당 건물 수, 무기 수는 별로 없으므로 감안
-- [무기 업그레이드]
-- 완성 처리 pending 무기들 업그레이드 수치 및 업그레이드 상태 갱신
UPDATE `weapon`
SET `is_upgrading` = FALSE,
    `upgrade` = `upgrade` + 1
WHERE `user_id` = {user_id}
  AND `is_upgrading` = TRUE
  -- AND `upgrade_finish_time` >= {last_visit}
  AND `upgrade_finish_time` <= NOW();

-- [건물 업그레이드]
-- 업그레이드 pending 건물들 처리
UPDATE `building`
SET `is_upgrading` = FALSE,
    `upgrade` = `upgrade` + 1
WHERE `user_id` = {user_id}
  AND `is_upgrading` = TRUE
  -- AND `upgrade_finish_time` >= {last_visit}
  AND `upgrade_finish_time` <= NOW();

-- [성 업그레이드]
-- 성 업그레이드 pending 처리
-- 단, 이때는 last_visit을 갱신하지 않음
UPDATE `user`
SET `is_upgrading` = FALSE,
    `castle_level` = `castle_level` + 1
WHERE `user_id` = {user_id}
  AND `is_upgrading` = TRUE
  -- AND `upgrade_finish_time` >= {last_visit}
  AND `upgrade_finish_time` <= NOW();

-- [인구 자동 생산 및 식량 자원 소모]
-- 해당 유저가 인구 자동 생성 모드인지 확인
-- 맞을 경우 이전 접속 종료 기간 부터 현재까지 인구 생성 및 단위시간별 식량자원 감소량 계산
SELECT `auto_generate_manpower`, `last_visit`, `manpower`, `appended_manpower`
FROM `user`
WHERE `user_id` = {user_id}
-- TODO: 자원 잉여 생산량보다 식량자원 소비율이 더 클때 처리

-- [자원 생산]
-- 공격력, 방어력 버프의 경우는 상관없지만, 충성도 & 인구수 관련 버프는 자원에 영향을 미칠 수 있음
-- 만약 인구하락, 충성도 버프 하락으로 자원 생산에 디버프가 발생할 경우 기존 자원 생산량에 차감할 양 계산
-- 때문에 버프 데이터 제거는 자원 생산 후 처리

-- 접속 종류 이후 충성도나 인구 버프 중 기간이 끝난 버프 기준으로 충성도 산정, 자원 증분치 추가 계산
-- 1. 해당 버프 완료 기준으로 충성도 재 계산
-- 2. 재 계산 된 충성도와 함께 finish_time ~ NOW() 까지 충성도로 인한 자원 생산 추가/감소 량 계산
-- + 인구 버프의 경우 버프로 인한 인구 상승치가 현재 남은 가용인력보다 높을 경우 충성도 하락
SELECT `b`.`buf_pk_id`, `b`.`buf_id`, `b`.`finish_time`, `u`.`appended_manpower`
FROM `buf` AS `b`, `user` AS `u`
WHERE `b`.`user_id` = `u`.`user_id`
  AND `b`.`user_id` = {user_id}
  AND `b`.`buf_id` IN ({PLAN_LUXURY_BUF}, {PLAN_MANPOWER_BUF}) -- PLAN_LUXURY_BUF : 사치자원 버프, PLAN_MANPOWER_BUF : 인구 증가 버프
  AND `b`.`finish_time` BETWEEN `u`.`last_visit` AND NOW();

-- 접속 종류 이후 인구 배치가 완료된 자원 채취 건물에서 생산되는 자원 추가로 계산
-- 1. 어떤 자원인지 기획데이터 확인
-- 2. 해당 건물에 최소 인력이 배치되어 있는지 manpower 비교
-- 3. deploy_finish_time ~ NOW() 까지 해당 자원 채취건물에서 생산되는 자원 계산
SELECT `building_pk_id`, `resource_id`, `manpower`
FROM `building` AS `b`, `user` AS `u`
WHERE `b`.`user_id` = `u`.`user_id`
  AND `b`.`user_id` = {user_id}
  AND `b`.`building_id` IN ({PLAN_MINE_ID}, {PLAN_FARM_ID}) -- PLAN_MINE_ID : 광산, PLAN_FARM_ID : 농장
  AND `b`.`deploy_finish_time` BETWEEN `u`.`last_visit` AND NOW();

-- 마지막 접속 종료 기준 생산 가능한 자원
SELECT `building_pk_id`, `resource_id`, `manpower`
FROM `building` AS `b`, `user` AS `u`
WHERE `b`.`user_id` = `u`.`user_id`
  AND `b`.`user_id` = {user_id}
  AND `b`.`building_id` IN ({PLAN_MINE_ID}, {PLAN_FARM_ID}) -- PLAN_MINE_ID : 광산, PLAN_FARM_ID : 농장
  AND `b`.`deploy_finish_time` < `u`.`last_visit`;

-- 해당 자원 기준으로 자원 생성 시뮬레이션
-- last_visit 부터 기획 단위 시간별로 체크
-- 인구 생산, 식량자원 소비, 버프(충성도), 종료 후 완료되었던 자원 채취 건물 인구배치 등
-- 전부 계산해서 최종적으로 변화하는 자원, 인구 수 측정
UPDATE `user`
SET `manpower_amount` = {manpower_amount}
    `appended_manpower` = {appended_manpower}
    `tactical_resource_amount` = {tactical_resource_amount}
    `food_resource_amount` = {food_resource_amount}
    `luxury_resource_amount` = {luxury_resource_amount}
    `last_update` = NOW()
WHERE `user_id` = {user_id};

-- [선전포고]
-- 접속 종료 후 완료된 선전포고 확인
SELECT `w`.`war_id`, `w`.`attack`, `w`.`manpower`
FROM `war` AS `w`, `user` AS `u`
WHERE `w`.`user_id` = `u`.`user_id`
  AND `w`.`user_id` = {user_id}
  AND `w`.`finish_time` BETWEEN `u`.`last_visit` AND NOW()
  AND `is_victory` IS NULL;

-- 해당 선전포고 시뮬레이션
-- 상대방 성 레벨 측정
SELECT `user_id` AS `target_user_id`, `castle_level`
FROM `user`
WHERE `territory_id` = {territory_id};

-- 상대 방어탑 측정
SELECT `building_pk_id`, `upgrade`, `manpower`
FROM `building`
WHERE `user_id` = {target_user_id}
  AND `building_id` = {PLAN_DEFENSE_POWER} -- PLAN_DEFENSE_POWER : 방어탑
  AND `deploy_finish_time` <= NOW();

-- 성 레벨과 방어탑 레벨 기준으로 방어력 계산 후 전쟁 시뮬레이션
UPDATE `war`
SET `is_victory` = {is_victory}
    `penanlty_finish_time` = {penanlty_finish_time}
WHERE `war_id` = {war_id};

-- TODO: 전쟁 보상 결정후 쿼리 정리 및 트랜잭션 처리 고민 (UPDATE with JOIN)
-- 승리 할 경우
-- 패배 할 경우

-- [약탈]
-- 다른 유저로부터 선전포고를 당한 경우에 완료처리가 안된 경우
SELECT `w`.`war_id`, `w`.`attack`, `w`.`manpower`
FROM `war` AS `w`, `user` AS `u`
WHERE `w`.`territory_id` = `u`.`territory_id`
  AND `w`.`territory_id` = {territory_id}
  AND `w`.`finish_time` BETWEEN `u`.`last_visit` AND NOW()
  AND `is_victory` IS NULL;

-- 자신의 방어력 측정
SELECT `castle_level`
FROM `user`
WHERE `user_id` = {user_id};

SELECT `building_pk_id`, `upgrade`
FROM `building`
WHERE `user_id` = {user_id}
  AND `building_id` = {PLAN_DEFENSE_POWER} -- PLAN_DEFENSE_POWER : 방어탑
  AND `deploy_finish_time` <= NOW();

-- 성 레벨과 방어탑 레벨 기준으로 방어력 계산 후 전쟁 시뮬레이션
UPDATE `war`
SET `is_victory` = {is_victory}
    `penanlty_finish_time` = {penanlty_finish_time}
WHERE `war_id` = {war_id};

-- TODO: 전쟁 보상 결정후 쿼리 정리 및 트랜잭션 처리 고민 (UPDATE with JOIN)
-- 승리 할 경우
-- 패배 할 경우

##########################################
-- API: POST /user/register
##########################################
-- 신규 유저에게 주어지는 영토 검증
-- 1. 영토 타입을 우선 검사 (유저 사용 가능 영토인지) [기획데이터]
-- 2. 해당 영토를 다른 유저가 이미 사용중인지 확인
SELECT *
FROM `user`
WHERE `territory_id` = {selected_territory_id};

-- 중복된 이름인지 확인
SELECT *
FROM `user`
WHERE `name` = {input_name};

-- 실제 유저 정보 추가
-- 추가된 user_id 반환
INSERT INTO `user` (
  `hive_id`,
  `hive_uid`,

  `last_visit`,
  `register_date`,
  `last_update`,

  `territory_id`,
  `name`,

  `manpower_amount`,
  `tactical_resource_amount`,
  `food_resource_amount`,
  `luxury_resource_amount`
) VALUE (
  {hive_id},
  {hive_uid},

  NOW(),
  NOW(),
  NOW(),

  {selected_territory_id},
  {user_name},

  {initial_manpower},
  {initial_tactical_resource_amount},
  {initial_food_resource_amount},
  {initial_luxury_resource_amount}
);

##########################################
-- 유저 정보 (User Infomation)
##########################################

##########################################
-- API: GET /user/tiles/{userId}
##########################################
-- 유저 영토의 영내 맵 탐사 현황 정보
SELECT *
FROM `exploration_in_territory`
WHERE `user_id` = {user_id};

##########################################
-- API: GET /user/territory/{userId}
##########################################
-- 유저 영토 탐사 현황 정보
SELECT *
FROM `exploration_out_of_territory`
WHERE `user_id` = {user_id};

##########################################
-- API: GET /user/status/{userId}
##########################################
-- 유저 스펙 상태 정보
SELECT *
FROM `user`
WHERE `user_id` = {user_id};

##########################################
-- API: GET /user/building/{userId}
##########################################
-- 유저 건물 현황 정보
-- TODO: 건물 타입별로 정렬기능 추가?
SELECT *
FROM `buliding`
WHERE `user_id` = {user_id};

##########################################
-- API: GET /user/weapon/{userId}
##########################################
-- 유저 무기 생산, 업그레이드 정보
-- TODO: 무기 타입별, 업그레이드 레벨별 정렬 기능 추가?
SELECT *
FROM `weapon`
WHERE `user_id` = {user_id};

##########################################
-- API: GET /user/war/{userId}
##########################################
-- 유저의 전쟁 현황 정보
-- TODO: 전쟁 날짜별, 승리, 패배 별 필터링 or 정렬 기능 추가?
SELECT *
FROM `war`
WHERE `user_id` = {user_id};

##########################################
-- API: GET /user/alliance/{userId}/{action_type}
##########################################
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

##########################################
-- API: POST /building/new
##########################################
-- 새로운 건물 생성
INSERT INTO `building` (
  `user_id`,
  `territory_id`,
  `tile_id`,
  `building_id`,

  `create_finish_time`,
  `last_update`
) VALUE (
  {user_id},
  {territory_id},
  {tile_id},
  {building_id},

  {finish_time},
  NOW()
);

##########################################
-- API: PUT /building
##########################################
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

##########################################
-- API: PUT /building/upgrade_finish
##########################################
-- 건물 업그레이드 완료
UPDATE `building`
SET `is_upgrading` = FALSE,
    `upgrade` = `upgrade` + 1
WHERE `building_pk_id` = {building_pk_id}

##########################################
-- API: PUT /building/manpower
##########################################
-- 건물 인구 배치
-- action_type: 0
UPDATE `building`
SET `manpower` = {manpower},
    `deploy_finish_time` = {deploy_finish_time},
    `last_update` = NOW()
WHERE `building_pk_id` = {building_pk_id};

-- 건물 인구 배치 취소
-- action_type: 1
SELECT `manpower`
FROM `building`
WHERE `building_pk_id` = {building_pk_id};

UPDATE `building`
SET `manpower` = 0,
    `last_update` = NOW(),
    `deploy_finish_time` = NULL
WHERE `building_pk_id` = {building_pk_id};

##########################################
## 무기 정보 (weapon Infomation)
##########################################

##########################################
-- API: POST /weapon/new
##########################################
-- 무기 생산
INSERT INTO `weapon` (
  `user_id`,
  `weapon_id`,
  `create_finish_time`,
  `last_update`
) VALUE (
  {user_id},
  {weapon_id},
  {finish_time},
  NOW()
);

##########################################
-- API: PUT /weapon/upgrade
##########################################
-- 무기 업그레이드
-- action_type : 0
UPDATE `weapon`
SET `is_upgrading` = TRUE,
    `upgrade_finish_time` = {finish_time},
    `last_update` = NOW()
WHERE `weapon_pk_id` = {weapon_pk_id};

-- 무기 삭제
-- action_type : 1
DELETE FROM `weapon`
WHERE `weapon_pk_id` = {weapon_pk_id};

##########################################
-- API: PUT /weapon/upgrade_finish
##########################################
-- 무기 업그레이드 완료
UPDATE `weapon`
SET `is_upgrading` = FALSE,
    `upgrade` = `upgrade` + 1,
    `last_update` = NOW()
WHERE `weapon_pk_id` = {weapon_pk_id};

##########################################
## 탐사 정보 (exploration Infomation)
##########################################

##########################################
-- API: POST /exploration
##########################################
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

##########################################
-- API: POST /exploration/territory
##########################################
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

##########################################
-- API: GET /exploration/territory/{territory_id}
##########################################
-- 해당 영토 정보
-- 1. 해당 영토의 타입 확인
-- 2. 타입별로 정보 반환
--  2-1. 유저 점령지 영토일 경우 - 해당 영토 유저 잉여 자원, 인구 수 등
--  2-2. 유저 점령지인데, 비어있는 영토일 경우 - empty
--  2-3. 특수 지역일 경우 - 보스 몬스터 정보 / 점령 동맹 유저 목록
SELECT *
FROM `user`
WHERE `territory_id` = {territory_id};

SELECT *
FROM `occupation` AS `o`, `user` AS `u`
WHERE `u`.`territory_id` = `o`.`territory_id`
  AND `o`.`territory_id` = {territory_id}
  AND `o`.`finish_time` >= NOW();
-- + 점령지에 적용중인 버프

##########################################
-- 전쟁 (war)
##########################################

##########################################
-- API: POST /war
##########################################
-- 전쟁 출전
-- 공격력 계산
SELECT `manpower`, `upgrade`
FROM `building`
WHERE `user_id` = {user_id}
  AND `building_id` = {PLAN_DEFENSE_POWER}
  AND `deploy_finish_time` <= NOW();

SELECT `weapon_id`, `upgrade`
FROM `weapon`
WHERE `user_id` = {user_id}
  AND `create_finish_time` <= NOW();

-- 모든 종류 버프 긁어모아서 기획 데이터에서 공격력 증가량만 합산해서 곱연산
SELECT `buf_id`
FROM `buf`
WHERE `user_id` = {user_id}
  AND `finish_time` >= NOW();

-- 실제 war 정보 추가
INSERT INTO `war` (
  `user_id`,
  `territory_id`,
  `attack`,
  `manpower`,
  `resource`,
  `finish_time`,
  `last_update`
) VALUE (
  {user_id},
  {territory_id},
  {attack},
  {manpower},
  {resource},
  {finish_time},
  NOW()
);

UPDATE `user`
SET `manpower_amount` = `manpower_amount` - {manpower},
    `food_resource_amount` = `food_resource_amount` - {resource}
WHERE `user_id` = {user_id};

##########################################
-- API: PUT /war
##########################################
-- 전쟁 준비 완료
-- action_type: 0
-- 전쟁 시뮬레이션
SELECT `attack`, `manpower`
FROM `war`
WHERE `war_id` = {war_id};

SELECT `u`.`castle_level`, `b`.`building_pk_id`, `b`.`upgrade`, `b`.`manpower`
FROM `building` AS `b`, `user` AS `u`
WHERE `b`.`territory_id` = `u`.`territory_id`
  AND `u`.`territory_id` = {territory_id}
  AND `building_id` = {PLAN_DEFENSE_POWER} -- PLAN_DEFENSE_POWER : 방어탑
  AND `deploy_finish_time` <= NOW();

-- 계산된 방어력, 공격력을 기준으로 전쟁 시뮬레이션
UPDATE `war`
SET `is_victory` = {is_victory}
    `penanlty_finish_time` = {penanlty_finish_time}
WHERE `war_id` = {war_id};

-- 전쟁 보상은 어떻게 처리?
-- 승리시
-- 패배시

-- 출전 취소
-- action_type: 1
SELECT `manpower`, `resource`
FROM `war`
WHERE `war_id` = {war_id}

DELETE FROM `war`
WHERE `war_id` = {war_id};

UPDATE `user`
SET `food_resource_amount` = `food_resource_amount` + {half_resource}
WHERE `user_id` = {user_id}

##########################################
## 동맹 (alliance)
##########################################

##########################################
-- API: POST /alliance
##########################################
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

##########################################
-- API: PUT /alliance
##########################################
-- 다른 유저의 동맹 요청 수락
-- action_type: 0
UPDATE `alliance`
SET `is_accepted` = TRUE
WHERE `alliance_id` = {alliance_id};

-- 다른 유저의 동맹 요청 거절
-- action_type: 1
DELETE FROM `alliance`
WHERE `alliance_id` = {alliance_id};

##########################################
-- API: GET /mail/{user_id}
##########################################
-- 동맹으로부터 우편 확인
SELECT *
FROM `mail`
WHERE `to_user_id` = {user_id};

##########################################
-- API: POST /mail
##########################################
-- 다른 유저에게 자원 우편 보내기
-- 자신에게 충분한 향의 자원이 있는지 부터 검사
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

##########################################
-- API: PUT /mail
##########################################
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

##########################################
-- API: POST /raid
##########################################
-- 특수지역 보스 레이드 신청


-- API: PUT /raid
-- 점령지가 된 특수 지역으로 점령전 신청

##########################################
-- 결산 (calculation)
##########################################

-- API: PUT /calculation
-- 단위 시간 별 정산