<?php
session_start();
?>
<html>
<head>
	<title>TWOW Voting Online Main Page</title>
	<link rel="stylesheet" href="styles.css">
</head>
</html>
<?php
if (isset($_SESSION["user"])) {
	ob_start();
	$logf = fopen("./private/log.log","a");
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
	$ip = $_SERVER["REMOTE_ADDR"];
	if(filter_var($ip, FILTER_VALIDATE_IP)) {
		// ip is valid
	} else {
		echo "Error: IP Address failed validation check. Please reload.";
		die(112);
	}
	$log = $ip." ".time()." Logged in as ".$_SESSION["username"]." (".$_SESSION["user"].") twowvotingmain.php - Access \n";
	fwrite($logf,$log);
	?>
	<div class="topnav" id="topbar">
	<span class="topnav">
		<a class="activel" href="https://random314.000webhostapp.com/twowvotingmain.php">Home</a>
		<a class="left" href="https://random314.000webhostapp.com/getmobilecode.php">Get Mobile Verification Code</a>
		<b class="horizontalnavr">Logged in as -username (-id)</b>
		<a href="logout.php" class="right"><b>Log Out</b></a>
		<div class="clr"></div>
	</span>
	</div>
	<?php
	$content = ob_get_clean();
	$content = str_replace("-id",htmlspecialchars($_SESSION["user"]),$content);
	$content = str_replace("-username",htmlspecialchars($_SESSION["username"]),$content);
	echo $content;
	$sql = "SELECT uniquename, mode, host_id, deadline, description, discord_link FROM minitwowinfo";
	$stmt = $conn->prepare($sql);
	$stmt->execute([]);
	$tbl = "<table id='t01'> <tr> <th> Name: </th> <th> Host (Discord ID): </th> <th> Current Status: </th> <th> Deadline: </th> <th> Discord Link: </th> <th> Description: </th> </tr>";
	$order = array("uniquename","host_id","mode","deadline","discord_link","description");
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$tbl .= " <tr>";
		for ($i = 0; $i < count($order); $i++) {
			$tbl .= " <td>";
			if ($order[$i] == "uniquename") {
				$tbl .= '<a href="https://random314.000webhostapp.com/twowvotingminitwow.php?minitwow='.htmlspecialchars($row[($order[$i])]).'">'.htmlspecialchars($row[($order[$i])]).'</a>';
			} elseif ($order[$i] == "discord_link") {
				$tbl .= '<a href="'.htmlspecialchars($row[($order[$i])]).'">'.htmlspecialchars($row[($order[$i])]).'</a>';
			} elseif ($order[$i] == "deadline") {
				if ($row[($order[$i])] === NULL) {
				$tbl .= 'None';
				} else {
				$tbl .= htmlspecialchars(gmdate("Y-m-d H:i:s", (int)$row[($order[$i])]));
				}
			} elseif ($order[$i] == "host_id") {
				$tbl .= htmlspecialchars((json_decode($row[($order[$i])],TRUE))["displayname"]);
			} else {
				$tbl .= htmlspecialchars($row[($order[$i])]);
			}
			$tbl .= "</td>";
		}
		$tbl .= " </tr>";
	}
	$tbl .= " </table>";
	echo $tbl;
} else { ?>
<html>
<body>
<p> <a href = "https://random314.000webhostapp.com/twowvoting.html">You must log in with your Discord account first. Click to be redirected.</a> </p>
</body>
</html>
<?php }
?>
