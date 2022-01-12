<?php

function get_board() {
	global $mysqli;

	$sql = 'select * from board';
	$st = $mysqli->prepare($sql);
	$st->execute();
	$res = $st->get_result();
	header('Content-type: application/json');
	$board = $res->fetch_all(MYSQLI_ASSOC);
	return $board;
}

function show_board() {
	$board = get_board();

	print("  0  1  2  3  4  5  6\n");
	$line = 1;
	for ($i = 0; $i < count($board); $i++) {
		if ($line == 1)
			print($board[$i]['x']);
		if ($board[$i]['piece_color'] == 'n')
			print('___');
		elseif ($board[$i]['piece_color'] == 'w') 
			print('|w|');
		elseif ($board[$i]['piece_color'] == 'b') 
			print('|b|');
		else
			print('|.|');

		if ($line == 7) {
			print("\n");
			$line = 0;
		}
		$line++;
	}
}

function reset_board() {
	global $mysqli;
	$mysqli->query('call clean_board()');
}

function show_piece($input) {
	global $mysqli;

	$x = $input['x'];
	$y = $input['y'];
	if ($x == null || $y == null) bad_request("Choose x and y coords of piece to show");
	
	$sql = 'select * from board where x=? and y=?';
	$st = $mysqli->prepare($sql);
	$st->bind_param('ii',$x,$y);
	$st->execute();
	$res = $st->get_result();
	header('Content-type: application/json');
	print json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);
}

function handle_piece_impl($input) {
	$token = $input['token'];
	if ($token == null || $token == '') bad_request("Token is not set");

	$color = current_color($token);
	if ($color == null) bad_request("You are not a player of this game");

	$status = read_status();

	if ($status['status'] != 'started') bad_request("Game is not in action");
	if ($status['player_turn'] != $color) bad_request("It is not your turn");
	if ($status['elimination'] == 1) eliminate_piece($input);
	elseif (pieces_placed($token) != 9) place_piece($input);
	else move_piece($input);
}

function move_piece($input) {
	$board = get_board();

	$x1 = $input['x1'];
	$y1 = $input['y1'];
	$x2 = $input['x2'];
	$y2 = $input['y2'];
	if ($x1 == null || $y1 == null || $x2 == null || $y2 == null) bad_request("No positions x1,y1,x2,y2 provided");

	// Check if a piece is placed at x2,y2 or if no piece is allowed to be placed (designated by 'n' as in none)
	if ($board[$x2*7+$y2]['piece_color'] != null) bad_request("Invalid position to move to");
	// Check if piece at position x1,y1 is ours
	if ($board[$x1*7+$y1]['piece_color'] != current_color($input['token'])) bad_request("Not your piece to move or there is no piece at this position to move");

	$token = $input['token'];
	if (! can_fly($token) && ! can_move($x1, $y1, $x2, $y2)) bad_request("You can't move there");

	global $mysqli;
	$sql = 'call move_piece(?,?,?,?)';
	$st = $mysqli->prepare($sql);
	$st->bind_param('iiii', $x1, $y1, $x2, $y2);
	$st->execute();

	elimination_check(current_color($input['token']), $x2, $y2);
}

function place_piece($input) {
	$token = $input['token'];
	$pieces_placed = pieces_placed($token);
	if ($pieces_placed == 9) bad_request("You placed all your pieces");

	$board = get_board();
	$x = $input['x'];
	$y = $input['y'];
	if ($x == null || $y == null) bad_request("No positions x & y provided");
	if ($board[$x*7+$y]['piece_color'] != null) bad_request("Invalid position or already taken");

	global $mysqli;
	$sql = 'call place_piece(?,?,?,?)';
	$st = $mysqli->prepare($sql);
	$pieces_placed++;
	$st->bind_param('iiis', $x, $y, $pieces_placed, $token);
	$st->execute();

	elimination_check(current_color($token), $x, $y);
}

