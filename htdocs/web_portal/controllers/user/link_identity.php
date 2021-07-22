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

    if ($_POST) { // If we receive a POST request it's to update a user
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

    if (empty($idString)) {
        show_view('error.php', "Could not authenticate user - null user principle");
        die();
    }

    $serv = \Factory::getUserService();
    $user = $serv->getUserByPrinciple($idString);
    $authTypes = $serv->getAuthTypes();

    if (is_null($user)) {
        $params['REGISTERED'] = false;
    } else {
        $params['REGISTERED'] = true;
    }

    $params['IDSTRING'] = $idString;
    $params['CURRENTAUTHTYPE'] = $authType;
    $params['AUTHTYPES'] = $authTypes;

    // Prevent users with multiple properties from continuing
    if ($user !== null) {
        if (count($user->getUserProperties()) > 1) {
            // Store properties that aren't the one currently in use
            foreach ($user->getUserProperties() as $prop){
                if ($prop->getKeyValue() !== $params['IDSTRING']) {
                    $params['OTHERPROPERTIES'][] = $prop;
                }
            }
            show_view('user/link_identity_rejected.php', $params);
            die();
        }
    }

    show_view('user/link_identity.php', $params, 'Link Identity');
}

function submit() {

    // "Primary" account info entered by the user, corresponding to a registered account
    // This account will have its ID string updated, or an identifier added to it
    $primaryIdString = $_REQUEST['PRIMARYIDSTRING'];
    $givenEmail = $_REQUEST['EMAIL'];
    $primaryAuthType = $_REQUEST['AUTHTYPE'];

    // Current account info, inferred from the in-use authentication
    // There may or may not be a corresponding registered account
    $currentIdString = Get_User_Principle();
    $currentAuthType = Get_User_AuthType();

    if (empty($currentIdString)){
        show_view('error.php', "Could not authenticate user - null user principle");
        die();
    }

    // Check ID string to be linked is different to current ID string
    if ($currentIdString === $primaryIdString) {
        show_view('error.php', "The ID string entered must differ to your current ID string");
        die();
    }

    try {
        \Factory::getLinkIdentityService()->newLinkIdentityRequest($primaryIdString, $currentIdString, $primaryAuthType, $currentAuthType, $givenEmail);
    } catch(\Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }

    $params['IDSTRING'] = $primaryIdString;
    $params['AUTHTYPE'] = $primaryAuthType;
    $params['EMAIL'] = $givenEmail;

    // Recovery or identity linking
    if ($primaryAuthType === $currentAuthType) {
        $params['REQUESTTEXT'] = 'account recovery';
    } else {
        $params['REQUESTTEXT'] = 'identity linking';
    }

    show_view('user/link_identity_accepted.php', $params);
}