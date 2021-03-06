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
				if (($data["mode"] == $_POST["mode"]) or (($_POST["mode"] == "respond") and (($data["mode"] == "respond") or ($data["mode"] == "respond-d") or ($data["mode"] == "signup-r")))) {
					if (($data["deadline"] >= time()) or ($_POST["deadlinebypass"] == 1)) {
						if (($data["mode"] == "respond") or ($data["mode"] == "respond-d") or ($data["mode"] == "signup-r")) {
							$iscontestant = false;
							foreach ($contestantsdata["contestants"] as $value) {
								if ($_POST["user"] == $value[0]) {
									$iscontestant = true;
									break;
								}
							}
							if ($iscontestant) {
							} else {
								if ($data["mode"] == "signup-r") {
									$spreadsheetId = $data["google_sheets_id"];
									$range = "Signup!A1:C1";
									$valueRange = new Google_Service_Sheets_ValueRange();
									$valueRange->setValues(["values" => [$_POST["user"],$_POST["username"],time()]]);
									$conf = ["valueInputOption" => "RAW"];
									$service->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf);
									array_push($contestantsdata["contestants"],[$_POST["user"],1]);
									$update = "UPDATE minitwowinfo SET contestantsdata = ? WHERE uniquename = ?";
									$conn->prepare($update)->execute([json_encode($contestantsdata),$minitwowname]);
								} else {
									if ($data["mode"] == "respond-d") {
									} else {
										echo (json_encode(["failure","You are not a contestant in this minitwow."]));
										die(126);
									}
								}
							}
							$response = $_POST["response"];
							$ucount = count($contestantsdata["responses"])+1;
							array_push($contestantsdata["responses"],[$_POST["user"],$response,(string)$ucount]);
							$spreadsheetId = $data["google_sheets_id"];
							$range = "Responses!A1:C1";
							$valueRange = new Google_Service_Sheets_ValueRange();
							$valueRange->setValues(["values" => [$_POST["user"],$_POST["username"],time(),$response,(string)$ucount]]);
							$conf = ["valueInputOption" => "RAW"];
							$service->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf);
							$update = "UPDATE minitwowinfo SET contestantsdata = ? WHERE uniquename = ?";
							$conn->prepare($update)->execute([json_encode($contestantsdata),$minitwowname]);
							echo (json_encode(["success","Your response: ".htmlspecialchars($response).", has been sent to: ".htmlspecialchars($minitwowname)."\n Please note the policy for dealing with repetitive submissions in each minitwow is different, your latest response may count as an edit, or a DRP, please DM the host with more info if there may be any ambiguity.",$data["mode"]]));
						} elseif ($data["mode"] == "vote") {
							$spreadsheetId = $data["google_sheets_id"];
							$range = "Voting!A1:D1";
							$conf = ["valueInputOption" => "RAW"];
							$votestr = $_POST["votes"];
							$votersp = "";
							$votes = explode(",", $votestr);
							for($i = 0; $i < count($votes); $i++) {
								$tvote = $votes[$i];
								if ($tvote[0] == "[") {
									$tvote = substr($tvote,1);
								}
								if (substr($tvote,-1) == "]") {
									$tvote = substr($tvote,0,-1);
								}
								if (preg_match("/(((MEGA)\ [!-~]+)|([A-Z]{5}\ [A-Z]+)|([A-Z]+))/",$tvote)) {
									if (strpos($tvote, " ")) {
										$tvote = (explode(" ",$tvote,2))[1];
									}
									if(strlen(count_chars($tvote,3)) == strlen($tvote)) { //good vote
									} else {
										if ($votersp == "") {
											$votersp = "Something in your vote may be wrong, here is a list of votes that may have issues, these votes were still sent:\n";
										}
										$votersp = $votersp.$votes[$i]." -Failed unique character check, check for duplicate characters \n";
									}
								} else {
									if ($votersp == "") {
										$votersp = "Something in your vote may be wrong, here is a list of votes that may have issues, these votes were still sent:\n";
									}
									$votersp = $votersp.$votes[$i]." -Failed regex syntax check \n";
								}
								$valueRange = new Google_Service_Sheets_ValueRange();
								$valueRange->setValues(["values" => [$_POST["user"],$_POST["username"],time(),$votes[$i]]]);
								$service->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf);
								array_push($contestantsdata["votes"],[$_POST["user"],$votes[$i]]);
							}
							$update = "UPDATE minitwowinfo SET contestantsdata = ? WHERE uniquename = ?";
							$conn->prepare($update)->execute([json_encode($contestantsdata),$minitwowname]);
							if ($votersp == "") {
								$votersp = "All your votes have been checked using an automatic script, finding no issues. Note that this script does not check for missing characters, and therefore cannot guarantee 100% that your vote is valid.";
							}
							echo (json_encode(["success","Votes: ".htmlspecialchars($votestr)." sent to ".htmlspecialchars($minitwowname)."\n".$votersp]));
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
		} else {
			echo(json_encode(["failure","User does not have API setup."]));
			die(193);
		}
	}
}
?>
