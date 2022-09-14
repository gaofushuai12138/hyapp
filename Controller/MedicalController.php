<?php
/**
 * 获取药品库存信息
 * @return mixed|string
 */

function a_array_unique($array)//去重
{
	$out = array();
//foreach ($array as $key => $value) {
	foreach ($array as $value) {
		if (!in_array($value, $out))//判断元素值是否在数组里
		   {
	   //$out[$key] = $value;
		   $out[] = $value;
		   }
		}
	return $out;
} 
 //药品库存信息
function api_medical_stock(){
    //$name = $_POST['name'];
	//$team_id = intval($_POST['team_id']);

	//获取业务数据库连接
	$groupId = $GLOBALS['group_id'];
	$db2 = $GLOBALS['groupdb'][$groupId]; 

	$sql1 = "  SELECT td_stock_medication.id,td_stock_medication.m_name,td_stock_medication.m_stock,td_stock_medication.m_in_stock,td_stock_medication.m_out_stock,td_medkit_out_detail.m_num,td_medkit_out.m_time,td_medkit_out.m_id FROM td_stock_medication inner join td_medkit_out_detail inner join td_medkit_out on td_medkit_out.m_id=td_medkit_out_detail.m_id and td_stock_medication.id=td_medkit_out_detail.m_wid where DATE_SUB(CURDATE(), INTERVAL 90 DAY) <= DATE(td_medkit_out.m_time) and td_medkit_out_detail.m_num>0 " ;
 
	
	
    $medical_info_list = $db2->getAll($sql1);
	

	$medical_list = [];
	$arr = array();
	$arr[] = 0;
	//$sum = array();
	//$sum[] = 0;
	if(empty($medical_info_list))
	{
		$medical_data[] = ['has_more'=>false,'medical_list'=>[]];
        return apiSucc($medical_data);
    }else{
        $hasMore = true;
		foreach($medical_info_list as $row){
            $arr[$row['m_name']] = $row['m_num'] + $arr[$row['m_name']];
			//$sum[$row['s_name']] = $sum[$row['s_name']] + 1;
		}
		//遍历获得的数据
		foreach($medical_info_list as $row){
			//整合所有要的信息
			//$forage_list[] = ['s_name'=>$row['s_name'],'s_stock'=>$row['s_stock'],'s_in_stock'=>$row['s_in_stock'],'s_out_stock'=>$row['s_out_stock'],'days'=>($arr[$row['s_name']]/$sum[$row['s_name']])];	
		    $medical_list[] = ['m_name'=>$row['m_name'],'m_stock'=>$row['m_stock'],'m_in_stock'=>$row['m_in_stock'],'m_out_stock'=>$row['m_out_stock'],'expect_days'=>round($row['m_stock']/($arr[$row['m_name']]/90))];
		}
	}

	
	
    $medical_list = a_array_unique($medical_list);
	
	$medical_data[] = ['has_more'=>$hasMore,'medical_list'=>$medical_list];
    return apiSucc($medical_data);
}

