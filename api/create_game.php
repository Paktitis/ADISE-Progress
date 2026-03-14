<?php
header('Content-Type: application/json; charset=utf-8'); //επιστροφη απαντησης σε JSON

$host = "localhost";    //Εδω κανει συνδεση με την βαση
$user = "iee2019231";   
$pass = "Ptuxiosta5!!";
$db   = "ADISE25_Progress_db";
$socket = "/home/student/iee/2019/iee2019231/mysql/run/mysql.sock";
//------για να μπορει να διαβσει/γραψει δεδομενα
$mysqli = new mysqli($host, $user, $pass, $db, null, $socket);
if ($mysqli->connect_error) {
  http_response_code(500);  //αν αποτυχει, επιστρεφει error
  echo json_encode(["ok"=>false, "error"=>"db_connect_failed", "details"=>$mysqli->connect_error]);
  exit;
}

$username = trim($_POST["username"] ?? "");  //Περιμενει το χρηστη
if ($username === "") {                   // να βαλει username
  http_response_code(400);    // αν δεν σταλθει βγαζει error
  echo json_encode(["ok"=>false, "error"=>"missing_username"]);
  exit;
}

// 1) Εδω γινεται ο ελεγχος! ( Βρίσκει ή φτιαχνει player )
//Παίρνει το username που δοθηκε πιο πανω και ψαχνει αν υπαρχει ηδη
//ο παικτης στον πινακα players.
$stmt = $mysqli->prepare("SELECT id FROM players WHERE username = ?");
$stmt->bind_param("s", $username); //στελνει το usrnm στο querry
$stmt->execute(); //εκτελει το querry και παιρνει αποτελεσμα
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {  //αν υπαρχει το player_id
  $player_id = (int)$row["id"];   //παιρνει το συγκεκριμενο id
} else {   // αλλιως, δημιουργει νεο παικτη στην βαση με αυτο το id
  $stmt2 = $mysqli->prepare("INSERT INTO players (username) VALUES (?)");
  $stmt2->bind_param("s", $username);
  if (!$stmt2->execute()) {
    http_response_code(500);
    echo json_encode(["ok"=>false, "error"=>"player_create_failed", "details"=>$stmt2->error]);
    exit;
  }
  $player_id = (int)$stmt2->insert_id;
}

// 2) Δημιουργια παρτιδας
//Φτιαχνει νεα εγγραφη στον πινακα games με τα πεδια που φαινονται παρακατω
$status = "waiting";
$stmt3 = $mysqli->prepare("INSERT INTO games (Status, Player1_id) VALUES (?, ?)");
$stmt3->bind_param("si", $status, $player_id);
if (!$stmt3->execute()) {
  http_response_code(500);
  echo json_encode(["ok"=>false, "error"=>"game_create_failed", "details"=>$stmt3->error]);
  exit;
}
$game_id = (int)$stmt3->insert_id; //παιρνει το id της νεας παρτιδας που δημιουργησε η βαση 


// 3) Βαλε τον player στο game_players (seat=1)
// Παιρνει το game_id της παρτιδας που εφτιαξε παραπανω και 
// συνδεει τον 1ο παικτη με την συγκεκριμενη παρτιδα και τον βαζει στην θεση 1
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

/*Το endpoint παιρνει σαν input ενα username. Ελεγχει αν υπαρχει ηδη στον πινακα 
players και αν οχι τον δημιουργει. Μετα φτιαχνει ενα νεο παιχνιδι στον πινακα
games με status waiting και περιμενει τον δευτερο παικτη. Τελος επιστρεφει JSON
με το game_id και player_id και βαζει τον πρωτο παικτη στον πινακα
game_players στην θεση 1
*/