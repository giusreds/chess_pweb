// URL of the current page, without GET parameters
const location_no_param = location.protocol + '//' + location.host + location.pathname;
// Eventual GET parameter to directly join a match 
const join_param = new URLSearchParams(location.search).get("join");
// Store the match in wich join
var match_to_join = null;

// Main
$(document).ready(() => {
    // Check if there is a join parameter
    if (join_param) join_match(join_param);
    console.log(location_no_param);

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
                "action": "logout"
            },
            success: function (data) {
                if (!data.error) location.replace("./");
            }
        });
    });
});

$("#host").submit(function (e) {
    e.preventDefault();
    $.ajax({
        type: "POST",
        url: "./php/api_join.php",
        data: $(this).serialize(),
        success: function (data) {
            $("#match_id").text(data.id);
            match_to_join = data.id;
            $("#share_whatsapp").attr("href", WhatsAppLink(match_to_join));
            setInterval(wait, 1000);
        }
    });
});


function host_match(event) {
    event.preventDefault();
    $.ajax({
        type: "POST",
        url: "./php/api_join.php",
        data: $(event.target).serialize(),
        success: function (data) {
            $("#match_id").text(data.id);
            match_to_join = data.id;
            $("#share_whatsapp").attr("href", WhatsAppLink(match_to_join));
            setInterval(wait(data.id), 1000);
        }
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


function join_match(match_id) {
    match_to_join = match_id;
    $.ajax({
        type: "POST",
        url: "./php/api_join.php",
        data: {
            "action": "join",
            "id": match_id
        },
        success: function (data) {
            $("#match_id").text(match_id);
            $("#share_whatsapp").attr("href", WhatsAppLink(match_id));
            setInterval(wait(data.id), 1000);
        }
    });
}

function wait(match_id) {
    $("#wait").show();
    $.ajax({
        type: "POST",
        url: "./php/api_join.php",
        data: {
            "action": "verify",
            "id": match_id
        },
        success: function (data) {
            $("#wait").show();
            data.players.forEach(player => {
                if (!$("#player_" + player.username).length)
                    $("<div></div>").appendTo("#players_list").attr("id", "player_" + player.username).addClass("player")
                        .append("<p>" + player.username + '</p><img src="./img/avatars/' + player.avatar + '.svg">');
            });
            if (data.status) {
                setTimeout(function () {
                    window.location.replace("./match.php?id=" + match_id);
                }, 2000);
            }
        }
    });
}


$("#host_btn").on("click", function () {
    $("#join_dialog").show();
    $("#join_form").hide();
    $("#host_form").show();
});

$("#join_btn").on("click", function () {
    $("#join_dialog").show();
    $("#join_form").show();
    $("#host_form").hide();
});

$("#dialog_close").on("click", function () {
    $("#join_dialog").hide();
});