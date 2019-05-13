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

-- 이전 접속때 완료 처리 하지 못한 각종 시간 소모 작업들 (Pending 작업) 처리

-- 업그레이드의 경우 user_id 동등비교와 finish_time 범위 비교로 인덱스를 태움
-- 인덱스 효율이 좋지 않겠지만, 유저당 건물 수, 무기 수는 별로 없으므로 감안
-- [무기 업그레이드]
-- [pending] 완성 처리 pending 무기들 업그레이드 수치 및 업그레이드 상태 갱신
UPDATE `weapon`
SET `is_upgrading` = FALSE,
    `upgrade` = `upgrade` + 1
    `last_upate` = NOW()
WHERE `user_id` = {user_id}
  AND `is_upgrading` = TRUE
  AND `upgrade_finish_time` <= NOW();

-- [건물 업그레이드]
-- [pending] 업그레이드 pending 건물들 처리
UPDATE `building`
SET `is_upgrading` = FALSE,
    `upgrade` = `upgrade` + 1
    `last_upate` = NOW()
WHERE `user_id` = {user_id}
  AND `is_upgrading` = TRUE
  AND `upgrade_finish_time` <= NOW();

-- [성 업그레이드]
-- 성 업그레이드 pending 처리
UPDATE `user`
SET `is_upgrading` = FALSE,
    `castle_level` = `castle_level` + 1
WHERE `user_id` = {user_id}
  AND `is_upgrading` = TRUE
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
SELECT `buf_pk_id`, `buf_id`, `finish_time`, `appended_manpower`
FROM `buf`
WHERE `user_id` = {user_id}
  AND `finish_time` BETWEEN {last_visit} AND NOW();

-- 접속 종류 이후 인구 배치가 완료된 자원 채취 건물에서 생산되는 자원 추가로 계산
-- 박물관의 경우는 충성도 계산을 위함
-- 1. 어떤 자원인지 기획데이터 확인
-- 2. 해당 건물에 최소 인력이 배치되어 있는지 manpower 비교
-- 3. deploy_finish_time ~ NOW() 까지 해당 자원 채취건물에서 생산되는 자원 계산
SELECT `building_pk_id`, `resource_id`, `manpower`, `building_id`, `deploy_finish_time`
FROM `building`
WHERE `user_id` = {user_id}
  -- AND `building_id` IN ({PLAN_MINE_ID}, {PLAN_FARM_ID}, {PLAN_MUSEUM_ID}) -- PLAN_MINE_ID : 광산, PLAN_FARM_ID : 농장, PLAN_MUSEUM_ID : 박물관
  AND `deploy_finish_time` BETWEEN {last_visit} AND NOW();

-- 마지막 접속 종료 기준 생산 가능한 자원
SELECT `building_pk_id`, `resource_id`, `manpower`
FROM `building`
WHERE `user_id` = {user_id}
  -- AND `building_id` IN ({PLAN_MINE_ID}, {PLAN_FARM_ID}) -- PLAN_MINE_ID : 광산, PLAN_FARM_ID : 농장
  AND `deploy_finish_time` <= {last_visit};

-- 해당 자원 기준으로 자원 생성 시뮬레이션
-- last_visit 부터 기획 단위 시간별로 체크
-- 인구 생산, 식량자원 소비, 버프(충성도), 종료 후 완료되었던 자원 채취 건물 인구배치 등
-- 전부 계산해서 최종적으로 변화하는 자원, 인구 수 측정
UPDATE `user`
SET `manpower_amount` = `manpower_amount` + {manpower_amount_diff}
    `appended_manpower` = `appended_manpower` + {appended_manpower_diff}
    `tactical_resource_amount` = `tactical_resource_amount` + {tactical_resource_amount_diff}
    `food_resource_amount` = `food_resource_amount` + {food_resource_amount_diff}
    `luxury_resource_amount` = `luxury_resource_amount` + {luxury_resource_amount_diff}
    `last_update` = NOW()
WHERE `user_id` = {user_id};

-- [선전포고]
-- 접속 종료 후 완료된 선전포고 확인
SELECT `war_id`, `attack`, `manpower`
FROM `war`
WHERE `user_id` = {user_id}
  AND `finish_time` BETWEEN {last_visit} AND NOW();

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

