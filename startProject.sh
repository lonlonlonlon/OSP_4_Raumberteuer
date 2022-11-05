#!/bin/bash
# stop db
docker-compose down

# run composer install
composer install
composer dump-autoload

# execute migrations
php bin/console doc:mig:mig

# clear cache
php bin/console cache:clear
php bin/console cache:warmup

# run docker for db
docker-compose up -d

# run php build in webserver as demo webserver
sudo php -S localhost:80 ./public/index.php