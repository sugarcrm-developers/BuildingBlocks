<?php
/*
 * Your installation or use of this SugarCRM file is subject to the applicable
 * terms available at
 * http://support.sugarcrm.com/Resources/Master_Subscription_Agreements/.
 * If you do not agree to all of the applicable terms or do not have the
 * authority to bind the entity as an authorized representative, then do not
 * install or use this SugarCRM file.
 *
 * Copyright (C) SugarCRM Inc. All rights reserved.
 */

use Sugarcrm\Sugarcrm\ProcessManager;

class PMSECustomListApi extends PMSEEngineFilterApi
{
    /**
     *
     * @return type
     */
    public function registerApiRest() {
        return [
            'getModuleList' => [
                'reqType' => 'GET',
                'path' => ['pmse_Inbox', '?', '?'],
                'pathVars' => ['', 'module', 'id'],
                'method' => 'getModuleList',
                'acl' => 'adminOrDev',
                'shortHelp' => 'Returns the Current Processes for given module and id',
                'longHelp' =>  '',
            ],
        ];
    }

    public function getModuleList(ServiceBase $api, array $args)
    {

        // cas_id
        // pro_title
        // cas_title
        // cas_status
        // prj_run_order
        // cas_create_date
        // assigned_user_full_name
        // cas_user_id_full_name
        // prj_user_id_full_name

        // Verify access
        ProcessManager\AccessManager::getInstance()->verifyUserAccess($api, $args);

        // Set up the Sugar Query object
        $q = new SugarQuery();

        // And remove the order by stability since it was causing us problems
        $q->setOrderByStability(false);

        // This is our primary select table
        $inboxBean = BeanFactory::newBean('pmse_Inbox');

        // Set the order by properly if we are expected a due date order
        if ($args['order_by'] == 'cas_due_date:asc') {
            $args['order_by'] = 'cas_create_date:asc';
        }

        $module = $args['module'];
        $id = $args['id'];

        // Set up the necessary options for the query we will run
        $options = $this->parseArguments($api, $args, $inboxBean);

        $fieldsArg = explode(",", $args['fields']);


        // Replacement for using .* to get all columns
        // Fields from inbox that are needed
        // Removed the pro_title column because it contains old data and is never updated
        $inboxFields = [
            'id', 'name', 'date_entered', 'date_modified',
            'modified_user_id', 'deleted',
            'cas_id', 'cas_parent', 'cas_status', 'pro_id',
            'cas_title', 'cas_custom_status', 'cas_init_user', 'cas_create_date',
            'cas_update_date', 'cas_finish_date', 'cas_pin', 'cas_assigned_status',
            'cas_module', 'team_id', 'team_set_id', 'assigned_user_id',
        ];

        // Now put them into a format that SugarQuery likes
        foreach ($inboxFields as $field) {
            $fields[] = array("a.$field", $field);
        }

        $q->from($inboxBean, array('alias' => 'a'));

        
        //INNER USER TABLE
        $fields[] = array("a.created_by", "created_by");
        $q->joinTable('users', array('alias' => 'u', 'joinType' => 'INNER', 'linkingTable' => true))
            ->on()
            ->equalsField('u.id', 'a.created_by');
        $fields[] = ['u.last_name', 'assigned_user_name'];

        //INNER PROCESS TABLE
        $q->joinTable('pmse_bpmn_process', array('alias' => 'pr', 'joinType' => 'INNER', 'linkingTable' => true))
            ->on()
            ->equalsField('pr.id', 'a.pro_id');
        $fields[] = array('pr.prj_id', 'prj_id');
        $fields[] = array('pr.name', 'pro_title');

        //INNER PROJECT TABLE
        $q->joinTable('pmse_project', array('alias' => 'prj', 'joinType' => 'INNER', 'linkingTable' => true))
            ->on()
            ->equalsField('prj.id', 'pr.prj_id');
        $fields[] = ['prj.assigned_user_id', 'prj_created_by'];
        // $fields[] = ['prj.prj_module', 'prj_module'];
        $fields[] = ['prj.prj_run_order', 'prj_run_order'];

        //INNER BPM FLOW
        // This relationship is adding several duplicated rows to the query
        // use of DISTINCT should be added
        $q->joinTable('pmse_bpm_flow', array('alias' => 'pf', 'joinType' => 'INNER', 'linkingTable' => true))
            ->on()
            ->equalsField('pf.cas_id', 'a.cas_id')
            ->equals('pf.cas_index', 1);
        $fields[] = ['pf.cas_sugar_module', 'cas_sugar_module'];
        $fields[] = ['pf.cas_sugar_object_id', 'cas_sugar_object_id'];

        // Since we are retrieving deleted project's processes, we need to know
        // which of them are from deleted projects.
        $fields[] = array('pr.deleted', 'prj_deleted');

        $q->select($fields);

        $q->where()
            // Filtered for given module
            ->equals('prj.prj_module', $module)
            // Filtered for given module
            ->equals('pf.cas_sugar_object_id', $id)
            // Filtered for not deleted records
            ->equals('u.deleted', 0);
        

        if ($args['q'] != "") {
            $regex = '/(?:(pnum):([\d]+))|(?:(pid):([a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}+))|(?:(ptitle):(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'|([^\s]+)))/';
            // If the string is only contains pid|ptitle: keyword
            if (preg_match_all($regex, $args['q'], $matches, PREG_SET_ORDER, 0)){
                $queryAnd = $q->where->queryAnd();
                $queryOr = $queryAnd->queryOr();
                foreach ($matches as $match) {
                    foreach ($match as $matchKey => $matchValue) {
                        if($matchValue == ""){
                            unset($match[$matchKey]);
                        }
                    }
                    $match = array_values($match);
                    switch($match[1]){
                        case "pid":{
                            $queryOr->equals('a.pro_id', $match[2]);
                            break;
                        }
                        case "pnum":{
                            $queryOr->equals('a.cas_id', $match[2]);
                            break;
                        }
                        case "ptitle":{
                            $queryOr->like('a.pro_title', '%'.$match[2].'%');
                            break;
                        }
                    }
                }
            }
            else{
                $qLike = $q->getDBManager()->quoted('%' . $args['q'] . '%');
                $q->where()->queryAnd()
                    ->addRaw("
                        a.pro_title LIKE $qLike OR
                        a.cas_id LIKE $qLike OR
                        a.pro_id LIKE $qLike
                    ");
            }            
        }
        
