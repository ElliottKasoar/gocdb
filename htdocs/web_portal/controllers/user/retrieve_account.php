<?php
/*______________________________________________________
 *======================================================
 * File: retrieve_account.php.php
 * Author: John Casson, David Meredith
 * Description: Retrieves a user account
 *
 * License information
 *
 * Copyright 2009 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 /*======================================================*/
require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
require_once __DIR__.'/../../components/Get_User_Principle.php';
require_once __DIR__.'/utils.php';

/**
 * Controller for a retrieve account request.
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function retrieve() {
    //Check the portal is not in read only mode, returns exception if it is
    checkPortalIsNotReadOnly();

    if($_POST) { // If we receive a POST request it's to update a user
        submit();
    } else { // If there is no post data, draw the edit user form
        draw();
    }
}

/**
 * Draws the register user form
 * @return null
 */
function draw() {
    $id = Get_User_Principle();
    if(empty($id)){
        show_view('error.php', "Could not authenticate user - null user principle");
        die();
    }

    $params['DN'] = $id;

    show_view('user/retrieve_account.php', $params, 'Retrieve Account');
}

function submit() {
    $oldId = $_REQUEST['OLDID'];
    $givenEmail =$_REQUEST['EMAIL'];
    $currentId = Get_User_Principle();
    $authType = Get_User_AuthType();
    $requestType = 'recover';

    if(empty($currentId)){
        show_view('error.php', "Could not authenticate user - null user principle");
        die();
    }

    // Check id string of account to be linked is different to current id
    if($currentId === $oldId) {
        show_view('error.php', "The id string entered must differ to your current id string");
        die();
    }

    try {
        $changeReq = \Factory::getLinkAccountService()->newLinkAccountRequest($currentId, $givenEmail, $oldId, $authType, $requestType);
    } catch(\Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }

    show_view('user/retrieve_account_accepted.php');
}