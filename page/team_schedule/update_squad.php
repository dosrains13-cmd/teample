<?php
include_once "./_common.php";
include_once "./setting.php";
include_once "../team/team.common.php";

// 공통 응답 초기화
$result = array('status' => false, 'msg' => '', 'squad_id' => '', 'data' => array());

// 권한 체크
if(!checkSquadPermission()) {
    sendErrorResponse(_t("접근 권한이 없습니다."));
}

// 처리 타입별 분기
$w = sanitizeInput($_POST['w']);

try {
    switch($w) {
        case 'create_squad': createSquad(); break;
        case 'save_position_move': savePositionWithMove(); break;
        case 'clear_position': clearPosition(); break;
        case 'reset_quarter': resetQuarter(); break;
        case 'reset_all_squads': resetAllSquads(); break;
        case 'update_formation_with_reset': updateFormationWithReset(); break;
        case 'squad':
        default: saveSquadData(); break;
    }
} catch (Exception $e) {
    sendErrorResponse(_t("처리 중 오류가 발생했습니다."), $e->getMessage());
}

/**
 * 권한 체크
 */
function checkSquadPermission() {
    global $parent, $is_admin_team, $is_member_team, $member;
    
    $ts_id = $_POST['ts_id'] ?? null;
    $is_schedule_creator = false;
    
    if($ts_id && isset($member['mb_id'])) {
        $sql = "SELECT mb_id FROM sweb_team_schedule WHERE ts_id = '{$ts_id}'";
        $schedule = sql_fetch($sql);
        $is_schedule_creator = ($schedule && $schedule['mb_id'] == $member['mb_id']);
    }
    
    return ($parent['te_is_schedule'] || $is_admin_team || $is_member_team || $is_schedule_creator);
}

/**
 * 공통 유틸리티 함수들
 */
function sanitizeInput($input) {
    return trim(strip_tags($input));
}

function validateRequiredParams($params) {
    foreach($params as $key => $value) {
        if(empty($value)) {
            sendErrorResponse(_t("필수 정보가 부족합니다.") . " ({$key})");
        }
    }
}

function executeSQL($sql, $error_msg = '', $context = array()) {
    if(sql_query($sql)) return true;
    
    sendErrorResponse($error_msg ?: _t("데이터베이스 오류가 발생했습니다."));
    return false;
}

function getSquadInfo($sq_id) {
    validateRequiredParams(array('sq_id' => $sq_id));
    
    $sql = "SELECT * FROM sweb_team_schedule_squad WHERE sq_id = '{$sq_id}'";
    $squad = sql_fetch($sql);
    
    if(!$squad) sendErrorResponse(_t("스쿼드 정보를 찾을 수 없습니다."));
    return $squad;
}

function getPlayerInfo($sj_id) {
    validateRequiredParams(array('sj_id' => $sj_id));
    
    $sql = "SELECT j.*, tj.tj_name, tj.tj_number 
            FROM sweb_team_schedule_join j
            LEFT JOIN sweb_team_join tj ON j.mb_id = tj.mb_id
            WHERE j.sj_id = '{$sj_id}'";
    $player = sql_fetch($sql);
    
    if(!$player) sendErrorResponse(_t("선수 정보를 찾을 수 없습니다."));
    return $player;
}

function deletePositionData($sq_id, $sp_position = null, $sj_id = null) {
    $conditions = array("sq_id = '{$sq_id}'");
    if($sp_position) $conditions[] = "sp_position = '{$sp_position}'";
    if($sj_id) $conditions[] = "sj_id = '{$sj_id}'";
    
    $sql = "DELETE FROM sweb_team_schedule_position WHERE " . implode(' AND ', $conditions);
    return executeSQL($sql, _t("포지션 데이터 삭제 실패"));
}

function addSquadLog($ts_id, $content) {
    $sql = "INSERT INTO sweb_team_schedule_log SET
            ts_id = '{$ts_id}',
            sl_content = '" . addslashes($content) . "',
            sj_id = 0,
            insert_date = NOW(),
            insert_ip = '{$_SERVER['REMOTE_ADDR']}'";
    sql_query($sql);
}

/**
 * 메인 처리 함수들
 */
