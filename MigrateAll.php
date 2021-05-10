<?php

require __DIR__ . '/lib/Gocdb_Services/Factory.php';

function addEntities() {

    shell_exec('~/switch_to_oracle.sh');
    $em = \Factory::getEntityManager();

    $dql_i = "SELECT i FROM Infrastructure i";
    $infrastructures = $em->createQuery($dql_i)->getResult();

    $dql_cn = "SELECT cn FROM Country cn";
    $countries = $em->createQuery($dql_cn)->getResult();

    $dql_t = "SELECT t FROM Tier t";
    $tiers = $em->createQuery($dql_t)->getResult();

    $dql_rt = "SELECT rt FROM  RoleType rt";
    $roletypes = $em->createQuery($dql_rt)->getResult();

    $dql_cs = "SELECT cs FROM CertificationStatus cs";
    $statuses = $em->createQuery($dql_cs)->getResult();

    $dql_st= "SELECT st FROM ServiceType st";
    $servicetypes = $em->createQuery($dql_st)->getResult();

    $dql_p= "SELECT p FROM Project p";
    $projects = $em->createQuery($dql_p)->getResult();

    $dql_sc = "SELECT sc FROM Scope sc";
    $scopes = $em->createQuery($dql_sc)->getResult();

    $dql_n = "SELECT n FROM NGI n";
    $NGIs = $em->createQuery($dql_n)->getResult();

    $dql_c = "SELECT c FROM CertificationStatusLog c";
    $logs = $em->createQuery($dql_c)->getResult();

    $dql_s = "SELECT s FROM Site s";
    $sites = $em->createQuery($dql_s)->getResult();

    $dql_se = "SELECT se FROM Service se";
    $services = $em->createQuery($dql_se)->getResult();

    $dql_e = "SELECT e FROM EndpointLocation e";
    $endpoints = $em->createQuery($dql_e)->getResult();

    $dql_u = "SELECT u FROM User u";
    $users = $em->createQuery($dql_u)->getResult();

    $dql_r = "SELECT r FROM Role r";
    $roles = $em->createQuery($dql_r)->getResult();

    $entitiesArr = [$infrastructures, $countries, $tiers, $roletypes, $statuses, $servicetypes, $projects, $scopes, $NGIs, $sites, $services, $endpoints, $users, $roles, $logs];

    echo shell_exec('~/switch_to_maria.sh');
    $em = \Factory::getNewEntityManager();

    foreach ($NGIs as $NGI) {
        echo $NGI->getScopeNamesAsString() . "\n";
    }

    foreach ($NGIs as $NGI) {
        foreach ($NGI->getScopes() as $scope) {
            $em->persist($NGI);
            $em->persist($scope);
            $NGI->addScope($scope);
        }
    }

    foreach ($projects as $project) {
        foreach ($project->getNgis() as $Ngi){
            $em->persist($Ngi);
            $em->persist($project);
            $project->addNgi($Ngi);
        }
    }

    foreach ($sites as $site) {
        foreach ($site->getScopes() as $scope) {
            $site->addScope($scope);
        }
    }

    $em->getConnection()->beginTransaction();
    foreach ($entitiesArr as $entities) {
        foreach ($entities as $entity) {
            $em->persist($entity);
        }
        $em->flush();
    }

    //$ee->flush();
    $em->getConnection()->commit();

    $em = \Factory::getNewEntityManager();
    $dql_n = "SELECT n FROM NGI n";
    $NGIs = $em->createQuery($dql_n)->getResult();

    echo "TEST \n";
    foreach ($NGIs as $NGI){
        echo $NGI->getScopeNamesAsString() . "\n";
        foreach ($NGI->getProjects() as $project){
            echo $project->getName() . "\n";
        }
    }
}

addEntities();
