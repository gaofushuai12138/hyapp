<?php


/**
 * 获取信息
 * @return mixed|string
 */

function api_breed_plan(){
	$room_ids = $_POST['room_ids'];

	$rooms_arr=explode(',', $room_ids);
	$admin_id = $GLOBALS['admin_id'];
	$groupId = $GLOBALS['group_id'];
	//debug($rooms_arr);
	//获取业务数据库连接
	$db2 = $GLOBALS['groupdb'][$groupId];

	$sql = "  SELECT t.h_id,t.h_name,t.r_m,IFNULL(t.pre_male_id,'') pre_male_id,IFNULL(t1.r_sn,'') pre_male_sn,IFNULL(t2.h_name,'') pre_huntch_name FROM td_hutchs t 
	       LEFT JOIN td_animals t1 ON t.pre_male_id = t1.r_id  
		   LEFT JOIN td_hutchs t2 ON t1.h_id = t2.h_id " ;
	$where = " where 1=1 and  FIND_IN_SET(t.r_room,'$room_ids') > 0 ";
	//debug($sql. $where );
	$breed_info = $db2->getAll($sql . $where );

	foreach($breed_info as $row){
		$room_list[] = ['hutch_id'=>$row['h_id'],'hutch_name'=>$row['h_name'],'female_cnt'=>$row['r_m'],'pre_male_sn'=>$row['pre_male_sn'],'pre_male_id'=>$row['pre_male_id'],'pre_huntch_name'=>$row['pre_huntch_name']];
	}
	$breed_data[] = ['room_list'=>$room_list];

    return apiSucc($breed_data);
}

function api_breed_selectmale()
{
	$groupId = $GLOBALS['group_id'];
	$db2 = $GLOBALS['groupdb'][$groupId];
	$sql =" SELECT t.animal_id,t.animal_sn FROM td_pudding t LEFT JOIN td_animals t1 ON t.animal_id = t1.r_id
			WHERE t1.r_type = 1 ";
	$animals = $db2->getAll($sql);
	foreach($animals as $row){
		$animal_list[] = ['animal_id'=>$row['animal_id'],'animal_sn'=>$row['animal_sn']];
	}
	$pre_male_data[] = ['pre_male_list'=>$animal_list];
	return apiSucc($pre_male_data);
}

function api_breed_choosemale()
{
	$hutch_id = $_POST['hutch_id'];
	$animal_id = $_POST['animal_id'];
	$groupId = $GLOBALS['group_id'];
	$db2 = $GLOBALS['groupdb'][$groupId];
	$sql =" SELECT t.animal_id,t.animal_sn FROM td_pudding t LEFT JOIN td_animals t1 ON t.animal_id = t1.r_id
			WHERE t1.r_type = 1  ";
	$where .= " and t.animal_id = '$animal_id' ";
	$where .= " and t.status in (0,6) ";  //待配种
	$animals = $db2->getAll($sql.$where);
	if(empty($animals))
	{
		return apiFail("无目标羊或者该羊目前无法配种");
	}
	else
	{
		$state_upd = "update td_hutchs set pre_male_id='$animal_id' where h_id = $hutch_id ";  //设置状态为预配中
		$result = $db2->query($state_upd);
		return apiSucc("设置成功");
	}
}


function api_breed_removemale()
{
	$hutch_id = $_POST['hutch_id'];

	$groupId = $GLOBALS['group_id'];
	$db2 = $GLOBALS['groupdb'][$groupId];

    $state_upd = "update td_hutchs set pre_male_id=0 where h_id = $hutch_id ";  //设置状态为预配中
	$result = $db2->query($state_upd);
	return apiSucc("设置成功");
}

