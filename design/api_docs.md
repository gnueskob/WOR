# World Of Renaissance API DOCUMENT

## API 기본 정보

***

## API 상세 정보

- URL prefix : `http://localhost/wor`
- 로그인 이후에 요청들은 `x-access-token` 헤더를 통한 토큰 검증

***

## 로그인 및 회원 가입

***

## 로그인

- `PUT /user/login`

| 요청변수     | 타입     | 필수 여부 | 설명      |
| :------- | :----- | :---- | :------ |
| hive_id  | STRING | Y     | 하이브 ID  |
| hive_uid | INT    | Y     | 하이브 UID |

```json
// request
{
  "hive_id":"gnues",
  "hive_uid":100321
}
```

```json
// response
// x-access-token : M8lVQ4Zn424a1rCUMAQP8gm6B+9KSoj3G4tjhAzG0aMwm/GyY1VZwxlYN9B23p3sBubpK10Nh78yTiVR72srVYgywMBQMXFuZO3uJbawu/w=
{
    "success": true,
    "res": {
        "user": {
            "user_id": 1,
            "hive_id": "gnues",
            "hive_uid": 100321,
            "register_date": "2019-06-15 13:17:27",
            "country": "Asia/Seoul",
            "lang": "KR",
            "os_version": "Android 8.0",
            "device_name": "SM-G930S",
            "app_version": "1.3.23",
            "last_visit": "2019-06-23 21:11:14",
            "territory_id": 1,
            "name": "com2us_test",
            "castle_level": 1,
            "castle_to_level": 1,
            "upgrade_time": "2019-06-15 13:19:17",
            "penalty_finish_time": null,
            "auto_generate_manpower": 1,
            "manpower": 0,
            "appended_manpower": 0,
            "tactical_resource": 0,
            "food_resource": 0,
            "luxury_resource": 0,
            "friend_attack": 0,
            "war_request": 0,
            "war_victory": 0,
            "war_defeated": 0,
            "despoil_defense_success": 0,
            "despoil_defense_fail": 0,
            "boss1_kill_count": 0,
            "boss2_kill_count": 0,
            "boss3_kill_count": 0,
            "current_castle_level": 1,
            "available_manpower": 0,
            "used_manpower": 0
        }
    }
}
```

## 회원가입

- `POST /user/register`

| 요청변수        | 타입       | 필수 여부 | 설명      |
| :---------- | :------- | :---- | :------ |
| hive_id     | STRING   | Y     | 하이브 ID  |
| hive_uid    | INT      | Y     | 하이브 UID |
| country     | CHAR(30) | Y     | 국가      |
| lang        | CHAR(2)  | Y     | 언어      |
| os_version  | CHAR(20) | Y     | 운영체제    |
| device_name | CHAR(20) | Y     | 단말기 정보  |
| app_version | CHAR(10) | Y     | 앱 버전    |

```json
// request
{
  "hive_id":"gnues2",
  "hive_uid":100322,
  "country": "Asia/Seoul",
  "lang": "KR",
  "os_version": "Android 8.0",
  "device_name": "SM-G930S",
  "app_version": "1.3.23"
}
```

```json
// response
// x-access-token UJtUuovvlswCLqx75b9L6CZLU/0yNiagKObSZ6H08ADyr7l+TZnVrA/xytpgm9lpSYYPhFkwrEWCBl8eKEZtohPEW3VYDSaHPr+rLKwNE84=
{
    "success": true,
    "res": {
        "user": {
            "user_id": 3,
            "hive_id": "gnues2",
            "hive_uid": 100322,
            "register_date": "2019-06-23 21:31:01",
            "country": "Asia/Seoul",
            "lang": "KR",
            "os_version": "Android 8.0",
            "device_name": "SM-G930S",
            "app_version": "1.3.23",
            "last_visit": "2019-06-23 21:31:01",
            "territory_id": null,
            "name": null,
            "castle_level": 1,
            "castle_to_level": 1,
            "upgrade_time": "2019-06-23 21:31:01",
            "penalty_finish_time": null,
            "auto_generate_manpower": 1,
            "manpower": 10,
            "appended_manpower": 0,
            "tactical_resource": 0,
            "food_resource": 0,
            "luxury_resource": 0,
            "friend_attack": 0,
            "war_request": 0,
            "war_victory": 0,
            "war_defeated": 0,
            "despoil_defense_success": 0,
            "despoil_defense_fail": 0,
            "boss1_kill_count": 0,
            "boss2_kill_count": 0,
            "boss3_kill_count": 0,
            "current_castle_level": 1,
            "available_manpower": 10,
            "used_manpower": 0
        }
    }
}
```

