<?php
namespace org\gocdb\services;
/* Copyright ? 2011 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
require_once __DIR__ . '/AbstractEntityService.php';
require_once __DIR__ . '/../Doctrine/entities/User.php';

/**
 * GOCDB Stateless service facade (business routnes) for user objects.
 * The public API methods are transactional.
 *
 * @author John Casson
 * @author David Meredith
 * @author George Ryall
 */
class User extends AbstractEntityService{

    /*
     * All the public service methods in a service facade are typically atomic -
     * they demarcate the tx boundary at the start and end of the method
     * (getConnection/commit/rollback). A service facade should not be too 'chatty,'
     * ie where the client is required to make multiple calls to the service in
     * order to fetch/update/delete data. Inevitably, this usually means having
     * to refactor the service facade as the business requirements evolve.
     *
     * If the tx needs to be propagated across different service methods,
     * consider refactoring those calls into a new transactional service method.
     * Note, we can always call out to private helper methods to build up a
     * 'composite' service method. In doing so, we must access the same DB
     * connection (thus maintaining the atomicity of the service method).
     */

    /**
     * Gets a user object from the DB
     * @param $id User ID
     * @return User object
     */
    public function getUser($id) {
        return $this->em->find("User", $id);
    }

    /**
     * Lookup a User object by user's id string, stored in certificateDn.
     * @param string $userPrinciple the user's principle id string, e.g. DN.
     * @return User object or null if no user can be found with the specified principle
     */
    public function getUserFromDn($userPrinciple) {
        if(empty($userPrinciple)) {
            return null;
        }
        $dql = "SELECT u from User u WHERE u.certificateDn = :certDn";
        $user = $this->em->createQuery($dql)
                  ->setParameter(":certDn", $userPrinciple)
                  ->getOneOrNullResult();
        return $user;
    }

    /**
     * Lookup a User object by user's principle id string from UserProperty.
     * @param string $userPrinciple the user's principle id string, e.g. DN.
     * @return User object or null if no user can be found with the specified principle
     */
    public function getUserByPrinciple($userPrinciple) {
        if(empty($userPrinciple)) {
            return null;
        }

        $dql = "SELECT u FROM User u JOIN u.userProperties up WHERE up.keyValue = :keyValue";
        $user = $this->em->createQuery($dql)
                  ->setParameters(array('keyValue' => $userPrinciple))
                  ->getOneOrNullResult();

        return $user;
    }

    /**
     * Lookup a User object by user's principle id string and auth type from UserProperty.
     * @param string $userPrinciple the user's principle id string, e.g. DN.
     * @param string $authType the authorisation type e.g. IGTF X509 Cert.
     * @return User object or null if no user can be found with the specified principle
     */
    public function getUserByPrincipleAndType($userPrinciple, $authType) {
        if(empty($userPrinciple) || empty($authType)) {
            return null;
        }

        $dql = "SELECT u FROM User u JOIN u.userProperties up
                WHERE up.keyName = :keyName
                AND up.keyValue = :keyValue";
        $user = $this->em->createQuery($dql)
                  ->setParameters(array('keyName' => $authType, 'keyValue' => $userPrinciple))
                  ->getOneOrNullResult();

        return $user;
    }

