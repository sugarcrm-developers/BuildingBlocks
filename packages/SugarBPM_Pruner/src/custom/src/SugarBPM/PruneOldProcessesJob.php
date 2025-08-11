<?php

namespace Sugarcrm\Sugarcrm\custom\SugarBPM;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\InvalidArgumentException as ExceptionInvalidArgumentException;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use RunnableSchedulerJob;
use Scheduler;
use SchedulersJob;
use Psr\Log\LoggerAwareTrait;

class PruneOldProcessesJob implements RunnableSchedulerJob
{
    use LoggerAwareTrait;

    const DONE_STATUSES = ["COMPLETED", "CANCELLED", "TERMINATED", "ERROR"];

    protected $deleteDaysBefore = 30;
    /**
     * @var SchedulersJob
     */
    protected $job;

    protected $timeStart = null;

    /**
     * @var int
     */
    protected $timeoutThreshold = 10 * 60; // 10 minutes
    protected $limit            = 100;

    /**
     * @var int
     */
    protected $deadline = 0;

    public function __construct()
    {
        $this->setLogger(\Sugarcrm\Sugarcrm\Logger\Factory::getLogger('bpm_pruner'));
        $this->limit = \SugarConfig::getInstance()->get('bpm_pruner.limit', $this->limit);
        $this->deleteDaysBefore = \SugarConfig::getInstance()->get('bpm_pruner.days_before', $this->deleteDaysBefore);
    }

    /**
     * @param SchedulersJob $job
     *
     * @return void
     */
    public function setJob(SchedulersJob $job): void
    {
        $this->job = $job;
    }

    /**
     * @param mixed $data
     *
     * @return bool
     */
    public function run($data): bool
    {
        try {
            $this->init($data);

            $status = $this->runWithDeadline($this->deadline);

            if ($status["shouldReschedule"] === true) {
                $this->scheduleNextQuery(true, $status["processed"]);
            }

        } catch (\Throwable $e) {
            $this->job->name .= " [ERROR: " . $e->getMessage() . "]";
            $this->job->message = $e->getMessage() . "\n" . $e->getTraceAsString();
        }

        return true;
    }

    /**
     *
     * @param mixed $data
     * @return void
     */
    protected function init($data): void
    {
        /** @var SugarCronJobs $jobq */
        global $jobq;

        $this->timeStart = \TimeDate::getInstance()->getNow()->getTimestamp();

        if (\intval($jobq->max_runtime) <= $this->timeoutThreshold) {
            $this->deadline = $this->timeStart + (\intval($jobq->max_runtime));
        } else {
            $this->deadline = $this->timeStart + (\intval($jobq->max_runtime)) - $this->timeoutThreshold;
        }

        $this->logger->info(
            "Reschedule job started at {$this->time_start} with a deadline of {$this->deadline}."
        );

        //Set $this->shouldProcess = true in order to enter at the first time in the functionality coode.
        $this->shouldProcess = true;
    }

    /**
     *
     * @param int $deadline
     * @return array
     * @throws Exception
     * @throws DBALException
     */
    protected function runWithDeadline(int $deadline): array
    {

        while ($this->shouldProcess === true) {
            $recordsData = $this->getData($this->deleteDaysBefore);
            $processed   = 0;

            if (empty($recordsData) === true) {
                return [
                    "shouldReschedule" => false,
                ];
            }

            foreach ($recordsData as $recordData) {
                $recordCasId = $recordData["cas_id"];

                $this->deleteOldRecords($recordCasId);

                $processed++;

                if (time() > $deadline) {
                    return [
                        "shouldReschedule" => true,
                        "processed"        => $processed,
                    ];
                }
            }

            if (time() > $this->deadline) {
                return [
                    "shouldReschedule" => true,
                    "processed"        => $processed,
                ];
            }

            if ($processed < $this->limit) {
                $this->shouldProcess = false;
            }
        }
        return [
            "shouldReschedule" => false,
        ];
    }

    /**
     *
     * @param string $firstLimitDateModified
     * @param string $secondLimitDateModified
     * @param array $statuses
     * @param string $status
     * @return array
     * @throws Exception
     * @throws DBALException
     */
    protected function getData(int $daysAgo): array
    {
        $conn = \DBManagerFactory::getConnection();

        $bpm_inbox_select_query = <<<EOQ
SELECT DISTINCT cas_id
FROM pmse_inbox
WHERE
cas_status IN (?) AND
date_modified < DATE_SUB(NOW(), INTERVAL ? DAY) 
LIMIT ?
EOQ;

        $this->logger->info("getData: Running query to get cas_id list");
        $bpm_inbox_select_stmt = $conn->executeQuery($bpm_inbox_select_query,
            [
                self::DONE_STATUSES,
                $daysAgo,
                $this->limit,
            ],
            [
                \Sugarcrm\Sugarcrm\Dbal\Connection::PARAM_STR_ARRAY,
                \Doctrine\DBAL\ParameterType::INTEGER,
                \Doctrine\DBAL\ParameterType::INTEGER,
            ]);

        return $bpm_inbox_select_stmt->fetchAllAssociative();
    }


