<?php


/**
 * 获取信息
 * @return mixed|string
 */

 
//首页顶部图表
function api_chart_maintop()
{
	$groupId=$GLOBALS['group_id'];
	$db2 = $GLOBALS['groupdb'][$groupId];
	$sql = " SELECT * FROM (SELECT report_day,amount_all FROM td_animal_daily_report_private ORDER BY report_day DESC LIMIT 7 ) a ORDER BY a.report_day ";
	$list = $db2->getAll($sql);
	
	$report_day = [];
	$amount_all = [];

	foreach($list as $row){
		$report_day_arr = explode('-',$row['report_day']);
		$report_day[] = $report_day_arr[1].'-'.$report_day_arr[2];
		$amount_all[] = intval($row['amount_all']);
	}

	$chart_data[] = ['report_day'=>$report_day,'amount_all'=>$amount_all];

    return apiSucc($chart_data);
}

?>