-- [약탈]
-- 다른 유저로부터 선전포고를 당한 경우에 완료처리가 안된 경우
SELECT `war_id`, `attack`, `manpower`
FROM `war`
WHERE `territory_id` = {territory_id}
  AND `finish_time` BETWEEN {last_visit} AND NOW();

-- 자신의 방어력 측정
-- [중복]
SELECT `castle_level`
FROM `user`
WHERE `user_id` = {user_id};

-- [중복]
SELECT `building_pk_id`, `upgrade`
FROM `building`
WHERE `user_id` = {user_id}
  AND `deploy_finish_time` <= NOW();

-- 성 레벨과 방어탑 레벨 기준으로 방어력 계산 후 전쟁 시뮬레이션
-- [중복]
UPDATE `war`
SET `is_victory` = {is_victory}
    `penanlty_finish_time` = {penanlty_finish_time}
WHERE `war_id` = {war_id};

-- 전쟁 보상 결정후 쿼리 정리 및 트랜잭션 처리 고민 (PDO 트랜잭션 처리)
-- 승리 할 경우
-- 패배 할 경우
-- (아래 전쟁 부분 참조)

-- 레이드 완료 처리
-- (아래 레이드 부분 참조)

-- 만료된 데이터 삭제
-- 버프
DELETE FROM `buf`
WHERE `user_id` = {user_id}
  AND `finish_time` < SUBDATE(NOW(), INTERVAL {expire_date_buf} DAY);

-- 전쟁
-- 1. 특수 보스지역이 아닌 유저 영토로 출전했던 기록 중 이미 끝난 데이터
-- 2. 특수 보스 지역으로 출전하여 패배한 기록 중 만료된 데이터
SELECT `war_id`, `territory_id`, `is_victory`
FROM `war`
WHERE `user_id` = {user_id}
  AND `finish_time` < SUBDATE(NOW(), INTERVAL {expire_date_war} DAY);

DELETE FROM `war`
WHERE `war_id` IN ({war_ids});

-- 특수 보스지역으로 레이드 출전하여 점령기간까지 만료된 출전 데이터
SELECT DISTINCT `raid_id`
FROM `occupation`
WHERE `user_id` = {user_id}
  AND `finish_time` < NOW();

DELETE FROM `war`
WHERE `raid_id` IN ({raid_ids});

-- 레이드 정보 중 만료된 데이터
DELETE FROM `raid`
WHERE `finish_time` < SUBDATE(NOW(), INTERVAL {expire_date_raid} DAY);

-- 점령
DELETE FROM `occupation`
WHERE `user_id` = {user_id}
  AND `finish_time` < SUBDATE(NOW(), INTERVAL {expire_date_occupation} DAY);

-- 우편
DELETE FROM `mail`
WHERE `to_user_id` = {user_id}
  AND `is_accepted` = TRUE
  AND `last_update` < SUBDATE(NOW(), INTERVAL {expire_date_mail} DAY);

