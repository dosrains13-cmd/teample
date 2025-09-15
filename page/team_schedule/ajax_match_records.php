<?php
include_once "./_common.php";
include_once "./setting.php";
include_once "../team/team.common.php";

$ts_id = $_REQUEST['ts_id']; // GET/POST 모두 지원
$quarter = (int)$_REQUEST['quarter'] ?: 1;
$page = (int)$_REQUEST['page'] ?: 1;
$rows = (int)$_REQUEST['rows'] ?: 10;
$get_player_records = $_REQUEST['get_player_records'];

$sql = "SELECT te_id FROM sweb_team_schedule WHERE ts_id = '{$ts_id}'";
$schedule_info = sql_fetch($sql);
$team_id = $schedule_info['te_id'];

// 권한 체크
if(!$is_member){
    // 로그인한 사용자면 접근 가능
    echo "<div>" . _t("로그인이 필요합니다.") . "</div>";
    exit;
}

// 해당 쿼터의 경기 기록 조회
$sql = "SELECT * FROM sweb_team_schedule_match WHERE ts_id = '{$ts_id}' AND sm_quarter = '{$quarter}'";
$match = sql_fetch($sql);

if(!$match) {
    if($page == 1) {
        echo '<div class="no-records"><p>' . _t("경기 기록이 없습니다.") . '</p></div>';
    }
    exit;
}

// 선수별 기록 요청인 경우 (기존 유지)
if($get_player_records) {
    $sql = "SELECT 
        sj_id,
        sr_type,
        COUNT(*) as count
    FROM sweb_team_schedule_record 
    WHERE sm_id = '{$match['sm_id']}'
        AND sj_id > 0
        AND sr_type IN ('goal', 'assist', 'yellow', 'red')
    GROUP BY sj_id, sr_type";
    
    $result = sql_query($sql);
    $player_records = array();
    
    while($row = sql_fetch_array($result)) {
        $sj_id = $row['sj_id'];
        $type = $row['sr_type'];
        $count = (int)$row['count'];
        
        if(!isset($player_records[$sj_id])) {
            $player_records[$sj_id] = array();
        }
        
        $player_records[$sj_id][$type] = $count;
    }
    
    echo json_encode(array(
        'status' => true, 
        'player_records' => $player_records
    ));
    exit;
}

// 페이징 처리
$from_record = ($page - 1) * $rows;

// 전체 기록 수 조회
$sql = "SELECT COUNT(*) as cnt FROM sweb_team_schedule_record WHERE sm_id = '{$match['sm_id']}'";
$total_result = sql_fetch($sql);
$total_count = $total_result['cnt'];
$total_page = ceil($total_count / $rows);

// 개별 기록들 조회
$sql = "SELECT DISTINCT
    r.sr_id,
    r.sj_id,
    r.sr_type,
    r.sr_minute,
    r.insert_date,
    j.sj_name,
    j.sj_is_guest,
    j.mb_id,
    tj.tj_name,
    tj.tj_number
FROM sweb_team_schedule_record r
LEFT JOIN sweb_team_schedule_join j ON r.sj_id = j.sj_id
LEFT JOIN sweb_team_join tj ON j.mb_id = tj.mb_id AND tj.te_id = '{$team_id}'
WHERE r.sm_id = '{$match['sm_id']}'
ORDER BY r.sr_id DESC
LIMIT {$from_record}, {$rows}";


$result = sql_query($sql);
$records = array();
while($row = sql_fetch_array($result)){
    $records[] = $row;
}


// 기록 유형별 한글 매핑
$action_names = array(
    'goal' => _t('골'),
    'assist' => _t('어시스트'),
    'yellow' => _t('경고'),
    'red' => _t('퇴장'),
    'sub_in' => _t('교체 IN'),
    'sub_out' => _t('교체 OUT'),
    'team_goal_plus' => _t('우리팀 득점'),
    'team_goal_minus' => _t('우리팀 점수 차감'),
    'opponent_goal_plus' => _t('상대팀 득점'),
    'opponent_goal_minus' => _t('상대팀 점수 차감')
);

