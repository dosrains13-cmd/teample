<?php
include_once "./_common.php";
include_once "./setting.php";
include_once "../team/team.common.php";

$result = ['status' => false, 'msg' => '', 'our_score' => 0, 'opponent_score' => 0];

// 권한 체크
if(!($is_member_team || $is_admin_team)){
    $result['msg'] = _t("접근 권한이 없습니다.");
    echo json_encode($result);
    exit;
}

$w = $_POST['w'];
$ts_id = (int)$_POST['ts_id'];
$quarter = (int)($_POST['quarter'] ?: 1);

// 공통 유효성 검사
if(!$ts_id) {
    $result['msg'] = _t("일정 정보가 없습니다.");
    echo json_encode($result);
    exit;
}

try {
    switch($w) {
        case 'add_record': addRecord(); break;
        case 'update_score': updateScore(); break;
        case 'delete_record': deleteRecord(); break;
        case 'update_match_status': updateMatchStatus(); break;
        case 'substitution': handleSubstitution(); break;
        default: 
            $result['msg'] = _t("잘못된 요청입니다.");
    }
} catch (Exception $e) {
    $result['msg'] = _t("처리 중 오류가 발생했습니다.") . ": " . $e->getMessage();
    debugLog("Exception in update_match.php: " . $e->getMessage());
}

echo json_encode($result);

/**
 * 공통 함수들
 */

// 경기 정보 조회/생성 (캐싱 추가)
function getOrCreateMatch($ts_id, $quarter) {
    static $match_cache = [];
    $cache_key = "{$ts_id}_{$quarter}";
    
    if(isset($match_cache[$cache_key])) {
        return $match_cache[$cache_key];
    }
    
    $sql = "SELECT * FROM sweb_team_schedule_match WHERE ts_id = {$ts_id} AND sm_quarter = {$quarter}";
    $match = sql_fetch($sql);
    
    if(!$match) {
        $sql = "INSERT INTO sweb_team_schedule_match SET
                ts_id = {$ts_id}, sm_quarter = {$quarter}, sm_our_score = 0, 
                sm_opponent_score = 0, sm_status = 'ready', 
                insert_date = NOW(), insert_ip = '{$_SERVER['REMOTE_ADDR']}'";
        
        if(sql_query($sql)) {
            $match_id = sql_insert_id();
            $match = ['sm_id' => $match_id, 'sm_our_score' => 0, 'sm_opponent_score' => 0, 'sm_status' => 'ready'];
        }
    }
    
    $match_cache[$cache_key] = $match;
    return $match;
}

// 선수 정보 조회 (단순화)
function getPlayerInfo($sj_id) {
    static $player_cache = [];
    
    if(isset($player_cache[$sj_id])) {
        return $player_cache[$sj_id];
    }
    
    $sql = "SELECT j.*, COALESCE(tj.tj_name, j.sj_name) as name, tj.tj_number as number
            FROM sweb_team_schedule_join j
            LEFT JOIN sweb_team_join tj ON j.mb_id = tj.mb_id
            WHERE j.sj_id = {$sj_id}";
    
    $player = sql_fetch($sql);
    $player_cache[$sj_id] = $player;
    return $player;
}

// 점수 업데이트 (통합)
function updateMatchScore($match_id, $team, $change) {
    $field = ($team == 'our') ? 'sm_our_score' : 'sm_opponent_score';
    $sql = "UPDATE sweb_team_schedule_match SET 
            {$field} = GREATEST(0, {$field} + ({$change})),
            modify_date = NOW(), modify_ip = '{$_SERVER['REMOTE_ADDR']}'
            WHERE sm_id = {$match_id}";
    
    if(sql_query($sql)) {
        $sql = "SELECT sm_our_score, sm_opponent_score FROM sweb_team_schedule_match WHERE sm_id = {$match_id}";
        return sql_fetch($sql);
    }
    return false;
}

// 기록 저장 (통합)
function saveRecord($match_id, $sj_id, $type, $minute = null, $related_player = null, $description = null) {
    $sql = "INSERT INTO sweb_team_schedule_record SET
            sm_id = {$match_id}, sj_id = {$sj_id}, sr_type = '{$type}',
            sr_minute = " . ($minute ? $minute : 'NULL') . ",
            sr_related_player = " . ($related_player ? $related_player : 'NULL') . ",
            sr_description = " . ($description ? "'{$description}'" : 'NULL') . ",
            insert_date = NOW(), insert_ip = '{$_SERVER['REMOTE_ADDR']}'";
    
    return sql_query($sql);
}

// 경기 시간 계산 (단순화)
function calculateMatchMinute($match) {
    if(!$match['sm_start_time']) return 0;
    
    $elapsed = time() - strtotime($match['sm_start_time']) - ($match['sm_pause_total'] ?: 0);
    return max(0, floor($elapsed / 60));
}

