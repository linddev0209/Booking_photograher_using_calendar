<?php
require("includes/cdb.php");
$order_id=$_GET['order_id'];

$link = mysqli_connect($connection, $sqluser, $sqlpw, $dbname);

if(mysqli_connect_errno()) {
    $msg = "Database connection failed: ";
    $msg .= mysqli_connect_error();
    $msg .= " : " . mysqli_connect_errno();
    exit($msg);
 }

// apply modifications as a downloaded file with your customized HTTP headers
header("Content-Type: archive/zip");
header("Content-type: application/force-download");
header("Content-Disposition: attachment; filename=".$order_id.".zip");

if(!($orders_db = mysqli_query($link,"select * from orders where id = '$order_id'"))){
	printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
	exit();
}
$orders = mysqli_fetch_array($orders_db);

include_once('includes/tbszip.php'); // load the TbsZip library
$zip = new clsTbsZip(); // create a new instance of the TbsZip class
$zip->CreateNew(); // start a new empty archive for adding files

// add a files to the archive
if($orders['fp']=="y" || $orders['hdr_only']=="y" || $orders['photos']=="y") { 
if(!($photo_db = mysqli_query($link,"select * FROM photos WHERE order_id = '$order_id'"))){
	printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
	exit();
}
while($photo_gal = mysqli_fetch_array($photo_db)){
	if (file_exists('./floorplan/'.$order_id.'/photos/'.$order_id."_".$photo_gal['fp_id']."_".$photo_gal['id'].'_wr.jpg')) {
	$zip->FileAdd('photos/'.$order_id."_".$photo_gal['fp_id']."_".$photo_gal['id'].'.jpg', './floorplan/'.$order_id.'/photos/'.$order_id."_".$photo_gal['fp_id']."_".$photo_gal['id'].'_wr.jpg', TBSZIP_FILE);
} else {
	$image = new SimpleImage2('./floorplan/'.$order_id.'/photos/'.$order_id."_".$photo_gal['fp_id']."_".$photo_gal['id'].'.jpg');
    $image->thumbnail(1401,788)->toFile('./floorplan/'.$order_id.'/photos/'.$order_id."_".$photo_gal['fp_id']."_".$photo_gal['id'].'_wr.jpg'); }
	$zip->FileAdd('photos/'.$order_id."_".$photo_gal['fp_id']."_".$photo_gal['id'].'.jpg', './floorplan/'.$order_id.'/photos/'.$order_id."_".$photo_gal['fp_id']."_".$photo_gal['id'].'_wr.jpg', TBSZIP_FILE);

} }


// apply modifications as an HTTP downloaded file
$zip->Flush(TBSZIP_DOWNLOAD, $order_id.'.zip');

// -----------------
// Close the archive
// -----------------

$zip->Close(); // stop to work with the opened archive. Modifications are not applied to the opened archive, use Flush() to commit  

if(!($photo_db = mysqli_query($link,"select * FROM photos WHERE order_id = '$order_id'"))){
	printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
	exit();
}
while($photo_gal = mysqli_fetch_array($photo_db)){
	unlink('./floorplan/'.$order_id.'/photos/'.$order_id."_".$photo_gal['fp_id']."_".$photo_gal['id'].'_wr.jpg');	
}

?>