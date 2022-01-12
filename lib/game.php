<?php

function show_status() {
	global $mysqli;
	
	check_abort();
	
	$sql = 'select * from game_status';
	$st = $mysqli->prepare($sql);

	$st->execute();
	$res = $st->get_result();

	header('Content-type: application/json');
	print(json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT));
}

function check_abort() {
	global $mysqli;
	
	$sql = "update game_status set status='aborted', result=if(player_turn='w','b','w'),player_turn=null where player_turn is not null and last_change<(now()-interval 5 minute) and status='started'";
	$st = $mysqli->prepare($sql);
	$r = $st->execute();
}

function update_game_status() {
	global $mysqli;
	
	$status = read_status();
	$new_status = null;
	$new_turn = null;
	
	$st3 = $mysqli->prepare('select count(*) as aborted from players where last_action< (now() - interval 5 minute)');
	$st3->execute();
	$res3 = $st3->get_result();
	$aborted = $res3->fetch_assoc()['aborted'];
	if ($aborted > 0) {
		$sql = 'update players set username=null, token=null where last_action< (now() - interval 5 minute)';
		$st2 = $mysqli->prepare($sql);
		$st2->execute();
		if ($status['status'] == 'started') $new_status = 'aborted';
	}
	
	$sql = 'select count(*) as c from players where username is not null';
	$st = $mysqli->prepare($sql);
	$st->execute();
	$res = $st->get_result();
	$active_players = $res->fetch_assoc()['c'];
	
	switch($active_players) {
		case 0:
			$new_status='not_active'; break;
		case 1:
			$new_status='initialized'; break;
		case 2:
			$new_status='started'; 
			if ($status['player_turn'] == null) $new_turn = 'w'; // It was not started before...
			break;
	}

	$sql = 'update game_status set status=?, player_turn=?';
	$st = $mysqli->prepare($sql);
	$st->bind_param('ss',$new_status,$new_turn);
	$st->execute();
}

function read_status() {
	global $mysqli;
	
	$sql = 'select * from game_status';
	$st = $mysqli->prepare($sql);

	$st->execute();
	$res = $st->get_result();
	$status = $res->fetch_assoc();
	return($status);
}

function update_turn() {
	$color = read_status()['player_turn'];
	$other_color = ($color == 'w') ? 'b' : 'w';

	global $mysqli;
	$sql = 'update game_status set player_turn=?';
	$st = $mysqli->prepare($sql);
	$st->bind_param('s', $other_color);
	$st->execute();
}

function reset_game() {
	global $mysqli;
	$mysqli->query('call reset_game()');
}

?>