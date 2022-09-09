<?php


/**
 * 获取信息
 * @return mixed|string
 */

function api_msg_list(){
	$tp = $_POST['tp'];
	$keyword = $_POST['keyword'];
	$page = $_POST['page'];
	$size = $_POST['size'];
	$limit = $size*$page;
	$msg_info;
	$has_more = false;
	if($tp == 1){//消息类型 1 公告 2 异常 3 任务
		$sql = " SELECT * FROM td_msg_notice WHERE msg_title LIKE '%$keyword%' ";
		$msg_info = $GLOBALS['db']->getAll($sql);
		debug($msg_info);
	}else if($tp == 2){
		$groupId=$GLOBALS['group_id'];
		$db2 = $GLOBALS['groupdb'][$groupId];
		$sql = " SELECT id,msg_title,msg_sub_title,msg_short_desc,type_id,note AS msg_desc,addtime AS msg_time,files FROM td_warning WHERE msg_title LIKE '%$keyword%'  limit $limit , $size ";
		$msg_info = $db2->getAll($sql);
	}else if($tp == 3){
		$sql = " SELECT * FROM td_msg_task WHERE msg_title LIKE '%$keyword%' ";
		$msg_info = $GLOBALS['db']->getAll($sql);
	}
	//debug($msg_info);
	$msg_list = [];
	if(empty($msg_info))
	{
		$msg_data[] = ['has_more'=>false,'msg_list'=>[]];
		
		return apiSucc($msg_data);
	}else{
		if(count($msg_info) > $size){
			$has_more = true;
		}
		foreach($msg_info as $row){
			$msg_img = "";
			if($tp == 2){
				$files = json_decode($row['files'],true);
				debug($files);
				$msg_img = $files[0]['file_name'];
				$msg_list[] = ['msg_id'=>$row["id"],"msg_title"=> $row['msg_title'],'msg_sub_title'=>$row['msg_sub_title'],'msg_short_desc'=>$row['msg_short_desc'],'type_id'=>$row['type_id'],'msg_time'=>$row['msg_time']];
			}else if($tp == 1){
				$msg_img = $row['msg_img'];
				//修改
				$msg_list[] = ['msg_id'=>$row['msg_id'],'msg_title'=>$row['msg_title'],'msg_sub_title'=>$row['msg_sub_title'],'msg_time'=>$row['msg_time'],'msg_short_desc'=>$row['msg_short_desc'],'msg_month'=>date('n',strtotime($row['msg_time'])),'msg_day'=>date('j',strtotime($row['msg_time'])),'msg_img'=>$msg_img];
			}
			
		}
	}
	$msg_data[] = ['has_more'=>$has_more,'msg_list'=>$msg_list];

    return apiSucc($msg_data);
}

function api_msg_create(){
	$msg_sub_title = $_POST['sub_title'];
	$msg_short_desc = $_POST['content'];
	$files = $_POST['files'];

	if($msg_sub_title=="园区安全")
	{
		$type_id = 4;
	} else if($msg_sub_title=="园区环卫")
	{
		$type_id = 5;
	} else if($msg_sub_title=="设施报修")
	{
		$type_id = 6;
	} else if($msg_sub_title=="羊只情况")
	{
		$type_id = 7;
	}
	if(strlen($msg_short_desc) > 60 )
	{
		//$msg_title = common_substr($msg_short_desc,20);
		$msg_title = substr($msg_short_desc,0,60);
	} else 
	{
		$msg_title = $msg_short_desc;
	}
	$groupId=$GLOBALS['group_id'];
	$db2 = $GLOBALS['groupdb'][$groupId];

	$msg_info = ['msg_title'=>$msg_title,'msg_sub_title'=>$msg_sub_title,'msg_short_desc'=>$msg_short_desc,'type_id'=>$type_id,'note'=>$msg_short_desc,'files'=>$files ,'state'=>1,'addtime'=>date("Y-m-d H:i:s"),'add_user'=>$GLOBALS['admin_id'],'grp_id'=>$GLOBALS['group_id']];
	$db2->autoExecute("td_warning", $msg_info, 'INSERT', "");
    return apiSucc("添加成功");
}

