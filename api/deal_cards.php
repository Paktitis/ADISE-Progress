<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$mysqli = new mysqli("localhost", "root", "", "adise_db");
if ($mysqli->connect_error) {
  echo json_encode(["ok" => false, "error" => "db"]);
  exit;
}

$game_id = (int)($_POST["game_id"] ?? 0);
if ($game_id <= 0) {
  echo json_encode(["ok" => false, "error" => "missing_game_id"]);
  exit;
}

/* Πάρε game */
$q = $mysqli->query("SELECT ID, Status, Player1_id, Player2_id, State_json FROM games WHERE ID=$game_id");
$g = $q->fetch_assoc();

if (!$g) {
  echo json_encode(["ok" => false, "error" => "game_not_found"]);
  exit;
}

if ($g["Status"] == "finished") {
  echo json_encode([
    "ok" => false,
    "error" => "game_finished",
    "message" => "Game is finished. No more cards to deal. Check final results."
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($g["Status"] != "playing") {
  echo json_encode([
    "ok" => false,
    "error" => "game_not_ready",
    "status" => $g["Status"]
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

if (empty($g["Player1_id"]) || empty($g["Player2_id"])) {
  echo json_encode(["ok" => false, "error" => "missing_players"]);
  exit;
}

$p1 = (int)$g["Player1_id"];
$p2 = (int)$g["Player2_id"];

/* Έλεγξε ότι και οι 2 παίκτες έχουν 0 φύλλα */
$stmt = $mysqli->prepare("SELECT Player_id, Cards_left FROM game_players WHERE Game_id=?");
$stmt->bind_param("i", $game_id);
$stmt->execute();
$res = $stmt->get_result();

$cards = [];
while ($r = $res->fetch_assoc()) {
  $cards[(int)$r["Player_id"]] = (int)$r["Cards_left"];
}

if (!isset($cards[$p1]) || !isset($cards[$p2])) {
  echo json_encode(["ok" => false, "error" => "game_players_rows_missing"]);
  exit;
}

if ($cards[$p1] > 0 || $cards[$p2] > 0) {
  echo json_encode([
    "ok" => false,
    "error" => "players_still_have_cards",
    "p1_cards" => $cards[$p1],
    "p2_cards" => $cards[$p2]
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

/* Φόρτωσε state */
$state = [];
if (!empty($g["State_json"])) {
  $tmp = json_decode($g["State_json"], true);
  if (is_array($tmp)) $state = $tmp;
}

if (!isset($state["table"]) || !is_array($state["table"])) $state["table"] = [];
if (!isset($state["captured"]) || !is_array($state["captured"])) $state["captured"] = [];
if (!isset($state["xeri_count"]) || !is_array($state["xeri_count"])) $state["xeri_count"] = [];
if (!array_key_exists("last_capturer", $state)) $state["last_capturer"] = null;

/* Flag: πρώτο deal ή όχι */
$is_first_deal = false;

/* Αν δεν υπάρχει deck, φτιάξε καινούρια τράπουλα (ΜΟΝΟ στην αρχή της παρτίδας) */
if (!isset($state["deck"]) || !is_array($state["deck"])) {
  $suits = ["S", "H", "D", "C"];
  $ranks = ["A", "2", "3", "4", "5", "6", "7", "8", "9", "10", "J", "Q", "K"];
  $deck = [];

  foreach ($suits as $s) {
    foreach ($ranks as $r) {
      $deck[] = $r . $s;
    }
  }

  shuffle($deck);
  $state["deck"] = $deck;
  $is_first_deal = true;
}

$deck = $state["deck"];

/* Αν δεν υπάρχουν αρκετά φύλλα για νέο μοίρασμα 6-6 -> τέλος παρτίδας */
if (count($deck) < 12) {

  /* Ο τελευταίος που μάζεψε παίρνει ό,τι έχει μείνει στο τραπέζι */
  $lc = $state["last_capturer"];

  if ($lc !== null && count($state["table"]) > 0) {
    $lc_key = (string)$lc;

    if (!isset($state["captured"][$lc_key]) || !is_array($state["captured"][$lc_key])) {
      $state["captured"][$lc_key] = [];
    }

    $state["captured"][$lc_key] = array_merge($state["captured"][$lc_key], $state["table"]);
    $state["table"] = [];
  }

  $state["deck"] = $deck;
  $new_state_json = $mysqli->real_escape_string(json_encode($state, JSON_UNESCAPED_UNICODE));

  $mysqli->query("UPDATE games SET State_json='$new_state_json', Status='finished' WHERE ID=$game_id");

  echo json_encode([
    "ok" => true,
    "finished" => true,
    "deck_left" => count($deck),
    "last_capturer" => $lc,
    "message" => "Game finished. No more cards to deal. Check final results."
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

/* Μοίρασμα 6-6 */
$hand1 = array_splice($deck, 0, 6);
$hand2 = array_splice($deck, 0, 6);

/* ΜΟΝΟ στο πρώτο deal: άνοιξε 4 φύλλα κάτω */
if ($is_first_deal && count($state["table"]) == 0) {
  if (count($deck) < 4) {
    echo json_encode(["ok" => false, "error" => "deck_empty"]);
    exit;
  }

  for ($i = 0; $i < 4; $i++) {
    $state["table"][] = array_shift($deck);
  }
}

/* Αποθήκευση hands */
$hand1_json = $mysqli->real_escape_string(json_encode($hand1, JSON_UNESCAPED_UNICODE));
$hand2_json = $mysqli->real_escape_string(json_encode($hand2, JSON_UNESCAPED_UNICODE));

$mysqli->query("UPDATE game_players SET Hand_json='$hand1_json', Cards_left=6 WHERE Game_id=$game_id AND Player_id=$p1");
$mysqli->query("UPDATE game_players SET Hand_json='$hand2_json', Cards_left=6 WHERE Game_id=$game_id AND Player_id=$p2");

/* Save state */
$state["deck"] = $deck;
$new_state_json = $mysqli->real_escape_string(json_encode($state, JSON_UNESCAPED_UNICODE));
$mysqli->query("UPDATE games SET State_json='$new_state_json' WHERE ID=$game_id");

$table_top = count($state["table"]) > 0 ? $state["table"][count($state["table"]) - 1] : null;

echo json_encode([
  "ok" => true,
  "finished" => false,
  "game_id" => $game_id,
  "table_top" => $table_top,
  "p1_cards" => 6,
  "p2_cards" => 6,
  "deck_left" => count($deck)
], JSON_UNESCAPED_UNICODE);