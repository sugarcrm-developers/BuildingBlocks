<?php
// Copyright 2018 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

use Symfony\Component\Validator\Constraints as Assert;
use Sugarcrm\Sugarcrm\Security\Validator\Validator;
use Sugarcrm\Sugarcrm\Security\Validator\Constraints\Mvc;

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
        $AddDeletedParam = $this->validateAddDeletedParam($args['add_deleted']);
        $modulesParam = $this->validateModulesParam($args['modules']);

        // Get the server's current time
        // TODO:  Is this the right way to get the current server timestamp?
        global $timedate;
        $currentTime = $timedate->getNow()->format($this->dateFormat);

        // Query for updated records
        $arrayOfQueryResults = $this->queryForRecentChanges($sinceParam, $currentTime, $modulesParam, $AddDeletedParam);

        // Return the formatted results
        return $this->convertToResponseArray($currentTime, $arrayOfQueryResults);
    }

    /**
     * Query for the list of user_ids and associated user_names for users who have records updated between $dateTimeOne
     * and $dateTimeTwo
     * @param $dateTimeOne Search for records updated after this dateTime
     * @param $dateTimeTwo Search for records updated up to and including this dateTime
     * @param $modules List of modules to query for updates
     * @param $addDeleted Whether or not 'deleted' = 0 should be added to Where clause of generated SQL query
     * @return array Array of query results. Each module in $modules will have its own set of results in the array.
     */
    protected function queryForRecentChanges($dateTimeOne, $dateTimeTwo, $modules, $addDeleted)
    {
        $arrayOfQueryResults = array();

        foreach ($modules as $module) {
            $query = new SugarQuery();
            $query->distinct(true);
            $query->from(BeanFactory::newBean($module), array('team_security' => true, 'add_deleted' => $addDeleted));
            //TODO:  I was getting invalid link errors for modules like Accounts and Opportunities when I tried to do a
            // join instead of a joinTable.  Is there a better way to get the table name than lower casing the module name?
            $tableName = strtolower($module);
            $query->joinTable('users')->on()
                ->equalsField("$tableName.assigned_user_id", 'users.id');
            $query->select(array('users.id', 'users.user_name'));
            $query->where()->gt('date_modified', $dateTimeOne);
            $query->where()->lte('date_modified', $dateTimeTwo);
            $result = $query->execute();
            $arrayOfQueryResults[] = $result;
        }
        return $arrayOfQueryResults;
    }

    /**
     * Ensures the since param has been passed and that it uses the correct date format
     * @param $sinceParam The param passed in as an argument
     * @return mixed The validated param
     * @throws SugarApiExceptionInvalidParameter if the since param is not in the correct date format
     * @throws SugarApiExceptionMissingParameter if the since param is not passed
     */
    protected function validateSinceParam($sinceParam){
        if (empty($sinceParam)) {
            throw new SugarApiExceptionMissingParameter('Missing required parameter: since');
        }

        $errors = $this->validator->validate($sinceParam, new Assert\DateTime(array('format' => $this->dateFormat)));
        if (count($errors) > 0) {
            throw new SugarApiExceptionInvalidParameter((string)$errors . '    since param should be of the format '
                . $this->dateFormat . '.');
        }
        return $sinceParam;
    }

    /**
     * Ensures the add_deleted param has been passed and that it is a boolean
     * @param $AddDeletedParam The param passed in as an argument
     * @return mixed The validated param
     * @throws SugarApiExceptionInvalidParameter if the param is not a boolean
     * @throws SugarApiExceptionMissingParameter if the param is not passed
     */
    protected function validateAddDeletedParam($AddDeletedParam){
        if (empty($AddDeletedParam) && $AddDeletedParam != '0') {
            throw new SugarApiExceptionMissingParameter('Missing required parameter: add_deleted');
        }

        $isTrueErrors = $this->validator->validate($AddDeletedParam, new Assert\IsTrue());
        $isFalseErrors = $this->validator->validate($AddDeletedParam, new Assert\IsFalse());
        if (count($isTrueErrors) > 0 && count($isFalseErrors) > 0) {
            throw new SugarApiExceptionInvalidParameter('The value of add_deleted should be set to 0 or 1');
        }
        return $AddDeletedParam;
    }

    /**
     * Ensure the modules param has been passed and that it is a comma separated list of valid modules
     * @param $modulesParam A comma separated list of modules
     * @return array A list of modules
     * @throws SugarApiExceptionInvalidParameter if any of the modules in the list are not valid
     * @throws SugarApiExceptionMissingParameter if the param is not passed
     */
    protected function validateModulesParam($modulesParam){
        if (empty($modulesParam)) {
            throw new SugarApiExceptionMissingParameter('Missing required parameter: modules');
        }

        $modules = explode(',', $modulesParam);
        foreach ($modules as $module) {
            $errors = $this->validator->validate($module, new Mvc\ModuleName());
            if (count($errors) > 0) {
                throw new SugarApiExceptionInvalidParameter('Error validating module: ' . (string)$errors);
            }
        }
        return $modules;
    }

    /**
     * Converts the query response data to a JSON array
     *
     * The response will be in the following format:
     * {
     *      "currentTime": "Y-m-d G:i:s T",
     *      "usersWithRecentChanges": [
     *          {
     *              "assigned_user_id": "sample user id",
     *              "user_name": "the user_name assocated with the above user id"
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
        $users = array();

        //TODO:  Do you want to know which module the update is in as well?
        foreach($dataArray as $data){
            foreach($data as $obj){
                $users[] = array(
                    'assigned_user_id' => $obj['id'],
                    'user_name' => $obj['user_name']
                );
            }
        }

        $result = array(
            'currentTime' => $currentTime,
            'usersWithRecentChanges' => $users
        );

        return $result;
    }
}
