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
                if($_SESSION['SESS_LEVEL'] == 'dealer'){
					header("location: dealer_index.php");
					exit();
				}elseif($_SESSION['SESS_LEVEL'] == 'admin'){
					header("location: admin_index.php");
					exit();
				}elseif($_SESSION['SESS_LEVEL'] == 'photographer'){
					header("location: photographer_index.php");
					exit();
				}elseif($_SESSION['SESS_LEVEL'] == 'agent'){
					header("location: order_history.php");
					exit();
				}
            
        } else {
			header("location: index.php?flag=failedauth&ref=staff_index.php");
			exit();
		}
}

if($_SESSION['SESS_LEVEL']!="staff") {
	header("location: index.php?flag=failedauth");
	exit();
}

if ($_GET['task'] == "logout"){ logout(); exit(); }



$fname = $_SESSION['SESS_FIRST_NAME'];
$lname = $_SESSION['SESS_LAST_NAME'];
$mem_id = $_SESSION['SESS_MEMBER_ID'];

$link = mysqli_connect($connection, $sqluser, $sqlpw, $dbname);

if(mysqli_connect_errno()) {
    $msg = "Database connection failed: ";
    $msg .= mysqli_connect_error();
    $msg .= " : " . mysqli_connect_errno();
    exit($msg);
 }