?>

<?php if($page == 1): ?>
<h3><?php echo _t('최근 기록'); ?></h3>
<ul class="record-items" id="record_item_area">
<?php endif; ?>

<?php if(empty($records) && $page == 1): ?>
<li class="no-records">
    <p><?php echo _t('아직 기록된 내용이 없습니다.'); ?></p>
</li>
<?php endif; ?>

<?php if(!empty($records)): ?>
    <?php foreach($records as $record): ?>
        <?php
        // 선수명 결정
		$player_name = '';
		if($record['sj_id'] > 0) {
			if(!empty($record['sj_is_guest']) && $record['sj_is_guest'] == '1') {
				$player_name = $record['sj_name'] ?: _t('게스트');
			} else {
				// 🔥 수정: tj_name 우선, 없으면 sj_name
				$player_name = $record['tj_name'] ?: $record['sj_name'] ?: _t('알 수 없음');
			}
		}
        
        $action_name = isset($action_names[$record['sr_type']]) ? $action_names[$record['sr_type']] : $record['sr_type'];
        $record_time = date('H:i:s', strtotime($record['insert_date']));
        $is_team_score = in_array($record['sr_type'], array('team_goal_plus', 'team_goal_minus', 'opponent_goal_plus', 'opponent_goal_minus'));
        ?>
        <li class="record-item <?php echo $record['sr_type']; ?>">
            <div class="record-content">
                <?php if(!$is_team_score && $player_name): ?>
                <span class="player-name"><?php echo htmlspecialchars($player_name); ?></span>
                <?php endif; ?>
                <span class="action-type"><?php echo $action_name; ?></span>
                
                <?php if($record['sr_minute']): ?>
                <span class="minute"><?php echo $record['sr_minute']; ?>'</span>
                <?php endif; ?>
            </div>
            <div class="record-actions">
                <div class="record-time"><?php echo $record_time; ?></div>
                <button class="delete-record-btn" onclick="deleteRecord(<?php echo $record['sr_id']; ?>)" title="<?php echo _t('삭제'); ?>">×</button>
            </div>
        </li>
    <?php endforeach; ?>
<?php endif; ?>

<?php if($page == 1): ?>
</ul>
<?php endif; ?>

<?php if($total_page <= $page): ?>
    <?php if($page > 1): ?>
    <li class="scroll_end"></li>
    <?php endif; ?>
    <div class="data_end"></div>
<?php endif; ?>








<?php
/**
 * 시간 경과 표시 함수
 */
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => _t('년'),
        'm' => _t('개월'),
        'w' => _t('주'),
        'd' => _t('일'),
        'h' => _t('시간'),
        'i' => _t('분'),
        's' => _t('초'),
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . $v;
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ' . _t('전') : _t('방금 전');
}
?>

<style>
.record-items {
    list-style: none;
    padding: 0;
    margin: 0;
}

.record-item {
    padding: 8px 12px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.record-item:last-child {
    border-bottom: none;
}

.record-content {
    flex: 1;
}

.player-name {
    font-weight: bold;
    margin-right: 8px;
}

.action-type {
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
    margin-right: 8px;
}

.record-item.goal .action-type {
    background: #28a745;
    color: white;
}

.record-item.assist .action-type {
    background: #17a2b8;
    color: white;
}

.record-time {
    font-size: 11px;
    color: #999;
    white-space: nowrap;
}


/* 팀 점수 기록 스타일 */
.record-item.team_goal_plus .action-type,
.record-item.opponent_goal_plus .action-type {
    background: #28a745;
    color: white;
}

.record-item.team_goal_minus .action-type,
.record-item.opponent_goal_minus .action-type {
    background: #dc3545;
    color: white;
}


</style>