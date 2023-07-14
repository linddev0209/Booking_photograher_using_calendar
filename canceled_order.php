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
				}elseif($_SESSION['SESS_LEVEL'] == 'agent'){
					header("location: enter_order1.php");
					exit();
				} else {
			header("location: index.php?flag=failedauth&ref=canceled.php");
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

if ($_SESSION['SESS_LEVEL']=="dealer"){
	$dealer_id = $_SESSION['SESS_MEMBER_ID'];
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

$hold_offset = $_GET['hold_offset'];
if($hold_offset=="") $hold_offset=0; // set default records
$canceled_offset = $_GET['canceled_offset'];
if($canceled_offset=="") $canceled_offset=0; // set default records
$totpage = 8; // total records per page

if ($_SESSION['SESS_LEVEL']=="dealer"){
	if(!($h_count = mysqli_query($link,"SELECT DISTINCT * FROM orders WHERE status = 'hold' AND orders.assigned = '$dealer_id'"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
	} 
	if(!($c_count = mysqli_query($link,"SELECT DISTINCT * FROM orders WHERE status = 'canceled' AND orders.assigned = '$dealer_id'"))){
	printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
	exit();
	}
} else { 
	if(!($h_count = mysqli_query($link,"SELECT DISTINCT * FROM orders WHERE status = 'hold'"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
	}
	if(!($c_count = mysqli_query($link,"SELECT DISTINCT * FROM orders WHERE status = 'canceled'"))){
	printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
	exit();
	}
}

$hold_records = mysqli_num_rows($h_count);
$canceled_records = mysqli_num_rows($c_count);

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Canceled/On Hold Orders | Look2 Home Marketing</title>
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
					<li><a class="active" href="admin_index.php">Orders</a></li>
					<li><a href="companies.php">Companies</a></li>
					<li><a href="agents.php">Agents</a></li>
					<li><a href="dealers.php">Dealers</a></li>
					<li><a href="staff.php">Staff</a></li>
					<li><a href="admin.php">Admins</a></li>
					<li><a href="billing.php">Billing</a></li>
					<?php } if ($_SESSION['SESS_LEVEL']=="staff") {  ?>
					<li><a class="active" href="staff_index.php">Orders</a></li>
					<li><a href="companies.php">Companies</a></li>
					<li><a href="agents.php">Agents</a></li>
					<li><a href="dealers.php">Dealers</a></li>
					<?php } if ($_SESSION['SESS_LEVEL']=="dealer") { ?>
					<li><a class="active" href="dealer_index.php">Orders</a></li>
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
				<div class="cs_fullcontent_right"><a href="enter_order.php">Enter Order</a> | <a href="canceled_order.php">Review Canceled Orders</a></div>
			</div>
	    <div class="section_container">
					 <div class="section_header">
			  <h1>On Hold</h1><span class="numb"><?php if($hold_records!="0") { ?>Total: <strong> <?php echo $hold_records; ?></strong><?php } ?></span><div class="clear"></div></div>
				<div class="section_date"><strong>Date</strong></div>
				<div class="section_address"><strong>Address</strong></div>
				<div class="section_market"><strong>Agent</strong></div>
				<div class="section_dealer"><strong>Dealer</strong></div>
				<div class="clear"></div>
			  <hr class="sep" />
				<?php if ($_SESSION['SESS_LEVEL']=="dealer"){
				if(!($h_order = mysqli_query($link,"SELECT orders.* FROM orders, users where orders.status = 'hold' AND orders.agent_id = users.id AND orders.assigned = '$dealer_id' ORDER BY ordered DESC LIMIT $hold_offset, $totpage"))){
					printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
					exit();
				} } else { if(!($h_order = mysqli_query($link,"SELECT orders.* FROM orders, users where orders.status = 'hold' AND orders.agent_id = users.id ORDER BY ordered DESC LIMIT $hold_offset, $totpage"))){
					printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
					exit();
				} }
				while($hold_order = mysqli_fetch_array($h_order)){
					$agent_hid = $hold_order['agent_id'];
					$assigned_hid = $hold_order['assigned'];
					$hold_date = date("m/d/y", strtotime($hold_order['ordered']));
					?>
				<div class="section_date"><?php echo $hold_date; ?></div>
				<div class="section_address"><a href="schedule.php?order_id=<?php echo $hold_order['id']; ?>"><?php if($hold_order['street_num']!="0") { echo $hold_order['street_num']; } ?> <?php if($hold_order['street_dir']!="") { echo $hold_order['street_dir']; } ?> <?php echo $hold_order['street_name']; ?></a></div>
				<div class="section_market"><?php 
	if(!($as_holdorder = mysqli_query($link,"SELECT firstname, lastname FROM users where id = '$agent_hid'"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
	} 
	$h_agent = mysqli_fetch_array($as_holdorder);  echo $h_agent['lastname'].", ".$h_agent['firstname'];
	?></div>
			  <div class="section_dealer"><?php 
	if(!($a_holdorder = mysqli_query($link,"SELECT firstname, lastname FROM users where id = '$assigned_hid'"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
	} 
	$h_assigned = mysqli_fetch_array($a_holdorder);  echo $h_assigned['lastname'].", ".$h_assigned['firstname'];
	?>
			  </div>
			  	<div class="clear"></div>
				<?php } ?>
				<hr class="sep" />
				<div class="section_footer">
					<? 
	$hold_prev = $hold_offset-$totpage;
	if ($hold_prev >= 0) $hold_nav.="<a href='".$PHP_SELF."?=hold_offset".$hold_prev."&canceled_offset=".$canceled_offset."'><strong>&lt;&lt;&nbsp;Prev</strong></a> | "; 
	if($hold_records!="0") {
	$hold_nav.="Section Page";
	}
	if($hold_records/$totpage < 10){
		$hold_crumbmax = $hold_records/$totpage;
	}else{
		$hold_crumbmax = 10; 
	}
	$hold_bottom = 0;
	if(($hold_offset/$totpage) > $hold_crumbmax/2) $hold_bottom = ($hold_offset/$totpage) - ceil($hold_crumbmax/2);
	$hold_top = $hold_bottom+$hold_crumbmax;
	if(ceil($hold_records/$totpage) < ($hold_offset/$totpage) + $hold_crumbmax/2)	$hold_top = ceil($hold_records/$totpage);
	for($h_hold=$hold_bottom; $h_hold<$hold_top; $h_hold++){
		if($hold_offset/$totpage==$h_hold){
			$hold_nav.=" ".($h_hold+1); 
		}else {  
			$hold_nav.=" <a href='".$PHP_SELF."?hold_offset=".($h_hold)*$totpage."&canceled_offset=".$canceled_offset."'>".($h_hold+1)."</a>"; 
	  	} 
	} 
    $hold_next = $hold_offset+$totpage;
	if ($hold_next < ($hold_records))	$hold_nav.=" | <a href='".$PHP_SELF."?hold_offset=".$hold_next."&canceled_offset=".$canceled_offset."'><b>Next&nbsp;&gt;&gt;</b></a>";
	echo $hold_nav; ?>
	      </div>
		</div>
			<div class="section_container">
				<div class="section_header">
				  <h1>Canceled</h1><span class="numb"><?php if($canceled_records!="0") { ?>Total: <strong> <?php echo $canceled_records; ?></strong><?php } ?></span><div class="clear"></div></div>
				<div class="section_date"><strong>Ordered</strong></div>
				<div class="section_address"><strong>Address</strong></div>
			  <div class="section_market"><strong>Agent</strong></div>
				<div class="section_dealer"><strong>Dealer</strong></div>
				<div class="clear"></div>
				<hr class="sep" />
				<?php if ($_SESSION['SESS_LEVEL']=="dealer"){
				if(!($c_order = mysqli_query($link,"SELECT orders.* FROM orders, users where orders.status = 'canceled' AND orders.agent_id = users.id AND orders.assigned = '$dealer_id' ORDER BY ordered DESC LIMIT $canceled_offset, $totpage"))){
					printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
					exit();
				} } else { if(!($c_order = mysqli_query($link,"SELECT orders.* FROM orders, users where orders.status = 'canceled' AND orders.agent_id = users.id ORDER BY ordered DESC LIMIT $canceled_offset, $totpage"))){
					printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
					exit();
				} }
				while($canceled_order = mysqli_fetch_array($c_order)){
					$agent_cid = $canceled_order['agent_id'];
					$assigned_cid = $canceled_order['assigned'];
					$canceled_date = date("m/d/y", strtotime($canceled_order['ordered']));
					?>
				<div class="section_date"><?php echo $canceled_date; ?></div>
				<div class="section_address"><a href="schedule.php?order_id=<?php echo $canceled_order['id']; ?>"><?php if($canceled_order['street_num']!="0") { echo $canceled_order['street_num']; } ?> <?php if($canceled_order['street_dir']!="") { echo $canceled_order['street_dir']; } ?> <?php echo $canceled_order['street_name']; ?></a></div>
				<div class="section_market"><?php 
	if(!($as_canorder = mysqli_query($link,"SELECT firstname, lastname FROM users where id = '$agent_cid'"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
	} 
	$c_agent = mysqli_fetch_array($as_canorder);  echo $c_agent['lastname'].", ".$c_agent['firstname'];
	?></div>
				<div class="section_dealer">
					<?php 
	if(!($a_canorder = mysqli_query($link,"SELECT firstname, lastname FROM users where id = '$assigned_cid'"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
	} 
	$c_assigned = mysqli_fetch_array($a_canorder);  echo $c_assigned['lastname'].", ".$c_assigned['firstname'];
	?>
				</div>
			  	<div class="clear"></div>
				<?php } ?>
				<hr class="sep" />
				
				<div class="section_footer">
					<? 
	$canceled_prev = $canceled_offset-$totpage;
	if ($canceled_prev >= 0) $canceled_nav.="<a href='".$PHP_SELF."?=canceled_offset".$canceled_prev."&hold_offset=".$hold_offset."'><strong>&lt;&lt;&nbsp;Prev</strong></a> | "; 
	if($canceled_records!="0") {
	$canceled_nav.="Section Page";
	}
	if($canceled_records/$totpage < 10){
		$canceled_crumbmax = $canceled_records/$totpage;
	}else{
		$canceled_crumbmax = 10; 
	}
	$canceled_bottom = 0;
	if(($canceled_offset/$totpage) > $canceled_crumbmax/2) $canceled_bottom = ($canceled_offset/$totpage) - ceil($canceled_crumbmax/2);
	$canceled_top = $canceled_bottom+$canceled_crumbmax;
	if(ceil($canceled_records/$totpage) < ($canceled_offset/$totpage) + $canceled_crumbmax/2)	$canceled_top = ceil($canceled_records/$totpage);
	for($c_can=$canceled_bottom; $c_can<$canceled_top; $c_can++){
		if($canceled_offset/$totpage==$c_can){
			$canceled_nav.=" ".($c_can+1); 
		}else {  
			$canceled_nav.=" <a href='".$PHP_SELF."?canceled_offset=".($c_can)*$totpage."&hold_offset=".$hold_offset."'>".($c_can+1)."</a>"; 
	  	} 
	} 
    $canceled_next = $canceled_offset+$totpage;
	if ($canceled_next < ($canceled_records))	$canceled_nav.=" | <a href='".$PHP_SELF."?canceled_offset=".$canceled_next."&hold_offset=".$hold_offset."'><b>Next&nbsp;&gt;&gt;</b></a>";
	echo $canceled_nav; ?>
				</div>
			</div>
			<div class="clear"></div>
			<div class="clear"></div>
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