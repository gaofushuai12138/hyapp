<?php
/**
 * 获取员工信息
 * @return mixed|string
 */

function api_staff_list(){
    $name = $_POST['name'];
	$team_id = intval($_POST['team_id']);
	
	$groupId = $GLOBALS['group_id'];
	//获取业务数据库连接
	$db2 = $GLOBALS['groupdb'][$groupId]; 
    
	$sql1 = "  SELECT * FROM td_staff,td_team WHERE td_staff.team_id = td_team.team_id  " ;
    $sql2= "  SELECT * FROM  td_staff inner join td_team on td_team.team_id= td_staff.team_id where td_team.team_id=$team_id and td_staff.name='$name'" ;
	$sql3= "  SELECT * FROM  td_staff inner join td_team on td_team.team_id= td_staff.team_id where td_team.team_id=$team_id " ;
	$sql4= "  SELECT * FROM  td_staff inner join td_team on td_team.team_id= td_staff.team_id where td_staff.name='$name'" ;
    //获取数据表所有信息
	//$vanc_info_list = $db2->getAll($sql  .$where . "  order by add_time desc  limit  " . ($page * $size) . ", $size " . "");
	if($name == NULL and $team_id == NULL){
	    $staff_info_list = $db2->getAll($sql1);
	}else if($name == NULL and $team_id != NULL){ 
        $staff_info_list = $db2->getAll($sql3);
	}else if($name != NULL and $team_id == NULL){ 
        $staff_info_list = $db2->getAll($sql4);
	}else{
		$staff_info_list = $db2->getAll($sql2);
	}
    
	$staff_list = [];
	if(empty($staff_info_list))
	{
		$hasMore = false;
		$staff_data[] = ['has_more'=>false,'staff_list'=>[]];
        return apiSucc($staff_data);
    }else{
        $hasMore = true;
		//遍历获得的数据
		foreach($staff_info_list as $row){
			//整合所有要的信息
			$staff_list[] = ['team_name'=>$row['team_name'],'name'=>$row['name'],'position'=>$row['position'],'sex'=>$row['sex'],'age'=>$row['age'],'entry_time'=>$row['entry_time']];	
		}
	}
	$staff_data[] = ['has_more'=>$hasMore,'staff_list'=>$staff_list];
    return apiSucc($staff_data);
}

function api_staff_teamselect(){
	$groupId = $GLOBALS['group_id'];
	$db2 = $GLOBALS['groupdb'][$groupId]; 

	$sql = "SELECT td_team.team_id,td_team.team_name FROM td_team " ;

	$team_info_list = $db2->getAll($sql);
    $team_list = [];
    debug($team_info_list);
	if(empty($team_info_list))
	{
		$team_data[] = ['has_more'=>false,'team_list'=>[]];
        return apiSucc($team_data);
    }else{
        $hasMore = true;
		//遍历获得的数据
		foreach($team_info_list as $row){
			//整合所有要的信息
		    $team_list[] = ['team_id'=>$row['team_id'],'team_name'=>$row['team_name']];
		}
	}
	$team_data[] = ['has_more'=>$hasMore,'team_list'=>$team_list];
    return apiSucc($team_data);

}


?>