if ($_GET['task'] == "receive_remove"){ 
	$rem_id = $_GET['order_id'];
	if(!($received_remove = mysqli_query($link,"UPDATE orders set file_rec = '0000-00-00 00:00:00', status = 'scheduled' WHERE id = '$rem_id'"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
	}
	header("Location: staff_index.php");
	exit();
}

$p_offset = $_GET['p_offset'];
if($p_offset=="") $p_offset=0; // set default records
$s_offset = $_GET['s_offset'];
if($s_offset=="") $s_offset=0; // set default records
$r_offset = $_GET['r_offset'];
if($r_offset=="") $r_offset=0; // set default records
$totpage = 10; // total records per page

$fifteen_days = strtotime('-15 days');
$three_days = strtotime('-3 days');

if($_POST['Submit2']){
	if(!($rec_sel = mysqli_query($link,"SELECT * FROM orders where status = 'scheduled' ORDER BY ordered ASC"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
	}
	while($rec_orders=mysqli_fetch_array($rec_sel)){
		$id = $rec_orders['id'];
		$date = date("Y-m-d H:i:s");
		$received = $_POST['rec'.$id];
		if($received=="y"){
			if(!($rec_record = mysqli_query($link,"UPDATE orders set file_rec = '$date', status = 'received' WHERE id = '$id'"))){
				printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
			}
		}
	}
	header("Location: staff_index.php");
	exit();
}

if(!($p_count = mysqli_query($link,"SELECT DISTINCT * FROM orders WHERE status = 'pending'"))){
	printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
	exit();
}

$p_records = mysqli_num_rows($p_count);

if(!($s_count = mysqli_query($link,"SELECT DISTINCT * FROM orders WHERE status = 'scheduled'"))){
	printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
	exit();
}

$s_records = mysqli_num_rows($s_count);

if(!($r_count = mysqli_query($link,"SELECT DISTINCT * FROM orders WHERE status = 'received'"))){
	printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
	exit();
}

$r_records = mysqli_num_rows($r_count);

?>

<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title>Staff Dashboard | Look2 Home Marketing</title>
		<link href="css/l2_v3.css" rel="stylesheet" type="text/css" />
		<script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>
		<script type="text/javascript">
			$(document).ready(function() {
  				$("#keywords").keyup(function() {
    				var kw = $("#keywords").val();
					if(kw != '') {
						$.ajax ({
	     					type: "POST",
							url: "search.php",
							data: "kw="+ kw,
		 					success: function(option) {
		   						$("#results").html(option);
		 					}
	  					});
	 				} else {
	   					$("#results").html("");
	 				}
					return false;
  				});
  				$(".overlay").click(function() {
     				$(".overlay").css('display','none');
	 				$("#results").css('display','none');
	 				document.getElementById("keywords").value = "";
   				});
   				$("#keywords").focus(function() {
     				$(".overlay").css('display','block');
     				$("#results").css('display','block');
   				});
			});
			
			$(document).ready(function() {
  				$("#keywords2").keyup(function() {
    				var kw2 = $("#keywords2").val();
					if(kw2 != '') {
						$.ajax ({
	     					type: "POST",
							url: "search.php",
							data: "kw2="+ kw2,
		 					success: function(option) {
		   						$("#results2").html(option);
		 					}
	  					});
	 				} else {
	   					$("#results2").html("");
	 				}
					return false;
  				});
  				$(".overlay2").click(function() {
     				$(".overlay2").css('display','none');
	 				$("#results2").css('display','none');
	 				document.getElementById("keywords2").value = "";
   				});
   				$("#keywords2").focus(function() {
     				$(".overlay2").css('display','block');
     				$("#results2").css('display','block');
   				});
			});
			
			$(document).ready(function() {
  				$("#keywords3").keyup(function() {
    				var kw3 = $("#keywords3").val();
					if(kw3 != '') {
						$.ajax ({
	     					type: "POST",
							url: "search.php",
							data: "kw3="+ kw3,
		 					success: function(option) {
		   						$("#results3").html(option);
		 					}
	  					});
	 				} else {
	   					$("#results3").html("");
	 				}
					return false;
  				});
  				$(".overlay3").click(function() {
     				$(".overlay3").css('display','none');
	 				$("#results3").css('display','none');
	 				document.getElementById("keywords3").value = "";
   				});
   				$("#keywords3").focus(function() {
     				$(".overlay3").css('display','block');
     				$("#results3").css('display','block');
   				});
			});
			
			$(document).ready(function() {
  				$("#keywords4").keyup(function() {
    				var kw4 = $("#keywords4").val();
					if(kw4 != '') {
						$.ajax ({
	     					type: "POST",
							url: "search.php",
							data: "kw4="+ kw4,
		 					success: function(option) {
		   						$("#results4").html(option);
		 					}
	  					});
	 				} else {
	   					$("#results4").html("");
	 				}
					return false;
  				});
  				$(".overlay4").click(function() {
     				$(".overlay4").css('display','none');
	 				$("#results4").css('display','none');
	 				document.getElementById("keywords4").value = "";
   				});
   				$("#keywords4").focus(function() {
     				$(".overlay4").css('display','block');
     				$("#results4").css('display','block');
   				});
			});
		</script>
</head>

<body>
	<div id="container">
		<div id="left_column">
			<div id="logo_container">
				<svg xmlns="http://www.w3.org/2000/svg" height="66px" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 900.8 554.7" ><g transform="matrix(1.333333 0 0 -1.333333 0 632.204)"><defs><path id="A" d="M-123.8-61.1h990.1v694.9h-990.1z"/></defs><clipPath id="B"><use xlink:href="#A"/></clipPath><g clip-path="url(#B)"><path d="M16.5 377.59v-142.1h60.7v-16.8H0v158.9h16.5zm456.8-157.5l.4-1.3h0l-.4 1.3zm-183.4-9.6c30.5-46.6 73.4-71.6 128.6-75v-22c-30.8-.3-60.3 8.2-88.3 25.4-28.9 17.7-50.7 41.5-65.3 71.3l25 .3zm53 99.8c-9 9.3-19.8 14-32.4 14s-23.4-4.7-32.4-14-13.5-20.6-13.5-33.8c0-12.3 4.5-23 13.6-31.9 9-8.9 19.8-13.4 32.3-13.4s23.3 4.5 32.3 13.4 13.6 19.6 13.6 31.9c0 13.2-4.5 24.4-13.5 33.8m-75.9 12.2c12.1 11.6 26.5 17.4 43.5 17.4 16.9 0 31.4-5.8 43.5-17.4 12.6-12.2 18.9-27.5 18.9-46.1 0-17.3-6.2-31.8-18.5-43.4-12.1-11.4-26.7-17-43.9-17s-31.8 5.7-43.9 17c-12.3 11.7-18.5 26.1-18.5 43.4 0 18.6 6.3 34 18.9 46.1m-73.7-78c9 8.9 13.6 19.6 13.6 31.9 0 13.2-4.5 24.5-13.5 33.8s-19.8 14-32.4 14-23.4-4.7-32.4-14-13.5-20.6-13.5-33.8c0-12.3 4.5-23 13.6-31.9s19.8-13.4 32.3-13.4 23.3 4.5 32.3 13.4m30.1 32c0-17.3-6.2-31.8-18.5-43.4-12.1-11.4-26.7-17-43.9-17s-31.8 5.7-43.9 17c-12.3 11.6-18.5 26.1-18.5 43.4 0 18.5 6.3 33.9 18.9 46.1 12.1 11.5 26.6 17.3 43.5 17.3s31.4-5.8 43.5-17.4c12.6-12.1 18.9-27.5 18.9-46m305-216H419.6l80.8 88.8c9.9 12.5 12.3 25.2 7.1 38h0c-2.4 6-6 10.7-10.7 14.3-5.6 4.3-12.4 6.5-20.3 6.5-11 0-19.6-3.4-25.7-10.3-6-6.6-9.4-16.1-10.3-28.4h-16.2c.1 15.3 4.4 27.7 12.9 37.2 8.4 9.6 20.2 15.2 35.3 17l-52.3 46.1v-50.8h-15.7v162.8h15.7v-91.5l53.5 46.4h23.1l-63.8-55.7 68.1-61.8c16.8-10.2 25.2-24.6 25.3-43.1 1.3-16-9.5-35.5-32.3-58.5L456 76.39h72.4v-15.9zm-3.3 342.5c-14.7 13.6-31.2 24.1-49.7 31.5-18.4 7.4-36.7 11.1-54.9 11-30.4-.1-58.1-8.5-83.1-24.9-27.7-18.2-47.3-43.8-59-76.6h-26.2c13.8 39.4 36.9 70.5 69.4 93.3 14.8 10.4 30.8 18.4 47.9 23.9 16.8 5.3 33.8 8 51 8 23 0 45.1-4.4 66.5-13.2 21.1-8.6 39.8-20.9 56.1-36.8 16.7-16.3 29.6-35.1 38.7-56.5 9.6-22.6 14.4-46.6 14.4-72.2-.9-56.5-21.4-101.6-61.3-135.2v32.7c23.4 24.4 36.3 58.6 38.7 102.5.5 22.3-3.8 43.5-12.9 63.5-8.3 18.4-20.2 34.7-35.6 49" fill-rule="evenodd"/></g><path d="M536.586 98.993l4.03 2.96 8.938-12.17 14.588 10.714-8.938 12.17 4.03 2.96 21.843-29.741-4.03-2.96-10.418 14.185-14.588-10.714 10.418-14.185-4.03-2.96-21.843 29.741zm41.975 18.136c-1.714-1.68-2.442-3.794-2.112-6.131s1.65-4.684 3.96-7.04 4.63-3.724 6.96-4.101 4.317.31 6.102 2.06c1.714 1.68 2.371 3.724 2.112 6.131-.33 2.337-1.65 4.684-3.96 7.04s-4.63 3.724-6.96 4.101-4.317-.31-6.102-2.06zm-2.73 2.785c2.785 2.73 5.981 4.041 9.372 3.725 3.463-.247 6.698-1.976 9.918-5.262 3.15-3.214 4.884-6.555 5.062-10.021s-1.127-6.565-3.912-9.295c-2.857-2.8-5.981-4.041-9.372-3.725s-6.698 1.976-9.918 5.262c-3.15 3.214-4.884 6.555-5.062 10.021s1.127 6.565 3.912 9.295zm32.726 29.043c-.978 2.063-1.375 4.076-1.249 5.958s.774 3.635 2.062 5.419c1.697 2.351 3.796 3.55 6.354 3.677s5.319-1.003 8.319-3.169l13.541-9.775-2.692-3.73-13.378 9.658c-2.108 1.522-4.022 2.287-5.603 2.318s-2.945-.834-4.057-2.374c-1.405-1.946-1.81-3.874-1.413-5.887s1.829-3.787 4.019-5.368l12.649-9.131-2.692-3.73-13.378 9.658c-2.189 1.58-4.022 2.287-5.603 2.318s-2.945-.834-4.057-2.374c-1.346-1.865-1.81-3.874-1.273-5.864.478-2.072 1.829-3.787 4.019-5.368l12.649-9.131-2.692-3.73-22.378 16.155 2.692 3.73 3.486-2.517c-.793 1.806-1.005 3.562-.915 5.224s.76 3.274 1.931 4.896 2.571 2.707 4.201 3.257c1.711.491 3.49.564 5.463-.12zm31.065 39.875l1.976-.968-9.195-18.769c2.872-1.184 5.423-1.432 7.698-.653 2.185.823 3.915 2.536 5.279 5.32a22.2 22.2 0 0 1 1.705 4.844c.345 1.613.464 3.447.45 5.236l3.861-1.892c-.075-1.745-.374-3.491-.763-5.194s-1.047-3.273-1.839-4.89c-1.936-3.951-4.643-6.522-8.122-7.713s-7.104-.863-11.055 1.073c-4.041 1.98-6.746 4.641-8.071 8.074s-1.04 6.968.763 10.65c1.628 3.323 4.025 5.489 7.104 6.319s6.527.366 10.208-1.438zm-3.191-3.559c-2.245 1.1-4.349 1.351-6.222.71-1.919-.508-3.339-1.816-4.351-3.881-1.1-2.245-1.395-4.439-.798-6.402s1.997-3.651 4.244-4.975l7.127 14.548zm-9.033 31.935l1.708 7.2 26.592 3.353-22.23 15.037 1.708 7.2 35.904-8.517-1.131-4.768-31.525 7.478 22.424-15.083-1.154-4.865-26.81-3.404 31.525-7.478-1.108-4.67-35.904 8.517zm32.755 54.015c-.231-3.693.012-6.213.822-7.666s2.164-2.24 4.16-2.365c1.597-.1 2.926.318 3.992 1.353.967 1.042 1.554 2.408 1.666 4.204.156 2.495-.617 4.547-2.32 6.157s-4.048 2.558-6.942 2.739l-.998.063-.281-4.491zm-1.334 9.101l15.769-.988-.281-4.491-4.192.263c1.634-1.104 2.751-2.477 3.449-4.124.705-1.547.985-3.468.841-5.764-.175-2.795-1.117-5.04-2.82-6.637s-3.849-2.264-6.544-2.095c-3.094.194-5.427 1.342-6.793 3.532-1.466 2.196-2.07 5.34-1.814 9.432l.4 6.387-.399.025c-2.096.131-3.737-.467-4.922-1.796-1.285-1.322-1.903-3.187-2.059-5.682a17.59 17.59 0 0 1 .311-4.629c.305-1.522.717-2.951 1.428-4.398l-4.192.263c-.493 1.734-.892 3.362-1.097 4.878-.199 1.616-.305 3.125-.211 4.622.25 3.992 1.436 6.924 3.657 8.789 2.028 1.977 5.278 2.675 9.469 2.413zm-8.494 29.317c-.278-.512-.453-1.12-.527-1.724s-.144-1.307-.114-2.007c.111-2.598.992-4.462 2.75-5.788s4.182-1.823 7.279-1.69l14.587.624.197-4.596-27.575-1.18-.197 4.596 4.296.184c-1.741.926-2.991 2.074-3.855 3.538s-1.344 3.346-1.438 5.544c-.013.3-.03.699.057 1.003-.017.4.066.804.144 1.307l4.396.188z"/><path d="M635.283 306.664l-.59 4.562 22.513 2.911-13.546 11.961-.744 5.752 14.679-12.924 12.629 16.96.757-5.851-11.593-15.616 13.389 1.731.59-4.562-38.083-4.925zm13.578 52.054l2.131.547 5.194-20.244c2.953.964 5.054 2.432 6.278 4.502 1.127 2.044 1.331 4.471.56 7.474a22.2 22.2 0 0 1-1.849 4.791c-.785 1.45-1.886 2.923-3.058 4.274l4.165 1.069c1.076-1.376 1.983-2.898 2.792-4.445s1.329-3.169 1.777-4.913c1.094-4.262.704-7.975-1.168-11.14s-4.842-5.269-9.104-6.362c-4.359-1.118-8.144-.851-11.381.9s-5.316 4.624-6.335 8.595c-.92 3.584-.503 6.788 1.3 9.419 1.875 2.752 4.726 4.516 8.697 5.535zm-.141-4.682c-2.422-.621-4.185-1.796-5.193-3.5-1.13-1.632-1.361-3.55-.789-5.777.621-2.422 1.821-4.282 3.55-5.387s3.89-1.48 6.458-1.027l-4.026 15.692zm-24.076 11.142l7.405 2.752-3.274 8.811 3.281 1.219 3.274-8.811 14.061 5.225c2.156.801 3.364 1.57 3.717 2.341s.276 2.129-.386 3.91l-1.637 4.406 3.562 1.324 1.637-4.406c1.219-3.281 1.399-5.774.668-7.539-.766-1.671-2.772-3.164-6.052-4.383l-14.061-5.225 1.15-3.093-3.187-1.184-1.15 3.093-7.405-2.752-1.602 4.312zm2.773 14.181l-1.845 4.214 25.283 11.07 1.805-4.122-25.242-11.162zm-9.893-4.332l-1.845 4.214 5.313 2.326 1.805-4.122-5.313-2.326zm4.668 38.661l14.671 7.978 2.15-3.953-14.495-7.882c-2.284-1.242-3.802-2.637-4.467-4.136s-.515-3.24.44-4.997c1.194-2.196 2.805-3.483 4.791-3.997 2.074-.466 4.254-.077 6.626 1.213l13.705 7.452 2.198-4.041-24.247-13.185-2.198 4.041 3.778 2.054c-2.019.154-3.694.723-5.066 1.571-1.419.936-2.583 2.238-3.491 3.907-1.529 2.811-1.797 5.397-.804 7.758.905 2.313 3.07 4.401 6.409 6.217zm-13.322 23.473c-2.66-1.954-4.26-3.998-5.022-6.17-.681-2.113-.222-4.258 1.199-6.192s3.271-2.933 5.551-2.995c2.22.018 4.73.993 7.39 2.947s4.26 3.998 4.941 6.111c.622 2.194.222 4.258-1.199 6.192-1.48 2.015-3.33 3.013-5.551 2.995-2.279.063-4.65-.934-7.309-2.888zm5.9 10.042c3.788 2.782 7.201 4.049 10.297 3.718s5.958-2.199 8.504-5.665c.947-1.289 1.755-2.558 2.342-3.863.668-1.247 1.174-2.612 1.541-3.956l-3.546-2.605c-.205 1.462-.492 2.865-1.019 4.09s-1.136 2.391-1.965 3.52c-1.776 2.418-3.723 3.717-5.82 4.038s-4.446-.536-6.944-2.371l-1.773-1.302c1.822.222 3.5-.035 5.113-.711s2.862-1.869 4.105-3.561c2.013-2.74 2.564-5.686 1.711-8.917s-3-6.05-6.466-8.596-6.798-3.753-10.136-3.599-5.985 1.56-7.998 4.3c-1.184 1.612-1.927 3.299-2.171 4.982s.013 3.36.851 5.092l-3.385-2.486-2.664 3.627 19.423 14.268z"/></g></svg>
			</div>
			<div id="menu_column">
				<a class="anchor_enclose_white" href="enter_order.php">
					<div class="menu_button">
						Create New Order
					</div>
				</a>
				<ul>
					<li class="active_menu">
						<a href="staff_index.php">
							<svg xmlns="http://www.w3.org/2000/svg" class="menu_icon" viewBox="0 0 612 612" ><path d="M74.165 294.769v9.483V572.86h244.512V367.341h129.282v205.512h87.592v-268.6-9.483L304.858 122.543 74.165 294.769zm176.575 180.93H142.388V367.341h108.358v108.358h-.006zm354.986-215.855l-69.035-45.952V86.66h-80.915v66.486L303.912 39.14 6.191 259.892a15.7 15.7 0 0 0-2.988 21.991c5.245 6.897 15.088 8.245 21.991 2.988l278.84-206.403 282.853 206.464c2.824 2.122 6.129 3.141 9.41 3.141 4.763 0 9.477-2.165 12.563-6.269 5.196-6.928 3.8-16.758-3.134-21.96z"/></svg>Dashboard
						</a>
					</li>
					<li>
						<a href="calendar.php">
							<svg xmlns="http://www.w3.org/2000/svg" class="menu_icon" viewBox="0 0 512 512"><path d="M149.193 103.525c15.994 0 28.964-12.97 28.964-28.972V28.964C178.157 12.97 165.187 0 149.193 0 133.19 0 120.22 12.97 120.22 28.964v45.589c0 16.003 12.97 28.972 28.973 28.972zm213.622 0c15.994 0 28.964-12.97 28.964-28.972V28.964C391.78 12.97 378.81 0 362.815 0c-16.003 0-28.972 12.97-28.972 28.964v45.589c0 16.003 12.97 28.972 28.972 28.972zm72.349-62.238h-17.925v33.266c0 30.017-24.415 54.431-54.423 54.431-30.017 0-54.431-24.414-54.431-54.431V41.287h-104.77v33.266c0 30.017-24.414 54.431-54.422 54.431-30.018 0-54.432-24.414-54.432-54.431V41.287H76.836c-38.528 0-69.763 31.235-69.763 69.763v331.187C7.073 480.765 38.308 512 76.836 512h358.328c38.528 0 69.763-31.235 69.763-69.763V111.05c0-38.528-31.236-69.763-69.763-69.763zm35.818 400.95c0 19.748-16.07 35.818-35.818 35.818H76.836c-19.749 0-35.818-16.07-35.818-35.818V155.138h429.964v287.099zm-287.306-64.666h56.727v56.727h-56.727zm0-87.921h56.727v56.727h-56.727zm-87.911 87.921h56.718v56.727H95.765zm0-87.921h56.718v56.727H95.765zm263.752-87.92h56.718v56.727h-56.718zm-87.92 0h56.735v56.727h-56.735zm0 87.92h56.735v56.727h-56.735zm87.92 87.921h56.718v56.727h-56.718zm0-87.921h56.718v56.727h-56.718zm-87.92 87.921h56.735v56.727h-56.735zM183.676 201.73h56.727v56.727h-56.727zm-87.911 0h56.718v56.727H95.765z"/></svg>Calendar
						</a>
					</li>
					<li>
						<a href="appt_time.php">
							<svg xmlns="http://www.w3.org/2000/svg" class="menu_icon" viewBox="0 0 800 800"><path d="M336 0c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h24v42.4C186.9 126.3 52 273.7 52 452c0 191.9 156.1 348 348 348s348-156.1 348-348c.1-65.7-18.6-130-53.8-185.5l33.9-27.4 15.1 18.7c5.6 6.9 15.6 7.9 22.5 2.4l24.9-20.1c6.9-5.6 7.9-15.6 2.4-22.5L712.4 118c-5.6-6.9-15.6-7.9-22.5-2.4L665 135.7c-6.9 5.6-7.9 15.6-2.4 22.5l15.1 18.7-33.8 27.3c-53.9-53.1-125-88.8-204.1-97.9V64h24c8.8 0 16-7.2 16-16V16c0-8.8-7.2-16-16-16H336zm64 160c161.6 0 292 130.4 292 292S561.6 744 400 744 108 613.6 108 452s130.4-292 292-292zm0 59.5V452l185.9 139.6c52.9-70.4 61.4-164.7 22.1-243.5-39.4-78.8-119.9-128.5-208-128.6h0z"/></svg>Appt Duration
						</a>
					</li>
					<li>
						<a href="companies.php">
							<svg xmlns="http://www.w3.org/2000/svg" class="menu_icon" viewBox="0 0 50 50"><path d="M8 2v4H4v42h11v-9h4v9h11V6h-4V2zm2 8h2v2h-2zm4 0h2v2h-2zm4 0h2v2h-2zm4 0h2v2h-2zm10 4v4h2v2h-2v2h2v2h-2v2h2v2h-2v2h2v2h-2v2h2v2h-2v2h2v2h-2v2h2v2h-2v4h14V14zm-22 1h2v4h-2zm4 0h2v4h-2zm4 0h2v4h-2zm4 0h2v4h-2zm14 3h2v2h-2zm4 0h2v2h-2zm-30 3h2v4h-2zm4 0h2v4h-2zm4 0h2v4h-2zm4 0h2v4h-2zm14 1h2v2h-2zm4 0h2v2h-2zm-4 4h2v2h-2zm4 0h2v2h-2zm-30 1h2v4h-2zm4 0h2v4h-2zm4 0h2v4h-2zm4 0h2v4h-2zm14 3h2v2h-2zm4 0h2v2h-2zm-30 3h2v4h-2zm4 0h2v4h-2zm4 0h2v4h-2zm4 0h2v4h-2zm14 1h2v2h-2zm4 0h2v2h-2zm-4 4h2v2h-2zm4 0h2v2h-2zm-30 1h2v5h-2zm12 0h2v5h-2zm14 3h2v2h-2zm4 0h2v2h-2z"/></svg>Companies
						</a>
					</li>
					<li>
						<a href="agents.php">
							<svg xmlns="http://www.w3.org/2000/svg" class="menu_icon" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 364 364"><defs><path id="A" d="M0 0h364v364H0z"/></defs><clipPath id="B"><use xlink:href="#A"/></clipPath><path d="M278.8 194.8l-92.5 92.5-92.5-92.5c-27 12.7-49.8 42.1-49.8 69.8v200.8c0 11.4 5.8 21.4 14.6 27.4l-20.7 97.5-64.9-13.9c-5.4-1.2-10.7 2.3-11.9 7.7v.1L-76.3 760c-1.2 5.4 2.3 10.7 7.7 11.9l32.3 6.9c.1-2 .2-4 .7-6.1 4.8-22.4 26.8-36.8 49.3-32 6.5 1.4 12.2 4.4 17.1 8.2l30.6-143.6 22.8-107.1h15.9l12.6 268.4c0 19.4 15.7 35.2 35.2 35.2h38.4 38.4c19.4 0 35.2-15.8 35.2-35.2l12.6-268.4h23.1c18.3 0 33-14.8 33-33V264.6c-.1-27.7-22.9-57.1-49.8-69.8h0zM187 152.7c41.6 0 75.3-33.7 75.3-75.3C262.4 35.7 228.7 2 187 2c-41.6 0-75.3 33.7-75.3 75.3s33.7 75.4 75.3 75.4zm-21 31.7v59.2l20.3 27.1 20.3-27.1v-59.2h-20.3H166zM9.3 761.3c-11.1-2.4-22 4.7-24.4 15.8s4.7 22 15.9 24.4c11.1 2.4 22-4.7 24.4-15.8 2.3-11.1-4.8-22-15.9-24.4z" clip-path="url(#B)"/></svg>Agents
						</a>
					</li>
					<li>
						<a href="photographers.php">
							<svg xmlns="http://www.w3.org/2000/svg" class="menu_icon" viewBox="0 0 512 512"><path d="M256 0C114.509 0 0 114.498 0 256c0 141.491 114.498 256 256 256 141.491 0 256-114.498 256-256C512 114.509 397.502 0 256 0zm0 478.609c-122.746 0-222.609-99.862-222.609-222.609S133.254 33.391 256 33.391 478.609 133.254 478.609 256 378.746 478.609 256 478.609zM60.433 299.546l162.176-93.633-125.15-72.255c-37.44 48.407-49.61 109.334-37.026 165.888zm391.134-87.092l-162.176 93.633 125.148 72.255c37.441-48.406 49.612-109.334 37.028-165.888zM195.928 447.143V259.875L70.709 332.17c22.514 54.566 68.448 97.092 125.219 114.973zm33.391 7.424c59.128 7.906 118.14-10.677 162.076-51.031l-162.076-93.575v144.606zm86.753-389.71v187.27l125.219-72.295c-22.514-54.568-68.448-97.094-125.219-114.975zm-33.391-7.424c-59.128-7.906-118.14 10.679-162.076 51.031l162.076 93.575V57.433z"/></svg>Photographers
						</a>
					</li>
					<li>
						<a href="dealers.php">
							<svg xmlns="http://www.w3.org/2000/svg" class="menu_icon" viewBox="0 0 36 36"><circle cx="16.86" cy="9.73" r="6.46"/><path d="M21 28h7v1.4h-7zm-6 2v3a1 1 0 0 0 1 1h17a1 1 0 0 0 1-1V23a1 1 0 0 0-1-1h-7v-1.47a1 1 0 0 0-2 0V22h-2v-3.58a32.12 32.12 0 0 0-5.14-.42 26 26 0 0 0-11 2.39 3.28 3.28 0 0 0-1.88 3V30zm17 2H17v-8h7v.42a1 1 0 0 0 2 0V24h6z"/></svg>Dealers
						</a>
					</li>
				</ul>
			</div>
		</div>
		<div id="right_column">
			<div id="search_row_container">
				<?php if($_SESSION['SESS_LEVEL']=="admin" || $_SESSION['SESS_LEVEL']=="staff" || $_SESSION['SESS_LEVEL']=="dealer") { ?>
					<div id="search_order">
						<span>
							<svg xmlns="http://www.w3.org/2000/svg" class="search_icon" viewBox="0 0 24 24" ><path fill-rule="evenodd" d="M11 5a6 6 0 1 0 0 12 6 6 0 1 0 0-12zm-8 6a8 8 0 1 1 16 0c0 1.849-.627 3.551-1.68 4.906l3.387 3.387a1 1 0 0 1-1.414 1.414l-3.387-3.387C14.551 18.373 12.849 19 11 19a8 8 0 0 1-8-8z" /></svg>
						</span>
						<input class="form-field"  name="keywords" type="text" id="keywords" placeholder="Order #">
						<div class="clear"></div>
						<div id="results"></div>
						<div class="overlay"></div>
					</div>
					<div id="search_address">
						<span>
							<svg xmlns="http://www.w3.org/2000/svg" class="search_icon" viewBox="0 0 24 24" ><path fill-rule="evenodd" d="M11 5a6 6 0 1 0 0 12 6 6 0 1 0 0-12zm-8 6a8 8 0 1 1 16 0c0 1.849-.627 3.551-1.68 4.906l3.387 3.387a1 1 0 0 1-1.414 1.414l-3.387-3.387C14.551 18.373 12.849 19 11 19a8 8 0 0 1-8-8z" /></svg>
						</span>
						<input class="form-field" name="keywords2" type="text" id="keywords2" placeholder="Address">
						<div class="clear"></div>
						<div id="results2"></div>
						<div class="overlay2"></div>
					</div>
					<div id="search_company">
						<span>
							<svg xmlns="http://www.w3.org/2000/svg" class="search_icon" viewBox="0 0 24 24" ><path fill-rule="evenodd" d="M11 5a6 6 0 1 0 0 12 6 6 0 1 0 0-12zm-8 6a8 8 0 1 1 16 0c0 1.849-.627 3.551-1.68 4.906l3.387 3.387a1 1 0 0 1-1.414 1.414l-3.387-3.387C14.551 18.373 12.849 19 11 19a8 8 0 0 1-8-8z" /></svg>
						</span>
						<input class="form-field" name="keywords4" type="text" id="keywords4" placeholder="Companies">
						<div class="clear"></div>
						<div id="results4"></div>
						<div class="overlay4"></div>
					</div>
					<div id="search_agent">
						<span>
							<svg xmlns="http://www.w3.org/2000/svg" class="search_icon" viewBox="0 0 24 24" ><path fill-rule="evenodd" d="M11 5a6 6 0 1 0 0 12 6 6 0 1 0 0-12zm-8 6a8 8 0 1 1 16 0c0 1.849-.627 3.551-1.68 4.906l3.387 3.387a1 1 0 0 1-1.414 1.414l-3.387-3.387C14.551 18.373 12.849 19 11 19a8 8 0 0 1-8-8z" /></svg>
						</span>
						<input class="form-field" name="keywords3" type="text" id="keywords3" placeholder="Agents">
						<div class="clear"></div>
						<div id="results3"></div>
						<div class="overlay3"></div>
					</div>
				<?php } ?>
				<div id="account_container">
					<span class="account_links_container">
						<strong>Welcome, <?php echo $fname."&nbsp;".$lname; ?></strong><br /><a class="account_links" href="account.php"><svg xmlns="http://www.w3.org/2000/svg" class="account_icon" viewBox="0 0 24 24"><path d="M12 8a4 4 0 1 0 4 4 4 4 0 0 0-4-4zm0 6a2 2 0 1 1 2-2 2 2 0 0 1-2 2zm8.99-5h-1.575l-.046-.129 1.11-1.11a2.011 2.011 0 0 0 0-2.842l-1.4-1.4a2 2 0 0 0-1.421-.588h0a2 2 0 0 0-1.419.588L15.07 4.612 15 4.58V3.009A2.011 2.011 0 0 0 12.99 1h-1.98A2.011 2.011 0 0 0 9 3.009v1.557l-.086.049-.043.016-1.106-1.109a2 2 0 0 0-1.42-.589h0a2 2 0 0 0-1.421.588l-1.4 1.4a2.011 2.011 0 0 0 0 2.842l1.1 1.143-.043.093H3.01A2.011 2.011 0 0 0 1 11.009v1.982A2.011 2.011 0 0 0 3.01 15h1.575l.046.129-1.11 1.11a2.011 2.011 0 0 0 0 2.842l1.4 1.4a2.059 2.059 0 0 0 2.842 0l1.115-1.115.121.056v1.571A2.011 2.011 0 0 0 11.01 23h1.98A2.011 2.011 0 0 0 15 20.991v-1.557l.129-.065 1.109 1.109a2.058 2.058 0 0 0 2.843 0l1.4-1.4a2.011 2.011 0 0 0 0-2.842l-1.1-1.143.043-.093h1.566A2.011 2.011 0 0 0 23 12.991v-1.982A2.011 2.011 0 0 0 20.99 9zm0 4h-1.569a2.1 2.1 0 0 0-1.466 3.54l1.109 1.124-1.414 1.4-1.11-1.109A2.1 2.1 0 0 0 13 19.42L12.99 21 11 20.991V19.42a2.043 2.043 0 0 0-1.307-1.881 2.138 2.138 0 0 0-.816-.164 2 2 0 0 0-1.417.58l-1.124 1.109-1.4-1.414 1.108-1.108A2.1 2.1 0 0 0 4.579 13L3 12.991 3.01 11h1.569a2.1 2.1 0 0 0 1.466-3.54L4.936 6.336l1.414-1.4 1.11 1.109a2.04 2.04 0 0 0 2.227.419l.018-.007A2.04 2.04 0 0 0 11 4.58L11.01 3l1.99.009V4.58a2 2 0 0 0 1.227 1.845l.087.039a2.038 2.038 0 0 0 2.226-.419l1.124-1.109 1.4 1.414-1.108 1.108A2.1 2.1 0 0 0 19.421 11h1.569l.01.009z"/></svg>Account</a><a class="account_signout" href="<?php echo $_SERVER["PHP_SELF"]; ?>?task=logout"><svg xmlns="http://www.w3.org/2000/svg" class="account_icon" fill="none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" ><path d="M6 15l-3-3 3-3m-3 3h14m-7-4V5a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-9a1 1 0 0 1-1-1v-3"/></svg>Sign Out</a>
					</span>
					<span class="account_pic">
						<?php if(file_exists("floorplan/profile/".$mem_id.".jpg")){ ?><img src="floorplan/profile/<?php echo $mem_id; ?>.jpg" width="60px" alt="<?php echo $fname." ". $lname; ?>"><?php } ?>
					</span>
				</div>
				<div class="clear"></div>
				<hr class="hr_top" />
			</div>
			<div id="rc_content_container">
				<div class="section_container">
					<div class="section_header">
						<svg xmlns="http://www.w3.org/2000/svg" class="section_icon" viewBox="0 0 800 800"><path d="M220 360c121.5 0 220 98.5 220 220s-98.5 220-220 220S0 701.5 0 580s98.5-220 220-220zm0 315.2c-13.8 0-25 11.2-25 25s11.2 25 25 25 25-11.2 25-25-11.2-25-25-25zM670 0c71.8 0 130 58.2 130 130v460c0 71.8-58.2 130-130 130H439.1C465 679.6 480 631.6 480 580c0-60.6-20.7-116.4-55.6-160.5l2.7.4 2.8.1h179.9l4.1-.3c14.6-2 25.9-14.5 25.9-29.7s-11.3-27.7-25.9-29.7l-4.1-.3H460V150l-.3-4.1c-2-14.6-14.5-25.9-29.7-25.9s-27.7 11.3-29.7 25.9l-.3 4.1v240l.2 2.6C353.5 347.6 290 320 220 320c-51.6 0-99.6 15-140 40.9V130C80 58.2 138.2 0 210 0h460zM220 440.1c-41.9 0-74.6 32.7-74.1 78.2.1 11 9.1 19.9 20.2 19.8 11-.1 19.9-9.1 19.8-20.2-.2-23.2 14.4-37.8 34.1-37.8 18.9 0 34.1 15.7 34.1 38 0 9-3 15.6-12.7 27.1l-4 4.5-10.6 11.6C207.5 583 200 597 200 620c0 11 9 20 20 20s20-9 20-20c0-9.3 3.1-16.1 12.9-27.8l4-4.6 10.6-11.6c19.1-21.4 26.5-35.3 26.5-57.9.1-44.1-32.8-78-74-78z"/></svg><h1>Pending</h1><?php if($p_records!="0") { ?><span class="count_span"><?php echo $p_records; ?></span><?php } ?>
					</div>
					<div class="section_titles">
						<div class="section_date">
							<strong>Ordered</strong>
						</div>
						<div class="section_address">
							<strong>Address</strong>
						</div>
						<div class="section_city">
							<strong>City</strong>
						</div>
						<div class="section_state">
							<strong>State</strong>
						</div>
						<div class="section_photographer">
							<strong>Agent</strong>
						</div>
						<div class="section_location">
							<strong>Location/Dealer</strong>
						</div>
						<div class="section_receive">
							<strong>&nbsp;</strong>
						</div>
						<div class="clear"></div>
					</div>
					<?php
						if(!($p_order = mysqli_query($link,"SELECT * FROM orders where orders.status = 'pending' ORDER BY ordered ASC LIMIT $p_offset, $totpage"))){
							printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
							exit();
						}
						while($pend_order = mysqli_fetch_array($p_order)){
							$agent_pid = $pend_order['agent_id'];
							$assigned_pid = $pend_order['assigned'];
							$order_date = date("m/d/y", strtotime($pend_order['ordered']));
							$orig_pend = strtotime($order_date);
					?>
					<div class="section_result_row">
							<a href="schedule.php?order_id=<?php echo $pend_order['id']; ?>">
								<div class="section_date">
									<?php echo $order_date; ?>
								</div>
								<div class="section_address">
									<?php if($pend_order['rush']=="y") { ?><span class="highlight_red">**RUSH**&nbsp;-&nbsp;</span><?php } if($pend_order['street_num']!="0") { echo $pend_order['street_num']; } ?> <?php if($pend_order['street_dir']!="") { echo $pend_order['street_dir']; } ?> <?php echo $pend_order['street_name']; ?>
								</div>
								<div class="section_city">
									<?php echo $pend_order['city']; ?>
								</div>
								<div class="section_state">
									<?php echo $pend_order['state']; ?>
								</div>
								<div class="section_photographer">
									<?php 
										if($agent_pid!="0") {
											if(!($as_pendorder = mysqli_query($link,"SELECT firstname, lastname FROM users where id = '$agent_pid'"))){
												printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
												exit();
											} 
											$p_agent = mysqli_fetch_array($as_pendorder);  echo $p_agent['lastname'].", ".$p_agent['firstname'];
										} else {
											echo "&nbsp;";
										}
									?>
								</div>
								<div class="section_location">
									<?php 
										if(!($a_pendorder = mysqli_query($link,"SELECT firstname, lastname FROM users where id = '$assigned_pid'"))){
											printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
											exit();
										} 
										$p_assigned = mysqli_fetch_array($a_pendorder);  echo $p_assigned['lastname'].", ".$p_assigned['firstname'];
									?>
								</div>
							</a>
							<div class="section_receive">
								&nbsp;
							</div>
							<div class="clear"></div>
						</div>
					<?php } if($p_records=="0") { ?>
						<div class="section_result_row">
							<span class="no_results">No pending orders were found</span>
						</div>
					<?php } ?>
					<div class="section_footer">
						<div class="section_navigate">
							<?php
								$p_prev = $p_offset-$totpage;
								if ($p_prev >= 0) $p_nav.="<a href='".$PHP_SELF."?p_offset=".$p_prev."&s_offset=".$s_offset."&r_offset=".$r_offset."'><strong>&lt;&lt;&nbsp;Prev</strong></a> | "; 
								if($p_records!="0") {
									$p_nav.="Section Page:";
								}
								if($p_records/$totpage < 10){
									$p_crumbmax = $p_records/$totpage;
								}else{
									$p_crumbmax = 10; 
								}
								$p_bottom = 0;
								if(($p_offset/$totpage) > $p_crumbmax/2) $p_bottom = ($p_offset/$totpage) - ceil($p_crumbmax/2);
								$p_top = $p_bottom+$p_crumbmax;
								if(ceil($p_records/$totpage) < ($p_offset/$totpage) + $p_crumbmax/2)	$p_top = ceil($p_records/$totpage);
								for($p=$p_bottom; $p<$p_top; $p++){
									if($p_offset/$totpage==$p) {
										$p_nav.=" ".($p+1); 
									} else {  
										$p_nav.=" <a href='".$PHP_SELF."?p_offset=".($p)*$totpage."&s_offset=".$s_offset."&r_offset=".$r_offset."'>".($p+1)."</a>"; 
	  								} 
								} 
    							$p_next = $p_offset+$totpage;
								if ($p_next < ($p_records))	$p_nav.=" | <a href='".$PHP_SELF."?p_offset=".$p_next."&s_offset=".$s_offset."&r_offset=".$r_offset."'><b>Next&nbsp;&gt;&gt;</b></a>";
								echo $p_nav;
							?>
						</div>
					</div>
				</div>
				<div class="section_container">
					<div class="section_header">
						<svg xmlns="http://www.w3.org/2000/svg" class="section_icon" viewBox="0 0 800 800"><path d="M336.8 673L181 517.2l59.5-59.5 96.5 96.5 222.6-222.6 59.5 59.5L336.8 673zm441.8-504.7v546.8c0 46.4-37.7 84.1-84.1 84.1h-589c-46.4 0-84.1-37.7-84.1-84.1V168.3c0-46.4 37.7-84.1 84.1-84.1h42.1V0h84.1v84.1h336.5V0h84.1v84.1h42.1c46.5 0 84.2 37.7 84.2 84.2zm-84.1 84.1h-589v462.7h588.9V252.4z"/></svg><h1>Scheduled</h1><?php if($s_records!="0") { ?><span class="count_span"><?php echo $s_records; ?></span><?php } ?>
					</div>
					<div class="section_titles">
						<div class="section_date">
							<strong>Scheduled</strong>
						</div>
						<div class="section_address">
							<strong>Address</strong>
						</div>
						<div class="section_city">
							<strong>City</strong>
						</div>
						<div class="section_state">
							<strong>State</strong>
						</div>
						<div class="section_photographer">
							<strong>Photographer</strong>
						</div>
						<div class="section_location">
							<strong>Location/Dealer</strong>
						</div>
						<div class="section_receive">
							<strong>Receive</strong>
						</div>
						<div class="clear"></div>
					</div>
					<form action="staff_index.php" method="post" enctype="multipart/form-data">
						<?php if(!($s_order = mysqli_query($link,"SELECT * FROM orders where orders.status = 'scheduled' ORDER BY scheduled ASC LIMIT $s_offset, $totpage"))) {
							printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
							exit();
						}
						while($sched_order = mysqli_fetch_array($s_order)){
							$agent_sid = $sched_order['agent_id'];
							$assigned_sid = $sched_order['assigned'];
							$photographer_sid = $sched_order['p_assist'];
							$sched_date = date("m/d/y", strtotime($sched_order['scheduled']));
							$orig_sched = strtotime($sched_date);
						?>
							<div class="section_result_row">
								<a href="schedule.php?order_id=<?php echo $sched_order['id']; ?>">
									<div class="section_date">
										<?php echo $sched_date; ?>
									</div>
									<div class="section_address">
										<?php if($sched_order['rush']=="y") { ?><span class="highlight_red">**RUSH**&nbsp;-&nbsp;</span><?php } ?><?php if($sched_order['street_num']!="0") { echo $sched_order['street_num']; } ?> <?php if($sched_order['street_dir']!="") { echo $sched_order['street_dir']; } ?> <?php echo $sched_order['street_name']; ?>
									</div>
									<div class="section_city">
										<?php echo $sched_order['city']; ?>
									</div>
									<div class="section_state">
										<?php echo $sched_order['state']; ?>
									</div>
									<div class="section_photographer">
										<?php 
											if($photographer_sid!="0") {
												if(!($p_schedorder = mysqli_query($link,"SELECT firstname, lastname FROM users where id = '$photographer_sid'"))){
													printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
													exit();
												} 
												$s_photographer = mysqli_fetch_array($p_schedorder);  
												echo $s_photographer['lastname'].", ".$s_photographer['firstname'];
											} else {
												echo "&nbsp;";
											}
										?>
									</div>
									<div class="section_location">
										<?php if(!($a_schedorder = mysqli_query($link,"SELECT firstname, lastname FROM users where id = '$assigned_sid'"))){
											printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
											exit();
										} 
										$s_assigned = mysqli_fetch_array($a_schedorder);  
										echo $s_assigned['lastname'].", ".$s_assigned['firstname'];?>
									</div>
								</a>
								<div class="section_receive">
									<div class="receive"><input type="checkbox" name="rec<?php echo $sched_order['id']; ?>" id="rec<?php echo $sched_order['id']; ?>" value="y" /><label for="rec<?php echo $sched_order['id']; ?>"></label></div>
								</div>
								<div class="clear"></div>
							</div>
						<?php } if($s_records=="0") { ?>
							<div class="section_result_row">
								<span class="no_results">No scheduled orders were found</span>
							</div>
						<?php } ?>
						<div class="section_footer">
							<div class="section_navigate">
								<?php
									$s_prev = $s_offset-$totpage;
									if ($s_prev >= 0) $s_nav.="<a href='".$PHP_SELF."?s_offset=".$s_prev."&p_offset=".$p_offset."&r_offset=".$r_offset."'><strong>&lt;&lt;&nbsp;Prev</strong></a>| "; 
									if($s_records!="0") {
										$s_nav.="Section Page:";
									}
									if($s_records/$totpage < 10){
										$s_crumbmax = $s_records/$totpage;
									}else{
										$s_crumbmax = 10; 
									}
									$s_bottom = 0;
									if(($s_offset/$totpage) > $s_crumbmax/2) $s_bottom = ($s_offset/$totpage) - ceil($s_crumbmax/2);
									$s_top = $s_bottom+$s_crumbmax;
									if(ceil($s_records/$totpage) < ($s_offset/$totpage) + $s_crumbmax/2)	$s_top = ceil($s_records/$totpage);
									for($s=$s_bottom; $s<$s_top; $s++){
										if($s_offset/$totpage==$s){
											$s_nav.=" ".($s+1); 
										}else {  
											$s_nav.=" <a href='".$PHP_SELF."?s_offset=".($s)*$totpage."&p_offset=".$p_offset."&r_offset=".$r_offset."'>".($s+1)."</a>"; 
	  									} 
									} 
    								$s_next = $s_offset+$totpage;
									if ($s_next < ($s_records))	$s_nav.=" | <a href='".$PHP_SELF."?s_offset=".$s_next."&p_offset=".$p_offset."&r_offset=".$r_offset."'><b>Next&nbsp;&gt;&gt;</b></a>";
									echo $s_nav; ?>
							</div>
							<div class="section_submit">
								<input class="section_submit_button" name="Submit2" type="submit" id="Submit2" value="Receive" />
							</div>
						</div>
					</form>
				</div>
				<div class="section_container">
					<div class="section_header">
						<svg xmlns="http://www.w3.org/2000/svg" class="section_icon" viewBox="0 0 32 32"><path d="M29 8h-8.9l-2.3-3.5c-.1-.3-.5-.5-.8-.5H7C5.3 4 4 5.3 4 7v7.8a10.57 10.57 0 0 1 4-.8c5.5 0 10 4.5 10 10a10.57 10.57 0 0 1-.8 4H29c1.7 0 3-1.3 3-3V11c0-1.7-1.3-3-3-3zm1 15.6L21.4 10H29a.94.94 0 0 1 1 1v12.6zM8 16c-4.4 0-8 3.6-8 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm3.7 8.8c-.2.2-.4.3-.7.3s-.5-.1-.7-.3L9 23.5V28a.94.94 0 0 1-1 1 .94.94 0 0 1-1-1v-4.5l-1.3 1.3c-.4.4-1 .4-1.4 0s-.4-1 0-1.4l3-3.1c.2-.2.4-.3.7-.3s.5.1.7.3l3 3.1c.4.4.4 1 0 1.4z"/></svg><h1>Recieved</h1><?php if($r_records!="0") { ?><span class="count_span"><?php echo $r_records; ?></span><?php } ?>
					</div>
					<div class="section_titles">
						<div class="section_date">
							<strong>Ordered</strong>
						</div>
						<div class="section_address">
							<strong>Address</strong>
						</div>
						<div class="section_city">
							<strong>City</strong>
						</div>
						<div class="section_state">
							<strong>State</strong>
						</div>
						<div class="section_photographer">
							<strong>Agent</strong>
						</div>
						<div class="section_location">
							<strong>Location/Dealer</strong>
						</div>
						<div class="section_receive">
							<strong>Action</strong>
						</div>
						<div class="clear"></div>
					</div>
					<?php if(!($r_order = mysqli_query($link,"SELECT * FROM orders where orders.status = 'received' ORDER BY ordered ASC LIMIT $r_offset, $totpage"))){
						printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
						exit();
					}
					while($received_order = mysqli_fetch_array($r_order)){
						$agent_rid = $received_order['agent_id'];
						$assigned_rid = $received_order['assigned'];
						$received_date = date("m/d/y", strtotime($received_order['file_rec']));
						$orig_rec = strtotime($received_date);
					?>
						<div class="section_result_row">
							<a href="fplan.php?order_id=<?php echo $received_order['id']; ?>">
								<div class="section_date">
									<?php echo $received_date; ?>
								</div>
								<div class="section_address">
									<?php if($received_order['rush']=="y") { ?><span class="highlight_red">**RUSH**&nbsp;-&nbsp;</span><?php } ?><?php if($received_order['street_num']!="0") { echo $received_order['street_num']; } ?> <?php if($received_order['street_dir']!="") { echo $received_order['street_dir']; } ?> <?php echo $received_order['street_name']; ?>
								</div>
								<div class="section_city">
									<?php echo $received_order['city']; ?>
								</div>
								<div class="section_state">
									<?php echo $received_order['state']; ?>
								</div>
								<div class="section_photographer">
									<?php 
										if($agent_rid!="0") {
											if(!($as_recorder = mysqli_query($link,"SELECT firstname, lastname FROM users where id = '$agent_rid'"))){
												printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
												exit();
											} 
										$r_agent = mysqli_fetch_array($as_recorder);  echo $r_agent['lastname'].", ".$r_agent['firstname'];
										} else {
											echo "&nbsp;";
										}
									?>
								</div>
								<div class="section_location">
									<?php 
										if(!($a_recorder = mysqli_query($link,"SELECT firstname, lastname FROM users where id = '$assigned_rid'"))){
											printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
											exit();
										} 
										$r_assigned = mysqli_fetch_array($a_recorder);  echo $r_assigned['lastname'].", ".$r_assigned['firstname'];
									?>
								</div>
							</a>
							<div class="section_receive">
								<a class="remove_rec" href="staff_index.php?order_id=<?php echo $received_order['id']; ?>&task=receive_remove"><svg xmlns="http://www.w3.org/2000/svg" class="section_icon" viewBox="0 0 800 653.3" ><path d="M398.6 314.9L584.1 131c6.1-6.1 16-6 22.1.1L790 316.7c6.1 6.1 6 16-.1 22.1l-44.4 44.1c-6.1 6.1-16 6-22.1-.1l-71.7-72.3c4.3 89.1-28.1 179.5-97 246.9C427 682 224 680.1 98.6 553.1-26.6 426.2-25.5 221.9 101.2 96.4 160.5 37.6 236.9 6.5 314.3 3c9-.4 16.5 6.7 16.4 15.7l-.3 62.6c0 8.2-6.5 15.1-14.7 15.5-53.9 3.1-107 25.1-148.5 66.2-90.1 89.3-90.7 234.4-1.4 324.5s234.4 90.7 324.5 1.4c54.2-53.7 76-127.6 65.4-197.5l-91 90.2c-6.1 6.1-16 6-22.1-.1l-44.1-44.4c-6.1-6.2-6.1-16.1.1-22.2h0z"/></svg>Remove</a>
							</div>
							<div class="clear"></div>
						</div>
					<?php } if($r_records=="0") { ?>
						<div class="section_result_row">
							<span class="no_results">No received orders were found</span>
						</div>
					<?php } ?>
					<div class="section_footer">
						<div class="section_navigate">
							<?php
								$r_prev = $r_offset-$totpage;
								if ($r_prev >= 0) $r_nav.="<a href='".$PHP_SELF."?r_offset=".$r_prev."&p_offset=".$p_offset."&s_offset=".$s_offset."'><strong>&lt;&lt;&nbsp;Prev</strong></a> | "; 
								if($r_records!="0") {
									$r_nav.="Section Page:";
								}
								if($r_records/$totpage < 10){
									$r_crumbmax = $r_records/$totpage;
								}else{
									$r_crumbmax = 10; 
								}
								$r_bottom = 0;
								if(($r_offset/$totpage) > $r_crumbmax/2) $r_bottom = ($r_offset/$totpage) - ceil($r_crumbmax/2);
								$r_top = $r_bottom+$r_crumbmax;
								if(ceil($r_records/$totpage) < ($r_offset/$totpage) + $r_crumbmax/2)	$r_top = ceil($r_records/$totpage);
								for($r=$r_bottom; $r<$r_top; $r++){
									if($r_offset/$totpage==$r){
										$r_nav.=" ".($r+1); 
									}else {  
										$r_nav.=" <a href='".$PHP_SELF."?r_offset=".($r)*$totpage."&p_offset=".$p_offset."&s_offset=".$s_offset."'>".($r+1)."</a>"; 
	  								} 
								} 
    							$r_next = $r_offset+$totpage;
								if ($r_next < ($r_records))	$r_nav.=" | <a href='".$PHP_SELF."?r_offset=".$r_next."&p_offset=".$p_offset."&s_offset=".$s_offset."'><b>Next&nbsp;&gt;&gt;</b></a>";
								echo $r_nav; 
							?>
						</div>
						<div class="section_submit">
							&nbsp;
						</div>
					</div>
				</div>
			</div>
			<div id="rc_footer">
				<hr class="hr_top" />
				<div id="rc_footer_content">
					&copy;2023 Look2 Home Marketing. All Rights Reserved<br>
					<a href="terms.php">Terms/Privacy Policy</a>
				</div>
			</div>
		</div>
		<div class="clear"
	</div>
	<script type="text/javascript">
		window.onload = function() {
    		function unifyHeights() {
        	var maxHeight = 0;
				$('#container').children('#left_column, #right_column').each(function() {
            		var height = $(this).outerHeight();
            		// alert(height);
            		if ( height > maxHeight ) {
                		maxHeight = height;
            		}
        		});
        		$('#left_column, #right_column').css('height', maxHeight);
    		}
    		unifyHeights();
		};	
	</script>
</body>
</html>