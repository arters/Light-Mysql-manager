<?php
/*
  Smaller Mysql_manager 
*/
date_default_timezone_set('Asia/Taipei');
header("Content-type: text/html; charset=UTF-8");

define('PROG_NAME','Mysql_manager');
define('VERSION','1.0');

session_start();

$_GET['act'] = isset($_GET['act'])? trim($_GET['act']) : '';
$act = isset($_POST['act'])? $_POST['act'] : trim($_GET['act']);

if($act == 'login'){
    $_SESSION['server'] = isset($_POST['server'])? $_POST['server'] :'';
    $_SESSION['username'] = isset($_POST['username'])? $_POST['username'] :'';
    $_SESSION['password'] = isset($_POST['password'])? $_POST['password'] :'';
    $_SESSION['db'] = isset($_POST['db'])? $_POST['db'] :'';
}

$_SESSION['db']= isset($_SESSION['db'])? $_SESSION['db'] : '';
$_SESSION['db']= isset($_GET['db'])? $_GET['db'] : $_SESSION['db'];

$server = isset($_SESSION['server'])? $_SESSION['server'] :'';
$username = isset($_SESSION['username'])? $_SESSION['username'] :'';
$password = isset($_SESSION['password'])? $_SESSION['password'] :'';
$db = isset($_SESSION['db'])? $_SESSION['db'] :'';
$table_field = isset($_GET['table_field'])? $_GET['table_field'] :'';

$_SESSION['conn_state'] = false;

if($act == 'loginout'){
    session_unset(); // remove all session variables
    session_destroy(); // destroy the session
    $_SESSION['conn_state'] = false;
    header("Location: ?" );
}

$con='';
if (!empty($server) || !empty($username) ){
    $con = mysql_connect($server ,$username ,$password);
}
if (!$con){
    echo '沒有連線。Could not connect: ' . mysql_error() .'<hr>';
}else{
    $_SESSION['conn_state'] = true;
    mysql_select_db( $db , $con);
}

if($act == 'dump_all'){
    $export_use_table = isset($_POST['export_use_table'])? $_POST['export_use_table'] : false ;
    $save_mode = isset($_POST['save_mode'])? $_POST['save_mode'] :'';
    if($save_mode){
        $br_code = "\r\n";
        header("Content-type: text/plain; charset=UTF-8");
        header("Content-Disposition: attachment; filename=" . date('Ymd_His' , time()) . "-Database-" . $db . "-backup.sql");
        header("Content-Transfer-Encoding: binary");
        header("Cache-Control: cache, must-revalidate");
        header("Pragma: public");
        header("Pragma: no-cache");
        header("Expires: 0");
    }else{
        $br_code = "<br>";
        
    }

    $lock_tb = array();

    $tb_query = mysql_query("SHOW TABLE STATUS FROM `" . $db . "`");

    $output = "-- " . PROG_NAME . " 版本:  " . VERSION . $br_code;
    $output .= "-- --------------------------------------------------------" . $br_code ."-- 主機:                " . $server . $br_code ."-- 服務器版本:          " . mysql_get_server_info() . " port:" . mysql_get_proto_info()  . $br_code ."-- 服務器操作系統:      " . $_SERVER['SERVER_SOFTWARE'] . $br_code ."
-- --------------------------------------------------------{$br_code}
-- 資料庫備份 " . date('Y/m/d_H:i:s' , time()) . $br_code .  "-- 系統備份資料如下" . $br_code . $br_code;
    if (0 < mysql_num_rows($tb_query)) {
        while($tb_row = mysql_fetch_Assoc($tb_query)) {
            if(!is_array($export_use_table)) break;

            $tb_name = $tb_row['Name'];
            if (in_array($tb_name, $lock_tb)) continue;
            if (!in_array($tb_name, $export_use_table)) continue;
            $output .= "-- 列出資料表 `" . $tb_name . "` 資料" . $br_code;

            $query = mysql_query("SELECT * FROM $tb_name");
            if (0 < mysql_num_rows($query)) {
                while ($row = mysql_fetch_Assoc($query)) {
                    $x = array();
                    foreach ($row as $data) {
                        $x[] = "'" . str_replace(array("'", "\r\n", "\n"), array("\\'", "\\r\\n", "\\r\\n"), $data) . "'";
                    }
                    $output .= "INSERT INTO `" . $tb_name . "` VALUES (" . join(', ', $x) . ");\r\n";
                }
            }
            $output .= "\r\n\r\n";
        }
    }
    echo $output;
    exit;
}

?><!doctype html>
<!--[if IE 7 ]><html class="no-js ie ie7 lte7 lte8 lte9" lang="en-US"> <![endif]-->
<!--[if IE 8 ]><html class="no-js ie ie8 lte8 lte9" lang="en-US"> <![endif]-->
<!--[if IE 9 ]><html class="no-js ie ie9 lte9>" lang="en-US"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html class="no-js" lang="zh-TW"> <!--<![endif]-->
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" value="jwuyuan" content="width=device-width">
<title><?php echo PROG_NAME ?> <?php echo VERSION ?></title>
<style type="text/css">
table, td, th {
  border:1px solid #345;
}
th {
  background-color:green;
  color:white;
}
.red {
    color:red;
}
</style>
</head>
<body>

