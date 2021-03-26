

function updateChessboard(matchStatus) {
    var chessboard = matchStatus.chessboard;
    var captured = matchStatus.captured;
    // Add pieces or update their position
    for (var i = 0; i < 8; i++)
        for (var j = 0; j < 8; j++) {
            // If cell is empty, jump next
            if (!chessboard[i][j]) continue;
            addPiece("campo", chessboard[i][j]);
            // Set the position of the current piece
            $("#" + chessboard[i][j].name).css({
                "top": 60 * i + "px",
                "left": 60 * j + "px"
            });
        }
    // Captured pieces
    for (var i = 0; i < captured.length; i++) {
        // If a captured piece is on the board, delete it
        var current_piece = $("#" + captured[i].name);
        if (current_piece)
            fadeOut(current_piece, 1000);
        // The father of the captured piece is chosen
        // depending to the "owner" field
        addPiece("captured_" + captured[i].owner, captured[i], "c_");
    }
    for (var i = 0; i < 8; i++)
        for (var j = 0; j < 8; j++)
            $("#c_" + chessboard[i][j].name).remove;
    inCheck(matchStatus.incheck);
}

// If it doesn't exist, add a new piece on the chessboard or
// in the captured sections
function addPiece(father, piece, prefix = "") {
    // If the piece doesn't already exist
    if (!$("#" + prefix + piece.name).length) {
        var tmp = $("<img>").addClass("pedina")
            .attr({
                "id": prefix + piece.name,
                "src": "./img/pieces/" + piece.icon + ".png",
                "alt": piece.icon
            }).css("opacity", 0);
        $("#" + father).append(tmp);
    }
    // I reset anyway the "src" attribute because
    // pieces icons can change during the match
    // (pawn promotion)
    current_piece = $("#" + prefix + piece.name);
    current_piece.animate({
        "opacity": 1
    }, 600);
    current_piece.attr("src", "./img/pieces/" + piece.icon + ".png");
}


// Removes an element with a fadeOut animation
function fadeOut(element, duration = 1000) {
    element.animate({
        opacity: 0,
    }, duration, function () {
        this.remove();
    });
}

// Assign the class "incheck" to the cells
// where there are kings in check
function inCheck(inCheck) {
    $(".incheck").removeClass("incheck");
    if (!inCheck) return;
    inCheck.forEach(element => {
        $("#" + element).addClass("incheck");
    });
}