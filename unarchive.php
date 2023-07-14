<?
require("includes/cdb.php");

$link = mysqli_connect($connection, $sqluser, $sqlpw, $dbname);

if(mysqli_connect_errno()) {
    $msg = "Database connection failed: ";
    $msg .= mysqli_connect_error();
    $msg .= " : " . mysqli_connect_errno();
    exit($msg);
 }

if (!($link_arch = mysqli_connect($connection2, $sqluser2, $sqlpw2, $dbname2))){
	DisplayErrMsg(sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
	exit() ;
}

$i = 0;

$oid = $_GET['id'];

//Check if logged in
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
                if($_SESSION['SESS_LEVEL'] == 'agent'){
					header("location: enter_order1.php");
					exit();
				}
        } else {
			header("location: index.php?flag=failedauth&ref=agents.php");
			exit();
		}
}


//Check whether the session variable SESS_MEMBER_ID is present or not
	if(!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
		header("location: index.php?flag=failedauth&ref=agents.php");
		exit();
	}

$id = $_SESSION['SESS_MEMBER_ID'];
$fname = $_SESSION['SESS_FIRST_NAME'];
$lname = $_SESSION['SESS_LAST_NAME'];
$order_id = $_GET['order_id'];

if($_SESSION['SESS_LEVEL']!="admin") {
	if ($_SESSION['SESS_LEVEL']!="staff"){
		header("location: index.php?flag=failedauth");
				exit();
	}
}

