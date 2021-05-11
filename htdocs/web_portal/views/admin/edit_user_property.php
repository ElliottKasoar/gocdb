<div class="rightPageContainer">
    <h1>Update User Property</h1>
    <br />
    <br />
    The ID string for
    <b><?php echo xssafe($params['Title']) ." ". xssafe($params['Forename']) ." ". xssafe($params['Surname']) ?></b>
    is:
    <br />
    <?php xecho($params['IdString']) ?>
    <br />
    <br />
    <form class="inputForm" method="post" action="index.php?Page_Type=Admin_Edit_User_Property" name="editSType">
        <span class="input_name">New ID String</span>
        <input type="text" value="<?php xecho($params['IdString']) ?>" name="IDSTRING" class="input_input_text">
        <input class="input_input_hidden" type="hidden" name="ID" value="<?php echo $params['ID'] ?>" />
        <br />
        <input type="submit" value="Update ID String" class="input_button">
    </form>
</div>