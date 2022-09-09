<?php


/**
 * 获取信息
 * @return mixed|string
 */

 
//4 防疫计划函数
function api_vanc_list()
{
	$state = $_POST['state'];
	$page = intval($_POST['page']);
	$size = intval($_POST['size']);	
	$groupId = $GLOBALS['group_id'];
	
	//获取业务数据库连接
	$db2 = $GLOBALS['groupdb'][$groupId]; 

	$sql = "  SELECT * FROM td_vanc_plan WHERE 1=1" ;
	$where = "";
	if($state == 0){//状态类型 0 未发布 1 已发布 2 已完成
		$where .= " and state = 0 ";
		
	}else if($state == 1){
		$where .= " and state = 1 ";
	}else if($state == 2){
		$where .= " and state = 2 ";
	}
    //获取数据表所有信息
	$vanc_info_list = $db2->getAll($sql  .$where . "  order by add_time desc  limit  " . ($page * $size) . ", $size " . "");
	//$vanc_info_list = $db2->getAll($sql);
	$sql_total = " select count(*) from td_vanc_plan  ";
	$hasMore = $db2->getOne($sql_total) > ($page+1) * $size ? true : false ;
	$vanc_list = [];
	if(empty($vanc_info_list))
	{
		
		$vanc_data[] = ['has_more'=>false,'vanc_list'=>[]];
		return apiSucc($vanc_data);
	}else{
		if(count($vanc_info_list) > $size){
			$hasMore = true;
		}
		//遍历获得的数据
		foreach($vanc_info_list as $row){
			$hutchs = $row['hutchs'];
			$hutchs = json_decode($hutchs,true);
			$cnt = 0 ;
			foreach($hutchs as $hrow){
				//获取栏舍名
				$hutch_name = $hrow['hutch_name'];
				//栏舍集合
				$areas_list[$cnt] = $hutch_name ;
				$cnt = $cnt +1 ;
			}
			//将栏舍集合数组改为字符串格式, 并用逗号分割
			$areas_list_str = implode("," ,$areas_list);
			//获取疫苗名称vanc_name
			$vanc_name = $db2->getOne("select vancc_name from tm_vancc t1 , td_vanc_plan t2 where t1.id=t2.vancc_id");
			//整合所有要的信息
			$vanc_list[] = ['vanc_plan_id'=>$row['plan_id'],'plan_date_f'=>$row['plan_date_f'],'plan_date_t'=>$row['plan_date_t'],'areas'=>$areas_list_str,'vanc_name'=>$vanc_name,'finished_cnt'=>$row['finished_cnt'],'total_cnt'=>$row['total_cnt']];	
		}
	}
	$vanc_data[] = ['has_more'=>$hasMore,'vanc_list'=>$vanc_list];
    return apiSucc($vanc_data);
}


// 5修改饲喂计划状态函数
function api_vanc_change(){
	$state = $_POST['state'];
	$plan_id = $_POST['plan_id'];
	$groupId = $GLOBALS['group_id'];
	//获取业务数据库连接
	$db2 = $GLOBALS['groupdb'][$groupId];
	//修改状态
	$state_upd = "update td_vanc_plan set state=$state where plan_id = $plan_id ";
	$result = $db2->query($state_upd);
	if ($result){
		return apiSucc("状态更新成功！");
	}else{
		return apiFail("尝试更新失败!");
	}
		
}


//6 修改新增饲喂计划
function api_vanc_update(){
	$groupId = $GLOBALS['group_id'];
	//就是add_user_id
	$admin_id = $GLOBALS['admin_id'];
	//获取业务数据库连接
	$db2 = $GLOBALS['groupdb'][$groupId];
	//定义新增的数据
	$vanc_plan_id = $_POST['vanc_plan_id'];
    $plan_date_f = $_POST['plan_date_f'];
	$plan_date_t = $_POST['plan_date_t'];
	$total_cnt = $_POST['total_cnt'];
	$vancc_id = $_POST['vanc_id'];
	$add_time = date("Y-m-d H:i:s");

	//更新hutchs
	$rooms = $_POST['rooms'];
	foreach($rooms as $rlt){
		$room_id = $rlt['room_id'];
		$hutch_id_list = $rlt['hutchs'];
		foreach($hutch_id_list as $tt){
			$hutch_id = $tt['hutch_id'] ;
			$hutch_name = $db2 ->getOne("select h_name from td_hutchs where h_id=$hutch_id");
			//拼接hutchs
			$hutchs[] = ['hutch_id'=>$hutch_id,'hutch_name'=>$hutch_name];
		}
	}
	//将数组转换成json格式
	$hutchs = json_encode($hutchs);


	if($vanc_plan_id == 0)
	{
		//对td_vanc_plan插入数据
		$vanc_inst = "insert into td_vanc_plan(plan_date_f,plan_date_t,total_cnt,hutchs,state , add_time , add_user_id ,grp_id ,vancc_id ,finished_cnt) values('$plan_date_f','$plan_date_t' ,$total_cnt,'$hutchs' , 0 ,'$add_time' , $admin_id ,$groupId ,$vancc_id ,0)";
		$result1 = $db2->query($vanc_inst);
		if($result1){
			return apiSucc("新增数据成功！");
		}else{
			return apiFail("新增数据失败");
		}

	}else{

		//取对应id的state，为0就更新
		$vanc_planid_state = $db2 -> getOne("select state from td_vanc_plan where plan_id=$vanc_plan_id");
		if($vanc_planid_state == 0){
			//更新操作
		    $feed_info_upd = "update td_vanc_plan set hutchs='$hutchs',plan_date_f='$plan_date_f',plan_date_t='$plan_date_t',total_cnt=$total_cnt,vancc_id=$vancc_id  where  plan_id = $vanc_plan_id ";
			$result2 = $db2->query($feed_info_upd);
			if($result2){
				return apiSucc("更新数据成功!");
			}else{
				return apiFail("更新数据失败！");
			}
		}else{
			echo "该状态不能更新！";
		}

	}

    
}


// 7疫苗详情函数
function api_vanc_detail(){
	$state = $_POST['state'];
	$vanc_plan_id = $_POST['vanc_plan_id'];
	$groupId = $GLOBALS['group_id'];
	//获取业务数据库连接
	$db2 = $GLOBALS['groupdb'][$groupId];

	$sql = "SELECT * FROM td_vanc_plan WHERE plan_id = $vanc_plan_id ";
	$result = $db2->getAll($sql);


	$vanc_data[] = ['plan_date_f'=>$result[0]['plan_date_f'],'plan_date_t'=>$result[0]['plan_date_t'],'vanc_id'=>$result[0]['vancc_id'],'hutchs'=>$result[0]['hutchs']];
    return apiSucc($vanc_data);
		
}

?>