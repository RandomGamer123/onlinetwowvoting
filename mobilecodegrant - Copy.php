<?php
if (isset($_POST["accesstoken"]) and isset($_POST["user"])) {
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
	$sql = "SELECT mobileaccesstokenexpire, mobilesingleaccesstoken, last_known_username FROM user_verify_table WHERE discord_id = ?";
	$stmt = $conn->prepare($sql); 
	$stmt->execute([$_POST["user"]]);
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if (is_null($row["mobilesingleaccesstoken"]) or is_null($row["mobileaccesstokenexpire"])) {
		} else {
			if (time() > $row["mobileaccesstokenexpire"]) {
			} else {
				if (hash_equals($row["mobilesingleaccesstoken"],$_POST["accesstoken"])) {
					$expire = time()+86000*30;
					$code = bin2hex(random_bytes(64));
					$hash = hash("sha256",$code.(string)$_POST["user"].(string)$expire);
					$sql = "UPDATE user_verify_table SET mobilesingleaccesstoken = ?, mobileaccesstokenexpire = ?, mobilelongtermtoken = ?, mobiletokenexpire = ? WHERE discord_id = ?";
					$conn->prepare($sql)->execute([NULL,NULL,$code,$expire,$_POST["user"]]);
					echo(json_encode(["success",$_POST["user"],$row["last_known_username"],$expire,$hash]));
					die(181);
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