// URL of the current page, without GET parameters
const location_no_param =
    location.protocol + "//" + location.host + location.pathname;
// Eventual GET parameter to directly join a match
const join_param = new URLSearchParams(location.search).get("join");
("use strict");
var interval_join = null;


// Main
$(document).ready(() => {
    // Check if there is a join parameter
    if (join_param) join_match(join_param);

    // Host match
    $("#host_form").submit((e) => {
        host_match(e);
    });
    // Logout
    $("#logout_form").submit((e) => {
        logout(e);
    });
    // Show the host popup
    $("#host_btn").on("click", function () {
        $("#overlay").show();
        $("#join_form").hide();
        $("#host_form").show();
    });
    // Show the join popup
    $("#join_btn").on("click", function () {
        $("#overlay").show();
        $("#join_form").show();
        $("#host_form").hide();
        get_matches();
    });
    // Close the dialog
    $("#close_btn").on("click", function () {
        $("#overlay").hide();
    });
});

// Request to create a match
function host_match(event) {
    event.preventDefault();
    $.ajax({
        type: "POST",
        url: "./php/api_join.php",
        data: $(event.target).serialize(),
        success: function (data) {
            if (!data.error) location.href = "./?join=" + data.id;
            else {
                alert("There was an error.");
                location.reload();
            }
        },
    });
}

function logout(event) {
    event.preventDefault();
    $.ajax({
        type: "POST",
        url: "./php/api_auth.php",
        data: {
            action: "logout",
        },
        success: function (data) {
            if (!data.error) location.replace("./");
        },
    });
}

function join_match(match_id) {
    $.ajax({
        type: "POST",
        url: "./php/api_join.php",
        data: {
            action: "join",
            id: match_id,
        },
        success: function (data) {
            switch (data.error) {
                case 0:
                    // If the match is started
                    if (data.started) location.href = "./match.php?id=" + data.id;
                    // If it's the first pull request
                    if (interval_join === null) {
                        // Set the interval
                        interval_join = setInterval(function () {
                            join_match(match_id);
                        }, 1000);
                        window.document.title = "Joining | Strange Chess";
                        // Generate shareable link
                        $("#share_whatsapp").attr("href", whatsapp_link(match_id));
                        // Show the wait screen
                        $("main").hide();
                        $("#wait").show();
                        $("#match_id").text(match_id);
                    }
                    data.players.forEach((player) => {
                        if (!$("#player_" + player.username).length)
                            $("<div></div>")
                                .appendTo("#players_list")
                                .attr("id", "player_" + player.username)
                                .addClass("player")
                                .append(
                                    "<p>" +
                                        player.username +
                                        '</p><img src="./img/avatars/' +
                                        player.avatar +
                                        '.svg">'
                                );
                    });
                    break;
                default:
                    alert("Error! The match doesn't exist or isn't valid more.");
                    location.href = "./";
            }
        },
    });
}

// Analyzes the result of a join pull
function join_parse(data) {
    switch (data.error) {
        case 0:
            // If the match is started
            if (data.started) location.href = "./match.php?id=" + data.id;
            // If it's the first pull request
            if (interval_join === null) {
                // Set the interval
                interval_join = setInterval(function () {
                    join_match(match_id);
                }, 1000);
                // Generate shareable link
                $("#share_whatsapp").attr("href", whatsapp_link(match_id));
                // Show the wait screen
                $("main").hide();
                $("#wait").show();
                $("#match_id").text(match_id);
            }
            data.players.forEach((player) => {
                if (!$("#player_" + player.username).length)
                    $("<div></div>")
                        .appendTo("#players_list")
                        .attr("id", "player_" + player.username)
                        .addClass("player")
                        .append(
                            "<p>" +
                                player.username +
                                '</p><img src="./img/avatars/' +
                                player.avatar +
                                '.svg">'
                        );
            });
            break;
        default:
            alert("Error! The match doesn't exist or isn't valid more.");
            location.href = "./";
    }
}

// Creates WhatsApp link
function whatsapp_link(match_id) {
    // For reference check WhatsApp documentation:
    // https://faq.whatsapp.com/general/chats/how-to-use-click-to-chat/
    var base = "https://wa.me/?text=";
    var message = "I am playing Strange Chess. Join my match using this link: ";
    return base + encodeURI(message + location_no_param + "?join=" + match_id);
}

function add_match(match) {
    var name = "match_" + match.id;
    if (!$("#" + name).length) {
        var tmp = $("<div>").addClass("match_available").attr({
            id: name,
        });
        $("#available_matches_list").append(tmp);
    }
    var to_append = $()
        .add($("<h3>").text(match.host))
        .add($("<p>").text(match.actual + " / " + match.total))
        .add(
            $("<a>", {
                href: "./?join=" + match.id,
                target: "_top",
            }).text("JOIN")
        );
    $("#" + name)
        .empty()
        .append(to_append);
}

function get_matches() {
    $.ajax({
        type: "POST",
        url: "./php/api_join.php",
        data: {
            action: "get",
        },
        success: (data) => {
            data.forEach((match) => {
                add_match(match);
            });
        },
    });
    $(".match_available").each(() => {
        var match_id = $(this).id.substring(6, $(this).id.length);
        if (!data.some((el) => el.id == match_id)) $(this).remove();
    });
}
