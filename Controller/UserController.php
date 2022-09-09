<?php


/**
 * 获取用户信息
 * @return mixed|string
 */
function api_user_info(){
	$uname = $_POST['uname'];
	$pwd = $_POST['pwd'];
	if($phone=="")
	{
		return apiFail("手机号不存在!");
	}
	$password = 'abc123';
	$sql = " SELECT * FROM td_app_user ";
	$sql2 = " SELECT * FROM td_admin ";
	$user_info = $GLOBALS['db']->getRow($sql);
	$user_info2 = $GLOBALS['db2']->getRow($sql2);
	if(empty($user_info))
	{
		$user =& init_users();
		$GLOBALS['user'] = $user;
		require_once(ROOT_PATH.'/includes/lib_passport.php');
		$other['alias'] = $phone;
		$other['mobile_phone'] = $phone;
		if(false === register($phone, $password, null, $other)){
			return apiFail($GLOBALS['err']->_message[0]);
		}

		$sql = " select user_id from ecs_users where user_name = '$phone' ";
		$user_info = $GLOBALS['db']->getRow($sql);
	}
    return apiSucc($user_info);
}

function api_user_hutchs(){
	
	$last_time = $_POST['last_time'];

	

}

function api_user_login(){
	$uname = $_POST['uname'];
	$pwd = $_POST['pwd'];
	$GLOBALS['cur_grp_id'];
	$sql = " select * from td_app_user where uname = '$uname'";
	$user_Info = $GLOBALS['db']->getAll($sql);
	$org_list = [];
	$room_list = [];
	if(empty($user_Info)){
		return apiFail("用户不存在!");
	}else{
		//循环用户所归属的工作组
		$verifyed = false;
		foreach($user_Info as $user){
			$groupId = $user["grp_id"];
			$userId = $user["user_id"];
			$admin_id = $user["admin_id"];
			debug($user);
			//获取业务数据库连接
			$db2 = $GLOBALS['groupdb'][$groupId];
			//判断用户密码是否匹配
			//密码还未验证
			if(!$verifyed){
				$sql = "select * from td_admin where admin_id= $user[admin_id]";
				$userTemp = $db2->getRow($sql);
				//密码校验规则md5(md5(password)+salt)
				if($userTemp["pwd"] != strtoupper(md5(strtoupper(md5($pwd)) . $userTemp["sault"]))){
					return apiFail("密码错误!");
				}else{
					$verifyed = true;
					//判断用户Token是否存在
					$sql = "select * from td_app_user_token where user_id ='$userId'";
					$userToken = $GLOBALS["db"]->getRow($sql);
					
					$result = -1;
					$token = getToken();
					$exp_time = time() + 3600*24*7;//1小时有效时间
					$token_info = ['exp_time'=>$exp_time,'token'=>$token,'user_id'=>$userId,'grp_id'=>$groupId,'admin_id'=>$admin_id];
					if(empty($userToken)){
						
			debug('insert token' );
			debug($token_info);
						//如果userToken是空的插一条数据，否则修改
						$GLOBALS['db']->autoExecute("td_app_user_token", $token_info, 'INSERT', "");
					}else{
						
			debug('update token' );
			debug($token_info);
						$GLOBALS['db']->autoExecute("td_app_user_token", $token_info, 'UPDATE', "user_id=$userId");
					}
				}
			}
			//工作组信息
			$sql = "select * from td_app_group where grp_id = ' $groupId'";
			$groupInfo = $GLOBALS["db"]->getRow($sql);

			$org_list[] = ['org_id' => $groupInfo['grp_id'],'org_name' => $groupInfo['grp_name'],"org_ico"=>$groupInfo["grp_ico"]];
		}

	}

	$user_data[] = ['token'=>$token,'org_list'=>$org_list];
	return apiSucc($user_data);
}

 function api_user_tokenrefresh(){
	
	//更新token
	$old_token = $GLOBALS['token'];
	$uname = api_get_uname();
	debug($uname);
	$result = -1;
	$token = getToken();
	$exp_time = time() + 3600*24*7;//1小时有效时间
	$token_info = ['exp_time'=>$exp_time,'token'=>$token];
	$GLOBALS['db']->autoExecute("td_app_user_token", $token_info, 'UPDATE', "token='$old_token'");

	$sql = "SELECT t2.grp_id,t2.grp_name,t2.grp_ico FROM td_app_user t1 LEFT JOIN td_app_group t2 ON t1.grp_id = t2.grp_id  
WHERE 1=1
AND t1.uname = '$uname'";
	
	$sql = " select * from td_app_user where uname = '$uname'";
	$user_Info = $GLOBALS['db']->getAll($sql);
	$org_list = [];
	$room_list = [];
	foreach($user_Info as $user){
		$groupId = $user["grp_id"];
		$userId = $user["user_id"];
		$admin_id = $user["admin_id"];
		//工作组信息
		$sql = "select * from td_app_group where grp_id = ' $groupId'";
		$groupInfo = $GLOBALS["db"]->getRow($sql);
		$org_list[] = ['org_id' => $groupInfo['grp_id'],'org_name' => $groupInfo['grp_name'],"org_ico"=>$groupInfo["grp_ico"]];
	}


	$user_data[] = ['token'=>$token,'org_list'=>$org_list];
	return apiSucc($user_data);
 }

