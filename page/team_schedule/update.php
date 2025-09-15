<?php
include_once "./_common.php";
include_once "./setting.php";
include_once "../team/team.common.php";

$result['status'] = true;
$result['msg'] = "";
$result['key'] = "";
$result['html'] = "";
$result['reload'] = false;

if($error_msg){
	$result['status'] = false;
	$result['msg'] = $error_msg;
	echo json_encode($result);
	exit;
}

if($key){
	$sql = "select * from {$table_name} where {$key_column}='{$key}'";
	$view = sql_fetch($sql);
	$auth_mb_id = $view['mb_id'];
}else{
	$auth_mb_id = 'admin';
}

if($w == ""){
	$msg = fn_authCheckAjax($sweb['write_level'], "");
}else if($w == "u"){
	$msg = fn_authCheckAjax($sweb['write_level'], $auth_mb_id);
}else if($w == "d"){
	$msg = fn_authCheckAjax($sweb['delete_level'], $auth_mb_id);
}

if($msg){
	$result['status'] = false;
	$result['msg'] = $msg;
	echo json_encode($result);
	exit;
}

// 공통 column 세팅
$sql_common = "";
foreach($sweb['column'] as $k => $v){
	if(is_array($_POST[$prefix.$k])) $_POST[$prefix.$k] = fn_setCheckboxData($_POST[$prefix.$k]);
	$sql_common .= $prefix.$k . " = '".$_POST[$prefix.$k]."', ";
}

if($w == "" || $w == "u"){
	foreach($sweb['column'] as $k => $v){
		if($v['required'] == "required" && !$_POST[$prefix.$k]){
			$result['status'] = false;
			$result['msg'] = _t($v['msg']);
			echo json_encode($result);
			exit;
		}
	}
}

// 로그 생성 공통 함수
function createScheduleLog($type, $data) {
	$log_data = array(
		'type' => $type,
		'timestamp' => time()
	);
	$log_data = array_merge($log_data, $data);
	return json_encode($log_data, JSON_UNESCAPED_UNICODE);
}

