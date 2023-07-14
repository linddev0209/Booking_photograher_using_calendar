<?php
ob_start("ob_gzhandler");
require("includes/cdb.php");

session_start();
$link = mysqli_connect($connection, $sqluser, $sqlpw, $dbname);

if ($link -> connect_errno) {
	$msg = "Database connection failed: ";
    $msg .= mysqli_connect_error();
    $msg .= " : " . mysqli_connect_errno();
    exit($msg);
}

if($_SESSION['SESS_LEVEL']!="admin") {
	if ($_SESSION['SESS_LEVEL']!="staff"){
			header("location: index.php?flag=failedauth");
			exit();
	}
}

$company_id = $_POST['comp_id'];
if(!($agent_sel = mysqli_query($link,"SELECT id, firstname, lastname, cell_phone, email FROM users WHERE company_id = '$company_id' ORDER BY lastname ASC"))){
	printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
	exit();
}		
$num_sgents = mysqli_num_rows($agent_sel);	
echo"<strong>Agent:</strong>&nbsp;<select name='move_agent'><option value=\"\">Select Agent</option>";													
while($select_agent=mysqli_fetch_array($agent_sel)) { 
    echo "<option value=".$select_agent['id'].">".$select_agent['lastname'].", ".$select_agent['firstname']."</option>";
}
echo"</select>";
?>