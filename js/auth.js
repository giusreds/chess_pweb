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

    // Move between login and register forms
    $("#goto_register").on("click", () => {
        goto_auth("register");
    });
    $("#goto_login").on("click", () => {
        goto_auth("login");
    });
});

function auth_request(event, action) {
    event.preventDefault();
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
    }
}
// If auth operation failed or reset error_msg
function auth_failure(action, error_msg = "") {
    $("#" + action + "_error").text(error_msg);
}

function goto_auth(to_show) {
    if (to_show == "login") var to_hide = "register";
    else var to_hide = "login";
    $(".container." + to_show).removeClass("hidden");
    $(".container." + to_hide).addClass("hidden");
    // Resets the form
    $("#" + to_show + "_form").trigger("reset");
}
