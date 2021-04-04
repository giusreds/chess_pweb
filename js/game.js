// Retrieve the match id from url parameter
const match_id = new URLSearchParams(window.location.search).get("id");
const refresh_rate = 1000;

// Global variables
var match_status = null;
var current_clicked = null;

var refresh = null;

// Main
$(document).ready(() => {
    start("refresh");
    refresh = setInterval(start, refresh_rate);

    for (var i = 0; i < 8; i++)
        for (var j = 0; j < 8; j++)
            $("#" + nomeCella(i, j)).on("click", clickCell);
});

function start(mode = "pull") {
    var body = {
        id: match_id,
        action: mode,
    };

    $.ajax({
        type: "POST",
        url: "./php/api_game.php",
        data: body,
        success: (result) => {
            after(result);
        },
        dataType: "json",
    });
}

function after(result) {
    // console.log(result);
    $("#time").text(result.time);
    if (result && !result.status) {
        switch (result.winner) {
            case "you":
                var msg = "Game over: YOU WON! Congratulations.";
                break;
            case "opp":
                var msg = "Game over: YOU LOSE! Not well...";
                break;
            case "draw":
                var msg = "Game over: MATCH IS DRAW!";
                break;
        }
        clearInterval(refresh);
        alert(msg);
        window.location.href = "./";
    }
    if (result && result.changed) {
        match_status = result;
        updateChessboard(match_status);
        clickable();
        turn();
    }
}

function clickCell() {
    var current_cell = this;
    if (match_status.yourturn) {
        if (current_clicked == current_cell.id) {
            removeClicked();
            removeDest();
        } else {
            if (current_cell.classList.contains("clickable")) {
                if (current_clicked) {
                    removeClicked();
                    removeDest();
                }
                cirrent_clicked = current_cell.id;
                current_cell.classList.add("clicked");
                viewMoves(current_cell.id);
            } else if (current_cell.classList.contains("destination")) {
                pawnPromotion(current_clicked, current_cell.id);
                removeDest();
                clickable();
            } else {
                removeClicked();
                removeDest();
            }
        }
    }
}

// Detect if I have to promote a pawn
function pawnPromotion(source, destination) {
    s_r = parseInt(source.substring(0, 1));
    s_c = parseInt(source.substring(1, 2));
    dest_r = parseInt(destination.substring(0, 1));
    // console.log(s_r, s_c, dest_r);
    current_piece = match_status.chessboard[s_r][s_c];
    // console.log(current_piece);
    if (current_piece.type == "pawn" && current_piece.owner == "you" && !dest_r)
        promotion(source, destination);
    else moveTo(source, destination);
}

function removeClicked() {
    current_clicked = null;
    $(".clicked").removeClass("clicked");
}

function clickable() {
    var pieces = match_status.chessboard;
    for (var i = 0; i < 8; i++)
        for (var j = 0; j < 8; j++) {
            current_cell = $("#" + nomeCella(i, j));
            current_piece = pieces[i][j];
            if (
                match_status.yourturn &&
                current_piece &&
                current_piece.owner == "you"
            ) {
                //if (!current_cell.classList.contains("clickable"))
                current_cell.addClass("clickable");
                continue;
            }
            //if (current_cell.classList.contains("clickable"))
            current_cell.removeClass("clickable");
        }
}

function viewMoves(source) {
    current_clicked = source;
    $.ajax({
        type: "POST",
        url: "./php/api_game.php",
        data: {
            action: "check",
            id: match_id,
            source: source,
        },
        success: (result) => {
            if (current_clicked == source) {
                result.forEach((current_cell) => {
                    $("#" + current_cell).addClass("destination");
                });
                if (!result.length) removeClicked();
            }
        },
        dataType: "json",
    });
}

function removeDest() {
    $(".destination").removeClass("destination");
}

function moveTo(source, destination, promotion = null) {
    // console.log("Dest: " + destination);

    var body = {
        id: match_id,
        action: "move",
        source: source,
        destination: destination,
    };
    if (promotion) body.promotion = promotion;

    $.ajax({
        type: "POST",
        url: "./php/api_game.php",
        data: body,
        success: (result) => {
            // console.log(match_status);
            if (result && result.changed) after(result); //updateChessboard(match_status);
        },
        dataType: "json",
    });

    removeClicked();
    match_status.yourturn = 0;
    turn();
}

function nomeCella(r, c) {
    return r.toString() + c.toString();
}

// TEMPORANEA

function turn() {
    if (match_status.yourturn){
        $("body").addClass("yourturn");
        $("body").removeClass("opponentturn");
    } else {
        $("body").removeClass("yourturn");
        $("body").addClass("opponentturn");
    }
}

// Pawn promotion

var source_promotion, dest_promotion;
function promotion(source, destination) {
    $("#promotion").show();
    source_promotion = source;
    dest_promotion = destination;
}
$(document).ready(
    $(".promotion_field").on("change", function () {
        var promotion = $("input[name='promotion']:checked").val();
        $("#form_id").trigger("reset");
        $("#promotion").hide();
        moveTo(source_promotion, dest_promotion, promotion);
    })
);
