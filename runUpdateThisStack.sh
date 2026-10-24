#!/bin/bash



# STEP 1: Create necessary files and directories
# ==============================================
# Data directories
mkdir -p ./_DATA/apache/public
mkdir -p ./_DATA/mysql

# Backup directories
mkdir -p ./_DATA/backuper/db-long-term
mkdir -p ./_DATA/backuper/db-short-term

# Logs directories
mkdir -p ./_LOGS/apache/logs
mkdir -p ./_LOGS/apache/laravel_logs




# STEP 2: Run the stack
# =====================
sudo docker network create --subnet=172.18.0.0/24 external
sudo docker-compose down
sudo docker-compose up -d --build
