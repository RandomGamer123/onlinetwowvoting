<?php
session_start();
$_SESSION = array();
session_destroy();
setcookie("PHPSESSID","",time()+5,"/");
setcookie("loginuser","",time()+5,"/");
echo 'All cookies programmed to be set by us have been deleted. The session has been terminated. You have been logged out. Certain cookies, such as _omappvp, set by some default tools may still remain, you can delete them by yourself. <br> <a href="https://random314.000webhostapp.com/twowvoting.html">Return to main page.</a>';
?>