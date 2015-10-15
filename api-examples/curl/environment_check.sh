#!/bin/bash
# Copyright 2015 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

if [ -z "$SUGAR_BASE_URL" ]; then
	echo "SUGAR_BASE_URL not set!"
	exit
else
	echo "SUGAR_BASE_URL=$SUGAR_BASE_URL"
fi

if [ -z "$SUGAR_USER" ]; then
	echo "SUGAR_USER not set!"
	exit
else
	echo "SUGAR_USER=$SUGAR_USER"
fi

if [ -z "$SUGAR_PASSWORD" ]; then
	echo "SUGAR_PASSWORD not set!"
	exit
else
	echo "SUGAR_PASSWORD=$SUGAR_PASSWORD"
fi
