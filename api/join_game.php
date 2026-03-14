<?php
header('Content-Type: application/json; charset=utf-8'); //Δηλωνει οτι θα επιστρεψει JSON 
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost"; // Σε αυτο το block 6-17 γινεται η συνδεση με την βαση
$user = "iee2019231"; //Η συνδεση βασιζεται στο socket και οχι το TCP (localhost)
$pass = "Ptuxiosta5!!";    //καθως η βαση βρισκεται στο users και οχι τοπικα
$db   = "ADISE25_Progress_db";  
$socket = "/home/student/iee/2019/iee2019231/mysql/run/mysql.sock";  

$mysqli = new mysqli($host, $user, $pass, $db, null, $socket);
if ($mysqli->connect_error) {
  http_response_code(500); //Error σε περιπτωση αδυναμιας συνδεσης με την ΒΔ
  echo json_encode(["ok"=>false, "error"=>"db_connect_failed", "details"=>$mysqli->connect_error]);
  exit;
}

$username = trim($_POST["username"] ?? ""); //o client πχ τερματικο στελνει τα
$game_id  = (int)($_POST["game_id"] ?? 0); //username και game_id

if ($username === "" || $game_id <= 0) { //Αν δεν δοθει ονομα ή id παιχνιδιου
  http_response_code(400);   //Επιστροφη error
  echo json_encode(["ok"=>false, "error"=>"missing_username_or_game_id"]);
  exit;
}

//1) Βρισκει ή φτιάχνει player
//Και σε αυτο το σημειο ελεγχει αν υπαρχει ο παικτης με το username που δοθηκε
//Ψανχει το ιδιο ονομα στον πινακα players
$stmt = $mysqli->prepare("SELECT id FROM players WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) { //αν υπαρχει το player_id
  $player_id = (int)$row["id"]; // παιρνει το συγκεκριμενο id,
} else { //αλλιως δημιουργει καινουργια εγγραφη στον πινακα players 
  $stmt2 = $mysqli->prepare("INSERT INTO players (username) VALUES (?)");
  $stmt2->bind_param("s", $username);
  if (!$stmt2->execute()) {
    http_response_code(500);
    echo json_encode(["ok"=>false, "error"=>"player_create_failed", "details"=>$stmt2->error]);
    exit;
  }
  $player_id = (int)$stmt2->insert_id;
}

// 2) Ελεγχει αν το παιχνιδι ειναι valid και joinable //
$stmt3 = $mysqli->prepare("SELECT Status, Player1_id, Player2_id FROM games WHERE ID = ?");
$stmt3->bind_param("i", $game_id);
$stmt3->execute();
$g = $stmt3->get_result()->fetch_assoc();

//Και κανει 4 ελεγχους

if (!$g) { //1) Αν δεν υπαρχει το game => game_not_found
  http_response_code(404);
  echo json_encode(["ok"=>false, "error"=>"game_not_found"]);
  exit;
}

if ($g["Status"] !== "waiting") { // 2) Αν το status του game δεν ειναι waiting
  http_response_code(400);  // τοτε => game_not_joinable
  echo json_encode(["ok"=>false, "error"=>"game_not_joinable", "status"=>$g["Status"]]);
  exit;
}

if (!empty($g["Player2_id"])) { // 3) Αν υπαρχει ηδη 2ος παικτης ( δηλ not empty )
  http_response_code(400);  // => game_already_has_player2
  echo json_encode(["ok"=>false, "error"=>"game_already_has_player2"]);
  exit;
}

if ((int)$g["Player1_id"] === $player_id) { // 4) Αν ο ιδιος ο παικτης προσπαθει να μπει 
  http_response_code(400); //δευτερη φορα => same_player_cannot_join_twice
  echo json_encode(["ok"=>false, "error"=>"same_player_cannot_join_twice"]);
  exit;
}


// 3 ) Αν ολα καλα με τους παραπανω ελεγχους, ο παικτης  μπαινει στο παιχνιδι 
//στην θεση seat = 2!
$seat = 2;  //Γινεται νεα εγγραφη στον πινακα game_players με τα παρακατω πεδια και τιμες
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
//Μπηκε και ο 2ος παικτης και η παρτιδα το παιχνιδι ξεκινα
//Εδω γινονται 3 πραγματα : 
//a) Γραφεται ο player2  , b) οριζεται οτι παιζει πρωτος ο player1 , c)αλλαζει το status
$status = "playing";
$stmt4 = $mysqli->prepare("UPDATE games SET Player2_id = ?, Turn_Player_id = Player1_id, Status = ? WHERE ID = ?");
$stmt4->bind_param("isi", $player_id, $status, $game_id);

if (!$stmt4->execute()) {
  http_response_code(500);
  echo json_encode(["ok"=>false, "error"=>"game_update_failed", "details"=>$stmt4->error]);
  exit;
}


//Επιστροφη JSON και δηλωση οτι μπηκε και ο δευτερος παικτης στο παιχνιδι
// και το παιχνιδι ξεκινησε με σειρα τον παικτη 1!
echo json_encode([
  "ok" => true,
  "game_id" => $game_id,
  "player2_id" => $player_id,
  "status" => "playing",
  "turn_player" => "player1"
]); 

/*Επιτρεπει σε εναν δευτερο παικτη να μπει σε ενα ηδη υπαρχον παιχνιδι. 
Πρωτα ελεγχει οτι ο παικτης υπαρχει και αν οχι τον δημιουργει. Μετα ελεγχει οτι 
το παιχνιδι υπαρχει και οτι ειναι σε κατασταση waiting. 
Αν ολα ειναι σωστα, προσθετει τον δευτερο παικτη στην θεση 2 του τραπεζιου. */