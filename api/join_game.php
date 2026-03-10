<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$pass = "";
$db   = "adise_db";

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
  http_response_code(500);
  echo json_encode(["ok"=>false, "error"=>"db_connect_failed"]);
  exit;
}

$username = trim($_POST["username"] ?? "");
$game_id  = (int)($_POST["game_id"] ?? 0);

if ($username === "" || $game_id <= 0) {
  http_response_code(400);
  echo json_encode(["ok"=>false, "error"=>"missing_username_or_game_id"]);
  exit;
}

/* 1) Βρες ή φτιάξε player */
$stmt = $mysqli->prepare("SELECT id FROM players WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
  $player_id = (int)$row["id"];
} else {
  $stmt2 = $mysqli->prepare("INSERT INTO players (username) VALUES (?)");
  $stmt2->bind_param("s", $username);
  if (!$stmt2->execute()) {
    http_response_code(500);
    echo json_encode(["ok"=>false, "error"=>"player_create_failed", "details"=>$stmt2->error]);
    exit;
  }
  $player_id = (int)$stmt2->insert_id;
}

/* 2) Έλεγξε ότι το game υπάρχει και είναι joinable */
$stmt3 = $mysqli->prepare("SELECT Status, Player1_id, Player2_id FROM games WHERE ID = ?");
$stmt3->bind_param("i", $game_id);
$stmt3->execute();
$g = $stmt3->get_result()->fetch_assoc();

if (!$g) {
  http_response_code(404);
  echo json_encode(["ok"=>false, "error"=>"game_not_found"]);
  exit;
}

if ($g["Status"] !== "waiting") {
  http_response_code(400);
  echo json_encode(["ok"=>false, "error"=>"game_not_joinable", "status"=>$g["Status"]]);
  exit;
}

if (!empty($g["Player2_id"])) {
  http_response_code(400);
  echo json_encode(["ok"=>false, "error"=>"game_already_has_player2"]);
  exit;
}

if ((int)$g["Player1_id"] === $player_id) {
  http_response_code(400);
  echo json_encode(["ok"=>false, "error"=>"same_player_cannot_join_twice"]);
  exit;
}

/* 3) Βάλε τον παίκτη στο game_players seat=2 */
$seat = 2;
$stmt5 = $mysqli->prepare("
  INSERT INTO game_players (Game_id, Player_id, Seat, Hand_json, Cards_left, xeri_count, xeri_jack_count, Score, Captured_json)
  VALUES (?, ?, ?, '[]', 0, 0, 0, 0, '[]')
");
$stmt5->bind_param("iii", $game_id, $player_id, $seat);

if (!$stmt5->execute()) {
  http_response_code(500);
  echo json_encode(["ok"=>false, "error"=>"game_player_insert_failed", "details"=>$stmt5->error]);
  exit;
}

/* 4) Γράψε τον 2ο παίκτη και ξεκίνα το παιχνίδι */
$status = "playing";
$stmt4 = $mysqli->prepare("UPDATE games SET Player2_id = ?, Turn_Player_id = Player1_id, Status = ? WHERE ID = ?");
$stmt4->bind_param("isi", $player_id, $status, $game_id);

if (!$stmt4->execute()) {
  http_response_code(500);
  echo json_encode(["ok"=>false, "error"=>"game_update_failed", "details"=>$stmt4->error]);
  exit;
}


echo json_encode([
  "ok" => true,
  "game_id" => $game_id,
  "player2_id" => $player_id,
  "status" => "playing",
  "turn_player" => "player1"
]);