<?php
/*______________________________________________________
 *======================================================
 * File: edit_user_property.php
 * Author: George Ryall, John Casson, David Meredith
 * Description: Allows GOCD Admins to update a user's id.
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
require_once __DIR__ . '/../utils.php';

/**
 * Controller for an edit_user_property request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function edit_property() {
    //The following line will be needed if this controller is ever used for non administrators:
    //checkPortalIsNotReadOnlyOrUserIsAdmin($user);

    if($_POST) {     // If we receive a POST request it's to edit a user property
        submit();
    } else { // If there is no post data, draw the edit property page
        draw();
    }
}

/**
 * Draws the edit user property form
 * @return null
 */
function draw() {
    //Check the user has permission to see the page, will throw exception
    //if correct permissions are lacking
    checkUserIsAdmin();
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ) {
        throw new Exception("An id must be specified");
    }
    //Get user details
    $serv = \Factory::getUserService();
    $user = $serv->getUser($_REQUEST['id']);

    //Throw exception if not a valid user id
    if(is_null($user)) {
        throw new \Exception("A user with ID '".$_REQUEST['id']."' Can not be found");
    }

    // Get property
    $property = $serv->getProperty($_REQUEST['propertyId']);
    $params["IdString"] = $property->getKeyValue();

    $params["ID"] = $user->getId();
    $params["Title"] = $user->getTitle();
    $params["Forename"] = $user->getForename();
    $params["Surname"] = $user->getSurname();
    // $params["IdString"] = $user->getCertificateDn();

    //show the edit user property view
    show_view("admin/edit_user_property.php", $params, "Edit ID string");
}

/**
 * Retrieves the new property from a portal request and submit it to the
 * services layer's user functions.
 * @return null
*/
function submit() {
    require_once __DIR__ . '/../../../../htdocs/web_portal/components/Get_User_Principle.php';

    //Get a user service
    $serv = \Factory::getUserService();

    //Get the posted service type data
   $userID = $_REQUEST['ID'];
   $newIdString = $_REQUEST['IdString'];
   $user = $serv->getUser($userID);

    //get the user data for the edit user property function (so it can check permissions)
    $currentIdString = Get_User_Principle();
    $currentUser = $serv->getUserByPrinciple($currentIdString);

    try {
        //function will through error if user does not have the correct permissions
        $serv->editUserDN($user, $newIdString, $currentUser);

        $params = array('Name' => $user->getForename() . " " . $user->getSurname(),
                        'ID' => $user->getId());
        show_view("admin/edited_user_property.php", $params, "Success");
    } catch (Exception $e) {
         show_view('error.php', $e->getMessage());
         die();
    }
}

?>