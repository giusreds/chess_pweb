const match_id = new URLSearchParams(window.location.search).get("replay");
const autoplay_interval = 1800;

var total = 0;
var current = 0;
var auto_play = null;

$(document).ready(() => {
    fetch("restart");

    $("#back-btn").on("click", () => {
        fetch("previous");
    });

    $("#next-btn").on("click", () => {
        fetch("next");
    });

    $("#play-btn").on("click", autoPlay);
});

function fetch(action) {
    $.ajax({
        type: "POST",
        url: "./php/api_replay.php",
        data: {
            action: action,
            id: match_id,
        },
        success: (result) => {
            if (result) {
                total = result.total;
                current = result.actual;
                updateChessboard(result);
                button_status();
            }
        },
        dataType: "json",
    });
}

function button_status() {
    if (current == total) {
        var disableNext = true;
        if (auto_play) autoPlay();
        var disablePlay = true;
    }
    else {
        var disableNext = false;
        var disablePlay = false;
    }
    if (current == 0) var disablePrev = true;
    else var disablePrev = false;
    if(auto_play) {
        disablePrev = true;
        disableNext = true;
    }
    $("#play-btn").attr("disabled", disablePlay);
    $("#next-btn").attr("disabled", disableNext);
    $("#back-btn").attr("disabled", disablePrev);
}

function autoPlay() {
    if (auto_play) {
        clearInterval(auto_play);
        auto_play = null;
        var btnText = "PLAY";
    } else {
        auto_play = setInterval(() => {
            fetch("next");
        }, autoplay_interval);
        var btnText = "PAUSE";
    }
    $("#play-btn").text(btnText);
    button_status();
}