function createSquad() {
    $ts_id = sanitizeInput($_POST['ts_id']);
    $sq_type = sanitizeInput($_POST['sq_type']) ?: 'our';
    $sq_quarter = (int)$_POST['sq_quarter'] ?: 1;
    $sq_formation = sanitizeInput($_POST['sq_formation']) ?: '4-3-3';
    
    validateRequiredParams(array('ts_id' => $ts_id));
    
    // 중복 체크
    $sql = "SELECT sq_id FROM sweb_team_schedule_squad 
            WHERE ts_id = '{$ts_id}' AND sq_type = '{$sq_type}' AND sq_quarter = '{$sq_quarter}'";
    $existing = sql_fetch($sql);
    
    if($existing) {
        sendSuccessResponse(_t("기존 스쿼드를 사용합니다."), array('squad_id' => $existing['sq_id']));
    }
    
    // 새 스쿼드 생성
    $sql = "INSERT INTO sweb_team_schedule_squad SET
            ts_id = '{$ts_id}', sq_type = '{$sq_type}', sq_quarter = '{$sq_quarter}',
            sq_formation = '{$sq_formation}', insert_date = NOW(), insert_ip = '{$_SERVER['REMOTE_ADDR']}'";
    
    if(executeSQL($sql)) {
        $squad_id = sql_insert_id();
        addSquadLog($ts_id, _t("새 스쿼드가 생성되었습니다.") . " (ID: {$squad_id})");
        sendSuccessResponse(_t("스쿼드가 생성되었습니다."), array('squad_id' => $squad_id));
    }
}

function savePositionWithMove() {
    $sq_id = sanitizeInput($_POST['sq_id']);
    $sp_position = sanitizeInput($_POST['sp_position']);
    $sj_id = sanitizeInput($_POST['sj_id']);
    
    validateRequiredParams(array('sq_id' => $sq_id, 'sp_position' => $sp_position, 'sj_id' => $sj_id));
    
    $squad = getSquadInfo($sq_id);
    $player = getPlayerInfo($sj_id);
    
    // 동일 스쿼드 내 중복 배치 체크
    $sql = "SELECT sp_position FROM sweb_team_schedule_position 
            WHERE sq_id = '{$sq_id}' AND sj_id = '{$sj_id}' AND sp_position != '{$sp_position}'";
    $duplicate = sql_fetch($sql);
    
    if($duplicate) {
        sendErrorResponse(_t("해당 선수는 이미 다른 포지션에 배치되어 있습니다.") . " ({$duplicate['sp_position']})");
    }
    
    // 기존 위치 확인
    $sql = "SELECT sp_position FROM sweb_team_schedule_position WHERE sq_id = '{$sq_id}' AND sj_id = '{$sj_id}'";
    $current = sql_fetch($sql);
    $moved_from = ($current && $current['sp_position'] != $sp_position) ? $current['sp_position'] : null;
    
    // 기존 데이터 삭제 후 새 위치에 저장
    $sql = "DELETE FROM sweb_team_schedule_position WHERE sq_id = '{$sq_id}' AND (sp_position = '{$sp_position}' OR sj_id = '{$sj_id}')";
    executeSQL($sql);
    
    $sql = "INSERT INTO sweb_team_schedule_position SET
            sq_id = '{$sq_id}', sp_position = '{$sp_position}', sj_id = '{$sj_id}',
            insert_date = NOW(), insert_ip = '{$_SERVER['REMOTE_ADDR']}'";
    
    if(executeSQL($sql)) {
        $player_name = $player['tj_name'] ?: $player['sj_name'];
        $log_msg = $moved_from 
            ? "{$player_name} " . _t("선수가") . " {$moved_from}" . _t("에서") . " {$sp_position} " . _t("포지션으로 이동했습니다.")
            : "{$player_name} " . _t("선수가") . " {$sp_position} " . _t("포지션에 배치되었습니다.");
        
        addSquadLog($squad['ts_id'], $log_msg);
        sendSuccessResponse(_t("선수가 포지션에 배치되었습니다."), $moved_from ? array('moved_from' => $moved_from) : array());
    }
}

