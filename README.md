# myspir mini

Spir / Calendly 風の、自分専用ミニ日程調整アプリです。  
管理者が空き枠を登録し、クライアントが予約すると、Google Calendar API 経由で管理者カレンダーに予定を作成し、Google Meet リンクを自動発行します。

## 主な機能

- 管理者ログイン
- 空き枠の登録 / 公開・非公開切り替え
- 予約一覧 / 予約詳細
- Google OAuth 2.0 連携状態の確認
- クライアント向け予約ページ
- 予約時の Google カレンダー予定作成
- `conferenceData.createRequest` と `conferenceDataVersion=1` による Google Meet 自動発行

## 技術構成

- Docker
- PHP 8.3
- MySQL 8.4
- Tailwind CSS (CDN)
- Google Calendar API
- OAuth 2.0

## セットアップ

1. 環境変数ファイルを作成

```bash
cp .env.example .env
```

2. `.env` を編集

- `ADMIN_EMAIL`
- `ADMIN_PASSWORD`
- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI`

`GOOGLE_REDIRECT_URI` はデフォルトで `http://localhost:8080/admin/google/callback` です。  
Google Cloud Console 側の OAuth クライアントにも同じ URI を登録してください。

3. Docker 起動

```bash
docker compose up --build
```

4. アクセス

- クライアント予約ページ: [http://localhost:8080/book](http://localhost:8080/book)
- 管理画面ログイン: [http://localhost:8080/admin/login](http://localhost:8080/admin/login)

起動時に `scripts/setup.php` が自動実行され、以下を行います。

- テーブル作成
- 管理者ユーザーの自動作成 / 更新

## Google 連携手順

1. Google Cloud Console で Calendar API を有効化
2. OAuth 同意画面を設定
3. Web アプリの OAuth クライアントを作成
4. `.env` に `GOOGLE_CLIENT_ID` と `GOOGLE_CLIENT_SECRET` を設定
5. 管理画面の `Google連携設定` から接続

## デフォルト DB 接続

- Host: `db`
- Port: `3306`
- Database: `.env` の `DB_DATABASE`
- User: `.env` の `DB_USERNAME`
- Password: `.env` の `DB_PASSWORD`

ホストマシンから見る場合の MySQL ポートは `33060` です。

## 補足

- Google API エラー時は予約を確定せず、エラー表示します。
- Meet リンクが即時返らない場合は数回ポーリングして取得します。
- Google Meet の参加権限設定変更は実装対象外です。
