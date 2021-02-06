<?php

$peopleHrApiKey;
$holidayEvents;
$otherEvents;
$eventsCount;
$stringVar;
$eventsVar;
$holidayMessage;
$tmpArr = array();
$responsesArr = array();

$peopleHrApiKey = "";
$teamsConnectorWebhookUrl = "";

$holidayPayload = json_encode(
    [
        "APIKey" => $peopleHrApiKey,
        "Action" => "GetQueryResult",
        "QueryName" => $queryHolidaysName
    ]
);

$eventsPayload = json_encode(
    [
        "APIKey" => $peopleHrApiKey,
        "Action" => "GetQueryResult",
        "QueryName" => $queryEventsName
    ]
);

$responsesArr["holidays"] = json_decode(queryEvents($holidayPayload), true);
$responsesArr["events"] = json_decode(queryEvents($eventsPayload), true);

if($responsesArr["holidays"]["isError"] || $responsesArr["events"]["isError"])
{
    return false;
}

$eventsCount = count($responsesArr["holidays"]["Result"]) + count($responsesArr["events"]["Result"]);

if ($eventsCount === 1)
{
    $stringVar = "is";
    $eventsVar = "event";
}
else
{
    $stringVar = "are";
    $eventsVar = "events";
}

$holidayMessage = "**There $stringVar $eventsCount $eventsVar today.** <br>";

array_push($tmpArr, $holidayMessage);

foreach ($responsesArr["holidays"]["Result"] as $result)
{
    $value = $result['First Name']." ".$result['Last Name']." - ".$result['Holiday Type']." : ".$result['Holiday Start Date']."<br>";
    array_push($tmpArr, $value);
}

foreach ($responsesArr["events"]["Result"] as $result)
{
    $value = $result['First Name']." ".$result['Last Name']." - Other Events : ".$result['Other Events Start Date']."<br>";
    array_push($tmpArr, $value);
}

$teamsPayload = implode(',', array_values($tmpArr));
$json = json_encode(array("text" => $teamsPayload), JSON_UNESCAPED_SLASHES);
$json = preg_replace('~[,|]~', '', $json);

$updated = updateTeams($json, $teamsConnectorWebhookUrl);

echo $json;

function queryEvents($postJSON)
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
          'Content-Type: application/json',
          'Cookie: ServerID=1101; visid_incap_1627981=siW7epnfRkm4kmZp7+gi2xk6HWAAAAAAQUIPAAAAAADV1OpyRhFH8Nt/xbzJJ4n6; incap_ses_9196_1627981=hc6wWOKKzRZpFqq3WcGef2NOHWAAAAAA24oRvziO5oZQkCoGuLffEw=='
        ),
      ));

    $response = curl_exec($curl);

    curl_close($curl);

    return $response;
}

function updateTeams($payload, $teamsUrl)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $teamsUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => array(
          'Content-Type: application/json',
        ),
      ));

    $response = curl_exec($curl);

    curl_close($curl);

    return $response;
}