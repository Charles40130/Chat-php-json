<?php
error_reporting(0);
session_start();

if(! isset($_SESSION["id"])){
	$_SESSION["id"] = "Charles";
}

function send_chat($nick, $chat){
	$filename = "chat.json";
	if (!file_exists($filename)) {
		$decode = array();
	} else {
		$fopen = fopen($filename, "r");
		if (flock($fopen, LOCK_SH)) {
			$fgets = fgets($fopen);
			flock($fopen, LOCK_UN);
		}
		fclose($fopen);
		$decode = json_decode($fgets, true);
	}

	if (!is_array($decode)) {
		$decode = array();
	}

	end($decode);
	if (key($decode) >= 10) {
		array_shift($decode);
		$new_key = 10;
	} else {
		$new_key = key($decode);
		$new_key++;
	}

	$chat = htmlspecialchars($chat, ENT_QUOTES, 'UTF-8');
	$format = array($nick, $chat);
	$decode[$new_key] = $format;
	$encode = json_encode($decode);

	$fopen_w = fopen($filename, "w");
	if (flock($fopen_w, LOCK_EX)) {
		fwrite($fopen_w, $encode);
		flock($fopen_w, LOCK_UN);
	}
	fclose($fopen_w);
}

function show_chat($last_id = -1){
	$filename = "chat.json";
	if (!file_exists($filename)) {			// Si le fichier n'existe pas //
		return json_encode(array('status' => 'no data'));
	}

	$fopen = fopen($filename, "r");
	if (flock($fopen, LOCK_SH)) {
		$fgets = fgets($fopen);
		flock($fopen, LOCK_UN);
	}
	fclose($fopen);
	$decode = json_decode($fgets, true);

	$filtered_data = array();
	foreach ($decode as $key => $value) {
		if ($key > $last_id) {
			$filtered_data[$key] = $value;
		}
	}

	return json_encode($filtered_data);
}

if(isset($_POST["chat"]) && $_POST["chat"] != ""){
	$nick = $_SESSION["id"];
	$chat = $_POST["chat"];
	send_chat($nick, $chat);
}

if(isset($_GET["chat"])){
	$last_id = isset($_GET["last_id"]) ? intval($_GET["last_id"]) : -1;
	echo show_chat($last_id);
	exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
	<title>MY CHAT</title>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.4/css/bootstrap.min.css" rel="stylesheet" type="text/css">
	<style>
		.msg { list-style-type: none; }
		.msg .nick { text-shadow: 1px 2px 3px red; }
	</style>
</head>
<body>
	<div style="margin-top: 5px" class="container">
		<div class="row">
			<div class="col-md-12" id="chat"></div>
			<div class="col-md-12">
				<form id="input-chat" action="" method="post">
					<div class="form-group">
						<label>Chat</label>
						<textarea class="form-control" name="chat"></textarea><br>
						<input class="btn btn-sm btn-primary" value="Envoyer" type="submit"/>
						<a class="btn btn-sm btn-warning" href="">Refresh</a>
					</div>
				</form>
			</div>
		</div>
		<script>
		let lastId = -1;

		async function fetchChat() {
			const response = await fetch(`?chat=1&last_id=${lastId}`);
			const data = await response.json();

			if (data.status !== 'no data') {
				const chatDiv = document.getElementById('chat');
				Object.keys(data).forEach(key => {
					const post = data[key];
					const row = document.createElement('div');
					row.innerHTML = `<b style="color:#${post[0]}">${post[0]}</b>: ${post[1]}`;
					chatDiv.appendChild(row);
					lastId = key;
				});
			}
		}

		document.getElementById('input-chat').addEventListener('submit', async function(e) {
			e.preventDefault();
			const formData = new FormData(this);
			await fetch(this.action, {
				method: 'POST',
				body: formData
			});
			this.reset();
			await fetchChat();
		});

		setInterval(fetchChat, 2000);
		</script>
	</div>
</body>
</html>
