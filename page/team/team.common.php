<?php
include_once(G5_LIB_PATH.'/thumbnail.lib.php');

$sql = "select 
			T1.*, 
			T2.good_cnt,
			T3.is_good,
			T4.tg_id,
			T5.*
		from sweb_team T1 
		left outer join (
			select te_id, count(*) as good_cnt from sweb_team_good group by te_id
		) T2 
		ON T1.te_id = T2.te_id 
		left outer join (
			select te_id, count(*) as is_good from sweb_team_good where mb_id = '".$member['mb_id']."' group by te_id
		) T3 
		ON T1.te_id = T3.te_id
		left outer join (
			select te_id, tg_id from sweb_team_good where mb_id = '".$member['mb_id']."' and te_id = '{$te_id}'
		) T4
		ON T1.te_id = T4.te_id
		left outer join (
			select 
				file_id,
				file_table_idx,
				file_name,
				file_rename,
				file_desc
			from {$sweb['file_table']} 
			where file_code='team' and file_sub_code='logo' and file_order=0
		) T5
		ON T1.te_id = T5.file_table_idx
		where T1.te_id = '{$te_id}'";
$parent = sql_fetch($sql);


$error_msg = "";
if(!$parent){
	$error_msg = "존재하지 않는 팀입니다.";
}

$is_admin_team = false;
if($is_admin == 'super' || $parent['mb_id'] == $member['mb_id']){
	$is_admin_team = true;
}


$is_member_team = false;
$sql = "select * from sweb_team_join where te_id = '{$te_id}' and mb_id='".$member['mb_id']."' ";
$player = sql_fetch($sql);
if($player['tj_id']){
	$is_member_team = true;
}

$f_path = G5_DATA_PATH . "/file/team/";
$f_url = G5_DATA_URL . "/file/team/";
$thumb = thumbnail($parent['file_rename'], $f_path, $f_path, 600, 0, false, false, 'center', false, '80/0.5/3');



?>