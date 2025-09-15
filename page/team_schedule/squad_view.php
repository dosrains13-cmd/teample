<?php 
include_once "./_common.php";

include_once "./setting.php";
include_once "../team/team.common.php";
if($error_msg) alert($error_msg, G5_URL);
$code3 = "squad_view";
$g5['title'] = "스쿼드 보기";

include_once(G5_THEME_MOBILE_PATH.'/head.php');

if($parent['te_is_schedule'] || $is_admin_team || $is_member_team){
    // 접근 권한 있음
}else{
    alert("접근 권한이 없습니다.");
    exit;
}

// 접근권한 체크
fn_authCheck($sweb['view_level'], "");

// 일정 데이터 조회
$sql = "
select 
    T1.*, 
    T2.sj_id,
    if(T2.sj_id, '1', '') as is_join,
    T2.sj_status,
    T2.sj_gubun
from {$table_name} T1 
left outer join (
    select {$key_column}, sj_id, sj_status, sj_gubun from {$table_name_join} where mb_id = '".$member['mb_id']."'
) T2
ON T1.{$key_column} = T2.{$key_column} 
where T1.{$key_column} = '{$key}'";
$view = sql_fetch($sql);

// 날짜 형식 생성
$date = "";
if($view[$prefix.'start_date'] == $view[$prefix.'end_date']){
    $date .= $view[$prefix.'start_date'] . " (".get_yoil($view[$prefix.'start_date']).") <span>" . $view[$prefix.'start_time'] . "~" . $view[$prefix.'end_time']."</span>";
}else{
    $date .= $view[$prefix.'start_date'] . " (".get_yoil($view[$prefix.'start_date']).") " . $view[$prefix.'start_time'] . " ~ " . $view[$prefix.'end_date'] . " (".get_yoil($view[$prefix.'end_date']).") " . $view[$prefix.'end_time'];
}

// 기존 스쿼드 조회
$sql = "SELECT * FROM sweb_team_schedule_squad WHERE ts_id = '{$key}' ORDER BY sq_type, sq_quarter ASC";
$result = sql_query($sql);
$squads = array();
while($row = sql_fetch_array($result)){
    $squads[$row['sq_type']][$row['sq_quarter']] = array(
        'sq_id' => $row['sq_id'],
        'formation' => $row['sq_formation']
    );
}

// 포지션별 선수 조회
$squad_positions = array();
$player_info = array();
if(!empty($squads)){
    $sq_ids = array();
    foreach($squads as $type => $quarters){
        foreach($quarters as $quarter => $squad){
            $sq_ids[] = $squad['sq_id'];
        }
    }
    
    if(!empty($sq_ids)){
        // 포지션 정보 가져오기
        $sql = "SELECT * FROM sweb_team_schedule_position WHERE sq_id IN (".implode(',', $sq_ids).")";
        $result = sql_query($sql);
        while($row = sql_fetch_array($result)){
            $squad_positions[$row['sq_id']][$row['sp_position']] = $row['sj_id'];
        }
        
        // 선수 정보 가져오기
        $sj_ids = array();
        foreach($squad_positions as $sq_id => $positions){
            foreach($positions as $position => $sj_id){
                $sj_ids[] = $sj_id;
            }
        }
        
        if(!empty($sj_ids)){
            $sj_ids = array_unique($sj_ids);
            $sql = "SELECT 
                j.sj_id, j.sj_name, j.sj_is_guest,
                tj.tj_name, tj.tj_number
            FROM {$table_name_join} AS j
            LEFT JOIN sweb_team_join AS tj ON j.mb_id = tj.mb_id AND tj.te_id = '{$parent_key}'
            WHERE j.sj_id IN (".implode(',', $sj_ids).")";
            $result = sql_query($sql);
            while($row = sql_fetch_array($result)){
                $player_info[$row['sj_id']] = array(
                    'name' => ($row['tj_name'] ? $row['tj_name'] : $row['sj_name']),
                    'number' => $row['tj_number'],
                    'is_guest' => $row['sj_is_guest']
                );
            }
        }
    }
}

// 유형별 최대 쿼터 수
$max_quarters = array(
    'soccer' => 4,
    'futsal' => 2
);

// 현재 선택된 유형 및 쿼터
$current_type = isset($_GET['type']) ? $_GET['type'] : 'soccer';
$current_quarter = isset($_GET['quarter']) ? $_GET['quarter'] : 1;

// 현재 선택된 스쿼드
$current_squad = isset($squads[$current_type][$current_quarter]) ? $squads[$current_type][$current_quarter] : null;

