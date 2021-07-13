<?php
namespace org\gocdb\services;

require_once __DIR__ . '/AbstractEntityService.php';

class LinkIdentity extends AbstractEntityService {

    /**
     * Processes an identity link request
     * @param \User $primaryUser
     * @param \User $currentUser
     */
    public function newLinkIdentityRequest($currentIdString, $givenEmail, $primaryIdString, $primaryAuthType, $currentAuthType) {

        $serv = \Factory::getUserService();

        // Ideally, the ID string and auth type match a user property
        $primaryUser = $serv->getUserByPrincipleAndType($primaryIdString, $primaryAuthType);
        if($primaryUser === null) {
            // If no valid user properties, check certificateDNs
            $primaryUser = $serv->getUserFromDn($primaryIdString);
            if($primaryUser === null) {
                throw new \Exception("Cannot find user with id $primaryIdString and auth type $primaryAuthType");
            }
        }

        // Check the given email address matches the one given
        if(strcasecmp($primaryUser->getEmail(), $givenEmail)) {
            throw new \Exception("E-mail address doesn't match id");
        }

        // $currentUser is user making request
        // Referred to as "secondaryUser" in LinkIdentityRequest
        // User may not be registered so don't throw exception if null/no email
        $currentUser = $serv->getUserByPrinciple($currentIdString);
        
        if($primaryUser === $currentUser) {
            throw new \Exception("The details entered are already associated with this account");
        }

        // Check the portal is not in read only mode, throws exception if it is
        // If portal is read only, but the current user is an admin, we will still be able to proceed
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($currentUser);

        // Check if there has already been a request to link current id, if there has,
        // remove it. This must be in a seperate try catch block to the new one,
        // to prevent constraint violations
        // Currently request to link multiple ids to one primary id are permitted
        $previousRequest = $this->getLinkIdentityRequestByIdString($currentIdString);
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

        //apply change
        try {
            $this->em->getConnection()->beginTransaction();
            $this->em->persist($linkIdentityReq);
            $this->em->flush();

            // Send email (before commit - if it fails we'll need a rollback)
            // Todo: add auth types to email?
            $this->sendConfirmationEmail($primaryUser, $code, $primaryIdString, $primaryAuthType, $currentIdString, $currentAuthType, $requestType, $registered);

            $this->em->getConnection()->commit();
        } catch(\Exception $ex) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $ex;
        }
    }

    private function generateConfirmationCode($idString) {
        $confirm_code = rand(1, 10000000);
        $confirm_hash = sha1($idString.$confirm_code);
        return $confirm_hash;
    }

    /**
     * Gets a link identity request from the database based on userid
     * Not currently in use
     * @param integer $userId userid of the request to be linked
     * @return arraycollection
     */
    public function getLinkIdentityRequestByUserId($userId) {
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
     * Id string my be present as primary or secondary user
     * @param string $idString ID string of user to be linked in primary account
     * @return arraycollection
     */
    public function getLinkIdentityRequestByIdString($idString) {
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
     * @param type $primaryIdString ID string $primaryUser
     * @param type $currentIdString ID string for current user
     * @param type $requestType account recovery or linking
     * @param type $link to be clicked
     * @param type $registered true if the current user is registered
     * @return arraycollection
     */
    private function composeEmail($primaryIdString, $primaryAuthType, $currentIdString, $currentAuthType, $requestType, $link, $registered) {

        if ($requestType === 'link') {

            $subject = "Validation of linking your GOCDB account";

            $body = "Dear GOCDB User,\n\n"
            ."A request to add a new authentication method to your GOCDB account "
                . "(ID string: $primaryIdString, authentication type: $primaryAuthType) has just been made on GOCDB."
            ."\n\nThe new authentication details are:"
            ."\nID string: $currentIdString"
            ."\nAuthentication type: $currentAuthType";

            if ($registered) {
                $body .= "\n\nThe new authentication method is currently associated with a second registered account."
                ." If linking is sucessful, any roles currently associated with this second account (ID string: $currentIdString)"
                ." will be requested for your GOCDB account (ID string: $primaryIdString)."
                ." These roles will be approved automatically if either account has permission to do so."
                ."\n\n The second account will then be deleted.";
            }

            $body .= "\n\nIf you wish to associate your GOCDB account with this authentication method, please validate your request by clicking on the link below:\n"
            ."$link".
            "\n\nIf you did not create this request in GOCDB, please immediately contact gocdb-admins@mailman.egi.eu";

        } elseif ($requestType === 'recover') {

            $subject = "Validation of recovering your GOCDB account";

            $body = "Dear GOCDB User,\n\n"
            ."A request to retrieve and associate your GOCDB account (ID string: $primaryIdString, authentication type: $primaryAuthType) "
                . "and privileges with a new ID string has just been made on GOCDB."
            ."\n\nThe new ID string is: $currentIdString";

            if ($registered) {
                $body .= "\n\nThe new ID string is currently associated with a second registered account."
                ." If recovery is sucessful, any roles currently associated with this second account (ID string: $currentIdString)"
                ." will be requested for your GOCDB account (ID string: $primaryIdString)."
                ." These roles will be approved automatically if either account has permission to do so."
                ."\n\n The second account will then be deleted.";
            }

            $body .= "\n\nIf you wish to associate your GOCDB account with this ID string, please validate your request by clicking on the link below:\n"
            ."$link".
            "\n\nIf you did not create this request in GOCDB, please immediately contact gocdb-admins@mailman.egi.eu";

        } else {
            throw new \Exception("Invalid request type");
        }
        return array('subject'=>$subject, 'body'=>$body);
    }

    /**
     * Sends a confimation email to the user
     *
     * @param \User $primaryUser user who will have new log-in added
     * @param type $confirmationCode generated confirmation code
     * @param type $primaryIdString ID string $primaryUser
     * @param type $primaryAuthType auth type of $primaryIdString
     * @param type $currentIdString ID string for current user
     * @param type $currentAuthType auth type of $currentIdString
     * @param type $registered true if the current user is registered
     * @throws \Exception
     */
    private function sendConfirmationEmail(\User $primaryUser, $confirmationCode, $primaryIdString, $primaryAuthType, $currentIdString, $currentAuthType, $requestType, $registered) {
        $portalUrl = \Factory::getConfigService()->GetPortalURL();
        $link = $portalUrl."/index.php?Page_Type=User_Validate_Identity_Link&c=".$confirmationCode;
        $composedEmail = $this->composeEmail($primaryIdString, $primaryAuthType, $currentIdString, $currentAuthType, $requestType, $link, $registered);
        $subject = $composedEmail['subject'];
        $body = $composedEmail['body'];

        // If "sendmail_from" is set in php.ini, use second line ($headers = '';):
        $headers = "From: no-reply@goc.egi.eu";

        // Mail command returns boolean. False if message not accepted for delivery.
        if (!mail($primaryUser->getEmail(), $subject, $body, $headers)) {
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