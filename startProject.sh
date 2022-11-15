#!/bin/bash

# check if run as root
if [ "$EUID" -ne 0 ]
  then echo "Please run as root"
  exit
fi


# stop php webserver
pkill -e -f 'sudo php -S localhost:80 ./public/index.php'

# stop db
sudo -u $SUDO_USER docker-compose down

# run docker for db
sudo -u $SUDO_USER docker-compose up -d --force-recreate

# fix db ip in .env
sudo -u $SUDO_USER php genEnv.php

# run composer install
sudo -u $SUDO_USER composer install
sudo -u $SUDO_USER composer dump-autoload

# execute migrations
sudo -u $SUDO_USER php bin/console doc:mig:mig --no-interaction

# clear cache
sudo -u $SUDO_USER php bin/console cache:clear
sudo -u $SUDO_USER php bin/console cache:warmup

# run php build in webserver as demo webserver
php -S localhost:8080 ./public/index.php