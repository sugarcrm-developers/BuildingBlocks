<?php
// Copyright 2018 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

use Symfony\Component\Validator\Constraints as Assert;
use Sugarcrm\Sugarcrm\Security\Validator\Validator;
use Sugarcrm\Sugarcrm\Security\Validator\Constraints\Mvc;
use Sugarcrm\Sugarcrm\Security\Validator\Constraints\Delimited;

/**
 * Class CustomRecentChangesApi
 */
class CustomRecentChangesApi extends SugarApi
{
    /**
     * The format for date strings
     */
    private $dateFormat;

    /**
     * The validator we will use to validate user input
     */
    private $validator;

    function __construct()
    {
        $this->dateFormat = 'Y-m-d G:i:s T';
        $this->validator = Validator::getService();
    }

    /**
     * Registers a custom API endpoint at /Users/recentChanges.
     * The endpoint identifies users who have had recent changes to their assigned records.
     * @return array The definition of the custom endpoint
     */
    public function registerApiRest()
    {
        return array(
            'recentChanges' => array(
                'reqType' => 'GET',
                'path' => array('Users', 'recentChanges'),
                'method' => 'getRecentChanges',
                'shortHelp' => 'Identify Users who have had recent changes to their assigned Meeting records.',
                'longHelp' => 'custom/modules/Users/clients/base/api/help/CustomRecentChangesApi.html',
            ),
        );
    }

    /**
     * This is the function that is used for /Users/recentChanges endpoint
     * @param ServiceBase $api
     * @param array $args The arguments passed to the GET request. All params are required:
     *      since: the datetime from which you want to get changes
     *      modules: a comma separated list of modules that will be checked
     *      add_deleted: Whether or not "'deleted' = 0" should be added to Where clause of generated SQL query. Value should be 0 or 1
     * @return array JSON array containing the server's current time and a list of users with changes
     * @throws SugarApiExceptionNotAuthorized if the user is not an admin
     */
    public function getRecentChanges(ServiceBase $api, array $args)
    {
        // Ensure the current user is an admin
        global $current_user;
        if (!$current_user->is_admin) {
            throw new SugarApiExceptionNotAuthorized('You do not have admin permissions');
        }

        // Validate the params
        $sinceParam = $this->validateSinceParam($args['since']);
        $addDeletedParam = $this->validateAddDeletedParam($args['add_deleted']);
        $modulesParam = $this->validateModulesParam($args['modules']);

        // Get the server's current time
        global $timedate;
        $currentTime = $timedate->getNow()->format($this->dateFormat);

        // Query for updated records
        $arrayOfQueryResults = $this->queryForRecentChanges($sinceParam, $currentTime, $modulesParam, $addDeletedParam);

        // Return the formatted results
        return $this->convertToResponseArray($currentTime, $arrayOfQueryResults);
    }

    /**
     * Query for the list of user_ids and associated user_names for users who have records updated between $dateTimeOne
     * and $dateTimeTwo
     * @param $dateTimeOne Search for records updated after this dateTime
     * @param $dateTimeTwo Search for records updated up to and including this dateTime
     * @param $modules A comma separated list of modules to query for updates
     * @param $addDeleted Whether or not 'deleted' = 0 should be added to Where clause of generated SQL query
     * @return array Array of query results. Each module in $modules will have its own set of results in the array.
     */
    protected function queryForRecentChanges($dateTimeOne, $dateTimeTwo, $modules, $addDeleted)
    {
        $arrayOfQueryResults = array();

        $modules = explode(',', $modules);
        foreach ($modules as $module) {
            $query = new SugarQuery();
            $query->distinct(true);
            $moduleBean = BeanFactory::newBean($module);
            $query->from($moduleBean, array('add_deleted' => $addDeleted));
            $moduleTableName = $moduleBean->table_name;
            $query->joinTable('users')->on()
                ->equalsField("$moduleTableName.assigned_user_id", 'users.id');
            $query->select(array('users.id', 'users.user_name'));
            $query->where()->gt('date_modified', $dateTimeOne);
            $query->where()->lte('date_modified', $dateTimeTwo);
            $result = $query->execute();
            $arrayOfQueryResults[$module] = $result;
        }
        return $arrayOfQueryResults;
    }

