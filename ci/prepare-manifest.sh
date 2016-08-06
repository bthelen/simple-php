#!/bin/bash
#
# All CF_* variables are provided externally from this script

set -e

# copy the artifact to the task-output folder
cp release/$CF_ARTIFACT_ID-*.tar.gz prepare-manifest-output/.

pushd prepare-manifest-output

ARTIFACT_PATH=$(ls $CF_ARTIFACT_ID-*.tar.gz)

cat <<EOF >manifest.yml
---
applications:
- name: $CF_APP_NAME
  host: $CF_APP_HOST
  path: $ARTIFACT_PATH
  memory: 256M
  instances: 1
  timeout: 180
  services: [ $CF_APP_SERVICES ]
  env:
    BP_DEBUG: "True"
EOF
popd
