#!/bin/bash
# Copyright 2015 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

VERSION="$1"

if [ -z "$VERSION" ]; then
  echo "Usage: $0 <version>"
  exit 1
fi

for i in * ; do
  if [ -d "$i" ]; then
  	echo "building $i"
    cd "$i"
    if [ -f "manifest.php" ]
    then
      zip -r --filesync ../$i.zip * -x "*.DS_Store" -x ".git*" -x "__MAC*"
   else
     if [ -f "pack.php" ]
     then
       php pack.php "$VERSION"
     fi
    fi
    cd ..
  fi
done
