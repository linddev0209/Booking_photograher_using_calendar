<?php 
ob_start("ob_gzhandler");
$host = "manager.look2homes.com"; // Sub domain
if(empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off"){
    $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $redirect);
    exit();
}

require("includes/cdb.php");

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
                if($_SESSION['SESS_LEVEL'] == 'admin') {
					header("location: admin_index.php");
					exit();
				}elseif($_SESSION['SESS_LEVEL'] == 'dealer'){
					header("location: dealer_index.php");
					exit();
				}elseif($_SESSION['SESS_LEVEL'] == 'd_assist'){
					header("location: photographer_index.php");
					exit();
				}elseif($_SESSION['SESS_LEVEL'] == 'staff'){
					header("location: staff_index.php");
					exit();
				}elseif($_SESSION['SESS_LEVEL'] == 'agent'){
					header("location: enter_order1.php");
					exit();
				}
        }
}


$link = mysqli_connect($connection, $sqluser, $sqlpw, $dbname);

if(mysqli_connect_errno()) {
    $msg = "Database connection failed: ";
    $msg .= mysqli_connect_error();
    $msg .= " : " . mysqli_connect_errno();
    exit($msg);
 }

if($_POST['Submit']) {
	if(isset($_POST["email"]) && isset($_POST["action"]) && isset($_POST["user_id"]) && ($_POST["action"]=="update")) {
		$error="";
		$pass1 = mysqli_real_escape_string($link,$_POST["pass1"]);
		$pass2 = mysqli_real_escape_string($link,$_POST["pass2"]);
		$email = $_POST["email"];
		$user_id = $_POST["user_id"];
		$curDate = date("Y-m-d H:i:s");
		if ($pass1!=$pass2){
			header("location: reset_password.php?flag=mismatch");
			exit();
  		} else {
			$reset_pass =  SHA1($pass1);
			if(!($dba = mysqli_query($link,"UPDATE users SET pass = '$reset_pass' WHERE email ='$email' AND id = '$user_id' "))){
				printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
				exit();
			}

			if(!($dba2 = mysqli_query($link,"DELETE FROM password_reset_temp WHERE email='$email' AND user_id='$user_id'"))){
				printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
				exit();
			}
			header("Location: index.php?flag=reset_complete");
			exit();	
		}
	}
}

if (isset($_GET["key"]) && isset($_GET["email"]) && isset($_GET["action"]) 
&& ($_GET["action"]=="reset") && !isset($_POST["action"])){
	$hashed_key = $_GET["key"];
	$email = $_GET["email"];
	$curDate = date("Y-m-d H:i:s");
	if(!($pk_result = mysqli_query($link,"SELECT * FROM password_reset_temp WHERE email = '".$email."' AND reset_key = '".$hashed_key."'"))){
			printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
			exit();
	}
	$pk_num = mysqli_num_rows($pk_result);
	if ($pk_num ==""){
   		header("location: reset_pwd.php?flag=norecord");
		exit();
	}else{
  		$pk_reset = mysqli_fetch_array($pk_result);
  		$exp_date = $pk_reset['exp_date'];
		$user_id = $pk_reset['user_id'];
		$user_email = $pk_reset['email'];
  		if ($exp_date >= $curDate){
  	
  	$gen_form='<div class="section_titles">
				<div class="section_full">
					<h1>Reset Password</h1>
				</div>
			</div>
			<form id="update" name="update" method="post" action="">
				<div class="section_result_row">
					<div class="section_form_25">&nbsp;</div>
					<div class="section_form_50"><strong>Enter New Password:</strong></div>
					<div class="clear"></div>
					<div class="section_form_25"><input type="hidden" name="action" value="update" />&nbsp;</div>
					<div class="section_form_50"><input type="password" name="pass1" maxlength="15" required /></div>
					<div class="clear"></div>
					<div class="section_form_25">&nbsp;</div>
					<div class="section_form_50"><strong>Re-Enter New Password:</strong></div>
					<div class="clear"></div>
					<div class="section_form_25"><input type="hidden" name="email" value="'.$user_email.'"/><input type="hidden" name="user_id" value="'.$user_id.'"/>&nbsp;</div>
					<div class="section_form_50"><input type="password" name="pass2" maxlength="15" required/></div>
					<div class="clear"></div>					
					<div class="section_form_25">&nbsp;</div>
					<div class="section_form_50"><input name="Submit" type="submit" value="Reset Password" class="section_submit_button_md" /></div>
					<div class="clear"></div>
					<div class="section_form_25">&nbsp;</div>
					<div class="section_form_50">&nbsp;</div>
					<div class="clear"></div>
				</div>
			</form>';
		} else {
			if(!($dba2 = mysqli_query($link,"DELETE FROM password_reset_temp WHERE email='$user_email' AND user_id='$user_id'"))){
				printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
				exit();
			}
			header("location: reset_pwd.php?flag=expired");
			exit();
		}
	}
} else {
	header("location: reset_pwd.php");
	exit();
}

