<?php
require_once '../vendor/autoload.php';
use Spatie\CalendarLinks\Link;
require_once 'functions.php';

// Detect browser language
$browserLang = detectBrowserLanguage();
$lang = substr($browserLang, 0, 2); // Get the first two characters of the language code
$lang = in_array($lang, ['en', 'fr']) ? $lang : 'en'; // Default to English if not supported

// Fetch translations
$trans = $translations[$lang];

// Fetch and validate URL parameter
$urlToFetch = filter_input(INPUT_GET, 'url', FILTER_VALIDATE_URL);
if (!$urlToFetch) {
    die('No valid URL to fetch');
}

// Fetch event data
$eventData = fetchUrl($urlToFetch);
$event = extractEventDataFromLdJson($eventData);

// Debug mode
if (!empty($_GET['debug'])) {
    echo '<pre>';
    var_dump($event);
    echo '</pre>';
    exit;
}

if (!is_array($event)) {
    print $event;
    exit;
}

// Process event data
$event = is_array($event) && isset($event[0]) ? $event[0] : $event;
if (isset($event["@graph"])) {
    $event = $event["@graph"][0];
}

// Format data
$localTimeZone = new DateTimeZone('Europe/Paris');
$startDate = new DateTime($event['start'], $localTimeZone);
$endDate = isset($event['end']) ? new DateTime($event['end'], $localTimeZone) : clone $startDate;

$allDay = $startDate->format('H:i:s') === '00:00:00' && $endDate->format('H:i:s') === '00:00:00';
if (!$allDay && $startDate == $endDate) {
    $endDate->modify('+1 hour');
}

$dateText = formatDateText($startDate, $endDate, $allDay, $lang);

$description = $event['description'] . ' ' . $event['url'];

// Create calendar links
$link = Link::create($event['name'], $startDate, $endDate, $allDay)
    ->description($description)
    ->address($event['address']);

?>
<!doctype html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($trans['title']) ?></title>
    <meta name="robots" content="noindex, nofollow, noarchive"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="/css/style.css" type="text/css"/>
</head>
<body>
<div class="container">
    <header>
        <h1>🗓 <?= htmlspecialchars($trans['title']) ?></h1>
    </header>
    <main>
        
        <?php if (!empty($errorMessage)): ?>
            <p class="alert"><?= htmlspecialchars($errorMessage) ?></p>
        <?php else: ?>
            <div class="event">
                <?= htmlspecialchars($event['name']) ?>
                <br/>🕐 <?= htmlspecialchars($dateText) ?>
                <br/>📍 <?= htmlspecialchars($event['address']) ?>
            </div>
            
            <?php if ($startDate < new DateTime()): ?>
                <p class="alert"><?= htmlspecialchars($trans['obsolete']) ?></p>
            <?php endif ?>
            
            <ul>
                <li class="apple"><a href="<?= htmlspecialchars($link->ics(['URL' => $event['url']])) ?>" rel="external nofollow"><?= htmlspecialchars($trans['apple_calendar']) ?></a></li>
                <li class="google"><a href="<?= htmlspecialchars($link->google()) ?>" rel="external nofollow"><?= htmlspecialchars($trans['google_calendar']) ?></a></li>
                <li class="yahoo"><a href="<?= htmlspecialchars($link->yahoo()) ?>" rel="external nofollow"><?= htmlspecialchars($trans['yahoo_calendar']) ?></a></li>
                <li class="weboutlook"><a href="<?= htmlspecialchars($link->webOutlook()) ?>" rel="external nofollow"><?= htmlspecialchars($trans['web_outlook']) ?></a></li>
                <li class="ics"><a href="<?= htmlspecialchars($link->ics(['URL' => $event['url']])) ?>" rel="external nofollow"><?= htmlspecialchars($trans['ics']) ?></a></li>
            </ul>
            <p><a href="<?= htmlspecialchars($event['url']) ?>"><?= htmlspecialchars($trans['back']) ?></a></p>
        <?php endif ?>
    </main>
    <footer>
        <p><?= htmlspecialchars($trans['github_source']) ?> <a href="https://github.com/jim005/addto" rel="external nofollow">GitHub</a> - <?= htmlspecialchars($trans['made_by']) ?> <a href="https://www.websenso.com" rel="external">WebSenso.com</a></p>
    </footer>
</div>
</body>
</html>