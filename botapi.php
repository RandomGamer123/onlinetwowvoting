<?php
if (isset($_POST["user"]) and isset($_POST["token"])) {
	if (isset($_POST["minitwow"])) {
		$minitwowname = $_POST["minitwow"];
		$configs = include("./private/config.php");
		$servername = $configs["servername"];
		$username = $configs["username"];
		$password = $configs["password"];
		$database = $configs["database"];
		try {
			$conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
			// set the PDO error mode to exception
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} catch(PDOException $e) {    
			echo "Database connection failed. Please reload and contact.";
			die(111);
			}
		$sql = "SELECT mode, contestantsdata, google_sheets_id, deadline FROM minitwowinfo WHERE uniquename = ?";
		$stmt = $conn->prepare($sql);
		$stmt->execute([$minitwowname]);
		$data = [];
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$data = $row;
		}
		require_once $configs["googleapifilev2"];
		require_once "./googleapi/google-api-php-client-2.2.3/vendor/google/apiclient-services/src/Google/Service/Sheets.php";
		$client = new \Google_Client();
		if ($_POST["sender"] == 512593919230476298) {
			$sql2 = "SELECT api_master_key FROM user_verify_table WHERE discord_id = ?";
			$stmt2 = $conn->prepare($sql2); 
			$stmt2->execute([$_POST["sender"]]);
			$data2 = [];
			while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
				$data2 = $row2;
			}
			if (hash_equals($data2["api_master_key"],$_POST["token"])) {
				$contestantsdata = json_decode($data["contestantsdata"],TRUE);
				$service = new \Google_Service_Sheets($client);
				$client->setApplicationName('online-twow-voting');
				$client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
				$client->setAccessType('offline');
				$client->setAuthConfig($configs["googlecredentials"]);
				if ($data["mode"] == $_POST["mode"]) {
					if (($data["deadline"] >= time()) or ($_POST["deadlinebypass"] == 1)) {
						if ($data["mode"] == "respond") {
							$response = $_POST["response"];
							array_push($contestantsdata["responses"],[$_POST["user"],$response]);
							$spreadsheetId = $data["google_sheets_id"];
							$range = "Responses!A1:C1";
							$valueRange = new Google_Service_Sheets_ValueRange();
							$valueRange->setValues(["values" => [$_POST["user"],$_POST["username"],time(),$response]]);
							$conf = ["valueInputOption" => "RAW"];
							$service->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf);
							$update = "UPDATE minitwowinfo SET contestantsdata = ? WHERE uniquename = ?";
							$conn->prepare($update)->execute([json_encode($contestantsdata),$minitwowname]);
							echo (json_encode(["success","Your response: ".htmlspecialchars($response).", has been sent to: ".htmlspecialchars($minitwowname)."\n Please note the policy for dealing with repetitive submissions in each minitwow is different, your latest response may count as an edit, or a DRP, please DM the host with more info if there may be any ambiguity."]));
						} elseif ($data["mode"] == "vote") {
							$votestr = $_POST["votes"];
							$votes = explode(",", $votestr);
							for($i = 0; $i < count($votes); $i++) {
								$valueRange = new Google_Service_Sheets_ValueRange();
								$valueRange->setValues(["values" => [$_POST["user"],$_POST["username"],time(),$votes[$i]]]);
								$service->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf);
								array_push($contestantsdata["votes"],[$_POST["user"],$votes[$i]]);
							}
							$update = "UPDATE minitwowinfo SET contestantsdata = ? WHERE uniquename = ?";
							$conn->prepare($update)->execute([json_encode($contestantsdata),$minitwowname]);
							echo (json_encode(["success","Votes: ".htmlspecialchars($votestr)." sent to ".htmlspecialchars($minitwowname)]));
						}
					} else {
						echo(json_encode(["failure","Deadline past."]));
						die(194);
					}
				} else {
					echo(json_encode(["failure","Mode given does not match current mode."]));
					die(195);
				}
			} else {
				echo (json_encode(["failure","Bot backend verification error. Tell Random if this happens."]));
				die(191);
			}
		}
		echo(json_encode(["failure","User does not have API setup."]));
		die(193);
	}
}
?>