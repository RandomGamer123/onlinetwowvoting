<?php
function psrand($rspnum,$screennum) {
	$lrseed = (int)$rspnum*(int)$screennum+(13**5);
	$m = 2147483647;
	$a = 16807;
	$fseed = floor((((round(fmod(((float)$lrseed*exp(1)),1)*$m*$a))%$m)/$m)*(10**7))/(10**7);
	return((round($fseed*$m*$a))%$m);
}
function cmp($a,$b) {
	return($a[2]-$b[2]);
}
if (isset($_POST["minitwow"]) and (isset($_POST["screenname"]))) {
	if (preg_match("/[A-Z]{4}/", $_POST["screenname"])) {
		$minitwowname = $_POST["minitwow"];
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
			echo(json_encode(["failure","Database connection failed. Please reload and contact."]));
			ob_end_flush();
			die(111);
			}
		$sql = "SELECT mode, description, contestantsdata, discord_link FROM minitwowinfo WHERE uniquename = ?";
		$stmt = $conn->prepare($sql);
		$stmt->execute([$minitwowname]);
		$data = [];
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$data = $row;
		}
		if ($data["mode"] == "vote") {
			$contestantsdata = json_decode($data["contestantsdata"],TRUE);
			if ($contestantsdata["screenmode"] == "t") {
				$output = "";
				for ($i = 0; $i < count($contestantsdata["votingscreens"]); $i++) {
					$output = $output.$contestantsdata["votingscreens"][$i];
				}
				echo(json_encode(["success","t",0,$output]));
				die(181);
			} elseif ($contestantsdata["screenmode"] == "n") {
				if (isset($_POST["screenname"])) {
					//DO STUFF
					$formatted = TRUE;
					$responses = $contestantsdata["responses"];
					$screenarr = str_split($_POST["screenname"]);
					$screenid = 0;
					for ($i = 0; $i < count($screenarr); $i++) {
						$screenid = $screenid + ((ord($screenarr[$i])-65)*(26**$i));
					}
					$mi = 1;
					for ($i = 0; $i < count($responses); $i++) {
						if(count($responses[$i])==3) {
							$responses[$i][2] = psrand($responses[$i][2],$screenid);
						} else {
							echo(json_encode(["failure","Some responses may not be in the new response format which contains the data needed for screen generation, please contact Random."]));
							die(191);
						}
					}
					if ($formatted == TRUE) {
						//DO MORE STUFF
						usort($responses,"cmp");
						$sl = $contestantsdata["screenlength"];
						$rspiter = floor((count($responses)-1)/$sl);
						$rspr = 0;
						if (count($responses) > $sl*26) {
							$rspiter = 25;
							$mi = 2;
						}
						if (count($responses)%$sl == 0) {
							$rspr = $sl;
						} else {
							$rspr = count($responses)%$sl;
						}
						$output = "";
						for ($i = 0; $i < $rspiter; $i++) {
							$output = $output."Screen ".htmlspecialchars($_POST["screenname"]).htmlspecialchars(chr($i+65));
							for ($j = 0; $j < $sl; $j++) {
								$output = $output."\n";
								$output = $output.chr($j+65).": ";
								$output = $output.($responses[$i*$sl+$j][1]);
							}
							$output = $output."\n\n";
						}
						$output = $output."Screen ".htmlspecialchars($_POST["screenname"]).htmlspecialchars(chr($rspiter+65));
						for ($i = 0; $i < $rspr; $i++) {
							$output = $output."\n";
							$output = $output.(chr($i+65).": ");
							$output = $output.($responses[$rspiter*$sl+$i][1]);
						}
						echo(json_encode(["success","n",$mi,$output]));
						die(182);
					}
				}
			} else {
				echo(json_encode(["failure","The screenmode of the minitwow is not recognised, please contact Random."]));
				die(192);
			}
		} else {
			echo(json_encode(["failure","This minitwow is not open for voting."]));
			die(193);
		}
	} else { 
		echo(json_encode(["failure","Your screen name does not satisfy the regex, your screenname has to be 4 capital English alphabet letters."]));
		die(194);
	}
} else { 
	echo(json_encode(["failure","Important info not included in the request, if you accessed this through the bot, please contact Random."]));
	die(195);
}
?>