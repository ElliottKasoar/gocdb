<div class="rightPageContainer">
    <h1 class="Error">Error</h1>
    <p>You cannot recover or link your identifier to another account while registered with an account
    associated with multiple identifiers. Your current identifier is:</p>

    <p><?php echo "{$params['CURRENTAUTHTYPE']}: {$params['IDSTRING']}"?></p>

    <p>The other identifiers associated with this account are:</p>
    <ul>
        <?php foreach ($params['OTHERPROPERTIES'] as $prop) {
            echo "<li> {$prop->getKeyName()}: {$prop->getKeyValue()} </li>";
        } ?>
    </ul>

    <p>If you wish to associate your current identifier with another account,
    please unlink all other identifiers first.</p>
    <p>If you wish to add new identifiers to this account, please
    access GOCDB while authenticated with the new identifier.</p>
</div>