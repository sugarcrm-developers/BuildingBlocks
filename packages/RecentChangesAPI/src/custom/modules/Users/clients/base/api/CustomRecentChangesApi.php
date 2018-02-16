<?php
// Copyright 2018 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

/**
 * Class CustomRecentChangesApi
 */
class CustomRecentChangesApi extends SugarApi
{
    public function registerApiRest()
    {
        return array(
            'recentChanges' => array(
                'reqType' => 'GET',
                'path' => array('Users', 'recentChanges'),
                'method' => 'getRecentChanges',
                'shortHelp' => 'Identify Users who have had recent changes to their assigned records.',
                'longHelp' => 'custom/modules/Users/clients/base/api/help/CustomRecentChangesApi.html',
            ),
        );
    }

    /**
     * @param ServiceBase $api
     * @param array $args
     * @return array List of User ID strings
     */
    public function getRecentChanges(ServiceBase $api, array $args)
    {
        // allow only admins
        // all fields required
        // use input validation framework
        $query = new SugarQuery();
        $query->distinct(true);
        $query->select('assigned_user_id');
        $query->from(BeanFactory::newBean('Meetings'), array('add_deleted'=>true));
        $query->where()->gt('date_modified','2018-02-14T12:00:00Z');
        $result = $query->execute();
        return $this->convertToResponseArray($result);
    }

    /**
     * Pulls out the assigned user ID in each row of the response and appends it to a simple string array.
     * This will simplify the JSON response that is sent back.
     * @param $data
     * @return array
     */
    protected function convertToResponseArray($data)
    {
        $result = array();
        foreach($data as $obj){
            $result[] = $obj['assigned_user_id'];
        }
        return $result;
    }
}
