<?php

// Time in seconds for each turn
$turn_time = 60;

function isMyMatch($match_id, $user_id)
{
    global $mysqli;

    $query = $mysqli->prepare(
        "SELECT COUNT(*) AS `count`
        FROM `match_team`
        WHERE `match_id` = ?
        AND `user` = ?"
    );
    $query->bind_param("si", $match_id, $user_id);
    $query->execute();
    $result = $query->get_result();
    $row = $result->fetch_array();
    if ($row["count"])
        return true;
    return false;
}

// Removes unnecessary attributes from pieces info
// Used before sending chessboard to client
function clean(&$chessboard)
{
    for ($i = 0; $i < 8; $i++)
        for ($j = 0; $j < 8; $j++) {
            $attributes = array("firstmove", "enpassant");
            foreach ($attributes as $attribute)
                if (isset($chessboard[$i][$j]->$attribute))
                    unset($chessboard[$i][$j]->$attribute);
        }
}

// Returns the last row of the match log
function getLastMove($match_id)
{
    global $mysqli;
    $query = $mysqli->prepare(
        "SELECT * FROM `match_log` WHERE `id` = ?
        ORDER BY `timestamp` DESC LIMIT 1"
    );
    $query->bind_param("s", $match_id);
    $query->execute();
    $result = $query->get_result();
    if (!$result) return NULL;
    return $result->fetch_array();
}

// Get remaining time to do the move
// Returns a negative number if time exceeded
// Negative result is used to handle the end of the match
function getTime($timestamp)
{
    global $turn_time;
    $last_move = date_timestamp_get(DateTime::createFromFormat("Y-m-d H:i:s", $timestamp));
    $now = date_timestamp_get(date_create());
    return $last_move + $turn_time - $now;
}

// End the match and set a winner (0, 1, 2)
// 2 means that the match ended in a draw
function setWinner($match_id, $winner)
{
    global $mysqli;
    $query = $mysqli->prepare(
        "UPDATE `match_info` SET
        `status` = 0, `winner` = ?
        WHERE `id` = ?"
    );
    $query->bind_param("is", $winner, $match_id);
    $query->execute();
    // Increment on users profiles
    $query = $mysqli->prepare(
        "UPDATE `user` `U`
        INNER JOIN `match_team` `T` 
        ON `U`.`id` = `T`.`user`
        SET `U`.`total` = `U`.`total` + 1,
        `U`.`won` = `U`.`won` + IF(`T`.`team` = ?, 1, 0)
        WHERE `T`.`match_id` = ?"
    );
    $query->bind_param("is", $winner, $match_id);
    $query->execute();
}

// Returns an array with match status and winner
// attributes from DataBase row
function getStatus($match_id)
{
    global $mysqli;
    $query = $mysqli->prepare(
        "SELECT `status`, `winner` 
        FROM `match_info` WHERE `id` = ?"
    );
    $query->bind_param("s", $match_id);
    $query->execute();
    $result = $query->get_result();
    if (!$result) return;
    $row = $result->fetch_array();
    return array($row["status"], $row["winner"]);
}

// Checks if the match is started
function isStarted($match_id)
{
    $status = getStatus($match_id)[0];
    if ($status === NULL)
        return 0;
    return 1;
}

// Checks if the match is ended
function isEnded($match_id)
{
    $status = getStatus($match_id)[0];
    if ($status === 0)
        return 1;
    return 0;
}

// Returns the winner of the match
function whoIsTheWinner($match_id, $player_id)
{
    if (!isEnded($match_id)) return NULL;
    $winner = getStatus($match_id)[1];
    if ($winner == 2) return "draw";
    if ($winner == $player_id) return "you";
    return "opp";
}

// If time exceeded, ends the match
function checkTimeExceeded($match_id)
{
    $last_move = getLastMove($match_id);
    $time = getTime($last_move["timestamp"]);
    // If it's all okay do nothing
    if (!isStarted($match_id) || isEnded($match_id) || $time > 0)
        return;
    // Else end the match
    $winner = ($last_move["turn"]) ? 0 : 1;
    setWinner($match_id, $winner);
}

