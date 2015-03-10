<?php 
session_start();
## Database settings
$wgDBtype = "mysql";
$wgDBserver = "127.0.0.1";
$wgDBname = "newwiki";
$wgDBuser = "root";
$wgDBpassword = "anetadmin";

//if($_COOKIE['newwiki_nw_UserID']!=""){

	$con = mysql_connect($wgDBserver,$wgDBuser,$wgDBpassword );
	if (!$con){
		die('Could not connect: ' . mysql_error());
	}
	mysql_select_db($wgDBname, $con);
	mysql_query("set names utf8");
	$result = mysql_query("SELECT * FROM nw_integral_user");
//}
?>
<html>
<head>
	<title>我的积分</title>
	<meta http-equiv="content-type" content="text/html;charset=utf-8">
	<script src="jquery-2.0.3.min.js"></script>
</head>
<body>
<div align="center">
	<a href="javascript:window.location.reload()">刷新</a>
	
	<tr><td>
	<table border="1" width="500" bgcolor="#C0C0C0" cellspacing="0" id="abc">
		<tr>
			<td>用户名</td>
			<td>积分</td>
			<td>上次积分</td>
		</tr>
		<?php
		$i = 0;
		while ($row = mysql_fetch_array($result)) {

			$c = null;
			$up[]=$row;
			if($_COOKIE['newwiki_nw_UserID']==$row['uid']){
				$c = ' class="bg"';
			}
			$results = mysql_query("SELECT * FROM nw_user where user_id=".$row['uid']);
			$info = mysql_fetch_array($results);
			echo '<tr><td bgcolor="#FFFFFF" '.$c.'>'.$info['user_name'].'</td><td bgcolor="#FFFFFF" '.$c.'>'.$row['integral'].'</td><td bgcolor="#FFFFFF" '.$c.'>'.$_SESSION['up'][$i]['integral'].'</td></tr>';
			$i++;
		}

		?>
		
	</table></td><td>

<?php
$_SESSION['up'] = $up;
?>
</table>

</div>
<script type="text/javascript">
$(function(){
    $('#abc tr').each(function(){
        var a=$(this).find("td");
        if(a.eq(1).html()!=a.eq(2).html()){
            a.eq(2).css("color","red")
        }
    })
})

</script>
<style type="text/css" media="screen">
	table {margin-top:50px;font-size:34px;}
	table td{padding:5px}
	.bg{background: #008e2b;color:#fff}
</style>
	
</body>
</html>
<?php
mysql_close($con);
?>