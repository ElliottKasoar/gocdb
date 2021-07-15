<?php
namespace org\gocdb\services;

require_once __DIR__ . '/AbstractEntityService.php';
class LinkIdentity extends AbstractEntityService {

    /**
     * Processes an identity link request
     * @param string $currentIdString
     * @param string $givenEmail
     * @param string $primaryIdString
     * @param string $primaryAuthType
     * @param string $currentAuthType
     */
    public function newLinkIdentityRequest($currentIdString, $givenEmail, $primaryIdString, $primaryAuthType, $currentAuthType) {

        $serv = \Factory::getUserService();

        // $primaryUser is user who will have ID string updated/added
        // Ideally, ID string and auth type match a user property
        $primaryUser = $serv->getUserByPrincipleAndType($primaryIdString, $primaryAuthType);
        if($primaryUser === null) {
            // If no valid user properties, check certificateDNs
            $primaryUser = $serv->getUserFromDn($primaryIdString);
            if($primaryUser === null) {
                // Don't throw exception to limit info shared
                return;
            }
        }

        // $currentUser is user making request, referred to as "secondaryUser" in LinkIdentityRequest
        // May not be registered so can be null
        $currentUser = $serv->getUserByPrinciple($currentIdString);
        
        if($primaryUser === $currentUser) {
            // Can throw exception as it's their own ID string
            throw new \Exception("The details entered are already associated with this account");
        }

        // Check the given email address matches the one given
        if(strcasecmp($primaryUser->getEmail(), $givenEmail)) {
            // Don't throw exception to limit info shared
            return;
        }

        // Check the portal is not in read only mode, throws exception if it is
        // If portal is read only, but the current user is an admin, we will still be able to proceed
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($currentUser);

        // Remove any existing requests involving either user
        $this->removeRelatedRequests($currentIdString, $primaryUser, $currentUser);

        // Generate confirmation code
        $code = $this->generateConfirmationCode($primaryIdString);

        // Create link identity request
        $linkIdentityReq = new \LinkIdentityRequest($primaryUser, $currentUser, $code, $primaryIdString, $currentIdString, $primaryAuthType, $currentAuthType);

        // Recovery or identity linking
        if ($primaryAuthType === $currentAuthType) {
            $requestType = 'recover';
        } else {
            $requestType = 'link';
        }

        // Recovery or identity linking
        if ($currentUser === null) {
            $registered = false;
        } else {
            $registered = true;
        }

        // Apply change
        try {
            $this->em->getConnection()->beginTransaction();
            $this->em->persist($linkIdentityReq);
            $this->em->flush();

            // Send confirmation email to primary user (before commit - if it fails we'll need a rollback)
            $this->sendPrimaryConfirmationEmail($primaryUser, $code, $primaryIdString, $primaryAuthType, $currentIdString, $currentAuthType, $requestType, $registered);

            // Send confirmation email to secondary user, if registered with different email to primary user
            if ($registered) {
                if ($currentUser->getEmail() !== $primaryUser->getEmail()) {
                    $this->sendSecondaryConfirmationEmail($currentUser, $primaryIdString, $primaryAuthType, $currentIdString, $currentAuthType, $requestType);
                }
            }

            $this->em->getConnection()->commit();
        } catch(\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * Removes any existing requests which involve either user
     * @param string $currentIdString
     * @param \User $primaryUser
     * @param \User $currentUser
     */
    private function removeRelatedRequests($currentIdString, $primaryUser, $currentUser) {

        $previousRequests = [];

        // Check for a previous request
        $previousRequests[] = $this->getLinkIdentityRequestByUserId($primaryUser->getId());
        if ($currentUser !== null) {
            $previousRequests[] = $this->getLinkIdentityRequestByUserId($currentUser->getId());
        } else {
            $previousRequests[] = $this->getLinkIdentityRequestByIdString($currentIdString);
        }

        // Remove any requests found
        foreach ($previousRequests as $previousRequest) {
            if(!is_null($previousRequest)) {
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
        $confirm_code = rand(1, 10000000);
        $confirm_hash = sha1($idString.$confirm_code);
        return $confirm_hash;
    }

    /**
     * Gets a link identity request from the database based on user ID
     * @param integer $userId userid of the request to be linked
     * @return arraycollection
     */
    private function getLinkIdentityRequestByUserId($userId) {
        $dql = "SELECT l
                FROM LinkIdentityRequest l
                JOIN l.primaryUser u
                WHERE u.id = :id";

        $request = $this->em
            ->createQuery($dql)
            ->setParameter('id', $userId)
            ->getOneOrNullResult();

        return $request;
    }

    /**
     * Gets a link identity request from the database based on current ID string
     * ID string may be present as primary or secondary user
     * @param string $idString ID string of user to be linked in primary account
     * @return arraycollection
     */
    private function getLinkIdentityRequestByIdString($idString) {
        $dql = "SELECT l
                FROM LinkIdentityRequest l
                WHERE l.primaryIdString = :idString
                OR l.secondaryIdString = :idString";

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
     *
     * @param string $primaryIdString ID string $primaryUser
     * @param string $currentIdString ID string for current user
     * @param string $requestType account recovery or linking
     * @param string $link to be clicked
     * @param bool $registered true if the current user is registered
     * @return arraycollection
     */
    private function composePrimaryEmail($primaryIdString, $primaryAuthType, $currentIdString, $currentAuthType, $requestType, $link, $registered) {

        if ($requestType === 'link') {

            $subject = "Validation of linking your GOCDB account";

            $body = "Dear GOCDB User,"
            . "\n\nA request to add a new authentication method to your GOCDB account"
            . " (ID string: $primaryIdString, authentication type: $primaryAuthType) has just been made on GOCDB."
            . "\n\nThe new authentication details are:"
            . "\nID string: $currentIdString"
            . "\nAuthentication type: $currentAuthType";

            if ($registered) {
                $body .= "\n\nThe new authentication method is currently associated with a second registered account."
                . " If linking is sucessful, any roles currently associated with this second account (ID string: $currentIdString)"
                . " will be requested for your GOCDB account (ID string: $primaryIdString)."
                . " These roles will be approved automatically if either account has permission to do so."
                . "\n\n The second account will then be deleted.";
            }

            $body .= "\n\nIf you wish to associate your GOCDB account with this authentication method, please validate your request by clicking on the link below:"
            . "\n$link"
            . "\n\nIf you did not create this request in GOCDB, please immediately contact gocdb-admins@mailman.egi.eu";

        } elseif ($requestType === 'recover') {

            $subject = "Validation of recovering your GOCDB account";

            $body = "Dear GOCDB User,\n\n"
            ."A request to retrieve and associate your GOCDB account (ID string: $primaryIdString, authentication type: $primaryAuthType)"
            . " and privileges with a new ID string has just been made on GOCDB."
            ."\n\nThe new ID string is: $currentIdString";

            if ($registered) {
                $body .= "\n\nThis new ID string is currently associated with a second registered account."
                . " If recovery is sucessful, any roles currently associated with this second account (ID string: $currentIdString)"
                . " will be requested for your GOCDB account (ID string: $primaryIdString)."
                . " These roles will be approved automatically if either account has permission to do so."
                . "\n\n The second account will then be deleted.";
            }

            $body .= "\n\nIf you wish to associate your GOCDB account with this ID string, please validate your request by clicking on the link below:\n"
            . "$link"
            . "\n\nIf you did not create this request in GOCDB, please immediately contact gocdb-admins@mailman.egi.eu";

        } else {
            throw new \Exception("Invalid request type");
        }
        return array('subject'=>$subject, 'body'=>$body);
    }

    /**
     * Sends a confimation email to the user being linked or recovered
     *
     * @param \User $primaryUser user who will have new log-in added
     * @param string $confirmationCode generated confirmation code
     * @param string $primaryIdString ID string $primaryUser
     * @param string $primaryAuthType auth type of $primaryIdString
     * @param string $currentIdString ID string for current user
     * @param string $currentAuthType auth type of $currentIdString
     * @param bool $registered true if the current user is registered
     * @throws \Exception
     */
    private function sendPrimaryConfirmationEmail(\User $primaryUser, $confirmationCode, $primaryIdString, $primaryAuthType, $currentIdString, $currentAuthType, $requestType, $registered) {

        // Create link to be clicked in email
        $portalUrl = \Factory::getConfigService()->GetPortalURL();
        $link = $portalUrl."/index.php?Page_Type=User_Validate_Identity_Link&c=".$confirmationCode;

        // Compose emails for identity linking or recovery
        $composedEmail = $this->composePrimaryEmail($primaryIdString, $primaryAuthType, $currentIdString, $currentAuthType, $requestType, $link, $registered);
        $subject = $composedEmail['subject'];
        $body = $composedEmail['body'];

        // If "sendmail_from" is set in php.ini, use second line ($headers = '';):
        $headers = "From: no-reply@goc.egi.eu";

        // Mail command returns boolean. False if message not accepted for delivery.
        if (!mail($primaryUser->getEmail(), $subject, $body, $headers)) {
            throw new \Exception("Unable to send email message");
        }
    }

    /**
     * Composes confimation email to be sent to the user
     *
     * @param string $primaryIdString ID string $primaryUser
     * @param string $primaryAuthType auth type of $primaryIdString
     * @param string $currentIdString ID string for current user
     * @param string $currentAuthType auth type for current user
     * @param string $requestType account recovery or linking
     * @return arraycollection
     */
    private function composeSecondaryEmail($primaryIdString, $primaryAuthType, $currentIdString, $currentAuthType, $requestType) {

        if ($requestType === 'link') {

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

        } elseif ($requestType === 'recover') {

            $subject = "Validation of recovering your GOCDB account";

            $body = "Dear GOCDB User,"
            . "\n\nA request to recover one of your accounts"
            . " (ID string: $primaryIdString, authentication type: $primaryAuthType) has just been made on GOCDB."
            . "\n\nThe new ID string will be:"
            . "\n$currentIdString"
            . "\n\nThis new ID string is current associated with a different GOCDB account, which will be deleted"
            . " on completetion of the account recovery process."
            . "\n\nIf you did not create this request in GOCDB, please immediately contact gocdb-admins@mailman.egi.eu";

        } else {
            throw new \Exception("Invalid request type");
        }
        return array('subject'=>$subject, 'body'=>$body);
    }


    /**
     * Sends a confimation email to the user carrying out the process
     *
     * @param \User $currentUser user that will be deleted
     * @param string $primaryIdString ID string $primaryUser
     * @param string $primaryAuthType auth type of $primaryIdString
     * @param string $currentIdString ID string for current user
     * @param string $currentAuthType auth type for current user
     * @param  $requestType "link" identity or "recover" account
     * @throws \Exception
     */
    private function sendSecondaryConfirmationEmail(\User $currentUser, $primaryIdString, $primaryAuthType, $currentIdString, $currentAuthType, $requestType) {

        // Compose emails for identity linking or recovery
        $composedEmail = $this->composeSecondaryEmail($primaryIdString, $primaryAuthType, $currentIdString, $currentAuthType, $requestType);
        $subject = $composedEmail['subject'];
        $body = $composedEmail['body'];

        //If "sendmail_from" is set in php.ini, use second line ($headers = '';):
        $headers = "From: no-reply@goc.egi.eu";

        // Mail command returns boolean. False if message not accepted for delivery.
        if (!mail($currentUser->getEmail(), $subject, $body, $headers)) {
            throw new \Exception("Unable to send email message");
        }
    }

    public function confirmIdentityLinking ($code, $currentId) {

        // Get the request
        $request = $this->getLinkIdentityRequestByConfirmationCode($code);

        // Check there is a result
        if(is_null($request)) {
            throw new \Exception("Confirmation URL invalid. If you have submitted multiple requests for the same account, please ensure you have used the link in the most recent email");
        }

        $primaryUser = $request->getPrimaryUser();
        $secondaryUser = $request->getSecondaryUser();

        // Check the portal is not in read only mode, throws exception if it is. If portal is read only, but the user whose id is being changed is an admin, we will still be able to proceed.
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($primaryUser);

        // Check the id currently being used by the user is same as in the request
        if($currentId !== $request->getSecondaryIdString()) {
            throw new \Exception("Your current ID does not match the one to which requested be linked. The link will only work once, if you have refreshed the page or clicked the link a second time you will see this messaage"); //TODO: reword
        }

        // Create property array from the secondary user's credentials
        $propArr = array($request->getSecondaryAuthType(), $request->getSecondaryIdString());
        require_once __DIR__ . '/User.php';

        // Are we recovering or linking an identity? True if linking
        $linking = ($request->getPrimaryAuthType() !== $request->getSecondaryAuthType());

        // Recovering: Allow overwrite in addProperties, which will edit the property ID string
        // Linking: Prevent overwriting, as we want to add a new property
        $preventOverwrite = $linking;

        // If linking, does primary user have user properties? If not, we will add this using the request info
        $oldUser = ($primaryUser->getCertificateDn() === $request->getPrimaryIdString());
        if ($linking && $oldUser) {
            $propArrOld = array($request->getPrimaryAuthType(), $request->getPrimaryIdString());
        }

        $serv = \Factory::getUserService();

        // Update primary user, remove request (and secondary user)
        try{
            $this->em->getConnection()->beginTransaction();

            // Add old certificateDn as property if linking
            if ($oldUser && $linking) {
                $serv->addUserProperty($primaryUser, $propArrOld, $primaryUser);
            }

            // Merge roles and remove secondary user so their ID string is free to be added
            if ($secondaryUser !== null) {
                \Factory::getRoleService()->mergeRoles($primaryUser, $secondaryUser);
                $serv->deleteUser($secondaryUser, $secondaryUser);
            }

            $this->em->remove($request);
            $this->em->flush();

            // Add (or update if recovering i.e. $preventOverwrite=false) the ID string
            $serv->addUserProperty($primaryUser, $propArr, $primaryUser, $preventOverwrite);

            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch(\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }
}