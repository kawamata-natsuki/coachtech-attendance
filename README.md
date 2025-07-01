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
    docker-compose exec php bash
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
 
    メール認証は Mailtrap を使用します。  
    Mailtrap のアカウントを作成し、受信箱に記載される `MAIL_USERNAME` と `MAIL_PASSWORD` を `.env`設定してください：  
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



