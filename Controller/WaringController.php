<?php


/**
 * 获取信息
 * @return mixed|string
 */


//1 新增异常上报
function api_waring_report(){
	$groupId = $GLOBALS['group_id'];
	//就是add_user_id
	$admin_id = $GLOBALS['admin_id'];
	//获取业务数据库连接
	$db2 = $GLOBALS['groupdb'][$groupId];
	//定义新增的数据
    $note = $_POST['note'];
	$type_name = $_POST['type_name'];
	$type_id = $_POST['type_id'];
    $files = $_POST['files'];
	$addtime = date("Y-m-d H:i:s");
    //对td_vanc_plan插入数据
    $waring_inst = "insert into td_warning(note, state , addtime , add_user ,grp_id ,type_id ,files ,msg_sub_title) values('$note' , 1 ,'$addtime' , $admin_id ,$groupId ,$type_id ,'$files' ,'$type_name')";
    $result1 = $db2->query($waring_inst);
    debug($waring_inst);
    if($result1){
        return apiSucc("新增数据成功！");
    }else{
        return apiFail("新增数据失败");
    }
    
}


