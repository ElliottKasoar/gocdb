<?php

/**
 * A custom Key=Value pair (extension property) used to augment a {@see User}
 * object with additional attributes. These properties are used to store
 * user identifiers, with keys storing authentication realms and values
 * storing ID strings.
 * <p>
 * A unique constraint is defined on the DB preventing duplicate keys for a given user.
 * This allows the pairs to be upadated based enitrely on the key name and entity
 * unique identifier, rather than needing the custom property id.
 * <p>
 * When the owning parent User is deleted, its UserProperties
 * are also cascade-deleted.
 *
 * @Entity @Table(name="User_Properties", uniqueConstraints={@UniqueConstraint(name="user_keypairs", columns={"parentUser_id", "keyName"})})
 */
class UserProperty {

    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /**
     * Bidirectional - Many UserProperties (SIDE THAT OWNS FK)
     * can be linked to one User (OWNING ORM SIDE).
     *
     * @ManyToOne(targetEntity="User", inversedBy="userProperties")
     * @JoinColumn(name="parentUser_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $parentUser = null;

    /** @Column(type="string", nullable=false) */
    protected $keyName = null;

    /** @Column(type="string", nullable=true, unique=true) */
    protected $keyValue = null;

    public function __construct() {
    }

    /**
     * Get the owning parent {@see User}. When the User is deleted,
     * these properties are also cascade deleted.
     * @return \User
     */
    public function getParentUser() {
        return $this->parentUser;
    }

    /**
     * Get the key name, usually a simple alphanumeric name, but this is not
     * enforced by the entity.
     * @return string
     */
    public function getKeyName() {
        return $this->keyName;
    }

    /**
     * Get the key value, can contain any char.
     * @return String
     */
    public function getKeyValue() {
        return $this->keyValue;
    }

    /**
     * @return int The PK of this entity or null if not persisted
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Do not call in client code, always use the opposite
     * <code>$user->addUserPropertyDoJoin($userProperty)</code>
     * instead which internally calls this method to keep the bidirectional
     * relationship consistent.
     * <p>
     * This is the OWNING side of the ORM relationship so this method WILL
     * establish the relationship in the database.
     *
     * @param \User $user
     */
    public function _setParentUser(\User $user) {
        $this->parentUser = $user;
    }

    /**
     * The custom keyname of this key=value pair.
     * This value should be a simple alphanumeric name without special chars, but
     * this is not enforced here by the entity.
     * @param string $keyName
     */
    public function setKeyName($keyName) {
        $this->keyName = $keyName;
    }

    /**
     * The custom value of this key=value pair.
     * This value can contain any chars.
     * @param string $keyValue
     */
    public function setKeyValue($keyValue) {
        $this->keyValue = $keyValue;
    }

}