function api_msg_uploadimg(){
	$filename = $_FILES['file']['name'];
	debug("filename:".$filename);
	//获取文件临时路径
	$temp_name = $_FILES['file']['tmp_name'];
	$arr = pathinfo($filename);
	//获取文件的后缀名
	$ext_suffix = $arr['extension'];
	$new_filename = date('YmdHis',time()).rand(100,1000).'.'.$ext_suffix;

	if (!file_exists('../uploads')){
		mkdir('../uploads');
	}
	//move_uploaded_file($_FILES["file"]["tmp_name"],'uploads/'.$new_filename);
    if (move_uploaded_file($_FILES["file"]["tmp_name"],'../uploads/'.$new_filename))
	{
		//新路径
		$res['file_name']='uploads/'.$new_filename;
		return apiSucc($res);
	}
	else
	{
		return apiFail("获取失败");
	}
}


/**
 * 获取通知消息详情
 */
function api_msg_detail(){
	//获取类型
	$tp = $_POST["tp"];
	debug("tp:".$tp);
	$msg_id = $_POST['msg_id'];
	debug("msg_id:".$msg_id);
	$msg_info = null;
	$msg_list = [];
	
	if($tp == 1){   //消息类型 1 公告 2 异常 3 任务
		$groupId=$GLOBALS['group_id'];
		// $db2 = $GLOBALS['groupdb'][$groupId];
		// $msg_info = $GLOBALS['db']->getAll($sql);
		$sql = "select * from td_msg_notice where msg_id='$msg_id' and '$groupId'";
		// $sql = " SELECT * FROM td_warning WHERE id = '$msg_id' ";
		$msg_info = $GLOBALS['db']->getAll($sql);
	}else if($tp == 2){
		$groupId=$GLOBALS['group_id'];
		debug("groupId:".$groupId);
		$db2 = $GLOBALS['groupdb'][$groupId];
		$sql = "SELECT id,msg_title,msg_sub_title,msg_short_desc,type_id,note AS msg_desc,addtime AS msg_time,files FROM td_warning where id = '$msg_id'";
		$msg_info = $db2->getAll($sql);
	}else if($tp == 3){

	}
	// $msg_list = [];
	debug($msg_info);
	if(empty($msg_info))
	{
		return apiFail("无数据!");
	}else{
		foreach($msg_info as $row){
			$msg_data[] = ['msg_id'=>$row['msg_id'],'msg_title'=>$row['msg_title'],'msg_sub_title'=>$row['msg_sub_title'],'msg_time'=>$row['msg_time'],'msg_short_desc'=>$row['msg_short_desc'],'msg_month'=>date('n',strtotime($row['msg_time'])),'msg_day'=>date('j',strtotime($row['msg_time'])),'msg_img'=>$row['msg_img'],'msg_content'=>$row['msg_desc'],'files'=>$row['files']];
		}
	}
    return apiSucc($msg_data);
}







function common_substr($sourcestr, $cutlength) {
   $returnstr = '';
   $i = 0;
   $n = 0;
   $str_length = strlen($sourcestr); //字符串的字节数 
   while ( ($n < $cutlength) and ($i <= $str_length) ) {
    $temp_str = substr($sourcestr, $i, 1 );
    $ascnum = ord($temp_str); //得到字符串中第$i位字符的ascii码 
    if ($ascnum >= 224) {//如果ascii位高与224，
        if($n+3>$cutlength){
            return $returnstr;
        }
        $returnstr = $returnstr . substr($sourcestr, $i, 4 ); //根据utf-8编码规范，将3个连续的字符计为单个字符  
        $i = $i + 3; //实际byte计为3
        $n +=3;
        //echo $n."\n";
    } elseif ($ascnum >= 192){ //如果ascii位高与192，
        if( $n+2>$cutlength){
            return $returnstr;
        }
        $returnstr = $returnstr . substr($sourcestr, $i, 2 ); //根据utf-8编码规范，将2个连续的字符计为单个字符 
        $i = $i + 2; //实际byte计为2
        $n +=2;
    } elseif ($ascnum >= 65 && $ascnum <= 90) {//如果是大写字母，
        $returnstr = $returnstr . substr ( $sourcestr, $i, 1 );
        $i = $i + 1; //实际的byte数仍计1个
        $n ++; //但考虑整体美观，大写字母计成一个高位字符
    }elseif ($ascnum >= 97 && $ascnum <= 122) {
        $returnstr = $returnstr . substr( $sourcestr, $i, 1 );
        $i = $i + 1; //实际的byte数仍计1个
        $n ++; //但考虑整体美观，大写字母计成一个高位字符
    } else {//其他情况下，半角标点符号，
        $returnstr = $returnstr . substr( $sourcestr, $i, 1 );
        $i = $i + 1; 
        $n = $n + 1;
    }
   }
   return $returnstr;
}
?>