// If there's checkmate or draw, ends the match
function checkIfCheckmateDraw($match_id, $team_id, $chessboard)
{
    $status = isCheckMate($chessboard, "opp");
    if (!$status) return;
    switch ($status) {
        case 1:
            // If checkmate
            $winner = $team_id;
            break;
        case 2:
            // If draw
            $winner = 2;
            break;
    }
    setWinner($match_id, $winner);
}

// Checks if the match is started and not ended
// (is currently active)
function isMatchActive($match_id)
{
    if (!isEnded($match_id) && isStarted($match_id))
        return true;
    return false;
}

// Calculates the index in the original chessboard
// depending on the player_id
function index($n, $player_id)
{
    // If I'm in team 0, I'll be operating
    // on the rotated chessboard
    return ($player_id) ? $n : 7 - $n;
}

// Returns the cells where there are kings in check
function inCheck($chessboard)
{
    $result = array();
    $range = array("you", "opp");
    foreach ($range as $current_player) {
        if (isKingInCheck($chessboard, $current_player))
            array_push($result, findKing($chessboard, $current_player));
    }
    return $result;
}

// Reads data from the DataBase and returns an object
// with the current status of the match relatively
// to the current team
function fetchMatch($match_row, $player_id)
{
    $chessboard = json_decode($match_row["chessboard"]);
    $captured = json_decode($match_row["captured"]);
    $isYourTurn = ($match_row["turn"] == $player_id) ? 1 : 0;

    $result["chessboard"] = array();
    // Custom matrix creation
    for ($i = 0; $i < 8; $i++) {
        $tmp_row = array();
        for ($j = 0; $j < 8; $j++) {
            $a = index($i, $player_id);
            $b = index($j, $player_id);
            $tmp_cell = $chessboard[$a][$b];
            // Set "you" for the pieces of the current team
            // and "opp" for opponent's pieces
            if ($tmp_cell) {
                $tmp_cell->owner = ($tmp_cell->owner == $player_id) ? "you" : "opp";
            }
            array_push($tmp_row, $tmp_cell);
        }
        array_push($result["chessboard"], $tmp_row);
    }
    // Captured pieces
    $result["captured"] = $captured;
    // Set the owner of every captured piece with "you" if captured
    // by the current team and "opp" if captured by the opponent
    for ($i = 0; $i < count($captured); $i++)
        $result["captured"][$i]->owner = ($result["captured"][$i]->owner == $player_id) ? "opp" : "you";

    $result["incheck"] = inCheck($result["chessboard"]);
    $result["yourturn"] = $isYourTurn;
    return $result;
}


// ---------------------------------------------------
//              Returns allowed movements
// ---------------------------------------------------

function allowed($chessboard, $position, $owner = "you", $test = 0)
{
    $r = cellIndex($position)[0];
    $c = cellIndex($position)[1];
    $res = array();
    // Se gli indici escono fuori dalla matrice
    if (!innerBoard($r, $c)) return $res;
    $current_piece = $chessboard[$r][$c];
    // Se la cella sorgente e'vuota
    if (!$current_piece) return $res;
    // In base al pezzo, determino i movimenti ammessi
    switch ($current_piece->type) {
        case "pawn":
            $res = pawn($chessboard, $r, $c, $owner, $test);
            break;
        case "rook":
            $res = rook($chessboard, $r, $c, $owner);
            break;
        case "bishop":
            $res = bishop($chessboard, $r, $c, $owner);
            break;
        case "queen":
            $res = queen($chessboard, $r, $c, $owner);
            break;
        case "knight":
            $res = knight($chessboard, $r, $c, $owner);
            break;
        case "king":
            $res = king($chessboard, $r, $c, $owner, $test);
            break;
    }
    if (!$test) {
        $not_allowed = array();
        // Per ogni destinazione ammessa, verifico se dopo lo spostamento
        // ci sia una situazione di rischio per il re
        foreach ($res as $current_allowed)
            if (isKingInCheck(move($chessboard, array(), $position, $current_allowed, $owner)[0], $owner))
                // Se il re e'in pericolo, salvo lo spostamento
                // nell'array di destinazioni non consentite
                array_push($not_allowed, $current_allowed);

        // Tolgo le destinazioni non consentite dall'insieme
        // delle destinazioni teoriche
        foreach ($not_allowed as $current_notallowed)
            while (($key = array_search($current_notallowed, $res)) !== false)
                array_splice($res, $key, 1);
        // Ritorno le destinazioni effettivamente consentite
    }
    return $res;
}


