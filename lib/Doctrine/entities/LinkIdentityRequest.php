<?php

/**
 * Records a new identity link request record.
 * <p>
 * Users may want to link two or more auth mechanisms to a single account.
 * This record stores the relevant data needed to do this, including
 * a confirmation code that is sent to the user's existing email
 * address - they need to provide the code to complete the identity linking transaction.
 *
 * @Entity @Table(name="LinkIdentityRequests")
 */
class LinkIdentityRequest {

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
    protected $primaryAuthType;

    /** @Column(type="string") */
    protected $secondaryAuthType;

    public function __construct(\User $primaryUser, $secondaryUser, $code, $primaryIdString, $secondaryIdString, $primaryAuthType, $secondaryAuthType) {
        $this->setPrimaryUser($primaryUser);
        $this->setSecondaryUser($secondaryUser);
        $this->setConfirmCode($code);
        $this->setPrimaryIdString($primaryIdString);
        $this->setSecondaryIdString($secondaryIdString);
        $this->setPrimaryAuthType($primaryAuthType);
        $this->setSecondaryAuthType($secondaryAuthType);
    }

    /**
     * @return int The PK of this entity or null if not persisted
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get the User which is having an identity added to it.
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
     * provide the code to complete the identity linking transaction.
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
     * Get the auth type of the primary user.
     * @return string
     */
    public function getPrimaryAuthType() {
        return $this->primaryAuthType;
    }

    /**
     * Get the auth type of the secondary user.
     * @return string
     */
    public function getSecondaryAuthType() {
        return $this->secondaryAuthType;
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
     * provide the code to complete the identity linking transaction.
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
     * Set the auth type of the primary user account.
     * @param string $primaryAuthType
     */
    public function setPrimaryAuthType($primaryAuthType) {
        $this->primaryAuthType = $primaryAuthType;
    }

    /**
     * Set the auth type of the secondary user account.
     * @param string $secondaryAuthType
     */
    public function setSecondaryAuthType($secondaryAuthType) {
        $this->secondaryAuthType = $secondaryAuthType;
    }
}