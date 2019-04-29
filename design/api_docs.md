# World Of Renaissance API DOCUMENT

## API 기본 정보

***

## API 상세 정보

- URL prefix : `http://localhost/wor`

***

## 로그인 및 회원 가입

***

## 로그인

- `PUT /user/login`

| 요청변수    | 타입     | 필수 여부 | 설명      |
| :------ | :----- | :---- | :------ |
| hive_id  | STRING | Y     | 하이브 ID  |
| hive_uid | INT    | Y     | 하이브 UID |

- TODO: 반환 포맷 정리

## 회원가입

- `POST /user/register`

| 요청변수     | 타입      | 필수 여부 | 설명      |
| :------- | :------ | :---- | :------ |
| hive_id   | STRING  | Y     | 하이브 ID  |
| hive_uid  | INT     | Y     | 하이브 UID |
| country  | CHAR(2) | Y     | 국가      |
| language | CHAR(2) | Y     | 언어      |

- TODO: 반환 포맷 정리

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

## 유저 정보

***

## 영토 맵 정보

- `GET /user/{userId}/territory`
- 자신이 보유한 `영토`를 기준으로 탐사된 `영내 타일`정보 반환
- 영내 탐사 시 탐사 가능, 불가능 타일을 알아보기 위함

- TODO: 반환 포맷 정리

## 영토(유저) 상태 정보

- `GET /user/{userId}/status`
- 자신이 보유한 각각의 자원, 인구, 공격력 정보 반환

- TODO: 반환 포맷 정리

## 유저 건물 정보

- `GET /user/{userId}/building`
- 유저가 현재 영토에 가지고 있는 모든 건물 정보
- 클라에서 해당 건물 정보들을 다 들고있으므로 하나의 건물에 대한 정보를 요청할 필요 X

- TODO: 반환 포맷 정리

***

## 유저 데이터 업데이트

***

## 건물 생성

- `POST /building`
- 새로운 건물 생성

| 요청변수      | 타입       | 필수 여부 | 설명       |
| :-------- | :------- | :---- | :------- |
| user_id | INT | Y | 유저 ID |
| location_x | INT | Y | x 위치 좌표 |
| location_y | INT | Y | y 위치 좌표 |
| type | TINYINT | Y | 건물 종류 |

- TODO: 반환 포맷 정리

## 건물 업그레이드 / 삭제

- `PUT /building/{buildingId}`
- 해당 건물 업그레이드 or 삭제

| 요청변수      | 타입       | 필수 여부 | 설명       |
| :-------- | :------- | :---- | :------- |
| type | INT | Y | 0: 업그레이드, 1: 삭제 |

- TODO: 반환 포맷 정리