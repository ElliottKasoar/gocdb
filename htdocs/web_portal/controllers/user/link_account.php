<?php

require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
require_once __DIR__.'/../../components/Get_User_Principle.php';
require_once __DIR__.'/utils.php';

/**
 * Controller for a link account request.
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function link_account() {
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
    $id = Get_User_Principle();
    $authType = Get_User_AuthType();
    if(empty($id)){
        show_view('error.php', "Could not authenticate user - null user principle");
        die();
    }
    $user = \Factory::getUserService()->getUserByPrinciple($id);

    // if(is_null($user)) {
    //     show_view('error.php', "Only registered users can link an account.");
    //     die();
    // }

    $params['IDSTRING'] = $id;
    $params['CURRENTAUTHTYPE'] = $authType;
    $params['AUTHTYPES'] = ['IGTF', 'IRIS IAM - OIDC', 'FAKE'];

    show_view('user/link_account.php', $params, 'Link Account');
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

    // Check id string of account to be linked is different to current id
    if($currentId === $primaryId) {
        show_view('error.php', "The id string entered must differ to your current id string");
        die();
    }

    try {
        $linkReq = \Factory::getLinkAccountService()->newLinkAccountRequest($currentId, $givenEmail, $primaryId, $primaryAuthType, $currentAuthType);
    } catch(\Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }
// secondary, email, promary. auth
    show_view('user/link_account_accepted.php');
}