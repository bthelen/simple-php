#!/bin/bash
#
# All CF_* variables are provided externally from this script

set -e

if [ "true" = "$CF_SKIP_SSL" ]; then
  cf api $CF_API_URL --skip-ssl-validation
else
  cf api $CF_API_URL
fi

# Login to CF

cf auth $CF_USERNAME $CF_PASSWORD

UUID=$(uuidgen)

# Use temp org if none specified
if [ -z "$CF_ORG" ]; then
  CF_ORG="simple-$UUID-Org"
fi

HAS_ORG=$(cf orgs | grep $CF_ORG || true)
if [ -z "$HAS_ORG" ]; then
  cf create-org $CF_ORG
fi

cf target -o $CF_ORG

# Use temp space if none specified
if [ -z "$CF_SPACE" ]; then
  CF_SPACE="simple-$UUID"
fi

HAS_SPACE=$(cf spaces | grep $CF_SPACE || true)
if [ -z "$HAS_SPACE" ]; then
  cf create-space $CF_SPACE
fi

cf target -s $CF_SPACE

SLEEP=0

HAS_SERVICE=$(cf services | grep oracle || true)
if [ -z "$HAS_SERVICE" ]; then
  cf cups oracle -p "{ \"url\": \"$CF_DB_SERVICE_URL\", \"username\": \"$CF_DB_SERVICE_USERNAME\", \"password\": \"$CF_DB_SERVICE_PASSWORD\" }"
fi

HAS_SERVICE=$(cf services | grep redis || true)
if [ -z "$HAS_SERVICE" ]; then
   cf create-service $CF_REDIS_SERVICE
  SLEEP=5
fi

HAS_SERVICE=$(cf services | grep rabbit || true)
if [ -z "$HAS_SERVICE" ]; then
   cf create-service $CF_RABBIT_SERVICE
  SLEEP=10
fi

# give services time to spin up
echo "waiting for services to initialize ($SLEEP seconds)"
sleep $SLEEP
