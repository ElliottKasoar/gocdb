<div class="rightPageContainer">
    <div class=Form_Holder>
        <div class=Form_Holder_2>
            <form name="Link_Cert_Req" action="index.php?Page_Type=Link_Account"
                  method="post" class="inputForm" id="linkAccountForm">
                <h1>Link An Account</h1>
                <span>Your current Account ID (e.g. certificate DN) is: <?=$params['IDSTRING'];?></span>
                <br/>
                <span>Your current authentication method is: <?=$params['CURRENTAUTHTYPE'];?></span>
                <br/>
                <br/>

                <div class="form-group" id="authTypeGroup">
                    <label class="control-label" for="authType">Authentication type:</label>
                    <div class="controls">
                        <select
                            class="form-control"
                            name="AUTHTYPE" id="authType" size=<?=sizeof($params['AUTHTYPES']);?>
                            onchange="updateWarningMessage(); formatAuthType(); formatIdFromAuth();">
                            <?php
                                foreach ($params['AUTHTYPES'] as $authType) {
                                    echo "<option value=\"" . $authType . "\">" . $authType . "</option>";
                                }
                            ?>
                        </select>
                    </div>
                    </br>
                    <span class="auth-message hidden" id="authTypeLabel1"></span>
                    </br>
                    <span class="auth-message hidden" id="authTypeLabel2"></span>
                    </br>
                    <span class="auth-message auth-warning-severe hidden" id="authTypeLabel3"></span>
                    <br class="authPlaceholder" id="authPlaceholder3" />
                </div>

                <div class="form-group" id="primaryIdGroup">
                    <label class="control-label" for="primaryId">Account ID to be linked *
                        <label class="input_syntax" >(e.g. if DN: /C=.../OU=.../...)</label>
                    </label>

                    <div class="controls">
                        <input class="form-control" type="text" name="PRIMARYID" id="primaryId" onchange="formatId();" disabled/>
                    </div>
                    <span id="idError" class="label label-danger hidden"></span>
                    <br id="idPlaceholder" />
                </div>

                <br/>

                <div class="form-group" id="emailGroup">
                    <label class="control-label" for="primaryId">E-mail address of account to be linked *
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
        font-style: italic;
    }
</style>

<script type="text/javascript">

    $(document).ready(function() {
        // Add the jQuery form change event handlers
        $("#linkAccountForm").find(":input").change(function() {
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
            authTypeText1 += selectedAuthType + ' is the same as your current authentication method. ';
            authTypeText2 += 'If you submit and confirm this request, your old id will be overwritten and ';
            authTypeText2 += 'you will no longer be able to login using it. Are you sure you wish to proceed?';

            // Stronger warning for certain types. Certificates will be less severe?
            if (selectedAuthType === "IGTF") {
                authTypeText3 += 'Certificates sometimes expire...';
                $('#authTypeLabel3').removeClass("hidden");
                $('#authPlaceholder3').addClass("hidden");
            } else {
                $('#authTypeLabel3').addClass("hidden");
                $('#authPlaceholder3').removeClass("hidden");
            }
            $('.auth-message').addClass("auth-warning");

        } else {
            authTypeText1 += selectedAuthType + ' is different to your current authentication method. ';
            authTypeText2 += 'If you submit and confirm this request, your current id will be added ';
            authTypeText2 += 'as a login method to the account associated with the id you enter.';
            $('.auth-message').removeClass("auth-warning");
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
        if (authType === "IGTF") {
            var regExId = /^(\/[a-zA-Z]+=[a-zA-Z0-9\-\_\s\.@,'\/]+)+$/;
            // var regExId = /^(\/[a-zA-Z]+=[a-zA-Z0-9\-\_\s\.@,'\/\)\(]+)+$/;
        } else if (authType === "IRIS IAM - OIDC") {
            var regExId = /^([a-f0-9]{8}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{12})@iris\.iam\.ac.uk$/;
        } else {
            var regExId = /^[^`'\";<>]{0,4000}$/;
        }
        return regExId;
    }

    function getRegExEmail() {
        return regExEmail = /^(([0-9a-zA-Z]+[-._])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}){1}$/;
    }

    // Validate all inputs on any change
    // Enable/disabled id string input based on selection of auth type
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

        // Validate id string
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

    // Enable id string input if auth type is valid
    function enableId(valid, empty) {
        // Disable/enable id string based on auth type validity
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

        // Enable id string input if auth type is valid
        enableId(valid, empty);
    }

    // Format id string input on selection of auth type based on validation
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

    // Format id string input on entering value based on validation
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
            $("#idError").text("You have entered an invalid id for this authentication type");
        } else if (empty) {
            $("#idError").text("Please enter the id of the account you want to link to");
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