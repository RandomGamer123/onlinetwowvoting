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
		$sql = "SELECT mode, contestantsdata, google_sheets_id FROM minitwowinfo WHERE uniquename = ?";
		$stmt = $conn->prepare($sql);
		$stmt->execute([$minitwowname]);
		$data = [];
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$data = $row;
		}
		require_once $configs["googleapifilev2"];
		require_once "./googleapi/google-api-php-client-2.2.3/vendor/google/apiclient-services/src/Google/Service/Sheets.php";
		$client = new \Google_Client();
		if ($_POST["user"] == 512593919230476298) {
			$sql2 = "SELECT api_master_key FROM user_verify_table WHERE discord_id = ?";
			$stmt2 = $conn->prepare($sql2); 
			$stmt2->execute([$_POST["user"]]);
			$data2 = [];
			while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
				$data2 = $row2;
			}
			if (hash_equals($data2["api_master_key"],$_POST["token"])) {
				$client->setApplicationName('online-twow-voting');
				$client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
				$client->setAccessType('offline');
				$client->setAuthConfig($configs["googlecredentials"]);
				if (($data["mode"] == "signup") and ($_POST["mode"] = "signup")) {
					$contestantsdata = json_decode($data["contestantsdata"],TRUE);
					if (in_array($_POST["targetuser"],$contestantsdata["contestants"])) {
						echo(json_encode(["failure","You have already signed up to this minitwow."]));
						die(123);
					} else {
						$service = new \Google_Service_Sheets($client);
						array_push($contestantsdata["contestants"],$_POST["targetuser"]);
						$spreadsheetId = $data["google_sheets_id"];
						$range = "Signup!A1:C1";
						$valueRange = new Google_Service_Sheets_ValueRange();
						$valueRange->setValues(["values" => [$_POST["targetuser"],$_POST["targetusername"],time()]]);
						$conf = ["valueInputOption" => "RAW"];
						$service->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf);
						$update = "UPDATE minitwowinfo SET contestantsdata = ? WHERE uniquename = ?";
						$conn->prepare($update)->execute([json_encode($contestantsdata),$minitwowname]);
						echo (json_encode(["success","Signed up to: ".htmlspecialchars($minitwowname)]));
						die(50);
					}
				}
			} else {
				echo (json_encode(["failure","Bot backend verification error. Tell Random if this happens."]));
				die(191);
			}
		}
		$sql2 = "SELECT mobiletokenexpire, mobilelongtermtoken, last_known_username FROM user_verify_table WHERE discord_id = ?";
		$stmt2 = $conn->prepare($sql2); 
		$stmt2->execute([$_POST["user"]]);
		while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
			if (is_null($row["mobilelongtermtoken"]) or is_null($row["mobiletokenexpire"])) {
			} else {
				if (time() > $row["mobiletokenexpire"]) {
				} else {
					if (hash_equals(hash("sha256",$row["mobilelongtermtoken"].(string)$_POST["user"].(string)$row["mobiletokenexpire"]),$_POST["token"])) {
					} else {
						echo (json_encode(["failure","Verification error. Try logging out and logging back in again."]));
						die(190);
					}
				}
			}
		}
		$client->setApplicationName('online-twow-voting');
		$client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
		$client->setAccessType('offline');

		/*
		 * The JSON auth file can be provided to the Google Client in two ways, one is as a string which is assumed to be the
		 * path to the json file. This is a nice way to keep the creds out of the environment.
		 *
		 * The second option is as an array. For this example I'll pull the JSON from an environment variable, decode it, and
		 * pass along.
		 */
		$client->setAuthConfig($configs["googlecredentials"]);

		/*
		 * With the Google_Client we can get a Google_Service_Sheets service object to interact with sheets
		 */
		$service = new \Google_Service_Sheets($client);
		if ($data["mode"] == "signup") {
			if ($_POST["mode"]=="signup") {
				$contestantsdata = json_decode($data["contestantsdata"],TRUE);
				if (in_array($_POST["user"],$contestantsdata["contestants"])) {
					echo(json_encode(["failure","You have already signed up to this minitwow."]));
					die(123);
				} else {
					array_push($contestantsdata["contestants"],$_POST["user"]);
					$spreadsheetId = $data["google_sheets_id"];
					$range = "Signup!A1:C1";
					$valueRange = new Google_Service_Sheets_ValueRange();
					$valueRange->setValues(["values" => [$_POST["user"],$_POST["username"],time()]]);
					$conf = ["valueInputOption" => "RAW"];
					$service->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf);
					$update = "UPDATE minitwowinfo SET contestantsdata = ? WHERE uniquename = ?";
					$conn->prepare($update)->execute([json_encode($contestantsdata),$minitwowname]);
					echo (json_encode(["success","Signed up to: ".htmlspecialchars($minitwowname)]));
				}
			}
		} elseif ($data["mode"] == "vote") {
			if ($_POST["mode"] == "vote") {
				$contestantsdata = json_decode($data["contestantsdata"],TRUE);
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
					if (preg_match("/((MEGA)|([A-Z]{5}))\ [A-Z]+/",$tvote)) {
						$tvote = (explode(" ",$tvote,2))[1];
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
					$votersp = "All your votes have been checked using an automatic script, finding no issues. Note that this script does not check for missing characters.";
				}
				echo (json_encode(["success","Votes: ".htmlspecialchars($votestr)." sent to ".htmlspecialchars($minitwowname)."\n".$votersp]));
			}
		} elseif ((($data["mode"] == "respond") or ($data["mode"] == "respond-d") or ($data["mode"] == "signup-r")) and ($_POST["mode"] == "respond" or $_POST["mode"] == "respond-d" or $_POST["mode"] == "signup-r")) {
			$contestantsdata = json_decode($data["contestantsdata"],TRUE);
			$lclname = $_POST["username"];
			if (isset($_POST["response"])) {
				$response = $_POST["response"];
			} else {
				echo (json_encode(["failure","Response not received, please retry and access this via the main page. If this persists, please contact me."]));
				die(129);
			}
			if (($data["mode"] == "respond") and ($_POST["mode"] == "respond")) {
				if (in_array($_POST["user"],$contestantsdata["contestants"])) {
				} else {
					echo (json_encode(["failure","You are not a contestant in this minitwow."]));
					die(126);
				}
			} elseif (($data["mode"] == "respond-d") and ($_POST["mode"] == "respond-d")) {
				if (in_array($_POST["user"],$contestantsdata["contestants"])) {
				} else {
					$lclname = "DUMMY: ".$lclname;
				}
			} elseif (($data["mode"] == "signup-r") and ($_POST["mode"] == "signup-r")) {
				$alr = "";
				if (in_array($_POST["user"],$contestantsdata["contestants"])) {
					$alr = " You have already signed up to this minitwow. Your response will still be sent.";
					die(128);
				} else {
					array_push($contestantsdata["contestants"],$_POST["user"]);
					$spreadsheetId = $data["google_sheets_id"];
					$range = "Signup!A1:C1";
					$valueRange = new Google_Service_Sheets_ValueRange();
					$valueRange->setValues(["values" => [$_POST["user"],$_POST["username"],time()]]);
					$conf = ["valueInputOption" => "RAW"];
					$service->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf);
					$update = "UPDATE minitwowinfo SET contestantsdata = ? WHERE uniquename = ?";
					$conn->prepare($update)->execute([json_encode($contestantsdata),$minitwowname]);
					echo (json_encode(["success","Signed up to: ".htmlspecialchars($minitwowname).$alr]));
				}
			} else {
				echo(json_encode(["failure","Mode related error or mode conflict. Please contact me if you accessed this through the main page."]));
				die(127);
			}
			$ucount = count($contestantsdata["responses"])+1;
			array_push($contestantsdata["responses"],[$_POST["user"],$response,(string)$ucount]);
			$spreadsheetId = $data["google_sheets_id"];
			$range = "Responses!A1:C1";
			$valueRange = new Google_Service_Sheets_ValueRange();
			$valueRange->setValues(["values" => [$_POST["user"],$lclname,time(),$response,(string)$ucount]]);
			$conf = ["valueInputOption" => "RAW"];
			$service->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf);
			$update = "UPDATE minitwowinfo SET contestantsdata = ? WHERE uniquename = ?";
			$conn->prepare($update)->execute([json_encode($contestantsdata),$minitwowname]);
			echo (json_encode(["success","Your response: ".htmlspecialchars($response).", has been sent to: ".htmlspecialchars($minitwowname)."\n Please note the policy for dealing with repetitive submissions in each minitwow is different, your latest response may count as an edit, or a DRP, please DM the host with more info if there may be any ambiguity."]));
		} else {
			echo(json_encode(["failure","Mode not recongized or mode conflict."]));
		}
	}
}
?>
