<?php
session_start();
$logf = fopen("./private/log.log","a");
$configs = include("./private/config.php");
$discclientid = $configs["discclientid"];
$discclientsecret = $configs["discclientsecret"];
$servername = $configs["servername"];
$username = $configs["username"];
$password = $configs["password"];
$database = $configs["database"];
$redirecturi = $configs["redirecturi"];
$apiendpoint = $configs["apiendpoint"];
$usersapi = $configs["apiurl"];
try {
    $conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Connected successfully"; 
    } catch(PDOException $e) {    
    echo "Connection failed: " . $e->getMessage();
    }
if ($_GET["state"] == $_SESSION["state"]) {
	$mode = substr($_GET["state"],-1);
	unset($_SESSION['state']);
	$code = $_GET["code"];
	$fields = array(
		'client_id' => urlencode($discclientid),
		'client_secret' => urlencode($discclientsecret),
		'grant_type' => urlencode("authorization_code"),
		'code' => urlencode($code),
		'redirect_uri' => urlencode($redirecturi),
		'scope' => urlencode("identify"),
	);
	$fields_string = "";
	foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
	rtrim($fields_string, '&');
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL, $apiendpoint);
	curl_setopt($ch,CURLOPT_POST, count($fields));
	curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,True);
	curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"POST");
	$headers = array(
		"content-type: application/x-www-form-urlencoded",
	);
	curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
	$result = json_decode(curl_exec($ch));
	$access_token = $result->access_token;
	$_SESSION["access_token"] = $access_token;
	curl_close($ch);
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL, $usersapi);
	$headers = array(
		"Authorization: Bearer ".$access_token,
	);
	curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
	curl_setopt($ch,CURLOPT_HTTPGET,True);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,True);
	$user = curl_exec($ch);
	$user = json_decode($user);
	$ip = $_SERVER["REMOTE_ADDR"];
	if(filter_var($ip, FILTER_VALIDATE_IP)) {
		// ip is valid
	} else {
		echo "Error: IP Address failed validation check. Redirecting.";
		sleep(5);
		echo '<meta http-equiv="refresh" content="0; URL=https://random314.000webhostapp.com/twowvoting.html">';
	}
	$log = $ip." ".time()." "."oauth.php - Access \n";
	fwrite($logf,$log);
	$id = $user->id;
	$username = $user->username;
	$sqlstring = "INSERT IGNORE INTO user_verify_table (discord_id) VALUES (?);";
	$updatestring = "UPDATE user_verify_table SET last_ip = ? WHERE discord_id = ?";
	$accessupdate = "UPDATE user_verify_table SET access_token = ? WHERE discord_id = ?";
	$usernameupdate = "UPDATE user_verify_table SET last_known_username = ? WHERE discord_id = ?";
	$conn->prepare($sqlstring)->execute([$id]);
	$conn->prepare($updatestring)->execute([$ip,$id]);
	$conn->prepare($accessupdate)->execute([$access_token,$id]);
	$conn->prepare($usernameupdate)->execute([$username,$id]);
	if ($mode == "1") {
		$cookiedata = md5($access_token."@cd".(string)(time()).(string)(uniqid()));
		$cookiesql = "UPDATE user_verify_table SET cookiedata = ? WHERE discord_id = ?";
		$conn->prepare($cookiesql)->execute([$cookiedata,$id]);
		setcookie("loginuser", $cookiedata, time() + (86400 * 30), "/");
	}
	$_SESSION["user"] = $id;
	$_SESSION["username"] = $username;
	curl_close($ch);
	echo '<meta http-equiv="refresh" content="0; URL=https://random314.000webhostapp.com/twowvotingmain.php">';
} else {
	echo "State does not match. Redirecting.";
	echo '<meta http-equiv="refresh" content="0; URL=https://random314.000webhostapp.com/twowvoting.html">';
}
?>