##########################################
-- API: POST /user/register
##########################################
-- 신규 유저에게 주어지는 영토 검증
-- 1. 영토 타입을 우선 검사 (유저 사용 가능 영토인지) [기획데이터]
-- 2. 해당 영토를 다른 유저가 이미 사용중인지 확인
-- [중복]
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
  `unit_time`,

  `territory_id`,
  `name`,

  `manpower_amount`,
  `tactical_resource_amount`,
  `food_resource_amount`,
  `luxury_resource_amount`
) VALUES (
  {hive_id},
  {hive_uid},

  NOW(),
  NOW(),
  NOW(),
  {PLAN_UNIT_TIME},

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
-- [중복]
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
-- 유저의 동맹중인 영토 정보
-- action_type : 0
SELECT
  `a`.`alliance_id`, `a`.`created_date`, `a`.`last_update`,
  `u`.`user_id`, `u`.`name`, `u`.`territory_id`
FROM `alliance` AS `a`, `user` AS `u`
WHERE `a`.`res_user_id` = `u`.`user_id`
  AND `a`.`req_user_id` = {user_id}
  AND `is_accepted` = TRUE
UNION
SELECT
  `a`.`alliance_id`, `a`.`created_date`, `a`.`last_update`,
  `u`.`user_id`, `u`.`name`, `u`.`territory_id`
FROM `alliance` AS `a`, `user` AS `u`
WHERE `a`.`req_user_id` = `u`.`user_id`
  AND `a`.`res_user_id` = {user_id}
  AND `is_accepted` = TRUE

-- 자신에게 동맹을 요청한 영토
-- action_type : 1
SELECT
  `a`.`alliance_id`, `a`.`created_date`, `a`.`last_update`,
  `u`.`user_id`, `u`.`name`, `u`.`territory_id`
FROM `user` AS `u`, `alliance` AS `a`
WHERE `u`.`user_id` = `a`.`req_user_id`
  AND `a`.`res_user_id` = {user_id}
  AND `a`.`is_accepted` = FALSE;

-- 동맹 요청을 보낸 영토
SELECT
  `a`.`alliance_id`, `a`.`created_date`, `a`.`last_update`,
  `u`.`user_id`, `u`.`name`, `u`.`territory_id`
FROM `user` AS `u`, `alliance` AS `a`
WHERE `u`.`user_id` = `a`.`req_user_id`
  AND `a`.`res_user_id` = {user_id}
  AND `a`.`is_accepted` = FALSE;


##########################################
-- API: POST /buf
##########################################
-- 버프 사용
-- buf_id : 1 (사치자원 버프)
-- 현재 총 인구수, 사치자원 양 조회
SELECT `manpower_amount`, `luxury_resource_amount`
FROM `user`
WHERE `user_id` = {user_id};

-- 충분하면 버프 시전
INSERT INTO `buf` (
  `user_id`,
  `buf_id`,
  `finish_time`,
  `last_update`
) VALUES (
  {user_id},
  {PLAN_LUXURY_BUF},
  {finish_time},
  NOW()
);

-- 사치자원 감소
UPDATE `user`
SET `luxury_resource_amount` = `luxury_resource_amount` - {diff}
WHERE `user_id` = {user_id};


##########################################
-- 건물 정보 (building Infomation)
##########################################

##########################################
-- API: POST /building/new
##########################################
-- 해당 타일이 탐사된 타일인지 확인
SELECT *
FROM `exploration_in_territory`
WHERE `user_id` = {user_id}
  AND `tile_id` = {tile_id}
  AND `finish_time` <= NOW();

-- 새로운 건물 생성
INSERT INTO `building` (
  `user_id`,
  `territory_id`,
  `tile_id`,
  `building_id`,
  `resource_id`,

  `create_finish_time`,
  `last_update`
) VALUES (
  {user_id},
  {territory_id},
  {tile_id},
  {building_id},
  {resource_id},

  {finish_time},
  NOW()
);

##########################################
-- API: PUT /building
##########################################
-- 해당 건물 이미 업그레이드 중인지 검사
-- [중복]
SELECT `is_upgrading`
FROM `building`
WHERE `building_pk_id` = {building_pk_id};

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
-- [중복]
UPDATE `building`
SET `is_upgrading` = FALSE,
    `upgrade` = `upgrade` + 1
WHERE `building_pk_id` = {building_pk_id}

##########################################
-- API: PUT /building/manpower
##########################################
-- 건물에 이미 최대 인구배치가 돼있는지 검사
-- 유저의 가용병력이 충분한지 검사
SELECT `manpower_amount`
FROM `user`
WHERE `user_id` = {user_id};

-- 배치되고 있는 인력도 포함해야함 ^^7
-- [중복]
SELECT SUM(`manpower`)
FROM `building`
WHERE `user_id` = {user_id};

-- 건물 인구 배치
-- action_type: 0
-- [중복]
UPDATE `building`
SET `manpower` = {manpower},
    `deploy_finish_time` = {deploy_finish_time},
    `last_update` = NOW()
WHERE `building_pk_id` = {building_pk_id};

-- 건물 인구 배치 취소
-- action_type: 1
-- [중복]
SELECT `manpower`
FROM `building`
WHERE `building_pk_id` = {building_pk_id};

-- [중복]
UPDATE `building`
SET `manpower` = 0,
    `last_update` = NOW(),
    `deploy_finish_time` = NULL
WHERE `building_pk_id` IN ({building_pk_ids});

##########################################
## 무기 정보 (weapon Infomation)
##########################################

##########################################
-- API: POST /weapon/new
##########################################
-- 공방 인구배치 완료 여부 확인
SELECT `manpower`
FROM `building`
WHERE `building_pk_id` = {building_pk_id}
  AND `deploy_finish_time` <= NOW();
-- 이미 해당 무기 생산 중인지 확인
SELECT `weapon_id`
FROM `weapon`
WHERE `user_id` = {user_id}
  AND `weapon_id` = {weapon_id};
-- 무기 생산
INSERT INTO `weapon` (
  `user_id`,
  `weapon_id`,
  `create_finish_time`,
  `last_update`
) VALUES (
  {user_id},
  {weapon_id},
  {finish_time},
  NOW()
);

##########################################
-- API: PUT /weapon/upgrade
##########################################
-- 공방 인구배치 완료 여부 확인
-- [중복]
SELECT `manpower`
FROM `building`
WHERE `building_pk_id` = {building_pk_id}
  AND `deploy_finish_time` <= NOW();
-- 이미 해당 무기 업그레이드 중인지 확인
-- [중복]
SELECT `is_upgrading`
FROM `weapon`
WHERE `weapon_pk_id` = {weapon_pk_id};
-- 무기 업그레이드
-- action_type : 0
-- [중복]
UPDATE `weapon`
SET `is_upgrading` = TRUE,
    `upgrade_finish_time` = {finish_time},
    `last_update` = NOW()
WHERE `weapon_pk_id` = {weapon_pk_id};

-- 무기 삭제
-- action_type : 1
-- [중복]
DELETE FROM `weapon`
WHERE `weapon_pk_id` = {weapon_pk_id};

##########################################
-- API: PUT /weapon/upgrade_finish
##########################################
-- 무기 업그레이드 완료
-- [중복]
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
) VALUES (
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
) VALUES (
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
-- [중복]
SELECT *
FROM `user`
WHERE `territory_id` = {territory_id};

-- 해당 영토를 점령중인 유저 조회
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
-- 해당 영토가 이미 탐사되었는지 검사
SELECT `exploration_id`
FROM `exploration_out_of_territory`
WHERE `user_id` = {user_id}
  AND `territory_id` = {territory_id}
  AND `finish_time` <= NOW();

-- 해당 영토 유저가 이미 전쟁 중인지 검사
SELECT `war_id`
FROM `war`
WHERE `territory_id` = {territory_id}
  AND `finish_time` >= NOW();

-- 충분한 식량자원 있는지 검사
SELECT `food_resource_amount`
FROM `user`
WHERE `user_id` = {user_id};

-- 전쟁 출전
-- 공격력 계산
-- [중복]
SELECT `manpower`, `upgrade`
FROM `building`
WHERE `user_id` = {user_id}
  AND `building_id` = {PLAN_ARMY_ID}
  AND `deploy_finish_time` <= NOW();

-- 현재 생산이 완료된 무기 정보 조회
SELECT `weapon_id`, `upgrade`
FROM `weapon`
WHERE `user_id` = {user_id}
  AND `create_finish_time` <= NOW();

-- 모든 종류 버프 긁어모아서 기획 데이터에서 공격력 증가량만 합산해서 곱연산
SELECT `buf_id`
FROM `buf`
WHERE `user_id` = {user_id}
  -- AND `buf_id` IN ({PLAN_ATTACK_BUFS})
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
) VALUES (
  {user_id},
  {territory_id},
  {attack},
  {manpower},
  {resource},
  {finish_time},
  NOW()
);

