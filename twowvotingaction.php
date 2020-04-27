<?php
session_start();
if (isset($_SESSION["user"])) {
	$ip = $_SERVER["REMOTE_ADDR"];
	if(filter_var($ip, FILTER_VALIDATE_IP)) {
		// ip is valid
	} else {
		echo "Error: IP Address failed validation check. Please reload.";
		die(112);
	}
	if (isset($_SESSION["minitwow"])) {
		$logf = fopen("./private/log.log","a");
		$log = $ip." ".time()." Logged in as ".$_SESSION["username"]." (".$_SESSION["user"].") twowvotingaction.php - Access \n";
		fwrite($logf,$log);
		$minitwowname = $_SESSION["minitwow"];
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
			ob_end_flush();
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
			if (($_GET["mode"] == "signup") or (!isset($_GET["mode"]) and !isset($_POST["mode"]))) {
				$contestantsdata = json_decode($data["contestantsdata"],TRUE);
				$iscontestant = false;
				foreach ($contestantsdata["contestants"] as $value) {
					if ($_SESSION["user"] == $value[0]) {
						$iscontestant = true;
						break;
					}
				}
				if ($iscontestant) {
					echo("You have already signed up to this minitwow.");
					die(123);
				} else {
					array_push($contestantsdata["contestants"],[$_SESSION["user"],1]);
					$spreadsheetId = $data["google_sheets_id"];
					$range = "Signup!A1:C1";
					$valueRange = new Google_Service_Sheets_ValueRange();
					$valueRange->setValues(["values" => [$_SESSION["user"],$_SESSION["username"],time()]]);
					$conf = ["valueInputOption" => "RAW"];
					$service->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf);
					$update = "UPDATE minitwowinfo SET contestantsdata = ? WHERE uniquename = ?";
					$conn->prepare($update)->execute([json_encode($contestantsdata),$minitwowname]);
					echo ("Signed up to: ".htmlspecialchars($minitwowname));
				}
			} else {
				echo ("Mode conflict.");
				die(125);
			}
		} elseif ($data["mode"] == "vote") {
			if ($_POST["mode"] == "voting") {
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
					if (preg_match("/(((MEGA)\ [!-~]+)|([A-Z]{5}\ [A-Z]+)|([A-Z]+))/",$tvote)) {
						if (strpos($tvote, " ")) {
							$tvote = (explode(" ",$tvote,2))[1];
						}
						if(strlen(count_chars($tvote,3)) == strlen($tvote)) { //good vote
						} else {
							if ($votersp == "") {
								$votersp = "Something in your vote may be wrong, here is a list of votes that may have issues, these votes were still sent:<br>";
							}
							$votersp = $votersp.htmlspecialchars($votes[$i])." -Failed unique character check, check for duplicate characters <br>";
						}
					} else {
						if ($votersp == "") {
							$votersp = "Something in your vote may be wrong, here is a list of votes that may have issues, these votes were still sent:<br>";
						}
						$votersp = $votersp.htmlspecialchars($votes[$i])." -Failed regex syntax check <br>";
					}
					$valueRange = new Google_Service_Sheets_ValueRange();
					$valueRange->setValues(["values" => [$_SESSION["user"],$_SESSION["username"],time(),$votes[$i]]]);
					$service->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf);
					array_push($contestantsdata["votes"],[$_SESSION["user"],$votes[$i]]);
				}
				$update = "UPDATE minitwowinfo SET contestantsdata = ? WHERE uniquename = ?";
				$conn->prepare($update)->execute([json_encode($contestantsdata),$minitwowname]);
				if ($votersp == "") {
					$votersp = "All your votes have been checked using an automatic script, finding no issues. Note that this script does not check for missing characters, and therefore cannot guarantee 100% that your vote is valid.";
				}
				echo ("Votes: ".htmlspecialchars($votestr)." sent to ".htmlspecialchars($minitwowname)."<br>".$votersp);
			} else {
				echo ("Mode conflict.");
				die(125);
			}
		} elseif (($data["mode"] == "respond") or ($data["mode"] == "respond-d") or ($data["mode"] == "signup-r")) {
			$contestantsdata = json_decode($data["contestantsdata"],TRUE);
			$lclname = $_SESSION["username"];
			if (isset($_POST["response"])) {
				$response = $_POST["response"];
			} else {
				echo ("Response not received, please retry and access this via the main page. If this persists, please contact me.");
				die(129);
			}
			$iscontestant = false;
			foreach ($contestantsdata["contestants"] as $value) {
				if ($_SESSION["user"] == $value[0]) {
					$iscontestant = true;
					break;
				}
			}
			if (($data["mode"] == "respond") and ($_POST["mode"] == "respond")) {
				if ($iscontestant) {
				} else {
					echo ("You are not a contestant in this minitwow.");
					die(126);
				}
			} elseif (($data["mode"] == "respond-d") and ($_POST["mode"] == "respond-d")) {
				if ($iscontestant) {
				} else {
					$lclname = "DUMMY: ".$lclname;
				}
			} elseif (($data["mode"] == "signup-r") and ($_POST["mode"] == "signup-r")) {
				if ($iscontestant) {
					echo ("You have already signed up to this minitwow. Your response will still be sent.");
					die(128);
				} else {
					array_push($contestantsdata["contestants"],[$_SESSION["user"],1]);
					$spreadsheetId = $data["google_sheets_id"];
					$range = "Signup!A1:C1";
					$valueRange = new Google_Service_Sheets_ValueRange();
					$valueRange->setValues(["values" => [$_SESSION["user"],$_SESSION["username"],time()]]);
					$conf = ["valueInputOption" => "RAW"];
					$service->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf);
					$update = "UPDATE minitwowinfo SET contestantsdata = ? WHERE uniquename = ?";
					$conn->prepare($update)->execute([json_encode($contestantsdata),$minitwowname]);
					echo ("Signed up to: ".htmlspecialchars($minitwowname));
				}
			} else {
				echo("Mode related error or mode conflict. Please contact me if you accessed this through the main page.");
				die(127);
			}
			$ucount = count($contestantsdata["responses"])+1;
			array_push($contestantsdata["responses"],[$_SESSION["user"],$response,(string)$ucount]);
			$spreadsheetId = $data["google_sheets_id"];
			$range = "Responses!A1:C1";
			$valueRange = new Google_Service_Sheets_ValueRange();
			$valueRange->setValues(["values" => [$_SESSION["user"],$lclname,time(),$response,(string)$ucount]]);
			$conf = ["valueInputOption" => "RAW"];
			$service->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf);
			$update = "UPDATE minitwowinfo SET contestantsdata = ? WHERE uniquename = ?";
			$conn->prepare($update)->execute([json_encode($contestantsdata),$minitwowname]);
			echo ("Your response: ".htmlspecialchars($response).", has been sent to: ".htmlspecialchars($minitwowname)."\n Please note the policy for dealing with repetitive submissions in each minitwow is different, your latest response may count as an edit, or a DRP, please DM the host with more info if there may be any ambiguity.");
		} else {
			echo("Mode not recongized. Terminating. Please access this from the main page. If you did so, please contact me.");
			die(124);
		}
	} else { 
		$log = $ip." ".time()." Logged in as ".$_SESSION["username"]." (".$_SESSION["user"].") twowvotingaction.php - No minitwow chosen. \n";
		fwrite($logf,$log);?>
	<html>
	<body>
	<p> <a href = "https://random314.000webhostapp.com/twowvotingmain.php">You must choose a minitwow first. Click to be redirected.</a> </p>
	</body>
	</html>
	<?php }
} else { ?>
<html>
<body>
<p> <a href = "https://random314.000webhostapp.com/twowvoting.html">You must log in with your Discord account first. Click to be redirected.</a> </p>
</body>
</html>
<?php }
?>
