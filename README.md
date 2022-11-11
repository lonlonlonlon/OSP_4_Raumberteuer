# OSP_4_Raumberteuer

## Requirements:
#### - php-8.1 (Iconv, mbstring, PCRE, Ctype, Session, SimpleXML, Tokenizer)
#### - composer
#### - docker, docker-compose
(configured to run as non root, see (https://docs.docker.com/engine/install/linux-postinstall/)
#### - pkill

## Start Project:
### - (sudo chmod 755 ./startProject.sh)
### - sudo ./startProject.sh

## Solving known issues:
### - Backend unable to connect to Database (connection refused):
####     remove the # at the beginning of line 32 in envtemplate, and put a # at the beginning of line 33 in envtemplate
### - ./startProject.sh fails to execute with sudo unknown user error
#### make sure the project folder sits inside the home directory of a non-root user