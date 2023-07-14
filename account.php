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

$link = mysqli_connect($connection, $sqluser, $sqlpw, $dbname);

if(mysqli_connect_errno()) {
    $msg = "Database connection failed: ";
    $msg .= mysqli_connect_error();
    $msg .= " : " . mysqli_connect_errno();
    exit($msg);
 }

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
		if ($_SESSION['SESS_LEVEL']!="dealer"){
			if ($_SESSION['SESS_LEVEL']!="d_assist"){
				if ($_SESSION['SESS_LEVEL']!="agent"){
					header("location: index.php?flag=failedauth");
					exit();
				}
			}
		}
	}
}

$login_url = 'https://accounts.google.com/o/oauth2/auth?scope=' . urlencode('https://www.googleapis.com/auth/calendar') . '&redirect_uri=' . urlencode(CLIENT_REDIRECT_URL) . '&response_type=code&client_id=' . CLIENT_ID . '&access_type=online';

if ($_GET['task'] == "logout"){ logout(); exit(); }

$fname = $_SESSION['SESS_FIRST_NAME'];
$lname = $_SESSION['SESS_LAST_NAME'];
$mem_id = $_SESSION['SESS_MEMBER_ID'];

if(!($prof_db = mysqli_query($link,"select * from users where id = '$mem_id'"))){
	printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
}
$profile = mysqli_fetch_array($prof_db);

if ($_SESSION['SESS_LEVEL']=="agent") {
	$c4 = $profile['c4'];
}