// DB저장
if($w == ""){
	$sql = "insert into {$table_name} set
			{$sql_common}
			mb_id = '".$member['mb_id']."',
			{$parent_key_column} = '{$parent_key}',
			insert_date = now(),
			insert_ip = '".$_SERVER['REMOTE_ADDR']."'
	";
	sql_query($sql);
	$result['sql'] = $sql;
	$result['key'] = sql_insert_id();

	if($result['key']){
		$result['status'] = true;
		$result['msg'] = _t($sweb['write_msg']);
		$result['url'] = $sweb['view_url'] . "?" . $key_column . "=" . $result['key'] . "&" . $parent_key_column . "=" . $parent_key;

		for($i = 0; $i < count($file_attach); $i++){
			$file_name = $file_attach[$i];
			file_upload($_FILES['file_'.$file_name], $_POST['file_del_'.$file_name], $file_code, $file_name, $result['key'], "", "");
		}
	}else{
		$result['status'] = false;
		$result['msg'] = _t("처리 중 오류가 발생하였습니다.") . $sql;
	}

}else if($w == "u"){
	$result['key'] = $_POST[$key_column];
	$sql = "update {$table_name} set
			{$sql_common}
			modify_date = now(),
			modify_ip = '".$_SERVER['REMOTE_ADDR']."'
			where ".$key_column." = '".$result['key']."'
	";
	$result['sql'] = $sql;

	if(sql_query($sql)){
		$result['status'] = true;
		$result['msg'] = _t($sweb['modify_msg']);

		$return_url = $_POST['return_url'];
		if($return_url && (strpos($return_url, 'view.php') !== false || strpos($return_url, 'squad.php') !== false)) {
			$result['url'] = $return_url;
		} else {
			$result['url'] = $sweb['view_url'] . "?" . $key_column . "=" . $result['key'] . "&" . $parent_key_column . "=" . $parent_key;
		}

		for($i = 0; $i < count($file_attach); $i++){
			$file_name = $file_attach[$i];
			file_upload($_FILES['file_'.$file_name], $_POST['file_del_'.$file_name], $file_code, $file_name, $result['key'], "", "");
		}
	}else{
		$result['status'] = false;
		$result['msg'] = _t("처리 중 오류가 발생하였습니다.") . $sql;
	}

}else if($w == "d"){
	$parent_key_to_use = $parent_key;
	
	if(!$parent_key_to_use) {
		$sql_get_parent = "select {$parent_key_column} from {$table_name} where {$key_column} = '{$key}'";
		$view_parent = sql_fetch($sql_get_parent);
		$parent_key_to_use = $view_parent[$parent_key_column];
	}
	
	// 관련 데이터 삭제
	$sql = "DELETE FROM sweb_team_schedule_record WHERE sm_id IN (
		SELECT sm_id FROM sweb_team_schedule_match WHERE ts_id = '{$key}'
	)";
	sql_query($sql);
	
	$sql = "DELETE FROM sweb_team_schedule_match WHERE ts_id = '{$key}'";
	sql_query($sql);
	
	$sql = "DELETE FROM sweb_team_schedule_position WHERE sq_id IN (
		SELECT sq_id FROM sweb_team_schedule_squad WHERE ts_id = '{$key}'
	)";
	sql_query($sql);
	
	$sql = "DELETE FROM sweb_team_schedule_squad WHERE ts_id = '{$key}'";
	sql_query($sql);
	
	$sql = "DELETE FROM {$table_name_log} WHERE {$key_column} = '{$key}'";
	sql_query($sql);
	
	$sql = "DELETE FROM {$table_name_join} WHERE {$key_column} = '{$key}'";
	sql_query($sql);
	
	$sql = "DELETE FROM {$table_name} WHERE {$key_column} = '{$key}'";
	$result['sql'] = $sql;
	
	if(sql_query($sql)){
		$result['status'] = true;
		$result['msg'] = $sweb['delete_msg'];
		
		if($parent_key_to_use) {
			$result['url'] = $sweb['list_url'] . "?te_id=" . $parent_key_to_use;
		} else {
			$result['url'] = $sweb['delete_after_url'];
		}
	}else{
		$result['status'] = false;
		$result['msg'] = _t("처리 중 오류가 발생하였습니다.");
	}

}else if($w == "j"){
	if(!$is_member_team){
		$result['status'] = false;
		$result['msg'] = _t("소속된 팀이 아닙니다.");
		echo json_encode($result);
		exit;
	}

	$sql = "select * from {$table_name_join} where {$key_column} = '{$key}' and mb_id = '".$member['mb_id']."' ";
	$view = sql_fetch($sql);

	if($view){
		$sql = "update {$table_name_join} set
				sj_status = '{$sj_status}',
				sj_gubun = '{$sj_gubun}',
				sj_is_guest = '0',
				modify_date = now(),
				modify_ip = '".$_SERVER['REMOTE_ADDR']."'
				where sj_id = '".$view['sj_id']."'
		";
		sql_query($sql);

		if($view['sj_status'] != $sj_status){
			// JSON 구조화된 로그 저장
			$action = $sj_status == "1" ? "attend" : "absent";
			$msg = createScheduleLog('attendance', array(
				'player_name' => $player['tj_name'],
				'action' => $action
			));
			
			$sql = "insert into {$table_name_log} set
					{$key_column} = '{$key}',
					sl_content = '{$msg}',
					sj_id = '{$view['sj_id']}',
					insert_date = now(),
					insert_ip = '".$_SERVER['REMOTE_ADDR']."'
			";
			sql_query($sql);
		}
	}else{
		$sql = "insert into {$table_name_join} set
				{$key_column} = '{$key}',
				mb_id = '".$member['mb_id']."',
				sj_status = '{$sj_status}',
				sj_gubun = '{$sj_gubun}',
				sj_is_guest = '0',
				insert_date = now(),
				insert_ip = '".$_SERVER['REMOTE_ADDR']."'
		";
		sql_query($sql);

		$result['key'] = sql_insert_id();
		
		// JSON 구조화된 로그 저장
		$action = $sj_status == "1" ? "attend" : "absent";
		$msg = createScheduleLog('attendance', array(
			'player_name' => $player['tj_name'],
			'action' => $action
		));
		
		$sql = "insert into {$table_name_log} set
				{$key_column} = '{$key}',
				sl_content = '{$msg}',
				sj_id = '{$result['key']}',
				insert_date = now(),
				insert_ip = '".$_SERVER['REMOTE_ADDR']."'
		";
		sql_query($sql);
	}

	$result['reload'] = 1;

}else if($w == "jg"){
	if(!$is_member_team){
		$result['status'] = false;
		$result['msg'] = _t("소속된 팀이 아닙니다.");
		echo json_encode($result);
		exit;
	}

	$sql = "insert into {$table_name_join} set
			{$key_column} = '{$key}',
			mb_id = '',
			sj_name = '{$sj_name}',
			sj_status = '1',
			sj_gubun = '1',
			sj_is_guest = '1',
			parent_mb_id = '".$member['mb_id']."',
			insert_date = now(),
			insert_ip = '".$_SERVER['REMOTE_ADDR']."'
	";
	sql_query($sql);

	$result['key'] = sql_insert_id();

	// JSON 구조화된 게스트 로그 저장
	$msg = createScheduleLog('guest_join', array(
		'guest_name' => $sj_name,
		'added_by' => $member['mb_id']
	));
	
	$sql = "insert into {$table_name_log} set
			{$key_column} = '{$key}',
			sl_content = '{$msg}',
			sj_id = '{$result['key']}',
			insert_date = now(),
			insert_ip = '".$_SERVER['REMOTE_ADDR']."'
	";
	sql_query($sql);

	$result['reload'] = 1;

}else if($w == "dg"){
	if(!$is_member_team){
		$result['status'] = false;
		$result['msg'] = _t("소속된 팀이 아닙니다.");
		echo json_encode($result);
		exit;
	}
	
	if(!$is_admin_team){
		$sql = "select * from {$table_name_join} where sj_id = '".$sj_id."' ";
		$view = sql_fetch($sql);
		if($view['mb_id'] != $member['mb_id'] && $view['parent_mb_id'] != $member['mb_id']){
			$result['status'] = false;
			$result['msg'] = _t("권한이 없습니다.");
			exit;
		}
	}
	
	$sql = "delete from {$table_name_join} where sj_id = '".$sj_id."'";
	sql_query($sql);

	$result['reload'] = 1;

}else if($w == "t"){
	if(!$is_member_team){
		$result['status'] = false;
		$result['msg'] = _t("소속된 팀이 아닙니다.");
		echo json_encode($result);
		exit;
	}

	foreach($_POST['team'] as $k => $v){
		$sql = "update {$table_name_join} set sj_team='{$v}' where sj_id = '".$k."'";
		sql_query($sql);	
		$result['msg'] .= $sql;
	}
}

echo json_encode($result);
?>