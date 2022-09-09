<?php


/**
 * 获取信息
 * @return mixed|string
 */

 //1 饲喂列表
function api_feed_list(){
	$state = $_POST['state'];
	$page = intval($_POST['page']);
	$size = intval($_POST['size']);
	
	$admin_id = $GLOBALS['admin_id'];
	$groupId = $GLOBALS['group_id'];
	
	//获取业务数据库连接
	$db2 = $GLOBALS['groupdb'][$groupId];
	$sql = "  SELECT * FROM td_feed_plan WHERE 1=1 " ;
	$where = "";
	if($state == 0){//状态类型 0 未发布 1 已发布 2 已完成
		$where .= " and state = 0 ";
		
	}else if($state == 1){
		$where .= " and state = 1 ";
	}else if($state == 2){
		$where .= " and state = 2 ";
	}
	$feed_info_list = $db2->getAll($sql . $where . "  order by add_time desc  limit  " . ($page * $size) . ", $size " . "");
	
	$sql_total = " select count(1) from td_feed_plan  where 1=1 $where ";

	$hasMore = $db2->getOne($sql_total) > ($page+1) * $size ? true : false ;
	$feed_list = [];
	if(empty($feed_info_list))
	{
		$feed_data[] = ['has_more'=>false,'feed_list'=>[]];
		return apiSucc($feed_data);
	}else{
		if(count($feed_info_list) > $size){
			$hasMore = true;
		}
		foreach($feed_info_list as $row){
            //获取栏舍
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
			//获取配方名，通过formula_id 获取formula_name
			$formula_name = $db2->getOne("select formula_name from td_feed_formula t1 , td_feed_plan t2 where t1.formula_id=t2.formula_id");
			//整合所有要的信息
			$feed_list[] = ['feed_plan_id'=>$row['plan_id'],'feed_date'=>$row['feed_date'],'sheep_type'=>$row['sheep_type'], 'areas'=>$areas_list_str, 'formula_name'=>$formula_name, 'feed_weight'=>$row['weight']];
					
		}
	}
	$feed_data[] = ['has_more'=>$hasMore,'feed_list'=>$feed_list];
    return apiSucc($feed_data);
}


//2 
function api_feed_change()
{
	$state = $_POST['state'];
	$plan_id = $_POST['plan_id'];
	$groupId = $GLOBALS['group_id'];
	//获取业务数据库连接
	$db2 = $GLOBALS['groupdb'][$groupId];
	//修改状态
	$state_upd = "update td_feed_plan set state=$state where plan_id = $plan_id ";
	$result = $db2->query($state_upd);
	if ($result){
		return apiSucc("状态更新成功！");
	}else{
		return apiFail("尝试更新失败!");
	}
}


