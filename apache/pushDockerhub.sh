#!/bin/bash
# -----------------------------------------------------------
#  [*] Apache — Docker Hub release
#
#  Builds the production image and publishes it to Docker
#  Hub as vuknf/cyberphish-apache under two tags: latest,
#  the one docker-compose.yml runs, and the day's date as
#  eight digits, year first, so every release stays
#  reachable. It signs in as admin@knf.vu.lt first, and
#  docker asks for the password. The build context is the
#  current folder, so the script runs from this one. It
#  builds the working tree as it stands and runs no test —
#  runTests.sh does that, under a tag of its own.
# -----------------------------------------------------------
set -e

CONTAINER_NAME=cyberphish-apache

TODAY=$(date +%Y%m%d)
sudo docker login -u admin@knf.vu.lt
sudo docker build -t $CONTAINER_NAME .
sudo docker tag $CONTAINER_NAME vuknf/$CONTAINER_NAME:latest
sudo docker tag $CONTAINER_NAME vuknf/$CONTAINER_NAME:$TODAY
sudo docker push vuknf/$CONTAINER_NAME:latest
sudo docker push vuknf/$CONTAINER_NAME:$TODAY