-- 인구수는 그대로 병영에 두고, 식량자원만 잠시 빼둠
-- [중복]
UPDATE `user`
SET `food_resource_amount` = `food_resource_amount` - {resource}
WHERE `user_id` = {user_id};

##########################################
-- API: PUT /war
##########################################
-- 전쟁 준비 완료
-- action_type: 0
-- 전쟁 시뮬레이션
SELECT `attack`, `manpower`, `food_resource`
FROM `war`
WHERE `war_id` = {war_id};

-- [현재 방어력 계산]
-- 현재 상대방 성 업그레이드 단계
-- [중복]
SELECT `castle_level`, `user_id` AS `target_user_id`
FROM `user`
WHERE `territory_id` = {territory_id};

-- 현재 상대방 방어탑 업그레이드 수치
-- [중복]
SELECT `building_pk_id`, `upgrade`, `manpower`
FROM `building`
WHERE `user_id` = {target_user_id}
  AND `building_id` = {PLAN_DEFENSE_POWER}
  AND `deploy_finish_time` <= NOW();

-- 계산된 방어력, 공격력을 기준으로 전쟁 시뮬레이션
-- [승리시]
-- [중복]
UPDATE `war`
SET `is_victory` = TRUE,
    `penanlty_finish_time` = {penanlty_finish_time}
