<?php 

require("includes/cdb.php");

$link = mysqli_connect($connection, $sqluser, $sqlpw, $dbname);

if(mysqli_connect_errno()) {
    $msg = "Database connection failed: ";
    $msg .= mysqli_connect_error();
    $msg .= " : " . mysqli_connect_errno();
    exit($msg);
 }

session_start();
//Check if logged in
if(!isset($_SESSION['SESS_MEMBER_ID'])) {
        //check the cookie
        if(isset($_COOKIE['MEMBER_ID']) && isset($_COOKIE['FIRST_NAME']) && isset($_COOKIE['LAST_NAME']) && isset($_COOKIE['LEVEL'])) {
            //restore session from cookie
			session_regenerate_id();
			$_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp
			$_SESSION['CREATED'] = time();  // update creation time
			$_SESSION['SESS_MEMBER_ID'] = site_crypt($_COOKIE['MEMBER_ID'], 'd' );
			$_SESSION['SESS_FIRST_NAME'] = site_crypt($_COOKIE['FIRST_NAME'], 'd' );
			$_SESSION['SESS_LAST_NAME'] = site_crypt($_COOKIE['LAST_NAME'], 'd' );
			$_SESSION['SESS_LEVEL'] = site_crypt($_COOKIE['LEVEL'], 'd' );
			session_write_close();
        } else {
			header("location: index.php?flag=failedauth");
			exit();
		}
}

if($_SESSION['SESS_LEVEL']!="admin") {
	if ($_SESSION['SESS_LEVEL']!="staff"){
				header("location: index.php?flag=failedauth");
				exit();
	}
}

$order_id 	= $_GET['order_id'];
$fp_id 		= $_GET['fp_id'];
$photo_id 	= $_GET['photo_id'];
$x 			= $_GET['xcoord'];
$y 			= $_GET['ycoord'];

if(!($upicon = mysqli_query($link,"SELECT * FROM info_coord WHERE order_id = '$order_id' AND fp_id = '$fp_id' AND info_id = '$photo_id'"))){
	printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
	exit();
} 

if(mysqli_num_rows($upicon) > 0 ) {
	if(!($upicon = mysqli_query($link,"UPDATE info_coord SET xcoord = '$x', ycoord='$y' WHERE order_id = '$order_id' AND fp_id = '$fp_id' AND info_id = '$photo_id'"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
	} 
} else {
	if(!($upicon = mysqli_query($link,"INSERT into info_coord (fp_id, info_id, order_id, xcoord, ycoord) VALUES ($fp_id, $photo_id, $order_id, '$x', '$y')"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
	}
}

?>