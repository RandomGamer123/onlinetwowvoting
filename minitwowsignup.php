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
		$log = $ip." ".time()." Logged in as ".$_SESSION["username"]." (".$_SESSION["user"].") minitwowsignup.php - Access \n";
		fwrite($logf,$log);
		ob_start();
		if (isset($_GET["minitwow"])) {
			$minitwowname = $_GET["minitwow"];
			$_SESSION["minitwow"] = $minitwowname;
		} else {
			$minitwowname = $_SESSION["minitwow"];
		}
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
		?>
		<head>
			<title>TWOW Voting Online: -uniquename Signups</title>
			<link rel="stylesheet" href="styles.css">
		</head>
		<div class="topnav" id="topbar">
		<span class="topnav">
			<a class="left" href="https://random314.000webhostapp.com/twowvotingmain.php">Home</a>
			<a class="left" href="https://random314.000webhostapp.com/twowvotingminitwow.php">-uniquename</a>
			<b class="horizontalnavr">Logged in as -username (-id)</b>
			<a href="logout.php" class="right"><b>Log Out</b></a>
			<div class="clr"></div>
		</span>
		</div>
		<?php
		$content = ob_get_clean();
		$content = str_replace("-id",htmlspecialchars($_SESSION["user"]),$content);
		$content = str_replace("-username",htmlspecialchars($_SESSION["username"]),$content);
		$content = str_replace("-uniquename",htmlspecialchars($minitwowname),$content);
		echo $content;
		$sql = "SELECT mode, description, contestantsdata, discord_link FROM minitwowinfo WHERE uniquename = ?";
		$stmt = $conn->prepare($sql);
		$stmt->execute([$minitwowname]);
		$data = [];
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$data = $row;
		}
		if ($data["mode"] == "signup") {
			$contestantsdata = json_decode($data["contestantsdata"],TRUE);
			if (in_array($_SESSION["user"],$contestantsdata["contestants"])) {
				echo("You have already signed up to this minitwow.");
				die(123);
			} else {
				echo('<h2>Description of '.htmlspecialchars($minitwowname).':</h2><p>'.htmlspecialchars($data["description"]).'</p>');
				echo('<h4><a href='.htmlspecialchars($data['discord_link']).'>Link to Discord server.</a></h4>');
				echo('<h4>Are you sure you want to signup?</h4>');
				echo('<form action="http://random314.000webhostapp.com/twowvotingaction.php"><input type="submit" value="Signup!"</input></form>');
			}
		} elseif ($data["mode"] == "signup-r") {
			echo "You signup to this minitwow by responding to the prompt directly. Please navigate to the appropriate page.";
			die(122);
		} else {
			echo "This minitwow is not open for signups.";
			die(121);
		}
	} else { 
		$log = $ip." ".time()." Logged in as ".$_SESSION["username"]." (".$_SESSION["user"].") minitwowsignup.php - No minitwow chosen. \n";
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