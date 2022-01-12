<?php

require_once "../lib/utils.php";

function show_users() {
	global $mysqli;
	$sql = 'select * from players';
	$st = $mysqli->prepare($sql);
	$st->execute();
	$res = $st->get_result();
	header('Content-type: application/json');
	print json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);
}

function show_user($color) {
	global $mysqli;
	$sql = 'select * from players where piece_color=?';
	$st = $mysqli->prepare($sql);
	$st->bind_param('s',$color);
	$st->execute();
	$res = $st->get_result();
	header('Content-type: application/json');
	print(json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT));
}

function set_user($color,$input) {
	if(!isset($input['username']) || $input['username'] == '') bad_request("No username given");

	$username=$input['username'];
	global $mysqli;
	$sql = 'select count(*) as c from players where piece_color=? and username is not null';
	$st = $mysqli->prepare($sql);
	$st->bind_param('s',$color);
	$st->execute();
	$res = $st->get_result();
	$r = $res->fetch_all(MYSQLI_ASSOC);
	if ($r[0]['c'] > 0) bad_request("Player $color is already set. Please select another color");
	$sql = 'update players set username=?, token=md5(CONCAT( ?, NOW()))  where piece_color=?';
	$st2 = $mysqli->prepare($sql);
	$st2->bind_param('sss',$username,$username,$color);
	$st2->execute();
	
	update_game_status();
	$sql = 'select * from players where piece_color=?';
	$st = $mysqli->prepare($sql);
	$st->bind_param('s',$color);
	$st->execute();
	$res = $st->get_result();
	header('Content-type: application/json');
	print(json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT));
}

function handle_user($method, $color,$input) {
	if($method == 'GET') show_user($color);
	elseif ($method == 'PUT') set_user($color,$input);
}

function current_color($token) {
	global $mysqli;

	if ($token == null) return(null);

	$sql = 'select * from players where token=?';
	$st = $mysqli->prepare($sql);
	$st->bind_param('s',$token);
	$st->execute();
	$res = $st->get_result();
	$row = $res->fetch_assoc();
	return($row['piece_color']);
}

function pieces_placed($token) {
	global $mysqli;

	$sql = 'select * from players where token=?';
	$st = $mysqli->prepare($sql);
	$st->bind_param('s', $token);
	$st->execute();
	$res = $st->get_result();
	$row = $res->fetch_assoc();
	return $row['pieces_placed'];
}

function can_fly($token) {
	global $mysqli;

	$sql = 'select can_fly from players where token=?';
	$st = $mysqli->prepare($sql);
	$st->bind_param('s', $token);
	$st->execute();
	$res = $st->get_result();
	$row = $res->fetch_assoc();
	if ($row['can_fly'] == 1) return True;
	return False;
}

function pieces_remaining($color) {
	global $mysqli;

	$sql = 'select * from players where piece_color=?';
	$st = $mysqli->prepare($sql);
	$st->bind_param('s', $color);
	$st->execute();
	$res = $st->get_result();
	$row = $res->fetch_assoc();
	return $row['pieces_remaining'];
}

?>