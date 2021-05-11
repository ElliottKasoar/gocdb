<div class="rightPageContainer">
    <div class=Form_Holder>
        <div class=Form_Holder_2>
            <form name="Link_Cert_Req" action="index.php?Page_Type=Link_Account"
                  method="post" class="inputForm">
                <h1>Link An Account</h1>
                Your current Account ID (e.g. certificate DN) is: <?php echo $params['IDSTRING'];?>
                <br/>
                <br/>

                <span class="">Authentication type:</span>
                <select class="" name="AUTHTYPE">
                    <?php
                        foreach ($params['AUTHTYPES'] as $authType) {
                            echo "<option value=\"" . $authType . "\">" . $authType . "</option>";
                        }
                    ?>
                </select>
                <br/>
                <br/>
                <span class="input_name">Account ID to be linked *
                    <span class="input_syntax" >(e.g. if DN: /C=.../OU=.../...)</span>
                </span>
                <input class="input_input_text" type="text" name="PRIMARYID" />

                <span class="input_name">E-mail address of account to be linked *
                    <span class="input_syntax" >(valid e-mail format)</span>
                </span>
                <input class="input_input_text" type="text" name="EMAIL" />

                <span class="input_name">
                    Once you have submitted this form, you will receive a confirmation
                    e-mail containing instructions on how to validate the request.
                </span>

                <input class="input_button" type="submit" value="Execute" />
            </form>
        </div>
    </div>
</div>