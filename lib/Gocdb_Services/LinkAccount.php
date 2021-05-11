<?php
namespace org\gocdb\services;

require_once __DIR__ . '/AbstractEntityService.php';

class LinkAccount extends AbstractEntityService {

    /**
     * Validates an account link request
     * @param string $primaryIdString
     * @param string $email
     */
    public function validate($idString, $email) {
        require_once __DIR__ . '/User.php';
        $userService = new \org\gocdb\services\User();
        $userService->setEntityManager($this->em);

        $user = $userService->getUserByPrinciple($idString);
        if($user === null) {
            $user = $userService->getUserFromDn($idString);
            if($user === null) {
                throw new \Exception("Can't find user with id $idString");
            }
        }

        if(strcasecmp($user->getEmail(), $email)) {
            throw new \Exception("E-mail address doesn't match id");
        }
    }

    /**
     * Processes an account link request for the passed user
     * @param \User $primaryUser
     * @param \User $currentUser
     */
    public function newLinkAccountRequest($currentIdString, $givenEmail, $primaryIdString, $authType) {

        // Get user who will have the current log-in method added, throw exception if they don't exist
        require_once __DIR__ . '/User.php';
        $userService = new \org\gocdb\services\User();
        $userService->setEntityManager($this->em);
        
        // This user may not have properties added yet, but must exist
        $primaryUser = $userService->getUserByPrinciple($primaryIdString);
        if($primaryUser === null) {
            $primaryUser = $userService->getUserFromDn($primaryIdString);
            if($primaryUser === null) {
                throw new \Exception("Can't find user with id $primaryIdString");
            }
        }

        // Check the given email address matches the one given
        if(strcasecmp($primaryUser->getEmail(), $givenEmail)) {
            throw new \Exception("E-mail address doesn't match id");
        }

        // $currentUser is user making request
        // Referred to as "secondaryUser" in LinkAccountRequest
        // User may not be registered so don't throw exception if null/no email
        $currentUser = $userService->getUserByPrinciple($currentIdString);
        
        // Check the portal is not in read only mode, throws exception if it is. If portal is read only, but the user linking to another account is an admin, we will still be able to proceed.
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($currentUser);

        // Check if there has already been a request to link current id, if there has,
        // remove it. This must be in a seperate try catch block to the new one,
        // to prevent constraint violations
        $previousRequest = $this->getLinkAccountRequestByCurrentId($currentIdString);
        if(!is_null($previousRequest)){
            try{
                $this->em->getConnection()->beginTransaction();
                $this->em->remove($previousRequest);
                $this->em->flush();
                $this->em->getConnection()->commit();
            } catch(\Exception $e){
                $this->em->getConnection()->rollback();
                $this->em->close();
                throw $e;
            }
        }

        // Generate confirmation code
        $code = $this->generateConfirmationCode($primaryIdString);

        // Create link account request
        $linkAccountReq = new \LinkAccountRequest($primaryUser, $currentUser, $code, $primaryIdString, $currentIdString, $authType);

        //apply change
        try {
            $this->em->getConnection()->beginTransaction();
            $this->em->persist($linkAccountReq);
            $this->em->flush();

            // Send email (before commit - if it fails we'll need a rollback)
            $this->sendConfirmationEmail($primaryUser, $code, $primaryIdString, $currentIdString);

            $this->em->getConnection()->commit();
        } catch(\Exception $ex){
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $ex;
        }

    }

    private function generateConfirmationCode($idString){
        $confirm_code = rand(1, 10000000);
        $confirm_hash = sha1($idString.$confirm_code);
        return $confirm_hash;
    }

    /**
     * Gets a link account request from the database based on userid
     * @param integer $userId userid of the request to be linked
     * @return arraycollection
     */
    public function getLinkAccountRequestByUserId($userId){
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
     * @param string $idString id string of user to be linked in primary account
     * @return arraycollection
     */
    public function getLinkAccountRequestByCurrentId($idString){
        $dql = "SELECT l
                FROM LinkAccountRequest l
                WHERE l.secondaryIdString = :idString";

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
    public function getLinkAccountRequestByConfirmationCode($code){
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
     * Sends a confimation email to the user
     *
     * @param \User $primaryUser user who will have new log-in added
     * @param type $confirmationCode generated confirmation code
     * @param type $primaryIdString id string $primaryUser
     * @param type $currentIdString id string for current user
     * @throws \Exception
     */
    private function sendConfirmationEmail(\User $primaryUser, $confirmationCode, $primaryIdString, $currentIdString){
        $portal_url = \Factory::getConfigService()->GetPortalURL();
        // echo $portal_url;
       // die();

        $link = $portal_url."/index.php?Page_Type=User_Validate_Account_Link&c=".$confirmationCode;
        $subject = "Validation of linking your GOCDB account";
        $body = "Dear GOCDB User,\n\n"
            ."A request to link your GOCDB account (account ID: $primaryIdString) and privileges with another "
                . "account ID has just been made on GOCDB."
            ."\n\nThe second account ID is: $currentIdString"
            ."\n\nIf you wish to associate your GOCDB account with this account ID, please validate your request by clicking on the link below:\n"
            ."$link".
            "\n\nIf you did not create this request in GOCDB, please immediately contact gocdb-admins@mailman.egi.eu" ;
            ;
        // If "sendmail_from" is set in php.ini, use second line ($headers = '';):
        $headers = "From: no-reply@goc.egi.eu";
        // $headers = "";

        // Mail command returns boolean. False if message not accepted for delivery.
        if (!mail($primaryUser->getEmail(), $subject, $body, $headers)){
            throw new \Exception("Unable to send email message");
        }

        // echo $body;
    }

    public function confirmAccountLinking ($code, $currentId){
        // Get the request
        $request = $this->getLinkAccountRequestByConfirmationCode($code);

        // Check there is a result
        if(is_null($request)){
            throw new \Exception("Confirmation URL invalid. If you have submitted multiple requests for the same account, please ensure you have used the link in the most recent email");
        }

        $primaryUser = $request->getPrimaryUser();
        $secondaryUser = $request->getSecondaryUser();
        // $user = $userService->getUserByPrinciple($currentId);

        // Check the portal is not in read only mode, throws exception if it is. If portal is read only, but the user whos DN is being changed is an admin, we will still be able to proceed.
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($primaryUser);

        // Check the id currently being used by the user is same as in the request
        if($currentId != $request->getPrimaryIdString()){
            // throw new Exception("Your current ID does not match the one to which it was requested be linked. The link will only work once, if you have refreshed the page or clicked the link a second time you will see this messaage"); //TODO: reword
        }

        // Add property array
        $propArr = array(array($request->getAuthType(), $request->getSecondaryIdString()));
        require_once __DIR__ . '/User.php';
        $userService = new \org\gocdb\services\User();
        $userService->setEntityManager($this->em);
        $userService->addProperties($primaryUser, $propArr, $primaryUser);

        // deleteUser function - but won't work if not that user
        // deleteUser(\User $user, \User $currentUser = null)

        // Update user, remove request from table
        // Need to delete secondary user if exists?
        try{
            $this->em->getConnection()->beginTransaction();
            if ($secondaryUser !== null){
                $this->em->remove($secondaryUser);
            }
            $this->em->remove($request);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch(\Exception $e){
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }

    }
}