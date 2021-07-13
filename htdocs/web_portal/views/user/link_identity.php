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

            <li> If linking is successful:
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

            <li> If recovery is successful:
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
                    <li <?= $params['REGISTERED'] ? "" : "hidden"; ?>>
                        <b>The account you are currently using will then be deleted.</b>
                    </li>
                </ul>
            </li>

    </div>
    <br/>
    <div class=Form_Holder>
        <div class=Form_Holder_2>
            <form name="Link_Cert_Req" action="index.php?Page_Type=Link_Identity"
                  method="post" class="inputForm" id="linkIdentityForm">
                <span>Your current ID string (e.g. certificate DN) is: <?=$params['IDSTRING'];?></span>
                <br/>
                <span>Your current authentication type is: <?=$params['CURRENTAUTHTYPE'];?></span>
                <br/>
                <br/>

                <h2>Details of account to be linked or recovered</h2>
                <br/>

                <div class="form-group" id="authTypeGroup">
                    <label class="control-label" for="authType">Authentication type *</label>
                    <div class="controls">
                        <select
                            class="form-control"
                            name="AUTHTYPE" id="authType"
                            size=<?=sizeof($params['AUTHTYPES']);?>
                            onchange="updateWarningMessage(); formatAuthType(); formatIdFromAuth();">
                            <?php
                                foreach ($params['AUTHTYPES'] as $authType) {
                                    echo "<option onclick=\"updateWarningMessage(); formatAuthType(); formatIdFromAuth();\" value=\"";
                                    echo $authType . "\">" . $authType . "</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <br/>
                    <span class="auth-message hidden" id="authTypeLabel1"></span>
                    <br/>
                    <span class="auth-message hidden" id="authTypeLabel2"></span>
                    <br/>
                    <span class="auth-message auth-warning-severe hidden" id="authTypeLabel3"></span>
                    <br class="authPlaceholder" id="authPlaceholder3" />
                </div>

                <div class="form-group" id="primaryIdGroup">
                    <label class="control-label" for="primaryId">ID string *
                        <label class="input_syntax" >(e.g. for IGTF X509 Cert: /C=.../OU=.../...)</label>
                    </label>

                    <div class="controls">
                        <input class="form-control" type="text" name="PRIMARYID" id="primaryId" onchange="formatId();" disabled/>
                    </div>
                    <span id="idError" class="label label-danger hidden"></span>
                    <br id="idPlaceholder" />
                </div>

                <br/>

                <div class="form-group" id="emailGroup">
                    <label class="control-label" for="primaryId">E-mail address *
                        <label class="input_syntax" >(valid e-mail format)</label>
                    </label>

                    <div class="controls">
                        <input class="form-control" type="text" name="EMAIL" id="email" onchange="formatEmail();"/>
                    </div>
                    <span id="emailError" class="label label-danger hidden"></span>
                    <br id="emailPlaceholder" />
                </div>

                <span class="input_name">
                    Once you have submitted this form, you will receive a confirmation
                    e-mail containing instructions on how to validate the request.
                </span>
                <br/>

                <button type="submit" id="submitRequest_btn" class="btn btn-default" style="width: 100%" value="Execute" disabled>Submit</button>

            </form>
        </div>
    </div>
</div>

<style>
    .hidden {
        display: none;
    }
    .auth-warning {
        color: red;
    }
    .auth-warning-severe {
        color: red;
        font-style: italic;
    }
</style>

<script type="text/javascript">

    $(document).ready(function() {
        // Add the jQuery form change event handlers
        $("#linkIdentityForm").find(":input").change(function() {
            validate();
        });
    });

    /**
     * Updates the authentication type message
     * Message depends on whether the selected auth type is the same as the auth type currently in use
     * If auth types are the same, different severity of warnings depending on which type
     *
     * @returns {null}
     */
    function updateWarningMessage() {
        var selectedAuthType = $('#authType').val();
        var currentAuthType = "<?=$params['CURRENTAUTHTYPE'];?>";

        var authTypeText1 = "";
        var authTypeText2 = "";
        var authTypeText3 = "";
        if (selectedAuthType !== null && selectedAuthType !== "") {
            $('#authTypeLabel1').removeClass("hidden");
            $('#authTypeLabel2').removeClass("hidden");
        } else {
            $('#authTypeLabel1').addClass("hidden");
            $('#authTypeLabel2').addClass("hidden");
        }

        // Different warnings if selected auth type is same as method currently in use
        if (selectedAuthType === currentAuthType) {
            authTypeText1 += '"' + selectedAuthType + '" is the same as your current authentication type.';
            authTypeText2 += ' Proceeding will begin the account recovery process.';
            authTypeText2 += ' If this is successful, you will no longer be able to log in using your old ID string.';

            // Stronger warning for certain types. Certificates will be less severe?
            if (selectedAuthType === "IGTF X509 Cert") {
                authTypeText3 += 'Certificates sometimes expire...';
                $('#authTypeLabel3').removeClass("hidden");
                $('#authPlaceholder3').addClass("hidden");
            } else {
                $('#authTypeLabel3').addClass("hidden");
                $('#authPlaceholder3').removeClass("hidden");
            }
            $('#authTypeLabel2').addClass("auth-warning");

        } else {
            authTypeText1 += '"' + selectedAuthType + '" is different to your current authentication type.';
            authTypeText2 += 'Proceeding will begin the identity linking process.'
            $('#authTypeLabel2').removeClass("auth-warning");
            $('#authTypeLabel3').addClass("hidden");
            $('#authPlaceholder3').removeClass("hidden");
        }

        $('#authTypeLabel1').text(authTypeText1);
        $('#authTypeLabel2').text(authTypeText2);
        $('#authTypeLabel3').text(authTypeText3);
    }

    function getRegExAuthType() {
        return regExAuthType = /^[^`'\";<>]{0,4000}$/;
    }

    function getRegExId() {
        var inputAuthType = '#authType';
        var authType = $(inputAuthType).val();

        // Start with slash only?
        if (authType === "IGTF X509 Cert") {
            // var regExId = /^(\/[a-zA-Z]+=[a-zA-Z0-9\-\_\s\.@,'\/]+)+$/;
            // var regExId = /^(\/[a-zA-Z]+=[a-zA-Z0-9\-\_\s\.@,'\/]+)+$/;
            var regExId = /^\/.+$/;

        // End with @iris.iam.ac.uk only?
        } else if (authType === "IRIS IAM - OIDC") {
            // var regExId = /^([a-f0-9]{8}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{12})@iris\.iam\.ac.uk$/;
            var regExId = /^.+@iris\.iam\.ac.uk$/;

        // Remove later
        } else if (authType === "FAKE") {
            var regExId = /^[^`'\";<>]{0,4000}$/;

        // Remove later?
        } else {
            var regExId = /^$/;
        }
        return regExId;
    }

    function getRegExEmail() {
        return regExEmail = /^(([0-9a-zA-Z]+[-._])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}){1}$/;
    }

    // Validate all inputs on any change
    // Enable/disabled ID string input based on selection of auth type
    // Enable/disable and format submit button based on all other inputs
    function validate() {
        var idValid = false;
        var emailValid = false;
        var authTypeValid = false;

        // Validate auth type
        var regExAuthType = getRegExAuthType();
        var inputAuthType = '#authType';
        authTypeValid = isInputValid(regExAuthType, inputAuthType);
        authTypeEmpty = isInputEmpty(inputAuthType);

        // Validate ID string
        var regExId = getRegExId();
        var inputId = '#primaryId';
        idValid = isInputValid(regExId, inputId);
        idEmpty = isInputEmpty(inputId);

        // Validate email
        var regExEmail = getRegExEmail();
        var inputEmail = '#email';
        emailValid = isInputValid(regExEmail, inputEmail);
        emailEmpty = isInputEmpty(inputEmail);

        // Set the button based on validate status
        if(authTypeValid && idValid && emailValid && !authTypeEmpty && !idEmpty && !emailEmpty) {
            $('#submitRequest_btn').addClass('btn btn-success');
            $('#submitRequest_btn').prop('disabled', false);
        } else {
            $('#submitRequest_btn').removeClass('btn btn-success');
            $('#submitRequest_btn').addClass('btn btn-default');
            $('#submitRequest_btn').prop('disabled', true);
        }
    }

    // Check if user input is valid based on regex
    // Input is regex and a selector e.g. '#id'
    // Returns boolean flag (true if valid)
    function isInputValid(regEx, input) {
        var inputValue = $(input).val();
        var inputValid = false;
        if(regEx.test(inputValue) !== false) {
            inputValid=true;
        }
        return inputValid;
    }

    // Check if user input is empty
    // Input is selector e.g. '#id'
    // Returns boolean flag (true if empty)
    function isInputEmpty(input) {
        var inputValue = $(input).val();
        var inputEmpty = true;
        if(inputValue) {
            inputEmpty=false;
        }
        return inputEmpty;
    }

    // Enable ID string input if auth type is valid
    function enableId(valid, empty) {
        // Disable/enable ID string based on auth type validity
        if(valid && !empty) {
            $('#primaryId').prop('disabled', false);
        } else {
            $('#primaryId').prop('disabled', true);
        }
    }

    // Format authentication type input on selecting a value based on validation
    // Selections should be successful, but invalid/empty formating retained
    function formatAuthType() {
        var regEx = getRegExAuthType();
        var input = '#authType';
        var valid = isInputValid(regEx, input);
        var empty = isInputEmpty(input);

        if(valid && !empty) {
            $('#authTypeGroup').addClass("has-success");
            $('#authTypeGroup').removeClass("has-error");
        } else {
            $('#authTypeGroup').removeClass("has-success");
            $('#authTypeGroup').addClass("has-error");
        }

        // Enable ID string input if auth type is valid
        enableId(valid, empty);
    }

    // Format ID string input on selection of auth type based on validation
    // Only apply if value has been entered (valid/invalid based on regex)
    function formatIdFromAuth() {
        var regEx = getRegExId();
        var input = '#primaryId';
        var valid = isInputValid(regEx, input);
        var empty = isInputEmpty(input)

        if (!empty) {
            if (valid) {
                $('#primaryIdGroup').addClass("has-success");
                $('#primaryIdGroup').removeClass("has-error");
                $("#idError").addClass("hidden");
                $("#idPlaceholder").removeClass("hidden");
            } else {
                $('#primaryIdGroup').removeClass("has-success");
                $('#primaryIdGroup').addClass("has-error");
                $("#idError").removeClass("hidden");
                $("#idPlaceholder").addClass("hidden");
                $("#idError").text("You have entered an invalid id for the selected authentication method");
            }
        } else {
            $('#primaryIdGroup').removeClass("has-error");
            $("#idError").addClass("hidden");
            $("#idPlaceholder").removeClass("hidden");
        }
    }

    // Format ID string input on entering value based on validation
    // Error if invalid (regex) format or if nothing entered
    function formatId() {
        var regEx = getRegExId();
        var input = '#primaryId';
        var valid = isInputValid(regEx, input);
        var empty = isInputEmpty(input);

        if(valid && !empty) {
            $('#primaryIdGroup').addClass("has-success");
            $('#primaryIdGroup').removeClass("has-error");
            $("#idError").addClass("hidden");
            $("#idPlaceholder").removeClass("hidden");
        } else {
            $('#primaryIdGroup').removeClass("has-success");
            $('#primaryIdGroup').addClass("has-error");
            $("#idError").removeClass("hidden");
            $("#idPlaceholder").addClass("hidden");
        }
        if (!valid && !empty) {
            $("#idError").text("You have entered an invalid ID for the selected authentication method");
        } else if (empty) {
            $("#idError").text("Please enter the ID string of the account you want to be linked");
        }
    }

    // Format email input on entering a value based on validation
    // Error if invalid (regex) format or if nothing entered
    function formatEmail() {
        var regEx = getRegExEmail();
        var input = '#email';
        var valid = isInputValid(regEx, input);
        var empty = isInputEmpty(input);

        if(valid && !empty) {
            $('#emailGroup').addClass("has-success");
            $('#emailGroup').removeClass("has-error");
            $("#emailError").addClass("hidden");
            $("#emailPlaceholder").removeClass("hidden");
        } else {
            $('#emailGroup').removeClass("has-success");
            $('#emailGroup').addClass("has-error");
            $("#emailError").removeClass("hidden");
            $("#emailPlaceholder").addClass("hidden");
        }
        if(!valid && !empty) {
            $("#emailError").text("Please enter a valid email");
        } else if (empty) {
            $("#emailError").text("Please enter the account's email");
        }
    }
</script>