// 포메이션 매핑 정보 - 화면에 보여줄 형태로 변환
$formation_display = array(
    'soccer' => array(
        '4-3-3' => '4-3-3',
        '4-4-2' => '4-4-2',
        '4-2-3-1' => '4-2-3-1',
        '3-5-2' => '3-5-2',
        '3-4-3' => '3-4-3',
        '5-3-2' => '5-3-2',
        '5-4-1' => '5-4-1',
        '4-1-4-1' => '4-1-4-1',
        '4-5-1' => '4-5-1',
        '4-1-2-1-2' => '4-1-2-1-2 (다이아몬드)'
    ),
    'futsal' => array(
        '1-2-1' => '1-2-1 (다이아몬드)',
        '2-2' => '2-2 (스퀘어)',
        '3-1' => '3-1',
        '1-3' => '1-3',
        '2-1-1' => '2-1-1 (삼각형)'
    )
);

?>

<!-- view start -->
<?php include "../team/tab.php"; ?>

<div class="schedule-view">
    <div class="schedule-info round">
        <div class="schedule">
            <ul>
                <li>
                    <div>
                        <i>
                            <?php if($arr_gubun[$view[$prefix.'gubun']]){ ?><?php echo $arr_gubun[$view[$prefix.'gubun']]; ?><?php } ?>
                        </i>
                        <?php if($is_admin_team || $is_member_team){ ?>
                        <a href="#none;" onclick="fn_squad_edit();" class="team_adm schedule_adm">
                            <i class="fa fa-edit" aria-hidden="true"></i>
                        </a>
                        <?php } ?>
                        <p><?php echo $date; ?></p>
                        <div class="location"><?php echo $view[$prefix.'location']; ?></div>
                        <h4><?php echo $view[$prefix.'name']; ?></h4>				
                    </div>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="squad-container">
    <div class="squad-tabs">
        <ul>
            <li class="<?php echo ($current_type == 'soccer') ? 'active' : ''; ?>" onclick="changeSquadView('soccer', 1)">축구</li>
            <li class="<?php echo ($current_type == 'futsal') ? 'active' : ''; ?>" onclick="changeSquadView('futsal', 1)">풋살</li>
        </ul>
    </div>
    
    <!-- 축구 쿼터 탭 -->
    <div class="quarter-tabs" id="soccer_quarters" style="<?php echo ($current_type == 'soccer') ? '' : 'display:none'; ?>">
        <?php for($i=1; $i <= $max_quarters['soccer']; $i++){ ?>
            <button type="button" class="quarter-btn <?php echo ($current_type == 'soccer' && $current_quarter == $i) ? 'active' : ''; ?>" onclick="changeSquadView('soccer', <?php echo $i; ?>)"><?php echo $i; ?>쿼터</button>
        <?php } ?>
    </div>
    
    <!-- 풋살 쿼터 탭 -->
    <div class="quarter-tabs" id="futsal_quarters" style="<?php echo ($current_type == 'futsal') ? '' : 'display:none'; ?>">
        <?php for($i=1; $i <= $max_quarters['futsal']; $i++){ ?>
            <button type="button" class="quarter-btn <?php echo ($current_type == 'futsal' && $current_quarter == $i) ? 'active' : ''; ?>" onclick="changeSquadView('futsal', <?php echo $i; ?>)"><?php echo $i; ?>쿼터</button>
        <?php } ?>
    </div>
    
    <?php if($current_squad): ?>
    <div class="formation-info">
        <h3><?php echo isset($formation_display[$current_type][$current_squad['formation']]) ? $formation_display[$current_type][$current_squad['formation']] : $current_squad['formation']; ?> 포메이션</h3>
    </div>
    
    <div class="field-container">
        <?php
			// 포메이션에 따른 포지션 레이아웃 생성
			$positions = explode('-', $current_squad['formation']);
			$position_layout = array();

			// 공격수부터 수비수까지 순서대로 추가
			for($i = 0; $i < count($positions); $i++) {
				$count = (int)$positions[$i];
				$row = array();
				
				$posType = "";
				switch($i) {
					case 0: $posType = "FW"; break;
					case count($positions) - 1: $posType = "DF"; break;
					default: 
						if($current_type == 'soccer') {
							$posType = "MF" . ($i+1);
						} else {
							$posType = "MF";
						}
						break;
				}
				
				for($j = 0; $j < $count; $j++) {
					$row[] = $posType . ($j+1);
				}
				
				$position_layout[] = $row;
			}

			// GK는 항상 마지막에 추가
			$position_layout[] = array('GK');
        ?>
        
        <div class="squad-field" style="background-image: url('<?php echo G5_IMG_URL; ?>/field.jpg');">
            <?php foreach($position_layout as $row_idx => $row): ?>
            <div class="position-row <?php echo ($row_idx == 0) ? 'gk-row' : ''; ?>">
                <?php foreach($row as $position): ?>
                    <?php
                    $player_id = isset($squad_positions[$current_squad['sq_id']][$position]) ? $squad_positions[$current_squad['sq_id']][$position] : null;
                    $has_player = $player_id && isset($player_info[$player_id]);
                    ?>
                    <div class="position <?php echo $has_player ? 'filled' : ''; ?>" data-position="<?php echo $position; ?>">
                        <div class="player-icon"><?php echo $position; ?></div>
                        <?php if($has_player): ?>
                        <div class="player-info">
                            <span class="number"><?php echo $player_info[$player_id]['number']; ?></span>
                            <span class="name"><?php echo $player_info[$player_id]['name']; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php else: ?>
    <div class="no-squad-message">
        <p>등록된 스쿼드 정보가 없습니다.</p>
        <?php if($is_admin_team || $is_member_team): ?>
        <p><button type="button" onclick="fn_squad_edit();">스쿼드 등록하기</button></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="btn-box">
        <button type="button" onclick="fn_view();return false;" class="dont">일정으로 돌아가기</button>
    </div>