if ($oid=="") {
	echo "No Orders Selected";
} else {

if(!($db_arch =  mysqli_query($link_arch,"SELECT * FROM orders WHERE id = '$oid'"))){
	printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
	exit(); 
}
while($ind_arch = mysqli_fetch_array($db_arch)) {
	$order_id = $oid;
	$order_fp = $ind_arch['fp'];
	$order_draw = $ind_arch['draw_only'];
	$order_hdr_photos = $ind_arch['hdr_photos'];
	$order_hdr_only = $ind_arch['hdr_only'];
	$order_twilight = $ind_arch['twilight'];
	$order_photos = $ind_arch['photos'];
	$order_vid = $ind_arch['vid'];
	$order_3d = $ind_arch['3d'];
	$order_atc = $ind_arch['atc'];
	$order_vid_nar = filter_var($ind_arch['vid_nar'], FILTER_SANITIZE_STRING);
	$order_vid_only = $ind_arch['vid_only'];
	$order_aerial_pics = $ind_arch['aerial_pics'];
	$order_cine_vid = $ind_arch['cine_vid'];
	$order_avid_o = $ind_arch['avid_o'];
	$order_avid = $ind_arch['avid'];
	$order_cvid_o = $ind_arch['cvid_o'];
	$order_addy_url = $ind_arch['addy_url'];
	$order_launch = $ind_arch['launch'];
	$order_company_id = $ind_arch['company_id'];
	$order_agent_id = $ind_arch['agent_id'];
	$order_assigned = $ind_arch['assigned'];
	$order_ordered = $ind_arch['ordered'];
	$order_scheduled = $ind_arch['scheduled'];
	$order_processed = $ind_arch['processed'];
	$order_processed_by = $ind_arch['processed_by'];
	$order_file_rec = $ind_arch['file_rec'];
	$order_status = $ind_arch['status'];
	$order_disp_area = $ind_arch['disp_area'];
	$order_disp_bed = $ind_arch['disp_bed'];
	$order_instruct = filter_var($ind_arch['instruct'], FILTER_SANITIZE_STRING);
	$order_pets = $ind_arch['pets'];
	$order_qr_gen = $ind_arch['qr_gen'];
	$order_flyer_layout = $ind_arch['flyer_layout'];
	$order_flyer_int = filter_var($ind_arch['flyer_int'], FILTER_SANITIZE_STRING);
	$order_owner = filter_var($ind_arch['owner'], FILTER_SANITIZE_STRING);
	$order_owner_email = $ind_arch['owner_email'];
	$order_cell_phone1 = $ind_arch['cell_phone1'];
	$order_street_num = $ind_arch['street_num'];
	$order_street_name = filter_var($ind_arch['street_name'], FILTER_SANITIZE_STRING);
	$order_street_dir = $ind_arch['street_dir'];
	$order_unit = $ind_arch['unit'];
	$order_city = filter_var($ind_arch['city'], FILTER_SANITIZE_STRING);
	$order_state = $ind_arch['state'];
	$order_zip = $ind_arch['zip'];
	$order_county = $ind_arch['county'];
	$order_prop_type = $ind_arch['prop_type'];
	$order_price = $ind_arch['price'];
	$order_latitude = $ind_arch['latitude'];
	$order_longitude = $ind_arch['longitude'];
	$order_vacant = $ind_arch['vacant'];
	$order_directions = filter_var($ind_arch['directions'], FILTER_SANITIZE_STRING);
	$order_lockbox = $ind_arch['lockbox'];
	$order_s_map = $ind_arch['s_map'];
	$order_rush = $ind_arch['rush'];
	$order_fp_assist = $ind_arch['fp_assist'];
	$order_p_assist = $ind_arch['p_assist'];
	$order_a_assist = $ind_arch['a_assist'];
	$order_zd_assist = $ind_arch['3d_assist'];
	$order_t_assist = $ind_arch['t_assist'];
	
	
	if(!($sel_photos =  mysqli_query($link_arch,"SELECT * FROM photos WHERE order_id = '$order_id' ORDER BY id"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
		exit(); 
	}
	
	while($move_photos=mysqli_fetch_array($sel_photos)){
		$ap_id = $move_photos['id'];
		$ap_order = $move_photos['order_id'];
		$ap_fp = $move_photos['fp_id'];
		$ap_caption = filter_var($move_photos['caption'], FILTER_SANITIZE_STRING);
		$ap_sort = $move_photos['sort'];
		$ap_flyer = $move_photos['flyer_sort'];
		
		if(!($int_photos =  mysqli_query($link,"INSERT INTO photos VALUES ('$ap_id','$ap_order','$ap_fp','$ap_caption','$ap_sort','$ap_flyer')"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
			exit();
		}
		
		if(!($rm_photo =  mysqli_query($link_arch,"DELETE FROM photos WHERE id = '$ap_id'"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
			exit();
		}
	}
	
	
	if(!($sel_area =  mysqli_query($link_arch,"SELECT * FROM area_calc WHERE order_id = '$order_id' ORDER BY id"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
		exit(); 
	}
	
	while($move_area=mysqli_fetch_array($sel_area)){
		$area_id = $move_area['id'];
		$area_order = $move_area['order_id'];
		$area_area = filter_var($move_area['area'], FILTER_SANITIZE_STRING);
		$area_sqft = $move_area['sqft'];
		$area_sort = $move_area['sort'];
		
		if(!($int_area =  mysqli_query($link,"INSERT INTO area_calc VALUES ('$area_id','$area_order','$area_area','$area_sqft','$area_sort')"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
			exit();
		}
		
		if(!($rm_area =  mysqli_query($link_arch,"DELETE FROM area_calc WHERE id = '$area_id'"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
			exit();
		}
	}
	
	
	if(!($sel_coor =  mysqli_query($link_arch,"SELECT * FROM coordinates WHERE order_id = '$order_id' ORDER BY photo_id"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
		exit(); 
	}
	
	while($move_coor=mysqli_fetch_array($sel_coor)){
		$coor_fp = $move_coor['fp_id'];
		$coor_photo = $move_coor['photo_id'];
		$coor_order = $move_coor['order_id'];
		$coor_x = $move_coor['xcoord'];
		$coor_y = $move_coor['ycoord'];
		
		if(!($int_coor =  mysqli_query($link,"INSERT INTO coordinates VALUES ('$coor_fp','$coor_photo','$coor_order','$coor_x','$coor_y')"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
			exit();
		}
		
		if(!($rm_coor =  mysqli_query($link_arch,"DELETE FROM coordinates WHERE photo_id = '$coor_photo'"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
			exit();
		}
	}
	
	
	if(!($sel_fp =  mysqli_query($link_arch,"SELECT * FROM floorplans WHERE order_id = '$order_id' ORDER BY id"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
		exit(); 
	}
	
	while($move_fp=mysqli_fetch_array($sel_fp)){
		$fp_id = $move_fp['id'];
		$fp_order = $move_fp['order_id'];
		$fp_label = filter_var($move_fp['label'], FILTER_SANITIZE_STRING);
		$fp_sort = $move_fp['sort'];
		
		if(!($int_fp =  mysqli_query($link,"INSERT INTO floorplans VALUES ('$fp_id','$fp_order','$fp_label','$fp_sort')"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
			exit();
		}
		
		if(!($rm_fp =  mysqli_query($link_arch,"DELETE FROM floorplans WHERE id = '$fp_id'"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
			exit();
		}
	}
	
	if(!($sel_icont =  mysqli_query($link_arch,"SELECT * FROM info_cont WHERE order_id = '$order_id' ORDER BY id"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
		exit(); 
	}
	
	while($move_icont=mysqli_fetch_array($sel_icont)){
		$icont_id = $move_icont['id'];
		$icont_order = $move_icont['order_id'];
		$icont_fp = $move_icont['fp_id'];
		$icont_label = filter_var($move_icont['label'], FILTER_SANITIZE_STRING);
		$icont_content = filter_var($move_icont['content'], FILTER_SANITIZE_STRING);
		$icont_sort = $move_icont['sort'];
		
		if(!($int_fp =  mysqli_query($link,"INSERT INTO info_cont VALUES ('$icont_id','$icont_order','$icont_fp','$icont_label','$icont_content','$icont_sort')"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
			exit();
		}
		
		if(!($rm_icont =  mysqli_query($link_arch,"DELETE FROM info_cont WHERE id = '$icont_id'"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
			exit();
		}
	}
	
	
	if(!($sel_icoor =  mysqli_query($link_arch,"SELECT * FROM info_coord WHERE order_id = '$order_id' ORDER BY info_id"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
		exit(); 
	}
	
	while($move_icoor=mysqli_fetch_array($sel_icoor)){
		$icoor_fp = $move_icoor['fp_id'];
		$icoor_info = $move_icoor['info_id'];
		$icoor_order = $move_icoor['order_id'];
		$icoor_x = $move_icoor['xcoord'];
		$icoor_y = $move_icoor['ycoord'];
		
		if(!($int_icoor =  mysqli_query($link,"INSERT INTO info_coord VALUES ('$icoor_fp','$icoor_info','$icoor_order','$icoor_x','$icoor_y')"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
			exit();
		}
		
		if(!($rm_icoor =  mysqli_query($link_arch,"DELETE FROM info_coord WHERE info_id = '$icoor_info'"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
			exit();
		}
	}
	
	
	if(!($sel_od =  mysqli_query($link_arch,"SELECT * FROM order_data WHERE order_id = '$order_id'"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
		exit(); 
	}
	
	while($move_od=mysqli_fetch_array($sel_od)){
		$od_order = $move_od['order_id'];
		$od_beds = $move_od['beds'];
		$od_offices = $move_od['offices'];
		$od_baths = $move_od['baths'];
		$od_hbath = $move_od['h_bath'];
		$od_int = $move_od['internal'];
		$od_ext = $move_od['external'];
		$od_done = $move_od['fp_done'];
		$od_efp = $move_od['efp_done'];
		$od_p = $move_od['photos_done'];
		$od_t = $move_od['tp_done'];
		$od_fix = $move_od['fix_done'];
		$od_drawn = filter_var($move_od['drawn_by'], FILTER_SANITIZE_STRING);
		
		if(!($int_od =  mysqli_query($link,"INSERT INTO order_data VALUES ('$od_order','$od_beds','$od_offices','$od_baths','$od_hbath','$od_int','$od_ext','$od_done','$od_efp','$od_p','$od_t','$od_fix','$od_drawn')"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
			exit();
		}
		
		if(!($rm_od =  mysqli_query($link_arch,"DELETE FROM order_data WHERE order_id = '$od_order'"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
			exit();
		}
	}
	
	
	if(!($sel_trans =  mysqli_query($link_arch,"SELECT * FROM transactions WHERE order_id = '$order_id' ORDER BY id"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
		exit(); 
	}
	
	while($move_trans=mysqli_fetch_array($sel_trans)){
		$trans_uid = $move_trans['id'];
		$trans_order = $move_trans['order_id'];
		$trans_date = $move_trans['date_trans'];
		$trans_dealer = $move_trans['dealer'];
		$trans_ifp = $move_trans['ifp'];
		$trans_hdr_up = $move_trans['hdr_up'];
		$trans_hdr_only = $move_trans['hdr_only'];
		$trans_spg_only = $move_trans['spg_only'];
		$trans_tlight_up = $move_trans['tlight_up'];
		$trans_real_com = $move_trans['real_com'];
		$trans_addy_url = $move_trans['addy_url'];
		$trans_vid = $move_trans['vid'];
		$trans_vid_o = $move_trans['vid_o'];
		$trans_draw_only = $move_trans['draw_only'];
		$trans_rebuild = $move_trans['rebuild'];
		$trans_retake = $move_trans['retake'];
		$trans_canc_fee = $move_trans['canc_fee'];
		$trans_trip_fee = $move_trans['trip_fee'];
		$trans_sign = $move_trans['sign'];
		$trans_post_only = $move_trans['post_only'];
		$trans_lighted = $move_trans['lighted'];
		$trans_sr = $move_trans['sr'];
		$trans_qr = $move_trans['qr'];
		$trans_qrgen = $move_trans['qrgen'];
		$trans_aphotos = $move_trans['aphotos'];
		$trans_avid = $move_trans['avid'];
		$trans_cvid = $move_trans['cvid'];
		$trans_3d = $move_trans['3d'];
		$trans_ae = $move_trans['ae'];
		$trans_rush_fee = $move_trans['rush_fee'];
		$trans_misc = $move_trans['misc'];
		$trans_total_trans = $move_trans['total_trans'];
		$trans_trans_id = $move_trans['trans_id'];
		$trans_result_trans = $move_trans['result_trans'];
		$trans_c4 = $move_trans['c4'];
		$trans_res_ad = $move_trans['res_ad'];
		
		if(!($int_trans =  mysqli_query($link,"INSERT INTO transactions VALUES ('$trans_uid','$trans_order','$trans_date','$trans_dealer','$trans_ifp','$trans_hdr_up','$trans_hdr_only','$trans_spg_only','$trans_tlight_up','$trans_real_com','$trans_addy_url','$trans_vid','$trans_vid_o','$trans_draw_only','$trans_rebuild','$trans_retake','$trans_canc_fee','$trans_trip_fee','$trans_sign','$trans_post_only','$trans_lighted','$trans_sr','$trans_qr','$trans_qrgen','$trans_aphotos','$trans_avid','$trans_cvid','$trans_3d','$trans_ae','$trans_rush_fee','$trans_misc','$trans_total_trans','$trans_trans_id','$trans_result_trans','$trans_c4','$trans_res_ad')"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
			exit();
		}
		
		if(!($rm_trans =  mysqli_query($link_arch,"DELETE FROM transactions WHERE id = '$trans_uid'"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
			exit();
		}
	}
	
	
	if(!($sel_zillow =  mysqli_query($link_arch,"SELECT * FROM zillow_tours WHERE order_id = '$order_id'"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
		exit(); 
	}
	
	while($move_zillow=mysqli_fetch_array($sel_zillow)){
		$zil_id = $move_zillow['id'];
		$zil_oid = $move_zillow['order_id'];
		$zil_tour = $move_zillow['tour_url'];

		
		if(!($int_zillow =  mysqli_query($link,"INSERT INTO zillow_tours VALUES ('$zil_id','$zil_oid','$zil_tour')"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
			exit();
		}
		
		if(!($rm_zillow =  mysqli_query($link_arch,"DELETE FROM zillow_tours WHERE id = '$zil_id'"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
			exit();
		}
	}
	
	
	if(!($unt_order_arch =  mysqli_query($link,"INSERT INTO orders VALUES ('$order_id','$order_fp','$order_draw','$order_hdr_photos','$order_hdr_only','$order_twilight','$order_photos','$order_vid','$order_3d','$order_atc','$order_vid_nar','$order_vid_only','$order_aerial_pics','$order_cine_vid','$order_avid_o','$order_avid','$order_cvid_o','$order_fact_sketch','$order_launch','$order_company_id','$order_agent_id','$order_assigned','$order_ordered','$order_scheduled','$order_processed','$order_processed_by','$order_file_rec','complete','$order_disp_area','$order_disp_bed','$order_instruct','$order_pets','$order_qr_gen','$order_flyer_layout','$order_flyer_int','$order_owner','$order_owner_email','$order_cell_phone1','$order_street_num','$order_street_name','$order_street_dir','$order_unit','$order_city','$order_state','$order_zip','$order_county','$order_prop_type','$order_price','$order_latitude','$order_longitude','$order_vacant','$order_directions','$order_lockbox','$order_s_map','$order_rush','$order_fp_assist','$order_p_assist','$order_a_assist','$order_zd_assist','$order_t_assist')"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
	}
	
	if(!($rm_order =  mysqli_query($link_arch,"DELETE FROM orders WHERE id = '$order_id'"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
			exit();
		}
	$i++;
}

echo "Orders Unarchived: ".$i;
exit();
}
?>
