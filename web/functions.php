<?php


function detectBrowserLanguage() {
    $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    return $langs[0]; // Return the first language from the browser's language preferences
}


/**
 * Fetch LD-JSON data from an URL
 *
 * @param string $urlToFetch
 * @return mixed|null
 */
function fetchUrl($urlToFetch)
{
    if (empty($urlToFetch)) {
        http_response_code(400);
        return "Error: can't find URL to fetch";
    }

    $eventData = null;

    try {
        // Fetch data using cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlToFetch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AddTo by WebSenso');
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 2000); // Timeout en millisecondes
        $contentToFetch = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception('cURL error: ' . curl_error($ch));
        }

        if ($httpCode !== 200) {
            throw new Exception('Failed to fetch URL, HTTP code: ' . $httpCode);
        }

        curl_close($ch);

        // Parse the HTML to retrieve the "ld+json" script
        $contentToParse = new DomDocument();
        @$contentToParse->loadHTML($contentToFetch);

        $xp = new DOMXPath($contentToParse);
        $jsonScripts = $xp->query('//script[@type="application/ld+json"]');

        if ($jsonScripts->length === 0) {
            throw new Exception('No ld+json script found in the HTML content');
        }

        $ldJson = trim($jsonScripts->item(0)->nodeValue);
        $eventData = json_decode($ldJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON decode error: ' . json_last_error_msg());
        }

        if (empty($eventData)) {
            throw new Exception('No data in the JSON-LD');
        }

    } catch (Exception $e) {
        http_response_code(400);
        return "Error: " . $e->getMessage();
    }

    return $eventData;
}


/**
 * Extract Ld-Json events data to array PHP
 *
 * @param array $eventData
 * @return array|string
 */
function extractEventDataFromLdJson($eventData)
{
    // Ensure the input is an array and contains the @graph key
    if (!is_array($eventData) || !isset($eventData["@graph"])) {
        return "Invalid input data.";
    }

    // Extract event data
    $event = null;
    foreach ($eventData["@graph"] as $item) {
        if (isset($item["@type"]) && $item["@type"] === "Event") {
            $event = $item;
            break;
        }
    }

    if (!$event) {
        return "No event found in the provided LD-JSON tags.";
    }

    // Extract event details
    $eventDetails = array(
        "name" => $event["name"] ?? '',
        "start" => $event["startDate"] ?? '',
        "end" => $event["endDate"] ?? '',
        "description" => $event["description"] ?? '',
        "url" => $event["url"] ?? ''
    );

    if (isset($event["location"]["address"])) {
        $location = $event["location"]["address"];
        $streetAddress = is_array($location["streetAddress"]) ? implode(" ", $location["streetAddress"]) : ($location["streetAddress"] ?? '');
        $eventDetails["address"] = sprintf(
            "%s, %s, %s, %s",
            $streetAddress,
            $location["addressLocality"] ?? '',
            $location["postalCode"] ?? '',
            $location["addressCountry"] ?? ''
        );
    } else {
        $eventDetails["address"] = '';
    }

    return $eventDetails;
}


$translations = [
    'en' => [
        'title' => 'Add to your Calendar',
        'event' => 'Event',
        'time' => 'Time',
        'location' => 'Location',
        'obsolete' => '⚠️ This event seems obsolete. Check the dates.',
        'back' => '↩️ Back to event',
        'apple_calendar' => 'Apple Calendar',
        'google_calendar' => 'Google Calendar',
        'yahoo_calendar' => 'Yahoo Calendar',
        'web_outlook' => 'Web Outlook',
        'ics' => 'ICS: iCal & Outlook',
        'github_source' => 'Source on',
        'made_by' => 'Made by WebSenso.com',
    ],
    'fr' => [
        'title' => 'Ajouter à votre calendrier',
        'event' => 'Événement',
        'time' => 'Heure',
        'location' => 'Emplacement',
        'obsolete' => '⚠️ Cet événement semble obsolète. Vérifiez les dates.',
        'back' => '↩️ Retour à l\'événement',
        'apple_calendar' => 'Calendrier Apple',
        'google_calendar' => 'Google Agenda',
        'yahoo_calendar' => 'Yahoo Agenda',
        'web_outlook' => 'Web Outlook',
        'ics' => 'ICS: iCal & Outlook',
        'github_source' => 'Source sur',
        'made_by' => 'Conçu par l\'agence WebSenso',
    ],
];


