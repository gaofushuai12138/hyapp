<?php
//开启session
session_start();
define('WSKF', true);
define('INIT_NO_USERS',true);
define('NOT_CHECK_SESSION',true);
require('includes/init.php');

// 设置时区
date_default_timezone_set('Asia/Shanghai');
header('Content-Type: text/html;charset=utf-8');
header('Access-Control-Allow-Origin:*'); // *代表允许任何网址请求
header('Access-Control-Allow-Methods:POST,GET,OPTIONS,DELETE'); // 允许请求的类型
header('Access-Control-Allow-Credentials: true'); // 设置是否允许发送 cookies
header('Access-Control-Allow-Headers: Content-Type,Content-Length,Accept-Encoding,X-Requested-with, Origin,token'); // 设置允许自定义请求头的字段

$body_json = file_get_contents("php://input");

if(!empty($body_json)){
	debug("body:".$body_json);
	try{
	$json_obj = json_decode($body_json,true);
	
	if(empty($json_obj))
	{
		echo( apiFail('不是正确的json参数!'));
		die;
	}
	foreach($json_obj as $key=>$item)
	{
		$_POST[$key] = $item;
	}
	}catch(Exception $e){
		debug("e:".$e);
		echo apiFail('错误的请求内容!');
		die;
	}

}

$cur_grp_id = "";
$cur_admin_id = "";
debug($_POST);
function bootstrap(){
	$arr_notoken = ['api_user_login'];
    $act = $_POST['act'];
	
	$all_header = get_all_header();
	$token_param = $all_header['token'];

	if(!in_array($act,$arr_notoken)){
		if(empty($token_param)){
			return apiFail('token not exists!');
		}else{
			$sql = "select exp_time,admin_id,grp_id from td_app_user_token where token = '$token_param'";
			$token_info = $GLOBALS['db']->getRow($sql);
			
			debug($token_info);
			$exp_time = intval($token_info['exp_time']);
			
			$GLOBALS['token'] = $token_param;
			$GLOBALS['group_id'] = $token_info['grp_id'];
			$GLOBALS['admin_id'] = $token_info['admin_id'];
			
			debug("失效时间：$exp_time 系统时间：" . time());
			if(time()>$exp_time){
				return apiFail('token失效!',-1);
			}else{

			}
		}
	}
    debug("act:".$act);
    $action = explode('_',$act);
	debug($action);
    if(count($action) < 3 || $action[0] != 'api'){
        return apiFail('访问失败!');
    }
    $controllerName = strtoupper(substr($action[1],0,1)).strtolower(substr($action[1],1));
    $controller = 'Controller/'.$controllerName.'Controller.php';
	debug("feedcontroller".$controller);
    if(!is_file($controller)){
        return apiFail('controller is not exists');
    }
    require_once($controller);
    if(function_exists($act)){
        try{
            return $act();
        }catch(Exception $e){
            debug(json_encode($e));
            return apiFail($e);
        }
    }else{
		debug("sdsddsds");
        return apiFail('错误的请求内容!');
    }
}



function apiFail($msg, $code = 1){
    $msg = sprintf($msg,'');
    $json = ['code'=>$code,'msg'=>$msg,'st'=>time()];
    return json_encode($json,JSON_UNESCAPED_UNICODE);
	
}

function apiSucc($data='',$code = 0){
	debug($data);
    $json = ['code'=>$code,'data'=>$data,'st'=>time()];
    return json_encode($json,JSON_UNESCAPED_UNICODE);
	
}


echo bootstrap();