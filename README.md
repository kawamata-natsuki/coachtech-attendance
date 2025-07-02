# coachtech-attendance

## 環境構築

1. リポジトリをクローン

    ```bash
    git clone git@github.com:kawamata-natsuki/coachtech-attendance.git
    ``` 
<br>  

2. `.env.docker` ファイルの準備（ Docker 用）

    ```bash
    cp .env.docker.example .env.docker
    ```
    ※この `.env.docker` は Docker ビルド用の設定ファイルです（ Laravelの `.env` とは別物です）。  
      以下のコマンドで自分の UID / GID を確認し、自分の環境に合わせて `.env.docker` の UID / GID を設定してください：
      ```bash
      id -u
      id -g
      ```
<br>

3. `docker-compose.override.yml`の作成

    `docker-compose.override.yml` は、開発環境ごとの個別調整（ポート番号の変更など）を行うための設定ファイルです。  
    `docker-compose.yml` ではポートは設定されていないため、各自 `docker-compose.override.yml` を作成してポートを設定してください。    ```bash
    touch docker-compose.override.yml
    ```
    ```yaml
    services:
     nginx:
      ports:
        - "8090:80"

    phpmyadmin:
      ports:
        - 8091:80
    ```
<br>

4. Docker イメージのビルドと起動

    以下のコマンドで Docker イメージをビルドし、コンテナを起動します：
    ```bash
    docker-compose up -d --build
    ```
    
    ※ Mac の M1・M2 チップの PC の場合、 `no matching manifest for linux/arm64/v8 in the manifest list entries` のメッセージが表示されビルドができないことがあります。  
    エラーが発生する場合は、 `docker-compose.yml` ファイルの `mysql` に以下のように追記してください：
    ```yaml
    mysql:
        platform: linux/x86_64  # この行を追加
        image: mysql:8.0.26
        environment:
    ```
<br>

5. Laravel のセットアップ

    PHP コンテナに入って、 Composer をインストールします：
    ```bash
    docker compose exec php bash
    composer install
    ```
<br>

6. `.env` ファイルの設定  

    ---

    `.env` ファイルの準備

    Laravel 用の環境設定ファイルを作成します：
    ```
    cp .env.example .env
    ```

    ---

    メール設定
 
    メール認証は Mailtrap（[https://mailtrap.io/](https://mailtrap.io/)）を使用します。  
    Mailtrapのアカウントを作成し、Inbox（受信箱）に表示される `MAIL_USERNAME` と `MAIL_PASSWORD` を `.env`設定してください：  
    ```ini
    MAIL_MAILER=smtp
    MAIL_HOST=sandbox.smtp.mailtrap.io
    MAIL_PORT=2525
    MAIL_USERNAME=your_mailtrap_username_here
    MAIL_PASSWORD=your_mailtrap_password_here
    MAIL_ENCRYPTION=null
    MAIL_FROM_ADDRESS=no-reply@example.com
    MAIL_FROM_NAME="${APP_NAME}"  
    ```
<br>

7.  権限設定

    本模擬案件では Docker 内で `appuser` を作成・使用しているため、基本的に `storage` や `bootstrap/cache` の権限変更は不要です。  
    ただし、ファイル共有設定やOS環境によっては権限エラーになる場合があります。  
    その場合は、以下のコマンドで権限を変更してください：
    ```bash
    sudo chmod -R 775 storage
    sudo chmod -R 775 bootstrap/cache
    ```
<br>

8.  アプリケーションキーの生成

    ```bash
    php artisan key:generate
    ```
<br>

9.  マイグレーションの実行 

    ```bash
    php artisan migrate
    ```
<br>

10. シーディングの実行

    ```bash
    php artisan db:seed
    ```
<br>

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

1. `.env.testing.example` をコピーして `.env.testing` を作成：

   ```bash
   cp .env.testing.example .env.testing
   ```

    ※ `.env.testing.example` はテスト専用の設定テンプレートです。

2. テスト用データベースを作成：

   ```bash
   docker compose exec mysql mysql -u root -proot -e "CREATE DATABASE demo_test;"
   ```

3. テスト用データベースにマイグレーションを実行：

    ```
    php artisan migrate:fresh --env=testing
    ```

4. テスト用環境に切り替える前に .env をバックアップ
テスト環境に切り替える前に現在の開発用 .env を保存します：

cp .env .env.backup

5.  テスト用環境に切り替え
Laravelの仕様上、php artisan test 実行時に APP_ENV=testing が自動で適用されないケースがあります。
そのため、以下のMakefileコマンドを利用して .env をテスト用に切り替えてからテストを実行します：


make set-testing-env

内容：
set-testing-env:
	cp .env.testing .env
	php artisan config:clear


5. テスト実行

php artisan test tests/Feature

6. テスト完了後、開発環境に戻す
テスト後は開発環境に戻して作業を続けてください：

make restore-env

内容：
restore-env:
	cp .env.backup .env
	php artisan config:clear