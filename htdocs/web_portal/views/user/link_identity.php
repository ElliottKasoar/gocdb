<div class="rightPageContainer">
    <h1>Link Identity or Recover an Account</h1>
    <br />
    <div>
        <h2>What is identity linking?</h2>
        <ul>
            <li>
                You can use this process to add your current authentication method as a way to log in to an existing account.
            </li>
            <li>
                This allows access to a single account through two or more identifiers.
            </li>
            <li>
                You must have access to the email address associated with the account being linked.
            </li>
            <li>
                <b>Your current authentication type must be different to any authentication types aleady associated
                with the account being linked.</b>
            </li>
        </ul>

        <h2>What is account recovery?</h2>
        <ul>
            <li>
                If your identifier has changed, you can use this process to update it and regain control of your old account.
            </li>
            <li>
                You must have access to the email address associated with your old account.
            </li>
            <li>
                <b>Your current authentication type must be the same as the authentication type you enter for your old account.</b>
            </li>
    </div>

    <br />

    <div class=Form_Holder>
        <div class=Form_Holder_2>
            <form name="Link_Cert_Req" action="index.php?Page_Type=Link_Identity"
                  method="post" class="inputForm" id="linkIdentityForm">
                <span>
                    Your current ID string (e.g. certificate DN) is: <label><?=$params['IDSTRING'];?></label>
                </span>
                <br />
                <span>
                    Your current authentication type is: <label id="currentAuthType"><?=$params['CURRENTAUTHTYPE'];?></label>
                </span>
                <br />
                <br />

                <h2>Details of account to be linked or recovered</h2>
                <br />

                <div class="form-group" id="authTypeGroup">
                    <label class="control-label" for="authType">Authentication type *</label>
                    <div class="controls">
                        <select
                            class="form-control"
                            name="AUTHTYPE" id="authType"
                            size=<?=count($params['AUTHTYPES']);?>
                            onchange="updateWarningMessage(); formatAuthType(); formatIdStringFromAuth();">
                            <?php
                                foreach ($params['AUTHTYPES'] as $authType) {
                                    echo "<option onclick=\"updateWarningMessage(); formatAuthType(); formatIdStringFromAuth();\" value=\"";
                                    echo $authType . "\">" . $authType . "</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <br />
                    <span class="auth-message hidden" id="authTypeLabel1"></span>
                    <br />
                    <span class="auth-message hidden" id="authTypeLabel2"></span>
                    <br />
                    <span class="auth-message auth-warning hidden" id="authTypeLabel3"></span>
                    <br id="authPlaceholder3">
                </div>

                <div class="form-group" id="primaryIdStringGroup">
                    <label class="control-label" for="primaryIdString">ID string *
                        <label class="input_syntax" >(e.g. for IGTF X509 Cert: /C=.../OU=.../...)</label>
                    </label>

                    <div class="controls">
                        <input class="form-control" type="text" name="PRIMARYIDSTRING" id="primaryIdString" onchange="formatIdString();" disabled/>
                    </div>
                    <span id="idStringError" class="label label-danger hidden"></span>
                    <br id="idStringPlaceholder">
                </div>

                <br />

                <div class="form-group" id="emailGroup">
                    <label class="control-label" for="email">E-mail address *
                        <label class="input_syntax" >(valid e-mail format)</label>
                    </label>

                    <div class="controls">
                        <input class="form-control" type="text" name="EMAIL" id="email" onchange="formatEmail();"/>
                    </div>
                    <span id="emailError" class="label label-danger hidden"></span>
                    <br id="emailPlaceholder">
                </div>

                <h2>What happens next?</h2>
                <div>
                    <li>
                        Once you have submitted this form, you will receive a confirmation
                        e-mail containing instructions on how to validate the request.
                    </li>
                    <li>
                        Any existing linking or recovery requests you have made will expire.
                    </li>

                    <li class="hidden" id="linkingDetails"> If you successfully validate your linking request:
                        <ul>
                            <li <?= $params['REGISTERED'] ? "" : "hidden"; ?>>
                                Any roles you have with the account you are currently using will be requested
                                for the account being linked.
                            </li>
                            <li <?= $params['REGISTERED'] ? "" : "hidden"; ?>>
                                These roles will be approved automatically if either account has permission to do so.
                            </li>
                            <li>
                                Your current ID string and authentication type will be added to the account being linked.
                            </li>
                            <li <?= $params['REGISTERED'] ? "" : "hidden"; ?>>
                                <b>The account you are currently using will then be deleted.</b>
                            </li>
                        </ul>
                    </li>

                    <li id="recoveryDetails"> If you successfully validate your recovery request:
                        <ul>
                            <li <?= $params['REGISTERED'] ? "" : "hidden"; ?>>
                                Any roles you have with the account you are currently using will be requested for your old account.
                            </li>
                            <li <?= $params['REGISTERED'] ? "" : "hidden"; ?>>
                                These roles will be approved automatically if either account has permission to do so.
                            </li>
                            <li>
                                The ID string of your old account that matches your current authentication type will be updated to your current ID string.
                            </li>
                            <li>
                                <b>You will no longer be able to log in with your old ID string</b>.
                            </li>
                            <li <?= $params['REGISTERED'] ? "" : "hidden"; ?>>
                                <b>The account you are currently using will then be deleted.</b>
                            </li>
                        </ul>
                    </li>

                    <li class="hidden invis" id="requestPlaceholder"></li>
                </div>

                <br />

                <button type="submit" id="submitRequest_btn" class="btn btn-default" style="width: 100%" value="Execute" disabled>Submit</button>

            </form>
        </div>
    </div>
</div>

<style>
    .auth-warning {
        color: red;
    }
    .auth-warning-severe {
        color: red;
        font-style: italic;
    }
    .invis {
        opacity: 0;
    }
</style>

<script type="text/javascript" src="<?php echo \GocContextPath::getPath() ?>javascript/linking.js"></script>