// ---------------------------------------------------
//               Pieces movement rules
// ---------------------------------------------------

// Pawn
function pawn($chessboard, $r, $c, $owner, $onlycapture = 0)
{
    $response = array();
    if ($owner == "you") {
        $range = array(-1, -2);
        $capture = -1;
    } else {
        $range = array(1, 2);
        $capture = 1;
    }
    if (!$onlycapture)
        foreach ($range as $i)
            if (innerBoard($r + $i, $c) && !$chessboard[$r + $i][$c]) {
                if (
                    $i == -1 || isset($chessboard[$r][$c]->firstmove)
                    && !$chessboard[$r + $i][$c]
                )
                    array_push($response, cellName($r + $i, $c));
            } else break;
    // En passant capture
    if (isset($chessboard[$r][$c]->enpassant)) {
        for ($i = 0; $i < 8; $i++)
            for ($j = 0; $j < 8; $j++) {
                $current_cell = $chessboard[$i][$j];
                if (
                    $current_cell && $current_cell->name == $chessboard[$r][$c]->enpassant
                ) {
                    array_push($response, cellName($i + $capture, $j));
                    break;
                }
            }
    }
    // Capture
    $range = array(-1, 1);
    foreach ($range as $i)
        if (
            innerBoard($r + $capture, $c + $i) && ($chessboard[$r + $capture][$c + $i]
                && $chessboard[$r + $capture][$c + $i]->owner != $owner || $onlycapture)
        )
            array_push($response, cellName($r + $capture, $c + $i));

    return $response;
}

// Rook
function rook($chessboard, $r, $c, $owner)
{
    $response = array();
    $range = array(-1, 0, 1);
    foreach ($range as $a)
        foreach ($range as $b)
            if (abs($a) != abs($b))
                for ($i = 1; innerBoard($r + $a * $i, $c + $b * $i); $i++) {
                    if ($chessboard[$r + $a * $i][$c + $b * $i]) {
                        if ($chessboard[$r + $a * $i][$c + $b * $i]->owner == $owner) break;
                        array_push($response, cellName($r + $a * $i, $c + $b * $i));
                        break;
                    }
                    array_push($response, cellName($r + $a * $i, $c + $b * $i));
                }
    return $response;
}

// Bishop
function bishop($chessboard, $r, $c, $owner)
{
    $response = array();
    $range = array(-1, 1);
    foreach ($range as $a)
        foreach ($range as $b)
            for ($i = 1; innerBoard($r + $a * $i, $c + $b * $i); $i++) {
                if ($chessboard[$r + $a * $i][$c + $b * $i]) {
                    if ($chessboard[$r + $a * $i][$c + $b * $i]->owner == $owner) break;
                    array_push($response, cellName($r + $a * $i, $c + $b * $i));
                    break;
                }
                array_push($response, cellName($r + $a * $i, $c + $b * $i));
            }
    return $response;
}

// Queen
function queen($chessboard, $r, $c, $owner)
{
    // Union between rook and bishop movements
    $orthogonal = rook($chessboard, $r, $c, $owner);
    $diagonal = bishop($chessboard, $r, $c, $owner);
    return array_merge($orthogonal, $diagonal);
}

// Knight
function knight($chessboard, $r, $c, $owner)
{
    $response = array();
    $range = array(-2, -1, 1, 2);
    foreach ($range as $i)
        foreach ($range as $j)
            if (
                innerBoard($r + $i, $c + $j) && abs($i) != abs($j)
                && (!$chessboard[$r + $i][$c + $j]
                    || $chessboard[$r + $i][$c + $j]->owner != $owner)
            )
                array_push($response, cellName($r + $i, $c + $j));
    return $response;
}

