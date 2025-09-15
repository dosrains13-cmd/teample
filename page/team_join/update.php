<?php
include_once "./_common.php";
include_once "./setting.php";
include_once "../team/team.common.php";




$result['status'] = true;
$result['msg'] = "";
$result['key'] = "";
$result['html'] = "";
$result['reload'] = false;

if($key){
	$sql = "select * from {$table_name} where {$key_column}='{$key}'";
	$view = sql_fetch($sql);
	$auth_mb_id = $view['mb_id'];
}else{
	$auth_mb_id = 'admin';
}

if($w == ""){
	
	$msg = fn_authCheckAjax($sweb['write_level'], "");
	if(!$parent['te_is_join'] && !$is_admin_team && !$is_member_team){
		$msg = "팀가입 신청이 불가능한 팀입니다.";
	}
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


//공통 column 세팅
$sql_common = "";
foreach($sweb['column'] as $k => $v){
	//체크박스 데이터 합치기.
	if(is_array($_POST[$prefix.$k])) $_POST[$prefix.$k] = fn_setCheckboxData($_POST[$prefix.$k]);

	$sql_common .= $prefix.$k . " = '".$_POST[$prefix.$k]."', ";
}

if($w == "" || $w == "u"){
	//폼 체크(setting.php에 required가 required 로 설정된 컬럼 조회하여 체크)
	foreach($sweb['column'] as $k => $v){
		if($v['required'] == "required" && !$_POST[$prefix.$k]){
			$result['status'] = false;
			$result['msg'] = $v['msg'];
			echo json_encode($result);
			exit;
		}
	}


	if(count($_POST[$prefix.'position']) < 1){
		$result['status'] = false;
		$result['msg'] = "포지션을 선택하세요.";
		echo json_encode($result);
		exit;
	}

	if(count($_POST[$prefix.'position']) > 2){
		$result['status'] = false;
		$result['msg'] = "포지션은 2개까지 선택 가능합니다.";
		echo json_encode($result);
		exit;
	}
}

if($w == ""){
	$sql = "select * from {$table_name} where {$parent_key_column}='{$parent_key}' and mb_id='".$member['mb_id']."' ";
	$view = sql_fetch($sql);
	if($view[$parent_key_column]){
		$result['status'] = false;
		$result['msg'] = $sweb['write_overlap_msg'];
		echo json_encode($result);
		exit;
	}

}




//DB저장
if($w == ""){

	$status = 0;
	if($parent['te_is_autojoin']) $status = 1;
	
	$sql = "insert into {$table_name} set
			{$sql_common}
			mb_id = '".$member['mb_id']."',
			{$prefix}status = ".$status.",
			{$parent_key_column} = '{$parent_key}',
			{$prefix}position = '".fn_setCheckboxData($_POST[$prefix.'position'])."',
			insert_date = now(),
			insert_ip = '".$_SERVER['REMOTE_ADDR']."'
	";
	sql_query($sql);
	$result['sql'] = $sql;
	$result['key'] = sql_insert_id();

	if($result['key']){
		$result['status'] = true;
		$result['msg'] = $sweb['write_msg'];
		$result['url'] = $sweb['write_after_url'];

		//파일저장
		for($i = 0; $i < count($file_attach); $i++){
			$file_name = $file_attach[$i];
			file_upload($_FILES['file_'.$file_name], $_POST['file_del_'.$file_name], $file_code, $file_name, $result['key'], "", "");
		}

		
	}else{
		$result['status'] = false;
		$result['msg'] = "처리 중 오류가 발생하였습니다." . $sql;
		//$result['error'] = mysql_error();
	}
}else if($w == "u"){
	$result['key'] = $_POST[$key_column];
	$sql = "update {$table_name} set
			{$sql_common}
			{$prefix}position = '".fn_setCheckboxData($_POST[$prefix.'position'])."',
			modify_date = now(),
			modify_ip = '".$_SERVER['REMOTE_ADDR']."'
			where ".$key_column." = '".$result['key']."'
	";
	$result['sql'] = $sql;
	if(sql_query($sql)){
		$result['status'] = true;
		$result['msg'] = $sweb['modify_msg'];
		$result['url'] = $sweb['modify_after_url'];

		//파일저장
		for($i = 0; $i < count($file_attach); $i++){
			$file_name = $file_attach[$i];
			file_upload($_FILES['file_'.$file_name], $_POST['file_del_'.$file_name], $file_code, $file_name, $result['key'], "", "");
		}


	}else{
		$result['status'] = false;
		$result['msg'] = "처리 중 오류가 발생하였습니다.".$sql;
		//$result['error'] = mysql_error();
	}


}else if($w == "d"){

    // 팀 관리자(소유자) 체크
    $team_info = sql_fetch("SELECT mb_id FROM {$parent_table_name} WHERE {$parent_key_column} = '{$parent_key}'");
    
    // 팀의 관리자(소유자)인 경우 탈퇴 불가 메시지 반환
    if($team_info['mb_id'] == $member['mb_id']){
        $result['status'] = false;
        $result['msg'] = "팀의 관리자는 탈퇴할 수 없습니다. 먼저 다른 회원에게 관리자 권한을 위임해주세요.";
        echo json_encode($result);
        exit;
    }
    
    $sql = "delete from {$table_name} where {$key_column} = '".$key."'";
    $result['sql'] = $sql;
    if(sql_query($sql)){
        $result['status'] = true;
        $result['msg'] = $sweb['delete_msg'];
        $result['url'] = $sweb['delete_after_url'];
    }else{
        $result['status'] = false;
        $result['msg'] = "처리 중 오류가 발생하였습니다.";
        //$result['error'] = mysql_error();
    }

}else if($w == "ds"){
	for($i = 0; $i < count($chk); $i++){
		$sql = "delete from {$table_name} where {$key_column} = '".$chk[$i]."'";
		sql_query($sql);
	}
	
	$result['status'] = true;
	$result['msg'] = $sweb['delete_msg'];
	$result['url'] = $sweb['delete_after_url'];
	
}else if($w == "a"){
	$sql = "select * from {$table_name} where {$key_column} = '{$key}'";
	$view = sql_fetch($sql);
	
	$status = 1;
	if($view[$prefix.'status'] == "1") $status = 0;

	$sql = "update {$table_name} set {$prefix}status={$status} where {$key_column} = '{$key}'";
	sql_query($sql);
}


echo json_encode($result);


?>