***

## 기획 데이터 관련

***

## 기획데이터 업로드

- `POST /planData`

| 요청변수      | 타입       | 필수 여부 | 설명       |
| :-------- | :------- | :---- | :------- |
| territory | CSV File | N     | 영토 정보    |
| tiles     | CSV File | N     | 영내 정보    |
| resources | CSV File | N     | 자원 정보    |
| building  | CSV File | N     | 건물 정보    |
| castle    | CSV File | N     | 성 레벨별 정보 |
| weapon    | CSV File | N     | 무기 정보    |
| action    | CSV File | N     | 게임 기능 정보 |

- TODO: 반환 포맷 정리

***

## User

***

## 영내 맵 정보

- `GET /user/tile/{userId}`
- 자신이 보유한 `영토`를 기준으로 탐사된 `영내 타일`정보 반환
- 영내 탐사 시 탐사 가능, 불가능 타일을 알아보기 위함

| 요청변수   | 타입   | 필수 여부 | 설명    |
| :----- | :--- | :---- | :---- |
| userId | INT  | Y     | 유저 ID |

- TODO: 반환 포맷 정리

## 영토 맵 정보

- `GET /user/territory/{userId}`
- 탐사된 `영토`정보 반환

| 요청변수   | 타입   | 필수 여부 | 설명    |
| :----- | :--- | :---- | :---- |
| userId | INT  | Y     | 유저 ID |

- TODO: 반환 포맷 정리

## 유저 상태 정보

- `GET /user/info/{userId}`
- 자신이 보유한 각각의 자원, 인구 정보 반환

| 요청변수   | 타입   | 필수 여부 | 설명    |
| :----- | :--- | :---- | :---- |
| userId | INT  | Y     | 유저 ID |

- TODO: 반환 포맷 정리

## 유저 건물 정보

- `GET /user/building/{userId}`
- 유저가 현재 영토에 가지고 있는 모든 건물 정보
- 클라에서 해당 건물 정보들을 다 들고있으므로 하나의 건물에 대한 정보를 요청할 필요 X

| 요청변수   | 타입   | 필수 여부 | 설명    |
| :----- | :--- | :---- | :---- |
| userId | INT  | Y     | 유저 ID |

- TODO: 반환 포맷 정리

## 유저 무기 정보

- `GET /user/weapon/{userId}`
- 유저가 현재 영토에 가지고 있는 모든 무기 정보

| 요청변수   | 타입   | 필수 여부 | 설명    |
| :----- | :--- | :---- | :---- |
| userId | INT  | Y     | 유저 ID |

- TODO: 반환 포맷 정리

## 유저 전쟁 정보

- `GET /user/war/{userId}`
- 유저가 현재 영토에 가지고 있는 모든 출전 정보

| 요청변수   | 타입   | 필수 여부 | 설명    |
| :----- | :--- | :---- | :---- |
| userId | INT  | Y     | 유저 ID |

- TODO: 반환 포맷 정리

## 유저 동맹 정보

- `GET /user/alliance/{userId}/{action_type}`
- 유저와 동맹 중 / 요청 중인 유저 정보

| 요청변수        | 타입   | 필수 여부 | 설명                                         |
| :---------- | :--- | :---- | :----------------------------------------- |
| userId      | INT  | Y     | 유저 ID                                      |
| action_type | INT  | Y     | 0: 동맹 중인 영토, 1: 동맹을 요청한 영토, 2: 동맹을 요청받은 영토 |

- TODO: 반환 포맷 정리

## 유저 버프 정보

- `GET /buf/info/{userId}`
- 유저에게 현재 적용중인 버프 확인

| 요청변수   | 타입   | 필수 여부 | 설명    |
| :----- | :--- | :---- | :---- |
| userId | INT  | Y     | 유저 ID |

## 버프

- `POST /buf/:user_id`
- 사치자원을 이용한 버프 활성화

| 요청변수   | 타입   | 필수 여부 | 설명                           |
| :----- | :--- | :---- | :--------------------------- |
| userId | INT  | Y     | 유저 ID                        |
| buf_id | INT  | Y     | 버프 타입 (default = 1: 사치자원 버프) |

***

## 건물 정보

***

## 건물 생성

- `POST /building/new`
- 새로운 건물 생성

