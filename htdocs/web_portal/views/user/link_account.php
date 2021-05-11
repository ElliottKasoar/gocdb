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
                    <label for="authType">Authentication type:</label>
                    <div class="controls">
                        <select class="form-control"
                            name="AUTHTYPE" id="selectedAuthType" onchange="updateWarningMessage(); formatAuthType(); formatIdFromAuth();"
                            size=<?=sizeof($params['AUTHTYPES']);?>>
                            <?php
                                foreach ($params['AUTHTYPES'] as $authType) {
                                    echo "<option value=\"" . $authType . "\">" . $authType . "</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <span id="authTypeError" class="label label-danger hidden"></span>
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
            //updateWarningMessage();
            validate();
        });
    });

    /**
     * Updates the authentication type message
     * Message depends on whether the selected auth type is the same as the auth type currently in use
     *
     * @returns {null}
     */
    function updateWarningMessage() {
        var selectedAuthType = $('#selectedAuthType').val();
        var currentAuthType = "<?=$params['CURRENTAUTHTYPE'];?>";

        var authTypeText1 = "";
        var authTypeText2 = "";
        var authTypeText3 = "";

        if (selectedAuthType !== null || selectedAuthType !== "") {
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
        var inputAuthType = '#selectedAuthType';
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

    function validate() {
        var idValid = false;
        var emailValid = false;
        var authTypeValid = false;

        // Validate auth type
        var regExAuthType = getRegExAuthType();
        var inputAuthType = '#selectedAuthType';
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

    function isInputValid(regEx, input) {
        var inputValue = $(input).val();
        var inputValid = false;
        if(regEx.test(inputValue) !== false) {
            inputValid=true;
        }
        return inputValid;
    }

    function isInputEmpty(input) {
        var inputValue = $(input).val();
        var inputEmpty = true;
        if(inputValue) {
            inputEmpty=false;
        }
        return inputEmpty;
    }

    function formatAuthType() {
        var regEx = getRegExAuthType();
        var input = '#selectedAuthType';
        var valid = isInputValid(regEx, input);
        var empty = isInputEmpty(input);

        if(valid && !empty) {
            $('#authTypeGroup').addClass("has-success");
            $('#authTypeGroup').removeClass("has-error");
            $('#primaryId').prop('disabled', false);
            $("#authTypeError").addClass("hidden");
            $("#authTypeError").text("You have entered an invalid authentication type");
        } else {
            $('#authTypeGroup').removeClass("has-success");
            $('#authTypeGroup').addClass("has-error");
            $('#primaryId').prop('disabled', true);
            $("#authTypeError").removeClass("hidden");
        }
    }

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