?>

<!doctype html>
<html>
	
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title>Reset Password | Look2 Home Marketing</title>
		<link href="css/l2_v3.css" rel="stylesheet" type="text/css" />
</head>

<body>
	<div id="login_container">
		<div id="login_logo">
			<svg xmlns="http://www.w3.org/2000/svg" height="100px" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 900.8 554.7" ><g transform="matrix(1.333333 0 0 -1.333333 0 632.204)"><defs><path id="A" d="M-123.8-61.1h990.1v694.9h-990.1z"/></defs><clipPath id="B"><use xlink:href="#A"/></clipPath><g clip-path="url(#B)"><path d="M16.5 377.59v-142.1h60.7v-16.8H0v158.9h16.5zm456.8-157.5l.4-1.3h0l-.4 1.3zm-183.4-9.6c30.5-46.6 73.4-71.6 128.6-75v-22c-30.8-.3-60.3 8.2-88.3 25.4-28.9 17.7-50.7 41.5-65.3 71.3l25 .3zm53 99.8c-9 9.3-19.8 14-32.4 14s-23.4-4.7-32.4-14-13.5-20.6-13.5-33.8c0-12.3 4.5-23 13.6-31.9 9-8.9 19.8-13.4 32.3-13.4s23.3 4.5 32.3 13.4 13.6 19.6 13.6 31.9c0 13.2-4.5 24.4-13.5 33.8m-75.9 12.2c12.1 11.6 26.5 17.4 43.5 17.4 16.9 0 31.4-5.8 43.5-17.4 12.6-12.2 18.9-27.5 18.9-46.1 0-17.3-6.2-31.8-18.5-43.4-12.1-11.4-26.7-17-43.9-17s-31.8 5.7-43.9 17c-12.3 11.7-18.5 26.1-18.5 43.4 0 18.6 6.3 34 18.9 46.1m-73.7-78c9 8.9 13.6 19.6 13.6 31.9 0 13.2-4.5 24.5-13.5 33.8s-19.8 14-32.4 14-23.4-4.7-32.4-14-13.5-20.6-13.5-33.8c0-12.3 4.5-23 13.6-31.9s19.8-13.4 32.3-13.4 23.3 4.5 32.3 13.4m30.1 32c0-17.3-6.2-31.8-18.5-43.4-12.1-11.4-26.7-17-43.9-17s-31.8 5.7-43.9 17c-12.3 11.6-18.5 26.1-18.5 43.4 0 18.5 6.3 33.9 18.9 46.1 12.1 11.5 26.6 17.3 43.5 17.3s31.4-5.8 43.5-17.4c12.6-12.1 18.9-27.5 18.9-46m305-216H419.6l80.8 88.8c9.9 12.5 12.3 25.2 7.1 38h0c-2.4 6-6 10.7-10.7 14.3-5.6 4.3-12.4 6.5-20.3 6.5-11 0-19.6-3.4-25.7-10.3-6-6.6-9.4-16.1-10.3-28.4h-16.2c.1 15.3 4.4 27.7 12.9 37.2 8.4 9.6 20.2 15.2 35.3 17l-52.3 46.1v-50.8h-15.7v162.8h15.7v-91.5l53.5 46.4h23.1l-63.8-55.7 68.1-61.8c16.8-10.2 25.2-24.6 25.3-43.1 1.3-16-9.5-35.5-32.3-58.5L456 76.39h72.4v-15.9zm-3.3 342.5c-14.7 13.6-31.2 24.1-49.7 31.5-18.4 7.4-36.7 11.1-54.9 11-30.4-.1-58.1-8.5-83.1-24.9-27.7-18.2-47.3-43.8-59-76.6h-26.2c13.8 39.4 36.9 70.5 69.4 93.3 14.8 10.4 30.8 18.4 47.9 23.9 16.8 5.3 33.8 8 51 8 23 0 45.1-4.4 66.5-13.2 21.1-8.6 39.8-20.9 56.1-36.8 16.7-16.3 29.6-35.1 38.7-56.5 9.6-22.6 14.4-46.6 14.4-72.2-.9-56.5-21.4-101.6-61.3-135.2v32.7c23.4 24.4 36.3 58.6 38.7 102.5.5 22.3-3.8 43.5-12.9 63.5-8.3 18.4-20.2 34.7-35.6 49" fill-rule="evenodd"/></g><path d="M536.586 98.993l4.03 2.96 8.938-12.17 14.588 10.714-8.938 12.17 4.03 2.96 21.843-29.741-4.03-2.96-10.418 14.185-14.588-10.714 10.418-14.185-4.03-2.96-21.843 29.741zm41.975 18.136c-1.714-1.68-2.442-3.794-2.112-6.131s1.65-4.684 3.96-7.04 4.63-3.724 6.96-4.101 4.317.31 6.102 2.06c1.714 1.68 2.371 3.724 2.112 6.131-.33 2.337-1.65 4.684-3.96 7.04s-4.63 3.724-6.96 4.101-4.317-.31-6.102-2.06zm-2.73 2.785c2.785 2.73 5.981 4.041 9.372 3.725 3.463-.247 6.698-1.976 9.918-5.262 3.15-3.214 4.884-6.555 5.062-10.021s-1.127-6.565-3.912-9.295c-2.857-2.8-5.981-4.041-9.372-3.725s-6.698 1.976-9.918 5.262c-3.15 3.214-4.884 6.555-5.062 10.021s1.127 6.565 3.912 9.295zm32.726 29.043c-.978 2.063-1.375 4.076-1.249 5.958s.774 3.635 2.062 5.419c1.697 2.351 3.796 3.55 6.354 3.677s5.319-1.003 8.319-3.169l13.541-9.775-2.692-3.73-13.378 9.658c-2.108 1.522-4.022 2.287-5.603 2.318s-2.945-.834-4.057-2.374c-1.405-1.946-1.81-3.874-1.413-5.887s1.829-3.787 4.019-5.368l12.649-9.131-2.692-3.73-13.378 9.658c-2.189 1.58-4.022 2.287-5.603 2.318s-2.945-.834-4.057-2.374c-1.346-1.865-1.81-3.874-1.273-5.864.478-2.072 1.829-3.787 4.019-5.368l12.649-9.131-2.692-3.73-22.378 16.155 2.692 3.73 3.486-2.517c-.793 1.806-1.005 3.562-.915 5.224s.76 3.274 1.931 4.896 2.571 2.707 4.201 3.257c1.711.491 3.49.564 5.463-.12zm31.065 39.875l1.976-.968-9.195-18.769c2.872-1.184 5.423-1.432 7.698-.653 2.185.823 3.915 2.536 5.279 5.32a22.2 22.2 0 0 1 1.705 4.844c.345 1.613.464 3.447.45 5.236l3.861-1.892c-.075-1.745-.374-3.491-.763-5.194s-1.047-3.273-1.839-4.89c-1.936-3.951-4.643-6.522-8.122-7.713s-7.104-.863-11.055 1.073c-4.041 1.98-6.746 4.641-8.071 8.074s-1.04 6.968.763 10.65c1.628 3.323 4.025 5.489 7.104 6.319s6.527.366 10.208-1.438zm-3.191-3.559c-2.245 1.1-4.349 1.351-6.222.71-1.919-.508-3.339-1.816-4.351-3.881-1.1-2.245-1.395-4.439-.798-6.402s1.997-3.651 4.244-4.975l7.127 14.548zm-9.033 31.935l1.708 7.2 26.592 3.353-22.23 15.037 1.708 7.2 35.904-8.517-1.131-4.768-31.525 7.478 22.424-15.083-1.154-4.865-26.81-3.404 31.525-7.478-1.108-4.67-35.904 8.517zm32.755 54.015c-.231-3.693.012-6.213.822-7.666s2.164-2.24 4.16-2.365c1.597-.1 2.926.318 3.992 1.353.967 1.042 1.554 2.408 1.666 4.204.156 2.495-.617 4.547-2.32 6.157s-4.048 2.558-6.942 2.739l-.998.063-.281-4.491zm-1.334 9.101l15.769-.988-.281-4.491-4.192.263c1.634-1.104 2.751-2.477 3.449-4.124.705-1.547.985-3.468.841-5.764-.175-2.795-1.117-5.04-2.82-6.637s-3.849-2.264-6.544-2.095c-3.094.194-5.427 1.342-6.793 3.532-1.466 2.196-2.07 5.34-1.814 9.432l.4 6.387-.399.025c-2.096.131-3.737-.467-4.922-1.796-1.285-1.322-1.903-3.187-2.059-5.682a17.59 17.59 0 0 1 .311-4.629c.305-1.522.717-2.951 1.428-4.398l-4.192.263c-.493 1.734-.892 3.362-1.097 4.878-.199 1.616-.305 3.125-.211 4.622.25 3.992 1.436 6.924 3.657 8.789 2.028 1.977 5.278 2.675 9.469 2.413zm-8.494 29.317c-.278-.512-.453-1.12-.527-1.724s-.144-1.307-.114-2.007c.111-2.598.992-4.462 2.75-5.788s4.182-1.823 7.279-1.69l14.587.624.197-4.596-27.575-1.18-.197 4.596 4.296.184c-1.741.926-2.991 2.074-3.855 3.538s-1.344 3.346-1.438 5.544c-.013.3-.03.699.057 1.003-.017.4.066.804.144 1.307l4.396.188z"/><path d="M635.283 306.664l-.59 4.562 22.513 2.911-13.546 11.961-.744 5.752 14.679-12.924 12.629 16.96.757-5.851-11.593-15.616 13.389 1.731.59-4.562-38.083-4.925zm13.578 52.054l2.131.547 5.194-20.244c2.953.964 5.054 2.432 6.278 4.502 1.127 2.044 1.331 4.471.56 7.474a22.2 22.2 0 0 1-1.849 4.791c-.785 1.45-1.886 2.923-3.058 4.274l4.165 1.069c1.076-1.376 1.983-2.898 2.792-4.445s1.329-3.169 1.777-4.913c1.094-4.262.704-7.975-1.168-11.14s-4.842-5.269-9.104-6.362c-4.359-1.118-8.144-.851-11.381.9s-5.316 4.624-6.335 8.595c-.92 3.584-.503 6.788 1.3 9.419 1.875 2.752 4.726 4.516 8.697 5.535zm-.141-4.682c-2.422-.621-4.185-1.796-5.193-3.5-1.13-1.632-1.361-3.55-.789-5.777.621-2.422 1.821-4.282 3.55-5.387s3.89-1.48 6.458-1.027l-4.026 15.692zm-24.076 11.142l7.405 2.752-3.274 8.811 3.281 1.219 3.274-8.811 14.061 5.225c2.156.801 3.364 1.57 3.717 2.341s.276 2.129-.386 3.91l-1.637 4.406 3.562 1.324 1.637-4.406c1.219-3.281 1.399-5.774.668-7.539-.766-1.671-2.772-3.164-6.052-4.383l-14.061-5.225 1.15-3.093-3.187-1.184-1.15 3.093-7.405-2.752-1.602 4.312zm2.773 14.181l-1.845 4.214 25.283 11.07 1.805-4.122-25.242-11.162zm-9.893-4.332l-1.845 4.214 5.313 2.326 1.805-4.122-5.313-2.326zm4.668 38.661l14.671 7.978 2.15-3.953-14.495-7.882c-2.284-1.242-3.802-2.637-4.467-4.136s-.515-3.24.44-4.997c1.194-2.196 2.805-3.483 4.791-3.997 2.074-.466 4.254-.077 6.626 1.213l13.705 7.452 2.198-4.041-24.247-13.185-2.198 4.041 3.778 2.054c-2.019.154-3.694.723-5.066 1.571-1.419.936-2.583 2.238-3.491 3.907-1.529 2.811-1.797 5.397-.804 7.758.905 2.313 3.07 4.401 6.409 6.217zm-13.322 23.473c-2.66-1.954-4.26-3.998-5.022-6.17-.681-2.113-.222-4.258 1.199-6.192s3.271-2.933 5.551-2.995c2.22.018 4.73.993 7.39 2.947s4.26 3.998 4.941 6.111c.622 2.194.222 4.258-1.199 6.192-1.48 2.015-3.33 3.013-5.551 2.995-2.279.063-4.65-.934-7.309-2.888zm5.9 10.042c3.788 2.782 7.201 4.049 10.297 3.718s5.958-2.199 8.504-5.665c.947-1.289 1.755-2.558 2.342-3.863.668-1.247 1.174-2.612 1.541-3.956l-3.546-2.605c-.205 1.462-.492 2.865-1.019 4.09s-1.136 2.391-1.965 3.52c-1.776 2.418-3.723 3.717-5.82 4.038s-4.446-.536-6.944-2.371l-1.773-1.302c1.822.222 3.5-.035 5.113-.711s2.862-1.869 4.105-3.561c2.013-2.74 2.564-5.686 1.711-8.917s-3-6.05-6.466-8.596-6.798-3.753-10.136-3.599-5.985 1.56-7.998 4.3c-1.184 1.612-1.927 3.299-2.171 4.982s.013 3.36.851 5.092l-3.385-2.486-2.664 3.627 19.423 14.268z"/></g></svg>
		</div>
		<div class="section_container">
			<div class="section_header">
				<svg xmlns="http://www.w3.org/2000/svg" class="section_icon" viewBox="0 0 512 512"><path d="M497.569 215.166l-55.345-13.064c-4.032-13.946-9.495-27.27-16.376-39.732l29.85-48.303a18.72 18.72 0 0 0-2.688-23.086l-31.99-31.99a18.72 18.72 0 0 0-23.076-2.678l-48.292 29.851c-12.462-6.882-25.785-12.344-39.732-16.377l-13.064-55.368A18.75 18.75 0 0 0 278.63 0h-45.237a18.75 18.75 0 0 0-18.227 14.419l-13.064 55.368c-13.946 4.032-27.27 9.484-39.732 16.377l-48.303-29.872c-7.387-4.549-16.946-3.441-23.086 2.699L58.99 90.97c-6.13 6.14-7.248 15.709-2.689 23.087l29.862 48.313c-6.882 12.462-12.344 25.786-16.367 39.721l-55.378 13.065C5.978 217.165 0 224.704 0 233.392v45.226c0 8.678 5.978 16.237 14.419 18.226l55.378 13.065c4.032 13.946 9.485 27.259 16.367 39.71l-29.872 48.324c-4.549 7.398-3.441 16.957 2.699 23.098L90.97 453.02c6.14 6.14 15.709 7.257 23.087 2.688l48.323-29.872c12.463 6.882 25.786 12.344 39.722 16.366l13.064 55.366c2 8.463 9.549 14.431 18.227 14.431h45.237c8.677 0 16.226-5.968 18.226-14.431l13.064-55.366c13.937-4.021 27.259-9.484 39.712-16.366l48.312 29.861c7.398 4.57 16.947 3.452 23.087-2.688l31.989-31.99c6.13-6.129 7.248-15.688 2.678-23.087l-29.861-48.302c6.893-12.452 12.345-25.774 16.377-39.721l55.366-13.065c8.463-2.001 14.42-9.539 14.42-18.226V233.38a18.73 18.73 0 0 0-14.431-18.214zm-241.563 87.937c-26.002 0-47.098-21.097-47.098-47.108s21.097-47.108 47.098-47.108c26.011 0 47.108 21.097 47.108 47.108s-21.097 47.108-47.108 47.108z"/></svg><h1>Management Panel</h1>
			</div>
			<?php if($_GET['flag'] == "invalid"){ ?>
           		<div class="section_error_row">
					<div class="section_full">
						<?php echo "The link is invalid or has expired. <a href='reset_pwd.php'>Click here</a> to reset password."; ?>
					</div>
				</div>
            <?php } if($_GET['flag'] == "expired"){ ?>
				<div class="section_error_row">
					<div class="section_full">
						<?php echo "The link has expired. Please retry by <a href='reset_pwd.php'>clicking here</a> to reset password."; ?>
					</div>
				</div>
			<?php } if($_GET['flag'] == "mismatch"){ ?>
				<div class="section_error_row">
					<div class="section_full">
						<?php echo "Passwords do not match, both passwords should be same."; ?>
					</div>
				</div>
            <?php } if($_GET['flag'] == "complete"){ ?>
				<div class="section_flag_row">
					<div class="section_full">
						<?php echo "<strong>Password Reset! <a href='index.php'>clicking here</a>Cleck here to log in.</a></strong>"; ?>
					</div>
				</div>
            <?php } echo $gen_form; ?>
		</div>
		<div id="rc_footer">
			<hr class="hr_top" />
			<div id="rc_footer_content">
				&copy;2023 Look2 Home Marketing. All Rights Reserved
			</div>
		</div>
		<div class="section_full">&nbsp;</div>
	</div>
</body>
</html>