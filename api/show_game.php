<?php
//header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors',1);
error_reporting(E_ALL);

$host = "localhost";
$user = "iee2019231"; //Συνδεση με την βαση 
$pass = "Ptuxiosta5!!";
$db   = "ADISE25_Progress_db";
$socket = "/home/student/iee/2019/iee2019231/mysql/run/mysql.sock";

$mysqli = new mysqli($host, $user, $pass, $db, null, $socket);
if ($mysqli->connect_error) {
  http_response_code(500);
  echo json_encode(["ok"=>false, "error"=>"db_connect_failed", "details"=>$mysqli->connect_error]);
  exit;
}

// Παιρνει σαν εισοδο τα game_id και username 
$game_id  = isset($_REQUEST["game_id"]) ? (int)$_REQUEST["game_id"] : 0;
$username = isset($_REQUEST["username"]) ? trim($_REQUEST["username"]) : "";

if ($game_id <= 0 || $username === "") {
  http_response_code(400); //μηνυμα λαθους αν κατι λειπει
  echo json_encode(["ok"=>false, "error"=>"missing_game_id_or_username"]);
  exit;
}

/* 1) Βρες player_id από username */
//Ψαχνει στον πινακα players τον παικτη με το username που δεχτηκε σαν εισοδο
$stmt = $mysqli->prepare("SELECT id FROM players WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
if (!$row) {
  http_response_code(404); //μηνυμα λαθους καταχωρηθηκε λαθος ονομα
  echo json_encode(["ok"=>false, "error"=>"player_not_found"]);
  exit;
}
$player_id = (int)$row["id"];


/* 2) Πάρε game */
//Παιρνει τα βασικα στοιχεια της παρτιδας.Φαιντονται στο SELECT
$stmt = $mysqli->prepare("SELECT id, status, player1_id, player2_id, turn_player_id, state_json, winner_id FROM games WHERE id = ?");
$stmt->bind_param("i", $game_id);
$stmt->execute();
$g = $stmt->get_result()->fetch_assoc();

if (!$g) {
  http_response_code(404); // Μηνυμα λαθους αν δεν υπαρχει το παιχνιδι
  echo json_encode(["ok"=>false, "error"=>"game_not_found"]);
  exit;
}

/* 3) Πάρε το row του player από game_players */
//Ελεγχει οτι ο παικτης ανηκει στο συγκεκριμενο παιχνιδι
$stmt = $mysqli->prepare("SELECT seat, hand_json, cards_left, xeri_count, xeri_jack_count, score FROM game_players WHERE game_id = ? AND player_id = ?");
$stmt->bind_param("ii", $game_id, $player_id);
$stmt->execute();
$gp = $stmt->get_result()->fetch_assoc();

if (!$gp) {
  http_response_code(404);
  echo json_encode(["ok"=>false, "error"=>"player_not_in_this_game"]);
  exit;
}

//-------Βρισκει τον αντιπαλο ------
$opp_id = null;
if ((int)$g["player1_id"] === $player_id) $opp_id = $g["player2_id"] ? (int)$g["player2_id"] : null;
if ((int)$g["player2_id"] === $player_id) $opp_id = $g["player1_id"] ? (int)$g["player1_id"] : null;

$opp = null;
if ($opp_id) {
  $stmt = $mysqli->prepare("SELECT username FROM players WHERE id = ?");
  $stmt->bind_param("i", $opp_id);
  $stmt->execute();
  $opp_name = $stmt->get_result()->fetch_assoc();
  $opp_username = $opp_name ? $opp_name["username"] : null;

  $stmt = $mysqli->prepare("SELECT seat, cards_left, xeri_count, xeri_jack_count, score FROM game_players WHERE game_id = ? AND player_id = ?");
  $stmt->bind_param("ii", $game_id, $opp_id);
  $stmt->execute();
  $opp_gp = $stmt->get_result()->fetch_assoc();

  //Διαβαζει ολα αυτα που αφορουν τον αντιπαλο
  $opp = [
    "player_id" => $opp_id,
    "username"  => $opp_username,
    "seat"      => $opp_gp ? (int)$opp_gp["seat"] : null,
    "cards_left"=> $opp_gp ? (int)$opp_gp["cards_left"] : null,
    "xeri_count" => $opp_gp ? (int)$opp_gp["xeri_count"] : null,
    "xeri_jack_count" => $opp_gp ? (int)$opp_gp["xeri_jack_count"] : null,
    "score"     => $opp_gp ? (int)$opp_gp["score"] : null,
  ];
}

/* 5) Διάβασε table_card από state_json */
$table_card = null;
$state = null;
if (!empty($g["state_json"])) {
  $state = json_decode($g["state_json"], true);
  if (is_array($state) && isset($state["table"])) {
    $table_card = $state["table"];
  }
}

// ----------Μετατρεπει το χερι του παικτη σε array για να φαινονται τα φυλλα του -----
$hand = [];
if (!empty($gp["hand_json"])) {
  $tmp = json_decode($gp["hand_json"], true);
  if (is_array($tmp)) $hand = $tmp;
}

//Επιστρεφει ολη την εικονα του παιχνιδιου!
echo json_encode([
  "ok" => true,
  "game_id" => (int)$g["id"],
  "status" => $g["status"],
  "table_card" => $table_card,
  "turn_player_id" => $g["turn_player_id"] ? (int)$g["turn_player_id"] : null,
  "your_turn" => ($g["turn_player_id"] && (int)$g["turn_player_id"] === $player_id),
  "you" => [
    "player_id" => $player_id,
    "username" => $username,
    "seat" => (int)$gp["seat"],
    "hand" => $hand,
    "cards_left" => (int)$gp["cards_left"],
    "xeri_count" => isset($gp["xeri_count"]) ? (int)$gp["xeri_count"] : 0,
    //"xeri_count" => (int)$gp["xeri_count"],
    "xeri_jack_count" => isset($gp["xeri_jack_count"]) ? (int)$gp["xeri_jack_count"] : 0,
    //"xeri_jack_count" => (int)$gp["xeri_jack_count"],
    "score" => (int)$gp["score"],
  ],
  "opponent" => $opp,
  "winner_id" => $g["winner_id"] ? (int)$g["winner_id"] : null
]);

/*Αυτο το endpoint επιστρεφει την τρεχουσα κατασταση του παιχνιδιου για ενα συγκεκριμενο παικτη.
 Παιρνει το game_id και το username , βρισκει τον παικτη και την παρτιδα, ελεγχει οτι ο παικτης
συμμετεχει στο game και μετα επιστρεφει σε JSON το τραπεζι, το χερι του παικτη , την σειρα κλπ */