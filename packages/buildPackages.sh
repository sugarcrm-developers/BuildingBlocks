#!/bin/bash
# Copyright 2015 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

for i in * ; do
  if [ -d "$i" ]; then
    cd "$i"
    zip -r --filesync ../$i.zip * -x "*.DS_Store" -x ".git*" -x "__MAC*"
    cd ..
  fi
done