function clearPosition() {
    $sq_id = sanitizeInput($_POST['sq_id']);
    $sp_position = sanitizeInput($_POST['sp_position']);
    
    validateRequiredParams(array('sq_id' => $sq_id, 'sp_position' => $sp_position));
    
    $squad = getSquadInfo($sq_id);
    
    // 현재 배치된 선수 정보 가져오기 (로그용)
    $sql = "SELECT p.*, j.sj_name, tj.tj_name 
            FROM sweb_team_schedule_position p
            LEFT JOIN sweb_team_schedule_join j ON p.sj_id = j.sj_id
            LEFT JOIN sweb_team_join tj ON j.mb_id = tj.mb_id
            WHERE p.sq_id = '{$sq_id}' AND p.sp_position = '{$sp_position}'";
    $position_info = sql_fetch($sql);
    
    if(deletePositionData($sq_id, $sp_position)) {
        if($position_info) {
            $player_name = $position_info['tj_name'] ?: $position_info['sj_name'];
            addSquadLog($squad['ts_id'], "{$player_name} " . _t("선수가") . " {$sp_position} " . _t("포지션에서 제외되었습니다."));
        } else {
            addSquadLog($squad['ts_id'], _t("포지션이 비워졌습니다.") . " ({$sp_position})");
        }
        sendSuccessResponse(_t("포지션이 비워졌습니다."));
    }
}

function resetQuarter() {
    $sq_id = sanitizeInput($_POST['sq_id']);
    validateRequiredParams(array('sq_id' => $sq_id));
    
    $squad = getSquadInfo($sq_id);
    
    if(deletePositionData($sq_id)) {
        addSquadLog($squad['ts_id'], _t("쿼터가 초기화되었습니다.") . " (ID: {$sq_id})");
        sendSuccessResponse(_t("쿼터가 초기화되었습니다."));
    }
}

function resetAllSquads() {
    $ts_id = sanitizeInput($_POST['ts_id']);
    $sq_type = sanitizeInput($_POST['sq_type']) ?: 'our';
    
    validateRequiredParams(array('ts_id' => $ts_id));
    
    $sql = "SELECT sq_id FROM sweb_team_schedule_squad WHERE ts_id = '{$ts_id}' AND sq_type = '{$sq_type}'";
    $result_query = sql_query($sql);
    
    $squad_ids = array();
    while($row = sql_fetch_array($result_query)) {
        $squad_ids[] = $row['sq_id'];
    }
    
    if(empty($squad_ids)) {
        sendSuccessResponse(_t("초기화할 스쿼드가 없습니다."));
    }
    
    // 포지션 데이터 삭제
    $sql = "DELETE FROM sweb_team_schedule_position WHERE sq_id IN (" . implode(',', $squad_ids) . ")";
    executeSQL($sql);
    
    // 스쿼드 삭제
    $sql = "DELETE FROM sweb_team_schedule_squad WHERE ts_id = '{$ts_id}' AND sq_type = '{$sq_type}'";
    executeSQL($sql);
    
    addSquadLog($ts_id, _t("모든 스쿼드가 초기화되었습니다.") . " (타입: {$sq_type})");
    sendSuccessResponse(_t("모든 스쿼드가 초기화되었습니다."));
}

function updateFormationWithReset() {
    $sq_id = sanitizeInput($_POST['sq_id']);
    $formation = sanitizeInput($_POST['formation']);
    
    validateRequiredParams(array('sq_id' => $sq_id, 'formation' => $formation));
    
    $squad = getSquadInfo($sq_id);
    
    // 포지션 데이터 삭제 후 포메이션 업데이트
    deletePositionData($sq_id);
    
    $sql = "UPDATE sweb_team_schedule_squad SET
            sq_formation = '{$formation}', modify_date = NOW(), modify_ip = '{$_SERVER['REMOTE_ADDR']}'
            WHERE sq_id = '{$sq_id}'";
    
    if(executeSQL($sql)) {
        addSquadLog($squad['ts_id'], _t("포메이션이 변경되었습니다.") . " ({$formation})");
        sendSuccessResponse(_t("포메이션이 변경되고 스쿼드가 초기화되었습니다."));
    }
}

function saveSquadData() {
    $ts_id = sanitizeInput($_POST['ts_id']);
    validateRequiredParams(array('ts_id' => $ts_id));
    sendSuccessResponse(_t("스쿼드 데이터가 저장되었습니다."));
}

/**
 * 응답 함수들
 */
function sendSuccessResponse($message, $data = array()) {
    global $result;
    $result['status'] = true;
    $result['msg'] = $message;
    $result['data'] = $data;
    if(isset($data['squad_id'])) $result['squad_id'] = $data['squad_id'];
    echo json_encode($result);
    exit;
}

function sendErrorResponse($message, $debug_info = '') {
    global $result;
    $result['status'] = false;
    $result['msg'] = $message;
    if($debug_info) $result['debug'] = $debug_info;
    echo json_encode($result);
    exit;
}
?>