    /**
     *
     * @param string $recordCasId
     * @return void
     * @throws Exception
     * @throws DBALException
     */
    public function deleteOldStartEvents(string $recordCasId): void
    {

        $conn = \DBManagerFactory::getConnection();

        //Check that cas_id linked to Process Definiton that is not a "First Update" BPM
        $bpm_flow_start_event_select_query = <<<EOQ
SELECT cas_id
FROM pmse_bpm_flow
INNER JOIN pmse_bpm_event_definition 
ON pmse_bpm_event_definition.id = pmse_bpm_flow.bpmn_id
WHERE pmse_bpm_flow.cas_id = ? AND pmse_bpm_flow.cas_index = 1
AND pmse_bpm_event_definition.evn_params NOT IN ("updated", "newfirstupdated")
LIMIT 1
EOQ;

        $this->logger->info("deleteOldStartEvents: Running query to confirm cas_id belongs to non-update Process Definition [cas_id : {$recordCasId}]");

        $bpm_flow_start_event_select_stmt = $conn->executeQuery($bpm_flow_start_event_select_query,[$recordCasId], [\Doctrine\DBAL\ParameterType::STRING]);
        $bpm_flow_start_event_select_result_count = $bpm_flow_start_event_select_stmt->rowCount();
        
        if($bpm_flow_start_event_select_result_count > 0) {
            $this->logger->info("deleteOldStartEvents: Deleting rows from pmse_bpm_flow [cas_id : {$recordCasId}]");
            $bpm_flow_start_event_delete_query = 'DELETE FROM pmse_bpm_flow WHERE cas_id = ?';
            $bpm_flow_start_event_delete_stmt = $conn->executeQuery($bpm_flow_start_event_delete_query,[$recordCasId], [\Doctrine\DBAL\ParameterType::STRING]);
        }
    }

    /**
     *
     * @param string $recordCasId
     * @return void
     * @throws Exception
     */
    public function deleteOldRecords(string $recordCasId): void
    {
        $this->deleteOldStartEvents($recordCasId);

        $conn = \DBManagerFactory::getConnection();

        $this->logger->info("deleteOldRecords: Deleting rows from pmse_bpm_flow [cas_id : {$recordCasId}]");
        $bpm_flow_delete_query = 'DELETE FROM pmse_bpm_flow WHERE cas_id = ? AND cas_index != 1';
        $bpm_flow_delete_stmt = $conn->executeQuery($bpm_flow_delete_query,[$recordCasId], [\Doctrine\DBAL\ParameterType::STRING]);

        $this->logger->info("deleteOldRecords: Deleting rows from pmse_inbox [cas_id : {$recordCasId}]");
        $bpm_inbox_delete_query = 'DELETE FROM pmse_inbox WHERE cas_id = ?';
        $bpm_inbox_delete_stmt = $conn->executeQuery($bpm_inbox_delete_query,[$recordCasId], [\Doctrine\DBAL\ParameterType::STRING]);

        $this->logger->info("deleteOldRecords: Deleting rows from pmse_bpm_thread [cas_id : {$recordCasId}]");
        $bpm_thread_delete_query = 'DELETE FROM pmse_bpm_thread WHERE cas_id = ?';
        $bpm_thread_delete_stmt = $conn->executeQuery($bpm_thread_delete_query,[$recordCasId], [\Doctrine\DBAL\ParameterType::STRING]);
    }

    /**
     *
     * @param bool $timeLimitReached
     * @param string $processed
     * @return void
     * @throws UnsatisfiedDependencyException
     * @throws Exception
     * @throws ExceptionInvalidArgumentException
     */
    protected function scheduleNextQuery(bool $timeLimitReached, string $processed): void
    {
        global $current_user;
        $processed = [
            "Processed" => $processed,
        ];
        $job                   = new \SchedulersJob();
        $job->name             = $this->buildJobName($processed);
        $job->target           = $this->job->target;
        $job->assigned_user_id = $current_user->id;
        $job->scheduler_id     = $this->job->scheduler_id;

        /**
         * === Start hack ===
         *
         * Prevent the current job from being scheduled multiple times at the same time and date
         */
        $timedate              = \TimeDate::getInstance();
        $previousAllowCache    = $timedate->allow_cache;
        $timedate->allow_cache = false;
        $job->execute_time     = $timedate->getNow()->modify($timeLimitReached ? "+15 seconds" : "+120 seconds")->asDb();
        $timedate->allow_cache = $previousAllowCache;
        /**
         * === End hack ===
         */

        $jobQueue = new \SugarJobQueue();
        $jobQueue->submitJob($job);
    }

    /**
     * @param array $params
     *
     * @return string
     *
     * @throws SugarQueryException
     */
    protected function buildJobName(array $params = []): string
    {
        if (empty($this->job->scheduler_id)) {
            return $this->job->name;
        }

        $scheduler = \BeanFactory::retrieveBean("Schedulers", $this->job->scheduler_id);

        $jobName = $scheduler instanceof Scheduler ? $scheduler->name : "";

        foreach ($params as $name => $value) {
            $jobName = "[CHILD]{$jobName}[{$name}: {$value}]";
        }

        return $jobName;
    }
}
