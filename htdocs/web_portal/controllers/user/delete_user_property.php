<?php
/*______________________________________________________
 *======================================================
 * File: delete_user_property.php
 * Author: Elliott Kasoar
 * Description: Removes a user's property
 *
 * License information
 *
 * Copyright  2021 STFC
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
require_once __DIR__ . '/../utils.php';

/**
 * Controller for a user property removal request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function delete_property() {

    $serv = \Factory::getUserService();

    $currentIdString = Get_User_Principle();
    $currentUser = $serv->getUserByPrinciple($currentIdString);

    if($currentUser === null) {
        throw new \Exception("Unregistered users can't edit users");
    }

    // Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($currentUser);

    // Get the posted data
    $userId = $_REQUEST['id'];
    $propertyId = $_REQUEST['propertyId'];

    $user = $serv->getUser($userId);
    $property = $serv->getProperty($propertyId);

    // Throw exception if not a valid user id
    if(is_null($user)) {
        throw new \Exception("A user with ID '" . $userId . "' cannot be found");
    }

    $serv->editUserAuthorization($user, $currentUser);

    // Throw exception if not a valid property id
    // Non-admins can't tell if a given property matches a specific user
    // But they can currently still tell how many properties exist
    // This could be changed to only give info about if the property matches one of theirs
    if(is_null($property)) {
        throw new \Exception("A property with ID '" . $propertyId . "' cannot be found");
    }

    // Throw exception if trying to remove property that current user is authenticated with
    if($property->getKeyValue() === $currentIdString) {
        throw new \Exception("You cannot unlink your current ID string. Please log in using a different authentication mechanism and try again.");
    }

    $params = array('ID' => $user->getId());

    try {
        // Function will throw error if user does not have the correct permissions
        $serv->deleteUserProperty($user, $property, $currentUser);
        show_view("user/deleted_user_property.php", $params);
    } catch (Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }
}