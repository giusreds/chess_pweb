<?php

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
