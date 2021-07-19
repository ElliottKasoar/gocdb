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
    var currentAuthType = $('#currentAuthType').text();
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

        $('#linkingDetails').addClass("hidden");
        $('#recoveryDetails').removeClass("hidden");
        $('#requestPlaceholder').addClass("hidden");

        authTypeText1 = "'" + selectedAuthType + "' is the same as your current authentication type.";
        authTypeText2 = "Proceeding will begin the account recovery process.";

        // Stronger warning for certain types. Certificates will be less severe?
        if (selectedAuthType === "IGTF X509 Cert") {
            authTypeText3 = 'X509 Certificates sometimes expire. Are you sure you wish to continue?';
            $('#authTypeLabel3').removeClass("hidden");
            $('#authTypeLabel3').removeClass("auth-warning-severe");
            $('#authPlaceholder3').addClass("hidden");
        } else {
            authTypeText3 = "'" + selectedAuthType + "' ID strings rarely expire. Are you sure you wish to continue?";
            $('#authTypeLabel3').removeClass("hidden");
            $('#authTypeLabel3').addClass("auth-warning-severe");
            $('#authPlaceholder3').addClass("hidden");
        }

    } else {

        $('#linkingDetails').removeClass("hidden");
        $('#recoveryDetails').addClass("hidden");
        $('#requestPlaceholder').removeClass("hidden");

        authTypeText1 = '"' + selectedAuthType + '" is different to your current authentication type.';
        authTypeText2 = 'Proceeding will begin the identity linking process.'
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