WHERE `war_id` = {war_id};

-- 각 건물에서 인구수 차감 (업그레이드 낮은 순으로)
-- [중복]
SELECT `building_pk_id`
FROM `building`
WHERE `user_id` = {user_id}
  AND `building_id` = {PLAN_ARMY_ID}
  AND `deploy_finish_time` <= NOW();
-- GROUP BY `upgrade`, `manpower`;

-- [중복]
UPDATE `building`
SET `manpower` = `manpower` - {diff}
WHERE `building_pk_id` = {building_pk_id};

-- [패배시]
-- [중복]
UPDATE `war`
SET `is_victory` = FALSE
WHERE `war_id` = {war_id};

-- 각 건물에서 인구수 차감
-- [중복]
UPDATE `building`
SET `manpower` = 0
WHERE `building_pk_id` IN ({building_pk_ids});


-- 출전 취소
-- action_type: 1
-- [중복]
SELECT `manpower`, `resource`
FROM `war`
WHERE `war_id` = {war_id}

-- [중복]
DELETE FROM `war`
WHERE `war_id` = {war_id};

-- [중복]
UPDATE `user`
SET `food_resource_amount` = `food_resource_amount` + {half_resource}
WHERE `user_id` = {user_id}

-- 각 건물에서 인구수 차감
-- [중복]
UPDATE `building`
SET `manpower` = `manpower`/2
WHERE `building_pk_id` IN ({building_pk_ids});

##########################################
## 동맹 (alliance)
##########################################

##########################################
-- API: POST /alliance
##########################################
-- 이미 해당 유저에게 동맹 요청을 보낸 적이 있는지 검사
SELECT *
FROM `alliance`
WHERE `unique_key` = {unique_key}

