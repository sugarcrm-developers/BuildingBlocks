<?php


use Sugarcrm\Sugarcrm\Security\HttpClient\ExternalResourceClient;

class CustomPMSECustomWebhook extends \PMSEScriptTask
{
    private int $timeout = 10;

    private array $noteLines = [''];

    public function __construct()
    {
        parent::__construct();
    }

    private function addNoteLine(string $msg): void
    {
        $this->noteLines[] = $msg;
    }

    private function getNoteLines(bool $asString = false): array|string
    {
        $noteLines = $this->noteLines;
        if ($asString) {
            $noteLines = implode("<br>● ", $noteLines);
        }
        return $noteLines;
    }

    private function saveBPMNote(array $flowData): void
    {
        $notes = \BeanFactory::newBean('pmse_BpmNotes');
        $notes->cas_id = $flowData['cas_id'];
        $notes->cas_index = $flowData['cas_index'];
        $notes->not_user_id = $flowData['cas_user_id'];
        $notes->not_user_recipient_id = null;
        $notes->not_type = 'LOG'; //'GENERAL'
        $notes->not_date = date("Y-m-d H:i:s");
        $notes->not_status = 'ACTIVE';
        $notes->not_availability = '';
        $notes->not_content = $this->getNoteLines(true);
        $notes->not_recipients = '';
        $notes->save();
    }

    protected function isJsonString(string $string): bool
    {
        $isJson = false;
        try {
            json_decode($string);
            $isJson = json_last_error() === JSON_ERROR_NONE;
        } catch (\Exception $e) {
        }
        return $isJson;
    }

    public function run($flowData, $bean = null, $externalAction = '', $arguments = [])
    {
        $bpmnElement = $this->retrieveDefinitionData($flowData['bpmn_id']);

        if ($bpmnElement) {
            $act_fields = json_decode($bpmnElement['act_fields'], true);
            $variableFields = [
                'act_request_url',
                'act_request_headers',
                'act_request_payload',
                'act_request_timeout',
            ];
            $jsonFields = [
                'act_request_headers',
                'act_request_payload',
            ];
            foreach ($variableFields as $variableField) {
                $value = $act_fields[$variableField];
                if (in_array($variableField, $jsonFields) && $this->isJsonString($value)) {
                    $jsonValue = json_decode($value, true);
                    if (is_array($jsonValue)) {
                        foreach ($jsonValue as $k => $jv) {
                            $jsonValue[$k] = $this->beanHandler->mergeBeanInTemplate($bean, $jv);
                        }
                        $value = $jsonValue;
                    } else {
                        $value = $this->beanHandler->mergeBeanInTemplate($bean, $value);
                    }
                } else {
                    $value = $this->beanHandler->mergeBeanInTemplate($bean, $value);
                }
                $act_fields[$variableField] = $value;
            }

            if (isset($act_fields['act_request_timeout']) && is_numeric($act_fields['act_request_timeout'])) {
                $this->timeout = (int)$act_fields['act_request_timeout'];
                $this->addNoteLine("Request timeout set to {$this->timeout} seconds.");
            }

            $headers = $act_fields['act_request_headers'];
            if (empty($headers)) {
                $headers = [];
            }
            $payload = $act_fields['act_request_payload'];
            //If payload is an array, that means it was valid JSON and needs to be encoded back to string for request
            if (is_array($payload)) {
                $payload = json_encode($payload);
            }
            $this->processWebhook(
                $act_fields['act_request_method'],
                $act_fields['act_request_url'],
                $headers,
                $payload
            );
        } else {
            $this->addNoteLine("[ERROR] BPM Element not found.");
        }
        // save the note before returning
        $this->saveBPMNote($flowData);
        return parent::run($flowData, $bean, $externalAction, $arguments);
    }

    protected function processWebhook(
        string $method,
        string $url,
        array $headers = [],
        string|array|null $payload = null
    ): void
    {
        $this->addNoteLine("Processing Webhook: [$method] $url");
        try {
            $client = new ExternalResourceClient($this->timeout);
            //ExternalResourceClient does this automatically, so we add it here to properly log it
            $headers = array_merge(['Content-type' => 'application/x-www-form-urlencoded'], $headers);
            $this->addNoteLine("Headers: " . json_encode($headers, JSON_PRETTY_PRINT));
            if ($payload) {
                $this->addNoteLine("Payload RAW: " . json_encode($payload, JSON_PRETTY_PRINT));
                $payload = is_string($payload) ? $payload : http_build_query($payload);
                $this->addNoteLine("Payload: " . json_encode($payload, JSON_PRETTY_PRINT));
            }
            $response = $client->request($method, $url, $payload, $headers);
            if (!empty($response)) {
                $this->addNoteLine("Response Status Code: " . $response->getStatusCode());
                $bodyContent = $response->getBody()->getContents();
                // TODO: Better to write to somewhere else ?
                $this->addNoteLine("Response Body: " . $bodyContent);
            }
        } catch (Exception $e) {
            $this->addNoteLine("[ERROR] Request failed: " . $e->getMessage());
        }
    }
}