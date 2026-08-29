# 勤怠管理システム

## 概要

Laravelを使用して開発した勤怠管理システムです。

一般ユーザーは、出勤・休憩・退勤の打刻、勤怠情報の確認、勤怠修正申請を行うことができます。

管理者は、ユーザーの勤怠情報の確認、スタッフ管理、勤怠修正申請の確認・承認を行うことができます。

また、勤怠情報をAPIから取得・登録・更新・削除できる機能を実装しています。

---

## 使用技術

| 技術 | バージョン・内容 |
|---|---|
| PHP | 8.2 |
| Laravel | Laravel 10系 |
| MySQL | 8.4 |
| Docker | Docker Desktop |
| Laravel Sail | 使用 |
| Laravel Fortify | 認証機能 |
| Laravel Sanctum | API認証 |
| Blade | View |
| Tailwind CSS | CSSフレームワーク |
| Vite | フロントエンドビルド |
| PHPUnit | テスト |

---

## 環境構築

### 1. リポジトリをクローン

```bash
git clone <リポジトリURL>
cd <プロジェクトディレクトリ>

## ER図

```mermaid
erDiagram

    USERS ||--o{ ATTENDANCES : "1対多"
    USERS ||--o{ ATTENDANCE_CORRECTIONS : "1対多"
    ATTENDANCES ||--o{ ATTENDANCE_CORRECTIONS : "1対多"
    USERS ||--o{ ATTENDANCE_CORRECTIONS : "承認者"

    USERS {
        bigint id PK
        string name
        string email UK
        string password
        tinyint role
        timestamp created_at
        timestamp updated_at
    }

    ATTENDANCES {
        bigint id PK
        bigint user_id FK
        date work_date
        time start_time
        time end_time
        int break_time
        tinyint attendance_status
        string remark
        timestamp created_at
        timestamp updated_at
    }

    ATTENDANCE_CORRECTIONS {
        bigint id PK
        bigint attendance_id FK
        bigint user_id FK
        tinyint correction_type
        date correction_date
        time start_time
        time end_time
        text reason
        tinyint status
        bigint admin_id FK
        text comment
        timestamp requested_at
        timestamp approved_at
        timestamp created_at
        timestamp updated_at
    }


    
