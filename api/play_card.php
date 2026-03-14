<?php
header('Content-Type: application/json; charset=utf-8');

// Συνδεση με την Βαση μεσω Socket καθως
$host = "localhost"; // η βαση δεν ειναι πλεον τοπικα αλλα στο users
$user = "iee2019231";
$pass = "Ptuxiosta5!!";
$db   = "ADISE25_Progress_db";
$socket = "/home/student/iee/2019/iee2019231/mysql/run/mysql.sock";

$mysqli = new mysqli($host, $user, $pass, $db, null, $socket);
if ($mysqli->connect_error) {
  http_response_code(500);
  echo json_encode(["ok"=>false, "error"=>"db_connect_failed", "details"=>$mysqli->connect_error]);
  exit;
}


function json_out($arr, $code=200){
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

function rank_of($card){
  return substr($card, 0, -1); // "10S"->"10", "JH"->"J"
}

// Δεχεται σαν ορισματα τα game_id,username,card
$game_id  = intval($_POST["game_id"] ?? 0);
$username = trim($_POST["username"] ?? "");
$card     = trim($_POST["card"] ?? "");

if ($game_id <= 0 || $username === "" || $card === "") { //Ελεγχει αν λειπει καποια παραμετρος
  json_out(["ok"=>false,"error"=>"missing_params","need"=>"game_id, username, card"], 400);
}

// Απο το ονομα που δεχεται, βρισκει τον παικτη στην βαση ψαχνοντας στον 
//πινακα players
$stmt = $mysqli->prepare("SELECT id FROM players WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
if (!$row) json_out(["ok"=>false,"error"=>"player_not_found"], 404);
$player_id = intval($row["id"]);

//Φορτωνει το παιχνιδι και ελεγχει
//αν υπαρχει το game, αν ειναι σε κατασταση playing και αν ακολουθειται η σωστη σειρα στους παικτες
$stmt = $mysqli->prepare("SELECT id, status, player1_id, player2_id, turn_player_id, state_json FROM games WHERE id=?");
$stmt->bind_param("i", $game_id);
$stmt->execute();
$g = $stmt->get_result()->fetch_assoc();
if (!$g) json_out(["ok"=>false,"error"=>"game_not_found"], 404);

if ($g["status"] !== "playing") {
  json_out(["ok"=>false,"error"=>"game_not_playing","status"=>$g["status"]], 409);
}

$turn_player_id = $g["turn_player_id"] !== null ? intval($g["turn_player_id"]) : 0;
if ($turn_player_id !== $player_id) {
  json_out(["ok"=>false,"error"=>"not_your_turn","turn_player_id"=>$turn_player_id], 409);
}


//Εδω ελεγχουμε αν ο παικτης παιζει φυλλο που οντως εχει στο χερι του
$stmt = $mysqli->prepare("SELECT seat, hand_json, cards_left FROM game_players WHERE game_id=? AND player_id=?");
$stmt->bind_param("ii", $game_id, $player_id);
$stmt->execute();
$gp = $stmt->get_result()->fetch_assoc();
if (!$gp) json_out(["ok"=>false,"error"=>"player_not_in_game"], 409);

$hand = json_decode($gp["hand_json"] ?? "[]", true);
if (!is_array($hand)) $hand = [];

if (!in_array($card, $hand, true)) {
  json_out(["ok"=>false,"error"=>"card_not_in_hand","card"=>$card,"hand"=>$hand], 409);
}

//Φορτωνει στην βαση το state_json
$state = json_decode($g["state_json"] ?? "{}", true);
if (!is_array($state)) $state = [];
if (!isset($state["table"]) || !is_array($state["table"])) $state["table"] = [];
if (!isset($state["deck"])  || !is_array($state["deck"]))  $state["deck"]  = [];

$table = $state["table"]; // table BEFORE play


//Capture - Xeri λογικη
$did_capture = false;
$is_xeri = false;
$is_jack_xeri = false;

$card_rank = rank_of($card);

$pre_count = count($table);//εδω κοιταει το τελευταιο φυλλο που ειναι ηδη πανω στο τραπεζι
$prev_top  = ($pre_count >= 1) ? $table[$pre_count - 1] : null;
$prev_rank = $prev_top ? rank_of($prev_top) : null;

// Εδω κανω capture (μπαζα)
if ($pre_count > 0) {
  if ($card_rank === "J") { //μαζευω με J
    $did_capture = true; // ή με ιδιο αριθμο φυλλου
  } elseif ($prev_rank !== null && $card_rank === $prev_rank) {
    $did_capture = true;
  }
}

// Εδω γινεται η ξερη
// Κανω capture οταν υπαρχει μονο 1 καρτα στο τραπεζι πριν παιξει ο παικτης 
if ($did_capture && $pre_count === 1) {
  $is_xeri = true;
  if ($card_rank === "J") $is_jack_xeri = true; //αν εγινε το μαζεμα με J τοτε ειναι ξερη με βαλε
}


// Ενημερωση των δεδομενων
// Σε περιπτωση ξερης : 
//Διαβαζει το captured_json , Βαζει μεσα ολα τα φυλλα του τραπεζιου, βαζει και το φυλλο που επαιξε ο παικτης και ενημερωνει το captured_json
if ($did_capture) {
  // διαβαζει το captured_json
  $stmt = $mysqli->prepare("SELECT captured_json FROM game_players WHERE game_id=? AND player_id=?");
  $stmt->bind_param("ii", $game_id, $player_id);
  $stmt->execute();
  $capRow = $stmt->get_result()->fetch_assoc();

  $captured = json_decode($capRow["captured_json"] ?? "[]", true);
  if (!is_array($captured)) $captured = [];

  // Κανε capture: ολες τις καρτες που επεσαν στο τραπεζι καθως και την καρτα που επεσε τελευταια
  foreach ($table as $c) $captured[] = $c;
  $captured[] = $card;

  $captured_json = json_encode($captured, JSON_UNESCAPED_UNICODE);

  $stmt = $mysqli->prepare("UPDATE game_players SET captured_json=? WHERE game_id=? AND player_id=?");
  $stmt->bind_param("sii", $captured_json, $game_id, $player_id);
  $stmt->execute();

  // Μετα, αν ειναι ξερη => αυξανει το xeri_count
  //Αν ειναι jack xeri => αυξανει το xeri_jack_count
  if ($is_xeri) {
    if ($is_jack_xeri) {
      $stmt = $mysqli->prepare("UPDATE game_players SET xeri_jack_count = xeri_jack_count + 1 WHERE game_id=? AND player_id=?");
      $stmt->bind_param("ii", $game_id, $player_id);
      $stmt->execute();
    } else {
      $stmt = $mysqli->prepare("UPDATE game_players SET xeri_count = xeri_count + 1 WHERE game_id=? AND player_id=?");
      $stmt->bind_param("ii", $game_id, $player_id);
      $stmt->execute();
    }
  }
  // Κανει clear table
  $table = [];

  //Αν δεν εγινε captured , τοτε το φυλλο μπαινει απλα πανω στο τραπεζι
} else {
  
  $table[] = $card;
}

/* ----------------Εδω αφαιρει το φυλλο απο το χερι ---------------- */
$new_hand = [];
$removed = false;
foreach ($hand as $h) {
  if (!$removed && $h === $card) { $removed = true; continue; }
  $new_hand[] = $h;
}
$cards_left = max(0, intval($gp["cards_left"]) - 1);
$hand_json = json_encode(array_values($new_hand), JSON_UNESCAPED_UNICODE);

/* ---------------- Update player hand/cards_left ---------------- */
$stmt = $mysqli->prepare("UPDATE game_players SET hand_json=?, cards_left=? WHERE game_id=? AND player_id=?");
$stmt->bind_param("siii", $hand_json, $cards_left, $game_id, $player_id);
$stmt->execute();

/* ---------------Εδω αλλαζει σειρα των παικτων ---------------- */
$player1_id = intval($g["player1_id"]);
$player2_id = intval($g["player2_id"]);
$next_turn_player_id = ($player_id === $player1_id) ? $player2_id : $player1_id;

/* ---------------- Save state_json + turn in games ---------------- */
$state["table"] = $table;
$state_json = json_encode($state, JSON_UNESCAPED_UNICODE);

$stmt = $mysqli->prepare("UPDATE games SET state_json=?, turn_player_id=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
$stmt->bind_param("sii", $state_json, $next_turn_player_id, $game_id);
$stmt->execute();

//Καθε κινηση καταγραφεται στον πινακα moves ωστε να υπαρχει το ιστορικο 
//των κινησεων των παικτων για παν ενδεχομενο
$action = $did_capture ? "capture" : "play";
$move_obj = [
  "card" => $card,
  "did_capture" => $did_capture,
  "is_xeri" => $is_xeri,
  "is_jack_xeri" => $is_jack_xeri
];
$move_json = json_encode($move_obj, JSON_UNESCAPED_UNICODE);

$stmt = $mysqli->prepare("SELECT COUNT(*) AS c FROM moves WHERE game_id=?");
$stmt->bind_param("i", $game_id);
$stmt->execute();
$crow = $stmt->get_result()->fetch_assoc();
$turn_no = intval($crow["c"]) + 1;

//Εδω βλεπουμε τι καταγραφεται στον πινακα moves
$stmt = $mysqli->prepare("INSERT INTO moves (game_id, player_id, action, card, move_json, turn_no) VALUES (?,?,?,?,?,?)");
$stmt->bind_param("iisssi", $game_id, $player_id, $action, $card, $move_json, $turn_no);
$stmt->execute();

//Κλασσικη επιστροφη JSON
json_out([
  "ok" => true,
  "game_id" => $game_id,
  "played_by" => $username,
  "player_id" => $player_id,
  "card" => $card,
  "did_capture" => $did_capture,
  "is_xeri" => $is_xeri,
  "is_jack_xeri" => $is_jack_xeri,
  "table" => $table,
  "next_turn_player_id" => $next_turn_player_id,
  "cards_left" => $cards_left
]);

/*Το play_card ειναι το endpoint που εκτελει την κινηση του παικτη.
Πρωτα ελεγχει οτι το παιχνιδι υπαρχει , οτι ειναι σε κατασταση playing ,
οτι ειναι η σωστη σειρα και οτι ο παικτης εχει οντως το φυλλο στο χερι του.
Μετα εφαρμοζει τοους κανονες του παιχνιδιου για τις μπαζες και τις ξερες
 - ειτε απλη ειτε με Jack -. Αν το capture γινει οταν υπαρχει μονο μια καρτα 
πανω τοτε ειναι ξερη κλπ ... Τελος ενημερωνει το χερι του παικτη, την κατασταση του τραπεζιου
πραγματα που καταγραφει στον πινακα moves.*/