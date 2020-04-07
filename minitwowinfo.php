<?php
if (isset($_POST["minitwow"])) {
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
	$sql = "SELECT description, discord_link, host_id, contestantsdata FROM minitwowinfo WHERE uniquename = ?";
	$stmt = $conn->prepare($sql);
	$stmt->execute([$_POST["minitwow"]]);
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$alive = "false";
		$data = json_decode($row["contestantsdata"],True);
		if (in_array($_POST["user"],$data["contestants"])) {
			$alive = "true";
		}
		echo (json_encode([$row["description"],$row["discord_link"],$row["host_id"],$alive,$data["votingscreens"],sizeof($data["votingscreens"]),$data["screenmode"]]));
	}
}
?>