// 디버그 로그 (조건부)
function debugLog($message) {
    if(defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("[Match Debug] " . $message);
    }
}

/**
 * 메인 처리 함수들
 */

function addRecord() {
    global $result, $ts_id, $quarter;
    
    $sj_id = (int)$_POST['sj_id'];
    $action_type = $_POST['action_type'];
    
    $valid_actions = ['goal', 'assist', 'yellow', 'red', 'sub_in', 'sub_out'];
    if(!$sj_id || !in_array($action_type, $valid_actions)) {
        $result['msg'] = _t("잘못된 입력 정보입니다.");
        return;
    }
    
    $match = getOrCreateMatch($ts_id, $quarter);
    $player = getPlayerInfo($sj_id);
    
    if(!$player) {
        $result['msg'] = _t("선수 정보를 찾을 수 없습니다.");
        return;
    }
    
    // 골인 경우 점수 업데이트
    if($action_type == 'goal') {
        $updated_match = updateMatchScore($match['sm_id'], 'our', 1);
        if($updated_match) {
            $result['our_score'] = $updated_match['sm_our_score'];
            $result['opponent_score'] = $updated_match['sm_opponent_score'];
        }
    }
    
    // 기록 저장
    if(saveRecord($match['sm_id'], $sj_id, $action_type)) {
        $result['status'] = true;
        $action_names = [
            'goal' => _t('골'), 'assist' => _t('어시스트'),
            'yellow' => _t('경고'), 'red' => _t('퇴장')
        ];
        $result['msg'] = $player['name'] . " " . $action_names[$action_type] . " " . _t("기록되었습니다.");
    } else {
        $result['msg'] = _t("기록 저장 중 오류가 발생했습니다.");
    }
}

function updateScore() {
    global $result, $ts_id, $quarter;
    
    $team = $_POST['team'];
    $action = $_POST['action'];
    
    if(!in_array($team, ['our', 'opponent']) || !in_array($action, ['plus', 'minus'])) {
        $result['msg'] = _t("잘못된 요청 정보입니다.");
        return;
    }
    
    $match = getOrCreateMatch($ts_id, $quarter);
    $change = ($action == 'plus') ? 1 : -1;
    
    // 0 이하로 내려가지 않도록 체크
    $current_score = ($team == 'our') ? $match['sm_our_score'] : $match['sm_opponent_score'];
    if($action == 'minus' && $current_score <= 0) {
        $result['msg'] = _t("점수는 0보다 작을 수 없습니다.");
        return;
    }
    
    $updated_match = updateMatchScore($match['sm_id'], $team, $change);
    if($updated_match) {
        $result['status'] = true;
        $result['our_score'] = $updated_match['sm_our_score'];
        $result['opponent_score'] = $updated_match['sm_opponent_score'];
        
        $team_name = ($team == 'our') ? _t("우리팀") : _t("상대팀");
        $action_text = ($action == 'plus') ? _t("득점") : _t("점수 차감");
        $result['msg'] = "{$team_name} {$action_text}! ({$result['our_score']}-{$result['opponent_score']})";
        
        // 기록 저장
        $record_type = $team . '_goal_' . $action;
        saveRecord($match['sm_id'], 0, $record_type);
    } else {
        $result['msg'] = _t("점수 업데이트 중 오류가 발생했습니다.");
    }
}

function updateMatchStatus() {
    global $result, $ts_id, $quarter;
    
    $status = $_POST['status'];
    if(!in_array($status, ['ready', 'playing', 'finished'])) {
        $result['msg'] = _t("잘못된 상태 정보입니다.");
        return;
    }
    
    $match = getOrCreateMatch($ts_id, $quarter);
    $update_fields = ["sm_status = '{$status}'", "modify_date = NOW()", "modify_ip = '{$_SERVER['REMOTE_ADDR']}'"];
    
    // 상태별 시간 처리
    switch($status) {
        case 'playing':
            if(!$match['sm_start_time']) {
                $update_fields[] = "sm_start_time = NOW()";
                $update_fields[] = "sm_pause_total = 0";
            } elseif($match['sm_pause_start']) {
                $pause_duration = time() - strtotime($match['sm_pause_start']);
                $new_total = ($match['sm_pause_total'] ?: 0) + $pause_duration;
                $update_fields[] = "sm_pause_total = {$new_total}";
            }
            $update_fields[] = "sm_pause_start = NULL";
            break;
            
        case 'ready':
            if($match['sm_status'] == 'playing') {
                $update_fields[] = "sm_pause_start = NOW()";
            }
            break;
            
        case 'finished':
            $update_fields[] = "sm_end_time = NOW()";
            $update_fields[] = "sm_pause_start = NULL";
            break;
    }
    
    $sql = "UPDATE sweb_team_schedule_match SET " . implode(', ', $update_fields) . " WHERE sm_id = {$match['sm_id']}";
    
    if(sql_query($sql)) {
        // 🔥 수정: 업데이트된 match 데이터 조회해서 응답에 포함
        $sql = "SELECT * FROM sweb_team_schedule_match WHERE sm_id = {$match['sm_id']}";
        $updated_match = sql_fetch($sql);
        
        $result['status'] = true;
        $status_names = ['ready' => _t('준비중'), 'playing' => _t('진행중'), 'finished' => _t('종료')];
        $result['msg'] = _t("경기 상태가") . " '{$status_names[$status]}'" . _t("로 변경되었습니다.");
        
        // 🔥 추가: match_data 포함
        $result['match_data'] = array(
            'status' => $updated_match['sm_status'],
            'start_time_timestamp' => $updated_match['sm_start_time'] ? strtotime($updated_match['sm_start_time']) : null,
            'pause_total' => $updated_match['sm_pause_total'] ?: 0,
            'pause_start' => $updated_match['sm_pause_start']
        );
    } else {
        $result['msg'] = _t("상태 업데이트 중 오류가 발생했습니다.");
    }
}

