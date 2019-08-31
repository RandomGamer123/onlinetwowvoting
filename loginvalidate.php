<?php
session_start();
	$testingmode = True;
	if (isset($_POST['terms'])) {
		if ($_POST['terms'] == "agree") {
			$logf = fopen("./private/log.log","a");
			$configs = include("./private/config.php");
			$discclientid = $configs["discclientid"];
			$discclientsecret = $configs["discclientsecret"];
			$servername = $configs["servername"];
			$username = $configs["username"];
			$password = $configs["password"];
			$database = $configs["database"];
			$oauthredirect = $configs["oauthredirect"];
			$apiurl = $configs["apiurl"];
			try {
				$conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
				// set the PDO error mode to exception
				$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				} catch(PDOException $e) {    
				echo "Database connection failed. Redirecting.";
				if ($testingmode == True) {
					echo $e->getMessage();
				} else {
					sleep(5);
					echo '<meta http-equiv="refresh" content="0; URL=https://random314.000webhostapp.com/twowvoting.html">';
				}
				}
			//Connection succeeded
			$ip = $_SERVER["REMOTE_ADDR"];
			if(filter_var($ip, FILTER_VALIDATE_IP)) {
			  // ip is valid
			} else {
			  echo "Error: IP Address failed validation check. Redirecting.";
			  sleep(5);
			  echo '<meta http-equiv="refresh" content="0; URL=https://random314.000webhostapp.com/twowvoting.html">';
			}
			$log = $ip." ".time()." "."loginvalidate.php - Access \n";
			fwrite($logf,$log);
			//Now check if user is already logged in
			if(isset($_SESSION["user"])) {
				echo '<meta http-equiv="refresh" content="0; URL=https://random314.000webhostapp.com/twowvotingmain.php">';
			} elseif(isset($_COOKIE["loginuser"])) {
				$stmt = $conn->prepare("SELECT last_known_username, discord_id, cookiedata FROM user_verify_table WHERE last_ip = ?"); 
				$stmt->execute([$ip]);
				while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
					if (hash_equals($_COOKIE["loginuser"],$row["cookiedata"])) {
						$_SESSION["user"] = $row["discord_id"];
						$_SESSION["username"] = $row["last_known_username"];
						echo '<meta http-equiv="refresh" content="0; URL=https://random314.000webhostapp.com/twowvotingmain.php">';
					}
				}
			} else {
				$state = md5((string)(uniqid('', true))).(string)($_POST["mode"]);
				$_SESSION["state"] = $state;
				$url = $oauthredirect."&state=".$state;
				echo '<meta http-equiv="refresh" content="0; URL='.$url.'">';
			}
		} else {
			echo "You must agree to the Terms and Conditions and the Privacy Policy.";
			sleep(5);
			echo '<meta http-equiv="refresh" content="0; URL=https://random314.000webhostapp.com/twowvoting.html">';
		}
	} else {
		echo "You must agree to the Terms and Conditions and the Privacy Policy.";
		sleep(5);
		echo '<meta http-equiv="refresh" content="0; URL=https://random314.000webhostapp.com/twowvoting.html">';
	}
?>