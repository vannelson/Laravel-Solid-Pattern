# Laravel Setup Commands (Run one by one)

# 1. Install dependencies
composer install

# 2. Configure environment
cp .env.example .env
php artisan key:generate
# Remember to set your DB credentials in the .env file

# 3. Run migrations
php artisan migrate --path=database/migrations/2014_10_12_000000_create_users_table.php
php artisan migrate --path=database/migrations/2014_10_12_100000_create_password_reset_tokens_table.php
php artisan migrate --path=database/migrations/2019_08_19_000000_create_failed_jobs_table.php
php artisan migrate --path=database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php
php artisan migrate --path=database/migrations/2025_03_28_002525_add_role_and_type_to_users_table.php
php artisan migrate --path=database/migrations/2025_03_29_131410_create_albums_table.php
php artisan migrate --path=database/migrations/2025_03_29_131410_create_songs_table.php
php artisan migrate --path=database/migrations/2025_03_29_131410_create_reactions_table.php

# 4. Seed database
php artisan db:seed

# 5. Start server
php artisan serve

# Additional useful commands:
# php artisan cache:clear
# php artisan route:list