<?php
/**
 * 팀 삭제 전용 처리 파일
 * page/team/delete.php
 */

include_once "./_common.php";
include_once "./setting.php";

// 기본 response 데이터 세팅
$result['status'] = false;
$result['msg'] = "";
$result['backup_id'] = 0;

// POST 요청만 허용
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $result['msg'] = _t("잘못된 접근입니다.");
    echo json_encode($result);
    exit;
}

// 필수 파라미터 확인
$te_id = (int)$_POST['te_id'];
$team_name_verify = trim($_POST['team_name_verify']);
$delete_confirm = trim($_POST['delete_confirm']);
$delete_reason = trim($_POST['delete_reason']) ?: _t('사유 없음');

if(!$te_id) {
    $result['msg'] = _t("팀 정보가 없습니다.");
    echo json_encode($result);
    exit;
}

// 팀 정보 조회
$sql = "select * from {$table_name} where {$key_column} = '{$te_id}'";
$team_info = sql_fetch($sql);

if(!$team_info) {
    $result['msg'] = _t("존재하지 않는 팀입니다.");
    echo json_encode($result);
    exit;
}

// 권한 검증
if(!$is_admin && $member['mb_id'] != $team_info['mb_id']) {
    $result['msg'] = _t("팀을 삭제할 권한이 없습니다.");
    echo json_encode($result);
    exit;
}

// 폼 검증
if($team_name_verify !== $team_info['te_name']) {
    $result['msg'] = _t("팀명이 일치하지 않습니다.");
    echo json_encode($result);
    exit;
}

if($delete_confirm !== 'DELETE') {
    $result['msg'] = _t("DELETE를 정확히 입력해주세요.");
    echo json_encode($result);
    exit;
}

// 팀 삭제 처리 실행
try {
    $delete_result = executeTeamDeletion($te_id, $team_info, $delete_reason, $member);
    
    if($delete_result['status']) {
        $result['status'] = true;
        $result['msg'] = _t("팀이 성공적으로 삭제되었습니다.") . "\n" . 
                        _t("백업 ID: ") . $delete_result['backup_id'];
        $result['backup_id'] = $delete_result['backup_id'];
        $result['url'] = G5_URL;
    } else {
        $result['msg'] = _t("팀 삭제 중 오류가 발생했습니다: ") . $delete_result['msg'];
    }
    
} catch (Exception $e) {
    $result['msg'] = _t("시스템 오류: ") . $e->getMessage();
}

echo json_encode($result);

/**
 * =======================================================================
 * 팀 삭제 처리 함수들
 * =======================================================================
 */

/**
 * 팀 완전 삭제 메인 함수
 */
function executeTeamDeletion($te_id, $team_info, $delete_reason, $member) {
    $result = array('status' => false, 'msg' => '', 'backup_id' => 0);
    
    try {
        // 1단계: 백업 데이터 수집
        $backup_data = collectTeamBackupData($te_id);
        
        // 2단계: 백업 로그 저장
        $backup_id = saveTeamDeleteLog($te_id, $team_info, $delete_reason, $member, $backup_data);
        
        if(!$backup_id) {
            throw new Exception(_t("백업 저장에 실패했습니다."));
        }
        
        // 3단계: 파일 백업
        backupTeamFiles($te_id, $backup_id);
        
        // 4단계: 순차적 데이터 삭제
        $delete_summary = executeDataDeletion($te_id, $backup_id);
        
        // 5단계: 삭제 요약 업데이트
        $summary_json = json_encode($delete_summary, JSON_UNESCAPED_UNICODE);
        $sql = "update sweb_team_delete_log set 
                delete_summary = '" . addslashes($summary_json) . "' 
                where tdl_id = '{$backup_id}'";
        sql_query($sql);
        
        $result['status'] = true;
        $result['msg'] = _t("삭제 완료");
        $result['backup_id'] = $backup_id;
        
    } catch (Exception $e) {
        // 실패 시 백업 로그에 오류 기록
        if(isset($backup_id) && $backup_id > 0) {
            $error_msg = _t("삭제 실패: ") . $e->getMessage();
            $sql = "update sweb_team_delete_log set 
                    delete_summary = '" . addslashes($error_msg) . "' 
                    where tdl_id = '{$backup_id}'";
            sql_query($sql);
        }
        
        $result['msg'] = $e->getMessage();
    }
    
    return $result;
}

/**
 * 팀 관련 모든 백업 데이터 수집
 */