function api_user_savechannel(){

	//拿到channel
	$channel = $_POST["channel"];
	//获取前端传过来的userId
	$userId = $_POST["userId"];
	//更新token
	$old_token = $GLOBALS['token'];
	$result = -1;
	//$token = getToken();
	//$exp_time = time() + 3600;//1小时有效时间
	$token_info = ['notify_chanel'=>$channel];
	$GLOBALS['db']->autoExecute("td_app_user_token", $token_info, 'UPDATE', "token='$old_token'");
	return apiSucc("chanel保存成功");
}

function api_user_feedformula(){

	//拿到组id
	$group_id = $GLOBALS['group_id'];
	//获取业务数据库连接
	$db2 = $GLOBALS['groupdb'][$group_id];
	//获取配方列表
	$sql = "select formula_id,formula_name from td_feed_formula where grp_id='$group_id'";
	$formulaAll = $db2->getAll($sql);
	$formula_data[] = ['formula_list'=>$formulaAll];
	return apiSucc($formula_data);
}

function api_user_vanc(){

	//拿到组id
	$group_id = $GLOBALS['group_id'];
	//获取业务数据库连接
	debug("api_user_vanc grouopid: $group_id");
	//debug($GLOBALS['groupdb']);
	$db2 = $GLOBALS['groupdb'][$group_id];
	//获取疫苗列表
	$sql = "select id,vancc_name from tm_vancc where 1=1 ";
	$vanccAll = $db2->getAll($sql);
	$vancc_data[] = ['vancc_list'=>$vanccAll];
	return apiSucc($vancc_data);
}

function api_user_waring(){

	//拿到组id
	$group_id = $GLOBALS['group_id'];
	//获取业务数据库连接
	debug("api_user_waring grouopid: $group_id");
	//debug($GLOBALS['groupdb']);
	$db2 = $GLOBALS['groupdb'][$group_id];
	//获取异常类型列表
	$sql = "select code,code_name from td_code where code_type='app_waring_type' ";
	$waringAll = $db2->getAll($sql);
	$waring_data[] = ['waring_list'=>$waringAll];
	return apiSucc($waring_data);
}

function api_user_changepwd(){

	//拿到组id
	$oldPwd = $_POST["old"];
	$newPwd = $_POST["new"];
	$uname = api_get_uname();
	//获取业务数据库连接
	$sql = " select * from td_app_user where uname = '$uname'";
	$user_Info = $GLOBALS['db']->getAll($sql);
	if(empty($user_Info)){
		return apiFail("用户不存在!");
	}else{
		foreach($user_Info as $user){
			$groupId = $user["grp_id"];
			$userId = $user["user_id"];
			$admin_id = $user["admin_id"];
			
			//获取业务数据库连接
			$db2 = $GLOBALS['groupdb'][$groupId];
			//更新密码
			$sql = " SELECT t1.admin_id,t1.sault,t1.pwd  FROM  td_admin t1 where t1.admin_id='$admin_id'  "; //
			$row = $db2->getRow($sql);
			if(!empty($row))
			{
				$salt = $row["sault"];
				$saveed_pwd = $row['pwd'];
				if($saveed_pwd == strtoupper(md5(strtoupper(md5($oldPwd)) . $salt)))
				{
					$newpwd = strtoupper(md5(strtoupper(md5($newPwd)) . $salt));
					$sql = " update td_admin set pwd='$newpwd' where admin_id='$admin_id'   ";
					$db2->query($sql);
					return apiSucc("更新成功");
				}else{
					return apiFail("旧密码不正确！");
				}
			}
		}
	}
}

