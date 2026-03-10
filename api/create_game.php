<?php
header('Content-Type: application/json; charset=utf-8');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "adise_db";

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
  http_response_code(500);
  echo json_encode(["ok"=>false, "error"=>"db_connect_failed", "details"=>$mysqli->connect_error]);
  exit;
}

$username = trim($_POST["username"] ?? "");
if ($username === "") {
  http_response_code(400);
  echo json_encode(["ok"=>false, "error"=>"missing_username"]);
  exit;
}

// 1) Βρες ή φτιάξε player
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

// 2) Δημιούργησε game
$status = "waiting";
$stmt3 = $mysqli->prepare("INSERT INTO games (Status, Player1_id) VALUES (?, ?)");
$stmt3->bind_param("si", $status, $player_id);
if (!$stmt3->execute()) {
  http_response_code(500);
  echo json_encode(["ok"=>false, "error"=>"game_create_failed", "details"=>$stmt3->error]);
  exit;
}
$game_id = (int)$stmt3->insert_id;

// 3) Βάλε τον player στο game_players (seat=1)
$seat = 1;
$stmt4 = $mysqli->prepare("INSERT INTO game_players (Game_id, Player_id, Seat, Hand_json, Cards_left, xeri_count, xeri_jack_count, Score) VALUES (?, ?, ?, '[]', 0, 0,0, 0)");
$stmt4->bind_param("iii", $game_id, $player_id, $seat);
if (!$stmt4->execute()) {
  http_response_code(500);
  echo json_encode(["ok"=>false, "error"=>"game_player_create_failed", "details"=>$stmt4->error]);
  exit;
}

echo json_encode([
  "ok" => true,
  "game_id" => $game_id,
  "player_id" => $player_id,
  "status" => $status
]);