//3 新增饲喂数据
function api_feed_create()
{
	$groupId = $GLOBALS['group_id'];
	//获取业务数据库连接
	$db2 = $GLOBALS['groupdb'][$groupId];
    $admin_id = $GLOBALS['admin_id'];
    $db2 = $GLOBALS['groupdb'][$groupId];
    $team_id= $db2->getOne(" SELECT team_id from td_admin where admin_id = '$admin_id' ");
	//传过来的时间
	$add_time = date("Y-m-d H:i:s");

	//定义新增的数据
	$feed_plan_id = $_POST['feed_plan_id'];
    $feed_date	= $_POST['feed_date'];
	$sheep_type = $_POST['animal_type'];
	$formula_id = $_POST['formula_id'];
	$weight = $_POST['feed_weight'];
	$rooms = $_POST['rooms'];


	//更新hutchs
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
	
	//获取详细配方
	$feed_rate_json = $db2->getOne("select feed_rate_json from td_feed_formula where formula_id=$formula_id");
	$feed_rate_json = json_decode($feed_rate_json,true);
	//配方总重量固定为1000
	$sum_weight = 1000;
	//拼接detail_weight
	$detail_weight_w_sum = 0 ;
	foreach($feed_rate_json as $frj){
		$feed_id = $frj['feed_id'];
		$feed_weight = $frj['weight'];
		//计算要插入的detail_weight,并且限制在两位小数
		$detail_weight_w = $weight * ($feed_weight / $sum_weight);
		$detail_weight_w = round($detail_weight_w , 2);
		//计算过后的detail总重量
		$detail_weight_w_sum = $detail_weight_w_sum + $detail_weight_w ;
		//记录计算过后的detail_weight_w
		$detailww_list = ['detail_weight_w'=>$detail_weight_w];
		//拼接
		$detail_weight[] = ['feed_id'=>$feed_id, 'weight'=>$detail_weight_w];
	}

	//判断总重是否一致
	if($detail_weight_w_sum < $weight){
		//将误差加入最后一个detail_weight中
		$detailww_list_end = end($detailww_list) + ($weight - $detail_weight_w_sum);
		$detail_weight[sizeof($detail_weight)-1]['weight'] = $detailww_list_end ;
	}else{
		//将误差加入最后一个detail_weight中
		$detailww_list_end = end($detailww_list) - ($weight - $detail_weight_w_sum);
		$detail_weight[sizeof($detail_weight)-1]['weight'] = $detailww_list_end ;
	}

	//将数组转换成json格式
	$hutchs = json_encode($hutchs);
	$detail_weight = json_encode($detail_weight);

	//对td_feed_plan插入数据,如果feed_plan_id是0 则插入 ， 否则更新对应id号的信息
	if($feed_plan_id == 0){
		debug("hutchs:".$hutchs);
		debug("feed_date:".$feed_date);
		debug("formula_id:".$formula_id);
		debug("sheep_type:".$sheep_type);
		debug("weight:".$weight);
		debug("addtime".$add_time);
		debug("team_id".$team_id);
		debug("grp_id".$groupId);
		debug("admim_id".$admin_id);
		debug("detail_weight".$detail_weight);
		$feed_inst = "insert into td_feed_plan (hutchs,feed_date,formula_id,sheep_type,weight,add_time,team_id,grp_id , add_user_id ,state ,detail_weight) values ('$hutchs', '$feed_date', $formula_id, $sheep_type, $weight, '$add_time',$team_id,$groupId ,$admin_id ,0 ,'$detail_weight')";
		//插入
		$result1 = $db2->query($feed_inst);
		// debug($result1);
		//判断是否成功
		if($result1){
			return apiSucc("新增数据成功！");
		}else{
			return apiFail("新增数据失败");
		}
		
	}else {
		//取对应id的state，为0就更新
		$feed_planid_state = $db2 -> getOne("select state from td_feed_plan where plan_id=$feed_plan_id");
		if($feed_planid_state == 0){
			//更新操作
		    $feed_info_upd = "update td_feed_plan set hutchs='$hutchs',feed_date='$feed_date',formula_id=$formula_id,sheep_type=$sheep_type,weight=$weight,detail_weight='$detail_weight'  where  plan_id = $feed_plan_id ";
			$result2 = $db2->query($feed_info_upd);
			if($result2){
				return apiSucc("更新数据成功!");
			}else{
				return apiFail("更新数据失败！");
			}
		}else{
			echo "该状态不能更新!";
		}

	}
}

function api_feed_getfeedbyId(){
	$feedId = $_POST["feed_plan_id"];
	$groupId=$GLOBALS['group_id'];
	$db2 = $GLOBALS['groupdb'][$groupId];
	$sql = "select tfp.*,tff.formula_id,tff.formula_name,tff.feed_rate_json,tff.feed_rate_txt,tff.add_user_id,tff.add_time,tff.grp_id from td_feed_plan tfp left join td_feed_formula tff on tfp.formula_id = tff.formula_id  where plan_id ='$feedId'";
	$msg_info = $db2->getAll($sql);
	return apiSucc($msg_info);
	
}

?>