<?php

require_once dirname(__FILE__) . "/../lib/Doctrine/bootstrap.php";
require dirname(__FILE__) . '/../lib/Doctrine/bootstrap_doctrine.php';
require_once dirname(__FILE__) . '/../lib/Gocdb_Services/Factory.php';

echo "Querying identity linking and account recovery requests\n\n";

$em = $entityManager;
$dql = "SELECT l FROM LinkIdentityRequest l";
$requests = $entityManager->createQuery($dql)->getResult();

$nowUtc = new \DateTime(null, new \DateTimeZone('UTC'));

echo "Starting scan of request creation dates at: " . $nowUtc->format('D, d M Y H:i:s') . "\n\n";
foreach ($requests as $request) {
   
    $creationDate = $request->getCreationDate()->setTimezone(new \DateTimeZone('UTC'));

    // Will want one day? Currently 30 min
    $oneDay = \DateInterval::createFromDateString('30 minutes');
    $yesterdayUtc = $nowUtc->sub($oneDay);

    if ($yesterdayUtc > $creationDate) {
        echo "yesterdayUtc is greater than creationDate\n";
        echo "Yesterday time: " . $yesterdayUtc->format('D, d M Y H:i:s') . "\n";
        echo "Request " . $request->getId() . " (creation date: " . $creationDate->format('D, d M Y H:i:s') . ")\n\n";
    }

    $em->persist($request);
}

$em->flush();

$nowUtc = new \DateTime(null, new \DateTimeZone('UTC'));
echo "Completed ok: " . $nowUtc->format('D, d M Y H:i:s');