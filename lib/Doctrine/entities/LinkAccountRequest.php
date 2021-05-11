<?php

/**
 * Records a new account link request record.
 * <p>
 * Users may want to link two or more accounts with different auth mechanisms.
 * This record stores the relevant data needed to do this, including
 * a confirmation code that is sent to the user's existing email
 * address - they need to provide the code to complete the account linking transaction.
 *
 * @Entity @Table(name="LinkAccountRequests")
 */
class LinkAccountRequest {

    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /**
     * @OneToOne(targetEntity="User")
     * @JoinColumn(name="primary_user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $primaryUser;

    /** @Column(type="string") */
    protected $primaryIdString;

    /**
     * @OneToOne(targetEntity="User")
     * @JoinColumn(name="secondary_user_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected $secondaryUser;

    /** @Column(type="string") */
    protected $secondaryIdString;

    /** @Column(type="string") */
    protected $confirmCode;

    /** @Column(type="string") */
    protected $authType;

    public function __construct(\User $primaryUser, $secondaryUser, $code, $primaryIdString, $secondaryIdString, $authType) {
        $this->setPrimaryUser($primaryUser);
        $this->setSecondaryUser($secondaryUser);
        $this->setConfirmCode($code);
        $this->setPrimaryIdString($primaryIdString);
        $this->setSecondaryIdString($secondaryIdString);
        $this->setAuthType($authType);
    }

    /**
     * @return int The PK of this entity or null if not persisted
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get the User which is having an account added to it.
     * @return \User
     */
    public function getPrimaryUser() {
        return $this->primaryUser;
    }

    /**
     * Get the User whose log-in is being added to the primary User.
     * Can be null if user not yet registered.
     * @return \User
     */
    public function getSecondaryUser() {
        return $this->secondaryUser;
    }

    /**
     * Return the confirmation code that is used to authenticate the user.
     * This code is sent to the primary user's email address - they need to
     * provide the code to complete the account linking transaction.
     * @return string
     */
    public function getConfirmCode() {
        return $this->confirmCode;
    }

    /**
     * Get the ID string of the user who is having a new ID string added.
     * @return string
     */
    public function getPrimaryIdString() {
        return $this->primaryIdString;
    }

    /**
     * Get the ID string to be added to the primary user.
     * @return string
     */
    public function getSecondaryIdString() {
        return $this->secondaryIdString;
    }

    /**
     * Get the auth type to be added to the primary user.
     * @return string
     */
    public function getAuthType() {
        return $this->authType;
    }

    /**
     * Set the primary user.
     * @param \User $primaryUser
     */
    public function setPrimaryUser($primaryUser) {
        $this->primaryUser = $primaryUser;
    }

    /**
     * Set the secondary user.
     * @param \User $secondaryUser
     */
    public function setSecondaryUser($secondaryUser) {
        $this->secondaryUser = $secondaryUser;
    }

    /**
     * Set the confirmation code that is used to authenticate the user.
     * This code is sent to the primary user's email address - they need to
     * provide the code to complete the account linking transaction.
     * @param string $code
     */
    public function setConfirmCode($code) {
        $this->confirmCode = $code;
    }

    /**
     * Set the ID string of the primary user account.
     * @param string $primaryIdString
     */
    public function setPrimaryIdString($primaryIdString) {
        $this->primaryIdString = $primaryIdString;
    }

    /**
     * Set the ID string of the secondary user account.
     * @param string $secondaryIdString
     */
    public function setSecondaryIdString($secondaryIdString) {
        $this->secondaryIdString = $secondaryIdString;
    }

    /**
     * Set the auth type of the secondary user account.
     * @param string $authType
     */
    public function setAuthType($authType) {
        $this->authType = $authType;
    }
}