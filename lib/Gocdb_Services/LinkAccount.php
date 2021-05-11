<?php
namespace org\gocdb\services;

require_once __DIR__ . '/AbstractEntityService.php';

class LinkAccount extends AbstractEntityService {

    /**
     * Validates an account link request
     * @param string $primaryIdString
     * @param string $authType
     * @param string $email
     */
    public function validate($idString, $authType, $email) {

        $serv = \Factory::getUserService();

        // Ideally, the id string and auth type match a user property
        $user = $serv->getUserByPrincipleAndType($idString, $authType);
        if($user === null) {
            // If no valid user properties, check certificateDNs
            $user = $serv->getUserFromDn($idString);
            if($user === null) {
                throw new \Exception("Cannot find user with id $idString and auth type $authType");
            }
        }

        // Check the given email address matches the one given
        if(strcasecmp($user->getEmail(), $email)) {
            throw new \Exception("E-mail address doesn't match id");
        }
    }

    /**
     * Processes an account link request
     * @param \User $primaryUser
     * @param \User $currentUser
     */
    public function newLinkAccountRequest($currentIdString, $givenEmail, $primaryIdString, $primaryAuthType, $currentAuthType) {

        $serv = \Factory::getUserService();

        // Ideally, the id string and auth type match a user property
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
        // Referred to as "secondaryUser" in LinkAccountRequest
        // User may not be registered so don't throw exception if null/no email
        $currentUser = $serv->getUserByPrinciple($currentIdString);
        
        // Check the portal is not in read only mode, throws exception if it is. If portal is read only, but the user linking to another account is an admin, we will still be able to proceed.
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($currentUser);

        // Check if there has already been a request to link current id, if there has,
        // remove it. This must be in a seperate try catch block to the new one,
        // to prevent constraint violations
        // Currently request to link multiple ids to one primary id are permitted
        $previousRequest = $this->getLinkAccountRequestByIdString($currentIdString);
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

        // Create link account request
        $linkAccountReq = new \LinkAccountRequest($primaryUser, $currentUser, $code, $primaryIdString, $currentIdString, $primaryAuthType, $currentAuthType);

        // Recovery or account linking
        if ($primaryAuthType === $currentAuthType) {
            $requestType = 'recover';
        } else {
            $requestType = 'link';
        }

        //apply change
        try {
            $this->em->getConnection()->beginTransaction();
            $this->em->persist($linkAccountReq);
            $this->em->flush();

            // Send email (before commit - if it fails we'll need a rollback)
            // Todo: add auth types to email?
            $this->sendConfirmationEmail($primaryUser, $code, $primaryIdString, $currentIdString, $requestType);

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
     * Gets a link account request from the database based on userid
     * Not currently in use
     * @param integer $userId userid of the request to be linked
     * @return arraycollection
     */
    public function getLinkAccountRequestByUserId($userId) {
        $dql = "SELECT l
                FROM LinkAccountRequest l
                JOIN l.primaryUser u
                WHERE u.id = :id";

        $request = $this->em
            ->createQuery($dql)
            ->setParameter('id', $userId)
            ->getOneOrNullResult();

        return $request;
    }

    /**
     * Gets a link account request from the database based on current id string
     * Id string my be present as primary or secondary user
     * @param string $idString id string of user to be linked in primary account
     * @return arraycollection
     */
    public function getLinkAccountRequestByIdString($idString) {
        $dql = "SELECT l
                FROM LinkAccountRequest l
                WHERE l.primaryIdString = :idString
                OR l.secondaryIdString = :idString";

        $request = $this->em
            ->createQuery($dql)
            ->setParameter('idString', $idString)
            ->getOneOrNullResult();

        return $request;
    }

    /**
     * Gets an account link request from the database based on the confirmation code
     * @param string $code confirmation code of the request being retrieved
     * @return arraycollection
     */
    public function getLinkAccountRequestByConfirmationCode($code) {
        $dql = "SELECT l
                FROM LinkAccountRequest l
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
     * @param type $primaryIdString id string $primaryUser
     * @param type $currentIdString id string for current user
     * @param type $requestType account recovery or linking
     * @param type $link to be clicked
     * @return arraycollection
     */
    private function composeEmail($primaryIdString, $currentIdString, $requestType, $link) {

        if ($requestType === 'link') {

            $subject = "Validation of linking your GOCDB account";

            $body = "Dear GOCDB User,\n\n"
            ."A request to link your GOCDB account (account ID: $primaryIdString) and privileges with another "
                . "account ID has just been made on GOCDB."
            ."\n\nThe second account ID is: $currentIdString"
            ."\n\nIf you wish to associate your GOCDB account with this account ID, please validate your request by clicking on the link below:\n"
            ."$link".
            "\n\nIf you did not create this request in GOCDB, please immediately contact gocdb-admins@mailman.egi.eu";

        } elseif ($requestType === 'recover') {

            $subject = "Validation of recovering your GOCDB account";

            $body = "Dear GOCDB User,\n\n"
            ."A request to retrieve and associate your GOCDB account (account ID: $primaryIdString) and privileges with a new "
                . "account ID has just been made on GOCDB."
            ."\n\nThe new account ID is: $currentIdString"
            ."\n\nIf you wish to associate your GOCDB account with this account ID, please validate your request by clicking on the link below:\n"
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
     * @param type $primaryIdString id string $primaryUser
     * @param type $currentIdString id string for current user
     * @throws \Exception
     */
    private function sendConfirmationEmail(\User $primaryUser, $confirmationCode, $primaryIdString, $currentIdString, $requestType) {
        $portalUrl = \Factory::getConfigService()->GetPortalURL();
        $link = $portalUrl."/index.php?Page_Type=User_Validate_Account_Link&c=".$confirmationCode;
        $composedEmail = $this->composeEmail($primaryIdString, $currentIdString, $requestType, $link);
        $subject = $composedEmail['subject'];
        $body = $composedEmail['body'];

        // If "sendmail_from" is set in php.ini, use second line ($headers = '';):
        $headers = "From: no-reply@goc.egi.eu";

        // Mail command returns boolean. False if message not accepted for delivery.
        if (!mail($primaryUser->getEmail(), $subject, $body, $headers)) {
            throw new \Exception("Unable to send email message");
        }
    }

    public function confirmAccountLinking ($code, $currentId) {

        // Get the request
        $request = $this->getLinkAccountRequestByConfirmationCode($code);

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

        // Does primary user have user properties?
        $oldUser = false;
        if ($primaryUser->getCertificateDn() === $request->getPrimaryIdString()) {
            $oldUser = true;
            $propArrOld = array(array($request->getPrimaryAuthType(), $request->getPrimaryIdString()));
        }

        // Create new user property array to be added
        $propArr = array(array($request->getSecondaryAuthType(), $request->getSecondaryIdString()));
        require_once __DIR__ . '/User.php';

        // If recovering account overwrite user property
        if ($request->getPrimaryAuthType() === $request->getSecondaryAuthType()) {
            $preventOverwrite = false;
        } else {
            $preventOverwrite = true;
        }

        // Update user, remove request from table
        try{
            $this->em->getConnection()->beginTransaction();

            $serv = \Factory::getUserService();
            $serv->addProperties($primaryUser, $propArr, $primaryUser, $preventOverwrite);

            // If the primary user does not have user properties, need to overwrite certificateDn
            if ($oldUser) {
                $primaryUser->setCertificateDn($primaryUser->getId());
                // If linking (so not overwriting), add old id string as property
                if ($preventOverwrite) {
                    $serv->addProperties($primaryUser, $propArrOld, $primaryUser);
                }
            }

            $this->em->persist($primaryUser);

            if ($secondaryUser !== null) {
                \Factory::getRoleService()->mergeRoles($primaryUser, $secondaryUser);
                $this->em->remove($secondaryUser);
            }

            $this->em->remove($request);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch(\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }
}