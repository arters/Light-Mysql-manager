<?php
/*
  Smaller Mysql_manager
*/
define('PROG_NAME','Mysql_manager');
define('VERSION','1.0');
define('MAX_PER_PAGE','100');

date_default_timezone_set('Asia/Taipei');
header("Content-type: text/html; charset=UTF-8");

function change_query($queryKey, $queryValue){
    $queryStr = $_SERVER['QUERY_STRING'];
    parse_str($queryStr, $output);
    $output[$queryKey] = $queryValue;
    return http_build_query($output);
}

//Check if PHP session has already started (PHP >= 5.4.0 , PHP 7)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$_GET['act'] = isset($_GET['act'])? trim($_GET['act']) : null;
$act = isset($_POST['act'])? trim($_POST['act']) : trim($_GET['act']);

$mysqli = new stdClass;

if(isset($_GET['db'])){
    $_SESSION['db']= trim($_GET['db']);
}
if($act === 'login'){
    $_SESSION['server'] = isset($_POST['server'])? $_POST['server'] : null;
    $_SESSION['username'] = isset($_POST['username'])? $_POST['username'] : null;
    $_SESSION['password'] = isset($_POST['password'])? $_POST['password'] : null;
    $_SESSION['db'] = isset($_POST['db'])? $_POST['db'] : null;
}
$db = isset($_SESSION['db'])? $_SESSION['db'] : null;
$show_field = isset($_GET['show_field'])? $_GET['show_field'] : null;
$show_data = isset($_GET['show_data'])? $_GET['show_data'] : null;

if($act == 'loginout'){
    session_unset(); // remove all session variables
    session_destroy(); // destroy the session
    $_SESSION['conn_state'] = false;
    header("Location: ?" );
}

