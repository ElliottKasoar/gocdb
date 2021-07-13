<?php

require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
require_once __DIR__.'/../../components/Get_User_Principle.php';
require_once __DIR__.'/utils.php';

/**
 * Controller for a link identity request.
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function link_identity() {
    //Check the portal is not in read only mode, returns exception if it is
    checkPortalIsNotReadOnly();

    if($_POST) { // If we receive a POST request it's to update a user
        submit();
    } else { // If there is no post data, draw the edit user form
        draw();
    }
}

/**
 * Draws the form
 * @return null
 */
function draw() {
    $idString = Get_User_Principle();
    $authType = Get_User_AuthType();

    if(empty($idString)) {
        show_view('error.php', "Could not authenticate user - null user principle");
        die();
    }

    $serv = \Factory::getUserService();
    $user = $serv->getUserByPrinciple($idString);
    $authTypes = $serv->getAuthTypes();

    if (sizeof($user->getUserProperties()) > 1) {
        $errorMsg = "You cannot recover or link  your identifier to another account while registered with an account"
        . " associated with multiple identifiers."
        . " If you wish to associate your current identifier with another account,"
        . " please unlink all other identifiers first. If you wish to add new identifiers to this account, please "
        . " access GOCDB while authenticated with the new identifer.";
        show_view('error.php', $errorMsg);
        die();
    }

    if(is_null($user)) {
        $params['REGISTERED'] = false;
    } else {
        $params['REGISTERED'] = true;
    }

    $params['IDSTRING'] = $idString;
    $params['CURRENTAUTHTYPE'] = $authType;
    $params['AUTHTYPES'] = $authTypes;

    show_view('user/link_identity.php', $params, 'Link Identity');
}

function submit() {

    // "Primary" account info
    $primaryId = $_REQUEST['PRIMARYID'];
    $givenEmail = $_REQUEST['EMAIL'];
    $primaryAuthType = $_REQUEST['AUTHTYPE'];

    // "Secondary" account info
    $currentId = Get_User_Principle();
    $currentAuthType = Get_User_AuthType();

    if(empty($currentId)){
        show_view('error.php', "Could not authenticate user - null user principle");
        die();
    }

    // Check ID string to be linked is different to current ID string
    if($currentId === $primaryId) {
        show_view('error.php', "The ID string entered must differ to your current ID string");
        die();
    }

    try {
        \Factory::getLinkIdentityService()->newLinkIdentityRequest($currentId, $givenEmail, $primaryId, $primaryAuthType, $currentAuthType);
    } catch(\Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }

    show_view('user/link_identity_accepted.php');
}