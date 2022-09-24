<?php
require_once '../vendor/autoload.php';
use Spatie\CalendarLinks\Link;

try {
// Fetch data
  $urlToFetch = $_GET['url'];
  if (empty($urlToFetch)) {
    throw new Exception('can\'t find URL to fetch');
  }
  $contentToFetch = file_get_contents($urlToFetch);
  if ($contentToFetch === FALSE) {
    throw new Exception('can\'t fetch URL\'s content');
  }
  $contentToParse = new DomDocument();
  @$contentToParse->loadHTML($contentToFetch);

  // parse the HTML to retrieve the "ld+json" only
  $xp          = new domxpath($contentToParse);
  $jsonScripts = $xp->query('//script[@type="application/ld+json"]');
  $json        = trim($jsonScripts->item(0)->nodeValue); // get the first script only (it should be unique anyway)
  $eventData        = json_decode($json, TRUE);

  if (empty($eventData)) {
    throw new Exception('No data');
  }

// debug
  if ($_GET['debug']) {
    print "<pre>";
    print_r($eventData);
    print "</pre>";
  }

// Display links

  $event = ($eventData[0]) ? $eventData[0] : $eventData;   // Take only one event.
  if (!empty($event["@graph"])) {
    $event = $event["@graph"][0];
  }

  // Format data
  $localTimeZone = new DateTimeZone('Europe/Paris');

  $startDate = new DateTime($event['startDate']);
  $startDate->setTimezone($localTimeZone);

  $endDate = ($event['endDate']) ? new DateTime($event['endDate']) : (clone $startDate)->modify("+1 hour");
  $endDate->setTimezone($localTimeZone);

  $name        = $event['name'];
  $description = $event['description'];
  $description .= '\r\r Plus d\'info : ' . $event['url'];

  $address = '';
  $address .= ($event['location']['name']) ? $event['location']['name'] . ', ' : '';
  $address .= ($event['location']['address']['streetAddress']) ? $event['location']['address']['streetAddress'] . ', ' : '';
  $address .= $event['location']['address']['postalCode'] . ' ';
  $address .= $event['location']['address']['addressLocality'] . ', ';
  $address .= $event['location']['address']['addressCountry'];

  // debug
  if ($_GET['debug']) {
    print "<pre>";
    print_r($event['startDate']);
    print_r($startDate);
    print_r($endDate);
    print "</pre>";
  }

  // Create calendar links
  $link = Link::create($name, $startDate, $endDate);
  $link->description($description);
  $link->address($address);


} catch (Exception $e) {
  http_response_code(400); // https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/400
  $errorMessage = "Error: " . $e->getMessage();
}



// HTML
?><!doctype html>
<html class="no-js">

<head>
    <meta charset="utf-8">
    <title>Add -to-Calendar</title>
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style type="text/css">
        :root {
            --color-gris       : #333;
            --color-gris-clair : #999;
            }

        body {
            line-height : 1.65;
            font-family : 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
            text-align  : center;
            margin-top  : 2rem;
            }

        .container {
            max-width    : 42rem;
            margin-left  : auto;
            margin-right : auto;
            }

        quote {
            border-left   : 1px dashed var(--color-gris-clair);
            display       : block;
            text-align    : left;
            padding-left  : 1.5rem;
            margin-left   : 20px;
            margin-bottom : 2rem;
            color         : var(--color-gris-clair);
            font-size     : 0.8em;
            }


        footer p,
        footer p a {
            color : var(--color-gris-clair);
            }

        footer p {
            font-size : 0.8rem;
            }

        main {
            min-height : 50vh;
            }

        ul {
            margin  : 0;
            padding : 0;
            }

        ul li {

            list-style : none;
            }

        ul li a {
            padding             : 1rem 0;
            display             : inline-block;
            padding-left        : 80px;
            width               : calc(100% - 80px);
            text-decoration     : none;
            color               : black;
            background-repeat   : no-repeat;
            background-size     : 35px;
            background-position : 20px center;
            margin              : 0;
            text-align          : left;
            position            : relative;
            }

        ul li a:hover {
            background-color : var(--color-gris-clair);
            color            : white;
            border-radius    : 6px;
            }

        ul li a::before {
            content           : "";
            width             : 15px;
            height            : 15px;
            display           : block;
            border-top        : 1px solid var(--color-gris);
            border-right      : 1px solid var(--color-gris);
            position          : absolute;
            top               : 22px;
            right             : 22px;
            -webkit-transform : translateX(-50%) rotate(45deg);
            transform         : translateX(-50%) rotate(45deg);
            }

        ul li a:hover::before {
            border-top-color   : white;
            border-right-color : white;
            }


        .apple a {
            background-image : url('/images/apple.svg');
            }

        .google a {
            background-image : url('/images/google.svg');
            }

        .ics a {
            background-image : url('/images/ics.svg');
            }

        .yahoo a {
            background-image : url('/images/yahoo.svg');
            }

        .weboutlook a {
            background-image : url('/images/outlook.svg');
            }

        .alert {
            padding          : 15px;
            margin-bottom    : 20px;
            border-radius    : 4px;
            color            : #8a6d3b;
            background-color : #fcf8e3;
            border-color     : #faebcc;
            }
    </style>
</head>

<body>

<div class="container">

    <header>
        <h1>🗓</h1>
    </header>

    <main>
      <?php if ($errorMessage): ?>

          <p class="alert"><?= $errorMessage ?></p>

      <?php else: ?>

          <quote>
            <?= $name ?>
              <br/>🕐 <?= date_format($startDate, "d M Y - H \h i") ?>
              ⏩ <?= date_format($endDate, "d M Y - H \h i") ?>
              <br/>📍 <?= $address ?>
          </quote>

        <?php if ($startDate < new DateTime()): ?>
              <p class="alert">⚠️ This event is in the past.</p>
        <?php endif ?>


          <ul>

              <!--// Generate a data uri for an ics file (for iCal & Outlook)-->
              <li class="apple"><a
                          href="<?= $link->ics(['URL' => $event['url']]) ?>" rel="external nofollow">Apple
                      Calendar</a></li>

              <!-- // Generate a link to create an event on Google calendar-->
              <li class="google"><a href="<?= $link->google() ?>" rel="external nofollow">Google
                      Calendar</a></li>

              <!--// Generate a link to create an event on Yahoo calendar-->
              <li class="yahoo"><a href="<?= $link->yahoo() ?>" rel="external nofollow">Yahoo
                      Calendar</a></li>

              <!--// Generate a link to create an event on outlook.com calendar-->
              <li class="weboutlook"><a href="<?= $link->webOutlook() ?>" rel="external nofollow">Web
                      Outlook</a></li>

              <!--// Generate a data uri for an ics file (for iCal & Outlook)-->
              <li class="ics"><a
                          href="<?= $link->ics(['URL' => $event['url']]) ?>"  rel="external nofollow">ICS
                      : iCal & Outlook</a></li>

          </ul>

          <p><a href="<?= $event['url'] ?>">↩️</a></p>


      <?php endif ?>
    </main>

    <footer>
        <p>Source on <a href="https://github.com/jim005/addto" target="_blank" rel="external nofollow">GitHub</a>
            -
            Made by <a href="https://www.websenso.com" target="_blank" rel="external">WebSenso.com</a>
        </p>
    </footer>

</div>

</body>
</html>