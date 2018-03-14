<?php
// Copyright 2018 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

use Symfony\Component\Validator\Constraints as Assert;
use Sugarcrm\Sugarcrm\Security\Validator\Validator;
use Sugarcrm\Sugarcrm\Security\Validator\Constraints\Mvc;
use Sugarcrm\Sugarcrm\Security\Validator\Constraints\Delimited;
use Symfony\Component\Validator\Exception\InvalidOptionsException;

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
        $this->dateFormat = DateTime::ATOM; //'Y-m-d\TH:i:sP';
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
            'recentChangesPost' => array(
                'reqType' => array('POST'),
                'path' => array('Users', 'recentChanges'),
                'method' => 'getRecentChanges',
                'shortHelp' => 'Identify Users who have had recent changes to their assigned records.',
                'longHelp' => 'custom/modules/Users/clients/base/api/help/CustomRecentChangesApiPost.html',
            ),
            'recentChangesGet' => array(
                'reqType' => array('GET'),
                'path' => array('Users', 'recentChanges'),
                'method' => 'getRecentChanges',
                'shortHelp' => 'Identify Users who have had recent changes to their assigned records.',
                'longHelp' => 'custom/modules/Users/clients/base/api/help/CustomRecentChangesApiGet.html',
            ),

        );
    }

    /**
     * This is the function that is used for /Users/recentChanges endpoint
     * @param ServiceBase $api
     * @param array $args The arguments passed to the request. All params are required:
     *      since: the datetime from which you want to get changes
     *      modules: a comma separated list of modules that will be checked
     *      include_deleted: Whether or not deleted records should be included in results.
     * @return array JSON array containing the server's current time and a list of users with changes
     * @throws SugarApiExceptionNotAuthorized if the user is not an admin
     */
    public function getRecentChanges(ServiceBase $api, array $args)
    {
        $startTimeMicros = microtime(true);
        $this->writeLog('START');

        // Ensure the current user is an admin
        global $current_user;
        if (!$current_user->is_admin) {
            $this->writeLog('Not an admin, returning 403 code');
            throw new SugarApiExceptionNotAuthorized('You do not have admin permissions');
        }

        // Validate the params
        $sinceParam = $this->validateSinceParam($args['since']);
        $includeDeletedParam = $this->validateIncludeDeletedParam($args['include_deleted']);
        $modulesParam = $this->validateModulesParam($args['modules']);

        // Get the server's current time
        global $timedate;
        $currentTime = $timedate->getNow();

        // Convert $sinceParam into a SugarDateTime object
        $sinceParam = $timedate::fromTimestamp(DateTime::createFromFormat($this->dateFormat,$sinceParam)->getTimestamp());

        // Query for updated records
        $arrayOfQueryResults = $this->queryForRecentChanges($sinceParam->asDb(), $currentTime->asDb(), $modulesParam, (boolean)$includeDeletedParam);

        // Return the formatted results
        $results = $this->convertToResponseArray($currentTime->format($this->dateFormat), $arrayOfQueryResults);
        $runTime = microtime(true) - $startTimeMicros;
        $this->writeLog("FINISHED in $runTime seconds");
        return $results;
    }

    /**
     * Query for the list of user_ids and associated user_names for users who have records updated between $dateTimeOne
     * and $dateTimeTwo
     * @param $dateTimeOne string Search for records updated after this dateTime
     * @param $dateTimeTwo string Search for records updated up to and including this dateTime
     * @param $modules string A comma separated list of modules to query for updates
     * @param $includeDeleted boolean Whether or not deleted records should be included in results
     * @return array Array of query results. Each module in $modules will have its own set of results in the array.
     */
    protected function queryForRecentChanges($dateTimeOne, $dateTimeTwo, $modules, $includeDeleted)
    {
        $arrayOfQueryResults = array();
        $modules = explode(',', $modules);
        foreach ($modules as $module) {
            $startTime = microtime(true);
            $this->writeLog("Querying for changes in $module...");
            $query = new SugarQuery();
            $moduleBean = BeanFactory::newBean($module);
            $query->from($moduleBean, array('add_deleted' => !$includeDeleted));
            $moduleTableName = $moduleBean->table_name;
            $query->joinTable('users')->on()
                ->equalsField("$moduleTableName.assigned_user_id", 'users.id');
            $query->select(array('users.id', 'users.user_name'));
            $query->where()->gt('date_modified', $dateTimeOne);
            $query->where()->lte('date_modified', $dateTimeTwo);
            $result = $query->execute();
            $arrayOfQueryResults[$module] = $result;
            $this->writeLog("Finished querying for changes in $module in " . (microtime(true) - $startTime) . " seconds");
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
        $this->writeLog("Validating $paramName...");
        if (empty($paramValue) && $paramValue != '0') {
            throw new SugarApiExceptionMissingParameter("Missing required parameter: $paramName");
        }

        $errors = $this->validator->validate($paramValue, $constraint);

        if (count($errors) > 0 ) {
            throw new SugarApiExceptionInvalidParameter((string)$errors);
        }
        $this->writeLog("Finished validating $paramName");
        return $paramValue;
    }

    /**
     * Ensures the since param has been passed and that it uses the correct date format
     * @param $sinceParam string The param passed in as an argument
     * @return mixed The validated param
     */
    protected function validateSinceParam($sinceParam){
        try{
            # The DateTime constraint with a format option is available beginning with Symfony 3.1
            # If possible, we want to use the DateTime constraint
            $dateConstraint = new Assert\DateTime(array(
                'format' => $this->dateFormat,
                'message' => "Param 'since' must be of the format '$this->dateFormat'"));
            return $this->validateParam("since", $sinceParam, $dateConstraint);
        }
            # If the DateTime constraint with a format option is not available, we will validate in a different way
        catch (InvalidOptionsException $e){

            # Validate the param is a string
            $stringConstraint = new Assert\Type('String');
            $this->validateParam("since", $sinceParam, $stringConstraint);

            # Ensure we can create a DateTime from the $sinceParam
            if (DateTime::createFromFormat($this->dateFormat, $sinceParam) === false){
                throw new SugarApiExceptionInvalidParameter("Unable to generate date in the format of $this->dateFormat from $sinceParam");
            }
            return $sinceParam;
        }
    }

    /**
     * Ensures the include_deleted param has been passed and that it is a boolean
     * @param $includeDeletedParam string The param passed in as an argument
     * @return mixed The validated param
     */
    protected function validateIncludeDeletedParam($includeDeletedParam){
        $booleanConstraint = new Assert\Regex(array(
            'pattern' => '/^[01]$/',
            'message' => "Param 'include_deleted' must be 0 or 1"));
        return $this->validateParam("include_deleted", $includeDeletedParam, $booleanConstraint);
    }

    /**
     * Ensure the modules param has been passed and that it is a comma separated list of valid modules
     * @param $modulesParam string A comma separated list of modules
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
     *      "currentTime": "Y-m-d\TH:i:sP",
     *      "records": [
     *          {
     *              "id": "sample user id",
     *              "user_name": "the user_name associated with the above user id",
     *              "_recentlyChanged": [
     *                  {
     *                      "module": "module where the user has changes",
     *                      "count": the number of records assigned to this user that have changes in this module
     *                  }
     *              ]
     *          }
     *      ]
     * }
     *
     * @param $currentTime string The current datetime that was used as part of the query
     * @param $dataArray array Array of data results returned from queries
     * @return array The query response data formatted to be serialized as JSON
     */
    protected function convertToResponseArray($currentTime, $dataArray)
    {
        $this->writeLog("Formatting results...");
        // Loop through each record in the $dataArray, creating a record in $recordResults for each user
        $recordResults = array();
        foreach($dataArray as $module => $users){
            foreach($users as $user){
                // If a record for the user already exists in $recordResults for this module, increase the module count
                if(isset($recordResults[$user['id']]) && $recordResults[$user['id']]['_recentlyChanged'][$module]) {
                    $recordResults[$user['id']]['_recentlyChanged'][$module]['count'] =
                        $recordResults[$user['id']]['_recentlyChanged'][$module]['count'] + 1;
                } // Else if a record for the user already exists (meaning this is a new module for this user),
                // add the module to the user's results
                else if (isset($recordResults[$user['id']])) {
                    $recordResults[$user['id']]['_recentlyChanged'][$module] = array('module' => $module, 'count' => 1);
                } // Else this is a new user so create a new record in $recordResults
                else {
                    $recordResults[$user['id']] = array(
                        'id' => $user['id'],
                        'user_name' => $user['user_name'],
                    );
                    $recordResults[$user['id']]['_recentlyChanged'][$module] = array('module' => $module, 'count' => 1);
                }
            }
        }

        // $recordResults uses the user's id as the key for each record in the array and $recentlyChanged uses the module
        // name as the key for each record in the array.  Create a new $prettyRecordResult array that does not have keys
        // for items in the array
        $prettyRecordResults = array();
        forEach($recordResults as $record){
            $recentlyChanged = $record['_recentlyChanged'];
            $prettyRecentlyChanged = array();
            forEach($recentlyChanged as $module){
                $prettyRecentlyChanged[] = $module;
                $this->writeLog($record["user_name"] . " has " . $module["count"] . " change(s) in module " . $module["module"]);
            }
            $record['_recentlyChanged'] = $prettyRecentlyChanged;

            $prettyRecordResults[] = $record;
        }

        $this->writeLog("Finished formatting results");

        // Return the current time and the array of records
        return array(
            'currentTime' => $currentTime,
            'records' => $prettyRecordResults
        );
    }

    /**
     * Utility for logging messages to sugarcrm log file associated with this custom REST API.
     * Ensures that messages are created consistently (and can be easily found).
     * @param $message string Message to write to sugarcrm log.
     */
    protected function writeLog($message){
        $GLOBALS['log']->info(basename(__FILE__) . ' ' . $message);
    }
}