function api_breed_missionlist()
{
	$groupId = $GLOBALS['group_id'];
	$admin_id = $GLOBALS['admin_id'];
	$db2 = $GLOBALS['groupdb'][$groupId];

	$team_id= $db2->getOne(" SELECT team_id from td_admin where admin_id = '$admin_id' ");
	$sql =" SELECT t.mission_id,t.room_name,t.add_time FROM td_breed_mission t 
			WHERE 1 = 1  ";
	$where .= " and t.grp_id = '$groupId' ";
	if( $team_id > 0)
	{
		$where .= " and t.team_id = '$team_id' ";
	}
	$missionlist = $db2->getAll($sql.$where);
	if(empty($missionlist))
	{
		return apiFail("目前没有配种任务");
	}
	else
	{
		foreach($missionlist as $row){
			$mission_id = $row['mission_id'];
			$room_name = $row['room_name'];
			$add_time = $row['add_time'];
			$mission_cnt= $db2->getOne(" SELECT count(*) cnt from td_breed_mission_detail where mission_id = '$mission_id' ");//任务条数
			$finish_cnt= $db2->getOne(" SELECT count(*) cnt from td_breed_mission_detail where mission_id = '$mission_id' and status = 1 ");//已执行条数
			$mission_list[] = ['mission_id'=>$mission_id,'room_name'=>$room_name,'add_time'=>$add_time,'mission_cnt'=>$mission_cnt,'finish_cnt'=>$finish_cnt];
		}
		$mission_data[] = ['mission_list'=>$mission_list];
		return apiSucc($mission_data);
	}
}

function api_breed_createmission()
{
	$groupId = $GLOBALS['group_id'];
	$admin_id = $GLOBALS['admin_id'];
	$db2 = $GLOBALS['groupdb'][$groupId];
	$team_id= $db2->getOne(" SELECT team_id from td_admin where admin_id = '$admin_id' ");
	//传过来的时间
	$add_time = date("Y-m-d H:i:s");
	//传过来的animals参数
	$animals = $_POST['animals'];
	//初始状态设为0
	$status = 0;
	//计数器，为hutch列表最多设置为4
	$cnt = 0 ;
	//记录hutch_name的列表
	$hutch_name_list = [];
	foreach($animals as $ans){ 
		$hutch_id = $ans['hutch_id'];
		$hutch_name = $db2 ->getOne("select h_name from td_hutchs where h_id=$hutch_id");
		//放入huthc_name_list列表
		$hutch_name_list[$cnt] = $hutch_name;
		//最多插入4条
		$cnt = $cnt + 1 ;
		if ($cnt >= 4){
			break;
		}
	}

	//向td_bread_mission表格插入
	//将数组改为字符串格式, 并用逗号分割
	$hutch_name_list_str = implode("," ,$hutch_name_list);
	$tbm_inst = "insert into td_breed_mission(room_name, add_time, status, grp_id , team_id) values('$hutch_name_list_str', '$add_time' , $status ,$groupId , $team_id)" ;
	$result1 = $db2->query($tbm_inst);

	//拿到mission_id
	$mission_id = $db2->getOne("select mission_id from td_breed_mission where add_time='$add_time'");

	foreach($animals as $qq){
		$female_id = $qq['female_id'];
		$hutch_id = $qq['hutch_id'];
		//向td_bread_mission_detail表格插入
		$tbmdt_inst = "insert into td_breed_mission_detail(mission_id,hutch_id,femal_id,add_time,status) values($mission_id,$hutch_id,'$female_id','$add_time',$status)";
	$result2 = $db2->query($tbmdt_inst);

	}
	if($result1 && $result2){
		apiSucc("新增数据成功！" );
	}else{
		apiFail("新增数据失败");
	}
}