// King
function king($chessboard, $r, $c, $owner, $onlycapture)
{
    $response = array();
    $range = array(-1, 0, 1);
    foreach ($range as $i)
        foreach ($range as $j)
            if (
                innerBoard($r + $i, $c + $j)
                && (!$chessboard[$r + $i][$c + $j]
                    || $chessboard[$r + $i][$c + $j]->owner != $owner)
            )
                array_push($response, cellName($r + $i, $c + $j));

    // Castling
    if (!$onlycapture && isset($chessboard[$r][$c]->firstmove)) {
        $rooks = array(0, 7);
        foreach ($rooks as $rook) {
            if ($chessboard[$r][$rook] && isset($chessboard[$r][$rook]->firstmove)) {
                $valid = true;
                if ($c > $rook) {
                    for ($i = $rook + 1; $i < $c; $i++)
                        if ($chessboard[$r][$i]) $valid = false;
                    $range = array(0, -1, -2);
                } else {
                    for ($i = $c + 1; $i < $rook; $i++)
                        if ($chessboard[$r][$i]) $valid = false;
                    $range = array(0, 1, 2);
                }
                foreach ($range as $key)
                    if (isCellInCheck($chessboard, cellName($r, $c + $key), $owner)) $valid = false;
                if ($valid) array_push($response, cellName($r, $c + $range[2]));
            }
        }
    }
    // Return
    return $response;
}

// ---------------------------------------------------
//                Utility functions
// ---------------------------------------------------

// Restituisce il nome della cella (concatena)
function cellName($i, $j)
{
    return strval($i) . strval($j);
}

// Restituisce gli indici numerici
function cellIndex($cell)
{
    $r = intval(substr($cell, 0, 1));
    $c = intval(substr($cell, 1, 1));
    return array($r, $c);
}

// Controlla se gli indici sono validi
function innerBoard($r, $c)
{
    if ($r >= 0 && $r <= 7 && $c >= 0 && $c <= 7)
        return true;
    return false;
}

// Funzione che controlla se il re e'sotto attacco
function isKingInCheck($chessboard, $owner)
{
    $king_pos = findKing($chessboard, $owner);
    return isCellInCheck($chessboard, $king_pos, $owner);
}

// Funzione che trova la posizione del re
function findKing($chessboard, $owner)
{
    for ($i = 0; $i < 8; $i++)
        for ($j = 0; $j < 8; $j++) {
            $current_cell = $chessboard[$i][$j];
            if (
                $current_cell && $current_cell->type == "king"
                && $current_cell->owner == $owner
            ) {
                return cellName($i, $j);
            }
        }
    return null;
}

// Funzione che calcola se una determinata cella e'sotto attacco
function isCellInCheck($chessboard, $position, $owner)
{
    $opp_allowed = array();
    if ($owner == "you")
        $opp = "opp";
    else
        $opp = "you";

    $opp_pieces = allMyPieces($chessboard, $opp);
    foreach ($opp_pieces as $opp_curr)
        $opp_allowed = array_merge($opp_allowed, allowed($chessboard, $opp_curr, $opp, 1));
    $opp_allowed = array_unique($opp_allowed);
    if (in_array($position, $opp_allowed))
        return true;
    return false;
}

// Funzione che calcola se e'scacco matto
function isCheckMate($chessboard, $owner = "you")
{
    $pieces = allMyPieces($chessboard, $owner);
    $allowed = array();

    foreach ($pieces as $piece)
        $allowed = array_merge($allowed, allowed($chessboard, $piece, $owner));
    if (count($allowed) == 0) {
        if (isKingInCheck($chessboard, $owner)) return 1;
        return 2;
    }
    return 0;
}