</div>

<!-- page 이동 form start -->
<form method="get" id="moveForm" name="moveForm">
    <input type="hidden" name="page" value="<?php echo $page; ?>">
    <input type="hidden" name="<?php echo $key_column; ?>" value="<?php echo $key; ?>" />
    <input type="hidden" name="<?php echo $parent_key_column; ?>" value="<?php echo $parent_key; ?>" />
    <?php foreach($sweb['list']['qstr'] as $key_qstr => $value_qstr){ ?>
    <input type="hidden" name="<?php echo $key_qstr; ?>" value="<?php echo $value_qstr; ?>">
    <?php } ?>
</form>
<!-- // page 이동 form end -->

<?php
include_once(G5_THEME_MOBILE_PATH.'/tail.php');
?>

<script>
// 스쿼드 유형 및 쿼터 변경
function changeSquadView(type, quarter) {
    // 현재 URL 가져오기
    var url = new URL(window.location.href);
    
    // 파라미터 업데이트
    url.searchParams.set('type', type);
    url.searchParams.set('quarter', quarter);
    
    // 페이지 이동
    window.location.href = url.toString();
}

// 일정 화면으로 이동
function fn_view() {
    document.moveForm.<?php echo $key_column; ?>.value = '<?php echo $key; ?>';
    $('#moveForm').attr("action", "<?php echo $sweb['view_url']; ?>");
    $('#moveForm').submit();
}

// 스쿼드 편집 화면으로 이동
function fn_squad_edit() {
    document.moveForm.<?php echo $key_column; ?>.value = '<?php echo $key; ?>';
    $('#moveForm').attr("action", "squad.php");
    $('#moveForm').submit();
}
</script>

<style>
/* 스쿼드 탭 스타일 */
.squad-tabs {
    display: flex;
    margin-bottom: 15px;
    border-bottom: 1px solid #ddd;
}
.squad-tabs ul {
    display: flex;
    width: 100%;
}
.squad-tabs li {
    flex: 1;
    padding: 10px;
    text-align: center;
    cursor: pointer;
    background: #f5f5f5;
}
.squad-tabs li.active {
    background: #007bff;
    color: white;
    font-weight: bold;
}

/* 쿼터 탭 스타일 */
.quarter-tabs {
    display: flex;
    margin-bottom: 15px;
    gap: 5px;
}
.quarter-btn {
    flex: 1;
    padding: 8px 0;
    border: 1px solid #ddd;
    background: #f8f9fa;
    cursor: pointer;
    text-align: center;
    border-radius: 4px;
}
.quarter-btn.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

/* 포메이션 정보 스타일 */
.formation-info {
    margin-bottom: 15px;
    text-align: center;
}
.formation-info h3 {
    font-size: 18px;
    margin: 10px 0;
}

/* 필드 스타일 */
.field-container {
    position: relative;
    margin-bottom: 20px;
}
.squad-field {
    background-size: 100% 100%;
    padding: 15px 10px;
    border-radius: 10px;
    min-height: 400px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

/* 포지션 행 스타일 */
.position-row {
    display: flex;
    justify-content: space-around;
    margin: 10px 0;
}
.gk-row {
    margin-top: auto;
}

/* 포지션 스타일 */
.position {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.7);
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
}
.position.filled {
    background: rgba(0, 123, 255, 0.7);
    color: white;
}
.player-icon {
    font-size: 12px;
    font-weight: bold;
}
.player-info {
    text-align: center;
    font-size: 10px;
    margin-top: 2px;
    display: flex;
    flex-direction: column;
}
.player-info .number {
    font-weight: bold;
    font-size: 12px;
}
.player-info .name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 60px;
}

/* 스쿼드 없을 때 메시지 */
.no-squad-message {
    text-align: center;
    padding: 40px 20px;
    background: #f8f9fa;
    border-radius: 8px;
    margin: 20px 0;
}
.no-squad-message p {
    margin-bottom: 15px;
}
.no-squad-message button {
    padding: 10px 20px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

/* 버튼 영역 스타일 */
.btn-box {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-top: 20px;
}
.btn-box button {
    flex: 1;
    padding: 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
}
.btn-box .dont {
    background: #f5f5f5;
}
.btn-box button:last-child {
    background: #28a745;
    color: white;
}