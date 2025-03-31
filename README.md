Laravel Setup Guide

Install Dependencies

composer install

Configure Environment

Copy .env.example to .env

Set database credentials in .env

Generate application key:

php artisan key:generate

Run Migrations One by One

php artisan migrate --path=database/migrations/2014_10_12_000000_create_users_table.php
php artisan migrate --path=database/migrations/2014_10_12_100000_create_password_reset_tokens_table.php
php artisan migrate --path=database/migrations/2019_08_19_000000_create_failed_jobs_table.php
php artisan migrate --path=database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php
php artisan migrate --path=database/migrations/2025_03_28_002525_add_role_and_type_to_users_table.php
php artisan migrate --path=database/migrations/2025_03_29_131410_create_albums_table.php
php artisan migrate --path=database/migrations/2025_03_29_131410_create_songs_table.php
php artisan migrate --path=database/migrations/2025_03_29_131410_create_reactions_table.php

Run Seeders

php artisan db:seed

Start Development Server

php artisan serve

Additional Commands

Clear cache: php artisan cache:clear

View routes: php artisan route:list