function allMyPieces($chessboard, $owner)
{
    $resp = array();
    for ($i = 0; $i < 8; $i++)
        for ($j = 0; $j < 8; $j++) {
            $current_cell = $chessboard[$i][$j];
            if ($current_cell && $current_cell->owner == $owner)
                array_push($resp, cellName($i, $j));
        }
    return $resp;
}

// Nuova icona pezzo promosso
function pieceIcon($oldname, $newpiece)
{
    $color = substr($oldname, 0, 1);
    $piece_ = strtoupper(substr($newpiece, 0, 1));
    if ($piece_ == "K") $piece_ = "N";
    return $color . $piece_;
}


// Temporaneo
// Rimuove enpassant dell'avversario
function removePassant(&$chessboard, $owner)
{
    for ($i = 0; $i < 8; $i++)
        for ($j = 0; $j < 8; $j++) {
            $current = $chessboard[$i][$j];
            if ($current && isset($current->enpassant) && $current->owner == $owner)
                unset($chessboard[$i][$j]->enpassant);
        }
}

// Funzione che sposta
function move($chessboard, $captured, $source, $dest, $onlycapture = 0, $promotion = null)
{
    $x_s = cellIndex($source)[0];
    $y_s = cellIndex($source)[1];
    $x_d = cellIndex($dest)[0];
    $y_d = cellIndex($dest)[1];

    // TEMPORANEO
    if (!$onlycapture && $chessboard[$x_s][$y_s]->type == "pawn") {
        // Promozione pedone
        if ($x_d == 0 || $x_d == 7) {
            $allowed_promotions = array("rook", "queen", "knight", "bishop");
            if (isset($promotion) && in_array($promotion, $allowed_promotions)) {
                $chessboard[$x_s][$y_s]->type = $promotion;
                $chessboard[$x_s][$y_s]->icon = pieceIcon($chessboard[$x_s][$y_s]->icon, $promotion);
            } else exit;
        }
        // En passant (caso in cui il pedone si muove di 2)
        if (abs($x_s - $x_d) == 2) {
            $range = array(-1, 1);
            foreach ($range as $r) {
                if (
                    innerBoard($x_d, $y_d + $r) &&
                    $chessboard[$x_d][$y_d + $r] &&
                    $chessboard[$x_d][$y_d + $r]->type == "pawn" &&
                    $chessboard[$x_d][$y_d + $r]->owner != $chessboard[$x_s][$y_s]->owner
                )
                    $chessboard[$x_d][$y_d + $r]->enpassant = $chessboard[$x_s][$y_s]->name;
            }
        }
        // En passant (diagonale e dest vuota)
        if (abs($y_s - $y_d) == 1 && !$chessboard[$x_d][$y_d]) {
            array_push($captured, $chessboard[$x_s][$y_d]);
            $chessboard[$x_s][$y_d] = null;
        }
    }


    // Se c'era una pedina, la mangio
    if ($chessboard[$x_d][$y_d])
        array_push($captured, $chessboard[$x_d][$y_d]);
    // Muovo la pedina
    $chessboard[$x_d][$y_d] = $chessboard[$x_s][$y_s];
    $chessboard[$x_s][$y_s] = null;

    if (isset($chessboard[$x_d][$y_d]->firstmove))
        unset($chessboard[$x_d][$y_d]->firstmove);

    // Arrocco (unica mossa in cui il re si muove di 2 caselle)
    if ($chessboard[$x_d][$y_d]->type == "king" && abs($y_d - $y_s) == 2)
        // Arrocco dx
        if ($y_d >= 3) {
            $chessboard[$x_d][$y_d - 1] = $chessboard[$x_d][7];
            $chessboard[$x_d][7] = null;
            // Arrocco sx
        } else {
            $chessboard[$x_d][$y_d + 1] = $chessboard[$x_d][0];
            $chessboard[$x_d][0] = null;
        }


    // ENPASSANT REMOVE DALL'AVVERSARIO
    // TUTTO SUPER TEMPORANEO
    removePassant($chessboard, $chessboard[$x_d][$y_d]->owner);

    // Return
    return array($chessboard, $captured);
}