function api_breed_missiondetail()
{
	$mission_id = $_POST['mission_id'];

	$groupId = $GLOBALS['group_id'];
	$admin_id = $GLOBALS['admin_id'];
	$db2 = $GLOBALS['groupdb'][$groupId];

	$sql =" SELECT t.mission_id,t.hutch_id,t.femal_id,t.add_time,t.status,t1.r_sn,t2.h_name FROM td_breed_mission_detail t 
			LEFT JOIN td_animals t1 ON t1.r_id = t.femal_id 
			LEFT JOIN td_hutchs t2 ON t.hutch_id = t2.h_id 
			WHERE 1 = 1  ";
	$where .= " and t.mission_id = '$mission_id' ";

	$missionlist = $db2->getAll($sql.$where);
	if(empty($missionlist))
	{
		return apiFail("目前没有配种详情");
	}
	else
	{
		foreach($missionlist as $row){
			$mission_id = $row['mission_id'];
			$hutch_id = $row['hutch_id'];
			$hutch_name = $row['h_name'];
			$male_id = $row['femal_id'];
			$male_sn = $row['r_sn'];
			$status = $row['status'];
			if($status == 0)
			{
				$status_name = "未处理";
			} else if($status == 1)
			{
				$status_name = "已执行";
			} else if($status == 2)
			{
				$status_name = "无法执行";
			}

			$mission_list[] = ['hutch_id'=>$hutch_id,'hutch_name'=>$hutch_name,'male_id'=>$male_id,'male_sn'=>$male_sn,'status'=>$status,'status_name'=>$status_name];
		}
		$mission_data[] = ['room_list'=>$mission_list];
		return apiSucc($mission_data);
	}
}

function api_breed_missionfinish()
{
	$mission_id = $_POST['mission_id'];
	$hutch_id = $_POST['hutch_id'];
	$state = $_POST['state'];
	$note = $_POST['note'];
	$finish_time = date("Y-m-d H:i:s");
	$s_time = date("Y-m-d"); //配种日期

	$groupId = $GLOBALS['group_id'];
	$admin_id = $GLOBALS['admin_id'];
	$db2 = $GLOBALS['groupdb'][$groupId];

	$femal_id= $db2->getOne(" SELECT femal_id from td_breed_mission_detail where mission_id = '$mission_id' ");

	//本身栏舍羊编母羊号
	$sql = "SELECT h_id room_id,rfid_sn,r_sn,r_id anmial_id FROM td_animals WHERE h_id = $hutch_id and r_type = 2 ";
	$home_list = $db2->getAll($sql);
	$cnt = 0;
	$add_breed_ids = [];
	foreach($home_list as $row){
		if(breed_m_check($row['anmial_id'],50) > 0)  //判断50天内是否有配种记录
		{
		}else{
			$cnt =$cnt +1;
			$add_breed_ids[]  = $row['anmial_id'];
		}
	}
	if($cnt ==0){
		//$res['msg']= "该栏舍母羊都已提交组配信息。";
		return apiFail("该栏舍母羊都已提交组配信息。");
	}

	if(breed_f_check($femal_id,10) > 0)
	{
		//$res['msg']="该公羊10天内有配种记录无法再次配种";
		return apiFail("该公羊10天内有配种记录无法再次配种");
	}

	//公羊移栏
	$sql_f = "SELECT h_id room_id,rfid_sn,r_sn,r_id anmial_id FROM td_animals WHERE r_id = '$femal_id' and r_type=1 ";  //公羊信息
	$f_info = $db2->getRow($sql_f);
	if(!empty($f_info) )
	{
		if(breed_n_check($femal_id) > 0) //有未撤公羊的栏舍，先做撤公羊
		{
			$sql_breed = " UPDATE td_breed SET f_state = 1, g_time = CURDATE() WHERE f_id = '$femal_id' and f_state=0 ";  
			$db2-> query($sql_breed);
		}
		if( $f_info['room_id'] != $hutch_id ){
			doMoveAnimal($f_info['anmial_id'],$f_info['room_id'],$hutch_id);
		}
	} else 
	{
		//$res['msg']=$f_info['r_sn']. "该公羊信息不存在";
		return apiFail("该公羊信息不存在");
	}


	$team_id= $db2->getOne(" SELECT team_id from td_admin where admin_id = '$admin_id' ");
	
	$state_upd = "update td_breed_mission_detail set finish_time='$finish_time', status='$state',finish_note='$note' where mission_id = $mission_id and hutch_id = $hutch_id ";  //更新td_breed_mission_detail，执行中
	$result = $db2->query($state_upd);
	if($result)
	{
		if($state==1)
		{
			
			//femal_id
			//执行配种操作
			$sql_breed = " insert into td_breed(f_id,m_id,h_id,s_time,if_breed,grp_id,team_id) SELECT '$femal_id', r_id ,'$hutch_id','$s_time' ,3,'$groupId', '$team_id' from td_animals where h_id = '$hutch_id' and r_type = 2 ";  
			$db2->query($sql_breed);

			//修改公羊母羊状态（配种中）
			$sql_state = " UPDATE td_pudding SET STATUS = 2 WHERE animal_id IN (SELECT r_id FROM td_animals WHERE h_id='$hutch_id' )   "; 
			$db2-> query($sql_state);

			//修改公羊的与配母羊数
			$cnt = $db2->getOne(" SELECT count(*) cnt from td_animals where h_id = '$hutch_id' and r_state =1 ");
			$cnt = $cnt - 1;//母羊数
			$sql_cnt = " UPDATE td_pudding SET m_target_cnt = m_target_cnt + $cnt WHERE animal_id = '$femal_id'  ";
			$db2-> query($sql_cnt);
		}
		return apiSucc("更新成功");
	}
}

