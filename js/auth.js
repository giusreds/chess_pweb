$(document).ready(() => {
    // Event listeners
    $("#login_form").submit((e) => {
        auth_request(e, "login");
    });
    $("#register_form").submit((e) => {
        auth_request(e, "register");
    });
});

function auth_request(event, action) {
    event.preventDefault();
    formData = $(event.target).serialize() + "&action=" + action;
    $.ajax({
        type: "POST",
        url: "./php/api_auth.php",
        data: formData,
        success: function (data) {
            if (!data.error)
                auth_success(action);
            else
                $("#error").text(data.error_msg);
        }
    });
}

function auth_success(action) {
    switch (action) {
        case "login":
            window.location.href = "./";
            break;
        case "register":

    }
}