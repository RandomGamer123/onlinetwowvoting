<?php
$servername = "localhost";
$username = "id10211963_main_random314_site";
$password = "9Ab4cMx2d7DBtxK";
$database = "id10211963_main";
$logf = fopen("./private/log.log","a");
$conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
// set the PDO error mode to exception
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
echo "Connected successfully"; 
echo "This function is depreciated, please use oauth2 instead.";
$log = $_SERVER["REMOTE_ADDR"]." ".time()." "."verify.php - Access";
fwrite($logf,$log);
if($_POST["id"]) {
	$id = (int)$_POST["id"];
	if (is_numeric($id)) {
		$sql = "SELECT * FROM user_verify_table WHERE discord_id = ".$id.";";
		$result = mysqli_query($conn,$sql);
		if (mysqli_num_rows($result) > 0) {
			if (mysqli_num_rows($result) == 1) {
				$data = mysqli_fetch_assoc($result);
				if (time() > $data["code_expire"]) {
					echo "Code expired";
				} else {
					if ((int)$_POST["code"] == $data["verify_code"]) {
						if((bool)$_POST["keeplogin"] == True) {
							$cookiecode = (string)bin2hex(random_bytes(50));
							$cookie = (string)$id."-login-".(string)time()."-".$cookiecode;
							$sqlcookie = "UPDATE user_verify_table SET cookiedata = ".$cookie." WHERE discord_id = ".(string)$id.";";
							setcookie("loginuser",$cookie,time()+86400*30,"/");
							if (!isset($_COOKIE["loginuser"])) {
								echo "Cookies must be enabled to keep you verified";
							}
						}
						$log = $_SERVER["REMOTE_ADDR"]." ".time()." "."verify.php -Success";
						fwrite($logf,$log);
						$otc = hash("sha256",(string)bin2hex(random_bytes(50)).(string)$id.(string)time());
						$onetimecode = "UPDATE user_verify_table SET onetimecode = ".$otc." WHERE discord_id = ".(string)$id.";";
						$data = array("otc" => $otc,"id" => $id);
						$options = array(
							"http" => array(
								"header" => "Content-type: application/x-www-form-urlencoded\r\n",
								"method" => "POST",
								"content" => http_build_query($data)
							)
						);
						echo "Success";
						$context  = stream_context_create($options);
					} else {
						echo "Invalid code";
						sleep(2);
						echo '<meta http-equiv="refresh" content="0; URL=https://random314.000webhostapp.com/twowvoting.html">';
					}
				}
			} else {
				echo "ERROR: Duplicate datums found.";
				sleep(2);
				echo '<meta http-equiv="refresh" content="0; URL=https://random314.000webhostapp.com/twowvoting.html">';
				exit(4);
			}
		} else {
			echo "ERROR: Fail to locate user.";
			sleep(2);
			echo '<meta http-equiv="refresh" content="0; URL=https://random314.000webhostapp.com/twowvoting.html">';
			exit(3);
		}
	} else {
		echo "Either you tried SQL Injection, or an error happened.";
		sleep(2);
		echo '<meta http-equiv="refresh" content="0; URL=https://random314.000webhostapp.com/twowvoting.html">';
		exit(2);
	}
} else {
	echo "ERROR: Request not received. Please access this page through the main page. (main.html)";
	sleep(2);
	echo '<meta http-equiv="refresh" content="0; URL=https://random314.000webhostapp.com/twowvoting.html">';
	exit(1);
}
?>