if($_POST['Submitact']){
	if($_POST['pass']!="") {
		$passencrypt = SHA1($_POST['pass']);
	} else {
		$passencrypt = $profile['pass'];
	}
	
	$salt = 'L2HMS@lt!';

$salted_password = $mem_id.$passencrypt.$salt;
$hash = md5($salted_password);

	
	$password = $_POST['password']; 
	$firstname = $_POST['fname']; 
	$lastname = $_POST['lname']; 
	$address = $_POST['address']; 
	$city = $_POST['city']; 
	$state = $_POST['state']; 
	$zip = $_POST['zip']; 
	$officephone = $_POST['officephone']; 
	$cellphone = $_POST['cell']; 
	$email = $_POST['email']; 
	$site = $_POST['site']; 
	
	if($site!="" && (substr($site,0,7)!="http://")) $site = "http://".$site;  
	
	if(!($acount_update = mysqli_query($link,"UPDATE users SET email='$email',pass='$passencrypt',firstname='$firstname',lastname='$lastname',site='$site',address='$address',city='$city',state='$state',zip='$zip',office_phone='$officephone',cell_phone='$cellphone' WHERE id = '$mem_id'"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
	}

	if($_FILES['uphoto']['name'] != NULL){ 
		$file_name = $_FILES['uphoto']['name'];
      	$file_size = $_FILES['uphoto']['size'];
      	$file_tmp = $_FILES['uphoto']['tmp_name'];
      	$file_type = $_FILES['uphoto']['type'];
      	$file_ext=strtolower(end(explode('.',$_FILES['uphoto']['name'])));
      	$expensions= array("jpg");
      	if(in_array($file_ext,$expensions)=== false){
          	header("Location: account.php?flag=notvalid");
		  	exit;
      	}
		$verifyimg = getimagesize($_FILES['uphoto']['tmp_name']);

		if($verifyimg['mime'] != 'image/jpeg') {
    		header("Location: account.php?flag=notvalid");
			exit; 
		}
		$extension = strrchr($_FILES['uphoto']['name'],'.');  
		$save_path = 'floorplan/profile/'; 
		if(!is_dir($save_path)) mkdir($save_path);
		$filename = $save_path . $mem_id . $extension; 
		move_uploaded_file($_FILES['uphoto']['tmp_name'],$filename);
	} 
	
	header("Location: account.php?flag=updated");
	exit();
}

// Google passes a parameter 'code' in the Redirect Url
if(isset($_GET['code'])) {
	try {
		$capi = new GoogleCalendarApi();
		
		// Get the access token 
		$data = $capi->GetAccessToken(CLIENT_ID, CLIENT_REDIRECT_URL, CLIENT_SECRET, $_GET['code']);
		
		// Save the access token as a session variable
		$g_token = $data['access_token'];

		if(!($acount_update = mysqli_query($link,"UPDATE users SET g_token='$g_token' WHERE id = '$mem_id'"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
	}
		
		// Redirect to the page where user can create event
		header('Location: account.php?flag=cal_linked');
		exit();
	}
	catch(Exception $e) {
		echo $e->getMessage();
		exit();
	}
}

if ($_GET['task'] == "rem_cal"){
	if(!($dealer_rem = mysqli_query($link,"UPDATE users SET g_token='' WHERE id = '$mem_id'"))){
				printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
				exit();
			}
	header("Location: account.php?flag=cal_removed");
	exit();
}

?>

<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title>Manage Agent | Look2 Home Marketing</title>
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
				<?php if ($_SESSION['SESS_LEVEL']=="d_assist") { ?>
					<div class="menu_button_nodsp">&nbsp;</div>
				<?php } else { ?>
				<a class="anchor_enclose_white" href="enter_order.php">
					<div class="menu_button">
						Create New Order
					</div>
				</a>
				<?php } ?>
				<ul>
					<li>
						<?php if ($_SESSION['SESS_LEVEL']=="admin") { ?>
							<a href="admin_index.php">
						<?php } if ($_SESSION['SESS_LEVEL']=="staff") { ?>
							<a href="staff_index.php">
						<?php } if ($_SESSION['SESS_LEVEL']=="dealer") { ?>
							<a href="dealer_index.php">
						<?php } if ($_SESSION['SESS_LEVEL']=="d_assist") { ?>
							<a href="photographer_index.php">
						<?php } if ($_SESSION['SESS_LEVEL']=="agent") { ?>
							<a href="order_history.php">
						<?php } if ($_SESSION['SESS_LEVEL']=="admin" || $_SESSION['SESS_LEVEL']=="staff" || $_SESSION['SESS_LEVEL']=="dealer" || $_SESSION['SESS_LEVEL']=="d_assist" || $_SESSION['SESS_LEVEL']=="agent") { ?> 
							<svg xmlns="http://www.w3.org/2000/svg" class="menu_icon" viewBox="0 0 612 612" ><path d="M74.165 294.769v9.483V572.86h244.512V367.341h129.282v205.512h87.592v-268.6-9.483L304.858 122.543 74.165 294.769zm176.575 180.93H142.388V367.341h108.358v108.358h-.006zm354.986-215.855l-69.035-45.952V86.66h-80.915v66.486L303.912 39.14 6.191 259.892a15.7 15.7 0 0 0-2.988 21.991c5.245 6.897 15.088 8.245 21.991 2.988l278.84-206.403 282.853 206.464c2.824 2.122 6.129 3.141 9.41 3.141 4.763 0 9.477-2.165 12.563-6.269 5.196-6.928 3.8-16.758-3.134-21.96z"/></svg>Dashboard
						</a>
					</li>
					<?php } if ($_SESSION['SESS_LEVEL']=="admin" || $_SESSION['SESS_LEVEL']=="staff" || $_SESSION['SESS_LEVEL']=="dealer" || $_SESSION['SESS_LEVEL']=="d_assist") { ?>
					<li>
						<a href="calendar.php">
							<svg xmlns="http://www.w3.org/2000/svg" class="menu_icon" viewBox="0 0 512 512"><path d="M149.193 103.525c15.994 0 28.964-12.97 28.964-28.972V28.964C178.157 12.97 165.187 0 149.193 0 133.19 0 120.22 12.97 120.22 28.964v45.589c0 16.003 12.97 28.972 28.973 28.972zm213.622 0c15.994 0 28.964-12.97 28.964-28.972V28.964C391.78 12.97 378.81 0 362.815 0c-16.003 0-28.972 12.97-28.972 28.964v45.589c0 16.003 12.97 28.972 28.972 28.972zm72.349-62.238h-17.925v33.266c0 30.017-24.415 54.431-54.423 54.431-30.017 0-54.431-24.414-54.431-54.431V41.287h-104.77v33.266c0 30.017-24.414 54.431-54.422 54.431-30.018 0-54.432-24.414-54.432-54.431V41.287H76.836c-38.528 0-69.763 31.235-69.763 69.763v331.187C7.073 480.765 38.308 512 76.836 512h358.328c38.528 0 69.763-31.235 69.763-69.763V111.05c0-38.528-31.236-69.763-69.763-69.763zm35.818 400.95c0 19.748-16.07 35.818-35.818 35.818H76.836c-19.749 0-35.818-16.07-35.818-35.818V155.138h429.964v287.099zm-287.306-64.666h56.727v56.727h-56.727zm0-87.921h56.727v56.727h-56.727zm-87.911 87.921h56.718v56.727H95.765zm0-87.921h56.718v56.727H95.765zm263.752-87.92h56.718v56.727h-56.718zm-87.92 0h56.735v56.727h-56.735zm0 87.92h56.735v56.727h-56.735zm87.92 87.921h56.718v56.727h-56.718zm0-87.921h56.718v56.727h-56.718zm-87.92 87.921h56.735v56.727h-56.735zM183.676 201.73h56.727v56.727h-56.727zm-87.911 0h56.718v56.727H95.765z"/></svg>Calendar
						</a>
					</li>
					<?php } if ($_SESSION['SESS_LEVEL']=="admin" || $_SESSION['SESS_LEVEL']=="staff" || $_SESSION['SESS_LEVEL']=="dealer") { ?>
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
					<?php } if ($_SESSION['SESS_LEVEL']=="admin" || $_SESSION['SESS_LEVEL']=="staff") { ?>
						<li>
							<a href="dealers.php">
								<svg xmlns="http://www.w3.org/2000/svg" class="menu_icon" viewBox="0 0 36 36"><circle cx="16.86" cy="9.73" r="6.46"/><path d="M21 28h7v1.4h-7zm-6 2v3a1 1 0 0 0 1 1h17a1 1 0 0 0 1-1V23a1 1 0 0 0-1-1h-7v-1.47a1 1 0 0 0-2 0V22h-2v-3.58a32.12 32.12 0 0 0-5.14-.42 26 26 0 0 0-11 2.39 3.28 3.28 0 0 0-1.88 3V30zm17 2H17v-8h7v.42a1 1 0 0 0 2 0V24h6z"/></svg>Dealers
							</a>
						</li>
					<?php } if ($_SESSION['SESS_LEVEL']=="admin") { ?>
						<li>
							<a href="staff.php">
								<svg xmlns="http://www.w3.org/2000/svg" class="menu_icon" viewBox="0 0 477.655 477.655"><path d="M440.367 440.415l-10.173-29.91c-19.102-56.262-70.83-94.605-129.763-97.121-5.187 4.03-10.655 7.493-16.322 10.521-8.449 22.185-36.836 28.195-53.468 11.205-19.676-1.738-37.69-9.511-53.422-21.725-58.933 2.508-110.647 40.851-129.763 97.121L37.3 440.415c-2.936 8.603-1.522 18.084 3.774 25.469 5.279 7.391 13.821 11.771 22.906 11.771h349.693c9.083 0 17.626-4.379 22.906-11.771 5.294-7.385 6.707-16.866 3.788-25.469zM277.758 290.619c34.212-24.047 58.141-77.151 58.141-128.491 0-145.907-194.133-145.752-194.133 0 0 62.397 35.33 127.303 81.546 139.556 4.456-12.626 16.382-21.757 30.515-21.757 9.504-.001 17.983 4.168 23.931 10.692zM99.169 223.042c4.813 18.906 31.044 13.704 31.044-3.805 0-70.178 3.354-76.731-6.041-84.348 21.507-132.528 206.774-131.534 229.323.015-9.381 7.641-6.025 14.163-6.025 84.333 0 5.489 2.95 10.095 7.189 12.952 0 54.594-22.145 51.402-88.736 69.052-10.295-11.174-28.683-3.899-28.683 11.173 0 18.876 27.053 23.293 32.302 4.318 53.762-14.256 101.018-18.752 101.018-72.484v-11.027c3.991-2.066 6.817-5.729 7.951-10.179 51.822-1.056 51.838-78.719 0-79.775-1.072-4.24-3.711-7.703-7.423-9.815 1.336-15.902-1.94-36.805-11.057-56.985-63.405-130.835-250.676-79.643-253.609 47.155-.404 4.294-.078 7.338.17 9.83-3.712 2.112-6.351 5.575-7.423 9.815-21.71.419-39.212 18.084-39.212 39.888s17.502 39.467 39.212 39.887z"/></svg>Staff
							</a>
						</li>
						<li>
							<a href="admin.php">
								<svg xmlns="http://www.w3.org/2000/svg" class="menu_icon" viewBox="0 0 36 36" preserveAspectRatio="xMidYMid meet"><circle cx="14.67" cy="8.3" r="6"/><path d="M16.44 31.82a2.15 2.15 0 0 1-.38-2.55l.53-1-1.09-.33a2.14 2.14 0 0 1-1.5-2.1v-2.05a2.16 2.16 0 0 1 1.53-2.07l1.09-.33-.52-1a2.17 2.17 0 0 1 .35-2.52 18.92 18.92 0 0 0-2.32-.16A15.58 15.58 0 0 0 2 23.07v7.75a1 1 0 0 0 1 1h13.44zm17.26-8.36l-2-.6a6.73 6.73 0 0 0-.58-1.42l1-1.86a.35.35 0 0 0-.07-.43l-1.45-1.46a.38.38 0 0 0-.43-.07l-1.85 1a7.74 7.74 0 0 0-1.43-.6l-.61-2a.38.38 0 0 0-.36-.25h-2.08a.38.38 0 0 0-.35.26l-.6 2a6.85 6.85 0 0 0-1.45.61l-1.81-1a.38.38 0 0 0-.44.06l-1.47 1.44a.37.37 0 0 0-.07.44l1 1.82a7.24 7.24 0 0 0-.65 1.43l-2 .61a.36.36 0 0 0-.26.35v2.05a.36.36 0 0 0 .26.35l2 .61a7.29 7.29 0 0 0 .6 1.41l-1 1.9a.37.37 0 0 0 .07.44L19.16 32a.38.38 0 0 0 .44.06l1.87-1a7.09 7.09 0 0 0 1.4.57l.6 2.05a.38.38 0 0 0 .36.26h2.05a.38.38 0 0 0 .35-.26l.6-2.05a6.68 6.68 0 0 0 1.38-.57l1.89 1a.38.38 0 0 0 .44-.06L32 30.55a.38.38 0 0 0 .06-.44l-1-1.88a6.92 6.92 0 0 0 .57-1.38l2-.61a.39.39 0 0 0 .27-.35v-2.07a.4.4 0 0 0-.2-.36zm-8.83 4.72a3.34 3.34 0 1 1 3.33-3.34 3.34 3.34 0 0 1-3.33 3.34z"/><path fill-opacity="0" d="M0 0h36v36H0z"/></svg>Admins
							</a>
						</li>
						<li>
							<a href="billing.php">
								<svg xmlns="http://www.w3.org/2000/svg" class="menu_icon" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512.001 512.001"><path d="M448.875 1.78l-59.314 29.657L330.247 1.78c-4.7-2.35-10.232-2.35-14.932 0L256 31.437 196.686 1.78c-4.7-2.35-10.232-2.35-14.932 0L122.44 31.437 63.125 1.78C52.05-3.758 38.964 4.304 38.964 16.713v445.202c0 6.324 3.573 12.104 9.229 14.933l66.78 33.39c4.7 2.35 10.232 2.35 14.932 0l59.314-29.657 59.314 29.657c4.7 2.35 10.232 2.35 14.932 0l59.314-29.657 59.314 29.657c4.7 2.35 10.232 2.35 14.932 0l66.78-33.39c5.656-2.828 9.229-8.609 9.229-14.933V16.713c.002-12.383-13.06-20.481-24.159-14.933zm-9.229 393.355v56.462l-50.085 25.043-59.314-29.657c-2.35-1.175-4.908-1.762-7.466-1.762a16.7 16.7 0 0 0-7.466 1.762L256 476.639l-59.314-29.657c-4.7-2.35-10.232-2.35-14.932 0l-59.314 29.657-50.085-25.043v-56.462V43.726l42.619 21.31c4.7 2.35 10.232 2.35 14.932 0l59.314-29.657 59.314 29.657c4.7 2.35 10.232 2.35 14.932 0l59.314-29.657 59.314 29.657c4.7 2.35 10.232 2.35 14.932 0l42.619-21.31v351.409z"/><use xlink:href="#B"/><use xlink:href="#B" y="66.78"/><use xlink:href="#B" y="133.56"/><path d="M211.48 300.53h-89.04a16.7 16.7 0 0 0-16.695 16.695 16.7 16.7 0 0 0 16.695 16.695h89.04a16.7 16.7 0 0 0 16.695-16.695c0-9.221-7.475-16.695-16.695-16.695zm-89.04 77.91H89.05a16.7 16.7 0 0 0-16.695 16.695A16.7 16.7 0 0 0 89.05 411.83h33.39a16.7 16.7 0 0 0 16.695-16.695 16.7 16.7 0 0 0-16.695-16.695z"/><use xlink:href="#C"/><use xlink:href="#C" x="100.171"/><use xlink:href="#C" x="200.341"/><path d="M352.843 191.165c-17.515-9.258-35.627-18.832-35.627-29.761 0-15.343 12.482-27.825 27.825-27.825s27.825 12.482 27.825 27.825a16.7 16.7 0 0 0 16.695 16.695 16.7 16.7 0 0 0 16.695-16.695c0-27.966-18.858-51.594-44.52-58.882v-7.898a16.7 16.7 0 0 0-16.695-16.695 16.7 16.7 0 0 0-16.695 16.695v7.898c-25.663 7.287-44.52 30.916-44.52 58.882 0 31.046 29.616 46.701 53.413 59.28 17.515 9.258 35.627 18.832 35.627 29.761 0 15.343-12.482 27.825-27.825 27.825s-27.825-12.482-27.825-27.825a16.7 16.7 0 0 0-16.695-16.695 16.7 16.7 0 0 0-16.695 16.695c0 27.966 18.858 51.594 44.52 58.882v7.898a16.7 16.7 0 0 0 16.695 16.695 16.7 16.7 0 0 0 16.695-16.695v-7.898c25.663-7.287 44.52-30.916 44.52-58.882 0-31.047-29.616-46.701-53.413-59.28z"/><defs ><path id="B" d="M211.48 100.189h-89.04a16.7 16.7 0 0 0-16.695 16.695 16.7 16.7 0 0 0 16.695 16.695h89.04a16.7 16.7 0 0 0 16.695-16.695 16.7 16.7 0 0 0-16.695-16.695z"/><path id="C" d="M222.61 378.44h-33.39a16.7 16.7 0 0 0-16.695 16.695 16.7 16.7 0 0 0 16.695 16.695h33.39a16.7 16.7 0 0 0 16.695-16.695 16.7 16.7 0 0 0-16.695-16.695z"/></defs></svg>Billing
							</a>
						</li>
					<?php } ?>
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
				<form action="account.php" name="accountedit" method="post" enctype="multipart/form-data">
					<div class="section_container">
						<div class="section_header">
							<svg xmlns="http://www.w3.org/2000/svg" class="section_icon" viewBox="0 0 24 24"><path d="M12 8a4 4 0 1 0 4 4 4 4 0 0 0-4-4zm0 6a2 2 0 1 1 2-2 2 2 0 0 1-2 2zm8.99-5h-1.575l-.046-.129 1.11-1.11a2.011 2.011 0 0 0 0-2.842l-1.4-1.4a2 2 0 0 0-1.421-.588h0a2 2 0 0 0-1.419.588L15.07 4.612 15 4.58V3.009A2.011 2.011 0 0 0 12.99 1h-1.98A2.011 2.011 0 0 0 9 3.009v1.557l-.086.049-.043.016-1.106-1.109a2 2 0 0 0-1.42-.589h0a2 2 0 0 0-1.421.588l-1.4 1.4a2.011 2.011 0 0 0 0 2.842l1.1 1.143-.043.093H3.01A2.011 2.011 0 0 0 1 11.009v1.982A2.011 2.011 0 0 0 3.01 15h1.575l.046.129-1.11 1.11a2.011 2.011 0 0 0 0 2.842l1.4 1.4a2.059 2.059 0 0 0 2.842 0l1.115-1.115.121.056v1.571A2.011 2.011 0 0 0 11.01 23h1.98A2.011 2.011 0 0 0 15 20.991v-1.557l.129-.065 1.109 1.109a2.058 2.058 0 0 0 2.843 0l1.4-1.4a2.011 2.011 0 0 0 0-2.842l-1.1-1.143.043-.093h1.566A2.011 2.011 0 0 0 23 12.991v-1.982A2.011 2.011 0 0 0 20.99 9zm0 4h-1.569a2.1 2.1 0 0 0-1.466 3.54l1.109 1.124-1.414 1.4-1.11-1.109A2.1 2.1 0 0 0 13 19.42L12.99 21 11 20.991V19.42a2.043 2.043 0 0 0-1.307-1.881 2.138 2.138 0 0 0-.816-.164 2 2 0 0 0-1.417.58l-1.124 1.109-1.4-1.414 1.108-1.108A2.1 2.1 0 0 0 4.579 13L3 12.991 3.01 11h1.569a2.1 2.1 0 0 0 1.466-3.54L4.936 6.336l1.414-1.4 1.11 1.109a2.04 2.04 0 0 0 2.227.419l.018-.007A2.04 2.04 0 0 0 11 4.58L11.01 3l1.99.009V4.58a2 2 0 0 0 1.227 1.845l.087.039a2.038 2.038 0 0 0 2.226-.419l1.124-1.109 1.4 1.414-1.108 1.108A2.1 2.1 0 0 0 19.421 11h1.569l.01.009z"/></svg><h1>Edit My Account</h1>
						</div>
						<?php if($_GET['flag']=="updated"){ ?> 
							<div class="section_flag_row">
								<div class="section_full">
									<?php echo "<strong>Account Updated Successfully!</strong>"; ?>
								</div>
							</div>
						<?php } if($_GET['flag']=="notvalid") { ?>
							<div class="section_error_row">
								<div class="section_full">
									<?php echo "<strong>Image not valid, only .jpg allowed</strong>"; ?>
								</div>
							</div>
						<?php } ?>
						<div class="section_result_row">
							<div class="section_form_14">
								First Name:
							</div>
							<div class="section_form_36">
								<input name="fname" type="text" id="fname" value="<?php echo $profile['firstname']; ?>" size="32" />
							</div>
							<div class="section_form_14">
								Address:
							</div>
							<div class="section_form_36">
								<input name="address" type="text" id="address" value="<?php echo $profile['address']; ?>" size="32" />
							</div>
							<div class="clear"></div>
							<div class="section_form_14">
								Last Name:
							</div>
							<div class="section_form_36">
								<input name="lname" type="text" id="lname" value="<?php echo $profile['lastname']; ?>" size="32" />
							</div>
							<div class="section_form_14">
								City:
							</div>
							<div class="section_form_36">
								<input name="city" type="text" id="city" value="<?php echo $profile['city']; ?>" size="32" />
							</div>
							<div class="clear"></div>
							<div class="section_form_14">
								&nbsp;
							</div>
							<div class="section_form_36">
								&nbsp;
							</div>
							<div class="section_form_10">
								State:
							</div>
							<div class="section_form_14">
								<input name="state" type="text" id="state" value="<?php echo $profile['state']; ?>" size="4" maxlength="2" />
							</div>
							<div class="section_form_10">
								Zip:
							</div>
							<div class="section_form_14">
								<input name="zip" type="text" id="zip" value="<?php echo $profile['zip']; ?>" size="10" maxlength="5" />
							</div>
							<div class="clear"></div>
							<div class="section_form_14">
								Email Address:
							</div>
							<div class="section_form_36">
								<input name="email" type="text" id="email" value="<?php echo $profile['email']; ?>" size="32" />
							</div>
							<div class="section_form_14">
								Web Site:
							</div>
							<div class="section_form_36">
								<input name="site" type="text" id="site" value="<?php echo $profile['site']; ?>" size="32" />
							</div>
							<div class="clear"></div>
							<div class="section_form_14">
								Cell Phone:
							</div>
							<div class="section_form_36">
								<input name="cell" type="text" id="cell" value="<?php echo $profile['cell_phone']; ?>" size="24" maxlength="12" /><br>ex. 555-555-5555
							</div>
							<div class="section_form_14">
								Office Phone:
							</div>
							<div class="section_form_36">
								<input name="officephone" type="text" id="officephone" value="<?php echo $profile['office_phone']; ?>" size="24" maxlength="12" /><br>ex. 555-555-5555
							</div>
							<div class="clear"></div>
							<div class="section_form_14">
								User Photo:
							</div>
							<div class="section_form_36">
								Upload new photo - <strong>.jpg only</strong><br><input name="uphoto" type="file" id="uphoto" size="18" />
							</div>
							<div class="section_form_14">
								Current Photo:
							</div>
							<div class="section_form_36">
								<span class="agent_pic_order_80">
									<?php if(file_exists("floorplan/profile/".$mem_id.".jpg")){ ?><img src="floorplan/profile/<?php echo $mem_id; ?>.jpg" width="80px" alt="<?php echo $profile['firstname']." ". $profile['lastname']; ?>"><?php } ?>
								</span>
							</div>
							<div class="clear"></div>
							<div class="section_form_14">
								Password:
							</div>
							<div class="section_form_36">
								<input name="pass" readonly type="password" id="pass" onfocus="if (this.hasAttribute('readonly')) {
									this.removeAttribute('readonly'); this.blur(); this.focus(); }" />
							</div>
							<div class="clear"></div>
							<?php
								if ($_SESSION['SESS_LEVEL']=="dealer" || $_SESSION['SESS_LEVEL']=="d_assist") { 
							?>
							<div class="section_form_14">
								Link Calendar:
							</div>
							<?php if ($profile['g_token']=="") { ?>
							<div class="section_form_36">
								<a href="<?= $login_url ?>"><svg xmlns="http://www.w3.org/2000/svg" class="section_icon" viewBox="0 0 800 800"><path d="M610.5 189.5H800v421.1H610.5V189.5zM189.5 800h421.1V610.5H189.5V800zm421-610.5V0H63.2C28.3 0 0 28.2 0 63.1v.1 547.4h189.5V189.5h421zM370.3 397.8v-2.2c9.1-4.8 16.7-11.6 22.9-20.6 6.2-8.9 9.3-19.8 9.3-32.7 0-12.6-3.3-24-10-34.2-6.9-10.3-16.5-18.5-27.7-23.8-12.5-5.9-26.1-8.8-39.9-8.6-20 0-36.5 5.2-49.4 15.6s-21.7 22.4-26.4 35.9l36.2 15.1c2.9-8.3 7.5-15.4 13.8-21.1s14.8-8.6 25.6-8.6c11 0 20.1 2.9 27.2 8.8 7.1 5.7 11.1 14.4 10.7 23.4 0 11-4 19.6-12 25.9s-17.8 9.5-29.5 9.5h-18.9v36.2h21.1c13.6 0 24.9 3.6 34 10.9s13.6 16.6 13.6 28.1c0 11.2-4.3 20.5-12.9 27.7s-19 11-31 11c-11.7 0-21.7-3.4-29.9-10.4-8.3-6.9-14.1-16.7-17.4-29.4l-36.5 15.1c5.9 20.5 16.8 36.1 32.6 46.7 15.7 10.6 32.8 15.9 51.3 15.9 14.9.2 29.7-3.1 43.1-9.7 12.7-6.4 22.8-15.3 30.1-26.5s10.9-24 10.9-38.3-3.8-26.6-11.5-36.8c-7.5-10.1-17.7-18.1-29.4-22.9h0zm69.8-64.4l20.1 30.4 39.9-29v191.5h39.6V281.5H512l-71.9 51.9h0zM736.8 0H627.2v172.8H800V63.2C800 28.3 771.8 0 736.8 0h0zM627.2 783.3l156.1-156.1H627.2v156.1zM0 736.8C0 771.7 28.3 800 63.2 800h109.6V627.2H0v109.6z"/></svg>Login with Google</a>
							</div>
							<?php
								} else { 
							?>
							<div class="section_form_36">
								Calendar is linked! - <a href="account.php?task=rem_cal"><svg xmlns="http://www.w3.org/2000/svg" class="section_icon" viewBox="0 0 800 800"><path d="M610.5 189.5H800v421.1H610.5V189.5zM189.5 800h421.1V610.5H189.5V800zm421-610.5V0H63.2C28.3 0 0 28.2 0 63.1v.1 547.4h189.5V189.5h421zM370.3 397.8v-2.2c9.1-4.8 16.7-11.6 22.9-20.6 6.2-8.9 9.3-19.8 9.3-32.7 0-12.6-3.3-24-10-34.2-6.9-10.3-16.5-18.5-27.7-23.8-12.5-5.9-26.1-8.8-39.9-8.6-20 0-36.5 5.2-49.4 15.6s-21.7 22.4-26.4 35.9l36.2 15.1c2.9-8.3 7.5-15.4 13.8-21.1s14.8-8.6 25.6-8.6c11 0 20.1 2.9 27.2 8.8 7.1 5.7 11.1 14.4 10.7 23.4 0 11-4 19.6-12 25.9s-17.8 9.5-29.5 9.5h-18.9v36.2h21.1c13.6 0 24.9 3.6 34 10.9s13.6 16.6 13.6 28.1c0 11.2-4.3 20.5-12.9 27.7s-19 11-31 11c-11.7 0-21.7-3.4-29.9-10.4-8.3-6.9-14.1-16.7-17.4-29.4l-36.5 15.1c5.9 20.5 16.8 36.1 32.6 46.7 15.7 10.6 32.8 15.9 51.3 15.9 14.9.2 29.7-3.1 43.1-9.7 12.7-6.4 22.8-15.3 30.1-26.5s10.9-24 10.9-38.3-3.8-26.6-11.5-36.8c-7.5-10.1-17.7-18.1-29.4-22.9h0zm69.8-64.4l20.1 30.4 39.9-29v191.5h39.6V281.5H512l-71.9 51.9h0zM736.8 0H627.2v172.8H800V63.2C800 28.3 771.8 0 736.8 0h0zM627.2 783.3l156.1-156.1H627.2v156.1zM0 736.8C0 771.7 28.3 800 63.2 800h109.6V627.2H0v109.6z"/></svg>Unlink Google Calendar</a>
							</div>
							<?php	} ?>
							<div class="clear"></div>
							<?php } 
								if ($_SESSION['SESS_LEVEL']=="agent") { ?>
								<div class="section_form_14">
								Credit Card: 
								</div>
								<div class="section_form_36">
									<span class="cc_span">
										<?php $cardtype = explode("|",$c4); 
										if($cardtype[0] == "VISA") echo '<svg xmlns="http://www.w3.org/2000/svg" class="cc_icon" viewBox="0 0 800 520"><path d="M41 3.6h718c22.7 0 41 18.4 41 41v430.8c0 22.7-18.4 41-41 41H41c-22.7 0-41-18.4-41-41V44.6c0-22.6 18.4-41 41-41z" fill="#0e4595"/><path d="M300.7 361.3l34.2-200.8h54.7l-34.2 200.8h-54.7zm252.4-196.5c-10.8-4.1-27.8-8.4-49-8.4-54.1 0-92.2 27.2-92.5 66.3-.3 28.9 27.2 44.9 48 54.5 21.3 9.8 28.5 16.1 28.4 24.9-.1 13.5-17 19.6-32.7 19.6-21.9 0-33.5-3-51.5-10.5l-7.1-3.2-7.7 44.9c12.8 5.6 36.4 10.5 61 10.7 57.5 0 94.9-26.9 95.3-68.6.2-22.8-14.4-40.2-45.9-54.6-19.1-9.3-30.8-15.5-30.7-24.9 0-8.3 9.9-17.3 31.3-17.3 17.9-.3 30.9 3.6 41 7.7l4.9 2.3 7.2-43.4m140.9-4.3h-42.3c-13.1 0-22.9 3.6-28.7 16.7l-81.3 184h57.5l11.5-30.2 70.1.1 6.7 30.1h50.8L694 160.5h0zm-67.1 129.6l21.8-56.1c-.3.5 4.5-11.6 7.3-19.2l3.7 17.3 12.7 58h-45.5zM254.3 160.5l-53.6 136.9-5.7-27.8c-10-32.1-41.1-66.8-75.8-84.2l49 175.6 57.9-.1 86.2-200.4h-58" fill="#fff"/><path d="M150.7 160.5H62.4l-.7 4.2c68.7 16.6 114.1 56.8 132.9 105l-19.2-92.3c-3.2-12.7-12.8-16.5-24.7-16.9" fill="#f2ae14"/></svg>&nbsp;&nbsp;Ending in <strong>'.$cardtype[1].'</strong>';
										if($cardtype[0] == "MC") echo '<svg xmlns="http://www.w3.org/2000/svg" class="cc_icon" viewBox="0 0 800 520"><path d="M42.7 4.1h714.7c23.6 0 42.7 19.5 42.7 43.6v425.7c0 24.1-19.1 43.6-42.7 43.6H42.7C19.1 516.9 0 497.4 0 473.4V47.6c0-24 19.1-43.5 42.7-43.5z"/><path d="M462.9 260.5c0 107.9-85.7 195.4-191.5 195.4S80 368.4 80 260.5 165.7 65.1 271.5 65.1s191.4 87.5 191.4 195.4" fill="#d9222a"/><path d="M528.5 65.1c-49.5 0-94.6 19.2-128.5 50.6-6.9 6.4-13.4 13.3-19.3 20.7h38.7c5.3 6.6 10.2 13.5 14.6 20.7h-67.9c-4.1 6.6-7.8 13.6-11 20.7H445c3.1 6.7 5.8 13.6 8.1 20.7H346.9c-2.2 6.7-4.1 13.6-5.6 20.7h117.3c2.9 13.6 4.3 27.5 4.3 41.4 0 21.7-3.5 42.6-9.9 62.1H346.9c2.3 7.1 5 14 8.1 20.7h90c-3.3 7.1-7 14-11 20.7h-67.9c4.4 7.2 9.3 14.1 14.6 20.7h38.7c-5.9 7.4-12.4 14.3-19.3 20.7 34 31.4 79.1 50.6 128.5 50.6 105.7 0 191.5-87.5 191.5-195.4-.1-108.1-85.8-195.6-191.6-195.6" fill="#ee9f2d"/><path d="M694.5 369.4c0-3.5 2.8-6.3 6.2-6.3s6.2 2.8 6.2 6.3-2.8 6.3-6.2 6.3c-3.5.1-6.2-2.8-6.2-6.3h0zm6.2 4.8c2.6 0 4.7-2.2 4.7-4.8 0-2.7-2.1-4.8-4.7-4.8s-4.7 2.1-4.7 4.8 2.1 4.8 4.7 4.8zm-.9-2h-1.3v-5.5h2.3c.5 0 1 0 1.4.3a1.75 1.75 0 0 1 .7 1.4c0 .6-.4 1.2-.9 1.4l1 2.4h-1.4l-.8-2.2h-.9l-.1 2.2h0zm0-3.1h.7c.3 0 .5 0 .8-.1.2-.1.3-.4.3-.6s-.1-.5-.3-.6-.6-.1-.8-.1h-.7v1.4h0zm-473-87.2c-2.2-.3-3.1-.3-4.6-.3-11.8 0-17.7 4.1-17.7 12.3 0 5 2.9 8.2 7.5 8.2 8.3 0 14.4-8.3 14.8-20.2zm15.1 35.9h-17.2l.4-8.4c-5.3 6.6-12.3 9.7-21.8 9.7-11.3 0-19-9-19-22 0-19.6 13.4-31.1 36.5-31.1 2.4 0 5.4.2 8.5.6.6-2.7.8-3.8.8-5.2 0-5.3-3.6-7.3-13.3-7.3-10.2-.1-18.6 2.5-22 3.6l2.9-18.1c10.4-3.1 17.2-4.3 24.9-4.3 17.8 0 27.3 8.2 27.3 23.6 0 4.1-.6 9.3-1.7 16l-6.3 42.9h0zm-66.3 0h-20.8l11.9-76.2-26.6 76.2h-14.2l-1.7-75.8-12.5 75.8H92.2l16.3-99.1h29.9l1.8 55.5 18.2-55.5h33.2l-16 99.1m378.6-35.9c-2.2-.3-3.1-.3-4.6-.3-11.8 0-17.7 4.1-17.7 12.3 0 5 2.9 8.2 7.4 8.2 8.5 0 14.6-8.3 14.9-20.2h0zm15.1 35.9h-17.2l.4-8.4c-5.3 6.6-12.3 9.7-21.8 9.7-11.3 0-19-9-19-22 0-19.6 13.4-31.1 36.5-31.1 2.4 0 5.4.2 8.5.6.6-2.7.8-3.8.8-5.2 0-5.3-3.6-7.3-13.3-7.3-10.2-.1-18.5 2.5-22 3.6l2.9-18.1c10.4-3.1 17.2-4.3 24.9-4.3 17.9 0 27.3 8.2 27.3 23.6 0 4.1-.6 9.3-1.7 16l-6.3 42.9h0z"/><path d="M334.3 316.6c-5.7 1.8-10.1 2.6-14.9 2.6-10.6 0-16.4-6.2-16.4-17.7-.2-3.6 1.5-12.9 2.8-21.5l9-55h20.7l-2.4 12.2h12.5l-2.8 19.4h-12.5l-5.9 37c0 4.2 2.2 6 7.1 6 2.4 0 4.2-.2 5.6-.8l-2.8 17.8m63.3-.7c-7.1 2.2-13.9 3.3-21.2 3.3-23.1 0-35.2-12.4-35.2-36 0-27.6 15.3-47.8 36.2-47.8 17 0 27.9 11.4 27.9 29.2 0 5.9-.7 11.7-2.5 19.8h-41.1c-1.4 11.7 5.9 16.6 18 16.6 7.4 0 14.1-1.6 21.5-5.1l-3.6 20h0zM386 268.1c.1-1.7 2.2-14.4-9.6-14.4-6.6 0-11.3 5.1-13.2 14.4H386zm-131.7-5.4c0 10.2 4.8 17.2 15.8 22.5 8.4 4 9.7 5.2 9.7 8.9 0 5-3.7 7.3-11.9 7.3-6.2 0-12-1-18.6-3.2l-2.9 18.6c4.7 1.1 8.9 2 21.6 2.4 21.9 0 32.1-8.5 32.1-26.9 0-11.1-4.2-17.6-14.7-22.5-8.7-4.1-9.7-5-9.7-8.8 0-4.4 3.5-6.6 10.2-6.6 4.1 0 9.7.4 14.9 1.2l3-18.7c-5.4-.9-13.5-1.6-18.3-1.6-23.2.1-31.2 12.5-31.2 27.4m244.4-25.2c5.8 0 11.2 1.5 18.6 5.4l3.4-21.5c-3-1.2-13.8-8.4-22.8-8.4-13.9 0-25.7 7-33.9 18.7-12.1-4.1-17 4.2-23.1 12.4l-5.4 1.3c.4-2.7.8-5.4.7-8.1H417c-2.6 25-7.2 50.2-10.8 75.2l-.9 5.4H426c3.5-23 5.4-37.8 6.5-47.7l7.8-4.4c1.2-4.4 4.8-5.9 12.2-5.8-1 5.3-1.5 10.8-1.5 16.5 0 26.4 13.9 42.8 36.3 42.8 5.8 0 10.7-.8 18.4-2.9l3.7-22.6c-6.9 3.5-12.5 5.1-17.7 5.1-12.1 0-19.4-9.1-19.4-24.2 0-21.9 10.9-37.2 26.4-37.2"/><path d="M181.6 311.9h-20.8l11.9-76.2-26.6 76.2h-14.2l-1.7-75.7-12.5 75.7H98.2l16.3-99.1h29.8l.9 61.4 20.1-61.4h32.3l-16 99.1" fill="#fff"/><path d="M674.7 218.7l-4.6 28.6c-5.7-7.6-11.8-13.2-19.9-13.2-10.5 0-20 8.1-26.3 20.1-8.7-1.8-17.7-5-17.7-5v.1c.7-6.7 1-10.8.9-12.1H588c-2.6 25-7.2 50.2-10.8 75.2l-1 5.4H597l6.5-46.3c7.1-6.6 10.7-12.3 17.8-11.9-3.2 7.8-5 16.9-5 26.1 0 20.2 10 33.5 25.1 33.5 7.6 0 13.5-2.7 19.2-8.9l-1 7.5h19.7l15.8-99.1h-20.4 0zm-26 80.5c-7.1 0-10.6-5.3-10.6-15.9 0-15.8 6.7-27.1 16.1-27.1 7.1 0 11 5.6 11 15.8 0 16-6.8 27.2-16.5 27.2z"/><g fill="#fff"><path d="M232.7 276c-2.2-.3-3.1-.3-4.6-.3-11.8 0-17.7 4.1-17.7 12.3 0 5 2.9 8.2 7.4 8.2 8.5 0 14.6-8.2 14.9-20.2h0zm15.2 35.9h-17.2l.4-8.3c-5.2 6.6-12.3 9.7-21.8 9.7-11.3 0-19-9-19-22 0-19.6 13.4-31.1 36.5-31.1 2.4 0 5.4.2 8.5.6.6-2.7.8-3.8.8-5.2 0-5.3-3.6-7.3-13.3-7.3-10.2-.1-18.6 2.5-22 3.6l2.9-18.1c10.4-3.1 17.2-4.3 24.9-4.3 17.9 0 27.3 8.2 27.3 23.6 0 4.2-.6 9.3-1.7 16l-6.3 42.8h0zm278.8-96.4l-3.4 21.5c-7.4-3.8-12.8-5.4-18.6-5.4-15.5 0-26.4 15.3-26.4 37.1 0 15 7.3 24.1 19.4 24.1 5.1 0 10.8-1.6 17.7-5.1l-3.6 22.6c-7.7 2.1-12.6 2.9-18.4 2.9-22.4 0-36.3-16.4-36.3-42.8 0-35.4 19.3-60.2 46.8-60.2 9 .1 19.7 4.1 22.8 5.3m33.5 60.5c-2.2-.3-3.1-.3-4.6-.3-11.8 0-17.7 4.1-17.7 12.3 0 5 2.9 8.2 7.4 8.2 8.4 0 14.5-8.2 14.9-20.2h0zm15.1 35.9h-17.2l.4-8.3c-5.3 6.6-12.3 9.7-21.8 9.7-11.3 0-19-9-19-22 0-19.6 13.4-31.1 36.5-31.1 2.4 0 5.4.2 8.5.6.6-2.7.8-3.8.8-5.2 0-5.3-3.6-7.3-13.3-7.3-10.2-.1-18.6 2.5-22 3.6l2.9-18.1c10.4-3.1 17.2-4.3 24.9-4.3 17.9 0 27.3 8.2 27.3 23.6 0 4.2-.6 9.3-1.7 16l-6.3 42.8h0zm-235.1-1.2c-5.7 1.8-10.1 2.6-14.9 2.6-10.6 0-16.4-6.2-16.4-17.7-.1-3.6 1.5-12.9 2.9-21.5l9-55h20.7l-2.4 12.2h10.6l-2.8 19.4h-10.6l-5.9 37c0 4.2 2.2 6 7.1 6 2.4 0 4.2-.2 5.6-.8l-2.9 17.8m63.4-.6c-7.1 2.2-14 3.3-21.2 3.3-23.1 0-35.2-12.4-35.2-36 0-27.6 15.3-47.9 36.2-47.9 17 0 27.9 11.4 27.9 29.2 0 5.9-.7 11.7-2.5 19.8h-41.1c-1.4 11.7 5.9 16.6 18 16.6 7.4 0 14.1-1.6 21.5-5.1l-3.6 20.1h0zM392 262.3c.1-1.7 2.2-14.4-9.6-14.4-6.6 0-11.3 5.1-13.2 14.4H392zm-131.7-5.5c0 10.2 4.8 17.2 15.8 22.5 8.4 4 9.7 5.2 9.7 8.9 0 5-3.7 7.3-11.9 7.3-6.2 0-12-1-18.6-3.2l-2.8 18.6c4.7 1.1 8.9 2 21.6 2.4 21.9 0 32.1-8.5 32.1-26.9 0-11.1-4.2-17.6-14.7-22.5-8.7-4.1-9.7-5-9.7-8.8 0-4.4 3.5-6.6 10.2-6.6 4.1 0 9.6.5 14.9 1.2l3-18.7c-5.4-.9-13.5-1.6-18.3-1.6-23.3.1-31.3 12.5-31.3 27.4m425 55.1h-19.7l1-7.5c-5.7 6.2-11.5 8.9-19.2 8.9-15.1 0-25.1-13.3-25.1-33.5 0-26.8 15.5-49.4 33.8-49.4 8.1 0 14.2 3.4 19.8 11l4.6-28.6H701l-15.7 99.1zm-30.6-18.6c9.7 0 16.5-11.2 16.5-27.2 0-10.2-3.9-15.8-11-15.8-9.4 0-16.1 11.2-16.1 27.1-.1 10.6 3.5 15.9 10.6 15.9z"/><path d="M594 231.3c-2.6 25-7.2 50.2-10.8 75.2l-1 5.4H603c7.4-49.3 9.2-58.9 20.9-57.7 1.9-10.1 5.3-18.9 7.9-23.4-8.7-1.9-13.6 3.2-19.9 12.7.5-4.1 1.4-8.1 1.2-12.2H594m-171.1 0c-2.6 25-7.2 50.2-10.8 75.2l-.9 5.4H432c7.4-49.3 9.2-58.9 20.9-57.7 1.9-10.1 5.3-18.9 7.9-23.4-8.7-1.9-13.6 3.2-19.9 12.7.5-4.1 1.4-8.1 1.2-12.2h-19.2m271.6 74.3c0-3.5 2.8-6.3 6.2-6.3s6.2 2.8 6.2 6.3-2.8 6.3-6.2 6.3c-3.5 0-6.2-2.8-6.2-6.3zm6.2 4.8c2.6 0 4.7-2.2 4.7-4.8s-2.1-4.8-4.7-4.8-4.7 2.2-4.7 4.8c0 2.7 2.1 4.8 4.7 4.8zm-.9-2h-1.3v-5.5h2.3c.5 0 1 0 1.4.3a1.75 1.75 0 0 1 .7 1.4c0 .6-.4 1.2-.9 1.4l1 2.4h-1.4l-.8-2.2h-.9l-.1 2.2h0zm0-3.2h.7c.3 0 .5 0 .8-.1.2-.1.3-.4.3-.6s-.1-.4-.3-.6c-.2-.1-.6-.1-.8-.1h-.7v1.4h0z"/></g></svg>&nbsp;&nbsp;Ending in <strong>'.$cardtype[1].'</strong>';
										if($cardtype[0] == "DISC") echo '<svg xmlns="http://www.w3.org/2000/svg" class="cc_icon" viewBox="0 0 800 520" fill-rule="evenodd"><path d="M46.4 3.9C15.3 3.9 0 21.2 0 52.3v418.5c0 31.1 17.3 46.3 48.4 46.3h695.2c31.1 0 56.4-25.2 56.4-56.3V54.3C800 23.2 778.7 4 747.6 4H46.4v-.1z" fill="#4d4d4d"/><path d="M335.5 169.7c9.1 0 16.7 1.8 25.9 6.2v23.3c-8.8-8.1-16.4-11.4-26.4-11.4-19.8 0-35.3 15.4-35.3 34.9 0 20.6 15.1 35 36.3 35 9.6 0 17-3.2 25.4-11.1V270c-9.6 4.2-17.3 5.9-26.4 5.9-32.1 0-57-23.1-57-53 0-29.5 25.6-53.2 57.5-53.2h0 0zm-99.6.7c11.8 0 22.7 3.8 31.7 11.3l-11 13.6c-5.5-5.8-10.7-8.2-17-8.2-9.1 0-15.7 4.9-15.7 11.3 0 5.5 3.7 8.4 16.4 12.8 24 8.2 31.1 15.5 31.1 31.7 0 19.7-15.4 33.3-37.3 33.3-16 0-27.7-5.9-37.4-19.3l13.6-12.3c4.9 8.8 12.9 13.5 23 13.5 9.4 0 16.4-6.1 16.4-14.3 0-4.3-2.1-7.9-6.3-10.5-2.1-1.2-6.3-3-14.6-5.8-19.8-6.7-26.6-13.9-26.6-27.8 0-16.8 14.6-29.3 33.7-29.3h0zm240.8 1.8h23l28.8 68.2 29.2-68.2h22.8l-46.7 104.2h-11.3l-45.8-104.2h0zm-407.6.1H100c34.2 0 58 20.9 58 50.8 0 14.9-7.3 29.4-19.6 39-10.4 8.1-22.2 11.7-38.5 11.7H69.1V172.3h0 0zm98.6 0h21.1v101.5h-21.1V172.3h0zm422.3 0h59.7v17.2H611V212h37.3v17.2H611v27.4h38.7v17.2H590V172.3h0 0zm73.7 0H695c24.3 0 38.2 11 38.2 30 0 15.5-8.7 25.7-24.6 28.8l34 42.8h-25.9l-29.2-40.8h-2.7v40.8h-21l-.1-101.6h0zm21.1 16v30.8h6.2c13.5 0 20.6-5.5 20.6-15.7 0-9.9-7.1-15.1-20.3-15.1h-6.5 0 0zm-594.6 1.2v67.1h5.7c13.6 0 22.2-2.5 28.8-8.1 7.3-6.1 11.7-15.8 11.7-25.6 0-9.7-4.4-19.2-11.7-25.3-7-5.9-15.2-8.2-28.8-8.2l-5.7.1h0z" fill="#fff"/><path d="M425.8 169c31.7 0 57.5 24.2 57.5 54h0c0 29.8-25.7 54-57.5 54s-57.5-24.2-57.5-54h0c0-29.8 25.7-54 57.5-54h0zM800 299.3c-26.7 18.8-226.7 153-573.1 217.8h522.7c31.1 0 50.4-13.2 50.4-44.3V299.3h0 0z" fill="#f47216"/></svg>&nbsp;&nbsp;Ending in <strong>'.$cardtype[1].'</strong>';
										if($cardtype[0] == "AMEX") echo '<svg xmlns="http://www.w3.org/2000/svg" class="cc_icon" viewBox="0 0 800 520"><path d="M42.7 4.1h714.7c23.6 0 42.7 19.5 42.7 43.5v425.6c0 24-19.1 43.5-42.7 43.5H42.7C19.1 516.9 0 497.4 0 473.3V47.7C0 23.6 19.1 4.1 42.7 4.1z" fill="#306fc5"/><g fill="#fff"><path d="M116.7 199.5h23.9l-12-31.1-11.9 31.1zm127.1 104.2v14.4H284v15.5h-40.2v16.7h44.5L309 327l-19.6-23.3h-45.6zm314-135.3l-13.1 31.1h25l-11.9-31.1zM360 359.1v-63.2L331.8 327l28.2 32.1zm53.3-45.4c-1.1-6.7-5.4-10-12-10h-22.8v20h23.9c6.5 0 10.9-3.4 10.9-10zm76 7.7c2.2-1.1 3.3-4.4 3.3-7.8 1.1-4.4-1.1-6.7-3.3-7.8s-5.4-1.1-8.7-1.1h-21.7v17.7h21.7c3.3.2 6.5.2 8.7-1h0z"/><path d="M645.8 139.6v13.3l-6.5-13.3h-51.1v13.3l-6.5-13.3h-69.5c-12 0-21.7 2.2-30.4 6.7v-6.7h-48.9v1.1 5.5c-5.4-4.4-12-6.7-20.6-6.7h-175l-12 27.7-11.9-27.7h-39.1H158v13.3l-5.4-13.3h-1.1-46.7L83 191.7l-25 56.5-.5 1.1h.5 55.4.7l.4-1.1 6.5-16.6h14.1l6.5 17.7h63v-1.1V236l5.4 13.3h31.5l5.4-13.3v12.2 1.1h25H397h1.1v-28.8h2.2c2.2 0 2.2 0 2.2 3.3v24.4h78.2v-6.7c6.5 3.3 16.3 6.7 29.3 6.7h32.6l6.5-17.7h15.2l6.5 17.7h63v-11.1-5.5l9.8 16.6h2.2 1.1 47.8V139.6h-48.9 0 0 0zm-368.3 93.1h-10.9-6.5v-6.6-54.3l-1.1 2.5h0l-25.3 58.5h-.8-5.8-9.4l-26.1-61v61h-36.9l-7.6-16.6h-36.9l-7.6 16.6H83.4l32.1-77.6h27.2l30.4 74.3v-74.3h6.5 22.3l.5 1.1h0l13.7 30.4 9.8 22.8.3-1.1 21.7-53.2h29.3l.3 77.5h0 0 0zm74.9-61H310v14.4h41.3v15.5H310v15.5h42.4v16.6h-60.8V155h60.8v16.7h0zm77.5 28.6c0 .1.1.1 0 0 .5.5.9 1 1.2 1.4 2.1 2.8 3.8 6.9 3.8 13v.3.2.1 1.9 15.5h-16.3v-8.9c0-4.4 0-11.1-3.3-15.5-1-1-2.1-1.7-3.2-2.2-1.6-1.1-4.7-1.1-9.8-1.1h-19.6v27.7h-18.5v-77.6h41.3c9.8 0 16.3 0 21.7 3.3 5.3 3.3 8.5 8.7 8.7 17.2-.3 11.8-7.7 18.3-13 20.5.1 0 3.8.8 7 4.2h0zm36.6 32.4H448v-77.6h18.5v77.6zm211.9 0h-23.9L619.7 174v48.8l-.1-.1v10.1h-18.4 0-18.5l-6.5-16.6h-38l-6.5 17.7H511c-8.7 0-19.6-2.2-26.1-8.9s-9.8-15.5-9.8-29.9c0-11.1 2.2-22.2 9.8-31.1 5.4-6.7 15.2-8.9 27.2-8.9h17.4v16.6h-17.4c-6.5 0-9.8 1.1-14.1 4.4-3.3 3.3-5.4 10-5.4 17.7 0 8.9 1.1 14.4 5.4 18.9 3.3 3.3 7.6 4.4 13 4.4h7.6l25-61h10.9 16.3l30.4 74.3v-3.8-25-2.2-43.2h27.2l31.5 54.3v-54.3h18.5v76.5h0 0 0zm-264-45.3c.3-.3.6-.7.8-1.2 1-1.6 2.1-4.5 1.5-8.4 0-.4-.1-.7-.2-1v-.3h0c-.5-1.9-1.9-3.1-3.3-3.8-2.2-1.1-5.4-1.1-8.7-1.1h-21.7v17.7h21.7c3.3 0 6.5 0 8.7-1.1.3-.2.6-.4.9-.7h0c.1.2.2.1.3-.1h0zm328.1 156.2c0-7.8-2.2-15.5-5.4-21.1v-49.9h-.1v-3.3h-52.3c-6.8 0-15 6.7-15 6.7v-6.7h-50c-7.6 0-17.4 2.2-21.7 6.7v-6.7h-89.1v3.3 3.3c-6.5-5.5-18.5-6.7-23.9-6.7h-58.7v3.3 3.3c-5.4-5.5-18.5-6.7-25-6.7h-65.2L320.9 286l-14.1-16.6h-4.5-7.4H209v3.3 8.8 99.9h95.6l15.7-16 13.6 16h1.1 55.1 2.5 1.1 1.1v-11.1-14.4h5.4c7.6 0 17.4 0 25-3.3v27.7 2.2h48.9v-2.2-26.6h2.2c3.3 0 3.3 0 3.3 3.3v23.3 2.2h147.8c9.8 0 19.6-2.2 25-6.7v4.4 2.2H699c9.8 0 19.6-1.1 26.1-5.5 10-6.1 16.3-17 17.2-29.9 0-.4.1-.8.1-1.1l-.1-.1c.1-.7.2-1.5.2-2.2h0zM400.2 337h-21.7v3.3 6.7 6.7 12.2h-35.7L322 341.4l-.1.1-1-1.2-23.9 25.5h-69.5v-77.6h70.6l19.3 21.6 4.1 4.5.6-.6 22.8-25.5h57.6c11.2 0 23.7 2.8 28.4 14.4.6 2.3.9 4.9.9 7.8-.1 22.1-15.3 26.6-31.6 26.6h0zm108.7-1.1c2.2 3.3 3.3 7.8 3.3 14.4v15.5h-18.5v-10c0-4.4 0-12.2-3.3-15.5-2.2-3.3-6.5-3.3-13-3.3h-19.6v28.8h-18.5v-78.7h41.3c8.7 0 16.3 0 21.7 3.3s9.8 8.9 9.8 17.7c0 12.2-7.6 18.8-13 21.1 5.4 2.2 8.7 4.4 9.8 6.7zm74.9-32.2h-42.4v14.4h41.3v15.5h-41.3v15.5h42.4v16.6H523V287h60.8v16.7h0zm45.7 62.1h-34.8v-16.6h34.8c3.3 0 5.4 0 7.6-2.2 1.1-1.1 2.2-3.3 2.2-5.5s-1.1-4.4-2.2-5.5-3.3-2.2-6.5-2.2c-17.4-1.1-38 0-38-24.4 0-11.1 6.5-23.3 26.1-23.3h35.9v18.8h-33.7c-3.3 0-5.4 0-7.6 1.1s-2.2 3.3-2.2 5.5c0 3.3 2.2 4.4 4.3 5.5 2.2 1.1 4.3 1.1 6.5 1.1h9.8c9.8 0 16.3 2.2 20.6 6.7 3.3 3.3 5.4 8.9 5.4 16.6 0 16.6-9.8 24.4-28.2 24.4h0zm93.4-7.8c-4.3 4.4-12 7.8-22.8 7.8h-34.8v-16.6h34.8c3.3 0 5.4 0 7.6-2.2 1.1-1.1 2.2-3.3 2.2-5.5s-1.1-4.4-2.2-5.5-3.3-2.2-6.5-2.2c-17.4-1.1-38 0-38-24.4 0-10.5 5.9-20.1 20.5-22.9 1.7-.2 3.6-.4 5.6-.4h35.9v18.8h-23.9-8.7-1.1c-3.3 0-5.4 0-7.6 1.1-1.1 1.1-2.2 3.3-2.2 5.5 0 3.3 1.1 4.4 4.4 5.5 2.2 1.1 4.3 1.1 6.5 1.1h1.1 8.7c4.7 0 8.3.6 11.6 1.8 3 1.1 13 5.7 15.2 17.5.2 1.2.3 2.5.3 4-.1 6.7-2.2 12.2-6.6 16.6h0z"/></g><path d="M30.6 472.9V48.1c0-24.3 19.3-43.9 43.1-43.9H43.1C19.3 4.1 0 23.8 0 48.1v424.8c0 24.3 19.3 44 43.1 44h30.6c-23.8 0-43.1-19.7-43.1-44z" opacity=".15" fill="#202121" enable-background="new"/></svg>&nbsp;&nbsp;Ending in <strong>'.$cardtype[1].'</strong>';
										if($c4 == "") echo 'No credit card on file!';
									?>
									</span>
								</div>
								<div class="section_form_14">
									<a class="cc" href="agent_cc.php?agent=<?php echo $mem_id; ?>">Update Credit Card</a>
								</div>
							<?php } ?>
							<div class="clear"></div>
						</div>
						<div class="section_footer">
							<div class="section_navigate">
								&nbsp;
							</div>
							<div class="section_submit">
								<input name="Submitact" type="submit" value="Save" class="section_submit_button_md" />
							</div>
							<div class ="clear"></div>
						</div>
					</form>
				</div>				
				<div id="rc_footer">
					<hr class="hr_top" />
					<div id="rc_footer_content">
						&copy;2023 Look2 Home Marketing. All Rights Reserved<br>
						<a href="terms.php">Terms/Privacy Policy</a>
					</div>
				</div>
			</div>
		</div>
		<div class="clear"></div>
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