<p>連線設定</p>

<table width="500">
    <form action="" method="post">
        <input type="hidden" name="act" value="login">
    <tr>
        <td>主機名</td>
        <td><input type="text" name="server" value="<?php echo !empty($server)? $server :'localhost' ?>"></td>
    </tr>
    <tr>
        <td>用戶</td>
        <td><input type="text" name="username" value="<?php echo !empty($username)? $username :'root' ?>"></td>
    </tr>
    <tr>
        <td>密碼</td>
        <td><input type="password" name="password" value="<?php echo !empty($password)? $password :'' ?>"></td>
    </tr>
    <tr>
        <td>資料庫</td>
        <td><input type="text" name="db" value="<?php echo $db?>"></td>
    </tr>
    <tr>
        <td>連線</td>
        <td><input type="submit" value="登入" class="">　
        <?php
        if ($_SESSION['conn_state']){
        ?>
          <input type="button" value="登出" onclick="location.href='?act=loginout'">
        <?php
        }
        ?>
        </td>
    </tr>
    </form>
</table>

<?php
if ($_SESSION['conn_state'] && !empty($table_field)){
    echo "<p>選擇資料表：$table_field </p>";
    
    $field_ary=array();
    echo '<table width="800"><tr bgcolor="#CA95FF">
    <td width="40%">Field</td><td width="100">類型(Type)</td><td width="100">Null</td><td>Key</td><td>Default</td><td>Extra</td>
    </tr>';
    $result = mysql_query('SHOW COLUMNS FROM ' . $table_field . ''); //
    while($row = mysql_fetch_array($result)) {
        //print_r($row); //列出所有欄位
        $field_ary[]=$row['Field'];
        echo "<tr><td>" . $row['Field'] . "</td><td>" . $row['Type'] . "</td><td>" . $row['Null'] . "</td><td>" . $row['Key'] . "</td><td>" . $row['Default'] . "</td><td>" . $row['Extra'] . "</td></tr>";
    }
    echo "</table>";
    
    echo "<p>SQL查詢語法：<br><code>";
    // 顯示SQL查詢語法
    $field_str = implode(", ", $field_ary);
    $query_str = "<span class='red'>SELECT</span> $field_str <BR><span class='red'>FROM</span> $table_field";
    print_r($query_str);
    echo "</code>";
    
    echo "<p>PHP POST語法：<br><code>";
    foreach($field_ary as $key){
        echo '$' . $key .' = <span class="red">$_POST[\'</span>' . $key .'<span class="red">\']</span>; //<br>' ;
        
    }
    
    echo "</code>";
    echo "<hr>";
}

if ($_SESSION['conn_state'] && !empty($db) ){
    echo '<p> ' . $db .' 底下所有資料表： </p>
    <form action="" method="post">
        <input type="hidden" name="act" value="dump_all">
        <label><input type="checkbox" name="save_mode" value="12" checked>下載儲存</label>
        <input type="submit" value="匯出所有資料" class="">
    ';
    echo '<table width="800"><tr bgcolor="#C3C3C3">
    <td width="35"><input type="checkbox" id="checkAll"/></td>
    <td width="35%">資料表</td><td width="100">引擎</td><td width="100">校對</td><td>行數</td><td>註解</td><td>欄位資料</td>
    </tr>';
$result = mysql_query("SHOW TABLE STATUS");
while($row = mysql_fetch_array($result)){
    echo "<tr>
    <td><input type=\"checkbox\" name=\"export_use_table[]\" class=\"down_csv\" value=\"" . $row['Name'] . "\" /></td>
    <td>" . $row['Name'] . "</td><td>" . $row['Engine'] . "</td><td>" . $row['Collation'] . "</td><td>" . $row['Rows'] . "</td><td>" . $row['Comment'] . "</td><td><a href='?table_field=" . $row['Name'] . "'>欄位資料</a></td></tr>";
}
echo "</table>
    </form>
    ";
echo "<hr>";

}

if ($_SESSION['conn_state']){
    echo "<p> 資料庫 </p>";
    
    echo '<table width="800"><tr bgcolor="#C3C3C3">
      <td width="60%">資料庫</td><td>檢視</td>
      </tr><pre>';
  
    $result = mysql_query("SHOW databases");
    while($row = mysql_fetch_array($result)){
        echo "<tr>
        <td>" . $row['Database'] . "</td><td><a href='?db=" . $row['Database'] . "'>欄位資料</a></td></tr>";
    }
    echo "</table>";
    echo "<hr>";

}

if ($_SESSION['conn_state']){
    mysql_close($con);
}

/* 表頭設定 */
/*
header("Content-Disposition: attachment; filename=Database-" . $db['default']->dbname . "-" . str_replace('-', '', $grs->today) . "backup.sql");
header("Content-Transfer-Encoding: binary");
header("Cache-Control: cache, must-revalidate");
header("Pragma: public");
header("Pragma: no-cache");
header("Expires: 0");
*/
?>

</body>

<script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
<script>!window.jQuery && document.write('<script src="jquery-1.11.3.min.js"><\/script>')</script><!-- 使用本地端 -->
<script>
$(window).load(function(){
    $("#checkAll").change(function () {
        $(".down_csv").prop('checked', $(this).prop("checked"));
    });
});
</script>
</html>
