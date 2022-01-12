<?php

function bad_request($msg) {
	header("HTTP/1.1 400 Bad Request");
	print(json_encode(["error_msg"=>$msg]));
	exit;
}

?>