function collectTeamBackupData($te_id) {
    $backup_data = array();
    
    // 백업할 테이블 목록과 설명
    $backup_tables = array(
        'sweb_team' => array('key' => 'te_id', 'name' => 'team_info'),
        'sweb_team_join' => array('key' => 'te_id', 'name' => 'team_join'),
        'sweb_team_schedule' => array('key' => 'te_id', 'name' => 'team_schedule'),
        'sweb_team_stadium' => array('key' => 'te_id', 'name' => 'team_stadium'),
        'sweb_team_level' => array('key' => 'te_id', 'name' => 'team_level'),
        'sweb_team_position' => array('key' => 'te_id', 'name' => 'team_position'),
        'sweb_team_good' => array('key' => 'te_id', 'name' => 'team_good')
    );
    
    // 각 테이블별 데이터 수집
    foreach($backup_tables as $table => $config) {
        $sql = "select * from {$table} where {$config['key']} = '{$te_id}'";
        $result = sql_query($sql);
        
        $backup_data[$config['name']] = array();
        while($row = sql_fetch_array($result)) {
            $backup_data[$config['name']][] = $row;
        }
    }
    
    // 일정 관련 세부 데이터
    $schedule_ids = array();
    foreach($backup_data['team_schedule'] as $schedule) {
        $schedule_ids[] = $schedule['ts_id'];
    }
    
    if(!empty($schedule_ids)) {
        $ids_str = implode(',', $schedule_ids);
        
        // 일정 참가자
        $sql = "select * from sweb_team_schedule_join where ts_id in ({$ids_str})";
        $result = sql_query($sql);
        $backup_data['schedule_join'] = array();
        while($row = sql_fetch_array($result)) {
            $backup_data['schedule_join'][] = $row;
        }
        
        // 스쿼드 정보
        $sql = "select * from sweb_team_schedule_squad where ts_id in ({$ids_str})";
        $result = sql_query($sql);
        $backup_data['schedule_squad'] = array();
        while($row = sql_fetch_array($result)) {
            $backup_data['schedule_squad'][] = $row;
        }
        
        // 경기 기록
        $sql = "select * from sweb_team_schedule_match where ts_id in ({$ids_str})";
        $result = sql_query($sql);
        $backup_data['schedule_match'] = array();
        while($row = sql_fetch_array($result)) {
            $backup_data['schedule_match'][] = $row;
        }
    }
    
    // 팀 파일
    $sql = "select * from sweb_file where file_code = 'team' and file_table_idx = '{$te_id}'";
    $result = sql_query($sql);
    $backup_data['team_files'] = array();
    while($row = sql_fetch_array($result)) {
        $backup_data['team_files'][] = $row;
    }
    
    return $backup_data;
}

/**
 * 백업 로그 저장
 */
function saveTeamDeleteLog($te_id, $team_info, $delete_reason, $member, $backup_data) {
    $backup_json = json_encode($backup_data, JSON_UNESCAPED_UNICODE);
    
    $sql = "insert into sweb_team_delete_log set
            te_id = '{$te_id}',
            te_name = '" . addslashes($team_info['te_name']) . "',
            delete_mb_id = '" . $member['mb_id'] . "',
            delete_reason = '" . addslashes($delete_reason) . "',
            backup_data = '" . addslashes($backup_json) . "',
            delete_date = now(),
            delete_ip = '" . $_SERVER['REMOTE_ADDR'] . "'";
    
    sql_query($sql);
    return sql_insert_id();
}

/**
 * 팀 파일 백업
 */
