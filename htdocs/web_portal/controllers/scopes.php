<?php
require_once __DIR__.'/utils.php';
require_once __DIR__ . '/../../web_portal/components/Get_User_Principle.php';

function show_scopes(){
    //Check the user has permission to see the page, will throw exception
    //if correct permissions are lacking
    checkUserIsAdmin();

    $scopes = \Factory::getScopeService()->getScopes();
    $params['Scopes']= $scopes;



    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    $params['portalIsReadOnly'] = portalIsReadOnlyAndUserIsNotAdmin($user);

    show_view('scopes.php', $params, 'Scopes');
    die();
}