function eliminate_piece($input) {
	$token = $input['token'];
	$color = current_color($token);
	$other_color = $color == 'w' ? 'b' : 'w';

	$x = $input['x'];
	$y = $input['y'];
	if ($x == null || $y == null) bad_request("No positions x & y provided");
	// Check if a piece other than the opponent's color is placed at x,y
	if (get_board()[$x*7+$y]['piece_color'] != $other_color) bad_request("Invalid position to eliminate");

	global $mysqli;
	$sql = 'call eliminate_piece(?,?,?)';
	$st = $mysqli->prepare($sql);
	$st->bind_param('iis', $x, $y, $other_color);
	$st->execute();

	// If the opponent has only three pieces remaining, then the opponent can fly
	if (pieces_remaining($other_color) == 3) {
		$sql = "update players set can_fly=1 where piece_color=?";
		$st = $mysqli->prepare($sql);
		$st->bind_param('s', $other_color);
		$st->execute();
	}
	// If the opponent has only two pieces remaining the game ends
	else if (pieces_remaining($other_color) == 2) {
		$sql = "update game_status set status='ended', result=?, player_turn=null";
		$st = $mysqli->prepare($sql);
		$st->bind_param('s', $color);
		$st->execute();
	}

	update_turn();
}

function elimination_check($color, $x, $y) {
	$board = get_board();

	$lines = array(
		[[0,0],[0,3],[0,6]],
		[[0,0],[3,0],[6,0]],
		[[0,6],[3,6],[6,6]],
		[[6,0],[6,3],[6,6]],

		[[1,1],[1,3],[1,5]],
		[[1,1],[3,1],[5,1]],
		[[1,5],[3,5],[5,5]],
		[[5,1],[5,3],[5,5]],

		[[2,2],[2,3],[2,4]],
		[[2,2],[3,2],[4,2]],
		[[2,4],[3,4],[4,4]],
		[[4,2],[4,3],[4,4]],

		[[0,3],[1,3],[2,3]],
		[[3,0],[3,1],[3,2]],
		[[3,4],[3,5],[3,6]],
		[[4,3],[5,3],[6,3]]
	);

	for ($i = 0; $i < count($lines); $i++)
	{
		$x1 = $lines[$i][0][0];
		$y1 = $lines[$i][0][1];
		$x2 = $lines[$i][1][0];
		$y2 = $lines[$i][1][1];
		$x3 = $lines[$i][2][0];
		$y3 = $lines[$i][2][1];

		// If a line has 3 pieces of the same color and the coordinates
		// of the piece we just placed is part of this line, then we have formed
		// a new line, so the next round will be an elimination.
		if ($board[$x1*7+$y1]['piece_color'] == $color &&
			$board[$x2*7+$y2]['piece_color'] == $color &&
			$board[$x3*7+$y3]['piece_color'] == $color &&
			(($x == $x1 && $y == $y1) ||
			 ($x == $x2 && $y == $y2) ||
			 ($x == $x3 && $y == $y3)))
		{
			global $mysqli;
			$st = $mysqli->prepare('update game_status set elimination=1');
			$st->execute();
			return;
		}
	}

	update_turn();
}

function can_move($x1, $y1, $x2, $y2) {
	$legal_moves = array(
		"00"=>[[3,0],[0,3]],
		"03"=>[[0,0],[0,6],[1,3]],
		"06"=>[[3,6],[0,3]],

		"11"=>[[3,1],[1,3]],
		"13"=>[[0,3],[1,1],[2,3],[1,5]],
		"15"=>[[1,3],[3,5]],

		"22"=>[[2,3],[3,2]],
		"23"=>[[2,2],[1,3],[2,4]],
		"24"=>[[2,3],[3,4]],

		"30"=>[[0,0],[6,0],[3,1]],
		"31"=>[[3,0],[1,1],[3,2],[5,1]],
		"32"=>[[2,2],[3,1],[4,2]],
		"34"=>[[2,4],[4,4],[3,5]],
		"35"=>[[3,4],[1,5],[3,6],[5,5]],
		"36"=>[[0,6],[3,5],[6,6]],

		"42"=>[[3,2],[4,3]],
		"43"=>[[4,2],[5,3],[4,4]],
		"44"=>[[4,3],[3,4]],

		"51"=>[[3,1],[5,3]],
		"53"=>[[4,3],[5,1],[5,5],[6,3]],
		"55"=>[[5,3],[3,5]],

		"60"=>[[3,0],[6,3]],
		"63"=>[[6,0],[5,3],[6,6]],
		"66"=>[[6,3],[3,6]]
	);

	$key = strval($x1).strval($y1);
	$value = [$x2, $y2];
	return in_array($value, $legal_moves[$key]);
}

?>