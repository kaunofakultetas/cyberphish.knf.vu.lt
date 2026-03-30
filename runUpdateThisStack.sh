#!/bin/bash

# Data directories
mkdir -p _DATA/apache/public
mkdir -p _DATA/apache/upload

mkdir -p _DATA/backuper/db-long-term
mkdir -p _DATA/backuper/db-short-term

mkdir -p _DATA/mysql


# Logs directories
mkdir -p _LOGS/apache/logs
mkdir -p _LOGS/apache/laravel_logs



# Run the stack
sudo docker network create --subnet=172.18.0.0/24 external
sudo docker-compose down
sudo docker-compose up -d --build
