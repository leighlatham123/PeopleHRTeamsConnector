<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MicrosoftTeamsAbscences extends Command
{
    private $stringVar;
    private $eventsVar;
    private $totalEventsCount;
    private $holidayMessage;
    private $tmpArr = array();
    private $responsesArr = array();
    
    private $teamsAPI = "{{Your Teams API connector webhook}}";
    private $peopleHrApiKey = "{{Your People HR API connector webhook}}";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:MicrosoftTeamsAbscences';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates Microsoft Teams with the PeopleHR events.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $holidayPayload = json_encode(
            [
                "APIKey" => $this->peopleHrApiKey,
                "Action" => "GetQueryResult",
                "QueryName" => "Holiday : Outlook Feed (DO NOT REMOVE)"
            ]
        );

        $eventsPayload = json_encode(
            [
                "APIKey" => $this->peopleHrApiKey,
                "Action" => "GetQueryResult",
                "QueryName" => "Other Events : Outlook Feed (DO NOT REMOVE)"
            ]
        );

        $this->responsesArr["holidays"] = json_decode($this->queryEvents($holidayPayload), true);
        $this->responsesArr["events"] = json_decode($this->queryEvents($eventsPayload), true);

        if($this->responsesArr["holidays"]["isError"] || $this->responsesArr["events"]["isError"])
        {
            return false;
        }

        $holidaysCount = $this->checkCount($this->responsesArr["holidays"]["Result"]);
        $eventsCount = $this->checkCount($this->responsesArr["events"]["Result"]);

        $this->totalEventsCount = $holidaysCount + $eventsCount;

        if ($this->totalEventsCount === 1)
        {
            $this->stringVar = "is ";
            $this->eventsVar = "event ";
        }
        else
        {
            $this->stringVar = "are ";
            $this->eventsVar = "events ";
        }

        $this->holidayMessage = "**There ".$this->stringVar.$this->totalEventsCount." ".$this->eventsVar."today.** <br>";

        array_push($this->tmpArr, $this->holidayMessage);

        $this->arrayBuilder($holidaysCount, $this->responsesArr["holidays"]["Result"], "Holiday");
        $this->arrayBuilder($eventsCount, $this->responsesArr["events"]["Result"], "Other Events");

        $teamsPayload = implode(',', array_values($this->tmpArr));
        $json = json_encode(array("text" => $teamsPayload), JSON_UNESCAPED_SLASHES);
        $json = preg_replace('~[,|]~', '', $json);

        try
        {
            $this->updateTeams($json);
        }
        catch (Exception $e)
        {
            Log::channel('custom_commands')->error("Failed synchronization of People HR abscences with Microsoft Teams." . $e->getMessage());
            echo 'Failure, please check the logs.';
            return false;
        }

        Log::channel('custom_commands')->info("Successful synchronization of People HR abscences with Microsoft Teams.");
        echo "Complete!";
        return true;

    }

    private function checkCount($array)
    {
        if (!empty($array))
        {
            return count($array);
        }

        return 0;
    }

    private function arrayBuilder($check, $values, $string)
    {
        if (!empty($check))
        {
            foreach ($values as $value)
            {
                $v = $value['First Name']." ".$value['Last Name']." - $string : ".$value[$string.' Start Date']." - ".$value[$string.' End Date']."<br>";
                array_push($this->tmpArr, $v);
            }
        }
    }

    private function queryEvents($postJSON)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.peoplehr.net/Query',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postJSON,
            CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
}

    private function updateTeams($payload)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->teamsAPI,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }
}
