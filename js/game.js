// Retrieve the match id from url parameter
const match_id = new URLSearchParams(window.location.search).get("id");
const refresh_rate = 1600;

// Global variables
var match_status = null;
var current_clicked = null;


// Appena lo script viene caricato
$(document).ready(() => {
  start("refresh");
  var refresh = setInterval(start, refresh_rate);

  for (var i = 0; i < 8; i++)
    for (var j = 0; j < 8; j++)
      $("#" + nomeCella(i, j)).on("click", clickCell);
});

function start(mode = "pull") {

  var body = {
    "id": match_id,
    "action": mode
  };

  $.ajax({
    type: "POST",
    url: "./php/api_game.php",
    data: body,
    success: (result) => {
      after(result);
    },
    dataType: "json"
  });
}

function after(result) {
  // console.log(result);
  if (result && result.changed) {
    match_status = result;
    updateChessboard();
  }
}

// Se non esiste, aggiunge un nuovo pezzo sulla scacchiera
// o nelle sezioni dei pezzi mangiati
function addPiece(father, piece, prefix = "") {
  if (!$("#" + prefix + piece.name).length) {
    var tmp = $("<img>").addClass("pedina")
      .attr({
        "id": prefix + piece.name,
        "src": "./img/pieces/" + piece.icon + ".png",
        "alt": piece.icon
      }).css("opacity", 0);
    $("#" + father).append(tmp);
  }
  // Aggiorno in ogni caso l'attributo "source" perche'
  // l'icona dei pezzi puo'cambiare nel corso del gioco
  // (promozione)
  current_piece = $("#" + prefix + piece.name);
  current_piece.animate({
    "opacity": 1
  }, 600);
  current_piece.attr("src", "./img/pieces/" + piece.icon + ".png");
}

function updateChessboard() {
  var pieces = match_status.chessboard;
  // Aggiungo o aggiorno la posizione delle pedine sulla scacchiera
  for (var i = 0; i < 8; i++)
    for (var j = 0; j < 8; j++) {
      // Se la cella e'vuota, passo oltre
      if (!pieces[i][j]) continue;
      addPiece("campo", pieces[i][j]);
      // Imposto la posizione della pedina corrente
      $("#" + pieces[i][j].name).css({
        "top": 60 * i + "px",
        "left": 60 * j + "px"
      });
    }
  var mangiati = match_status.captured;
  // Lavoro sulle pedine mangiate
  for (var i = 0; i < mangiati.length; i++) {
    // Se una pedina mangiata e'ancora sulla scacchiera, la elimino
    var current_piece = $("#" + mangiati[i].name);
    if (current_piece)
      fadeOut(current_piece, 1000);
    // Il padre della pedina e'scelto in base al campo "proprietario"
    addPiece("captured_" + mangiati[i].owner, mangiati[i], "c_");
  }
  inCheck();
  clickable();
  // TEMPORANEO
  turno();
}


// Rimuove un elemento con transizione
function fadeOut(element, duration = 1000) {
  element.animate({
    opacity: 0,
  }, duration, function () {
    this.remove();
  });
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

// TEMPORANEO
function pawnPromotion(source, destination) {
  s_r = parseInt(source.substring(0, 1));
  s_c = parseInt(source.substring(1, 2));
  dest_r = parseInt(destination.substring(0, 1));
  // console.log(s_r, s_c, dest_r);
  current_piece = match_status.chessboard[s_r][s_c];
  // console.log(current_piece);
  if (current_piece.type == "pawn" && current_piece.owner == "you" && !dest_r)
    promotion(source, destination);
  else
    moveTo(source, destination);
}
// FINE TEMPORANEO

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
      if (match_status.yourturn && current_piece && current_piece.owner == "you") {
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
      "action": "check",
      "id": match_id,
      "source": source
    },
    success: (result) => {
      if (current_clicked == source) {
        result.forEach(current_cell => {
          $("#" + current_cell).addClass("destination");
        });
        if (!result.length) removeClicked();
      }
    },
    dataType: "json"
  });
}


function removeDest() {
  $(".destination").removeClass("destination");
  /*
  var destinations = Array.from($(".destination"));
  // console.log(destinations);
  destinations.forEach(element => {
    element.classList.remove("destination");
  });*/
}

function moveTo(source, destination, promotion = null) {
  // console.log("Dest: " + destination);

  var body = {
    "id": match_id,
    "action": "move",
    "source": source,
    "destination": destination
  };
  if (promotion)
    body.promotion = promotion;

  $.ajax({
    type: "POST",
    url: "./php/api_game.php",
    data: body,
    success: (result) => {
      // console.log(match_status);
      if (result && result.changed) after(result); //updateChessboard(match_status);
    },
    dataType: "json"
  });

  removeClicked();
  match_status.yourturn = 0;
  // TEMPORANEO
  turno();
}

function nomeCella(r, c) {
  return r.toString() + c.toString();
}


function inCheck() {
  $(".incheck").removeClass("incheck");
  var check = match_status.incheck;
  if (check)
    check.forEach(element => {
      $("#" + element).addClass("incheck");
    });
}


// TEMPORANEA

function turno() {
  if (match_status.yourturn) {
    document.getElementById("turn").innerHTML = "It's your turn!";
    document.getElementById("turn").style.color = "green";
  } else {
    document.getElementById("turn").innerHTML = "Wait for your opponent...";
    document.getElementById("turn").style.color = "red";
  }
}

// Temporanea promotion
// SUPER TEMPORANEA (PROMPT NO -> USA FORM)

function promotion(source, destination) {
  var promotion = prompt("Promotion ( knight | queen | rook | bishop )");
  moveTo(source, destination, promotion);
}