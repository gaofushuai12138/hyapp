<?php
/**
 * 获取饲料库存信息
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

//饲料库存信息
function api_forage_stock(){
    //$name = $_POST['name'];
	//$team_id = intval($_POST['team_id']);

	//获取业务数据库连接
	$groupId = $GLOBALS['group_id'];
	$db2 = $GLOBALS['groupdb'][$groupId]; 

	$sql1 = "  SELECT td_stock_statistics.id,td_stock_statistics.s_name,td_stock_statistics.s_stock,td_stock_statistics.s_in_stock,td_stock_statistics.s_out_stock,td_outhouse_detail.d_num,td_outhouse.o_time,td_outhouse.o_id FROM td_stock_statistics inner join td_outhouse_detail inner join td_outhouse on td_outhouse.o_id=td_outhouse_detail.o_id and td_stock_statistics.id=td_outhouse_detail.s_id where DATE_SUB(CURDATE(), INTERVAL 90 DAY) <= DATE(td_outhouse.o_time) and td_outhouse_detail.d_num>0 " ;
 
	
	
    $forage_info_list = $db2->getAll($sql1);
	

	$forage_list = [];
	$arr = array();
	$arr[] = 0;
	//$sum = array();
	//$sum[] = 0;
	if(empty($forage_info_list))
	{

		$forage_data[] = ['has_more'=>false,'forage_list'=>[]];
        return apiSucc($forage_data);
    }else{
        $hasMore = true;
		foreach($forage_info_list as $row){
            $arr[$row['s_name']] = $row['d_num'] + $arr[$row['s_name']];
			//$sum[$row['s_name']] = $sum[$row['s_name']] + 1;
		}
		//遍历获得的数据
		foreach($forage_info_list as $row){
			//整合所有要的信息
			//$forage_list[] = ['s_name'=>$row['s_name'],'s_stock'=>$row['s_stock'],'s_in_stock'=>$row['s_in_stock'],'s_out_stock'=>$row['s_out_stock'],'days'=>($arr[$row['s_name']]/$sum[$row['s_name']])];	
		    $forage_list[] = ['s_name'=>$row['s_name'],'s_stock'=>$row['s_stock'],'s_in_stock'=>$row['s_in_stock'],'s_out_stock'=>$row['s_out_stock'],'expect_days'=>round($row['s_stock']/($arr[$row['s_name']]/90))];
		}
	}
	

    $forage_list = a_array_unique($forage_list);
	
	$forage_data[] = ['has_more'=>$hasMore,'forage_list'=>$forage_list];
    return apiSucc($forage_data);
}

//饲料统计信息
function api_forage_statistics(){
    $datetime_from = $_POST['datetime_from'];
	$datetime_to = $_POST['datetime_to'];
	$team_id = intval($_POST['team_id']);
	$forage_id = intval($_POST['forage_id']);

	//获取业务数据库连接
	$groupId = $GLOBALS['group_id'];
	$db2 = $GLOBALS['groupdb'][$groupId]; 
	

	$sql1 = " SELECT td_stock_statistics.id,td_stock_statistics.s_name,td_outhouse_detail.d_num,SUBSTR(td_outhouse.o_time,1,10),td_outhouse.grp_id FROM td_stock_statistics inner join td_outhouse_detail inner join td_outhouse on td_outhouse.o_id=td_outhouse_detail.o_id  and td_outhouse.grp_id=td_outhouse_detail.grp_id and td_stock_statistics.id=td_outhouse_detail.s_id where td_outhouse.o_time >=  '$datetime_from'  and td_outhouse.o_time<= '$datetime_to'  and td_outhouse.grp_id= $team_id  and td_stock_statistics.id= $forage_id  and td_outhouse_detail.d_num>0	" ;
    $sql2 = " SELECT td_stock_statistics.id,td_stock_statistics.s_name,td_warehouse_detail.w_num,SUBSTR(td_warehouse.w_time,1,10),td_warehouse.grp_id FROM td_stock_statistics inner join td_warehouse_detail inner join td_warehouse on td_warehouse.w_id=td_warehouse_detail.w_id  and td_warehouse.grp_id=td_stock_statistics.grp_id and td_stock_statistics.id=td_warehouse_detail.w_sid where td_warehouse.w_time >= '$datetime_from' and td_warehouse.w_time<='$datetime_to' and td_warehouse.grp_id=$team_id and td_stock_statistics.id=$forage_id and td_warehouse_detail.w_num>0 ";
	

    $forage_info_list_out = $db2->getAll($sql1);
	$forage_info_list_in = $db2->getAll($sql2);

	$forage_list = [];
    //出库
	$s_out = [];
	$s_o_time = array();
	$s_out_sum = array();
	$s_out_all = [];

	$sum_out = array();
	//$sum_out[] = 0;
	if(empty($forage_info_list_out))
	{
		$forage_list_out = ["out_data is null"];
		$forage_data[] = ['has_more'=>false,'forage_list'=>$forage_list_out];
		//$forage_data[] = ['has_more'=>false,'forage_list'=>[]];
        return apiSucc($forage_data);
    }else{
        $hasMore = true;
		foreach($forage_info_list_out as $row){
            //$arr[$row['s_name']] = $row['d_num'] + $arr[$row['s_name']];
			$sum_out[$row['SUBSTR(td_outhouse.o_time,1,10)']] = $row['d_num'] + $sum_out[$row['SUBSTR(td_outhouse.o_time,1,10)']];
		}
		//遍历获得的数据
		foreach($forage_info_list_out as $row){
			//整合所有要的信息
		    //$forage_list[] = ['forage_id'=>$row['id'],'s_name'=>$row['s_name'],'d_num'=>$row['d_num'],'o_time'=>$row['SUBSTR(td_outhouse.o_time,1,10)'],'team_id'=>$row['grp_id'],'s_out_sum'=>$sum[$row['SUBSTR(td_outhouse.o_time,1,10)']]];
			//饲料出库日期与数量
			$s_out[] = ['o_time'=>$row['SUBSTR(td_outhouse.o_time,1,10)'],'s_out_sum'=>$sum_out[$row['SUBSTR(td_outhouse.o_time,1,10)']]];
		}
	    $s_out = a_array_unique($s_out);
	//饲料出库日期
	$i=0;
	foreach ($s_out as $row) {
		$s_out_time[]=$s_out[$i]['o_time'];
		$i++;
	}
	//每个日期内饲料出库数量
	$i=0;
	foreach ($s_out as $row) {
		$s_out_sum[]=$s_out[$i]['s_out_sum'];
		$i++;
	}
	$s_out_all[] = ['out_date'=>$s_out_time,'out_num'=>$s_out_sum];
	}
	

	//入库
	$s_in = [];
	$s_i_time = array();
	$s_in_sum = array();
	$s_in_all = [];

	$sum_in = array();
	//$sum_in[] = 0;
	if(empty($forage_info_list_in))
	{
		$forage_list_in = ["in_data is null"];
		$forage_data[] = ['has_more'=>false,'forage_list'=>$forage_list_in];
		//$forage_data[] = ['has_more'=>false,'forage_list'=>[]];
        return apiSucc($forage_data);
    }else{
        $hasMore = true;
		foreach($forage_info_list_in as $row){
            //$arr[$row['s_name']] = $row['d_num'] + $arr[$row['s_name']];
			$sum_in[$row['SUBSTR(td_warehouse.w_time,1,10)']] = (int)$row['w_num'] + $sum_in[$row['SUBSTR(td_warehouse.w_time,1,10)']];
		}
		//遍历获得的数据
		foreach($forage_info_list_in as $row){
			//整合所有要的信息
		    //$forage_list[] = ['forage_id'=>$row['id'],'s_name'=>$row['s_name'],'d_num'=>$row['d_num'],'o_time'=>$row['SUBSTR(td_outhouse.o_time,1,10)'],'team_id'=>$row['grp_id'],'s_out_sum'=>$sum[$row['SUBSTR(td_outhouse.o_time,1,10)']]];
			//饲料入库日期与数量
			$s_in[] = ['w_time'=>$row['SUBSTR(td_warehouse.w_time,1,10)'],'s_in_sum'=>$sum_in[$row['SUBSTR(td_warehouse.w_time,1,10)']]];
		}
	    $s_in = a_array_unique($s_in);
	//饲料入库日期
	$i=0;
	foreach ($s_in as $row) {
		$s_in_time[]=$s_in[$i]['w_time'];
		$i++;
	}
	//每个日期内饲料入库数量
	$i=0;
	foreach ($s_in as $row) {
		$s_in_sum[]=$s_in[$i]['s_in_sum'];
		$i++;
	}
	$s_in_all[] = ['in_date'=>$s_in_time,'in_num'=>$s_in_sum];
	}
	
	
	
	$forage_list[] = ['in_data'=>$s_in_all,'out_data'=>$s_out_all];
	
	$forage_data[] = ['has_more'=>$hasMore,'forage_list'=>$forage_list];
    return apiSucc($forage_data);
}
//下拉框
function api_forage_select(){
	$groupId = $GLOBALS['group_id'];
	$db2 = $GLOBALS['groupdb'][$groupId]; 

	$sql = "SELECT td_stock_statistics.id,td_stock_statistics.s_name FROM td_stock_statistics " ;

	$forage_info_list = $db2->getAll($sql);
    $forage_list = [];
    debug($forage_info_list);
	if(empty($forage_info_list))
	{
		$forage_data[] = ['has_more'=>false,'forage_list'=>[]];
        return apiSucc($forage_data);
    }else{
        $hasMore = true;
		//遍历获得的数据
		foreach($forage_info_list as $row){
			//整合所有要的信息
		    $forage_list[] = ['forage_id'=>$row['id'],'s_name'=>$row['s_name']];
		}
	}
	$forage_data[] = ['has_more'=>$hasMore,'forage_list'=>$forage_list];
    return apiSucc($forage_data);

}

?>