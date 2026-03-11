<?php
header('Content-Type: application/json; charset=utf-8');

/* ---------------- DB ---------------- */
$host = "localhost";
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

function rank_of($card){ return substr($card, 0, -1); }
function suit_of($card){ return substr($card, -1); }

/* ---------------- Input ---------------- */
$game_id = intval($_GET["game_id"] ?? $_POST["game_id"] ?? 0);
if ($game_id <= 0) json_out(["ok"=>false,"error"=>"missing_game_id"], 400);

/* ---------------- Load players in game ---------------- */
$stmt = $mysqli->prepare("
  SELECT gp.player_id, p.username, gp.captured_json, gp.xeri_count, gp.xeri_jack_count
  FROM game_players gp
  JOIN players p ON p.id = gp.player_id
  WHERE gp.game_id=?
  ORDER BY gp.seat ASC
");
$stmt->bind_param("i", $game_id);
$stmt->execute();
$res = $stmt->get_result();

$players = [];
while ($row = $res->fetch_assoc()) {
  $capt = json_decode($row["captured_json"] ?? "[]", true);
  if (!is_array($capt)) $capt = [];
  $players[] = [
    "player_id" => intval($row["player_id"]),
    "username"  => $row["username"],
    "captured"  => $capt,
    "xeri_count" => intval($row["xeri_count"] ?? 0),
    "xeri_jack_count" => intval($row["xeri_jack_count"] ?? 0),
  ];
}

if (count($players) < 2) json_out(["ok"=>false,"error"=>"need_two_players"], 409);

/* ---------------- Compute per-player details ---------------- */
$details = [];
$card_counts = []; // for 3pt rule

foreach ($players as $pl) {
  $capt = $pl["captured"];
  $count_cards = count($capt);
  $card_counts[$pl["player_id"]] = $count_cards;

  $has_2S = false;
  $has_10D = false;

  $face10_points = 0; // K,Q,J,10 except 10D
  $face10_cards = []; // for explanation

  foreach ($capt as $c) {
    $r = rank_of($c);
    $s = suit_of($c);

    if ($r === "2" && $s === "S") $has_2S = true;     // 2 spades
    if ($r === "10" && $s === "D") $has_10D = true;   // 10 diamonds

    // K,Q,J,10 (NOT 10D)
    if (in_array($r, ["K","Q","J","10"], true)) {
      if (!($r === "10" && $s === "D")) {
        $face10_points += 1;
        $face10_cards[] = $c;
      }
    }
  }

  $xeri_pts = $pl["xeri_count"] * 10;
  $xeri_j_pts = $pl["xeri_jack_count"] * 20;

  $details[$pl["player_id"]] = [
    "player_id" => $pl["player_id"],
    "username"  => $pl["username"],
    "cards_total" => $count_cards,
    "bonus_more_cards" => 0, // set later
    "bonus_2S" => $has_2S ? 1 : 0,
    "bonus_10D" => $has_10D ? 1 : 0,
    "face10_points" => $face10_points,
    "face10_cards" => $face10_cards,
    "xeri_count" => $pl["xeri_count"],
    "xeri_points" => $xeri_pts,
    "xeri_jack_count" => $pl["xeri_jack_count"],
    "xeri_jack_points" => $xeri_j_pts,
    "total" => 0 // set later
  ];
}

/* ---------------- Rule: +3 for most cards (no one if tie) ---------------- */
$max_cards = max(array_values($card_counts));
$winners = [];
foreach ($card_counts as $pid => $cnt) {
  if ($cnt === $max_cards) $winners[] = $pid;
}
if (count($winners) === 1) {
  $details[$winners[0]]["bonus_more_cards"] = 3;
}

/* ---------------- Totals & winner ---------------- */
$totals = [];
foreach ($details as $pid => $d) {
  $total =
    $d["bonus_more_cards"] +
    $d["bonus_2S"] +
    $d["bonus_10D"] +
    $d["face10_points"] +
    $d["xeri_points"] +
    $d["xeri_jack_points"];

  $details[$pid]["total"] = $total;
  $totals[$pid] = $total;
}

$max_total = max($totals);
$top = [];
foreach ($totals as $pid => $t) if ($t === $max_total) $top[] = $pid;

$winner_id = (count($top) === 1) ? $top[0] : null;

/* ---------------- Persist score + winner (optional αλλά χρήσιμο) ---------------- */
foreach ($details as $pid => $d) {
  $stmt = $mysqli->prepare("UPDATE game_players SET score=? WHERE game_id=? AND player_id=?");
  $stmt->bind_param("iii", $d["total"], $game_id, $pid);
  $stmt->execute();
}

if ($winner_id !== null) {
  $stmt = $mysqli->prepare("UPDATE games SET winner_id=?, status='finished', updated_at=CURRENT_TIMESTAMP WHERE id=?");
  $stmt->bind_param("ii", $winner_id, $game_id);
  $stmt->execute();
}

/* ---------------- Response ---------------- */
json_out([
  "ok" => true,
  "game_id" => $game_id,
  "winner_id" => $winner_id,
  "tie" => ($winner_id === null),
  "players" => array_values($details),
  "rules" => [
    "most_cards_3pts" => (count($winners)===1 ? "awarded" : "tie_no_points"),
    "2S_1pt" => true,
    "10D_1pt" => true,
    "K_Q_J_10_except_10D_1pt_each" => true,
    "xeri_10pts" => true,
    "xeri_with_J_20pts" => true
  ]
]);