//药品统计信息
function api_medical_statistics(){
    $datetime_from = $_POST['datetime_from'];
	$datetime_to = $_POST['datetime_to'];
	$team_id = intval($_POST['team_id']);
	$medical_id = intval($_POST['medical_id']);

	//获取业务数据库连接
	$groupId = $GLOBALS['group_id'];
	$db2 = $GLOBALS['groupdb'][$groupId]; 
	

	$sql1 = " SELECT td_stock_medication.id,td_stock_medication.m_name,td_medkit_out_detail.m_num,SUBSTR(td_medkit_out.m_time,1,10),td_medkit_out.grp_id FROM td_stock_medication inner join td_medkit_out_detail inner join td_medkit_out on td_medkit_out.m_id=td_medkit_out_detail.m_id  and td_medkit_out.grp_id=td_stock_medication.grp_id and td_stock_medication.id=td_medkit_out_detail.m_wid where td_medkit_out.m_time >= '$datetime_from' and td_medkit_out.m_time<='$datetime_to' and td_medkit_out.grp_id=$team_id and td_stock_medication.id=$medical_id and td_medkit_out_detail.m_num>0	" ;
    $sql2 = " SELECT td_stock_medication.id,td_stock_medication.m_name,td_medkit_in_deatail.w_num,SUBSTR(td_medkit_in.w_time,1,10),td_medkit_in.grp_id FROM td_stock_medication inner join td_medkit_in_deatail inner join td_medkit_in on td_medkit_in.w_id=td_medkit_in_deatail.w_id  and td_medkit_in.grp_id=td_stock_medication.grp_id and td_stock_medication.id=td_medkit_in_deatail.w_mid where td_medkit_in.w_time >= '$datetime_from' and td_medkit_in.w_time<='$datetime_to' and td_medkit_in.grp_id=$team_id and td_stock_medication.id=$medical_id and td_medkit_in_deatail.w_num>0 ";
	

    $medical_info_list_out = $db2->getAll($sql1);
	$medical_info_list_in = $db2->getAll($sql2);

	$medical_list = [];
    //出库
	$m_out = [];
	$m_o_time = array();
	$m_out_sum = array();
	$m_out_all = [];

	$sum_out = array();
	//$sum_out[] = 0;
	if(empty($medical_info_list_out))
	{
		$medical_list_out = ["out_data is null"];
		$medical_data[] = ['has_more'=>false,'medical_list'=>$medical_list_out];
		
        return apiSucc($medical_data);
    }else{
        $hasMore = true;
		foreach($medical_info_list_out as $row){
            //$arr[$row['s_name']] = $row['d_num'] + $arr[$row['s_name']];
			$sum_out[$row['SUBSTR(td_medkit_out.m_time,1,10)']] = $row['m_num'] + $sum_out[$row['SUBSTR(td_medkit_out.m_time,1,10)']];
		}
		//遍历获得的数据
		foreach($medical_info_list_out as $row){
			//整合所有要的信息
		    
			//药品出库日期与数量
			$m_out[] = ['m_time'=>$row['SUBSTR(td_medkit_out.m_time,1,10)'],'m_out_sum'=>$sum_out[$row['SUBSTR(td_medkit_out.m_time,1,10)']]];
		}
	    $m_out = a_array_unique($m_out);
	//药品出库日期
	$i=0;
	foreach ($m_out as $row) {
		$m_out_time[]=$m_out[$i]['m_time'];
		$i++;
	}
	//每个日期内药品出库数量
	$i=0;
	foreach ($m_out as $row) {
		$m_out_sum[]=$m_out[$i]['m_out_sum'];
		$i++;
	}
	$m_out_all[] = ['out_date'=>$m_out_time,'out_num'=>$m_out_sum];
	}
	

	//入库
	$m_in = [];
	$m_i_time = array();
	$m_in_sum = array();
	$m_in_all = [];

	$sum_in = array();
	//$sum_in[] = 0;
	if(empty($medical_info_list_in))
	{
		$medical_list_in = ["in_data is null"];
		$medical_data[] = ['has_more'=>false,'medical_list'=>$medical_list_in];
		
        return apiSucc($medical_data);
    }else{
        $hasMore = true;
		foreach($medical_info_list_in as $row){
            
			$sum_in[$row['SUBSTR(td_medkit_in.w_time,1,10)']] = $row['w_num'] + $sum_in[$row['SUBSTR(td_medkit_in.w_time,1,10)']];
		}
		//遍历获得的数据
		foreach($medical_info_list_in as $row){
			//整合所有要的信息
		    
			//药品入库日期与数量
			$m_in[] = ['w_time'=>$row['SUBSTR(td_medkit_in.w_time,1,10)'],'m_in_sum'=>$sum_in[$row['SUBSTR(td_medkit_in.w_time,1,10)']]];
		}
		//debug($m_in);
	    $m_in = a_array_unique($m_in);
	//药品入库日期
	$i=0;
	foreach ($m_in as $row) {
		$m_in_time[]=$m_in[$i]['w_time'];
		$i++;
	}
	//每个日期内药品入库数量
	$i=0;
	foreach ($m_in as $row) {
		$m_in_sum[]=$m_in[$i]['m_in_sum'];
		$i++;
	}
	$m_in_all[] = ['in_date'=>$m_in_time,'in_num'=>$m_in_sum];
	}
	
	
	
	$medical_list[] = ['in_data'=>$m_in_all,'out_data'=>$m_out_all];
	
	$medical_data[] = ['has_more'=>$hasMore,'medical_list'=>$medical_list];
    return apiSucc($medical_data);
}
//下拉框
function api_medical_select(){
	$groupId = $GLOBALS['group_id'];
	$db2 = $GLOBALS['groupdb'][$groupId]; 

	$sql = "SELECT td_stock_medication.id,td_stock_medication.m_name FROM td_stock_medication " ;

	$medical_info_list = $db2->getAll($sql);
    $medical_list = [];
    debug($medical_info_list);
	if(empty($medical_info_list))
	{
		$medical_data[] = ['has_more'=>false,'medical_list'=>[]];
        return apiSucc($medical_data);
    }else{
        $hasMore = true;
		//遍历获得的数据
		foreach($medical_info_list as $row){
			//整合所有要的信息
		    $medical_list[] = ['medical_id'=>$row['id'],'m_name'=>$row['m_name']];
		}
	}
	$medical_data[] = ['has_more'=>$hasMore,'medical_list'=>$medical_list];
    return apiSucc($medical_data);

}

?>