function api_user_nickname(){

	//拿到组id
	$name = $_POST["name"];
	$uname = api_get_uname();
	//获取业务数据库连接
	$sql = " select * from td_app_user where uname = '$uname'";
	$user_Info = $GLOBALS['db']->getAll($sql);
	if(empty($user_Info)){
		return apiFail("用户不存在!");
	}else{
		foreach($user_Info as $user){
			$groupId = $user["grp_id"];
			$userId = $user["user_id"];
			$admin_id = $user["admin_id"];
			
			//获取业务数据库连接
			$db2 = $GLOBALS['groupdb'][$groupId];
			//更新昵称
			$sql = " update td_admin set nickname='$name' where admin_id='$admin_id'   ";
			$db2->query($sql);
		}
	}
	return apiSucc("更新成功");
}

function api_user_choosegroup(){

	//更新token
	$old_token = $GLOBALS['token'];

	$new_groupId = $_POST['org_id'];
	if(empty($new_groupId)){
		apiFail('选工作组参数不正确。');
	}
	
	$uname = api_get_uname();
	$sql = "select admin_id from td_app_user where uname = '$uname' and grp_id = $new_groupId ";
	$new_admin_id = $GLOBALS['db']->getOne($sql);

			
	//获取业务数据库连接
	$db2 = $GLOBALS['groupdb'][$new_groupId];
	
	$result = -1;
	//$token = getToken();
	//$exp_time = time() + 3600;//1小时有效时间
	$token_info = ['admin_id'=>$new_admin_id,'grp_id'=>$new_groupId];
	$GLOBALS['db']->autoExecute("td_app_user_token", $token_info, 'UPDATE', "token='$old_token'");


	//返回栏舍信息

	//工作组信息
	$sql = "select * from td_app_group where grp_id = ' $groupId'";
	$groupInfo = $GLOBALS["db"]->getRow($sql);

	//拿到adminId -> admin表 拿到teamid -> rooms表->hutchs

	$sql = "select * from td_admin where admin_id = '$admin_id'";
	$adminInfo = $db2->getRow($sql);
	//拿到teamId
	$teamId = $adminInfo["team_id"];
	//根据teamId 拿到rommList

	$sql = "select r_id,r_name from td_room where 1=1 "; 

	if($teamID>0){
		$sql .= " and teams='$teamId' ";
	}
	$roomArr = $db2->getAll($sql);
	foreach($roomArr as $item){
		//拿到房间号
		$roomId = $item["r_id"];
		$sql = "select h_id,h_name,r_num from td_hutchs where r_room='$roomId'";
		$hutchsAll = $db2->getAll($sql);
		//生成roomlist
		$room_list[] = ["room"=>$item,"hutchsList"=>$hutchsAll];
	}
	

	$func_list=[];
	$func_list[]=['func_name'=>'fygl'];
	$func_list[]=['func_name'=>'fygl_add'];
	$func_list[]=['func_name'=>'iotctl'];
	$func_list[]=['func_name'=>'iotctl_setting'];
	$func_list[]=['func_name'=>'pzjh'];
	$func_list[]=['func_name'=>'pzrw'];
	$func_list[]=['func_name'=>'qsxdrw'];
	$func_list[]=['func_name'=>'qsxdrw_add'];
	$func_list[]=['func_name'=>'swgl_add'];
	$func_list[]=['func_name'=>'swgl'];
	$func_list[]=['func_name'=>'tcap'];
	$func_list[]=['func_name'=>'ygxx'];
	$func_list[]=['func_name'=>'ypxx'];
	$func_list[]=['func_name'=>'yzxx'];
	$user_data[] = ['room_list'=>$room_list,'func_list'=>$func_list];
	return apiSucc($user_data);
}

function api_get_uname(){
	$token = $GLOBALS['token'];
	//根据token取user_id
	$sql = " select t2.uname from td_app_user_token t1 left join td_app_user t2 on t1.user_id = t2.user_id  where t1.token = '$token'";
	//$user_id = $GLOBALS['db']->getOne($sql);

	//根据userid取uname
	//$sql = " select uname from td_app_user where user_id = '$user_id'";
	$uname = $GLOBALS['db']->getOne($sql);
	return $uname;
}

