<?php
session_start();
?>
<html>
<head>
	<title>Get Mobile Verification Code</title>
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
	$log = $ip." ".time()." Logged in as ".$_SESSION["username"]." (".$_SESSION["user"].") getmobilecode.php - Access \n";
	fwrite($logf,$log);
	?>
	<div class="topnav" id="topbar">
	<span class="topnav">
		<a class="left" href="https://random314.000webhostapp.com/twowvotingmain.php">Home</a>
		<a class="activel" href="https://random314.000webhostapp.com/getmobilecode.php">Get Mobile Verification Code</a>
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
	$code = bin2hex(random_bytes(6));
	$sql = "UPDATE user_verify_table SET mobilesingleaccesstoken = ?, mobileaccesstokenexpire = ? WHERE discord_id = ?";
	$conn->prepare($sql)->execute([$code,time()+300,$_SESSION["user"]]);
	echo("<h3>Your single-use code is: <code>".$code."</code></h3><h5>It will expire within 5 minutes.");
} else { ?>
<html>
<body>
<p> <a href = "https://random314.000webhostapp.com/twowvoting.html">You must log in with your Discord account first. Click to be redirected.</a> </p>
</body>
</html>
<?php }
?>