-- 다른 유저에게 동맹 요청
INSERT INTO `alliance` (
  `unique_key`
  `is_accepted`,
  `req_user_id`,
  `res_user_id`,
  `created_date`,
  `last_update`
) VALUES (
  {unique_key}
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

-- 유저가 특정 영토 탐사 했는지 조회
SELECT `user_id`, `territory_id`
FROM `exploration_out_of_territory`
WHERE (`user_id` = {user_id_1} AND `territory_id` = {territory_id_2})
   OR (`user_id` = {user_id_2} AND `territory_id` = {territory_id_1})
-- 만약 탐사가 진행중이면, finish_time 현재로 갱신 후 탐사 인구 반환

-- 탐사 정보가 아예 없을 시 추가
INSERT INTO `exploration_out_of_territory` (
  `user_id`,
  `territory_id`,
  `finish_time`,
  `last_update`
) VALUES (
  {user_id},
  {territory_id},
  {finish_time},
  NOW()
);

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
-- [중복]
SELECT `tactical_resource_amount`,
       `food_resource_amount`,
       `luxury_resource_amount`
FROM `user`
WHERE `user_id` = {user_id};

-- [중복]
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
) VALUES (
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
-- [중복]
SELECT `tactical_resource`,
       `food_resource`,
       `luxury_resource`
FROM `mail`
WHERE `to_user_id` = {user_id};

-- [중복]
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
## [lock] ##
-- 이미 해당 지역에 보스 레이드가 진행중인지 확인
-- [중복]
SELECT `war_id`
FROM `war`
WHERE `territory_id` = {territory_id}
  AND `finish_time` >= NOW();

-- 끌어다 쓸 동맹 병력 조회
-- 각 동맹 유저별로 공격력 계산하여 List화, 보스 레이드 출전 (전쟁 출전 요청 API 참고)
-- war 테이블에 동맹 유저 전부 보스 영토로 출전 (같은 raid_id로)
INSERT INTO `raid` (
  `user_id`,
  `finish_time`
) VALUES (
  {raid_lead_user_id},
  {finish_time}
);
## [lock 해제] ##

##########################################
-- API: PUT /raid
##########################################
-- [전쟁 완료 요청]
-- 전쟁 시뮬레이션 (기획 데이터 보스 체력, 공격력 & 이전에 출전했던 동맹 유저 스펙 기준)
SELECT `territory_id`, `user_id`, `raid_lead_user_id`, `attack`, `manpower`
FROM `war`
WHERE `raid_id` = {raid_id};

-- [승리 시]
-- 승리 상태로 갱신
-- [중복]
UPDATE `war`
SET `is_victory` = TRUE
WHERE `raid_id` = {raid_id};

-- 보스몹 처치 시 동맹 유저 모두 영토 점령 및 보상 획득
UPDATE `user`
SET `tactical_resource_amount` = `tactical_resource_amount` + {boss_reward_tactical_resource_amount},
    `food_resource_amount` = `food_resource_amount` + {boss_reward_food_resource_amount},
    `luxury_resource_amount` = `luxury_resource_amount` + {boss_reward_luxury_resource_amount}
WHERE `user_id` IN ({user_participation});

-- 버프 정보 추가
INSERT INTO `buf` (
  `user_id`,
  `buf_id`,
  `raid_id`,
  `finish_time`,
  `last_update`
) VALUES (
  {user_id},
  {buf_id},
  {finish_time},
  NOW()
), ...;

-- 레이드 정보 만료기간 갱신
UPDATE `raid`
SET `finish_time` = {finish_time}
WHERE `raid_id` = {raid_id};

-- 점령지 정보 추가
INSERT INTO `occupation` (
  `raid_id`,
  `territory_id`,
  `user_id`,
  `finish_time`,
  `last_update`
) VALUES (
  {raid_id},
  {territory_id},
  {user_id},
  {finish_time},
  NOW()
), ...;

-- 레이드 리드 유저 병영 병력 처리
-- [중복]
SELECT `building_pk_id`
FROM `building`
WHERE `user_id` = {raid_lead_user_id}
  AND `building_id` = {PLAN_ARMY_ID}
  AND `deploy_finish_time` <= NOW();

-- 병력 감소
-- [중복]
UPDATE `building`
SET `manpower` = `manpower` - {diff}
WHERE `building_pk_id` = {building_pk_id};

-- [패배 시]
-- 패배 상태로 갱신
-- [중복]
UPDATE `war`
SET `is_victory` = FALSE
WHERE `raid_id` = {raid_id};

-- 각 건물에서 인구수 차감
-- [중복]
UPDATE `building`
SET `manpower` = 0
WHERE `building_pk_id` IN ({building_pk_ids});

##########################################
-- API: POST /occupation
##########################################
-- 점령지가 된 특수 지역으로 점령전 신청
-- 해당 영토가 이미 다른 유저에게 점령중인지 확인
## [lock] ##
SELECT `user_id`
FROM `occupation`
WHERE `territory_id` = {territory_id}
  AND `finish_time` >= NOW();

-- 점령전 신청 가능한 영토이면 점령전 출전
-- 끌어다 쓸 동맹 병력 조회
-- 각 동맹 유저별로 공격력 계산하여 List화, 보스 레이드 출전 (전쟁 출전 요청 API 참고)
-- war 테이블에 동맹 유저 전부 보스 영토로 출전 (같은 raid_id로)
INSERT INTO `raid` (
  `user_id`,
  `finish_time`
) VALUES (
  {raid_lead_user_id},
  {finish_time}
);
## [lock 해제] ##

##########################################
-- API: PUT /occupation
##########################################
-- [점령전 완료 요청]
-- 점령전 시뮬레이션 (점령지역 유저 스펙, 공격력 & 이전에 출전했던 동맹 유저 스펙 기준)
-- 점령중인 동맹 유저들 스펙
SELECT `territory_id`, `user_id`, `raid_lead_user_id`, `attack`, `manpower`, `raid_id`
FROM `war`
WHERE `raid_id` IN (
  SELECT DISTINCT `raid_id`
  FROM `occupation`
  WHERE `territory_id` = {territory_id}
    AND `finish_time` >= NOW()
);

-- 점령 레이드 신청 동맹 유저들 스펙
-- [중복]
SELECT `territory_id`, `user_id`, `raid_lead_user_id`, `attack`, `manpower`, `is_victory`
FROM `war`
WHERE `raid_id` = {raid_id};

-- [승리 시]
-- 승리 상태로 갱신
-- [중복]
UPDATE `war`
SET `is_victory` = TRUE
WHERE `raid_id` = {raid_id};

-- 기존 유저들 버프 정보 만료시키기
-- TODO: UPDATE? OR DELETE?
UPDATE `buf`
SET `finish_time` = SUBDATE(NOW(), INTERVAL 1 SECOND);
WHERE `raid_id` = {before_raid_id};

-- 기존 레이드 정보 만료시키기
UPDATE `raid`
SET `finish_time` = SUBDATE(NOW(), INTERVAL 1 SECOND);
WHERE `raid_id` = {before_raid_id};

-- 버프 정보 추가
-- [중복]
INSERT INTO `buf` (
  `user_id`,
  `buf_id`,
  `finish_time`,
  `last_update`
) VALUES (
  {user_id},
  {buf_id},
  {finish_time},
  NOW()
), ...;

-- 레이드 정보 만료기간 갱신
UPDATE `raid`
SET `finish_time` = {finish_time}
WHERE `raid_id` = {raid_id};

-- 기존 점령 유저 정보 만료시키기
UPDATE `occupation`
SET `finish_time` = NOW()
WHERE `raid_id` = {before_raid_id};

-- 점령지 정보 추가
INSERT INTO `occupation` (
  `territory_id`,
  `user_id`,
  `finish_time`,
  `last_update`
) VALUES (
  {territory_id},
  {user_id},
  {finish_time},
  NOW()
), ...;

-- 점령전 리드 유저 병영 병력 처리
-- [중복]
SELECT `building_pk_id`
FROM `building`
WHERE `user_id` = {raid_lead_user_id}
  AND `building_id` = {PLAN_ARMY_ID}
  AND `deploy_finish_time` <= NOW();

-- 병력 감소
-- [중복]
UPDATE `building`
SET `manpower` = `manpower` - {diff}
WHERE `building_pk_id` = {building_pk_id};

-- [패배 시]
-- 패배 상태로 갱신
-- [중복]
UPDATE `war`
SET `is_victory` = FALSE
WHERE `raid_id` = {raid_id};

-- 각 건물에서 인구수 차감
-- [중복]
UPDATE `building`
SET `manpower` = 0
WHERE `building_pk_id` IN ({building_pk_ids});

##########################################
-- 결산 (calculation)
##########################################

##########################################
-- API: PUT /calculation
##########################################
-- 단위 시간 별 정산
-- 어떤 작업을 단위 시간마다 처리할 것인가?
-- 1. 자원 채취 건물 조건에 맞는 자원 생산
-- 2. 인구 자동 생산모드에서의 인구 생산
-- 3. 총 인구수로 인해 소모되는 식량 자원
-- 4. 다른 유저로부터의 선전 포고 노티
-- 5. 다른 유저로부터의 동맹 신청 노티
-- 6. 만료되었지만 처리되지 않은 요청들?

-- [검증]
-- 제 시간에 온 결산 요청인가?
-- 이전 결산 요청의 응답으로 보낸 충성도 기반 단위시간만큼 충분히 시간이 흐른 후 온 요청인지 검사
-- [중복]
SELECT `last_update`, `unit_time`
FROM `user`
WHERE `user_id` = {user_id};

-- [자원 생성]
-- 현재 생성 가능한 자원 조회
-- [중복]
SELECT `building_pk_id`, `resource_id`, `manpower`
FROM `building`
WHERE `user_id` = {user_id}
  AND `building_id` IN ({PLAN_MINE_ID}, {PLAN_FARM_ID}) -- PLAN_MINE_ID : 광산, PLAN_FARM_ID : 농장
  AND `deploy_finish_time` <= NOW();
-- 기획 데이터 PPU 기반으로 단위 시간당 자원 생산량 계산

-- [인구 생성]
-- 현재 생성 모드인지 확인
SELECT `auto_generate_manpower`
FROM `user`
WHERE `user_id` = {user_id};

-- 자동 생성 모드 일 경우 증가되는 인구 수 계산
-- 성 레벨에 맞는 PPU당 인구 생성
-- [중복]
SELECT `castle_level`
FROM `user`
WHERE `user_id` = {user_id};

-- [인구당 식량 자원 소모]
-- [중복]
SELECT `manpower_amount`
FROM `user`
WHERE `user_id` = {user_id};
-- 인구 별 식량자원 소모 량 계산

-- 현재 충성도 계산 (박물관, 사치자원 버프)
-- 다음 단위 시간 계산을 위함
-- [중복]
SELECT `building_pk_id`
FROM `building`
WHERE `user_id` = {user_id}
  AND `building_id` = {PLAN_MUSEUM_ID}
  AND `deploy_finish_time` <= NOW();

-- [중복]
SELECT `buf_pk_id`
FROM `buf`
WHERE `user_id` = {user_id}
  AND `finish_time` >= NOW();

-- [유저 데이터 갱신]
-- [중복]
UPDATE `user`
SET `manpower_amount` = {manpower_amount},
    `tactical_resource_amount` = {tactical_resource_amount},
    `food_resource_amount` = {food_resource_amount},
    `luxury_resource_amount` = {luxury_resource_amount},
    `unit_time` = {unit_time},
    `last_update` = NOW()
WHERE `user_id` = {user_id};

-- [다른 유저로부터의 선전 포고 노티]
SELECT `u`.`user_id`, `u`.`name`, `u`.`territory_id`
FROM `user` AS `u`, `war` AS `w`
WHERE `u`.`user_id` = `w`.`user_id`
  AND `w`.`territory_id` = {my_territory_id}
  AND `w`.`finish_time` >= NOW();

-- [다른 유저로부터의 동맹 요청 노티]
-- [중복]
SELECT
  `a`.`alliance_id`, `a`.`created_date`, `a`.`last_update`,
  `u`.`user_id`, `u`.`name`, `u`.`territory_id`
FROM `user` AS `u`, `alliance` AS `a`
WHERE `u`.`user_id` = `a`.`req_user_id`
  AND `a`.`res_user_id` = {user_id}
  AND `a`.`is_accepted` = FALSE;

-- 만료된 데이터 삭제
-- 버프
-- [중복]
DELETE FROM `buf`
WHERE `user_id` = {user_id}
  AND `finish_time` < SUBDATE(NOW(), INTERVAL {expire_date_buf} DAY);

-- 전쟁
-- [중복]
-- 특수 보스지역이 아닌 유저 영토로 출전했던 기록 중 이미 끝난 데이터
DELETE FROM `war`
WHERE `user_id` = {user_id}
  AND `territory_id` NOT IN ({PLAN_BOSS_TERRITORYs})
  AND `finish_time` < SUBDATE(NOW(), INTERVAL {expire_date_war} DAY);

-- 특수 보스지역으로 레이드 출전하여 점령기간까지 만료된 출전 데이터
DELETE FROM `war`
WHERE `raid_id` IN (
  SELECT DISTINCT `raid_id`
  FROM `occupation`
  WHERE `user_id` = {user_id}
    AND `finish_time` < NOW()
) AND `finish_time` < SUBDATE(NOW(), INTERVAL {expire_date_war} DAY);

-- 특수 보스 지역으로 출전하여 패배한 기록 중 만료된 데이터
DELETE FROM `war`
WHERE `user_id` = {user_id}
  AND `territory_id` IN ({PLAN_BOSS_TERRITORYs})
  AND `is_victory` = FALSE
  AND `finish_time` < SUBDATE(NOW(), INTERVAL {expire_date_war} DAY);

-- 점령
-- [중복]
DELETE FROM `occupation`
WHERE `user_id` = {user_id}
  AND `finish_time` < SUBDATE(NOW(), INTERVAL {expire_date_occupation} DAY);

-- 우편
-- [중복]
DELETE FROM `mail`
WHERE `to_user_id` = {user_id}
  AND `is_accepted` = TRUE
  AND `last_update` < SUBDATE(NOW(), INTERVAL {expire_date_mail} DAY);