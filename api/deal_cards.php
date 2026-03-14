<?php
header('Content-Type: application/json; charset=utf-8'); //Επιστροφη JSON response
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";   //Σε αυτο το block 6-17 γινεται η συνδεση με την βαση
$user = "iee2019231";  //Η συνδεση βασιζεται στο socket και οχι το TCP (localhost)
$pass = "Ptuxiosta5!!";   //καθως η βαση βρισκεται στο users και οχι τοπικα
$db   = "ADISE25_Progress_db";
$socket = "/home/student/iee/2019/iee2019231/mysql/run/mysql.sock";

$mysqli = new mysqli($host, $user, $pass, $db, null, $socket);
if ($mysqli->connect_error) {
  http_response_code(500);
  echo json_encode(["ok"=>false, "error"=>"db_connect_failed", "details"=>$mysqli->connect_error]);
  exit;
}

$game_id = (int)($_POST["game_id"] ?? 0); //Περιμενει να λαβει το game_id απο τον client
if ($game_id <= 0) {  //Αν δεν λαβει => missing_game_id
  echo json_encode(["ok" => false, "error" => "missing_game_id"]);
  exit;
}

//Με το SELECT παρακατω, βρισκει την παρτιδα
//Οταν παρει το game_id, επιλεγει τα παρακατω πεδια απο τον πινακα games
$q = $mysqli->query("SELECT ID, Status, Player1_id, Player2_id, State_json FROM games WHERE ID=$game_id");
$g = $q->fetch_assoc();

//Παρακατω γινονται καποιο ελεγχοι για το παιχνιδι!
if (!$g) { // Ελεγχει αν υπαρχει το παιχνιδι
  echo json_encode(["ok" => false, "error" => "game_not_found"]);
  exit;
}