//检查公羊配种日期是否在10天内,指定栏舍,指定时间内
function breed_f_check($f_sn,$num){
	$groupId = $GLOBALS['group_id'];
	$db2 = $GLOBALS['groupdb'][$groupId];

	$sql = " SELECT COUNT(*) cnt FROM td_breed WHERE 1=1 and  DATEDIFF(NOW(),s_time) <  $num AND f_id = '$f_sn' ";
	$cnt = $db2->getOne($sql);
	return $cnt;
}

//检查母羊配种日期是否在50天内
function breed_m_check($m_sn,$num){
	$groupId = $GLOBALS['group_id'];
	$db2 = $GLOBALS['groupdb'][$groupId];
	$sql = " SELECT COUNT(*) cnt FROM td_breed WHERE DATEDIFF(NOW(),s_time) <  $num AND m_id  = '$m_sn' ";
	$cnt = $db2->getOne($sql);
	return $cnt;
}

//检查公羊配种日期是否存在，不指定栏舍,不指定时间内
function breed_n_check($f_sn){
	$groupId = $GLOBALS['group_id'];
	$db2 = $GLOBALS['groupdb'][$groupId];
	$sql = " SELECT COUNT(*) cnt FROM td_breed WHERE 1=1 and f_state=0 AND f_id ='$f_sn'  ";
	$cnt = $db2->getOne($sql);
	return $cnt;
}

function doMoveAnimal($anmimal_id,$from_room_id,$to_room_id = 0)
{
	$groupId = $GLOBALS['group_id'];
	$db2 = $GLOBALS['groupdb'][$groupId];
	$move_log=[
			'animal_id'=>$anmimal_id,
			'in_h_id'=>$to_room_id,
			'out_h_id'=>$from_room_id,
			'move_date'=>date('Y-m-d H:m:i'),
			'grp_id'=>$GLOBALS['group_id']
		];
	//更新移栏记录 移入为空的数据
	$sql = " select * from td_animal_move  where  animal_id = $anmimal_id and in_h_id = 0 ";
	$moveRecode = $db2->getRow($sql);
	if(empty($moveRecode))
	{
		$db2->autoExecute("td_animal_move", $move_log, 'INSERT', "");
	}else{
		$sql = " update td_animal_move set in_h_id = $to_room_id  where  animal_id = $anmimal_id and in_h_id = 0 "; 
		$db2->query($sql);
	}
	$r_movetime = date('Y-m-d',time());
	if($to_room_id>0){
		$sql = "update td_animals set h_id = $to_room_id, r_movetime = '$r_movetime' where r_id = $anmimal_id";
	}else{
		$sql = "update td_animals set h_id = 0 where r_id = $anmimal_id";
	}
	$db2->query($sql);
}

?>