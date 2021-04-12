<?php

require_once __DIR__ . '/lib/Gocdb_Services/Factory.php';

$em = \Factory::getEntityManager();

function getScopes($em) {

    $dql = "SELECT sc FROM Scope sc";
    $scopes = $em->createQuery($dql)->getResult();
    return $scopes;
}

function saveScopesXML($em) {

    $scopes = getScopes($em);
    $xml = new \SimpleXMLElement("<results />");
    foreach($scopes as $scope) {
        $xmlScope = $xml->addChild('scope');
        $xmlScope->addAttribute('id', $scope->getId());

        $xmlId = $xmlScope->addChild('id', $scope->getId());

        $xmlName = $xmlScope->addChild('name', $scope->getName());
        $xmlName->addAttribute('key', 'primary');

        $xmlDescription = $xmlScope->addChild('description', htmlspecialchars($scope->getDescription()));
    }

    $dom_sxe = dom_import_simplexml($xml);
    $dom = new \DOMDocument('1.0');
    $dom->encoding='UTF-8';
    $dom_sxe = $dom->importNode($dom_sxe, true);
    $dom_sxe = $dom->appendChild($dom_sxe);
    $dom->formatOutput = true;
    $dom->save('lib/Doctrine/deployProd/data/scopes.xml');
}

saveScopesXML($em);

function getScopesFromXML() {
    $scopesFileName = "lib/Doctrine/deployProd/data/scopes.xml";
    $scopes = simplexml_load_file($scopesFileName);
    foreach($scopes as $scope) {
        $name = "";
        $id = "";
        $desc = "";
        foreach($scope as $key => $value) {

            if($key == "id") {
                $id = $value;
                echo "Id: $id \n";
            }

            if($key == "name") {
                $name = (string) $value;
                echo "Name: $name \n";
            }

            if($key == "description") {
                $desc = (string) $value;
                echo "Description: $desc \n";
            }
        }
    }
}

#getScopesFromXML();

