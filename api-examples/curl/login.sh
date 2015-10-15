#!/bin/bash
# Copyright 2015 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

. ./environment_check.sh

#Call OAuth Token endpoint to retrieve access token
curl -X POST -H Content-Type:application/json -d "{ \"grant_type\":\"password\", \"client_id\":\"sugar\", \"client_secret\":\"\", \"username\":\"$SUGAR_USER\", \"password\":\"$SUGAR_PASSWORD\", \"platform\":\"base\" }" $SUGAR_BASE_URL/rest/v10/oauth2/token

echo
