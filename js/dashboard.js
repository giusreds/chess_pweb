// URL of the current page, without GET parameters
const location_no_param =
    location.protocol + "//" + location.host + location.pathname;
// Eventual GET parameter to directly join a match
const join_param = new URLSearchParams(location.search).get("join");

var interval_join = null;

// Main
$(document).ready(() => {
    // Check if there is a join parameter
    if (join_param) join_match(join_param);

    // Host match
    $("#host_form").submit((e) => {
        host_match(e);
    });

    $("#logout_form").submit((e) => {
        e.preventDefault();
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

function join_match(match_id) {
    match_to_join = match_id;
    $.ajax({
        type: "POST",
        url: "./php/api_join.php",
        data: {
            action: "join",
            id: match_id,
        },
        success: function (data) {
            if (!data.error) {
                if (interval_join === null)
                    interval_join = setInterval(() => {
                        join_match(match_id);
                    }, 1000);
            } else {
                alert("Problem!");
                location.href = "./";
            }
            $("main").hide();
            $("#wait").show();
            $("#match_id").text(match_id);
            $("#share_whatsapp").attr("href", WhatsAppLink(match_id));
            if (data.started) location.href = "./match.php?id=" + match_id;
        },
    });
}

// Temp function
function WhatsAppLink(match_id) {
    // For reference check WhatsApp documentation:
    // https://faq.whatsapp.com/general/chats/how-to-use-click-to-chat/
    var base = "https://wa.me/?text=";
    var message = "I am playing Chess. Join my match using this code: ";
    return base + encodeURI(message + match_id);
}

function wait(match_id) {
    $("#wait").show();
    $.ajax({
        type: "POST",
        url: "./php/api_join.php",
        data: {
            action: "join",
            id: match_id,
        },
        success: function (data) {
            $("#wait").show();
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
            if (data.started) {
                setTimeout(function () {
                    window.location.replace("./match.php?id=" + match_id);
                }, 2000);
            }
        },
    });
}

$("#host_btn").on("click", function () {
    $("#overlay").show();
    $("#join_form").hide();
    $("#host_form").show();
});

$("#join_btn").on("click", function () {
    $("#overlay").show();
    $("#join_form").show();
    $("#host_form").hide();
    get_matches();
});

$("#dialog_close").on("click", function () {
    $("#join_dialog").hide();
});

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
