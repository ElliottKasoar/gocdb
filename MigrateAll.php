<?php

require __DIR__ . '/lib/Gocdb_Services/Factory.php';

function getEntities($oracle_em, $entityName) {

    $dql = "SELECT x FROM " . $entityName . " x";
    $entities = $oracle_em->createQuery($dql)->getResult();
    return $entities;
}

echo shell_exec('~/switch_to_oracle.sh');
$oracle_em = \Factory::getEntityManager();

echo shell_exec('~/switch_to_maria.sh');
$maria_em = \Factory::getNewEntityManager();

$entityNames = ['Infrastructure', 'Country', 'Tier', 'RoleType', 'CertificationStatus', 'ServiceType', 'Project', 'Scope', 'NGI', 'Site', 'Service', 'EndpointLocation', 'User', 'Role', 'CertificationStatusLog'];

foreach ($entityNames as $entityName) {
    $entitiesList[$entityName] = getEntities($oracle_em, $entityName);
}

$maria_em->getConnection()->beginTransaction();

foreach ($entitiesList['NGI'] as $NGI) {
    foreach ($NGI->getScopes() as $scope) {
        $maria_em->persist($NGI);
        $maria_em->persist($scope);
        $NGI->addScope($scope);
    }
}

foreach ($entitiesList['Project'] as $project) {
    foreach ($project->getNgis() as $Ngi){
        $maria_em->persist($Ngi);
        $maria_em->persist($project);
        $project->addNgi($Ngi);
    }
}

foreach ($entitiesList['Site'] as $site) {
    foreach ($site->getScopes() as $scope) {
        $site->addScope($scope);
    }
}

foreach ($entitiesList as $entities) {
    foreach ($entities as $entity) {
        $maria_em->persist($entity);
    }
    $maria_em->flush();
}

$maria_em->getConnection()->commit();