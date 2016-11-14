#!/bin/bash
# Copyright 2015 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

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
       php pack.php "1.0.0"
     fi
    fi
    cd ..
  fi
done