        if ($args['status'] != ""){
            $statusArgs = explode(",", $args['status']);
            if(safeCount($statusArgs) > 0){
                $q->where()->queryAnd()->in("cas_status", array_values($statusArgs));
            }
        }

        foreach ($options['order_by'] as $orderBy) {
            $q->orderBy($orderBy[0], $orderBy[1]);
        }

        // Add an extra record to the limit so we can detect if there are more records to be found
        $q->limit($options['limit']);
        $q->offset($options['offset']);

        $count = 0;
        $list = $q->execute();

        // Check if are passed in the field arguments
        $additionFieldsCheck = safeCount(array_intersect(['date_entered, date_modified', 'cas_create_date', 'cas_user_id_full_name', 'prj_user_id_full_name', 'assigned_user_full_name'], $fieldsArg)) > 0;
        if (!empty($list) && $additionFieldsCheck) {
            foreach ($list as $key => $value) {
                // Get the assigned bean early. This allows us to check for a bean
                // id to determine if the bean has been deleted or not. This bean
                // will also be used later to the assigned user of the record.
                $assignedBean = BeanFactory::getBean($list[$key]['cas_sugar_module'], $list[$key]['cas_sugar_object_id'], ['erased_fields' => true]);
                if (is_null($assignedBean)) {
                    continue;
                }

                if(in_array('cas_title', $fieldsArg)){
                    $list[$key] = PMSEEngineUtils::appendNameFields($assignedBean, $value);
                }
                if(in_array('cas_create_date', $fieldsArg)){
                    $list[$key]['cas_create_date'] = PMSEEngineUtils::getDateToFE($value['cas_create_date'], 'datetime');
                }
                // TODO: Add this to the field defs
                if(in_array('date_entered', $fieldsArg)){
                    $list[$key]['date_entered'] = PMSEEngineUtils::getDateToFE($value['date_entered'], 'datetime');
                }
                // TODO: Add this to the field defs
                if(in_array('date_modified', $fieldsArg)){
                    $list[$key]['date_modified'] = PMSEEngineUtils::getDateToFE($value['date_modified'], 'datetime');
                }
                if(in_array('assigned_user_full_name', $fieldsArg)){
                    $assignedUsersBean = BeanFactory::getBean('Users', $assignedBean->assigned_user_id);
                    $list[$key]['assigned_user_full_name'] = $assignedUsersBean->full_name;
                }
                if(in_array('prj_user_id_full_name', $fieldsArg)){
                    $prjUsersBean = BeanFactory::getBean('Users', $list[$key]['prj_created_by']);
                    $list[$key]['prj_user_id_full_name'] = $prjUsersBean->full_name;
                }
                
                if(in_array('cas_user_id_full_name', $fieldsArg)){
                    $qA = new SugarQuery();
                    $flowBean = BeanFactory::newBean('pmse_BpmFlow');
                    $qA->select->fieldRaw('*');
                    $qA->from($flowBean);
                    $qA->where()->equals('cas_id', $list[$key]['cas_id']);
                    
                    $processUsers = $qA->execute();
                    if (!empty($processUsers)) {
                        $processUsersNames = array();
                        foreach ($processUsers as $k => $v) {
                            if ($processUsers[$k]['cas_flow_status'] != 'CLOSED') {
                                $casUsersBean = BeanFactory::getBean('Users', $processUsers[$k]['cas_user_id']);
                                $processUsersNames[] = (!empty($casUsersBean->full_name)) ? $casUsersBean->full_name : '';
                            }
                        }
                        if (empty($processUsersNames)) {
                            $userNames = '';
                        } else {
                            $processUsersNames = array_unique($processUsersNames);
                            $userNames = implode(', ', $processUsersNames);
                        }
                        $list[$key]['cas_user_id_full_name'] = $userNames;
                    }
                }

                $count++;
            }
        }
        else{
            $count = safeCount($list);
        }
        if ($count == $options['limit']) {
            $offset = $options['offset'] + $options['limit'];
        } else {
            $offset = -1;
        }

        $data = array();
        $data['next_offset'] = $offset;
        $data['records'] = array_values($list);
        return $data;
    }
}
