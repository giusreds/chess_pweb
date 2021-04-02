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
            else auth_failure(data.error_msg);
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
    }
}
// If auth operation failed or reset error_msg
function auth_failure(error_msg = "") {
    $("#auth_error").text(error_msg);
}
