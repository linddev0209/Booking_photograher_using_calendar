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
if($orders['fp']=="y" || $orders['hdr_only']=="y" || $orders['photos']=="y" || $orders['aerial_pics']=="y"|| $orders['twilight']=="y") { 
if(!($photo_db = mysqli_query($link,"select * FROM photos WHERE order_id = '$order_id'"))){
	printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
	exit();
}
while($photo_gal = mysqli_fetch_array($photo_db)){
	$zip->FileAdd('photos/'.$order_id."_".$photo_gal['fp_id']."_".$photo_gal['id'].'.jpg', './floorplan/'.$order_id.'/photos/'.$order_id."_".$photo_gal['fp_id']."_".$photo_gal['id'].'.jpg', TBSZIP_FILE);
} }

if($orders['fp']=="y" || $orders['draw_only']=="y" || $orders['fact_sketch']=="y") { 
if(!($all_fp = mysqli_query($link,"SELECT * FROM floorplans where order_id = '$order_id'"))){
	printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
	exit();
}
while($fp_gal = mysqli_fetch_array($all_fp)){
	$zip->FileAdd($order_id."_".$fp_gal['id'].'.jpg', './floorplan/'.$order_id.'/'.$order_id."_".$fp_gal['id'].'.jpg', TBSZIP_FILE);
} }

if($orders['vid']=="y" || $orders['vid_only']=="y" || $orders['cine_vid']=="y" || $orders['cvid_o']=="y" || $orders['avid_o']=="y" || $orders['avid']=="y") { 
	if(file_exists("./floorplan/".$order_id."/video/".$order_id."_hr.mp4")){ 
	$zip->FileAdd($order_id.'_hr.mp4', './floorplan/'.$order_id.'/video/'.$order_id.'_hr.mp4', TBSZIP_FILE);
	}
	if(file_exists("./floorplan/".$order_id."/video/".$order_id."_branded.mp4")){ 
	$zip->FileAdd($order_id.'_branded.mp4', './floorplan/'.$order_id.'/video/'.$order_id.'_branded.mp4', TBSZIP_FILE);
	}
	if(file_exists("./floorplan/".$order_id."/video/".$order_id.".mp4")){ 
	$zip->FileAdd($order_id.'.mp4', './floorplan/'.$order_id.'/video/'.$order_id.'.mp4', TBSZIP_FILE);
	}
	if(file_exists("./floorplan/".$order_id."/video/".$order_id.".ogv")){ 
	$zip->FileAdd($order_id.'.ogv', './floorplan/'.$order_id.'/video/'.$order_id.'.ogv', TBSZIP_FILE);
	}
}

// apply modifications as an HTTP downloaded file
$zip->Flush(TBSZIP_DOWNLOAD, $order_id.'.zip');

// -----------------
// Close the archive
// -----------------

$zip->Close(); // stop to work with the opened archive. Modifications are not applied to the opened archive, use Flush() to commit  

?>