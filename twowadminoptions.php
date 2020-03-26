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
		$log = $ip." ".time()." Logged in as ".$_SESSION["username"]." (".$_SESSION["user"].") twowadminoptions.php - Access \n";
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
			<title>TWOW Voting Online: -uniquename Voting</title>
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
		$sql = "SELECT mode, description, host_id, contestantsdata, discord_link FROM minitwowinfo WHERE uniquename = ?";
		$stmt = $conn->prepare($sql);
		$stmt->execute([$minitwowname]);
		$data = [];
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$data = $row;
		}
		if (in_array($_SESSION["user"],(json_decode($data["host_id"],TRUE))["admins"])) {
			$contestantsdata = json_decode($data["contestantsdata"],TRUE);
			$onetimesalt = (string)(random_int(1000000000000000,9999999999999999));
			$_SESSION["ot_salt1"] = $onetimesalt;
			$_SESSION["sh1_expiry"] = time() + 300;
			$_SESSION["hashverify1"] = hash("sha256",$_SESSION["user"].$minitwowname.$onetimesalt);
			//do stuff ?>
			<html>
			<body>
			<h3>Admin Options:</h3><br>
			<form action="/responseupdater.php" method="post">
			<input type="hidden" id="rspupdate" name="rspupdate" value="confirm">
			<input type="submit" value="Update responses in database from connected Google Sheets.">
			</form>
			</body>
			</html>
			<?php
		} else {
			$log = $ip." ".time()." Logged in as ".$_SESSION["username"]." (".$_SESSION["user"].") twowadminoptions.php - Not an admin. \n";
			fwrite($logf,$log);
			echo("<p><a href = \"https://random314.000webhostapp.com/minitwowvoting.php?minitwow=".htmlspecialchars($minitwowname)."\">You are not an admin of this minitwow. Click to be redirected.</a></p>");
			}
	} else { 
		$log = $ip." ".time()." Logged in as ".$_SESSION["username"]." (".$_SESSION["user"].") twowadminoptions.php - No minitwow chosen. \n";
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