    /**
     * Updates the users last login time to the current time in UTC.
     * @param \User $user
     */
    public function updateLastLoginTime(\User $user){
        $nowUtc = new \DateTime(null, new \DateTimeZone('UTC'));
        $this->em->getConnection()->beginTransaction();
        try {
            // Set the user's member variables
            $user->setLastLoginDate($nowUtc);
            $this->em->merge($user);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $ex) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $ex;
        }
        return $user;
    }

    /**
     * Find sites a user has a role over with the specified role status.
     * @param \User $user The user
     * @param string $roleStatus Optional role status string @see \RoleStatus (default is GRANTED)
     * @return array of \Site objects or emtpy array
     */
    public function getSitesFromRoles(\User $user, $roleStatus = \RoleStatus::GRANTED) {
        $dql = "SELECT r FROM Role r
                INNER JOIN r.user u
                INNER JOIN r.ownedEntity o
                WHERE u.id = :id
                AND o INSTANCE OF Site
                AND r.status = :status";
        $roles = $this->em->createQuery($dql)
                    ->setParameter(":id", $user->getId())
                    ->setParameter(":status", $roleStatus)
                    ->getResult();
        $sites = array();

        foreach($roles as $role) {
            // Check whether this site is already in the list
            // (A user can hold more than one role over an entity)
            foreach($sites as $site) {
                if($site == $role->getOwnedEntity()) {
                    continue 2;
                }
            }
            $sites[] = $role->getOwnedEntity();
        }

        return $sites;
    }

    /**
     * Find NGIs a user has a role over with the specified role status.
     * @param \User $user
     * @param string $roleStatus Optional role status string @see \RoleStatus (default is GRANTED)
     * @return array of \NGI objects or empty array
     */
    public function getNgisFromRoles(\User $user, $roleStatus = \RoleStatus::GRANTED) {
        $dql = "SELECT r FROM Role r
                INNER JOIN r.user u
                INNER JOIN r.ownedEntity o
                WHERE u.id = :id
                AND o INSTANCE OF NGI
                AND r.status = :status";
        $roles = $this->em->createQuery($dql)
                    ->setParameter(":id", $user->getId())
                    ->setParameter(":status", $roleStatus)
                    ->getResult();
        $ngis = array();
        foreach($roles as $role) {
            // Check whether this site is already in the list
            // (A user can hold more than one role over an entity)
            foreach($ngis as $ngi) {
                if($ngi == $role->getOwnedEntity()) {
                    continue 2;
                }
            }
            $ngis[] = $role->getOwnedEntity();
        }

        return $ngis;
    }

    /**
     * Find service groups a user has a role over with the specified role status.
     * @param \User $user
     * @param string $roleStatus Optional role status string @see \RoleStatus (default is GRANTED)
     * @return array of \ServiceGroup objects or empty array
     */
    public function getSGroupsFromRoles(\User $user, $roleStatus = \RoleStatus::GRANTED) {
        $dql = "SELECT r FROM Role r
                INNER JOIN r.user u
                INNER JOIN r.ownedEntity o
                WHERE u.id = :id
                AND o INSTANCE OF ServiceGroup
                AND r.status = :status";
        $roles = $this->em->createQuery($dql)
                    ->setParameter(":id", $user->getId())
                    ->setParameter(":status", $roleStatus)
                    ->getResult();
        $sGroups = array();

        foreach($roles as $role) {
            // Check whether this site is already in the list
            // (A user can hold more than one role over an entity)
            foreach($sGroups as $sGroup) {
                if($sGroup == $role->getOwnedEntity()) {
                    continue 2;
                }
            }
            $sGroups[] = $role->getOwnedEntity();
        }

        return $sGroups;
    }

    /**
     * Find Projects a user has a role over with the specified role status.
     * @param \User $user
     * @param string $roleStatus Optional role status string @see \RoleStatus (default is GRANTED)
     * @return array of \Project objects or empty array
     */
    public function getProjectsFromRoles(\User $user, $roleStatus = \RoleStatus::GRANTED) {
        $dql = "SELECT r FROM Role r
                INNER JOIN r.user u
                INNER JOIN r.ownedEntity o
                WHERE u.id = :id
                AND o INSTANCE OF Project
                AND r.status = :status";
        $roles = $this->em->createQuery($dql)
                    ->setParameter(":id", $user->getId())
                    ->setParameter(":status", $roleStatus)
                    ->getResult();
        $projects = array();

        foreach($roles as $role) {
            // Check whether this site is already in the list
            // (A user can hold more than one role over an entity)
            foreach($projects as $project) {
                if($project == $role->getOwnedEntity()) {
                    continue 2;
                }
            }
            $projects[] = $role->getOwnedEntity();
        }

        return $projects;
    }

    /**
     * Updates a User
     * Returns the updated user
     *
     * Accepts an array $newValues as a parameter. $newVales' format is as follows:
     * <pre>
     *  Array
     *  (
     *      [TITLE] => Mr
     *      [FORENAME] => Will
     *      [SURNAME] => Rogers
     *      [EMAIL] => WAHRogers@STFC.ac.uk
     *      [TELEPHONE] => 01235 44 5011
     *  )
     * </pre>
     * @param User The User to update
     * @param array $newValues Array of updated user data, specified above.
     * @param User The current user
     * return User The updated user entity
     */
    public function editUser(\User $user, $newValues, \User $currentUser = null) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($currentUser);

        // Check to see whether the current user can edit this user
        $this->editUserAuthorization($user, $currentUser);

        // validate the input fields for the user
        $this->validate($newValues, 'user');

        //Explicity demarcate our tx boundary
        $this->em->getConnection()->beginTransaction();

        try {
            // Set the user's member variables
            $user->setTitle($newValues['TITLE']);
            $user->setForename($newValues['FORENAME']);
            $user->setSurname($newValues['SURNAME']);
            $user->setEmail($newValues['EMAIL']);
            $user->setTelephone($newValues['TELEPHONE']);
            $this->em->merge($user);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $ex) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $ex;
        }
        return $user;
    }


    /**
     * Check to see if the current user has permission to edit a user entity
     * @param org\gocdb\services\User $user The user being edited or deleted
     * @param User $currentUser The current user
     * @throws \Exception If the user isn't authorised
     * @return null
     */
    public function editUserAuthorization(\User $user, \User $currentUser = null) {
        if(is_null($currentUser)){
            throw new \Exception("unregistered users may not edit users");
        }

        if($currentUser->isAdmin()) {
            return;
        }
        // Allow the current user to edit their own info
        if($currentUser == $user) {
            return;
        }
        throw new \Exception("You do not have permission to edit this user.");
    }

    /**
     * Validates the user inputted user data against the
     * checks in the gocdb_schema.xml.
     * @param array $user_data containing all the fields for a GOCDB_USER
     *                       object
     * @throws \Exception If the site data can't be
     *                   validated. The \Exception message will contain a human
     *                   readable description of which field failed validation.
     * @return null */
    private function validate($userData, $type) {
        require_once __DIR__ .'/Validate.php';
        $serv = new \org\gocdb\services\Validate();
        foreach($userData as $field => $value) {
            $valid = $serv->validate($type, $field, $value);
            if(!$valid) {
                $error = "$field contains an invalid value: $value";
                throw new \Exception($error);
            }
        }
    }

    /**
     * Array
     * (
     *     [TITLE] => Mr
     *     [FORENAME] => Testing
     *     [SURNAME] => TestFace
     *     [EMAIL] => JCasson@gmail.com
     *     [TELEPHONE] => 01235 44 5010
     * )
     * Array
     * (
     *     [NAME] => Testing
     *     [VALUE] => /C=UK/O=eScience/OU=CLRC/L=RAL/CN=claire devereuxxxx
     * )
     * @param array $userValues User details, defined above
     * @param array $userPropertyValues User Property details, defined above
     */
    public function register($userValues, $userPropertyValues) {
        // validate the input fields for the user
        $this->validate($userValues, 'user');

        //Explicity demarcate our tx boundary
        $this->em->getConnection()->beginTransaction();
        $user = new \User();
        $propArr = array($userPropertyValues['NAME'], $userPropertyValues['VALUE']);
        $serv = \Factory::getUserService();
        try {
            $user->setTitle($userValues['TITLE']);
            $user->setForename($userValues['FORENAME']);
            $user->setSurname($userValues['SURNAME']);
            $user->setEmail($userValues['EMAIL']);
            $user->setTelephone($userValues['TELEPHONE']);
            $user->setAdmin(false);
            $this->em->persist($user);
            $this->em->flush();
            $serv->addUserProperty($user, $propArr, $user);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
        return $user;
    }

    /**
     * Deletes a user
     * @param \User $user To be deleted
     * @param \User $currentUser Making the request
     * @throws \Exception If user can't be authorized */
    public function deleteUser(\User $user, \User $currentUser = null) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($currentUser);

        $this->editUserAuthorization($user, $currentUser);
        $this->em->getConnection()->beginTransaction();
        try {
            $this->em->remove($user);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * Returns all users in GOCDB or those matching optional criteria note
     * forename and surname are handled case insensitivly
     * @param string $surname surname of users to be returned (matched case insensitivly)
     * @param string $forename forename of users to be returned (matched case insensitivly)
     * @param string $idString ID string of user to be returned. If specified only one user will be returned. Matched case sensitivly
     * @param mixed $isAdmin if null then admin status is ignored, if true only admin users are returned and if false only non admins
     * @return array An array of site objects
     */
    public function getUsers($surname=null, $forename=null, $idString=null, $isAdmin=null) {

        $dql =
            "SELECT u FROM User u LEFT JOIN u.userProperties up
             WHERE (UPPER(u.surname) LIKE UPPER(:surname) OR :surname is null)
             AND (UPPER(u.forename) LIKE UPPER(:forename) OR :forename is null)
             AND (u.certificateDn LIKE :idString OR up.keyValue LIKE :idString OR :idString is null)
             AND (u.isAdmin = :isAdmin OR :isAdmin is null)
             ORDER BY u.surname";

        $users = $this->em
            ->createQuery($dql)
            ->setParameter(":surname", $surname)
            ->setParameter(":forename", $forename)
            ->setParameter(":idString", $idString)
            ->setParameter(":isAdmin", $isAdmin)
            ->getResult();

        return $users;
    }

    /**
     * Returns a single user property from its ID
     * @param $id ID of user property
     * @return \UserProperty
     */
    public function getProperty($id) {
        $dql = "SELECT p FROM UserProperty p WHERE p.id = :ID";
        $property = $this->em->createQuery($dql)->setParameter('ID', $id)->getOneOrNullResult();
        return $property;
    }

    /**
     * Returns list of authentication types
     * List composed of AuthenticationRealms defined within tokens
     * Order of tokens determined by order listed in MyConfig1
     * Order of realms hardcoded based on order within tokens
     * @param bool $reducedRealms if true only return the "main" authentication types
     * @return array of authentication types
     */
    public function getAuthTypes($reducedRealms=true) {

        require_once __DIR__ . '/../Authentication/_autoload.php';
        // Get list of tokens in order they are currently used
        $myConfig1 = new \org\gocdb\security\authentication\MyConfig1();
        $authTokenNames = $myConfig1->getAuthTokenClassList();

        // Hardcoded auth realms in same order as in token definitions
        $x509Realms = ['IGTF X509 Cert'];
        if ($reducedRealms) {
            $shibRealms = ['EGI Proxy IdP'];
        } else {
            $shibRealms = ['EUDAT_SSO_IDP', 'UK_ACCESS_FED', 'EGI Proxy IdP'];
        }

        // Add auth types to a list in the correct order
        $authTypes = array();
        foreach ($authTokenNames as $authTokenName) {
            if (strpos($authTokenName, 'Shib') !== false) {
                $authTypes = array_merge($authTypes, $shibRealms);
            }
            if (strpos($authTokenName, 'X509') !== false) {
                $authTypes = array_merge($authTypes, $x509Realms);
            }
        }
        // Remove after testing
        $authTypes[] = 'FAKE';
        return $authTypes;
    }

    /**
     * Get one of the user's unique ID strings, favouring certain types
     * If user does not have user properties, returns certificateDn
     * @param \User $user User whose ID string we want
     * @return string
     */
    public function getIdString($user) {

        $authTypes = $this->getAuthTypes();
        $idString = null;

        // For each ordered auth type, check if a property matches
        // Gets certifcateDn if no user properties and IGTF X509 Cert listed
        foreach ($authTypes as $authType) {
            $idString = $this->getIdStringByAuthType($user, $authType);
            if ($idString !== null) {
                break;
            }
        }

        // If user only has unlisted properties, return first property
        if ($idString === null) {
            $idString = $user->getUserProperties()[0]->getKeyValue();
        }

        return $idString;
    }

    /**
     * Get a user's ID string of specified authentication type
     * If user does not have user properties, returns certificateDn for IGTF X509 Cert
     * @param \User $user User whose ID string we want
     * @param $authType authentication type of ID string we want
     * @return string
     */
    public function getIdStringByAuthType($user, $authType) {

        $props = $user->getUserProperties();
        $idString = null;

        // For each auth type, check if a property matches
        foreach ($props as $prop) {
            if ($prop->getKeyName() === $authType) {
                $idString = $prop->getKeyValue();
            }
        }

        // If no user properties and want IGTF X509 Cert, return certificateDn
        if (sizeof($props) === 0 && $authType === 'IGTF X509 Cert') {
            $idString = $user->getCertificateDn();
        }

        return $idString;
    }

    /**
     * Adds an extension property key/value pair to a user.
     * @param \User $user
     * @param array $propArr
     * @param \User $currentUser
     * @param bool $preventOverwrite
     * @throws \Exception
     */
    public function addUserProperty(\User $user, array $propArr, \User $currentUser, $preventOverwrite=true) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        // Check to see whether the current user can edit this user
        $this->editUserAuthorization($user, $currentUser);

        //Add the properties
        $this->em->getConnection()->beginTransaction();
        try {
            $this->addUserPropertyLogic($user, $propArr, $preventOverwrite);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * Logic to add an extension property to a user.
     * @param \User $user
     * @param array $propArr
     * @param bool $preventOverwrite
     * @throws \Exception
     */
    protected function addUserPropertyLogic(\User $user, array $propArr, $preventOverwrite=true) {

        // We will use this variable to track the keys as we go along, this will be used check they are all unique later
        $keys = array();

        $existingProperties = $user->getUserProperties();

        // We will use this variable to track the final number of properties and ensure we do not exceede the specified limit
        $propertyCount = sizeof($existingProperties);

        // Trim off any trailing and leading whitespace
        $keyName = trim($propArr[0]);
        $keyValue = trim($propArr[1]);

        $this->addUserPropertyValidation($user, $keyName, $keyValue, $preventOverwrite);

        /* Find out if a property with the provided key already exists for this user.
        * If we are preventing overwrites, this will be a problem. If we are not,
        * we will want to edit the existing property later, rather than create it.
        */
        $property = null;
        foreach ($existingProperties as $existProp) {
            if ($existProp->getKeyName() === $keyName) {
                $property = $existProp;
            }
        }

        /* If the property doesn't already exist, we add it. If it exists
        * and we are not preventing overwrites, we edit the existing one.
        * If it exists and we are preventing overwrites, we throw an exception
        */
        if (is_null($property)) {
            $property = new \UserProperty();
            $property->setKeyName($keyName);
            $property->setKeyValue($keyValue);
            $user->addUserPropertyDoJoin($property);
            $this->em->persist($property);

            // Increment the property counter to enable check against property limit
            $propertyCount++;
        } elseif (!$preventOverwrite) {
            $this->editUserPropertyLogic($user, $property, array('USERPROPERTIES'=>array('NAME'=>$keyName,'VALUE'=>$keyValue)));
        } else {
            throw new \Exception("A property with name \"$keyName\" already exists for this object, no properties were added.");
        }

        // Add the key to the keys array, to enable unique check
        $keys[] = $keyName;

        // Keys should be unique, create an exception if they are not
        if(count(array_unique($keys)) !== count($keys)) {
            throw new \Exception(
                "Property names should be unique. The requested new properties include multiple properties with the same name."
            );
        }

        // Check to see if adding the new properties will exceed the max limit defined in local_info.xml, and throw an exception if so
        $extensionLimit = \Factory::getConfigService()->getExtensionsLimit();
        if ($propertyCount > $extensionLimit) {
            throw new \Exception("Property(s) could not be added due to the property limit of $extensionLimit");
        }
    }

    /**
     * Validation when adding a user property
     * @param \User $user
     * @param string $keyName
     * @param string $keyValue
     * @param bool $preventOverwrite
     * @throws \Exception
     */
    protected function addUserPropertyValidation($user, $keyName, $keyValue, $preventOverwrite) {
        // Validate against schema
        $validateArray['NAME'] = $keyName;
        $validateArray['VALUE'] = $keyValue;
        $this->validate($validateArray, 'userproperty');

        // If certificateDn is not yet null:
        // 1. Check the new property value matches, or is "overwriting"
        // 2. Set certificateDn to null
        $certificateDn = $user->getCertificateDn();
        if ($certificateDn !== null) {
            if ($certificateDn !== $keyValue && $preventOverwrite) {
                throw new \Exception("Property value does not match certificateDn");
            }
            $user->setCertificateDn(null);
            $this->em->persist($user);
            $this->em->flush();
        }

        // Check the ID string does not already exist
        $this->valdidateUniqueIdString($keyValue);

        // Check auth type is valid
        $this->valdidateAuthType($keyName);
    }

    /**
     * Edit a user's property.
     *
     * @param \User $user
     * @param \UserProperty $prop
     * @param array $newValues
     * @param \User $currentUser
     * @throws \Exception
     */
    public function editUserProperty(\User $user, \UserProperty $prop, $newValues, \User $currentUser) {
        // Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($currentUser);

        // Check to see whether the current user can edit this user
        $this->editUserAuthorization($user, $currentUser);

        // Make the change
        $this->em->getConnection()->beginTransaction();
        try {
            $this->editUserPropertyLogic($user, $prop, $newValues);
            $this->em->flush ();
            $this->em->getConnection ()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection ()->rollback();
            $this->em->close ();
            throw $e;
        }
    }

    /**
     * Logic to edit a user's property, without the user validation.
     * Validation of the edited property values is performed by a seperate function.
     * @param \User $user
     * @param \UserProperty $prop
     * @param array $newValues
     * @throws \Exception
     */
    protected function editUserPropertyLogic(\User $user, \UserProperty $prop, $newValues) {

        // Trim off trailing and leading whitespace
        $keyName = trim($newValues['USERPROPERTIES']['NAME']);
        $keyValue = trim($newValues['USERPROPERTIES']['VALUE']);

        // Validate new property
        $this->editUserPropertyValidation($user, $prop, $keyName, $keyValue);

        // Set the user property values
        $prop->setKeyName($keyName);
        $prop->setKeyValue($keyValue);
        $this->em->merge($prop);
    }

    /**
     * Validation when editing a user's property
     * @param \User $user
     * @param \UserProperty $prop
     * @param string $keyName
     * @param string $keyValue
     * @throws \Exception
     */
    protected function editUserPropertyValidation(\User $user, \UserProperty $prop, $keyName, $keyValue) {

        // Validate new values against schema
        $validateArray['NAME'] = $keyName;
        $validateArray['VALUE'] = $keyValue;
        $this->validate($validateArray, 'userproperty');

        // Check that the property is owned by the user
        if ($prop->getParentUser() !== $user) {
            $id = $prop->getId();
            throw new \Exception("Property {$id} does not belong to the specified user");
        }

        // Check the property has changed
        if($keyName === $prop->getKeyName() && $keyValue === $prop->getKeyValue()) {
            throw new \Exception("The specified user property is the same as the current user property");
        }

        // Check the ID string is unique if it is being changed
        if($keyValue !== $prop->getKeyValue()) {
            $this->valdidateUniqueIdString($keyValue);
        }

        // Check auth type is valid
        $this->valdidateAuthType($keyName);

        // If the properties key has changed, check there isn't an existing property with that key
        if ($keyName !== $prop->getKeyName()) {
            $existingProperties = $user->getUserProperties();
            foreach ($existingProperties as $existingProp) {
                if ($existingProp->getKeyName() === $keyName) {
                    throw new \Exception("A property with that name already exists for this object");
                }
            }
        }
    }

    /**
     * Validate authentication type based on known list.
     * @param string $authType
     * @throws \Exception
     */
    protected function valdidateAuthType($authType) {
        if (!in_array($authType, $this->getAuthTypes(false))) {
            throw new \Exception("The authentication type entered is invalid");
        }
    }

    /**
     * Validate ID string is unique.
     * Checks both user properties and certificateDns
     * @param string $idString
     * @throws \Exception
     */
    protected function valdidateUniqueIdString($idString) {
        $oldUser = $this->getUserFromDn($idString);
        $newUser = $this->getUserByPrinciple($idString);
        if(!is_null($oldUser) || !is_null($newUser)) {
            throw new \Exception("ID string is already registered in GOCDB");
        }
    }

    /**
     * Deletes user properties: validates the user has permission then calls the
     * required logic
     * @param \User $user
     * @param \UserProperty $prop
     * @param \User $currentUser
     */
    public function deleteUserProperty(\User $user, \UserProperty $prop, \User $currentUser) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        // Check to see whether the current user can edit this user
        $this->editUserAuthorization($user, $currentUser);

        // Make the change
        $this->em->getConnection()->beginTransaction();
        try {
            $this->deleteUserPropertyLogic($user, $prop);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * All the logic to delete a user's property, before deletion a check is done to confirm the property
     * is from the parent user specified by the request, and an exception is thrown if this is
     * not the case
     * @param \User $user
     * @param \UserProperty $prop
     */
    protected function deleteUserPropertyLogic(\User $user, \UserProperty $prop) {
        // Check that the property's parent user the same as the one given
        if ($prop->getParentUser() !== $user) {
            $id = $prop->getId();
            throw new \Exception("Property {$id} does not belong to the specified user");
        }
        // Check the user has more than one property
        if (sizeof($user->getUserProperties()) < 2) {
            throw new \Exception("Users must have at least one identity string.");
        }
        // User is the owning side so remove elements from the user
        $user->getUserProperties()->removeElement($prop);
        // Once relationship is removed delete the actual element
        $this->em->remove($prop);
    }

    /**
     * Changes the isAdmin user property.
     * @param \User $user           The user who's admin status is to change
     * @param \User $currentUser    The user making the change, who themselvess must be an admin
     * @param boolean $isAdmin      The new property. This must be boolean true or false.
     */
    /*public function setUserIsAdmin(\User $user, \User $currentUser = null, $isAdmin= false){
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($currentUser);

        //Throws exception if the current user is not an administrator - only admins can make admins
        $this->checkUserIsAdmin($currentUser);

        //Check that $isAdmin is boolean
        if(!is_bool($isAdmin)){
            throw new \Exception("the setUserAdmin function takes on boolean values for isAdmin");
        }

        //Check user is not changing themselves - prevents lone admin acidentally demoting themselves
        if($user==$currentUser){
            throw new \Exception("To ensure there is always at least one administrator, you may not demote yourself, please ask another administrator to do it");
        }

        //Actually make the change
        $this->em->getConnection()->beginTransaction();
        try {
            $user->setAdmin($isAdmin);
            $this->em->merge($user);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }

    }*/
}
