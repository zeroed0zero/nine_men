<?php

require_once "../lib/dbconnect.php";
require_once "../lib/board.php";
require_once "../lib/users.php";
require_once "../lib/game.php";
require_once "../lib/utils.php";

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
$input = json_decode(file_get_contents('php://input'),true);

if ($input == null) $input = [];

switch (array_shift($request)) {
	case 'board': 
		switch ($color=array_shift($request)) {
			case '':
			case null:
				handle_board($method, $input);
				break;
			case 'piece':
				handle_piece($method, $input);
 				break;
			default:
				header("HTTP/1.1 404 Not Found");
				break;
		}
		break;
	case 'status':
		if (sizeof($request)==0)
			handle_status($method);
		else
			header("HTTP/1.1 404 Not Found");
		break;
	case 'players':
		handle_player($method, $request, $input);
		break;
	case 'reset':
		handle_reset($method);
		break;
	default:
		header("HTTP/1.1 404 Not Found");
		exit;
}

function handle_board($method, $input) {
	if ($method == 'GET') show_board();
	elseif ($method == 'POST') {
		reset_board();
		show_board();
	}
	else header('HTTP/1.1 405 Method Not Allowed');
}

function handle_piece($method, $input) {
	if ($method == "GET") show_piece($input);
	elseif ($method == "PUT") handle_piece_impl($input);
}

function handle_player($method, $request, $input) {
	switch ($color = array_shift($request)) {
		case '':
		case null:
			if ($method == 'GET') show_users($method);
			else {
				header("HTTP/1.1 400 Bad Request"); 
				print json_encode(['errormesg'=>"Method $method not allowed here."]);
			}
			break;
		case 'b': 
		case 'w': handle_user($method, $color, $input);
			break;
		default:
			header("HTTP/1.1 404 Not Found");
			print json_encode(['errormesg'=>"Player $color not found."]);
			break;
	}
}

function handle_status($method) {
	if ($method == 'GET') show_status();
	else header('HTTP/1.1 405 Method Not Allowed');
}

function handle_reset($method) {
	if ($method == 'POST') {
		reset_board();
		reset_game();
	}
	else header('HTTP/1.1 405 Method Not Allowed');
}

?>