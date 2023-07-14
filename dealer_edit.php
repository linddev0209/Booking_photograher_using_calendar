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
			header("location: index.php?flag=failedauth");
			exit();
	}
}

if ($_GET['task'] == "logout"){ logout(); exit(); }

$fname = $_SESSION['SESS_FIRST_NAME'];
$lname = $_SESSION['SESS_LAST_NAME'];
$mem_id = $_SESSION['SESS_MEMBER_ID'];

$d_id = $_GET['dealer'];

if ($d_id!="") {
	if(!($dealer_db = mysqli_query($link,"select * from users where id = '$d_id'"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
	}
	$deal = mysqli_fetch_array($dealer_db);
}

if ($_GET['task'] == "logout"){ logout(); exit(); }

if($_POST['Submitdealer']){
	$d_id = $_POST['dealer'];
	if($d_id!="") {
		if(!($dealer_db = mysqli_query($link,"select * from users where id = '$d_id'"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
			exit();
		}
		$deal = mysqli_fetch_array($dealer_db);
	}
	if($_POST['pass']!="") {
		$passencrypt = SHA1($_POST['pass']);
	} else {
		$passencrypt = $deal['pass'];
	}
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
	
	$d_fp = $_POST['d_fp']; 
	$d_photo = $_POST['d_photo'];
	$d_aerial = $_POST['d_aerial'];
	$d_twilight = $_POST['d_twilight'];
	$d_3d = $_POST['d_3d'];
	
	$active = $_POST['active'];
	$level = $_POST['level'];
	
	if($site!="" && (substr($site,0,7)!="http://")) $site = "http://".$site;  
	
	if($d_id!="") {
		if(!($dealer_update = mysqli_query($link,"UPDATE users SET email='$email',pass='$passencrypt',firstname='$firstname',lastname='$lastname',site='$site',address='$address',city='$city',state='$state',zip='$zip',office_phone='$officephone',cell_phone='$cellphone',level='$level',active='$active',d_fp='$d_fp',d_photo='$d_photo',d_aerial='$d_aerial',d_twilight='$d_twilight',d_3d='$d_3d' WHERE id = '$d_id'"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
			exit();
		}
	} else {
		if(!($dealer_update = mysqli_query($link,"INSERT INTO users SET email='$email',pass='$passencrypt',firstname='$firstname',lastname='$lastname',site='$site',address='$address',city='$city',state='$state',zip='$zip',office_phone='$officephone',cell_phone='$cellphone',level='$level',active='$active',d_fp='$d_fp',d_photo='$d_photo',d_aerial='$d_aerial',d_twilight='$d_twilight',d_3d='$d_3d'"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
			exit();
		}	
	}

	if($d_id!="") {
		$photo_id = $d_id; 
	} else {
		$photo_id = mysqli_insert_id($link); 
	}
	if($_FILES['uphoto']['name'] != NULL){ 
		$file_name = $_FILES['uphoto']['name'];
      	$file_size = $_FILES['uphoto']['size'];
      	$file_tmp = $_FILES['uphoto']['tmp_name'];
      	$file_type = $_FILES['uphoto']['type'];
      	$file_ext=strtolower(end(explode('.',$_FILES['uphoto']['name'])));
      	$expensions= array("jpg");
      	if(in_array($file_ext,$expensions)=== false){
          	header("Location: dealer_edit.php?dealer=".$photo_id."&flag=notvalid");
		  	exit;
      	}
		$verifyimg = getimagesize($_FILES['uphoto']['tmp_name']);

		if($verifyimg['mime'] != 'image/jpeg') {
    		header("Location: dealer_edit.php?dealer=".$photo_id."&flag=notvalid");
			exit; 
		}
		$extension = strrchr($_FILES['uphoto']['name'],'.');  
		$save_path = 'floorplan/profile/'; 
		if(!is_dir($save_path)) mkdir($save_path);
		$filename = $save_path . $photo_id . $extension; 
		move_uploaded_file($_FILES['uphoto']['tmp_name'],$filename);
	} 
	
	if($d_id!="") {
		$flag = "updated";
	 } else {
		$flag = "created";
	}
	header("Location: dealer_edit.php?dealer=".$photo_id."&flag=".$flag."");
	exit();	
}
?>

<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title>Manage Dealer | Look2 Home Marketing</title>
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
					<li>
						<?php if ($_SESSION['SESS_LEVEL']=="admin") { ?>
							<a href="admin_index.php">
						<?php } if ($_SESSION['SESS_LEVEL']=="staff") { ?>
							<a href="staff_index.php">
						<?php } ?> 
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
					<?php if ($_SESSION['SESS_LEVEL']=="admin" || $_SESSION['SESS_LEVEL']=="staff") { ?>
						<li class="active_menu">
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
				<form action="dealer_edit.php" method="post" name="dedit" id="dedit" enctype="multipart/form-data">
					<div class="section_container">
						<div class="section_header">
							<svg xmlns="http://www.w3.org/2000/svg" class="section_icon" viewBox="0 0 36 36"><circle cx="16.86" cy="9.73" r="6.46"/><path d="M21 28h7v1.4h-7zm-6 2v3a1 1 0 0 0 1 1h17a1 1 0 0 0 1-1V23a1 1 0 0 0-1-1h-7v-1.47a1 1 0 0 0-2 0V22h-2v-3.58a32.12 32.12 0 0 0-5.14-.42 26 26 0 0 0-11 2.39 3.28 3.28 0 0 0-1.88 3V30zm17 2H17v-8h7v.42a1 1 0 0 0 2 0V24h6z"/></svg><h1>Manage Dealer</h1>
						</div>
						<?php if($_GET['flag']=="updated"){ ?> 
							<div class="section_flag_row">
								<div class="section_full">
									<?php echo "<strong>Account Updated Successfully!</strong>"; ?>
								</div>
							</div>
						<?php } if($_GET['flag']=="created"){ ?> 
							<div class="section_flag_row">
								<div class="section_full">
									<?php echo "<strong>Account Created Successfully!</strong>"; ?>
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
								<input name="fname" type="text" id="fname" value="<?php echo $deal['firstname']; ?>" size="32" />
							</div>
							<div class="section_form_14">
								Address:
							</div>
							<div class="section_form_36">
								<input name="address" type="text" id="address" value="<?php echo $deal['address']; ?>" size="32" />
							</div>
							<div class="clear"></div>
							<div class="section_form_14">
								Last Name:
							</div>
							<div class="section_form_36">
								<input name="lname" type="text" id="lname" value="<?php echo $deal['lastname']; ?>" size="32" />
							</div>
							<div class="section_form_14">
								City:
							</div>
							<div class="section_form_36">
								<input name="city" type="text" id="city" value="<?php echo $deal['city']; ?>" size="32" />
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
								<input name="state" type="text" id="state" value="<?php echo $deal['state']; ?>" size="4" maxlength="2" />
							</div>
							<div class="section_form_10">
								Zip:
							</div>
							<div class="section_form_14">
								<input name="zip" type="text" id="zip" value="<?php echo $deal['zip']; ?>" size="10" maxlength="5" />
							</div>
							<div class="clear"></div>
							<div class="section_form_14">
								Email Address:
							</div>
							<div class="section_form_36">
								<input name="email" type="text" id="email" value="<?php echo $deal['email']; ?>" size="32" />
							</div>
							<div class="clear"></div>
							<div class="section_form_14">
								Cell Phone:
							</div>
							<div class="section_form_36">
								<input name="cell" type="text" id="cell" value="<?php echo $agent_profile['cell_phone']; ?>" size="24" maxlength="12" /><br>ex. 555-555-5555
							</div>
							<div class="section_form_14">
								Office Phone:
							</div>
							<div class="section_form_36">
								<input name="officephone" type="text" id="officephone" value="<?php echo $agent_profile['office_phone']; ?>" size="24" maxlength="12" /><br>ex. 555-555-5555
							</div>
							<div class="clear"></div>
							<div class="section_form_14">
								Dealer Photo:
							</div>
							<div class="section_form_36">
								Upload new photo - <strong>.jpg only</strong><br><input name="uphoto" type="file" id="uphoto" size="18" />
							</div>
							<div class="section_form_14">
								Current Photo:
							</div>
							<div class="section_form_36">
								<span class="agent_pic_order_80">
									<?php if(file_exists("floorplan/profile/".$d_id.".jpg")){ ?><img src="floorplan/profile/<?php echo $d_id; ?>.jpg" width="80px" alt="<?php echo $deal['firstname']." ". $deal['lastname']; ?>"><?php } ?>
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
							<div class="section_form_14">
								Active:
							</div>
							<div class="section_form_36">
								<select name="active" id="active">
                    				<option value="y" selected="selected">Yes</option>
                    				<option value="n" <?php if($deal['active']=='n') echo "selected" ?>>No</option>
                  				</select>
							</div>
							<div class="clear"></div>
							<div class="section_form_14">
								Calendar:
							</div>
							<?php if ($deal['g_token']=="") { ?>
							<div class="section_form_36">
								Google Calendar not linked!
							</div>
							<?php
								} else { 
							?>
							<div class="section_form_36">
								Google Calendar is linked! 
							</div>
							<?php	} ?>
							<div class="clear"></div>
						</div>
						<div class="section_titles">
							<div class="section_full"?>
								<h1>Services Offered:</h1>
							</div>
						</div>
						<div class="section_result_row">
							<div class="order_additional_sub">
								<div class="d_fp">
									<input type="checkbox" name="d_fp" id="d_fp" value="y" <?php if($deal['d_fp']=="y") echo "checked"; ?> /><label for="d_fp"></label><div class="clear"></div>
								</div>
								<span>
									Floor Plans: 
									<svg xmlns="http://www.w3.org/2000/svg" class="order_additional_icon" viewBox="0 0 800 800"><path d="M15.9 800H400h384.1c9.6 0 15.9-6.4 15.9-15.9V496 208 16c0-9.6-6.4-16-15.9-16h-304H304 16C6.4 0 0 6.4 0 15.9V400v128.1 192V784c0 9.6 6.4 16 15.9 16zm16-256h160c-7.9 84.8-75 152.1-160 160 0 0 0-160 0-160zm0-512.1h256v96H272c-9.6 0-15.9 6.4-15.9 15.9 0 9.6 6.4 15.9 15.9 15.9h63.9c9.6 0 15.9-6.4 15.9-15.9 0-9.6-6.4-15.9-15.9-15.9H320v-96h144V208c0 9.6 6.4 15.9 15.9 15.9h63.9v15.9c0 9.6 6.4 15.9 15.9 15.9 9.6 0 15.9-6.4 15.9-15.9v-63.9c0-9.6-6.4-15.9-15.9-15.9-9.6 0-15.9 6.4-15.9 15.9v15.9h-48v-160h272.1v160H704v-15.9c0-9.6-6.4-15.9-15.9-15.9s-15.9 6.4-15.9 15.9v63.9c0 9.6 6.4 15.9 15.9 15.9s15.9-6.4 15.9-15.9v-15.9h63.9v256h-96V464c0-9.6-6.4-15.9-15.9-15.9-9.6 0-15.9 6.4-15.9 15.9v63.9c0 9.6 6.4 15.9 15.9 15.9 9.6 0 15.9-6.4 15.9-15.9V512h96v256h-352V512h96v15.9c0 9.6 6.4 15.9 15.9 15.9s15.9-6.4 15.9-15.9V464c0-9.6-6.4-15.9-15.9-15.9s-15.9 6.4-15.9 15.9v15.9H400c-9.6 0-15.9 6.4-15.9 15.9v272.1H31.9v-32.6c107.6-8.1 192-97.5 192-207.4 0-9.6-6.4-15.9-15.9-15.9H31.9v-96H304c9.6 0 15.9-6.4 15.9-15.9V288h15.9c9.6 0 15.9-6.4 15.9-15.9 0-9.6-6.4-15.9-15.9-15.9h-63.9c-9.6 0-15.9 6.4-15.9 15.9 0 9.6 6.4 15.9 15.9 15.9h15.9v96h-256V31.9h.1 0z"/></svg>
								</span>
							</div>
							<div class="order_additional_sub">
								<div class="d_photo">
									<input type="checkbox" name="d_photo" id="d_photo" value="y" <?php if($deal['d_photo']=="y") echo "checked"; ?> /><label for="d_photo"></label><div class="clear"></div>
								</div>
								<span>
									Photography: 
									<svg xmlns="http://www.w3.org/2000/svg" class="order_additional_icon" viewBox="0 0 800 800"><path d="M745 170.9v-14.4c0-16-13.1-29-29.1-29h-71.7c-16 0-29.1 13.1-29.1 29v14h-42.6l-18.6-59.1C547.7 91.7 529.1 78 508.3 78H292.4c-20.8 0-39.4 13.4-45.5 33.5l-18.5 59.1H60.2C27 170.5 0 197.3 0 230.4v.1 390.1c0 33.1 26.9 60 60.1 60h.1 173.9c-50.6-45.3-82.6-111.1-82.6-184.5 0-136.9 111.5-248 248.9-248s248.9 111.1 248.9 248c.1 70.4-29.9 137.6-82.6 184.5h173.2c33.2 0 60.2-26.8 60.2-59.9v-.1-390c.3-31.3-24-57.2-55.1-59.7h0zm-467.7-44.4c2.6-8.6 10.9-14.4 19.9-14.4h206.6c9 0 17 5.8 19.8 14.4 5 15.8-3.8 32.6-19.6 37.6-3 .9-6.1 1.4-9.2 1.4h-189c-20.1 0-34.5-19.8-28.5-39zM167.2 270.8H63.7v-50.4h103.5v50.4zM532.3 681c58.5-41 96.8-108.6 96.8-185.3 0-125-101.6-226.3-227.1-226.3S175 370.6 175 495.7c0 76.6 38.3 144.3 96.8 185.3 37 25.6 81.7 41 130.3 41 48.6-.3 93.3-15.4 130.2-41zM231.6 495.7c0-93.9 76.6-170.2 170.8-170.2s170.8 76.2 170.8 170.2-76.5 170.2-170.8 170.2c-94.6-.3-170.8-76.3-170.8-170.2zm170.5 143.6c79.7 0 144.4-64.5 144.4-143.9s-64.6-143.9-144.4-143.9-144.4 64.4-144.4 143.9c.3 79.4 64.6 143.9 144.4 143.9zm53.7-240.7a19.25 19.25 0 0 1 19.3 19.3c0 10.6-8.7 19.3-19.3 19.3a19.25 19.25 0 0 1-19.3-19.3c0-10.7 8.3-19.3 19.3-19.3zm-53.7 32.3c35.7 0 64.9 28.9 64.9 64.7 0 35.6-28.9 64.7-64.9 64.7s-64.9-28.9-64.9-64.7c.2-35.8 29.2-64.7 64.9-64.7z"/></svg>
								</span>
							</div>
							<div class="order_additional_sub">
								<div class="d_aerial">
									<input type="checkbox" name="d_aerial" id="d_aerial" value="y" <?php if($deal['d_aerial']=="y") echo "checked"; ?> /><label for="d_aerial"></label><div class="clear"></div>
								</div>
								<span>
									Aerials: 
									<svg xmlns="http://www.w3.org/2000/svg" class="order_additional_icon" viewBox="0 0 512 512" ><path d="M296.81 161.734l7.529-3.341 6.888-3.524c-21.114-32.128 15.119-56.904 43.724-60.497 29.016-3.623 68.628 5.714 82.732 34.242 14.782 29.901-16.751 51.435-42.678 55.744-12.556 2.082-25.562 1.587-37.964-1.182-4.654-1.037-5.073-1.32-8.04 2.036-3.281 3.722-6.24 7.673-8.994 11.793 37.384 12.578 96.699 9.832 119.102-28.185 14.386-24.409.512-51.237-20.138-66.607-20.931-15.553-47.796-22.686-73.648-22.686-28.811 0-64.54 9.498-78.804 37.148-7.238 14.036-5.675 30.328 2.357 43.631 2.625 4.419 3.533 3.244 7.934 1.428zM93.855 199.812c24.714 6.873 51.686 5.447 75.92-2.662-2.296-3.493-9.306-15.813-13.592-14.608-5.18 1.465-10.511 2.479-15.859 3.067-12.494 1.38-25.325.533-37.4-3.074-12.464-3.837-24.775-10.71-31.534-22.273-7.544-12.99-4.538-27.98 4.554-39.33 18.52-22.51 52.442-30.1 80.276-25.668 27.705 4.409 60.55 28.589 41.557 59.292 3.585 1.945 17.079 10.397 20.359 8.581 2.701-1.495 4.638-8.314 5.614-10.923 5.889-15.515 2.265-32.198-8.36-44.676-17.285-20.306-46.629-27.614-72.343-27.552-22.853 0-46.278 5.614-65.913 17.491-19.68 11.907-37.995 33.181-33.288 57.789 4.592 24.028 28.117 38.46 50.009 44.546zM371.587 338.29c7.36 14.706 26.018 21.785 41.542 16.125 24.585-8.97 14.577-37.072-5.332-44.356-10.839-3.257-20.893-5.172-31.106-11.16-19.566-11.457-36.774-28.269-49.262-47.156-5.476-8.284-11.678-18.551-11.373-28.864.298-10.16 5.408-20.13 10.74-28.544 11.587-18.292 29.733-35.699 51.374-40.772 7.865-1.632 18.513-3.829 18.841-14.21.603-19.161-31.732-21.8-42.952-12.845-2.342 1.861-3.371 3.509-4.447 6.079-2.22 5.302-4.096 9.566-8.414 13.876-15.866 15.805-39.39 25.21-60.641 30.977-22.694 6.148-43.098 3.57-65.082-4.317-18.422-6.614-45.462-17.461-54.341-36.538-.9-1.93-1.037-4.035-2.014-5.934-3.906-6.996-12.99-9.421-20.451-9.421-10.244-.03-29.108 6.499-25.919 20.13 1.793 7.658 9.146 10.405 15.919 11.724 16.851 3.296 31.077 13.395 42.526 25.928 13.387 14.652 27.849 36.248 19.832 56.644-8.047 20.466-24.73 38.46-41.64 52.046-12.815 10.298-26.767 17.902-42.785 21.625-14.532 3.379-30.832 17.788-22.701 34.044 4.89 9.778 16.69 13.623 26.98 12.799 7.994-.64 23.868-5.789 26.439-14.806 2.8-9.847 9.61-18.071 16.995-24.92 16.704-15.485 37.91-26.508 59.108-34.44 21.496-8.048 41.939-11.701 64.418-5.927 22.472 5.774 44.471 15.828 63.548 29.032 12.944 8.971 23.173 19.146 30.198 33.181z"/><path d="M138.668 121.23c.733 0-2.326-.008 0 0h0zm361.144 183.749c-22.77-37.453-68.056-58.178-111.009-58.178-14.829 0-29.772 2.418-43.655 7.696 3.982 5.187 8.376 10.054 13.029 14.654 3.707 3.662 5.263 1.991 10.015.778 7.566-1.976 15.401-2.876 23.212-2.876 20.504 0 41.182 6.278 58.101 17.888 17.964 12.258 32.434 31.724 33.792 53.99 1.244 20.488-9.45 39.314-26.301 50.543-35.882 23.913-88.705 14.752-118.416-14.982-15.08-15.088-24.554-36.614-19.955-58.078a54.53 54.53 0 0 1 1.976-6.95c1.213-3.41-.206-2.96-3.067-4.569-3.951-2.212-7.986-4.272-12.082-6.202-2.228-1.045-6.743-3.928-8.497-2.952-.474.267-.672 1.594-1.038 2.029-20.947 44.898 9.695 92.382 48.91 114.418 37.652 21.069 87.248 25.157 125.685 3.685 40.552-22.662 53.526-71.031 29.3-110.894zm-308.053.29c-3.059 1.701-1.396 2.716-.251 5.774 1.242 3.31 2.113 6.743 2.67 10.236 3.493 22.022-7.186 43.571-23.189 58.201-30.954 28.299-84.006 35.935-118.743 10.008-17.096-12.754-26.356-33.592-22.756-54.813 3.692-21.763 19.177-39.734 37.605-50.94 17.941-10.908 39.421-16.133 60.36-14.676 5.385.397 10.74 1.205 15.98 2.517 4.523 1.13 3.882.931 7.147-1.243 5.271-3.516 9.786-10.153 13.677-15.058-13.15-4.584-27.018-6.788-40.939-6.788-43.503 0-89.407 21.152-111.917 59.506-24.836 42.311-7.468 92.343 36.202 113.152 40.489 19.298 91.07 12.922 128.004-11.427 37.102-24.448 62.115-72.922 38.064-115.121-7.52 3.089-14.812 6.721-21.914 10.672z"/></svg>
								</span>
							</div>
							<div class="clear"></div>
							<div class="order_additional_sub">
								<div class="d_twilight">
									<input type="checkbox" name="d_twilight" id="d_twilight" value="y" <?php if($deal['d_twilight']=="y") echo "checked"; ?> /><label for="d_twilight"></label><div class="clear"></div>
								</div>
								<span>
									Twilight: 
									<svg xmlns="http://www.w3.org/2000/svg" class="order_additional_icon" viewBox="0 0 800 800" ><path d="M384.5 152.8v137.9h31V152.8h-31zm-110.3 92.4l-28.7 11.9 26.4 63.7 28.7-11.9-26.4-63.7h0zm251.6 0l-26.4 63.7 28.7 11.9 26.4-63.7-28.7-11.9h0zM92.8 265l-21.9 21.9 97.5 97.5 21.9-21.9L92.8 265h0zm614.4 0l-97.5 97.5 21.9 21.9 97.5-97.5-21.9-21.9h0zM400 357.9c-36 0-71.4 9.5-102.6 27.5-59 34.1-96.9 94.9-101.9 162.2h409.1c-5.1-67.3-43-128.1-101.9-162.2-31.3-18-66.7-27.5-102.7-27.5h0zM73.3 443.1l-11.9 28.7 63.7 26.4 11.9-28.7-63.7-26.4h0zm653.4 0L663 469.5l11.9 28.7 63.7-26.4-11.9-28.7h0zM0 578.6v31h800v-31H0z"/></svg>
								</span>
								
							</div>
							<div class="order_additional_sub">
								<div class="d_3d">
									<input type="checkbox" name="d_3d" id="d_3d" value="y" <?php if($deal['d_3d']=="y") echo "checked"; ?> /><label for="d_3d"></label><div class="clear"></div>
								</div>
								<span>
									3D: 
									<svg xmlns="http://www.w3.org/2000/svg" class="order_additional_icon" viewBox="0 0 800 800"><path d="M266.7 133.3C266.7 59.7 326.4 0 400 0s133.3 59.7 133.3 133.3S473.6 266.7 400 266.7 266.7 207 266.7 133.3zM400 320c-103.1 0-186.7 83.6-186.7 186.7v106.7c0 14.7 11.9 26.7 26.7 26.7h320c14.7 0 26.7-11.9 26.7-26.7V506.7C586.7 403.6 503.1 320 400 320zM74.3 570.4c-15.1 15.6-21 30-21 42.9s6.1 27.8 21.7 43.5 39.7 31.5 71.5 45.1c63.5 27.2 153.1 44.7 253.5 44.7s190-17.5 253.5-44.7c31.8-13.7 55.8-29.2 71.5-45.1s21.7-30.5 21.7-43.5c0-13.1-5.9-27.4-21.1-42.9-15.1-15.6-38.6-31-69.7-44.6l21.4-48.9c35 15.3 64.9 34.1 86.5 56.3s36.2 49.4 36.2 80.1c0 31.1-14.8 58.5-37 81s-52.7 41.3-88.5 56.6c-71.6 30.7-168.7 49-274.5 49s-202.9-18.3-274.5-49c-35.7-15.3-66.4-34.2-88.5-56.6s-37-49.9-37-81c0-30.7 14.5-57.8 36.2-80.1s51.5-41 86.5-56.3l21.4 48.9c-31.1 13.5-54.4 28.9-69.8 44.6h0z"/></svg>
								</span>
							</div>
							<div class="clear"></div>
						</div>
						<div class="section_footer">
							<div class="section_navigate">
								<input name="dealer" type="hidden" value="<?php echo $d_id; ?>" /><input name="level" type="hidden" value="dealer" />
							</div>
							<div class="section_submit">
								<input class="section_submit_button_md" name="Submitdealer" type="submit" id="Submitdealer" value="Submit" />
							</div>
							<div class ="clear"></div>
						</div>
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