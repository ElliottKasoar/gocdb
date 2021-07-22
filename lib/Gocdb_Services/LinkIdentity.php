<?php
namespace org\gocdb\services;

require_once __DIR__ . '/AbstractEntityService.php';
class LinkIdentity extends AbstractEntityService {

    /**
     * Processes an identity link request
     * @param string $primaryIdString ID string of primary user
     * @param string $currentIdString ID string of current user
     * @param string $primaryAuthType auth type of primary ID string
     * @param string $currentAuthType auth type of current ID string
     * @param string $givenEmail email of primary user
     */
    public function newLinkIdentityRequest($primaryIdString, $currentIdString, $primaryAuthType, $currentAuthType, $givenEmail) {

        $serv = \Factory::getUserService();

        // $primaryUser is user who will have ID string updated/added
        // Ideally, ID string and auth type match a user property
        $primaryUser = $serv->getUserByPrincipleAndType($primaryIdString, $primaryAuthType);
        if ($primaryUser === null) {
            // If no valid user properties, check certificateDNs
            $primaryUser = $serv->getUserFromDn($primaryIdString);
        }

        // $currentUser is user making request
        // May not be registered so can be null
        $currentUser = $serv->getUserByPrinciple($currentIdString);

        // Recovery or identity linking
        if ($primaryAuthType === $currentAuthType) {
            $isLinking = false;
        } else {
            $isLinking = true;
        }

        // Validate details. For most errors, return without throwing an error to avoid sharing info
        if ($this->validate($primaryUser, $currentUser, $currentAuthType, $isLinking, $givenEmail) === 1) {
            return;
        }

        // Remove any existing requests involving either user
        $this->removeRelatedRequests($primaryUser, $currentUser, $primaryIdString, $currentIdString);

        // Generate confirmation code
        $code = $this->generateConfirmationCode($primaryIdString);

        // Create link identity request
        $linkIdentityReq = new \LinkIdentityRequest($primaryUser, $currentUser, $code, $primaryIdString, $currentIdString, $primaryAuthType, $currentAuthType);

        // Recovery or identity linking
        if ($currentUser === null) {
            $isRegistered = false;
        } else {
            $isRegistered = true;
        }

        // Apply change
        try {
            $this->em->getConnection()->beginTransaction();
            $this->em->persist($linkIdentityReq);
            $this->em->flush();

            // Send confirmation email(s) to primary user, and current user if registered with a different email
            // (before commit - if it fails we'll need a rollback)
            $this->sendConfirmationEmails($primaryUser, $currentUser, $code, $primaryIdString, $currentIdString, $primaryAuthType, $currentAuthType, $isLinking, $isRegistered);

            $this->em->getConnection()->commit();
        } catch(\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

/**
     * Performs validation on request
     * @param \User $primaryUser user who will have property added/updated
     * @param \User $currentUser user creating the request
     * @param string $currentAuthType auth type of current ID string
     * @param bool $isLinking true if linking, false if recovering
     * @param string $givenEmail email of primary user
     */
    private function validate($primaryUser, $currentUser, $currentAuthType, $isLinking, $givenEmail) {

        if ($primaryUser === null) {
            // Don't throw exception to limit info shared
            return 1;
        }

        if ($primaryUser === $currentUser) {
            // Can throw exception as it's their own ID string
            throw new \Exception("The details entered are already associated with this account");
        }

        // Check the portal is not in read only mode, throws exception if it is
        // If portal is read only, but the current user is an admin, we will still be able to proceed
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($currentUser);

        // Check the given email address matches the one given
        if (strcasecmp($primaryUser->getEmail(), $givenEmail)) {
            // Don't throw exception to limit info shared
            return 1;
        }

        // Prevent attempt to add duplicate auth type when linking
        if ($isLinking) {
            foreach ($primaryUser->getUserProperties() as $prop) {
                if ($prop->getKeyName() === $currentAuthType) {
                    return 1;
                }
            }
        }
        return 0;
    }

    /**
     * Removes any existing requests which involve either user
     * @param \User $primaryUser user who will have property added/updated
     * @param \User $currentUser user creating the request
     * @param string $primaryIdString ID string of primary user
     * @param string $currentIdString ID string of current user
     */
    private function removeRelatedRequests($primaryUser, $currentUser, $primaryIdString, $currentIdString) {

        // Set up list for previous requests matching various criteria
        $previousRequests = [];

        // Matching the primary user
        $previousRequests[] = $this->getLinkIdentityRequestByUserId($primaryUser->getId());

        // Matching the primary user's ID string - unlikely to exist but not impossible
        $previousRequests[] = $this->getLinkIdentityRequestByIdString($primaryIdString);

        // Matching the current user, if registered
        if ($currentUser !== null) {
            $previousRequests[] = $this->getLinkIdentityRequestByUserId($currentUser->getId());
        }

        // Matching the current ID string
        $previousRequests[] = $this->getLinkIdentityRequestByIdString($currentIdString);

        // Remove any requests found
        foreach ($previousRequests as $previousRequest) {
            if (!is_null($previousRequest)) {
                try{
                    $this->em->getConnection()->beginTransaction();
                    $this->em->remove($previousRequest);
                    $this->em->flush();
                    $this->em->getConnection()->commit();
                } catch(\Exception $e) {
                    $this->em->getConnection()->rollback();
                    $this->em->close();
                    throw $e;
                }
            }
        }
    }

    /**
     * Generates a confirmation code
     * @param string $idString ID string used to generated code
     */
    private function generateConfirmationCode($idString) {
        $confirmCode = rand(1, 10000000);
        $confirmHash = sha1($idString.$confirmCode);
        return $confirmHash;
    }

    /**
     * Gets a link identity request from the database based on user ID
     * @param integer $userId userid of the request to be linked
     * @return arraycollection
     */
    private function getLinkIdentityRequestByUserId($userId) {
        $dql = "SELECT l
                FROM LinkIdentityRequest l
                JOIN l.primaryUser pu
                JOIN l.currentUser cu
                WHERE pu.id = :id OR cu.id = :id";

        $request = $this->em
            ->createQuery($dql)
            ->setParameter('id', $userId)
            ->getOneOrNullResult();

        return $request;
    }

    /**
     * Gets a link identity request from the database based on current ID string
     * ID string may be present as primary or current user
     * @param string $idString ID string of user to be linked in primary account
     * @return arraycollection
     */
    private function getLinkIdentityRequestByIdString($idString) {
        $dql = "SELECT l
                FROM LinkIdentityRequest l
                WHERE l.primaryIdString = :idString
                OR l.currentIdString = :idString";

        $request = $this->em
            ->createQuery($dql)
            ->setParameter('idString', $idString)
            ->getOneOrNullResult();

        return $request;
    }

    /**
     * Gets an identity link request from the database based on the confirmation code
     * @param string $code confirmation code of the request being retrieved
     * @return arraycollection
     */
    public function getLinkIdentityRequestByConfirmationCode($code) {
        $dql = "SELECT l
                FROM LinkIdentityRequest l
                WHERE l.confirmCode = :code";

        $request = $this->em
            ->createQuery($dql)
            ->setParameter('code', $code)
            ->getOneOrNullResult();

        return $request;
    }

    /**
     * Composes confimation email to be sent to the user
     * @param string $primaryIdString ID string of primary user
     * @param string $currentIdString ID string of current user
     * @param string $primaryAuthType auth type of primary ID string
     * @param string $currentAuthType auth type of current ID string
     * @param bool $isLinking true if linking, false if recovering
     * @param bool $isRegistered true if current user is registered
     * @param string $link to be clicked
     * @return arraycollection
     */
    private function composePrimaryEmail($primaryIdString, $currentIdString, $primaryAuthType, $currentAuthType, $isLinking, $isRegistered, $link) {

        if ($isLinking) {

            $subject = "Validation of linking your GOCDB account";

            $body = "Dear GOCDB User,"
            . "\n\nA request to add a new authentication method to your GOCDB account"
            . " (ID string: $primaryIdString, authentication type: $primaryAuthType) has just been made on GOCDB."
            . "\n\nThe new authentication details are:"
            . "\nID string: $currentIdString"
            . "\nAuthentication type: $currentAuthType";

            if ($isRegistered) {
                $body .= "\n\nThe new authentication method is currently associated with a second registered account."
                . " If linking is sucessful, any roles currently associated with this second account (ID string: $currentIdString)"
                . " will be requested for your GOCDB account (ID string: $primaryIdString)."
                . " These roles will be approved automatically if either account has permission to do so."
                . "\n\n The second account will then be deleted.";
            }

            $body .= "\n\nIf you wish to associate your GOCDB account with this authentication method, please validate your request by clicking on the link below:"
            . "\n$link"
            . "\n\nIf you did not create this request in GOCDB, please immediately contact gocdb-admins@mailman.egi.eu";

        } else {

            $subject = "Validation of recovering your GOCDB account";

            $body = "Dear GOCDB User,\n\n"
            ."A request to retrieve and associate your GOCDB account (ID string: $primaryIdString, authentication type: $primaryAuthType)"
            . " and privileges with a new ID string has just been made on GOCDB."
            ."\n\nThe new ID string is: $currentIdString";

            if ($isRegistered) {
                $body .= "\n\nThis new ID string is currently associated with a second registered account."
                . " If recovery is sucessful, any roles currently associated with this second account (ID string: $currentIdString)"
                . " will be requested for your GOCDB account (ID string: $primaryIdString)."
                . " These roles will be approved automatically if either account has permission to do so."
                . "\n\n The second account will then be deleted.";
            }

            $body .= "\n\nIf you wish to associate your GOCDB account with this ID string, please validate your request by clicking on the link below:\n"
            . "$link"
            . "\n\nIf you did not create this request in GOCDB, please immediately contact gocdb-admins@mailman.egi.eu";
        }
        return array('subject'=>$subject, 'body'=>$body);
    }

    /**
     * Composes confimation email to be sent to the user
     * @param string $primaryIdString ID string of primary user
     * @param string $currentIdString ID string of current user
     * @param string $primaryAuthType auth type of primary ID string
     * @param string $currentAuthType auth type of current ID string
     * @param bool $isLinking true if linking, false if recovering
     * @return arraycollection
     */
    private function composeCurrentEmail($primaryIdString, $currentIdString, $primaryAuthType, $currentAuthType, $isLinking) {

        if ($isLinking) {

            $subject = "Validation of linking your GOCDB account";

            $body = "Dear GOCDB User,"
            . "\n\nA request to add a new authentication method to one of your accounts"
            . " (ID string: $primaryIdString, authentication type: $primaryAuthType) has just been made on GOCDB."
            . "\n\nThe authentication details to be added are:"
            . "\nID string: $currentIdString"
            . "\nAuthentication type: $currentAuthType"
            . "\n\nThese details are currently associated with a different GOCDB account, which will be deleted"
            . " on completetion of the identity linking process."
            . "\n\nIf you did not create this request in GOCDB, please immediately contact gocdb-admins@mailman.egi.eu";

        } else {

            $subject = "Validation of recovering your GOCDB account";

            $body = "Dear GOCDB User,"
            . "\n\nA request to recover one of your accounts"
            . " (ID string: $primaryIdString, authentication type: $primaryAuthType) has just been made on GOCDB."
            . "\n\nThe new ID string will be:"
            . "\n$currentIdString"
            . "\n\nThis new ID string is current associated with a different GOCDB account, which will be deleted"
            . " on completetion of the account recovery process."
            . "\n\nIf you did not create this request in GOCDB, please immediately contact gocdb-admins@mailman.egi.eu";
        }
        return array('subject'=>$subject, 'body'=>$body);
    }

    /**
     * Send confirmation email(s) to primary user, and current user if registered with a different email
     * @param \User $primaryUser user who will have property added/updated
     * @param \User $currentUser user creating the request
     * @param string $code confirmation code of the request being retrieved
     * @param string $primaryIdString ID string of primary user
     * @param string $currentIdString ID string of current user
     * @param string $primaryAuthType auth type of primary ID string
     * @param string $currentAuthType auth type of current ID string
     * @param bool $isLinking true if linking, false if recovering
     * @param bool $isRegistered true if current user is registered
     */
    private function sendConfirmationEmails($primaryUser, $currentUser, $code, $primaryIdString, $currentIdString, $primaryAuthType, $currentAuthType, $isLinking, $isRegistered) {

        // Create link to be clicked in email
        $portalUrl = \Factory::getConfigService()->GetPortalURL();
        $link = $portalUrl."/index.php?Page_Type=User_Validate_Identity_Link&c=" . $code;

        // Compose emails for identity linking or recovery
        $composedPrimaryEmail = $this->composePrimaryEmail($primaryIdString, $currentIdString, $primaryAuthType, $currentAuthType, $isLinking, $isRegistered, $link);
        $primarySubject = $composedPrimaryEmail['subject'];
        $primaryBody = $composedPrimaryEmail['body'];

        // If "sendmail_from" is set in php.ini, use second line ($headers = '';):
        $headers = "From: no-reply@goc.egi.eu";

        // Mail command returns boolean. False if message not accepted for delivery.
        if (!mail($primaryUser->getEmail(), $primarySubject, $primaryBody, $headers)) {
            throw new \Exception("Unable to send email message");
        }

        // Send confirmation email to current user, if registered with different email to primary user
        if ($isRegistered) {
            if ($currentUser->getEmail() !== $primaryUser->getEmail()) {

                $composedCurrentEmail = $this->composeCurrentEmail($primaryIdString, $currentIdString, $primaryAuthType, $currentAuthType, $isLinking);
                $currentSubject = $composedCurrentEmail['subject'];
                $currentBody = $composedCurrentEmail['body'];

                // Mail command returns boolean. False if message not accepted for delivery.
                if (!mail($currentUser->getEmail(), $currentSubject, $currentBody, $headers)) {
                    throw new \Exception("Unable to send email message");
                }
            }
        }
    }

    /**
     * Confirm and execute linking or recovery request
     * @param string $code confirmation code of the request being retrieved
     * @param string $currentIdString ID string of current user
     */
    public function confirmIdentityLinking ($code, $currentIdString) {

        $serv = \Factory::getUserService();

        // Get the request
        $request = $this->getLinkIdentityRequestByConfirmationCode($code);

        $invalidURL = "Confirmation URL invalid."
        . " If you have submitted multiple requests for the same account, please ensure you have used the link in the most recent email."
        . " Please also ensure you are authenticated in the same way as when you made the request.";

        // Check there is a result
        if (is_null($request)) {
            throw new \Exception($invalidURL);
        }

        $primaryUser = $request->getPrimaryUser();
        $currentUser = $request->getCurrentUser();

        // Check the portal is not in read only mode, throws exception if it is. If portal is read only, but the user whose id is being changed is an admin, we will still be able to proceed.
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($primaryUser);

        // Check the id currently being used by the user is same as in the request
        if ($currentIdString !== $request->getCurrentIdString()) {
            throw new \Exception($invalidURL);
        }

        // Create property array from the current user's credentials
        $propArr = array($request->getCurrentAuthType(), $request->getCurrentIdString());

        // Are we recovering or linking an identity? True if linking
        $isLinking = ($request->getPrimaryAuthType() !== $request->getCurrentAuthType());

        // If linking, does primary user have user properties?
        // If not, and linking, we will add this using the request info
        $oldUser = ($primaryUser->getCertificateDn() === $request->getPrimaryIdString());
        if ($isLinking && $oldUser) {
                $propArrOld = array($request->getPrimaryAuthType(), $request->getPrimaryIdString());
        }

        // If recovering, get property being updated (if it exists)
        if (!$isLinking && !$oldUser) {
            $property = $serv->getPropertyByIdString($request->getPrimaryIdString());
        }

        // Update primary user, remove request (and current user)
        try{
            $this->em->getConnection()->beginTransaction();

            // Add old certificateDn as property if linking
            if ($oldUser && $isLinking) {
                $serv->addUserProperty($primaryUser, $propArrOld, $primaryUser);
            }

            // Merge roles and remove current user so their ID string is free to be added
            if ($currentUser !== null) {
                \Factory::getRoleService()->mergeRoles($primaryUser, $currentUser);
                $serv->deleteUser($currentUser, $currentUser);
            }

            $this->em->remove($request);
            $this->em->flush();

            // Add or update the ID string
            if ($isLinking || $oldUser) {
                $serv->addUserProperty($primaryUser, $propArr, $primaryUser);
            } else {
                $serv->editUserProperty($primaryUser, $property, $propArr, $primaryUser);
            }

            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch(\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }

        return $request;
    }
}