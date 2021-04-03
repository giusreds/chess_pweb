// Main
$(document).ready(() => {
    // Login submit
    $("#login_form").submit((e) => {
        auth_request(e, "login");
    });
    // Register submit
    $("#register_form").submit((e) => {
        auth_request(e, "register");
    });

    // Register form fields validation
    $("#username_reg").on("change", validate_username);
    $("#psw_confirm").on("keyup", validate_password);
    $("#psw").on("change", validate_password);

    // Move between login and register forms
    $("#goto_register").on("click", () => {
        goto_auth("register");
    });
    $("#goto_login").on("click", () => {
        goto_auth("login");
    });

    // Parallax scroll effect

    // Scroll down to login
    $("#scroll_down").on("click", () => {
        $("#parallax").addClass("collapsing collapsed");
        window.scroll(0, $("#auth_form").position().y);
    });
    // Scroll top to parallax
    $("#scroll_up").on("click", () => {
        $("#parallax").addClass("collapsing").removeClass("collapsed");
        window.scroll(0, $("#parallax").position().y);
    });
    // Remove the transition property after transition
    $("#parallax").on("transitionend", () => {
        $("#parallax").removeClass("collapsing");
    });
});

// Calls the API to login or register and retrieves the response
function auth_request(event, action) {
    event.preventDefault();
    if (action == "register") {
        validate_password();
        validate_username();
    }
    // Get the form data and add the action
    form_data = $(event.target).serialize() + "&action=" + action;
    $.ajax({
        type: "POST",
        url: "./php/api_auth.php",
        data: form_data,
        success: function (data) {
            if (!data.error) auth_success(action);
            else auth_failure(action, data.error_msg);
        },
    });
}

// If auth operation succeeded
function auth_success(action) {
    switch (action) {
        case "login":
            location.reload();
            break;
        case "register":
            alert("Registration success!");
            goto_auth("login");
            break;
    }
}

// If auth operation failed or reset error_msg
function auth_failure(action, error_msg = "") {
    $("#" + action + "_error").text(error_msg);
}

// Checks if the password and password-confirm fields matches
function validate_password() {
    if ($("#psw").val() != $("#psw_confirm").val())
        var message = "The two password don't match.";
    else var message = "";
    $("#psw_confirm").get(0).setCustomValidity(message);
}

// Calls the API to check if the username matches the pattern
// and if it is unique
function validate_username() {
    var body = {
        action: "validate",
        username: $("#username_reg").val(),
    };
    $.ajax({
        type: "POST",
        url: "./php/api_auth.php",
        data: body,
        success: function (data) {
            if (!data.error) var message = "";
            else var message = data.error_msg;
            $("#username_reg").get(0).setCustomValidity(message);
        },
    });
}

// Move around from login to register and the opposite
function goto_auth(to_show) {
    if (to_show == "login") var to_hide = "register";
    else var to_hide = "login";
    $(".container." + to_show).removeClass("hidden");
    $(".container." + to_hide).addClass("hidden");
    // Resets the form
    $("#" + to_show + "_form").trigger("reset");
}