| 요청변수    | 타입      | 필수 여부 | 설명    |
| :------ | :------ | :---- | :---- |
| user_id | INT     | Y     | 유저 ID |
| tile_id | INT     | Y     | 타일 ID |
| type    | TINYINT | Y     | 건물 종류 |

- TODO: 반환 포맷 정리

## 건물 업그레이드 / 삭제(?)

- `PUT /building/upgrade`
- 해당 건물 업그레이드 or 삭제

| 요청변수           | 타입   | 필수 여부 | 설명              |
| :------------- | :--- | :---- | :-------------- |
| building_pk_id | INT  | Y     | 해당 건물 ID        |
| action_type    | INT  | Y     | 0: 업그레이드, 1: 삭제 |

- TODO: 반환 포맷 정리

## 건물 업그레이드 완료

- `PUT /building/upgrade_finish`
- 해당 건물 업그레이드 완료

| 요청변수           | 타입   | 필수 여부 | 설명       |
| :------------- | :--- | :---- | :------- |
| building_pk_id | INT  | Y     | 해당 건물 ID |

- TODO: 반환 포맷 정리

## 건물 인구 배치 / 취소

- `PUT /building/manpower`
- 해당 건물에 원하는 만큼의 인구 배치 / 취소

| 요청변수           | 타입   | 필수 여부 | 설명                 |
| :------------- | :--- | :---- | :----------------- |
| building_pk_id | INT  | Y     | 해당 건물 ID           |
| manpower       | INT  | Y     | 배치할 인구 수           |
| action_type    | INT  | Y     | 0: 인구 배치, 1: 배치 취소 |

- TODO: 반환 포맷 정리

***

## 무기 정보

***

## 무기 생산

- `POST /weapon/new`
- 새로운 무기 생산

| 요청변수           | 타입   | 필수 여부 | 설명    |
| :------------- | :--- | :---- | :---- |
| user_id        | INT  | Y     | 유저 ID |
| building_pk_id | INT  | Y     | 공방 ID |
| type           | INT  | Y     | 무기 종류 |

- TODO: 반환 포맷 정리

## 무기 업그레이드 / 삭제(?)

- `PUT /weapon/upgrade`
- 해당 무기 업그레이드 / 삭제

| 요청변수           | 타입   | 필수 여부 | 설명              |
| :------------- | :--- | :---- | :-------------- |
| weapon_pk_id   | INT  | Y     | 무기 ID           |
| building_pk_id | INT  | Y     | 공방 ID           |
| action_type    | INT  | Y     | 0: 업그레이드, 1: 삭제 |

- TODO: 반환 포맷 정리

## 무기 업그레이드 완료

- `PUT /weapon/upgrade_finish`
- 해당 무기 업그레이드 / 삭제

| 요청변수         | 타입   | 필수 여부 | 설명              |
| :----------- | :--- | :---- | :-------------- |
| weapon_pk_id | INT  | Y     | 무기 ID           |
| action_type  | INT  | Y     | 0: 업그레이드, 1: 삭제 |

- TODO: 반환 포맷 정리

***

## 탐사

***

## 영내 탐사

- `POST /exploration`
- 유저의 영토내에서 탐사되지 않은 영토 탐사

| 요청변수    | 타입   | 필수 여부 | 설명    |
| :------ | :--- | :---- | :---- |
| user_id | INT  | Y     | 유저 ID |
| tile_id | INT  | Y     | 타일 ID |

- TODO: 반환 포맷 정리

## 영토 탐사

- `POST /exploration/territory`
- 유저 영토 외의 다른 영토 탐사

| 요청변수         | 타입   | 필수 여부 | 설명    |
| :----------- | :--- | :---- | :---- |
| user_id      | INT  | Y     | 유저 ID |
| territory_id | INT  | Y     | 영토 ID |

- TODO: 반환 포맷 정리

## 탐사된 영토 정보

- `GET /exploration/territory/{territory_id}`
- 유저 영토 외의 다른 영토 탐사

- TODO: 반환 포맷 정리

***

## 전쟁

***

## 출전

- `POST /war`
- 다른 유저에게 선전포고(출전)

| 요청변수         | 타입   | 필수 여부 | 설명    |
| :----------- | :--- | :---- | :---- |
| user_id      | INT  | Y     | 유저 ID |
| territory_id | INT  | Y     | 영토 ID |
| manpower     | ARR  | Y     | 출전 병력 |
| recource     | INT  | Y     | 군량    |