if (isset($_SESSION['server']) && isset($_SESSION['username']) ){
    try {
        $mysqli = new mysqli($_SESSION['server'], $_SESSION['username'], $_SESSION['password'], $_SESSION['db']);
        if ($mysqli->connect_errno) {
            printf("Connect failed: %s\n", $mysqli->connect_error);
            exit();
        }
    } catch(Exception $e ) {
        echo "Error No: ".$e->getCode(). " - ". $e->getMessage() . "<br >";
    }
    $_SESSION['conn_state'] = true;
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

    $tb_query = $mysqli->query("SHOW TABLE STATUS FROM `" . $db . "`");

    $output = "-- " . PROG_NAME . " 版本:  " . VERSION . $br_code;
    $output .= "-- --------------------------------------------------------" . $br_code ."-- 主機:                " . $_SESSION['server'] . $br_code ."-- 服務器版本:          " . mysqli_get_server_info($mysqli) . " port:" . mysqli_get_proto_info($mysqli)  . $br_code ."-- 服務器操作系統:      " . $_SERVER['SERVER_SOFTWARE'] . $br_code ."
-- --------------------------------------------------------{$br_code}
-- 資料庫備份 " . date('Y/m/d_H:i:s' , time()) . $br_code .  "-- 系統備份資料如下" . $br_code . $br_code;
    if (0 < $tb_query->num_rows) {
        while($tb_row = $tb_query->fetch_assoc()) {
            if(!is_array($export_use_table)) break;

            $tb_name = $tb_row['Name'];
            if (in_array($tb_name, $lock_tb)) continue;
            if (!in_array($tb_name, $export_use_table)) continue;
            $output .= "-- 列出資料表 `" . $tb_name . "` 資料" . $br_code;

            $query = $mysqli->query("SELECT * FROM $tb_name");
            if (0 < $query->num_rows) {
                while ($row = $query->fetch_assoc()) {
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
.tableview {
  max-width: 1200px; width: 100%;
}
th {
  background-color:green;
  color:white;
}
.red {
    color:red;
}
.bg-gray { background-color: #BEBEBE; }
</style>
</head>
<body>

<p>連線設定</p>

<table width="500">
    <form action="" method="post">
        <input type="hidden" name="act" value="login">
    <tr>
        <td>*主機名</td>
        <td><input type="text" name="server" value="<?php echo isset($_SESSION['server'])? $_SESSION['server'] :'localhost' ?>"></td>
    </tr>
    <tr>
        <td>*用戶</td>
        <td><input type="text" name="username" value="<?php echo isset($_SESSION['username'])? $_SESSION['username'] :'root' ?>"></td>
    </tr>
    <tr>
        <td>*密碼</td>
        <td><input type="password" name="password" value="<?php echo isset($_SESSION['password'])? $_SESSION['password'] :'' ?>"></td>
    </tr>
    <tr>
        <td>資料庫</td>
        <td><input type="text" name="db" value="<?php echo $db?>"></td>
    </tr>
    <tr>
        <td>連線</td>
        <td><input type="submit" value="登入" class="">　
        <?php
        if (isset($_SESSION['conn_state'])){
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
if (isset($_SESSION['conn_state']) && isset($show_field)){
    echo "<p>選擇資料表：$show_field </p>";

    $field_ary = array();
    echo '<table class="tableview"><tr bgcolor="#CA95FF">
    <td>Key</td><td width="40%">Field</td><td width="100">類型(Type)</td><td width="100">注釋</td><td>Default</td><td width="130">校對(Collation)</td><td width="50">Null</td><td>Extra</td>
    </tr>';
    $result = $mysqli->query('SHOW FULL COLUMNS FROM ' . $show_field . ''); //
    if($result){
        while($row = $result->fetch_assoc()) {
            //print_r($row); //列出所有欄位
            $field_ary[]=$row['Field'];
            echo "<tr><td>" . $row['Key'] . "</td><td><b>" . $row['Field'] . "</b></td><td>" . $row['Type'] . "</td><td>" . $row['Comment'] . "</td><td>" . $row['Default'] . "</td><td>" . $row['Collation'] . "</td><td>" . $row['Null'] . "</td><td>" . $row['Extra'] . "</td></tr>";
        }
    }
    echo "</table>";

    echo "<p>SQL查詢語法：<br><code>";
    // 顯示SQL查詢語法
    $field_str = implode(", ", $field_ary);
    $query_str = "<span class='red'>SELECT</span> $field_str <BR><span class='red'>FROM</span> $show_field";
    print_r($query_str);
    echo "</code>";

    echo "<p>PHP POST 欄位語法：<br><code>";
    foreach($field_ary as $key){
        echo '$' . $key .' = <span class="red">$_POST[\'</span>' . $key .'<span class="red">\']</span> ? $_POST[\'</span>' . $key .'<span class="red">\']</span> : null; //<br>' ;

    }

    echo "</code>";
    echo "<hr>";
    die();
}

$html = '';
if (isset($_SESSION['conn_state']) && isset($show_data)){
    $page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;
    if($page < 1) $page = 1;
    $html .= "<p>瀏覽資料表：$show_data </p>";

    $result = $mysqli->query('SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`="' . $db . '" AND `TABLE_NAME`="' . $show_data . '"'); //列出所有欄位
    if($result){

        $statement = '`' . $db . '`.`' . $show_data . '`';

        $query = $mysqli->prepare("SELECT * FROM " . $statement);
        $query->execute();
        $query->store_result();
        $rows = $query->num_rows;
        $pages = ceil($rows / MAX_PER_PAGE);
        if($page > $pages) $page = $pages;
        $offset = (int)(($page - 1 ) * MAX_PER_PAGE);

        $html .= '<table class="tableview"><tr bgcolor="#CA95FF"><tr>';
        $html .= '<td class="bg-gray">#</td>';
        $rowArr = array();
        while($row = $result->fetch_assoc()) {
            $html .= '<td class="bg-gray">' . $row['COLUMN_NAME'] . '</td>';
            $rowArr[] = $row['COLUMN_NAME'];
        }
        $html .= '</tr>';

        $querySql = 'SELECT  * FROM ' . $statement . ' LIMIT ' . $offset . ', ' . MAX_PER_PAGE;
        $result = $mysqli->query($querySql); //
        if($result && count($rowArr) >0 ){
            $rowIndex = $offset;
            while($row = $result->fetch_assoc()) {
                ++$rowIndex;
                $html .= '<tr>';
                $html .= '<td class="bg-gray">' . $rowIndex . '</td>';
                foreach($rowArr as $field){
                    $html .= '<td>' . $row[$field] . '</td>';
                }
                $html .= '</tr>';
            }

        }
        $html .= '</table>';

        $prePage = $page - 1;
        $nextPage = $page + 1;
        
        $html .= '當前：第' . $page .'頁 | 總筆數：' . $rows . ' | 每頁：' . MAX_PER_PAGE . '筆 | ';
        $html .= '[<a href="?' . change_query("page", 1) .'">第一頁</a>]' ;
        if($prePage > 0){$html .= '[<a href="?' . change_query("page", $prePage) .'">上一頁</a>]' ;}
        if($nextPage <= $pages){$html .= '[<a href="?' . change_query("page", $nextPage) .'">下一頁</a>]' ;}

        $html .= '[<a href="?' . change_query("page", $pages) .'">最後一頁(' . $pages . ')</a>]' ;
        echo $html;
    }


    echo "<p>SQL查詢語法：<br><code>";
    // 顯示SQL查詢語法
    $query_str = '<span class="red">' . $querySql . '</span>';
    print_r($query_str);
    echo "</code>";

    die();

}

if (isset($_SESSION['conn_state']) && !empty($db) ){
    $html = '';
    $html .= '<p> ' . $db .' 底下所有資料表： </p>
    <form action="" method="post">
        <input type="hidden" name="act" value="dump_all">
        <label><input type="checkbox" name="save_mode" value="12" checked>下載儲存</label>
        <input type="submit" value="匯出勾選資料" class="">
    ';
    $html .= '<table class="tableview"><tr bgcolor="#C3C3C3">
    <td width="35"><input type="checkbox" id="checkAll"/></td>
    <td width="20%">資料表</td><td>註解</td><td width="100">引擎</td><td width="100">校對</td><td>數據條</td><td>大小</td><td>AUTO INCREMENT</td><td>INDEX LENGTH</td><td width="80">修改時間</td><td width="70">欄位</td><td width="70">資料</td>
    </tr>';
    //$result = $mysqli->query("SHOW TABLE STATUS");
    $querySql = 'SELECT * FROM information_schema.TABLES
     WHERE TABLES.TABLE_SCHEMA = "' . $db . '"
          AND TABLES.TABLE_TYPE = "BASE TABLE"';
    $result = $mysqli->query($querySql);
    if($result){
        while($row = $result->fetch_assoc()){
            $html .= "<tr>
            <td><input type=\"checkbox\" name=\"export_use_table[]\" class=\"down_csv\" value=\"" . $row['TABLE_NAME'] . "\" /></td>
            <td><b>" . $row['TABLE_NAME'] . "</b></td><td>" . $row['TABLE_COMMENT'] . "</td><td>" . $row['ENGINE'] . "</td><td>" . $row['TABLE_COLLATION'] . "</td><td>" . $row['TABLE_ROWS'] . "</td><td>" . $row['DATA_LENGTH'] . "</td><td>" . $row['AUTO_INCREMENT'] . "</td><td>" . $row['INDEX_LENGTH'] . "</td><td>" . $row['UPDATE_TIME'] . "</td><td><a href='?show_field=" . $row['TABLE_NAME'] . "'>檢視</a></td><td><a href='?show_data=" . $row['TABLE_NAME'] . "'>檢視</a></td></tr>";
        }
    }

    $html .= "</table></form>";
    $html .= "<hr>";

    $querySql = ' SELECT * FROM information_schema.TABLES WHERE TABLES.TABLE_SCHEMA = "' . $db . '" AND TABLES.TABLE_TYPE = "VIEW"';
    $result = $mysqli->query($querySql);
    if($result && $result->num_rows > 0){
        $html .= '<p> ' . $db .' 底下所有 View </p>';
        $html .= '<table class="tableview"><tr bgcolor="#C3C3C3">
        <td>#</td><td width="35%">名稱</td><td>TYPE</td><td>COMMENT</td></tr>';

        $i = 0;
        while($row = $result->fetch_assoc()){
            ++$i;
            $html .= "<tr><td>" . $i . "</td><td>" . $row['TABLE_NAME'] . "</td><td>" . $row['TABLE_TYPE'] . "</td><td>" . $row['TABLE_COMMENT'] . "</td></tr>";
        }
        $html .= "</table>";
    }

    $result = $mysqli->query("show triggers");
    if($result && $result->num_rows > 0){
        $html .= '<p> ' . $db .' 底下所有 trigger： </p>';
        $html .= '<table class="tableview"><tr bgcolor="#C3C3C3">
        <td>#</td><td width="35%">名稱</td><td>Timing</td><td>Table</td><td>Event</td></tr>';

        $i = 0;
        while($row = $result->fetch_assoc()){
            ++$i;
            $html .= "<tr><td>" . $i . "</td><td>" . $row['Trigger'] . "</td><td>" . $row['Timing'] . "</td><td>" . $row['Table'] . "</td><td>" . $row['Event'] . "</td></tr>";
        }
        $html .= "</table>";
    }
    echo $html;

}


if (isset($_SESSION['conn_state'])){
    echo "<p> 所有資料庫 </p>";

    echo '<table width="800"><tr bgcolor="#C3C3C3">
      <td width="60%">資料庫</td><td>檢視</td>
      </tr><pre>';

    $result = $mysqli->query("SHOW databases");
    if($result){
        while($row = $result->fetch_assoc()){
            echo "<tr>
            <td>" . $row['Database'] . "</td><td><a href='?db=" . $row['Database'] . "'>欄位資料</a></td></tr>";
        }
    }
    echo "</table>";
    echo "<hr>";

}

if (isset($_SESSION['conn_state'])){
    $mysqli->close();
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
