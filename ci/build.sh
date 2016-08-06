#!/bin/bash

set -e

VERSION=`cat version/number`

echo "####### ${ARTIFACT_ID}  #########"
echo "####### ${VERSION}  #########"

pushd project
composer install
#SLEEP=60
popd

# give services time to spin up
#echo "waiting for composer to initialize ($SLEEP seconds)"
#sleep $SLEEP

echo "before tar"
tar -czvf $ARTIFACT_ID-$VERSION.tar.gz ./project 
echo "after tar"
mv $ARTIFACT_ID-$VERSION.tar.gz ./build-output
echo "done"