```json
"manpower": [
  {"building_id": 1, "manpower": 231},
  {"building_id": 2, "manpower": 231},
  {"building_id": 3, "manpower": 231}
]
```

- TODO: 반환 포맷 정리

## 출전 완료 / 취소

- `PUT /war`
- 전쟁 준비 완료

| 요청변수         | 타입   | 필수 여부 | 설명                   |
| :----------- | :--- | :---- | :------------------- |
| user_id      | INT  | Y     | 유저 ID                |
| territory_id | INT  | Y     | 영토 ID                |
| war_id       | INT  | Y     | 출전 정보 ID             |
| action_type  | INT  | Y     | 0: 전쟁 준비완료, 1: 출전 취소 |


- TODO: 반환 포맷 정리

***

## 동맹

***

## 동맹 요청

- `POST /alliance`
- 다른 유저에게 동맹 요청

| 요청변수        | 타입   | 필수 여부 | 설명          |
| :---------- | :--- | :---- | :---------- |
| req_user_id | INT  | Y     | 동맹 요청 유저 ID |
| res_user_id | INT  | Y     | 동맹 응답 유저 ID |

- TODO: 반환 포맷 정리

## 동맹 수락 / 거절

- `PUT /alliance`
- 다른 유저로부터 받은 동맹 제안을 수락 / 거절

| 요청변수        | 타입   | 필수 여부 | 설명           |
| :---------- | :--- | :---- | :----------- |
| alliance_id | INT  | Y     | 동맹 ID        |
| action_type | INT  | Y     | 0: 수락, 1: 거절 |

- TODO: 반환 포맷 정리

## 동맹 자원 우편 확인

- `GET /mail/{user_id}`
- 다른 유저가 보낸 자원 현황 확인

- TODO: 반환 포맷 정리

## 동맹 유저 자원 보내기

- `POST /mail`
- 다른 유저에게 일정량 자원 보내기

| 요청변수              | 타입   | 필수 여부 | 설명          |
| :---------------- | :--- | :---- | :---------- |
| from_user_id      | INT  | Y     | 메일 발신 유저 ID |
| to_user_id        | INT  | Y     | 메일 수신 유저 ID |
| tactical_resource | INT  | Y     | 전략 자원 수량    |
| food_resource     | INT  | Y     | 식량 자원 수량    |
| luxury_resource   | INT  | Y     | 사치 자원 수량    |

- TODO: 반환 포맷 정리

## 보낸 자원 받기

- `PUT /mail`
- 다른 유저로 부터 받은 자원 수령하기

| 요청변수    | 타입   | 필수 여부 | 설명    |
| :------ | :--- | :---- | :---- |
| mail_id | INT  | Y     | 메일 ID |

- TODO: 반환 포맷 정리

***

## 레이드 신청

- `POST /raid`
- 특수 지역 보스 몬스터 레이드 신청

| 요청변수              | 타입   | 필수 여부 | 설명       |
| :---------------- | :--- | :---- | :------- |
| user_id           | INT  | Y     | 유저 ID    |
| territory_id      | INT  | Y     | 보스 영토 ID |
| alliance_user_ids | LIST | N     | 동맹 유저 ID |

- TODO: 반환 포맷 정리

## 레이드 완료

- `PUT /raid`
- 특수 지역 보스 몬스터 레이드 완료 요청

| 요청변수    | 타입   | 필수 여부 | 설명        |
| :------ | :--- | :---- | :-------- |
| raid_id | INT  | Y     | 레이드 출전 ID |

- TODO: 반환 포맷 정리

## 점령전 신청

- `POST /occupation`
- 점령지가 된 특수 지역으로 점령전 신청

| 요청변수    | 타입   | 필수 여부 | 설명    |
| :------ | :--- | :---- | :---- |
| user_id | INT  | Y     | 유저 ID |

- TODO: 반환 포맷 정리

## 점령전 완료

- `PUT /occupation`
- 점령지가 된 특수 지역으로 점령전 완료 요청

| 요청변수    | 타입   | 필수 여부 | 설명     |
| :------ | :--- | :---- | :----- |
| raid_id | INT  | Y     | 점령전 ID |

- TODO: 반환 포맷 정리

***

## 정산

***

## 폴링

- `PUT /calculation`
- 단위시간 별 정산

| 요청변수    | 타입   | 필수 여부 | 설명    |
| :------ | :--- | :---- | :---- |
| user_id | INT  | Y     | 유저 ID |
