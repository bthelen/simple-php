#!/bin/bash

set -e

VERSION=`cat version/number`

pushd project
  compose install
popd

tar -czvf $ARTIFACT_ID-$VERSION.tar.gz ./project
cp project/$ARTIFACT_ID-$VERSION.jar build-output/.
