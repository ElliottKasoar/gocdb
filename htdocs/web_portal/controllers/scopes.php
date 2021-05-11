<?php
require_once __DIR__.'/utils.php';
require_once __DIR__ . '/../../web_portal/components/Get_User_Principle.php';

function show_scopes() {

    $scopes = \Factory::getScopeService()->getScopes();
    $params['Scopes'] = $scopes;

    $idString = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($idString);
    $params['portalIsReadOnly'] = portalIsReadOnlyAndUserIsNotAdmin($user);

    $params['UserIsAdmin'] = false;
    if(!is_null($user)) {
        $params['UserIsAdmin'] = $user->isAdmin();
    }

    $optionalScopes = \Factory::getScopeService()->getScopesFilterByParams(
                    array('excludeReserved' => true), null);
    $reservedScopes = \Factory::getScopeService()->getScopesFilterByParams(
                    array('excludeNonReserved' => true), null);

    $params['optionalScopes'] = $optionalScopes;
    $params['reservedScopes'] = $reservedScopes;

    show_view('scopes.php', $params, 'Scopes');
    die();
}
