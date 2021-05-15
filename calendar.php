<?php
require_once 'vendor/autoload.php';
use Spatie\CalendarLinks\Link;


// Fetch data
$url = $_GET['url'];

$c = file_get_contents($url);
$d = new DomDocument();
@$d->loadHTML($c);

// parse the HTML to retrieve the "ld+json" only
$xp = new domxpath($d);
$jsonScripts = $xp->query('//script[@type="application/ld+json"]');
$json = trim($jsonScripts->item(0)->nodeValue); // get the first script only (it should be unique anyway)
$data = json_decode($json, true);

if (empty($data)) {
    $error = true;
    $message = 'Error : no data.';
}

// debug
if ($_GET['debug']) {
    print "<pre>";
    print_r($data);
    print "</pre>";
}

// Display links

if (!$error) {

    // Take only one event.
    $event = ($data[0]) ? $data[0] : $data;

    // Format data
    $startDate = new DateTime($event['startDate']);
    $endDate = new DateTime($event['endDate']);
    $name = $event['name'];
    $description = $event['description'];
    $description .= '<br /><br />';
    $description .= 'Source : ' . $event['url'];

    $address = '';
    $address .= $event['location']['address']['streetAddress'] . ', ';
    $address .= $event['location']['address']['postalCode'] . ' ';
    $address .= $event['location']['address']['addressLocality'] . ', ';
    $address .= $event['location']['address']['addressCountry'];


    // Create calendar links
    try {
        $link = Link::create($name, $startDate, $endDate);
        $link->description($description);
        $link->address($address);
    } catch (Exception $e) {
        $error = true;
        $message = 'Error : wrong data.';
    }
}

// Display HTML
?><!doctype html>
<html class="no-js" lang="fr">

<head>
    <meta charset="utf-8">
    <title>Add to Calendar</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex"/>

    <style type="text/css">
        :root {
            --color-gris: #333;
            --color-gris-clair: #999;
        }

        body {
            line-height: 1.65;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
            text-align: center;
            margin-top: 2rem;
        }

        .container {
            max-width: 42rem;
            margin-left: auto;
            margin-right: auto;
        }

        header h1 {
            color: var(--color-gris);
        }


        header p,
        footer p,
        footer p a {
            color: var(--color-gris-clair);
        }

        footer p {
            font-size: 0.8rem;
        }

        main {
            min-height: 50vh;
        }

        ul {
            margin: 0;
            padding: 0;
        }

        ul li {

            list-style: none;
        }

        ul li a {
            padding: 1rem 0;
            display: inline-block;
            padding-left: 80px;
            width: calc(100% - 80px);
            text-decoration: none;
            color: black;
            background-repeat: no-repeat;
            background-size: 35px;
            background-position: 20px center;
            margin: 0;
            text-align: left;
            position: relative;
        }

        ul li a:hover {
            background-color: var(--color-gris-clair);
            color: white;
            border-radius: 6px;
        }

        ul li a::before {
            content: "";
            width: 15px;
            height: 15px;
            display: block;
            border-top: 1px solid var(--color-gris);
            border-right: 1px solid var(--color-gris);
            position: absolute;
            top: 22px;
            right: 22px;
            -webkit-transform: translateX(-50%) rotate(45deg);
            transform: translateX(-50%) rotate(45deg);
        }

        ul li a:hover::before {
            border-top-color: white;
            border-right-color: white;
        }


        .apple a {
            background-image: url('images/apple.svg');
        }

        .google a {
            background-image: url('images/google.svg');
        }

        .ics a {
            background-image: url('images/ics.svg');
        }

        .yahoo a {
            background-image: url('images/yahoo.svg');
        }

        .weboutlook a {
            background-image: url('images/outlook.svg');
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #8a6d3b;
            background-color: #fcf8e3;
            border-color: #faebcc;
        }
    </style>
</head>

<body>

<div class="container">

    <header>
        <h1>Add to calendar</h1>
    </header>

    <main>
        <?php if ($error): ?>

            <?= $message ?>

        <?php else: ?>

            <p>🗓️ <?= $name ?></p>

            <?php if ($startDate < new DateTime()): ?>
                <p class="alert">⚠️ This event is in the past.</p>
            <?php endif ?>


            <ul>

                <!--// Generate a data uri for an ics file (for iCal & Outlook)-->
                <li class="apple"><a href="<?= $link->ics() ?>">Apple Calendar</a></li>

                <!-- // Generate a link to create an event on Google calendar-->
                <li class="google"><a href="<?= $link->google() ?>">Google Calendar</a></li>

                <!--// Generate a link to create an event on Yahoo calendar-->
                <li class="yahoo"><a href="<?= $link->yahoo() ?>">Yahoo Calendar</a></li>

                <!--// Generate a link to create an event on outlook.com calendar-->
                <li class="weboutlook"><a href="<?= $link->webOutlook() ?>">Web Outlook</a></li>

                <!--// Generate a data uri for an ics file (for iCal & Outlook)-->
                <li class="ics"><a href="<?= $link->ics() ?>">ICS : iCal & Outlook</a></li>

            </ul>


        <?php endif ?>
    </main>

    <footer>
        <p>Source on <a href="https://github.com/jim005/addto" target="_blank">GitHub</a> for <a
                    href="https://www.websenso.com" target="_blank">www.WebSenso.com</a></p>
    </footer>

</div>

</body>
</html>