function backupTeamFiles($te_id, $backup_id) {
    $backup_dir = G5_DATA_PATH . "/backup/team_delete/" . $backup_id;
    
    // 백업 디렉토리 생성
    if(!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    // 팀 파일 백업
    $team_file_dir = G5_DATA_PATH . "/file/team";
    if(is_dir($team_file_dir)) {
        $files = glob($team_file_dir . "/*");
        foreach($files as $file) {
            if(strpos(basename($file), $te_id . "_") === 0) {
                copy($file, $backup_dir . "/" . basename($file));
            }
        }
    }
    
    // 게시판 파일 백업
    $board_file_dir = G5_DATA_PATH . "/file/team_" . $te_id;
    if(is_dir($board_file_dir)) {
        copyDirectory($board_file_dir, $backup_dir . "/board_files");
    }
    
    // 백업 경로 업데이트
    $sql = "update sweb_team_delete_log set 
            file_backup_path = '" . addslashes($backup_dir) . "' 
            where tdl_id = '{$backup_id}'";
    sql_query($sql);
}

/**
 * 실제 데이터 삭제 실행
 */
function executeDataDeletion($te_id, $backup_id) {
    global $g5;
    $delete_summary = array();
    $delete_order = 1;
    
    // 삭제 순서 (FK 관계 고려한 역순)
    $delete_tables = array(
        'sweb_team_schedule_record' => _t('경기 개별 기록'),
        'sweb_team_schedule_match' => _t('경기 기록'),  
        'sweb_team_schedule_position' => _t('스쿼드 포지션'),
        'sweb_team_schedule_squad' => _t('스쿼드'),
        'sweb_team_schedule_log' => _t('일정 로그'),
        'sweb_team_schedule_join' => _t('일정 참가자'),
        'sweb_team_schedule' => _t('팀 일정'),
        'sweb_team_join_position' => _t('가입자 포지션'), 
        'sweb_team_join' => _t('팀 가입 정보'),
        'sweb_team_position' => _t('포지션 정보'),
        'sweb_team_level' => _t('팀 레벨'),
        'sweb_team_stadium' => _t('팀 구장'),
        'sweb_team_good' => _t('팀 좋아요')
    );
    
    // 각 테이블별 삭제
    foreach($delete_tables as $table => $description) {
        $count = deleteTableData($table, $te_id, $backup_id, $delete_order, $description);
        $delete_summary[$table] = $count;
        $delete_order++;
    }
    
    // 파일 삭제
    deleteTeamFiles($te_id, $backup_id, $delete_order);
    $delete_order++;
    
    // 게시판 삭제  
    deleteTeamBoard($te_id, $backup_id, $delete_order);
    $delete_order++;
    
    // 메인 팀 테이블 삭제 (최종)
    $count = deleteTableData('sweb_team', $te_id, $backup_id, $delete_order, _t('팀 정보'));
    $delete_summary['sweb_team'] = $count;
    
    return $delete_summary;
}

/**
 * 특정 테이블 데이터 삭제 및 로그
 */
function deleteTableData($table, $te_id, $backup_id, $delete_order, $description) {
    // 삭제할 레코드 수 확인
    $sql = "select count(*) as cnt from {$table} where te_id = '{$te_id}'";
    $row = sql_fetch($sql);
    $count = $row['cnt'];
    
    if($count > 0) {
        // 데이터 삭제
        $sql = "delete from {$table} where te_id = '{$te_id}'";
        sql_query($sql);
        
        // 상세 로그 기록
        $sql = "insert into sweb_team_delete_detail set
                tdl_id = '{$backup_id}',
                table_name = '{$table}',
                delete_count = '{$count}',
                table_comment = '" . addslashes($description) . "',
                delete_order = '{$delete_order}'";
        sql_query($sql);
    }
    
    return $count;
}

/**
 * 팀 관련 파일 삭제
 */
function deleteTeamFiles($te_id, $backup_id, $delete_order) {
    // sweb_file 테이블에서 팀 파일 삭제
    $sql = "select count(*) as cnt from sweb_file where file_code = 'team' and file_table_idx = '{$te_id}'";
    $row = sql_fetch($sql);
    $file_count = $row['cnt'];
    
    if($file_count > 0) {
        $sql = "delete from sweb_file where file_code = 'team' and file_table_idx = '{$te_id}'";
        sql_query($sql);
        
        // 로그 기록
        $sql = "insert into sweb_team_delete_detail set
                tdl_id = '{$backup_id}',
                table_name = 'sweb_file',
                delete_count = '{$file_count}',
                table_comment = '" . _t('팀 파일') . "',
                delete_order = '{$delete_order}'";
        sql_query($sql);
    }
    
    // 실제 파일 삭제
    $team_file_dir = G5_DATA_PATH . "/file/team";
    if(is_dir($team_file_dir)) {
        $files = glob($team_file_dir . "/*");
        foreach($files as $file) {
            if(strpos(basename($file), $te_id . "_") === 0) {
                unlink($file);
            }
        }
    }
}

/**
 * 팀 게시판 삭제
 */
function deleteTeamBoard($te_id, $backup_id, $delete_order) {
    global $g5;
    $bo_table = 'team_' . $te_id;
    
    // 게시판 존재 여부 확인
    $sql = "select count(*) as cnt from {$g5['board_table']} where bo_table = '{$bo_table}'";
    $row = sql_fetch($sql);
    
    if($row['cnt'] > 0) {
        // 게시글 수 확인
        $write_table = $g5['write_prefix'] . $bo_table;
        $sql = "select count(*) as cnt from {$write_table}";
        $write_row = sql_fetch($sql);
        $write_count = $write_row['cnt'];
        
        // 게시판 설정 삭제
        $sql = "delete from {$g5['board_table']} where bo_table = '{$bo_table}'";
        sql_query($sql);
        
        // 게시글 테이블 삭제
        $sql = "drop table if exists {$write_table}";
        sql_query($sql, false);
        
        // 게시판 디렉토리 삭제
        $board_dir = G5_DATA_PATH . "/file/" . $bo_table;
        if(is_dir($board_dir)) {
            removeDirectory($board_dir);
        }
        
        // 로그 기록
        $sql = "insert into sweb_team_delete_detail set
                tdl_id = '{$backup_id}',
                table_name = '{$bo_table}',
                delete_count = '{$write_count}',
                table_comment = '" . _t('팀 게시판') . "',
                delete_order = '{$delete_order}'";
        sql_query($sql);
    }
}

/**
 * 디렉토리 복사
 */
function copyDirectory($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst, 0755, true);
    while(($file = readdir($dir)) !== false) {
        if($file != '.' && $file != '..') {
            if(is_dir($src . '/' . $file)) {
                copyDirectory($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

/**
 * 디렉토리 삭제
 */
function removeDirectory($dir) {
    if(is_dir($dir)) {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
?>