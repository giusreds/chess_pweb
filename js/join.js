var match_id = null;

$("#host").submit(function (e) {
    e.preventDefault();
    $.ajax({
        type: "POST",
        url: "./php/api_join.php",
        data: $(this).serialize(),
        success: function (data) {
            $("#match_id").text(data.id);
            match_id = data.id;
            setInterval(wait, 1000);
        }
    });
});

$("#join").submit(function (e) {
    e.preventDefault();
    $.ajax({
        type: "POST",
        url: "./php/api_join.php",
        data: $(this).serialize(),
        success: function (data) {
            match_id = data.id;
            $("#match_id").text(match_id);
            setInterval(wait, 1000);
        }
    });
});

function wait() {
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
                    window.location.href = "./match.php?id=" + match_id;
                }, 2000);
            }
        }
    });
}


$("#host_btn").on("click", function () {
    $("#join_dialog").show();
    $("#join").hide();
    $("#host").show();
});

$("#join_btn").on("click", function () {
    $("#join_dialog").show();
    $("#join").show();
    $("#host").hide();
});

$("#dialog_close").on("click", function () {
    $("#join_dialog").hide();
});