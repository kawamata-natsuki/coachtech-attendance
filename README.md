# coachtech 勤怠管理アプリ

## 環境構築

1. リポジトリをクローン

```bash
git clone git@github.com:kawamata-natsuki/coachtech-attendance.git
``` 

2. クローン後、プロジェクトディレクトリに移動してVSCodeを起動
```
cd coachtech-attendance

code .
```

3. Dockerを起動する
Docker Desktopを起動してください

4. Docker用UID/GIDを設定する
プロジェクトルートに.env を作成する:  
```
touch .env
```
自分の環境に合わせてUID/GIDを設定
設定例: 
```
UID=1000
GID=1000
```
※UID/GIDは id -u / id -g コマンドで確認できます

5. `docker-compose.override.yml`の作成

`docker-compose.override.yml` は、開発環境ごとの個別調整（ポート番号の変更など）を行うための設定ファイルです。  
`docker-compose.yml` ではポートは設定されていないため、各自 `docker-compose.override.yml` を作成して、他のアプリケーションと競合しないポート番号を設定してください:     
```bash
touch docker-compose.override.yml
```
設定例
```yaml
services:
  nginx:
    ports:
    - "8090:80"        # 開発環境用のNginxポート
    
  php:
    build:
    args:
        USER_ID: 1000  # .envで指定したUIDを使用
        GROUP_ID: 1000 # .envで指定したGIDを使用
    ports:
    - "5173:5173"      # Viteのホットリロード用ポート

  phpmyadmin:
    ports:
    - 8091:80          # phpMyAdmin用ポート
```

4. プロジェクト直下で、以下のコマンドを実行、初期セットアップを行います：
```bash
cd ~/coachtech-attendance
make init
```
make init では以下が自動で実行されます：
- Dockerイメージのビルド
- コンテナ起動
- Laravel用 .env（.env.example → .env）配置
- Composer依存インストール
- APP_KEY生成
- DBマイグレーション・シーディング

## フロントエンドセットアップ（Vite）
本案件では勤怠登録画面の日時をViteを用いてリアルタイムで更新して、取得している。  

1. Node.js をインストール
Node.jsがインストールされていない場合は、公式サイトなどからインストールしてください。

2. 依存パッケージのインストール
プロジェクト直下で以下を実行します：
```bash
npm install
```

3. Vite の開発サーバー起動
以下のコマンドで勤怠登録画面の日時をリアルタイムで反映できます：  
```bash
npm run dev
```
    npm run devを起動していないと、JavaScriptによる時刻自動更新などのフロント機能が動作しません。

## 権限設定

本模擬案件では Docker 内で `appuser` を作成・使用しているため、基本的に `storage` や `bootstrap/cache` の権限変更は不要です。  
ただし、ファイル共有設定やOS環境によっては権限エラーになる場合があります。  
その場合は、以下のコマンドで権限を変更してください：
```bash
sudo chmod -R 775 storage
sudo chmod -R 775 bootstrap/cache
```
    
## メール設定
 
メール認証は Mailtrap（[https://mailtrap.io/](https://mailtrap.io/)）を使用します。  
Mailtrapのアカウントを作成し、Inbox（受信箱）に表示される `MAIL_USERNAME` と `MAIL_PASSWORD` を `.env`設定してください：  
```ini
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username_here
MAIL_PASSWORD=your_mailtrap_password_here
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

## 使用技術(実行環境)
- Laravel Framework 10.48.29
- PHP 8.2.28
- MYSQL 8.0.42
- Nginx 1.25.5
- phpMyAdmin 5.2.1

## ER図
![ER図](er.png)

## URL
- 開発環境 ：http://localhost/
  ※ポート番号は`docker-compose.override.yml`で各自調整してください。

## 動作確認
セットアップ完了後、以下のURLにブラウザでアクセスし、アプリケーションが正しく動作しているか確認してください：  
- 一般ユーザー画面: http://localhost:8090/login
- 管理者画面: http://localhost:8090/admin/login

## ログイン情報一覧
※ログイン確認用のテストアカウントです。  

| ユーザー種別    | メールアドレス             | パスワード  |
|----------------|----------------------------|------------|
| 一般ユーザー1   | reina.n@coachtech.com      | 12345678   |
| 一般ユーザー2   | taro.y@coachtech.com       | 12345678   |
| 一般ユーザー3   | issei.m@coachtech.com      | 12345678   |
| 一般ユーザー4   | keikichi.y@coachtech.com   | 12345678   |
| 一般ユーザー5   | tomomi.a@coachtech.com     | 12345678   |
| 一般ユーザー6   | norio.n@coachtech.com      | 12345678   |
| 管理者ユーザー  | admin@coachtech.com        | admin123   |


## テスト実行方法
テストケース ID4 「日時取得機能」は JavaScript を含むため、 Dusk による E2E テストは導入せず、 Feature テスト＋手動によるブラウザ確認で対応しています。
※このテスト実行方法についてはクライアント（コーチ）に事前相談し、了承を得ています。　

### テスト環境でのフロントビルドについて

テスト環境（.env.testing）ではViteのHMRを利用せず、ビルド済みのCSS/JSを読み込みます。
そのため、テストを正しく実行するには以下のコマンドでビルドを行い、`public/build` ディレクトリを生成してください：

```bash
npm install
npm run build
```

1. `.env.testing.example` をコピーして `.env.testing` を作成：

```bash
cp .env.testing.example .env.testing
```
※ `.env.testing.example` はテスト専用の設定テンプレートです。

2. テスト用データベースを作成：

```bash
docker compose exec mysql mysql -u root -proot -e "CREATE DATABASdemo_test;"
```

3. テスト用データベースにマイグレーションを実行：

```
php artisan migrate:fresh --env=testing
```

4. テスト用環境に切り替える前に .env をバックアップ
テスト環境に切り替える前に現在の開発用 .env を保存します：
```bash
cp .env .env.backup
```

5.  テスト用環境に切り替え
以下のコマンドで .env をテスト用環境に切り替えます：
```bash
make set-testing-env
```

6. テスト実行
```bash
php artisan test tests/Feature
```

7. テスト完了後、開発環境に戻す
テスト後は開発環境に戻して作業を続けてください：
```bash
make restore-env
```