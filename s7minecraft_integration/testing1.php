<?php
	if (!empty($_POST)) {
		if (isset($_POST['server_token'])) {
			$handle = fopen("../private/token_data.csv",'r');
			$tokens = [];
			while(!feof($handle)) {
				array_push($tokens, fgetcsv($handle)[0]);
			}
			foreach ($tokens as $item) {
				if ($item == $_POST['server_token']) {
					$bodydata = "No body data was sent.";
					if (isset($_POST["body"])) {
						$bodydata = $_POST["body"];
					}
					echo(json_encode(array("body" => ("Authorised successfully. Received body data: " . $bodydata))));
					exit();
				}
			}
			exit();
		} else {
			exit();
		}
	} else {
		exit();
	}
?>