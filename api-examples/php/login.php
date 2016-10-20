<?php
// Copyright 2015 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

// specify the REST web service to interact with
$baseurl = 'http://<<BASE_URL>>/sugarcrm/rest/v10';
/**
  * Authenicate and get back token
  */
$curl = curl_init($baseurl . "/oauth2/token");
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_HEADER, false);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
// Set the POST arguments to pass to the Sugar server
$rawPOSTdata = array(
    "grant_type" => "password",
    "username" => "<<USERNAME>>",
    "password" => "<<PASSWORD>>",
    "client_id" => "sugar",
    "client_secret" => "",
    "platform" => "api",
);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($rawPOSTdata));
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
// Make the REST call, returning the result
$response = curl_exec($curl);
if (!$response) {
    die("Connection Failure.\n");
}
// Convert the result from JSON format to a PHP array
$result = json_decode($response);
curl_close($curl);
if ( isset($result->error) ) {
    die("Error: " . $result->error_message . "\n");
}
$token = $result->access_token;
echo "Success! OAuth token is $token\n";


/**
  * Subsequent call to get my user data
  */
// Open a curl session for making the call
$curl = curl_init($baseurl . "/me");
curl_setopt($curl, CURLOPT_POST, false);
curl_setopt($curl, CURLOPT_HEADER, false);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json',"OAuth-Token: $token")); 
// Make the REST call, returning the result
$response = curl_exec($curl);
if (!$response) {
    die("Connection Failure.\n");
}
// Convert the result from JSON format to a PHP array
$result = json_decode($response);
curl_close($curl);
if ( isset($result->error) ) {
    die("Error: " . $result->error_message . "\n");
}
var_dump($result);
