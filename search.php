<?php
ob_start("ob_gzhandler");
require("includes/cdb.php");
error_reporting(E_ALL ^ E_NOTICE);  

session_start();
$link = mysqli_connect($connection, $sqluser, $sqlpw, $dbname);

if ($link -> connect_errno) {
	$msg = "Database connection failed: ";
    $msg .= mysqli_connect_error();
    $msg .= " : " . mysqli_connect_errno();
    exit($msg);
}

$link_arch = mysqli_connect($connection2, $sqluser2, $sqlpw2, $dbname2);

if ($link_arch -> connect_errno) {
    $msg = "Database connection failed: ";
    $msg .= mysqli_connect_error();
    $msg .= " : " . mysqli_connect_errno();
    exit($msg);
 }

if($_SESSION['SESS_LEVEL']!="admin") {
	if ($_SESSION['SESS_LEVEL']!="staff"){
		if ($_SESSION['SESS_LEVEL']!="dealer"){
				header("location: index.php?flag=failedauth");
				exit();
		}
	}
}
?>
<?php
if($_SESSION['SESS_LEVEL']=="admin" || $_SESSION['SESS_LEVEL']=="staff") {
if(isset($_POST['kw']) && $_POST['kw'] != '')
{
	$kws = filter_var($_POST['kw'], FILTER_SANITIZE_STRING);
	if(!($query  = mysqli_query($link,"Select * From orders WHERE id LIKE '%$kws%' ORDER BY id ASC Limit 10 "))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
	} 

	$res = mysqli_query($query);
	$count = mysqli_num_rows($query);
	
	$arch_limit = 10-$count;
	
	if(!($query_arch  = mysqli_query($link_arch,"Select * From orders WHERE id LIKE '%$kws%' ORDER BY id ASC Limit $arch_limit" ))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
		exit();
	}
 
	$res_arch = mysqli_query($query_arch);
  $count_arch = mysqli_num_rows($query_arch);
	
  $i = 0;
  
	if($count_arch > 0) {
		echo "<ul>";
		if($count_arch > 10) {
		while($row_arch = mysqli_fetch_array($query_arch)) {
			echo '<a href="schedule.php?arc=y&order_id='.$row_arch['id'].'"><li>';
			echo "<div id='rest'><strong>Order ID:</strong>&nbsp;".$row_arch['id']."&nbsp;&nbsp;";
			if($row_arch['street_num']=="0") { $street_num_arch=""; } else { $street_num_arch=$row_arch['street_num']; }
			echo "<strong>Address:</strong> ".$street_num_arch." ".$row_arch['street_name'];
			echo "</div>";
			echo "<div style='clear:both;'></div></li></a>";
			$i++;
		}
		} else {
			while($row_arch = mysqli_fetch_array($query_arch)) {
			echo '<a href="schedule.php?arc=y&order_id='.$row_arch['id'].'"><li>';
			echo "<div id='rest'><strong>Order ID:</strong>&nbsp;".$row_arch['id']."&nbsp;&nbsp;";
			if($row_arch['street_num']=="0") { $street_num_arch=""; } else { $street_num_arch=$row_arch['street_num']; }
			echo "<strong>Address:</strong> ".$street_num_arch." ".$row_arch['street_name'];
			echo "</div>";
			echo "<div style='clear:both;'></div></li></a>";
			$i++;
		}
			if($count > 0) {
				while($row = mysqli_fetch_array($query)) {
					echo '<a href="schedule.php?order_id='.$row['id'].'"><li>';
					echo "<div id='rest'><strong>Order ID:</strong>&nbsp;".$row['id']."&nbsp;&nbsp;";
					if($row['street_num']=="0") { $street_num=""; } else { $street_num=$row['street_num']; }
					echo "<strong>Address:</strong> ".$street_num." ".$row['street_name'];
					echo "</div>";
					echo "<div style='clear:both;'></div></li></a>";
				$i++;
				}
			}
		}
		echo "</ul>";
	} elseif ($count > 0) {
		echo "<ul>";
		while($row = mysqli_fetch_array($query)) {
			echo '<a href="schedule.php?order_id='.$row['id'].'"><li>';
			echo "<div id='rest'><strong>Order ID:</strong>&nbsp;".$row['id']."&nbsp;&nbsp;";
			if($row['street_num']=="0") { $street_num=""; } else { $street_num=$row['street_num']; }
			echo "<strong>Address:</strong> ".$street_num." ".$row['street_name'];
			echo "</div>";
			echo "<div style='clear:both;'></div></li></a>";
			$i++;
		}
		echo "</ul>";
	}
	else {
		echo "<div id='no_result'>No result found !</div>";
	}
}


if(isset($_POST['kw2']) && $_POST['kw2'] != '')
{

  $kws2 = filter_var($_POST['kw2'], FILTER_SANITIZE_STRING);
  if(!($query2  = mysqli_query($link,"SELECT id, street_num, street_name FROM orders WHERE CONCAT_WS(  ' ',  `street_num` ,  `street_name` ) LIKE '%$kws2%' ORDER BY street_num ASC, street_name ASC Limit 10"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
}
  $res2 = mysqli_query($query2);
  $count2 = mysqli_num_rows($query2);
	
	$arch_limit2 = 10-$count2;
	
	if(!($query_arch2 = mysqli_query($link_arch,"SELECT id, street_num, street_name FROM orders WHERE CONCAT_WS(  ' ',  `street_num` ,  `street_name` ) LIKE '%$kws2%' ORDER BY street_num ASC, street_name ASC Limit $arch_limit2"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link_arch), mysqli_error($link_arch)));
		exit();
}
 
	$res_arch2 = mysqli_query($query_arch2);
  $count_arch2 = mysqli_num_rows($query_arch2);
  $i = 0;
  
	
	if($count_arch2 > 0) {
		echo "<ul>";
		if($count_arch2 > 10) {
		while($row_arch2 = mysqli_fetch_array($query_arch2)) {
			echo '<a href="schedule.php?arc=y&order_id='.$row_arch2['id'].'"><li>';
			echo "<div id='rest'><strong>Order ID:</strong>&nbsp;".$row_arch2['id']."&nbsp;&nbsp;";
			if($row_arch2['street_num']=="0") { $street_num=""; } else { $street_num=$row_arch2['street_num']; }
					echo "<strong>Address:</strong> ".$street_num." ".$row_arch2['street_name'];
			echo "</div>";
			echo "<div style='clear:both;'></div></li></a>";
			$i++;
		}
		} else {
			while($row_arch2 = mysqli_fetch_array($query_arch2)) {
			echo '<a href="schedule.php?arc=y&order_id='.$row_arch2['id'].'"><li>';
			echo "<div id='rest'><strong>Order ID:</strong>&nbsp;".$row_arch2['id']."&nbsp;&nbsp;";
			if($row_arch2['street_num']=="0") { $street_num=""; } else { $street_num=$row_arch2['street_num']; }
			echo "<strong>Address:</strong> ".$street_num." ".$row_arch2['street_name'];
			echo "</div>";
			echo "<div style='clear:both;'></div></li></a>";
			$i++;
		}
			if($count2 > 0) {
				while($row2 = mysqli_fetch_array($query2)) {
			echo '<a href="schedule.php?order_id='.$row2['id'].'"><li>';
			echo "<div id='rest'><strong>Order ID:</strong>&nbsp;".$row2['id']."&nbsp;&nbsp;";
			if($row2['street_num']=="0") { $street_num=""; } else { $street_num=$row2['street_num']; }
			echo "<strong>Address:</strong> ".$street_num." ".$row2['street_name'];
			echo "</div>";
			echo "<div style='clear:both;'></div></li></a>";
			$i++;
				}
			}
		}
		echo "</ul>";
	} elseif ($count2 > 0) {
		echo "<ul>";
		while($row2 = mysqli_fetch_array($query2)) {
			echo '<a href="schedule.php?order_id='.$row2['id'].'"><li>';
			echo "<div id='rest'><strong>Order ID:</strong>&nbsp;".$row2['id']."&nbsp;&nbsp;";
			if($row2['street_num']=="0") { $street_num=""; } else { $street_num=$row2['street_num']; }
			echo "<strong>Address:</strong> ".$street_num." ".$row2['street_name'];
			echo "</div>";
			echo "<div style='clear:both;'></div></li></a>";
			$i++;
		}
		echo "</ul>";
	}
	else {
		echo "<div id='no_result'>No result found !</div>";
	}
	
}


if(isset($_POST['kw3']) && $_POST['kw3'] != '')
{
  $kws3 = filter_var($_POST['kw3'], FILTER_SANITIZE_STRING);
  if(!($query3  = mysqli_query($link,"SELECT id, CONCAT_WS(  ' ',  `firstname` ,  `lastname` ) AS agent_name
FROM users WHERE CONCAT_WS(  ' ',  `firstname` ,  `lastname` ) LIKE '%$kws3%' AND level = 'agent' Limit 10"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
}
  $res3 = mysqli_query($query3);
  $count3 = mysqli_num_rows($query3);
  $i = 0;
  
  if($count3 > 0)
  {
    echo "<ul>";
    while($row3 = mysqli_fetch_array($query3))
	{
		echo '<a href="agent_edit.php?agent='.$row3['id'].'"><li>';
		echo "<div id='rest'>".$row3['agent_name'];
		echo "</div>";
		echo "<div style='clear:both;'></div></li></a>";
		$i++;
	}
	echo "</ul>";
  }
  else
  {
    echo "<div id='no_result'>No result found !</div>";
  }
}


if(isset($_POST['kw4']) && $_POST['kw4'] != '')
{
  $kws4 = filter_var($_POST['kw4'], FILTER_SANITIZE_STRING);
  if(!($query4  = mysqli_query($link,"SELECT id, name FROM companies WHERE name LIKE '%$kws4%' Limit 10"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
}
  $res4 = mysqli_query($query4);
  $count4 = mysqli_num_rows($query4);
  $i = 0;
  
  if($count4 > 0)
  {
    echo "<ul>";
    while($row4 = mysqli_fetch_array($query4))
	{
		echo '<a href="company_edit.php?company='.$row4['id'].'"><li>';
		echo "<div id='rest'>".$row4['name'];
		echo "</div>";
		echo "<div style='clear:both;'></div></li></a>";
		$i++;
	}
	echo "</ul>";
  }
  else
  {
    echo "<div id='no_result'>No result found !</div>";
  }
}
}

if($_SESSION['SESS_LEVEL']=="dealer") {
	$dealer_id = $_SESSION['SESS_MEMBER_ID'];
	if(isset($_POST['kw']) && $_POST['kw'] != '')
{
  $kws = filter_var($_POST['kw'], FILTER_SANITIZE_STRING);
  if(!($query  = mysqli_query($link,"Select * From orders WHERE id LIKE '%$kws%' AND assigned='$dealer_id' Limit 10"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
}
  $res = mysqli_query($query);
  $count = mysqli_num_rows($query);
  $i = 0;
  
  if($count > 0)
  {
    echo "<ul>";
    while($row = mysqli_fetch_array($query))
	{
		echo '<a href="schedule.php?order_id='.$row['id'].'"><li>';
		echo "<div id='rest'><strong>Order ID:</strong>&nbsp;".$row['id']."&nbsp;&nbsp;";
		echo "<strong>Address:</strong>&nbsp;".$row['street_num']."&nbsp;".$row['street_name'];
		echo "</div>";
		echo "<div style='clear:both;'></div></li></a>";
		$i++;
	}
	echo "</ul>";
  }
  else
  {
    echo "<div id='no_result'>No result found !</div>";
  }
}


if(isset($_POST['kw2']) && $_POST['kw2'] != '')
{
  $kws2 = filter_var($_POST['kw2'], FILTER_SANITIZE_STRING);
  if(!($query2  = mysqli_query($link,"SELECT id, CONCAT_WS(  ' ',  `street_num` ,  `street_name` ) AS addy
FROM orders WHERE CONCAT_WS(  ' ',  `street_num` ,  `street_name` ) LIKE '%$kws2%' AND assigned='$dealer_id' Limit 10"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
}
  $res2 = mysqli_query($query2);
  $count2 = mysqli_num_rows($query2);
  $i = 0;
  
  if($count2 > 0)
  {
    echo "<ul>";
    while($row2 = mysqli_fetch_array($query2))
	{
		echo '<a href="schedule.php?order_id='.$row2['id'].'"><li>';
		echo "<div id='rest'><strong>Order ID:</strong>&nbsp;".$row2['id']."&nbsp;&nbsp;";
		echo "<strong>Address:</strong>&nbsp;".$row2['addy'];
		echo "</div>";
		echo "<div style='clear:both;'></div></li></a>";
		$i++;
	}
	echo "</ul>";
  }
  else
  {
    echo "<div id='no_result'>No result found !</div>";
  }
}


if(isset($_POST['kw3']) && $_POST['kw3'] != '')
{
  $kws3 = filter_var($_POST['kw3'], FILTER_SANITIZE_STRING);
  if(!($query3  = mysqli_query($link,"SELECT users.id as agents_id, CONCAT_WS(  ' ',  `firstname` ,  `lastname` ) AS agent_name,users.company_id, companies . * FROM users, companies WHERE CONCAT_WS(  ' ',  `firstname` ,  `lastname` ) LIKE  '%$kws3%' AND users.level =  'agent' AND companies.location = '$dealer_id' AND users.company_id = companies.id LIMIT 10"))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
}
  $res3 = mysqli_query($query3);
  $count3 = mysqli_num_rows($query3);
  $i = 0;
  
  if($count3 > 0)
  {
    echo "<ul>";
    while($row3 = mysqli_fetch_array($query3))
	{
		echo '<a href="agent_edit.php?agent='.$row3['agents_id'].'"><li>';
		echo "<div id='rest'>".$row3['agent_name'];
		echo "</div>";
		echo "<div style='clear:both;'></div></li></a>";
		$i++;
	}
	echo "</ul>";
  }
  else
  {
    echo "<div id='no_result'>No result found !</div>";
  }
}


if(isset($_POST['kw4']) && $_POST['kw4'] != '')
{
  $kws4 = filter_var($_POST['kw4'], FILTER_SANITIZE_STRING);
  if(!($query4  = mysqli_query($link,"SELECT id, name FROM companies WHERE name LIKE '%$kws4%' AND location = '$dealer_id' Limit 10", $link))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
}
  $res4 = mysqli_query($query4);
  $count4 = mysqli_num_rows($query4);
  $i = 0;
  
  if($count4 > 0)
  {
    echo "<ul>";
    while($row4 = mysqli_fetch_array($query4))
	{
		echo '<a href="company_edit.php?company='.$row4['id'].'"><li>';
		echo "<div id='rest'>".$row4['name'];
		echo "</div>";
		echo "<div style='clear:both;'></div></li></a>";
		$i++;
	}
	echo "</ul>";
  }
  else
  {
    echo "<div id='no_result'>No result found !</div>";
  }
}
}
?>