<?php
if (isset($_POST["token"]) and isset($_POST["user"])) {
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
		echo(json_encode(["error","database_failure"]));
		die(171);
		}
	$sql = "SELECT mobiletokenexpire, mobilelongtermtoken, last_known_username FROM user_verify_table WHERE discord_id = ?";
	$stmt = $conn->prepare($sql); 
	$stmt->execute([$_POST["user"]]);
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if (is_null($row["mobilelongtermtoken"]) or is_null($row["mobiletokenexpire"])) {
		} else {
			if (time() > $row["mobiletokenexpire"]) {
			} else {
				if (hash_equals(hash("sha256",$row["mobilelongtermtoken"].(string)$_POST["user"].(string)$row["mobiletokenexpire"]),$_POST["token"])) {
					echo(json_encode(["success",$_POST["user"],$row["last_known_username"]]));
					die(180);
				}
			}
		}
	}
	echo(json_encode(["error","authfailure"]));
	die(172);
} else {
	echo(json_encode(["error","data_lack"]));
	die(170);
}
?>