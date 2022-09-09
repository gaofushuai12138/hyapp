<?php


if (!defined('WSKF'))
{
    die('Hacking attempt');
}
error_reporting(E_ALL & ~E_NOTICE);
//error_reporting(E_ALL);

if (__FILE__ == '')
{
    die('Fatal error code: 0');
}

/* 取得当前ecshop所在的根目录 */
//define('ROOT_PATH', str_replace('third/animal', '', str_replace('\\', '/', dirname(__FILE__))));
define('ROOT_PATH', str_replace('includes', '', str_replace('\\', '/', dirname(__FILE__))));

//define('ROOT_PATH','D:/phpPlateForm/hyapp/includes/');

/* 初始化设置 */
@ini_set('memory_limit',          '32M');
@ini_set('session.cache_expire',  180);
@ini_set('session.use_trans_sid', 0);
@ini_set('session.use_cookies',   1);
@ini_set('session.auto_start',    0);
@ini_set('display_errors',        1);


if (file_exists('config.php'))
{
    include('config.php');
}
else
{
    include(ROOT_PATH . '/includes/config.php');
}

if (defined('DEBUG_MODE') == false)
{
    define('DEBUG_MODE', 0);
}

if (PHP_VERSION >= '5.1' && !empty($timezone))
{
    date_default_timezone_set($timezone);
}

$php_self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
if ('/' == substr($php_self, -1))
{
    $php_self .= 'index.php';
}
define('PHP_SELF', $php_self);

require(ROOT_PATH . 'includes/lib_common.php');
require(ROOT_PATH . 'includes/lib_time.php');

/* 对用户传入的变量进行转义操作。*/
if (!get_magic_quotes_gpc())
{
    if (!empty($_GET))
    {
        $_GET  = addslashes_deep($_GET);
    }
    if (!empty($_POST))
    {
        $_POST = addslashes_deep($_POST);
    }

    $_COOKIE   = addslashes_deep($_COOKIE);
    $_REQUEST  = addslashes_deep($_REQUEST);
}


/* 初始化数据库类 */
require(ROOT_PATH . 'includes/cls_mysql.php');
$db = new cls_mysql($db_host, $db_user, $db_pass, $db_name);
$db2 = new cls_mysql($db_host2, $db_user2, $db_pass2, $db_name2);
$db3 = new cls_mysql($db_host3, $db_user3, $db_pass3, $db_name3);
$db_host = $db_user = $db_pass = $db_name = NULL;
$db_host2 = $db_user2 = $db_pass2 = $db_name2 = NULL;
$db_host3 = $db_user3 = $db_pass3 = $db_name3 = NULL;

$groupdb = ['9'=>$db2,'10'=>$db3];

//header('Content-type: application/json;charset=utf-8');

if (!session_id()) session_start();
if (!defined('NOT_CHECK_SESSION'))
{
    
    if(empty($_SESSION['ses_key_user_id']))
    {
        header('Location:login.php');
    }
}


ob_start();
?>