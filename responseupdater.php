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
		$log = $ip." ".time()." Logged in as ".$_SESSION["username"]." (".$_SESSION["user"].") responseupdater.php - Access \n";
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
		$sql = "SELECT mode, contestantsdata, google_sheets_id, host_id FROM minitwowinfo WHERE uniquename = ?";
		$stmt = $conn->prepare($sql);
		$stmt->execute([$minitwowname]);
		$data = [];
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$data = $row;
		}
		if (in_array($_SESSION["user"],(json_decode($data["host_id"],TRUE))["admins"])) {
			if(((isset($_SESSION["ot_salt1"]))and(isset($_SESSION["hashverify1"]))and(isset($_SESSION["sh1_expiry"]))and(isset($_POST["rspupdate"])))) {
				if($_SESSION["sh1_expiry"] > time()) {
					if(hash_equals((hash("sha256",$_SESSION["user"].$minitwowname.($_SESSION["ot_salt1"]))),$_SESSION["hashverify1"])) {
						unset($_SESSION["ot_salt1"]);
						unset($_SESSION["hashverify1"]);
						unset($_SESSION["sh1_expiry"]);
						if($_POST["rspupdate"] == "confirm") {
							$contestantsdata = json_decode($data["contestantsdata"],TRUE);
							//do stuff
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
							$spreadsheetId = $data["google_sheets_id"];
							$range = "Responses!A2:D";
							$response = $service->spreadsheets_values->get($spreadsheetId, $range);
							$vallist = $response["values"];
							$exrsp = $contestantsdata["responses"];
							for ($i = 0; $i < count($vallist); $i++) {
								$lclobj = [(string)$vallist[$i][0],$vallist[$i][3]];
								if (in_array($lclobj,$exrsp)) {
									continue;
								} else {
									array_push($contestantsdata["responses"],$lclobj);
								}
							}
							$update = "UPDATE minitwowinfo SET contestantsdata = ? WHERE uniquename = ?";
							$conn->prepare($update)->execute([json_encode($contestantsdata),$minitwowname]);
							echo("Responses from Google Sheets merged into database.");
							die(179);
						} else {
						echo("Confirmation credentials not found, please access this through the admin options page, if you did so, please contact Random.");
						die(175);
						}
					} else {
						echo("Credentials failed verification, please access this through the admin options page, if you did so, please contact Random.");
						die(173);
					}
				} else {
					unset($_SESSION["ot_salt1"]);
					unset($_SESSION["hashverify1"]);
					unset($_SESSION["sh1_expiry"]);
					echo("Credentials expired, please go back to the admin options page and resend the request.");
					die(174);
				}
			} else {
				echo("Needed credentials not detected, please access this through the admin options page, if you did so, please contact Random.");
				die(172);
			}
		} else {
			echo("You are not an admin, access denied.");
			die(171);
		}
	} else { 
		$log = $ip." ".time()." Logged in as ".$_SESSION["username"]." (".$_SESSION["user"].") responseupdater.php - No minitwow chosen. \n";
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
