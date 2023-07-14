<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli('localhost', 'look2hom_l2int1', 'b5&?UKQA_pbX;8DC]p', 'look2hom_main');
if($_FILES['file'] && $_POST['submit-all']){ 
    $order_photo = (int)$_POST['order_id'];
    $photo_fp = (int)$_POST['fp_id'];
    $save_path = sprintf("floorplan/%s/photos/", $order_photo); 
    if(!is_dir("floorplan/%s/photos",$order_photo))
    {
        mkdir(sprintf("floorplan/%s/photos",$order_photo));
    } 
    $count = count($_FILES['file']['name']);
    for($i=0; $i < $count; $i++ )
    {
		$sql = "SELECT * FROM photos where order_id = '$order_photo' AND fp_id = '$photo_fp' ORDER BY sort DESC LIMIT 1";
		$result = $mysqli->query($sql);
		if($results->num_rows === 0) {
    		$z=$i + 1;
		} else {
    		$orders  = $result->fetch_assoc();
			$z=$orders['sort'] + 1;
    	}
		$stmt = $mysqli->prepare("INSERT INTO photos(order_id, fp_id, sort) VALUES (?, ?, ?)");
    	$stmt->bind_param("sss", $order_photo, $photo_fp, $z);
    	$stmt->execute();
    	$photo_id = $stmt->insert_id;
        $fileName[] = mb_strtolower(basename($_FILES['file']['name'][$i]),'UTF-8');
        $extension[] = pathinfo($fileName[$i], PATHINFO_EXTENSION);
        $fileNewName[] = sprintf($save_path . $order_photo . "_"  . "%s" . "_". $photo_id ."." . $extension[$i],$photo_fp); 
        move_uploaded_file($_FILES['file']['tmp_name'][$i],$fileNewName[$i]);
        print $fileNewName[$i];

    }
} 