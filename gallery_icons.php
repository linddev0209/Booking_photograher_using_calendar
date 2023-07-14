<?php 



header("Content-Type: application/xml; charset=UTF-8"); 



echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";



?>



<?php 



require("includes/cdb.php");



$order_id = $_GET['order_id'];



$fp_id = $_GET['fp_id'];







?>



<?php



$link = mysqli_connect($connection, $sqluser, $sqlpw, $dbname);

if(mysqli_connect_errno()) {
    $msg = "Database connection failed: ";
    $msg .= mysqli_connect_error();
    $msg .= " : " . mysqli_connect_errno();
    exit($msg);
 }




?>



<icons>



<?php



if(!($info_res = mysqli_query($link,"SELECT * FROM info_cont WHERE order_id = '$order_id' AND fp_id = '$fp_id' ORDER BY sort"))){

		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));

		exit();

	} 



while ($row = mysqli_fetch_array($info_res))



{



	//echo $row['id']; 


	$caption = $row['label'];

	$pid = $row['id'];

	$res1 = mysqli_query($link, "SELECT * FROM info_coord WHERE order_id = '$order_id' AND fp_id = '$fp_id' AND info_id='$pid' ");

	$row1 = mysqli_fetch_array($res1);

	$x = $row1['xcoord'];

	$y = $row1['ycoord'];



?>



<icon label="<?php echo $caption ?>" photoid="<?php echo $row['id'] ?>" sortno="<?php echo $row['sort'] ?>" x="<?php echo $x ?>" y="<?php echo $y ?>" />



<?php



}



?>



</icons>