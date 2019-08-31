<?php
if (isset($_POST["user"])) {
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
	$sql = "SELECT uniquename, mode, deadline, contestantsdata FROM minitwowinfo";
	$stmt = $conn->prepare($sql);
	$stmt->execute([]);
	$array = [];
	$rawlist = [];
	$rawmode = [];
	$inactiveraw = [];
	$inactivelist = [];
	$inactivemode = [];
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$data = json_decode($row["contestantsdata"],true);
		$output = "";
		if (in_array($_POST["user"],$data["contestants"])) {
			$output = "[ALIVE]".$output;
		}
		$inactive = ["hiatus","cancelled","ended"];
		if (in_array($row["mode"],$inactive)) {
			$output = $output."[INACTIVE]";
		}
		$output = $output.$row["uniquename"]." Mode: ".$row["mode"];
		if (is_null($row["deadline"])) {
		} else {
			$output = $output." Deadline: ".$row["deadline"];
		}
		if (in_array($_POST["user"],$data["contestants"])) {
			array_unshift($array,$output);
			array_unshift($rawlist,$row["uniquename"]);
			array_unshift($rawmode,$row["mode"]);
		} elseif (in_array($row["mode"],$inactive)) {
			array_push($inactivelist,$output);
			array_push($inactiveraw,$row["uniquename"]);
			array_push($inactivemode,$row["mode"]);
		} else {
			array_push($array,$output);
			array_push($rawlist,$row["uniquename"]);
			array_push($rawmode,$row["mode"]);
		}
	}
	echo (json_encode([array_merge($rawlist,$inactiveraw),array_merge($array,$inactivelist),array_merge($rawmode,$inactivemode)]));
}
?>