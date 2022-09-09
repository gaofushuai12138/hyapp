<?php


/**
 * 获取信息
 * @return mixed|string
 */

 
//1 清扫消毒任务列表函数
function api_clean_list()
{
	$state = $_POST['state'];
	$page = intval($_POST['page']);
	$size = intval($_POST['size']);	
	$groupId = $GLOBALS['group_id'];
	
	//获取业务数据库连接
	$db2 = $GLOBALS['groupdb'][$groupId]; 

	$sql = "  SELECT * FROM td_clean_plan WHERE 1=1" ;
	$where = "";
	if($state == 0){//状态类型 0 未确认 1 已确认 2 已执行
		$where .= " and state = 0 ";
		
	}else if($state == 1){
		$where .= " and state = 1 ";
	}else if($state == 2){
		$where .= " and state = 2 ";
	}
    //debug($sql);
    //获取数据表所有信息
	$clean_info_list = $db2->getAll($sql  .$where . "  order by add_time desc  limit  " . ($page * $size) . ", $size " . "");
    // $clean_info_list = $db2->getAll($sql);
    //debug($clean_info_list);

	$sql_total = " select count(*) from td_clean_plan  ";
	$hasMore = $db2->getOne($sql_total) > ($page+1) * $size ? true : false ;
	$clean_list = [];
	if(empty($clean_info_list))
	{
		
		$clean_data[] = ['has_more'=>false,'clean_list'=>[]];
		return apiSucc($clean_data);
	}else{
		if(count($clean_info_list) > $size){
			$hasMore = true;
		}
		//遍历获得的数据
		foreach($clean_info_list as $row){
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
			
			//整合所有要的信息
			$clean_list[] = ['clean_id'=>$row['clean_id'],'clean_date'=>$row['clean_date'],'type'=>$row['type'],'areas'=>$areas_list_str];	
		}
	}
	$clean_data[] = ['has_more'=>$hasMore,'clean_list'=>$clean_list];
    return apiSucc($clean_data);
}


// 2 修改清扫消毒计划状态函数
function api_clean_change(){
	$state = $_POST['state'];
	$clean_id = $_POST['clean_id'];
	$groupId = $GLOBALS['group_id'];
	//获取业务数据库连接
	$db2 = $GLOBALS['groupdb'][$groupId];
	//修改状态
	$state_upd = "update td_clean_plan set state=$state where clean_id = $clean_id ";
	$result = $db2->query($state_upd);
	if ($result){
		return apiSucc("状态更新成功！");
	}else{
		return apiFail("尝试更新失败!");
	}
		
}


//3 新增清扫消毒计划
function api_clean_update(){
	$groupId = $GLOBALS['group_id'];
	//就是add_user_id
	$admin_id = $GLOBALS['admin_id'];
	//获取业务数据库连接
	$db2 = $GLOBALS['groupdb'][$groupId];
	//定义新增的数据
	$clean_id = $_POST['clean_id'];
    $clean_date = $_POST['clean_date'];
	$type = $_POST['type'];
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


	if($clean_id == 0)
	{
		//对td_clean_plan插入数据
		$clean_inst = "insert into td_clean_plan(clean_date,type,hutchs,state , add_time , add_user_id ,grp_id ) values('$clean_date','$type' ,'$hutchs' , 0 ,'$add_time' , $admin_id ,$groupId )";
		$result1 = $db2->query($clean_inst);
		if($result1){
			return apiSucc("新增数据成功！");
		}else{
			return apiFail("新增数据失败");
		}

	}else{

		//取对应id的state，为0就更新
		$clean_planid_state = $db2 -> getOne("select state from td_clean_plan where clean_id=$clean_id");
		if($clean_planid_state == 0){
			//更新操作
		    $clean_info_upd = "update td_clean_plan set hutchs='$hutchs',clean_date='$clean_date',type=$type  where  clean_id = $clean_id ";
			$result2 = $db2->query($clean_info_upd);
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


// 4 清扫详情函数
function api_clean_detail(){
	$clean_id = $_POST['clean_id'];
	$groupId = $GLOBALS['group_id'];
	//获取业务数据库连接
	$db2 = $GLOBALS['groupdb'][$groupId];

	$sql = "SELECT * FROM td_clean_plan WHERE clean_id = $clean_id ";
	$result = $db2->getAll($sql);
    

    //根据hutch_id,查找room_id
    $hutchs_y = $result[0]['hutchs'];
    $hutchs_y = json_decode($hutchs_y,true);
    
    $cnt = 0 ;
    foreach($hutchs_y as $hrow){
        //获取栏舍id
        $hutch_id = $hrow['hutch_id']; 
        //根据hutch_id,查找room_id
        $sql2 = "SELECT r_room FROM td_hutchs WHERE h_id = $hutch_id";
        $room_id = $db2 ->getOne($sql2);
        $room_id_list[$cnt] = $room_id;       
        //栏舍id集合
        $hutch_id_list[$cnt] = $hutch_id ;

        $cnt = $cnt +1 ;
    }
    
    //为相同的room_id组合拼接hutch_id_list_r
    for ($i=0 ; $i < sizeof($room_id_list) ; $i++){
        //定义同一room_id的栏舍id集合
        $hutch_id_list_r = [];
        $rhc = 0;
        for($j=$i+1 ; $j <= sizeof($room_id_list) ; $j++){
            //判断是否为相同的room_id         
            if($room_id_list[$i] == $room_id_list[$j]){
                //拼接对应的hutch_id_list_r
                $hutch_id_list_r[$rhc] = $hutch_id_list[$j]; 
                $rhc ++; 
                      
            }else{
                $hutch_id_list_r[$rhc] = $hutch_id_list[$i];
            }
                  
        }

        //将栏舍集合数组改为字符串格式, 并用逗号分割
        $hutch_id_list_r = implode("," ,$hutch_id_list_r);
        
        //拼接rooms
        $rooms[] = ['room_id'=>$room_id_list[$i] , 'hutchs'=>$hutch_id_list_r];
        $i = $i + $rhc; 
    }

	$clean_data[] = ['clean_date'=>$result[0]['clean_date'],'type'=>$result[0]['type'],'rooms'=>$rooms];
    return apiSucc($clean_data);
		
}

?>