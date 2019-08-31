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
	if (isset($_GET["minitwow"]) or isset($_SESSION["minitwow"])) {
		$logf = fopen("./private/log.log","a");
		$log = $ip." ".time()." Logged in as ".$_SESSION["username"]." (".$_SESSION["user"].") twowvotingminitwow.php - Access \n";
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
			<title>TWOW Voting Online: -uniquename</title>
			<link rel="stylesheet" href="styles.css">
		</head>
		<div class="topnav" id="topbar">
		<span class="topnav">
			<a class="left" href="https://random314.000webhostapp.com/twowvotingmain.php">Home</a>
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
		$sql = "SELECT mode, host_id, deadline, description, contestantsdata, discord_link FROM minitwowinfo WHERE uniquename = ?";
		$stmt = $conn->prepare($sql);
		$stmt->execute([$minitwowname]);
		$data = [];
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$data = $row;
		}
		if (in_array($data["mode"],["hiatus","cancelled","ended"])) {
			$htmlnotice = '<div style="background-color:-noticecolour"><h1>This minitwow -status.</h1></div>';
			if ($data["mode"] == "hiatus") {
				$htmlnotice = str_replace("-status",htmlspecialchars("is on hiatus"),$htmlnotice);
				$htmlnotice = str_replace("-noticecolour","#FFFF66",$htmlnotice);
			} elseif ($data["mode"] == "cancelled") {
				$htmlnotice = str_replace("-status",htmlspecialchars("is cancelled"),$htmlnotice);
				$htmlnotice = str_replace("-noticecolour","#DC143C",$htmlnotice);
			} elseif ($data["mode"] == "ended") {
				$htmlnotice = str_replace("-status",htmlspecialchars("has ended"),$htmlnotice);
				$htmlnotice = str_replace("-noticecolour","#228B22",$htmlnotice);
			}
			echo $htmlnotice;
		}
		$welcome = '<div><h1>Welcome to -minitwow!</h1><h3>You are -contestant in this mini-twow. <br> Current <a href="https://random314.000webhostapp.com/documentation.html">mode</a> is: -mode</h3></div>';
		$welcome = str_replace("-minitwow",htmlspecialchars($minitwowname),$welcome);
		$welcome = str_replace("-mode",htmlspecialchars($data["mode"]),$welcome);
		$contestantsdata = json_decode($data["contestantsdata"],TRUE);
		if (in_array($_SESSION["user"],$contestantsdata["contestants"])) {
			$welcome = str_replace("-contestant","a contestant",$welcome);
		} else {
			$welcome = str_replace("-contestant","a spectator or eliminated contestant",$welcome);
		}
		echo $welcome;
		ob_start();?>
		<table class="functiontable">
			<tr><th class="functions" colspan=3>Functions:</th></tr>
			<tr><td align="center" class="functd" style="opacity:0.5"><a href="-signup" class="func">Signup</a></td><td align="center" class="functd" style="opacity:0.5"><a href="-respond" class="func">Respond</a></td><td align="center" class="functd" style="opacity:0.5"><a href="-vote" class="func">Vote</a></td></tr>
		</table>
		<?php
		$content = ob_get_clean();
		if ($data["mode"] == "signup") {
			$content = str_replace('style="opacity:0.5"><a href="-signup"','style="background-color:#add8e6"><a href=https://random314.000webhostapp.com/minitwowsignup.php',$content);
			$content = str_replace(' href="-respond"',' style="opacity:0.5"',$content);
			$content = str_replace(' href="-vote"',' style="opacity:0.5"',$content);
		} elseif ($data["mode"] == "vote") {
			$content = str_replace('style="opacity:0.5"><a href="-vote"','style="background-color:#add8e6"><a href=https://random314.000webhostapp.com/minitwowvoting.php',$content);
			$content = str_replace(' href="-respond"',' style="opacity:0.5"',$content);
			$content = str_replace(' href="-signup"',' style="opacity:0.5"',$content);
		} elseif (in_array($data["mode"],["respond","respond-d","signup-r"])) {
			$content = str_replace('style="opacity:0.5"><a href="-respond"','style="background-color:#add8e6"><a href=https://random314.000webhostapp.com/minitwowrespond.php',$content);
			$content = str_replace(' href="-vote"',' style="opacity:0.5"',$content);
			$content = str_replace(' href="-signup"',' style="opacity:0.5"',$content);
			$_SESSION["rspmode"] = $data["mode"];
		}
		echo $content;
	} else { 
		$log = $ip." ".time()." Logged in as ".$_SESSION["username"]." (".$_SESSION["user"].") twowvotingminitwow.php - No minitwow chosen. \n";
		fwrite($logf,$log);?>
	<html>
	<body>
	<p> <a href = "https://random314.000webhostapp.com/twowvotingmain.php">You must choose a minitwow first. Click to be redirected.</a> </p>
	</body>
	</html>
	<?php }
} else {
	$log = $ip." ".time()." twowvotingminitwow.php - Access \n";
	fwrite($logf,$log);?>
<html>
<body>
<p> <a href = "https://random314.000webhostapp.com/twowvoting.html">You must log in with your Discord account first. Click to be redirected.</a> </p>
</body>
</html>
<?php }
?>