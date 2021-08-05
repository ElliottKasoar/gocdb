<?php
/*______________________________________________________
 *======================================================
 * File: edit_user_property.php
 * Author: George Ryall, John Casson, David Meredith, Elliott Kasoar
 * Description: Allows GOCDB Admins to update a user property.
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
    // The following line will be needed if this controller is ever used for non administrators:
    // checkPortalIsNotReadOnlyOrUserIsAdmin($user);

    if ($_POST) { // If we receive a POST request it's to edit a user property
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
    require_once __DIR__ . '/../../../../htdocs/web_portal/components/Get_User_Principle.php';

    // Check the user has permission to see the page
    checkUserIsAdmin();

    // Check user ID is given and is a number
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ) {
        throw new \Exception("A user ID must be specified");
    }

    // Get user details
    $serv = \Factory::getUserService();
    $user = $serv->getUser($_REQUEST['id']);

    // Check user ID is valid
    if (is_null($user)) {
        throw new \Exception("A user with ID '" . $_REQUEST['id'] . "' cannot be found");
    }

    // Can only use property ID if user has properties
    if (count($user->getUserProperties()) !== 0) {

        // Throw exception if property ID not set or invalid
        if (!isset($_REQUEST['propertyId']) || !is_numeric($_REQUEST['propertyId'])) {
            throw new \Exception("A property ID must be specified for this user");
        }

        $property = $serv->getPropertybyId($_REQUEST['propertyId']);

        // Throw exception if not property doesn't exist
        if (is_null($property)) {
            throw new \Exception("A property with ID '" . $_REQUEST['propertyId'] . "' cannot be found");
        }

        $params["propertyId"] = $property->getId();
        $params["idString"] = $property->getKeyValue();
        $params["authType"] = $property->getKeyName();

        // Prevent user editing the identifier they are currently using
        if ($params["idString"] === Get_User_Principle()) {
            throw new \Exception("You cannot edit the identifier you are using");
        }

        // Check property belongs to user
        if ($user !== $serv->getUserByPrinciple($params["idString"])) {
            throw new \Exception("The ID string must belong to the user");
        }

    } else {
        // Use certificate DN
        $params["propertyId"] = null;
        $params["idString"] = $user->getCertificateDn();
        $params["authType"] = null;
    }

    // Will show warning if no user properties
    $dnWarning = false;
    if ($params['propertyId'] === null) {
        $dnWarning = true;
    }

    // Get all valid auth types
    $authTypes = $serv->getAuthTypes(false);

    $params["ID"] = $user->getId();
    $params["Title"] = $user->getTitle();
    $params["Forename"] = $user->getForename();
    $params["Surname"] = $user->getSurname();
    $params["dnWarning"] = $dnWarning;
    $params["authTypes"] = $authTypes;

    // Show the edit user property view
    show_view("admin/edit_user_property.php", $params, "Edit ID string");
}

/**
 * Retrieves the new property from a portal request and submit it to the
 * services layer's user functions.
 * @return null
*/
function submit() {
    require_once __DIR__ . '/../../../../htdocs/web_portal/components/Get_User_Principle.php';

    $serv = \Factory::getUserService();

    // Get the posted data
    $userID = $_REQUEST['ID'];
    $newIdString = $_REQUEST['idString'];
    $propertyId = $_REQUEST['propertyId'];
    $newAuthType = $_REQUEST['authType'];

    $user = $serv->getUser($userID);

    // If property exists, fetch and prepare updated values for edit
    $property = null;
    if ($propertyId !== "") {
        $property = $serv->getPropertyById($propertyId);
    }
    $propArr = array($newAuthType, $newIdString);

    // Get the user data for the edit user property function (so it can check permissions)
    $currentIdString = Get_User_Principle();
    $currentUser = $serv->getUserByPrinciple($currentIdString);

    try {
        // Function will throw error if user does not have the correct permissions
        if ($property !== null) {
            $serv->editUserProperty($user, $property, $propArr, $currentUser);
        } else {
            $serv->migrateUserCredentials($user, $propArr, $currentUser);
        }

        $params = array('Name' => $user->getForename() . " " . $user->getSurname(),
                        'ID' => $user->getId());
        show_view("admin/edited_user_property.php", $params, "Success");
    } catch (\Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }
}

?>