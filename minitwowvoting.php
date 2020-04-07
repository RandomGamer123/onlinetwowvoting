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
		$log = $ip." ".time()." Logged in as ".$_SESSION["username"]." (".$_SESSION["user"].") minitwowvoting.php - Access \n";
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
		$sql = "SELECT mode, description, contestantsdata, discord_link FROM minitwowinfo WHERE uniquename = ?";
		$stmt = $conn->prepare($sql);
		$stmt->execute([$minitwowname]);
		$data = [];
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$data = $row;
		}
		if ($data["mode"] == "vote") {
			$contestantsdata = json_decode($data["contestantsdata"],TRUE);
			echo('<h2>Description of '.htmlspecialchars($minitwowname).':</h2><p>'.htmlspecialchars($data["description"]).'</p>');
			echo('<h4><a href='.htmlspecialchars($data['discord_link']).'>Link to Discord server.</a></h4><br>');
			if ($contestantsdata["screenmode"] == "t") {
				echo('<h2>Voting Screens:</h2>');
				for ($i = 0; $i < count($contestantsdata["votingscreens"]); $i++) {
					echo('<img src='.htmlspecialchars($contestantsdata["votingscreens"][$i]).' alt=votingscreenimage'.(string)$i.'><br>');
				}
			} elseif ($contestantsdata["screenmode"] == "n") {
				echo('<h2>Voting Screens:</h2>');
				if (isset($_POST["screenname"])) {
					//DO STUFF
					$formatted = TRUE;
					$responses = $contestantsdata["responses"];
					$screenarr = str_split($_POST["screenname"]);
					$screenid = 0;
					for ($i = 0; $i < count($screenarr); $i++) {
						$screenid = $screenid + ((ord($screenarr[$i])-65)*(26**$i));
					}
					for ($i = 0; $i < count($responses); $i++) {
						if(count($responses[$i])==3) {
							$responses[$i][2] = psrand($responses[$i][2],$screenid);
						} else {
							echo("Some responses may not be in the new response format which contains the data needed for screen generation, please contact Random.");
							$formatted = FALSE;
							break;
						}
					}
					if ($formatted == TRUE) {
						//DO MORE STUFF
						usort($responses,"cmp");
						if ($_POST["screenname"] !== "MS") {
							$sl = $contestantsdata["screenlength"];
							$rspiter = floor((count($responses)-1)/$sl);
							$rspr = 0;
							if (count($responses) > $sl*26) {
								$rspiter = 25;
								echo("WARNING: There are too many responses for all responses to be included on screens to be generated using alphabetical characters based on the settings given, please contact Random if you want to vote on all of them. (As if this will ever be seen by anyone).");
							}
							echo("<div class=\"screencontain\">");
							if (count($responses)%$sl == 0) {
								$rspr = $sl;
							} else {
								$rspr = count($responses)%$sl;
							}
							for ($i = 0; $i < $rspiter; $i++) {
								echo("<div class=\"screen\">");
								echo("Screen ".htmlspecialchars($_POST["screenname"]).htmlspecialchars(chr($i+65)));
								for ($j = 0; $j < $sl; $j++) {
									echo("<br>");
									echo(htmlspecialchars(chr($j+65).": "));
									echo(htmlspecialchars($responses[$i*$sl+$j][1]));
								}
								echo("</div>");
							}
							echo("<div class=\"screenlast\">");
							echo("Screen ".htmlspecialchars($_POST["screenname"]).htmlspecialchars(chr($rspiter+65)));
							for ($i = 0; $i < $rspr; $i++) {
								echo("<br>");
								echo(htmlspecialchars(chr($i+65).": "));
								echo(htmlspecialchars($responses[$rspiter*$sl+$i][1]));
							}
						} else {
							echo("<div class=\"screencontain\">");
							echo("<div class=\"screenlast\">");
							echo("Screen "."MEGA (Megascreen)");
							$rspr = 0;
							if (count($responses) > 94) {
								$rspr = 94;
								echo("<br> Note: As there are more than 94 responses, the megascreen does not contain all responses. Please contact Random if there are somehow more than 94 responses.");
							} else {
								$rspr = count($responses);
							}
							for ($i = 0; $i < $rspr; $i++) {
								echo("<br>");
								echo(htmlspecialchars(chr($i+33).": "));
								echo(htmlspecialchars($responses[$i][1]));
							}
						}
						echo("</div></div>");
					}
				} else { ?>
					<p> Generate a series of screens by putting a 4 character long alphabetic screen name (Only capital letters). Type "MS" without the quotes to generate the megascreen.
					<form name="screennameform" method="post" action ="">
					<input type="text" id="screenname" name="screenname" pattern="([A-Z]{4})|(MS)" title="Four letter alphabetic screen name" required />
					<input type="Submit" value="Generate Screen!" />
					</form>
				<?php }
			}
			$anyvotes = False;
			echo('<h3> Your votes: </h3> <ul>');
			for ($i = 0; $i < count($contestantsdata["votes"]); $i++) {
				if ($contestantsdata["votes"][$i][0] == $_SESSION["user"]) {
					echo('<li>'.htmlspecialchars($contestantsdata["votes"][$i][1]).'</li>');
					$anyvotes = True;
				}
			}
			if ($anyvotes == False) {
					echo("You have 0 votes.");
			}
			echo('</ul>');
			echo('<form action="http://random314.000webhostapp.com/twowvotingaction.php" method="post">Votes:<input type="text" name="votes"><br><input type="hidden" id="mode" name="mode" value="voting"><br><input type="submit" value="Vote!"></form>');
		} else {
			echo "This minitwow is not open for voting.";
			die(121);
		}
	} else { 
		$log = $ip." ".time()." Logged in as ".$_SESSION["username"]." (".$_SESSION["user"].") minitwowvoting.php - No minitwow chosen. \n";
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
