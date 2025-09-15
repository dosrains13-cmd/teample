<?php
include_once "./_common.php";
include_once "./setting.php";

//기본 response 데이터 세팅
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

//권한 체크
if($w == ""){
	$msg = fn_authCheckAjax($sweb['write_level'], "");
}else if($w == "u"){
	$msg = fn_authCheckAjax($sweb['write_level'], $auth_mb_id);
}else if($w == "d"){
	$msg = fn_authCheckAjax($sweb['delete_level'], $auth_mb_id);
}
//권한 없을시 에러 메시지 발생
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
}



//DB저장
if($w == ""){
	if($agree1 != '1'){
		$result['status'] = false;
		$result['msg'] = "please check agreement!";
		echo json_encode($result);
		exit;	
	}

	$sql = "insert into {$table_name} set
			{$sql_common}
			mb_id = '".$member['mb_id']."',
			insert_date = now(),
			insert_ip = '".$_SERVER['REMOTE_ADDR']."'
	";
	sql_query($sql);
	$result['sql'] = $sql;
	$result['key'] = sql_insert_id();

	if($result['key']){
		$result['status'] = true;
		$result['msg'] = $sweb['write_msg'];
		$result['url'] = $sweb['write_after_url']."?".$key_column."=".$result['key'];
		

// 팀 게시판 자동 생성 코드
$team_id = $result['key'];
$team_name = $_POST[$prefix.'name']; // 팀 이름 가져오기
$bo_table = 'team_' . $team_id; // 게시판 테이블명 생성

// 이미 해당 테이블이 있는지 확인
$row = sql_fetch(" select count(*) as cnt from {$g5['board_table']} where bo_table = '{$bo_table}' ");
if ($row['cnt'] == 0) { // 없으면 생성
    // 게시판 기본 설정
    $gr_id = 'team'; // 팀 게시판 그룹 ID (그누보드에 미리 생성해두어야 함)
    $bo_subject = $team_name . ' 공지사항'; // 게시판 제목
    
    // 게시판 테이블 등록
    $sql = " insert into {$g5['board_table']}
        set bo_table = '{$bo_table}',
            gr_id = '{$gr_id}',
            bo_subject = '{$bo_subject}',
            bo_device = 'both',
            bo_admin = '{$member['mb_id']}',
            bo_list_level = '1',
            bo_read_level = '1',
            bo_write_level = '2',
            bo_comment_level = '2',
            bo_upload_level = '2',
            bo_download_level = '1',
            bo_html_level = '2',
            bo_count_modify = '1',
            bo_count_delete = '1',
            bo_use_category = '0',
            bo_use_sideview = '1',
            bo_use_file_content = '1',
            bo_use_secret = '0',
            bo_skin = 'basic',
            bo_mobile_skin = 'basic',
            bo_count_write = '0',
            bo_count_comment = '0',
			bo_mobile_page_rows ='15',
            bo_1 = '{$team_id}'

    ";
    sql_query($sql);
    
    // 게시판 테이블 생성 (board_form_update.php 참고)
    $file = file(G5_ADMIN_PATH . '/sql_write.sql');
    $file = get_db_create_replace($file);
    $sql = implode("\n", $file);
    
    $create_table = $g5['write_prefix'] . $bo_table;
    
    // sql_board.sql 파일의 테이블명을 변환
    $source = array('/__TABLE_NAME__/', '/;/');
    $target = array($create_table, '');
    $sql = preg_replace($source, $target, $sql);
    sql_query($sql, FALSE);
    
    // 게시판 디렉토리 생성
    $board_path = G5_DATA_PATH.'/file/'.$bo_table;
    @mkdir($board_path, G5_DIR_PERMISSION);
    @chmod($board_path, G5_DIR_PERMISSION);
    
    // 디렉토리에 있는 파일의 목록을 보이지 않게 한다.
    $file = $board_path . '/index.php';
    $f = @fopen($file, 'w');
    @fwrite($f, '');
    @fclose($f);
    @chmod($file, G5_FILE_PERMISSION);
    
    // 팀 정보에 게시판 정보 저장 (옵션)
    // 이를 위해 팀 테이블에 te_board 필드가 필요함
    $sql = " update {$table_name} set te_board = '{$bo_table}' where {$key_column} = '{$team_id}' ";
    sql_query($sql);
    
    // 성공 메시지에 게시판 생성 정보 추가
    $result['msg'] .= " 팀 게시판이 자동으로 생성되었습니다.";
}

		//파일저장
		for($i = 0; $i < count($file_attach); $i++){
			$file_name = $file_attach[$i];
			file_upload($_FILES['file_'.$file_name], $_POST['file_del_'.$file_name], $file_code, $file_name, $result['key'], "", "");
		}

		//추가데이터 저장
		if(count($sweb['add_data']) > 0){
			foreach($sweb['add_data'] as $k => $v){
				for($i = 0; $i < count($add_data[$k]); $i++){
					$row = $add_data[$k][$i];
					
					$sql_common = "";
					foreach($v['column'] as $k2 => $v2){
						$sql_common .= $v['prefix'].$v2['name'] . " = '".$row[$v['prefix'].$v2['name']]."' , ";
					}
					
					$sql = "insert into {$v['table_name']} set	
							{$sql_common}
							{$key_column} = '{$key}',
							insert_date = now(),
							insert_ip = '".$_SERVER['REMOTE_ADDR']."'
					";
					sql_query($sql);
					
				}
			}
		}

		$sql = "insert into {$table_name_join} set
				te_id = '{$result['key']}',
				mb_id = '".$member['mb_id']."',
				tj_name = '".$member['mb_name']."',
				tj_phone = '".$_POST['te_tel']."',
				tj_status='1',
				insert_date = now(),
				insert_ip = '".$_SERVER['REMOTE_ADDR']."'
		";
		sql_query($sql);
		
	}else{
		$result['status'] = false;
		$result['msg'] = "처리 중 오류가 발생하였습니다." . $sql;
		//$result['error'] = mysql_error();
	}
}else if($w == "u"){
	$result['key'] = $key;
	$sql = "update {$table_name} set
			{$sql_common}
			modify_date = now(),
			modify_ip = '".$_SERVER['REMOTE_ADDR']."'
			where ".$key_column." = '".$result['key']."'
	";
	$result['sql'] = $sql;
	if(sql_query($sql)){
		$result['status'] = true;
		$result['msg'] = $sweb['modify_msg'];
		$result['url'] = $sweb['modify_after_url']."?".$key_column."=".$key;

		//파일저장
		for($i = 0; $i < count($file_attach); $i++){
			$file_name = $file_attach[$i];
			file_upload($_FILES['file_'.$file_name], $_POST['file_del_'.$file_name], $file_code, $file_name, $result['key'], "", "");
		}

		//추가데이터 저장
		if(count($sweb['add_data']) > 0){
			foreach($sweb['add_data'] as $k => $v){
				$sql = "delete from {$v['table_name']} where {$key_column} = '{$key}' ";
				sql_query($sql);
				for($i = 0; $i < count($add_data[$k]); $i++){
					$row = $add_data[$k][$i];
					
					$sql_common = "";
					foreach($v['column'] as $k2 => $v2){
						$sql_common .= $v['prefix'].$v2['name'] . " = '".$row[$v['prefix'].$v2['name']]."' , ";
					}
					
					$sql = "insert into {$v['table_name']} set	
							{$sql_common}
							{$key_column} = '{$key}',
							insert_date = now(),
							insert_ip = '".$_SERVER['REMOTE_ADDR']."'
					";
					sql_query($sql);
					
				}
			}
		}

	}else{
		$result['status'] = false;
		$result['msg'] = "처리 중 오류가 발생하였습니다.".$sql;
		//$result['error'] = mysql_error();
	}
}else if($w == "d"){
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
	$result['reload'] = 1;
}else if($w == "g"){
	$sql = "select * from {$table_name_good} where {$key_column} = '{$key}' and mb_id = '".$member['mb_id']."' ";
	$view = sql_fetch($sql);
	
	if($view['tg_id']){
		$sql = "delete from {$table_name_good} where {$key_column} = '{$key}' and mb_id = '".$member['mb_id']."' ";
		sql_query($sql);

		$result['flag'] = "NOGOOD";
	}else{
		$sql = "insert {$table_name_good} set 
				{$key_column} = '{$key}',
				mb_id = '".$member['mb_id']."',
				insert_date = now(),
				insert_ip = '".$_SERVER['REMOTE_ADDR']."'
		";
		sql_query($sql);

		$result['flag'] = "GOOD";
	}
	
	$sql = "select count(*) as cnt from {$table_name_good} where {$key_column} = '{$key}' ";
	$view = sql_fetch($sql);

	$result['status'] = true;
	$result['cnt'] = $view['cnt'];
	
}else if($w == "c"){
	
	if(!$admin_id){
		$result['status'] = false;
		$result['msg'] = "관리자 ID를 입력해주세요! ";
		echo json_encode($result);
		exit;
	}else{
		$sql = "select * from {$table_name} where {$key_column} = '{$key}'";
		$view = sql_fetch($sql);
		if(!$is_admin && $member['mb_id'] != $view['mb_id']){
			$result['status'] = false;
			$result['msg'] = "권한이 없습니다! ";
			echo json_encode($result);
			exit;
		}
		
		$mb = get_member($admin_id);
		if(!$mb){
			$result['status'] = false;
			$result['msg'] = "관리자 ID를 확인 후 다시 입력해주세요! ";
			echo json_encode($result);
			exit;
		}
		
		$sql = "select count(*) as cnt from {$table_name} where {$key_column} = '{$key}' and mb_id = '{$admin_id}' ";
		$view = sql_fetch($sql);
		if($view['cnt'] > 0){
			$result['status'] = false;
			$result['msg'] = "이미 관리자인 ID입니다! ";
			echo json_encode($result);
			exit;
		}
		
		// 팀 관리자 변경
		$sql = "update {$table_name} set mb_id = '".$admin_id."' where {$key_column} = '{$key}' ";
		sql_query($sql);
		
		// 🔥 새 관리자 자동 팀 가입 처리
		$auto_join_msg = ""; // 변수 초기화
		
		// 기존 가입 여부 확인 (te_id로 직접 확인)
		$sql = "select * from {$table_name_join} where te_id = '{$key}' and mb_id = '{$admin_id}'";
		$join_check = sql_fetch($sql);
		
		if(!$join_check) {
			// 팀에 가입되어 있지 않으면 자동 가입 처리
			$safe_name = addslashes($mb['mb_name']); // SQL 인젝션 방지
			$safe_phone = str_replace('-', '', $mb['mb_hp']);
			
			$sql = "insert into {$table_name_join} set
					te_id = '{$key}',
					mb_id = '{$admin_id}',
					tj_name = '{$safe_name}',
					tj_number = '0',
					tj_phone = '{$safe_phone}',
					tj_gender = 250,
					tj_content = '관리자로 임명되어 자동 가입',
					tj_status = 1,
					tj_level = 257,
					tj_position = '',
					insert_date = now(),
					insert_ip = '".$_SERVER['REMOTE_ADDR']."'
			";
			sql_query($sql);
			$auto_join_msg = " (자동으로 팀에 가입되었습니다)";
			
		} else if($join_check['tj_status'] == 0) {
			// 가입 신청은 했지만 대기중인 경우 승인 처리
			$sql = "update {$table_name_join} set 
					tj_status = 1,
					modify_date = now(),
					modify_ip = '".$_SERVER['REMOTE_ADDR']."'
					where tj_id = '{$join_check['tj_id']}'";
			sql_query($sql);
			$auto_join_msg = " (가입이 자동으로 승인되었습니다)";
		} else {
			// 이미 정상 가입된 상태
			$auto_join_msg = " (이미 팀에 가입된 상태입니다)";
		}
		
		// 🔥 팀 게시판 관리자도 함께 변경
		$team_board_table = 'team_' . $key;
		$sql_board = "update g5_board set bo_admin = '".$admin_id."' where bo_table = '{$team_board_table}'";
		sql_query($sql_board);
		
		$result['status'] = true;
		$result['msg'] = "관리자가 변경되었습니다." . $auto_join_msg . " (게시판 관리자도 함께 변경됨)";
		$result['reload'] = true;
	}
}

echo json_encode($result);


?>