<?php
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../../web_portal/components/Get_User_Principle.php';

function view_scope() {

    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ) {
        throw new \Exception("An id must be specified");
    }
    $idString = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($idString);

    $params['UserIsAdmin'] = false;
    if(!is_null($user)) {
        $params['UserIsAdmin'] = $user->isAdmin();
    }

    $serv = \Factory::getScopeService();
    $scope = $serv ->getScope($_REQUEST['id']);

    $params['Name'] = $scope->getName();
    $params['Description'] = $scope->getDescription();
    $params['ID'] = $scope->getId();
    $params['NGIs'] = $serv->getNgisFromScope($scope);
    $params['Sites'] = $serv->getSitesFromScope($scope);
    $params['ServiceGroups'] = $serv->getServiceGroupsFromScope($scope);
    $params['Services'] = $serv->getServicesFromScope($scope);
    $params['portalIsReadOnly'] = portalIsReadOnlyAndUserIsNotAdmin($user);

    show_view("scope.php", $params, $params['Name']);
    die();
}
