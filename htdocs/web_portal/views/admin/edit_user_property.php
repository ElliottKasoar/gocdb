<div class="rightPageContainer">
    <h1>Update User Property</h1>
    <br />
    <br />
    The
    <b><?php echo xssafe($params['authType'])?></b>
    ID string for
    <b><?php echo xssafe($params['Title']) ." ". xssafe($params['Forename']) ." ". xssafe($params['Surname']) ?></b>
    is:
    <br />
    <?php xecho($params['IdString']) ?>
    <br />
    <br />
    <div class=<?= $params['dnWarning'] ? "" : "hidden"; ?>>
        <span style="color: red">Warning: This user does not have user properties!</span>
        <br />
        <br />
    </div>
    <form class="inputForm" method="post" action="index.php?Page_Type=Admin_Edit_User_Property" name="editSType">
        <span class="input_name">New ID String</span>
        <input type="text" value="<?php xecho($params['IdString']) ?>" name="IdString" class="input_input_text">
        <br />

        <div>
            <span class="input_name">New Authentication Type:</span>
            <select name="authType" class="input_input_text">
                <?php
                    foreach ($params['authTypes'] as $authType) {
                        echo "<option value=\"". $authType . "\">" . $authType. "</option>";
                    }
                ?>
            </select>
        </div>

        <input class="input_input_hidden" type="hidden" name="ID" value="<?php echo $params['ID'] ?>" />
        <input class="input_input_hidden" type="hidden" name="propertyId" value="<?php echo $params['propertyId'] ?>" />
        <input type="submit" value="Update ID String" class="input_button">
    </form>
</div>