    /**
     * Validates a param meets a given constraint
     * @param $paramName The name of the param to validate
     * @param $paramValue The value of the param to validate
     * @param $constraint The constraint to use to validate the param value
     * @return mixed The validated param
     * @throws SugarApiExceptionInvalidParameter if the param does not meet the given constraint
     * @throws SugarApiExceptionMissingParameter if the param is missing
     */
    protected function validateParam($paramName, $paramValue, $constraint){
        if (empty($paramValue) && $paramValue != '0') {
            throw new SugarApiExceptionMissingParameter("Missing required parameter: $paramName");
        }

        $errors = $this->validator->validate($paramValue, $constraint);

        if (count($errors) > 0 ) {
            throw new SugarApiExceptionInvalidParameter((string)$errors);
        }
        return $paramValue;
    }

    /**
     * Ensures the since param has been passed and that it uses the correct date format
     * @param $sinceParam The param passed in as an argument
     * @return mixed The validated param
     */
    protected function validateSinceParam($sinceParam){
        $dateConstraint = new Assert\DateTime(array(
            'format' => $this->dateFormat,
            'message' => "Param 'since' must be of the format '$this->dateFormat'"));
        return $this->validateParam("since", $sinceParam, $dateConstraint);
    }

    /**
     * Ensures the add_deleted param has been passed and that it is a boolean
     * @param $addDeletedParam The param passed in as an argument
     * @return mixed The validated param
     */
    protected function validateAddDeletedParam($addDeletedParam){
        $booleanConstraint = new Assert\Regex(array(
            'pattern' => '/^[01]$/',
            'message' => "Param 'add_deleted' must be 0 or 1"));
        return $this->validateParam("add_deleted", $addDeletedParam, $booleanConstraint);
    }

    /**
     * Ensure the modules param has been passed and that it is a comma separated list of valid modules
     * @param $modulesParam A comma separated list of modules
     * @return array The validated param
     */
    protected function validateModulesParam($modulesParam){
        $moduleConstraint = new Delimited(array(
            'constraints' => new Mvc\ModuleName(),
        ));
        return $this->validateParam("modules", $modulesParam, $moduleConstraint);
    }

    /**
     * Converts the query response data to a JSON array
     *
     * The response will be in the following format:
     * {
     *      "currentTime": "Y-m-d G:i:s T",
     *      "records": [
     *          {
     *              "id": "sample user id",
     *              "name": "the user_name associated with the above user id",
     *              "_recentlyChanged": "array of modules where the user has changes"
     *          }
     *      ]
     * }
     *
     * @param $currentTime The current datetime that was used as part of the query
     * @param $data Array of data results returned from queries
     * @return array The query response data formatted as a JSON array
     */
    protected function convertToResponseArray($currentTime, $dataArray)
    {
        // Loop through each record in the $dataArray, creating a record in $recordResults for each user
        $recordResults = array();
        foreach($dataArray as $module => $users){
            foreach($users as $user){
                // If a record for the user already exists in $recordResults, simply add the module to the _recentlyChanged array
                if($recordResults[$user['id']]){
                    $recordResults[$user['id']]['_recentlyChanged'][] = $module;
                } // Else create a new record in $recordResults
                else {
                    $recordResults[$user['id']] = array(
                        'id' => $user['id'],
                        'name' => $user['user_name'],
                        '_recentlyChanged' => array($module)
                    );
                }
            }
        }

        // $recordResults uses the user's id as the key for each record in the array. Create a new $prettyRecordResults
        // array that does not have keys for each record in the array
        $prettyRecordResults = array();
        forEach($recordResults as $record){
            $prettyRecordResults[] = $record;
        }

        // Return the current time and the array of records
        return array(
            'currentTime' => $currentTime,
            'records' => $prettyRecordResults
        );
    }
}
