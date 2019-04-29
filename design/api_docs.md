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
| HiveID  | STRING | Y     | 하이브 ID  |
| HiveUID | INT    | Y     | 하이브 UID |

## 회원가입

- `POST /user/register`

| 요청변수     | 타입      | 필수 여부 | 설명      |
| :------- | :------ | :---- | :------ |
| HiveID   | STRING  | Y     | 하이브 ID  |
| HiveUID  | INT     | Y     | 하이브 UID |
| Country  | CHAR(2) | Y     | 국가      |
| Language | CHAR(2) | Y     | 언어      |

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

***

## 유저 정보

***

## 영토 맵 정보

- `GET /user/{userId}/territory`
- 자신이 보유한 `영토`를 기준으로 탐사된 `영내 타일`정보 반환
- 영내 탐사 시 탐사 가능, 불가능 타일을 알아보기 위함

## 영토(유저) 상태 정보

- `GET /user/{userId}/status`
- 자신이 보유한 각각의 자원, 인구, 공격력 정보 반환

## 유저 건물 정보

- `GET /user/{userId}/building`
- 유저가 현재 영토에 가지고 있는 모든 건물 정보
- 클라에서 해당 건물 정보들을 다 들고있으므로 하나의 건물에 대한 정보를 요청할 필요 X

***

## 유저 데이터 업데이트

***

## 건물 생성

- `POST /building`
- 새로운 건물 생성

TODO: 요청 변수 정리 (유저 id, 위치xy, 건물 종류 등)

## 건물 업그레이드 / 삭제

- `PUT /building/{buildingId}`

TODO: 요청 변수 정리
