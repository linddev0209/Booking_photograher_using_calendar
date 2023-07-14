<?php 
ob_start("ob_gzhandler");
if(empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off"){
    $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $redirect);
    exit();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1');
header("Strict-Transport-Security: max-age=31536000");

header("Referrer-Policy: origin-when-cross-origin");

require("includes/cdb.php");


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

if($_SESSION['SESS_LEVEL']!="admin") {
	if ($_SESSION['SESS_LEVEL']!="staff"){
		if ($_SESSION['SESS_LEVEL']!="dealer"){
				header("location: index.php?flag=failedauth");
				exit();
		}
	}
}

if ($_GET['task'] == "logout"){ logout(); exit(); }

$fname = $_SESSION['SESS_FIRST_NAME'];
$lname = $_SESSION['SESS_LAST_NAME'];

$link = mysqli_connect($connection, $sqluser, $sqlpw, $dbname);

if(mysqli_connect_errno()) {
    $msg = "Database connection failed: ";
    $msg .= mysqli_connect_error();
    $msg .= " : " . mysqli_connect_errno();
    exit($msg);
 }

if ($_SESSION['SESS_LEVEL']=="dealer"){
	$dealer_id = $_SESSION['SESS_MEMBER_ID'];
}

if ($_GET['task'] == "hold"){
	$order_hold = $_GET['order_id'];
	if(!($hold_order = mysqli_query($link,"UPDATE orders SET scheduled='0000-00-00 00:00:00', status='hold' WHERE id = '$order_hold'"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
	}
	header("Location: appts.php");
	exit();
}

if ($_GET['task'] == "cancel"){
	$apt_cancel = $_GET['order_id'];
	if(!($cancel_apt = mysqli_query($link,"UPDATE orders SET scheduled='0000-00-00 00:00:00', status='pending' WHERE id = '$apt_cancel'"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
	}
	header("Location: appts.php");
	exit();
}

$offset = $_GET['offset'];
if($offset=="") $offset=0; // set default records
$totpage = 10; // total records per page

$today = date("Y-m-d"); 

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Appointments | Look2 Home Marketing</title>
<link href="css/l2_cbh.css" rel="stylesheet" type="text/css" />
</head>


<body>
<div id="horizon_top">
	<div class="container">
		<div id="header_bar"><span class="account_menu"><strong>Welcome, <?php echo $fname."&nbsp;".$lname; ?></strong><br /><a href="account.php">Account</a> | <a href="<?php echo $_SERVER["PHP_SELF"]; ?>?task=logout">Log Out</a></span><img src="images/logo.jpg" width="199" height="127" alt="Look2 Home Marketing"/><div class="clear"></div></div>
	</div>
</div>
<div id="horizon_menu">
	<div class="container">
		<div id="menu_bar">
			<div id="menu_section">
				<ul>
					<?php if ($_SESSION['SESS_LEVEL']=="admin") { ?>
					<li><a href="admin_index.php">Orders</a></li>
					<li><a href="companies.php">Companies</a></li>
					<li><a href="agents.php">Agents</a></li>
					<li><a href="dealers.php">Dealers</a></li>
					<li><a href="staff.php">Staff</a></li>
					<li><a href="admin.php">Admins</a></li>
					<li><a href="billing.php">Billing</a></li>
					<?php } if ($_SESSION['SESS_LEVEL']=="staff") {  ?>
					<li><a href="staff_index.php">Orders</a></li>
					<li><a href="companies.php">Companies</a></li>
					<li><a href="agents.php">Agents</a></li>
					<li><a href="dealers.php">Dealers</a></li>
					<?php } if ($_SESSION['SESS_LEVEL']=="dealer") { ?>
					<li><a href="dealer_index.php">Orders</a></li>
					<li><a href="companies.php">Companies</a></li>
					<li><a href="agents.php">Agents</a></li>
					<li><a href="appts.php">Appointments</a></li>
					<?php } ?> 
				</ul>
			</div>
		</div>
	</div>
</div>
<div id="horizon_content">
	<div id="container_content">
		<div id="cs_outter">
		  <div class="cs_container">
				<div class="cs_fullheader"><h1>Appointments</h1></div>
				<?php 
	if(!($sched_db = mysqli_query($link,"SELECT distinct(date(scheduled)) as sched_date FROM orders WHERE scheduled >= '$today' AND assigned = '$dealer_id' AND status = 'scheduled' ORDER BY scheduled ASC LIMIT $offset, $totpage"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
	}
	$i=1;
	while($sched_tot=mysqli_fetch_array($sched_db)){
		$date = $sched_tot['sched_date'];
		
		$j = 0;
	  ?>
				<div class="cs_halfcontent"><?php echo date("F d, Y", strtotime($date)); ?></div>
			<div class="cs_halfcontent">&nbsp;</div>
			<div class="clear"></div>
				  	<div class="cs_fifthcontent"><strong>Time</strong></div>
					<div class="cs_fifthcontent"><strong>Address</strong></div>
					<div class="cs_fifthcontent"><strong>Area</strong></div>
					<div class="cs_fifthcontent"><strong>Agent</strong></div>
					<div class="cs_fifthcontent"></div>
					<div class="clear"></div>
					<hr class="sep" />
				<?php
		if(!($orders_db = mysqli_query($link,"SELECT * FROM orders WHERE date(scheduled) = '$date' AND assigned = '$dealer_id' ORDER BY scheduled ASC"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
			exit();
		}
		while($sched_order=mysqli_fetch_array($orders_db)){
			$agent_id = $sched_order['agent_id'];
		
			if(!($agent_db = mysqli_query($link,"SELECT firstname, lastname FROM users where id = '$agent_id'"))){
				printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
				exit();
			}	 
			$agent_detail = mysqli_fetch_array($agent_db);
			$agent_name = $agent_detail['lastname'].", ".$agent_detail['firstname'];
?>
				 	<div class="cs_fifthcontent"><?php echo date("g:i A",strtotime($sched_order['scheduled'])); ?></div>
					<div class="cs_fifthcontent"><a href="schedule.php?order_id=<?php echo $sched_order['id']; ?>"><?php if($sched_order['street_num']!="0") { echo $sched_order['street_num']; } ?> <?php echo $sched_order['street_dir']." ".$sched_order['street_name']; ?></a></div>
					<div class="cs_fifthcontent"><?php echo $sched_order['city'].", ".$sched_order['state']; ?></div>
					<div class="cs_fifthcontent"><?php echo $agent_name; ?></div>
					<div class="cs_fifthcontent"><a href="schedule.php?order_id=<?php echo $sched_order['id']; ?>">Reschedule</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="?task=hold&order_id=<?php echo $sched_order['id']; ?>">On Hold</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="?task=cancel&order_id=<?php echo $sched_order['id']; ?>">Cancel Appointment</a></div>
					<div class="clear"></div>
				  	<?php $j++; } ?>
				  	<div class="cs_fifthcontent">&nbsp;</div>
					<div class="cs_fifthcontent">&nbsp;</div>
					<div class="cs_fifthcontent">&nbsp;</div>
					<div class="cs_fifthcontent">&nbsp;</div>
					<div class="cs_fifthcontent">&nbsp;</div>
					<div class="clear"></div>
				<?php $i++; } ?>
			</div>
		</div>
	</div>
</div>
<div id="horizon_footer_top">
	<div id="container_footer_top">
		<div id="footer_top_bar"></div>
	</div>
</div>
<div id="horizon_footer_bot">
	<div id="container_footer_bot">
		<div id="footer_bot_bar"><strong>&copy; 2019</strong> Look2 Home Marketing. All Rights Reserved. <a href="terms.php">Terms/Privacy Policy</a></div>
	</div>
</div>
</body>
</html>