if ($g["Status"] == "finished") { // Ελεγχος στο αν εχει τελειωσει
  echo json_encode([  //αν ναι => δεν μοιραζει αλλες καρτες
    "ok" => false,
    "error" => "game_finished",
    "message" => "Game is finished. No more cards to deal. Check final results."
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($g["Status"] != "playing") { //Ελεγχος στο αν ΔΕΝ ειναι playing η κατασταση του παιχνιδιου
  echo json_encode([   
    "ok" => false,      
    "error" => "game_not_ready",
    "status" => $g["Status"]
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

//Εδω ελεγχει αν υπαρχουν και οι 2 παικτες
//Αν δεν κλεισει το τραπεζι με 2 παικτες => δεν μοιραζει => δεν ξεκινα το παιχνιδι!
if (empty($g["Player1_id"]) || empty($g["Player2_id"])) { 
  echo json_encode(["ok" => false, "error" => "missing_players"]);
  exit;
}

$p1 = (int)$g["Player1_id"];
$p2 = (int)$g["Player2_id"];


//Εδω ελεγχουμε απο τον πινακα game_players ποσα φυλλα εχουν οι παικτες.
//Το deal γινεται μονο οταν και οι 2 παικτες εχουν τελειωσει τα φυλλα 
//του χεριου τους
$stmt = $mysqli->prepare("SELECT Player_id, Cards_left FROM game_players WHERE Game_id=?");
$stmt->bind_param("i", $game_id); //Παιρνει τα πεδια αυτα απο τον game_players
$stmt->execute();   //και με συγκεκριμενο game_id ( της παρτιδας )
$res = $stmt->get_result();  // διαβαζει ποσα φυλλα εχουν οι παικτες

$cards = [];
while ($r = $res->fetch_assoc()) { //αποθηκευει σε εναν πινακα τα φυλλα που εχου στα χερια τους
  $cards[(int)$r["Player_id"]] = (int)$r["Cards_left"]; // οι παικτες, της συγκεκριμενης παρτιδας
}

if (!isset($cards[$p1]) || !isset($cards[$p2])) {
  echo json_encode(["ok" => false, "error" => "game_players_rows_missing"]);
  exit;
}

if ($cards[$p1] > 0 || $cards[$p2] > 0) { //Ελεγχει αν καποιος εχει ακομα φυλλα
  echo json_encode([  //αν ναι => δεν επιτρεπει νεο μοιρασμα
    "ok" => false,
    "error" => "players_still_have_cards",
    "p1_cards" => $cards[$p1],
    "p2_cards" => $cards[$p2]
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

//Εδω φορτώνεται το παιχνιδι
//Το state_json ειναι η κατασταση του παιχνιδιου και κραταει την δυναμικη κατασταση της παρτιδας
// πχ table (φύλλα στο τραπεζι), deck (τραπουλα), captured = μπαζα , last_capturer κλπ
$state = []; //διαβαζει την κατασταση παιχνιδιου απο την βαση και την 
if (!empty($g["State_json"])) { // μετατρεπει σε array
  $tmp = json_decode($g["State_json"], true);
  if (is_array($tmp)) $state = $tmp;
}

//Βεβαιώνει οτι υπαρχουν τα κυρια πεδια που θα φαινονται και στην επιστροφη json
//Αρχικοποιει την κατασταση του παιχνιδιου
if (!isset($state["table"]) || !is_array($state["table"])) $state["table"] = [];
if (!isset($state["captured"]) || !is_array($state["captured"])) $state["captured"] = [];
if (!isset($state["xeri_count"]) || !is_array($state["xeri_count"])) $state["xeri_count"] = [];
if (!array_key_exists("last_capturer", $state)) $state["last_capturer"] = null;

//Ελεγχει αν ειναι το πρωτο μοιρασμα
$is_first_deal = false;

// Αν δεν υπάρχει deck, φτιάξε καινούρια τράπουλα (ΜΟΝΟ στην αρχή της παρτίδας) 
//Φτιαχνει 52 φυλλα τραπουλας
if (!isset($state["deck"]) || !is_array($state["deck"])) {
  $suits = ["S", "H", "D", "C"];
  $ranks = ["A", "2", "3", "4", "5", "6", "7", "8", "9", "10", "J", "Q", "K"];
  $deck = [];

  foreach ($suits as $s) {
    foreach ($ranks as $r) {
      $deck[] = $r . $s; //Με τον πολ/σμο φτιαχνονται τα φυλλα
    }
  }

  shuffle($deck); //Εδω ανακατευει την τραπουλα
  $state["deck"] = $deck;
  $is_first_deal = true;
}

$deck = $state["deck"];

//Σημαντικος ελεγχος - κανονας παιχνιδιου
//Αν δεν υπαρχουν αρκετα φυλλα για να μοιρασει 6-6 τοτε τελειωνει το παιχνιδι!
if (count($deck) < 12) {

  // Ο τελευταιος που πηρε μπαζα, παιρνει και οτι εχει το τραπεζι
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

//Μοιραζει 6-6
$hand1 = array_splice($deck, 0, 6); // το array_slice τα αφαιρει απο την τραπουλα
$hand2 = array_splice($deck, 0, 6);

//Αν δεν εχει πεσει τιποτα στο τραπεζι => 1ο Deal 
//Αρα ανοιγει 4 κατω
if ($is_first_deal && count($state["table"]) == 0) {
  if (count($deck) < 4) {
    echo json_encode(["ok" => false, "error" => "deck_empty"]);
    exit;
  }

  for ($i = 0; $i < 4; $i++) { //εκτελειται 4 φορες γιατι βαζει 4
    $state["table"][] = array_shift($deck); //φυλλα κατω
  }
}

// Εδω αποθηκευω τα φυλλα των παικτων
//Γραφεται δηλαδη στην βαση , ποια φυλλα θα εχει ο καθε παικτης και οτι εχει 
//6 φυλλα στο χερι
$hand1_json = $mysqli->real_escape_string(json_encode($hand1, JSON_UNESCAPED_UNICODE));
$hand2_json = $mysqli->real_escape_string(json_encode($hand2, JSON_UNESCAPED_UNICODE));

$mysqli->query("UPDATE game_players SET Hand_json='$hand1_json', Cards_left=6 WHERE Game_id=$game_id AND Player_id=$p1");
$mysqli->query("UPDATE game_players SET Hand_json='$hand2_json', Cards_left=6 WHERE Game_id=$game_id AND Player_id=$p2");

//Εδω αποθηκευεται η νεα κατασταση του παιχνιδιου
$state["deck"] = $deck;
$new_state_json = $mysqli->real_escape_string(json_encode($state, JSON_UNESCAPED_UNICODE));
$mysqli->query("UPDATE games SET State_json='$new_state_json' WHERE ID=$game_id");

//Πολυ σημαντικο καθως εδω βρισκεται το πανω φυλλο του τραπεζιου
//Φαινεται στην απαντηση που δινει το τερματικο
$table_top = count($state["table"]) > 0 ? $state["table"][count($state["table"]) - 1] : null;

//Η απαντηση που επιστρεφει, οτι το μοιρασμα εγινε κανονικα
//Οι παικτες εχουν απο 6 φυλλα κλπ κλπ
echo json_encode([
  "ok" => true,
  "finished" => false,
  "game_id" => $game_id,
  "table_top" => $table_top,
  "p1_cards" => 6,
  "p2_cards" => 6,
  "deck_left" => count($deck)
], JSON_UNESCAPED_UNICODE);

/*Το deal_cards ελεγχει πρωτα οτι το παιχνιδι ειναι ενεργο και οτι οι παικτες
δεν εχουν αλλα φυλλα στο χερι! 
Αν ειναι η πρωτη μοιρασια, δημιουργει και ανακατευει την τραπουλα. Μετα μοιραζει
φυλλα στον καθενα και μονο στην αρχη ανοιγει 4 κατω. 
Αν δεν υπαρχουν αρκετα φυλλα για νεο μοιρασμα, το παιχνιδι τελειωνει 
και ο τελευταιος που εκανε captured παιρνει τα τελευταια φυλλα του τραπεζιου. 
*/