function deleteRecord() {
    global $result, $ts_id, $quarter;
    
    $sr_id = (int)$_POST['sr_id'];
    if(!$sr_id) {
        $result['msg'] = _t("삭제할 기록을 찾을 수 없습니다.");
        return;
    }
    
    $sql = "SELECT r.*, COALESCE(tj.tj_name, j.sj_name) as player_name
            FROM sweb_team_schedule_record r
            LEFT JOIN sweb_team_schedule_join j ON r.sj_id = j.sj_id
            LEFT JOIN sweb_team_join tj ON j.mb_id = tj.mb_id
            WHERE r.sr_id = {$sr_id}";
    $record = sql_fetch($sql);
    
    if(!$record) {
        $result['msg'] = _t("기록을 찾을 수 없습니다.");
        return;
    }
    
    // 점수 관련 기록 처리 (통합)
    $score_changes = [
        'goal' => ['our', -1],
        'team_goal_plus' => ['our', -1],
        'team_goal_minus' => ['our', 1],
        'opponent_goal_plus' => ['opponent', -1],
        'opponent_goal_minus' => ['opponent', 1]
    ];
    
    if(isset($score_changes[$record['sr_type']])) {
        list($team, $change) = $score_changes[$record['sr_type']];
        $updated_match = updateMatchScore($record['sm_id'], $team, $change);
        if($updated_match) {
            $result['our_score'] = $updated_match['sm_our_score'];
            $result['opponent_score'] = $updated_match['sm_opponent_score'];
        }
    }
    
    if(sql_query("DELETE FROM sweb_team_schedule_record WHERE sr_id = {$sr_id}")) {
        $result['status'] = true;
        $result['deleted_record'] = [
            'sj_id' => $record['sj_id'],
            'sr_type' => $record['sr_type'],
            'player_name' => $record['player_name']
        ];
        $result['msg'] = _t("기록이 삭제되었습니다.");
    } else {
        $result['msg'] = _t("기록 삭제 중 오류가 발생했습니다.");
    }
}

function handleSubstitution() {
    global $result, $ts_id, $quarter;
    
    $out_id = (int)$_POST['out_player_id'];
    $in_id = (int)$_POST['in_player_id'];
    $out_name = $_POST['out_player_name'];
    $in_name = $_POST['in_player_name'];
    
    if(!$out_id || !$in_id || $out_id == $in_id) {
        $result['msg'] = _t("교체 정보가 올바르지 않습니다.");
        return;
    }
    
    $match = getOrCreateMatch($ts_id, $quarter);
    if($match['sm_status'] != 'playing') {
        $result['msg'] = _t("진행 중인 경기에서만 교체가 가능합니다.");
        return;
    }
    
    $minute = calculateMatchMinute($match);
    $description = "{$out_name} OUT ↔ {$in_name} IN";
    
    // 트랜잭션으로 양쪽 기록 한번에 처리
    sql_query("START TRANSACTION");
    
    try {
        if(!saveRecord($match['sm_id'], $out_id, 'sub_out', $minute, $in_id, $description) ||
           !saveRecord($match['sm_id'], $in_id, 'sub_in', $minute, $out_id, $description)) {
            throw new Exception("교체 기록 저장 실패");
        }
        
        sql_query("COMMIT");
        
        $result['status'] = true;
        $result['msg'] = $description . " " . _t("교체가 완료되었습니다.");
        $result['substitution_data'] = [
            'out_player_id' => $out_id, 'out_player_name' => $out_name,
            'in_player_id' => $in_id, 'in_player_name' => $in_name
        ];
        
    } catch (Exception $e) {
        sql_query("ROLLBACK");
        $result['msg'] = _t("교체 처리 중 오류가 발생했습니다.");
        debugLog("Substitution error: " . $e->getMessage());
    }
}

?>