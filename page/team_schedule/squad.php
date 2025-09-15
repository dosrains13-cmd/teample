<?php 
include_once "./_common.php";
include_once "./setting.php";
include_once "../team/team.common.php";

if($error_msg) alert($error_msg, G5_URL);
$code3 = "squad";
$g5['title'] = "스쿼드 관리";

// 올바른 키 값 설정
$schedule_id = $_GET['ts_id'];
$team_id = $_GET['te_id'];

include_once(G5_THEME_MOBILE_PATH.'/head.php');



// 일정 데이터 조회
$sql = "SELECT * FROM {$table_name} WHERE {$key_column} = '{$key}'";
$view = sql_fetch($sql);

if (!$view) {
    alert(_t("일정 정보를 찾을 수 없습니다."));
}

// 일정 작성자인지 확인
$is_schedule_creator = ($view['mb_id'] == $member['mb_id']);

// 스쿼드 수정 권한 체크 (일정 작성자도 포함)
$can_edit_squad = ($is_admin_team || $is_admin == 'super' || $is_schedule_creator);

// 권한 체크 - 팀원이거나 일정 작성자면 접근 가능
if(!($is_member_team || $is_admin_team || $is_schedule_creator)){
    alert(_t("팀원만 접근 가능합니다."));
    exit;
}


// 스쿼드 수정 권한 체크
$can_edit_squad = ($is_admin_team || $is_admin == 'super' || $view['mb_id'] == $member['mb_id']);

// gubun2에 따른 설정
$gubun2 = $view[$prefix.'gubun2'];
$squad_config = array();

switch($gubun2) {
    case '285': // 자체전
        $squad_config = array(
            'mode' => 'self',
            'teams' => array('A' => 'A팀', 'B' => 'B팀'),
            'quarter_fixed' => false,
            'default_quarters' => 2,
            'max_quarters' => 6
        );
        break;
    case '286': // 리그전
        $squad_config = array(
            'mode' => 'league',
            'teams' => array('our' => '우리팀'),
            'quarter_fixed' => true,
            'default_quarters' => 2,
            'max_quarters' => 2,
            'quarter_labels' => array(1 => '전반', 2 => '후반')
        );
        break;
    case '284': // 일반전
    default:
        $squad_config = array(
            'mode' => 'normal',
            'teams' => array('our' => '우리팀'),
            'quarter_fixed' => false,
            'default_quarters' => 2,
            'max_quarters' => 6
        );
        break;
}

// 날짜 형식 생성
$date = "";
if($view[$prefix.'start_date'] == $view[$prefix.'end_date']){
    $date .= $view[$prefix.'start_date'] . " (".get_yoil($view[$prefix.'start_date']).") <span>" . $view[$prefix.'start_time'] . "~" . $view[$prefix.'end_time']."</span>";
} else {
    $date .= $view[$prefix.'start_date'] . " ~ " . $view[$prefix.'end_date'];
}

// 참석자 목록 조회
$sql = "SELECT 
    j.*, 
    tj.tj_name, 
    tj.tj_number
FROM {$table_name_join} AS j
LEFT JOIN sweb_team_join AS tj ON j.mb_id = tj.mb_id AND tj.te_id = '{$team_id}'
WHERE j.ts_id = '{$schedule_id}' 
    AND (j.sj_status = '1' OR j.sj_is_guest = '1')
ORDER BY tj.tj_number ASC, j.sj_name ASC";

$result = sql_query($sql);
$players = array();
while($row = sql_fetch_array($result)){
    $players[] = array(
        'sj_id' => $row['sj_id'],
        'name' => ($row['tj_name'] ? $row['tj_name'] : $row['sj_name']),
        'number' => $row['tj_number'] ? $row['tj_number'] : '-',
        'is_guest' => $row['sj_is_guest']
    );
}

// 기존 스쿼드 데이터 조회
$squads = array();
$sql = "SELECT * FROM sweb_team_schedule_squad WHERE ts_id = '{$schedule_id}'";
$result = sql_query($sql);
while($row = sql_fetch_array($result)){
    $team = $row['sq_type'] ? $row['sq_type'] : 'our';
    $squads[$team][$row['sq_quarter']] = array(
        'sq_id' => $row['sq_id'],
        'formation' => $row['sq_formation']
    );
}

// 포지션별 선수 조회
$positions = array();
if(!empty($squads)){
    $sq_ids = array();
    foreach($squads as $team => $quarters){
        foreach($quarters as $quarter => $squad){
            $sq_ids[] = $squad['sq_id'];
        }
    }
    
    if(!empty($sq_ids)){
        $sql = "SELECT * FROM sweb_team_schedule_position WHERE sq_id IN (".implode(',', $sq_ids).")";
        $result = sql_query($sql);
        while($row = sql_fetch_array($result)){
            $positions[$row['sq_id']][$row['sp_position']] = $row['sj_id'];
        }
    }
}


// 현재 팀(first key) 에 이미 저장된 쿼터 수
$team_key = array_keys($squad_config['teams'])[0];
$saved_quarters = isset($squads[$team_key]) ? count($squads[$team_key]) : 0;
$initial_quarters = max($squad_config['default_quarters'], $saved_quarters);


?>



<?php 
// 카톡 제목
$kakao_title = $view[$prefix.'name'];

// 카톡 본문
$kakao_description = strip_tags($date) . "\\n";
$kakao_description .= $view['ts_location'];

if($view[$prefix.'match_team']) {
    $kakao_description .= "\\nVS " . $view[$prefix.'match_team'];
}

$kakao_description .= "\\n참석인원 : " . count($players) . "명";

// 스쿼드 정보 추가
if(!empty($squads)) {
    // 선수 맵 생성
    $player_map = array();
    foreach($players as $p) {
        $player_map[$p['sj_id']] = $p['number'] . ' ' . $p['name'];
    }
    
    // 팀별, 쿼터별 스쿼드
    foreach($squads as $team => $quarters) {
        // 자체전일 때 팀명 표시
        if($squad_config['mode'] == 'self') {
            $team_name = ($team == 'A') ? 'A팀' : 'B팀';
            $kakao_description .= "\\n\\n=== " . $team_name . " ===";
        }
        
        foreach($quarters as $quarter => $squad) {
            // 쿼터 제목
            if($squad_config['quarter_labels'] && isset($squad_config['quarter_labels'][$quarter])) {
                $quarter_label = $squad_config['quarter_labels'][$quarter];
            } else {
                $quarter_label = $quarter . "쿼터";
            }
            
            $kakao_description .= "\\n\\n【" . $quarter_label . " - " . $squad['formation'] . "】";
            
            // 포지션별 선수 (GK 먼저, 나머지 알파벳순)
            if(isset($positions[$squad['sq_id']])) {
                $squad_positions = $positions[$squad['sq_id']];
                uksort($squad_positions, function($a, $b) {
                    if($a == 'GK') return -1;
                    if($b == 'GK') return 1;
                    return strcmp($a, $b);
                });
                
                foreach($squad_positions as $pos => $player_id) {
                    if(isset($player_map[$player_id])) {
                        $kakao_description .= "\\n" . $pos . " : " . $player_map[$player_id];
                    }
                }
            } else {
                $kakao_description .= "\\n선수 배치 없음";
            }
        }
    }
} else {
    $kakao_description .= "\\n\\n스쿼드 미구성";
}

$kakao_url = G5_URL . "/page/team_schedule/match_record.php?ts_id=".$schedule_id."&te_id=".$team_id;
?>


<link rel="stylesheet" href="./style.css?ver=3">








<?php include "../team/tab.php"; ?>

<div class="squad-management">
    <!-- 일정 정보 -->
    <div class="schedule-info round">
        <div class="schedule">
            <ul>
                <li>
                    <div>
                        <i>
                            <?php echo _t($arr_gubun[$view[$prefix.'gubun']]); ?>
                            <?php if($view[$prefix.'gubun2'] && $arr_gubun2[$view[$prefix.'gubun2']]){ ?>
                                · <?php echo _t($arr_gubun2[$view[$prefix.'gubun2']]); ?>
                            <?php } ?>
                        </i>
                        <?php if($can_edit_squad){ ?>
                        <a href="#none;" onclick="fn_modify();" class="team_adm schedule_adm">
                            <i class="fa fa-cog" aria-hidden="true"></i>
                        </a>
                        <?php } ?>

						<?php if($view[$prefix.'match_team']) { ?>
						<b>VS <?php echo $view[$prefix.'match_team']; ?></b>
						<?php } ?>

                        <p><?php echo $date; ?></p>
                        <div class="location"><?php echo $view[$prefix.'location']; ?></div>
                        <h4><?php echo $view[$prefix.'name']; ?></h4>				
                    </div>
                </li>
            </ul>
        </div>
    </div>

    <?php if($can_edit_squad){ ?>
    <form method="post" id="squadForm" name="squadForm" action="./update_squad.php">
        <input type="hidden" name="w" value="squad" />
        <input type="hidden" name="ts_id" id="ts_id" value="<?php echo $schedule_id; ?>" />
        <input type="hidden" name="te_id" value="<?php echo $team_id; ?>" />
        <input type="hidden" name="gubun2" value="<?php echo $gubun2; ?>" />
        <input type="hidden" name="current_team" id="current_team" value="<?php echo array_keys($squad_config['teams'])[0]; ?>" />
        <input type="hidden" name="current_quarter" id="current_quarter" value="1" />
        

        <!-- 팀 선택 탭 (자체전일 때만) -->
        <?php if($squad_config['mode'] == 'self'): ?>

        <div class="team-tabs">
            <?php foreach($squad_config['teams'] as $team_key => $team_name): ?>
            <div class="team-tab <?php echo array_keys($squad_config['teams'])[0] == $team_key ? 'active' : ''; ?>" 
                 data-team="<?php echo $team_key; ?>" 
                 onclick="changeTeam('<?php echo $team_key; ?>')">
                <?php echo _t($team_name); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>


        <!-- 쿼터 관리 -->
        <div class="quarter-management">

            <div class="quarter-tabs" id="quarter_tabs_container">
                <!-- 동적 생성 -->
            </div>


			<div class="flex">
				<!-- 포메이션 선택 -->
				<div class="formation-select">
					<label for="formation"><?php echo _t('포메이션'); ?></label>
					<select name="formation" id="formation" onchange="changeFormation()">
						<optgroup label="<?php echo _t('4백 시스템'); ?>">
							<option value="4-3-3">4-3-3 (<?php echo _t('공격적'); ?>)</option>
							<option value="4-4-2">4-4-2 (<?php echo _t('클래식'); ?>)</option>
							<option value="4-2-3-1">4-2-3-1 (<?php echo _t('현대적'); ?>)</option>
							<option value="4-1-4-1">4-1-4-1 (<?php echo _t('수비적'); ?>)</option>
							<option value="4-3-1-2">4-3-1-2 (<?php echo _t('트레콰르티스타'); ?>)</option>
							<option value="4-5-1">4-5-1 (<?php echo _t('카운터 어택'); ?>)</option>
						</optgroup>
						<optgroup label="<?php echo _t('3백 시스템'); ?>">
							<option value="3-5-2">3-5-2 (<?php echo _t('윙백 활용'); ?>)</option>
							<option value="3-4-3">3-4-3 (<?php echo _t('공격적 3백'); ?>)</option>
							<option value="3-4-1-2">3-4-1-2 (<?php echo _t('3백 변형'); ?>)</option>
							<option value="3-6-1">3-6-1 (<?php echo _t('극수비'); ?>)</option>
						</optgroup>
						<optgroup label="<?php echo _t('5백 시스템'); ?>">
							<option value="5-3-2">5-3-2 (<?php echo _t('수비 안정'); ?>)</option>
							<option value="5-4-1">5-4-1 (<?php echo _t('극수비'); ?>)</option>
							<option value="5-2-3">5-2-3 (<?php echo _t('수비적 공격'); ?>)</option>
						</optgroup>
						<optgroup label="<?php echo _t('특수 포메이션'); ?>">
							<option value="4-1-2-1-2">4-1-2-1-2 (<?php echo _t('다이아몬드'); ?>)</option>
							<option value="4-4-1-1">4-4-1-1 (<?php echo _t('세컨드 스트라이커'); ?>)</option>
							<option value="4-6-0">4-6-0 (False 9)</option>
						</optgroup>
						<?php if($squad_config['mode'] == 'futsal'): ?>
						<optgroup label="<?php echo _t('풋살 포메이션'); ?>">
							<option value="1-2-1">1-2-1 (<?php echo _t('다이아몬드'); ?>)</option>
							<option value="2-2">2-2 (<?php echo _t('스퀘어'); ?>)</option>
							<option value="3-1">3-1 (<?php echo _t('수비적'); ?>)</option>
							<option value="1-3">1-3 (<?php echo _t('공격적'); ?>)</option>
							<option value="2-1-1">2-1-1 (<?php echo _t('비대칭'); ?>)</option>
						</optgroup>
						<?php endif; ?>
					</select>
				</div>
				

				<div class="quarter-header">
					<div class="quarter-header-controls">
						<?php if(!$squad_config['quarter_fixed']): ?>
						<div class="quarter-controls">
							<button type="button" class="quarter-btn-control" onclick="removeQuarter()" title="<?php echo _t('쿼터 제거'); ?>">-</button>
							<span id="quarter_count"><?php echo $initial_quarters; ?></span>

							<button type="button" class="quarter-btn-control" onclick="addQuarter()" title="<?php echo _t('쿼터 추가'); ?>">+</button>
						</div>
						<?php endif; ?>

					</div>
				</div>
            </div>

        </div>
        

        <!-- 메인 콘텐츠 영역 -->
        <div class="squad-content">
            <!-- 왼쪽: 선수 목록 -->
            <div class="player-list-panel">
                <h3><?php echo _t('참석 선수'); ?> (<?php echo count($players); ?>명)</h3>
                <div class="player-list">
                    <?php foreach($players as $player): ?>
                    <div class="player-item round" data-sjid="<?php echo $player['sj_id']; ?>">
                        <div class="player-basic">
                            <span class="player-number"><?php echo $player['number']; ?></span>
                            <span class="player-name"><?php echo $player['name']; ?></span>
                            <?php if($player['is_guest']): ?>
                            <span class="guest-badge"><?php echo _t('게스트'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="player-quarters" id="player_quarters_<?php echo $player['sj_id']; ?>">
                            <!-- 동적 생성 -->
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- 오른쪽: 필드 -->
            <div class="field-panel">
                <div class="soccer-field" id="field_container">
                    <!-- 동적 생성 -->
                </div>
            </div>
        </div>
						
		<!-- 쿼터 초기화 버튼 -->
		<div class="quarter-reset-controls">
			<button type="button" class="quarter-reset-btn" onclick="resetCurrentQuarter()" title="<?php echo _t('현재 쿼터 초기화'); ?>">
				<i class="fa fa-refresh" aria-hidden="true"></i> <?php echo _t('쿼터 리셋'); ?>
			</button>
			<button type="button" class="all-reset-btn" onclick="resetAllSquads()" title="<?php echo _t('모든 쿼터 초기화'); ?>">
				<i class="fa fa-trash" aria-hidden="true"></i> <?php echo _t('전체 리셋'); ?>
			</button>
			
			<button type="button" class="match-start-btn" onclick="startMatch()" title="<?php echo _t('경기 시작'); ?>">
				<i class="fa fa-play" aria-hidden="true"></i> <?php echo _t('경기 시작'); ?>
			</button>




			<div class="menu">
				<a href="javascript:Kakao_sendSquad();" class="share-btn">
					<img src="<?php echo G5_IMG_URL ?>/kakao.png" alt="<?php echo _t('카톡 공유'); ?>">
					<span><?php echo _t('스쿼드 공유'); ?></span>
				</a>			
			</div>





		</div>



		<!--
        <div class="btn-box">
            <button type="button" onclick="history.back();" class="dont"><?php echo _t('취소'); ?></button>
            <button type="button" onclick="saveSquad()"><?php echo _t('저장하기'); ?></button>
        </div>
		-->
    </form>
    
    <?php } else { ?>
    <!-- 조회 전용 모드 -->
    <div class="squad-view-only">
        <p><?php echo _t('수정 권한이 없습니다.'); ?></p>
    </div>
    <?php } ?>
</div>







<!-- 포메이션 및 포지션 시스템 JavaScript 포함 -->
<script src="./squad_formations.js"></script>
<!-- 선수 배치 JavaScript 포함 -->
<script src="./squad_player_assignment.js"></script>




<script>
// 다국어 번역 객체
window.squadTranslations = {
    // 모달 관련
    'player_selection': '<?php echo _t('선수 선택'); ?>',
    'clear_position': '<?php echo _t('포지션 비우기'); ?>',
    'cancel': '<?php echo _t('취소'); ?>',
    
    // 선수 상태
    'unassigned_players': '<?php echo _t('미배치 선수'); ?>',
    'assigned_players': '<?php echo _t('배치된 선수'); ?>',
    'people_count': '<?php echo _t('명'); ?>',
    'guest': '<?php echo _t('게스트'); ?>',
    'current_assigned': '<?php echo _t('현재 배치'); ?>',
    'no_players': '<?php echo _t('선수가 없습니다'); ?>',
    'no_assignable_players': '<?php echo _t('배치 가능한 선수가 없습니다'); ?>',
    
    // 확인 메시지
    'confirm_clear_position': '<?php echo _t('이 포지션을 비우시겠습니까?'); ?>',
    
    // 에러 메시지
    'error_squad_creation': '<?php echo _t('스쿼드 생성 중 오류가 발생했습니다'); ?>',
    'error_player_assignment': '<?php echo _t('선수 배치 중 오류가 발생했습니다'); ?>',
    'error_clear_position': '<?php echo _t('포지션 비우기 중 오류가 발생했습니다'); ?>'
};
</script>





<script>

	// 전역 변수
	var squadConfig = <?php echo json_encode($squad_config); ?>;
	var players = <?php echo json_encode($players); ?>;
	var squadData = <?php echo json_encode($squads); ?>;
	var positionData = <?php echo json_encode($positions); ?>;
	var scheduleId = <?php echo $schedule_id; ?>;
	var teamId = <?php echo $team_id; ?>;

	// positionData 배열 → 객체 변환
	if (Array.isArray(positionData)) {
		var temp = {};
		positionData.forEach(function(item, index) {
			if (item && typeof item === 'object') temp[index] = item;
		});
		positionData = temp;
	}

	var currentTeam = '<?php echo array_keys($squad_config['teams'])[0]; ?>';
	var currentQuarter = 1;
	var quarterCount = (function(){
		var teams = squadData[currentTeam] || {};
		var existing = Object.keys(teams).map(function(q){ return parseInt(q,10); });
		var maxExisting = existing.length ? Math.max.apply(null, existing) : 0;
		return Math.max(squadConfig.default_quarters, maxExisting);
	})();

	function getCurrentFormation() {
		return squadData[currentTeam] && squadData[currentTeam][currentQuarter] 
			? squadData[currentTeam][currentQuarter].formation 
			: '4-3-3';
	}

	function setCurrentFormation(formation) {
		if(!squadData[currentTeam]) squadData[currentTeam] = {};
		if(!squadData[currentTeam][currentQuarter]) {
			squadData[currentTeam][currentQuarter] = { sq_id: null, formation: formation };
		} else {
			squadData[currentTeam][currentQuarter].formation = formation;
		}
	}

	function updatePlayerQuarters() {
		if (typeof updatePlayerQuarterInfo === 'function') updatePlayerQuarterInfo();
	}

	function showError(message) {
		alert(message || '<?php echo _t('오류가 발생했습니다.'); ?>');
	}

	// 초기화
	$(document).ready(function() {
		try {
			if (typeof formations === 'undefined' || typeof initSquadManager !== 'function') {
				throw new Error();
			}
			initSquadManager(players, formations, squadData, positionData);
			initQuarterTabs();
			loadSquadData();
		} catch(error) {
			initQuarterTabs();
			$('#field_container').html('<div style="text-align:center; padding:50px; color:red;"><?php echo _t('포메이션 시스템 로드에 실패했습니다. 페이지를 새로고침해주세요.'); ?></div>');
		}
	});

	function initQuarterTabs() {
		var container = $('#quarter_tabs_container').empty();
		for(var i = 1; i <= quarterCount; i++) {
			var label = squadConfig.quarter_labels && squadConfig.quarter_labels[i] 
				? squadConfig.quarter_labels[i] 
				: i + ' <?php echo _t('쿼터'); ?>';
			
			var btn = $('<button type="button" class="quarter-btn" data-quarter="' + i + '">' + label + '</button>');
			if(i === currentQuarter) btn.addClass('active');
			btn.on('click', function() { changeQuarter($(this).data('quarter')); });
			container.append(btn);
		}
	}

	function changeTeam(team) {
		currentTeam = team;
		$('#current_team').val(team);
		$('.team-tab').removeClass('active');
		$('.team-tab[data-team="' + team + '"]').addClass('active');
		loadSquadData();
	}

	function changeQuarter(quarter) {
		currentQuarter = parseInt(quarter);
		$('#current_quarter').val(currentQuarter);
		$('.quarter-btn').removeClass('active');
		$('.quarter-btn[data-quarter="' + currentQuarter + '"]').addClass('active');
		loadSquadData();
	}

	function addQuarter() {
		if(quarterCount >= squadConfig.max_quarters) {
			showError('<?php echo _t('최대'); ?> ' + squadConfig.max_quarters + '<?php echo _t('쿼터까지 가능합니다.'); ?>');
			return;
		}
		quarterCount++;
		$('#quarter_count').text(quarterCount);
		initQuarterTabs();
	}

	function removeQuarter() {
		if(quarterCount <= 1) {
			showError('<?php echo _t('최소 1쿼터는 필요합니다.'); ?>');
			return;
		}
		quarterCount--;
		$('#quarter_count').text(quarterCount);
		if(currentQuarter > quarterCount) {
			currentQuarter = quarterCount;
			$('#current_quarter').val(currentQuarter);
		}
		initQuarterTabs();
	}

	function changeFormation() {
		var newFormation = $('#formation').val();
		var currentFormation = getCurrentFormation();
		
		if(newFormation === currentFormation) return;
		
		if(hasPlayersInCurrentSquad()) {
			var message = '<?php echo _t('포메이션을 변경하면 현재 배치된 모든 선수가 초기화됩니다.'); ?>\n\n<?php echo _t('계속하시겠습니까?'); ?>';
			if(confirm(message)) {
				executeFormationChangeWithReset(newFormation);
			} else {
				$('#formation').val(currentFormation).niceSelect('update');
			}
		} else {
			executeFormationChange(newFormation);
		}
	}

	function hasPlayersInCurrentSquad() {
		return squadManager.currentSquadId && positionData[squadManager.currentSquadId] && 
			   Object.keys(positionData[squadManager.currentSquadId]).length > 0;
	}

	function executeFormationChangeWithReset(newFormation) {
		if(!squadManager.currentSquadId) {
			executeFormationChange(newFormation);
			return;
		}
		
		$.ajax({
			url: './update_squad.php',
			type: 'POST',
			data: { w: 'update_formation_with_reset', sq_id: squadManager.currentSquadId, formation: newFormation },
			dataType: 'json',
			beforeSend: showLoading,
			success: function(response) {
				if(response.status) {
					if(positionData[squadManager.currentSquadId]) delete positionData[squadManager.currentSquadId];
					executeFormationChange(newFormation);
					updatePlayerQuarters();
				} else {
					showError(response.msg || '<?php echo _t('포메이션 변경 중 오류가 발생했습니다.'); ?>');
					$('#formation').val(getCurrentFormation()).niceSelect('update');
				}
			},
			error: function() {
				showError('<?php echo _t('포메이션 변경 중 오류가 발생했습니다.'); ?>');
				$('#formation').val(getCurrentFormation()).niceSelect('update');
			},
			complete: hideLoading
		});
	}

	function executeFormationChange(newFormation) {
		setCurrentFormation(newFormation);
		renderField();
	}

	function loadSquadData() {
		if(squadData[currentTeam] && squadData[currentTeam][currentQuarter]) {
			var squad = squadData[currentTeam][currentQuarter];
			if (typeof squadManager !== 'undefined') squadManager.currentSquadId = squad.sq_id;
			$('#formation').val(squad.formation).niceSelect('update');
		} else {
			if (typeof squadManager !== 'undefined') squadManager.currentSquadId = null;
			var defaultFormation = '4-3-3';
			$('#formation').val(defaultFormation).niceSelect('update');
			setCurrentFormation(defaultFormation);
		}
		renderField();
		updatePlayerQuarters();
	}

	function renderField() {
		var container = $('#field_container');
		
		if (typeof formations === 'undefined' || typeof renderFormationField !== 'function') {
			container.html('<div style="text-align:center; padding:50px;"><?php echo _t('포메이션을 로드하는 중입니다...'); ?></div>');
			return;
		}
		
		var currentSquadId = squadData[currentTeam] && squadData[currentTeam][currentQuarter] 
			? squadData[currentTeam][currentQuarter].sq_id : null;
		var currentPositions = currentSquadId && positionData[currentSquadId] ? positionData[currentSquadId] : {};
		
		try {
			renderFormationField(getCurrentFormation(), container, currentPositions);
		} catch(error) {
			container.html('<div style="text-align:center; padding:50px; color:red;"><?php echo _t('필드 렌더링 중 오류가 발생했습니다.'); ?></div>');
		}
	}

	function resetCurrentQuarter() {
		if (!confirm('<?php echo _t('현재 쿼터의 모든 선수 배치를 초기화하시겠습니까?'); ?>') || !squadManager.currentSquadId) {
			if(!squadManager.currentSquadId) showError('<?php echo _t('초기화할 스쿼드가 없습니다.'); ?>');
			return;
		}
		
		$.ajax({
			url: './update_squad.php',
			type: 'POST',
			data: { w: 'reset_quarter', sq_id: squadManager.currentSquadId },
			dataType: 'json',
			beforeSend: showLoading,
			success: function(response) {
				if (response.status) {
					if (positionData[squadManager.currentSquadId]) delete positionData[squadManager.currentSquadId];
					renderField();
					updatePlayerQuarters();
					alert('<?php echo _t('현재 쿼터가 초기화되었습니다.'); ?>');
				} else {
					showError(response.msg || '<?php echo _t('쿼터 초기화 중 오류가 발생했습니다.'); ?>');
				}
			},
			error: function() { showError('<?php echo _t('쿼터 초기화 중 오류가 발생했습니다.'); ?>'); },
			complete: hideLoading
		});
	}

	function resetAllSquads() {
		if (!confirm('<?php echo _t('모든 쿼터의 스쿼드를 초기화하시겠습니까?'); ?>\n\n<?php echo _t('이 작업은 되돌릴 수 없습니다.'); ?>')) return;
		
		$.ajax({
			url: './update_squad.php',
			type: 'POST',
			data: { w: 'reset_all_squads', ts_id: $('#ts_id').val(), sq_type: currentTeam },
			dataType: 'json',
			beforeSend: showLoading,
			success: function(response) {
				if (response.status) {
					squadData = {};
					positionData = {};
					squadManager.currentSquadId = null;
					renderField();
					updatePlayerQuarters();
					alert('<?php echo _t('모든 스쿼드가 초기화되었습니다.'); ?>');
				} else {
					showError(response.msg || '<?php echo _t('전체 초기화 중 오류가 발생했습니다.'); ?>');
				}
			},
			error: function() { showError('<?php echo _t('전체 초기화 중 오류가 발생했습니다.'); ?>'); },
			complete: hideLoading
		});
	}

	function updatePositionDisplay(position, playerName, playerNumber) {
		var $position = $(`.position[data-position="${position}"]`);
		if ($position.length === 0) return;
		
		$position.find('.position-player').html(`
			<div class="player-display">
				<span class="player-number">${playerNumber || '-'}</span>
				<span class="player-name">${playerName}</span>
			</div>
		`);
		$position.addClass('filled').find('.position-label').text(position);
	}

	function clearPositionDisplay(position) {
		var $position = $(`.position[data-position="${position}"]`);
		if ($position.length === 0) return;
		$position.find('.position-player').empty().end().removeClass('filled').find('.position-label').text(position);
	}

	function updatePlayerQuarterInfo() {
		if (!squadManager.players || squadManager.players.length === 0) return;
		
		squadManager.players.forEach(function(player) {
			var $container = $('#player_quarters_' + player.sj_id);
			var $playerItem = $('.player-item[data-sjid="' + player.sj_id + '"]');
			
			if ($container.length === 0) return;
			
			$container.empty();
			$playerItem.removeClass('current empty');
			
			var quarterInfo = getPlayerQuarterInfo(player.sj_id);
			if (quarterInfo.length > 0) {
				quarterInfo.forEach(function(info) {
					var $badge = $('<span class="quarter-badge">').text(info.label);
					if (info.isCurrent) {
						$badge.addClass('current');
						$playerItem.addClass('current');
					}
					$container.append($badge);
				});
			} else {
				$container.append('<span class="quarter-badge empty"><?php echo _t('미배치'); ?></span>');
				$playerItem.addClass('empty');
			}
		});
	}

	function getPlayerQuarterInfo(playerId) {
		var info = [];
		var currentSquadId = squadData[currentTeam] && squadData[currentTeam][currentQuarter] 
			? squadData[currentTeam][currentQuarter].sq_id : null;
		
		if(currentSquadId && positionData[currentSquadId]) {
			for (var position in positionData[currentSquadId]) {
				if (positionData[currentSquadId][position] == playerId) {
					var teamLabel = currentTeam === 'our' ? '' : currentTeam + '<?php echo _t('팀'); ?> ';
					info.push({
						label: teamLabel + currentQuarter + '<?php echo _t('쿼터'); ?>-' + position,
						isCurrent: true
					});
				}
			}
		}
		return info;
	}

	function showLoading() {
		squadManager.isLoading = true;
		if (typeof loadingStart === 'function') loadingStart();
	}

	function hideLoading() {
		squadManager.isLoading = false;
		if (typeof loadingEnd === 'function') loadingEnd();
	}

	function fn_modify() {
		location.href = './form.php?ts_id=<?php echo $schedule_id; ?>&te_id=<?php echo $team_id; ?>';
	}

	function startMatch() {
		var hasPlayers = squadManager.currentSquadId && positionData[squadManager.currentSquadId] && 
			Object.keys(positionData[squadManager.currentSquadId]).length > 0;
		
		if(!hasPlayers) {
			showError('<?php echo _t('현재 쿼터에 선수를 배치한 후 경기를 시작할 수 있습니다.'); ?>');
			return;
		}
		
		if(confirm('<?php echo _t('경기를 시작하시겠습니까?'); ?>')) {
			location.href = './match_record.php?ts_id=<?php echo $schedule_id; ?>&te_id=<?php echo $team_id; ?>&quarter=' + currentQuarter;
		}
	}


</script>



<script src="//developers.kakao.com/sdk/js/kakao.min.js" charset="utf-8"></script>
<script src="<?php echo G5_JS_URL; ?>/kakaolink.js" charset="utf-8"></script>
<script type='text/javascript'>
Kakao.init("bbff4333946e27c2b17ba7ee2533d7cf");



// 현재 언어 확인
var currentLanguage = '<?php echo isset($_SESSION["user_lang"]) ? $_SESSION["user_lang"] : "ko_KR"; ?>';

function isEnglishMode() {
    return currentLanguage.startsWith('en');
}

// 실시간 카톡 데이터 생성 함수 (수정됨)
function generateKakaoContent() {
    var isEn = isEnglishMode();
    
    if (isEn) {
        // 영어 모드: DOM에서 번역된 텍스트 추출
        var title = $('.schedule h4').text() + " Squad";
        
        // 날짜 정보
        var dateText = $('.schedule p').first().text();
        var content = dateText + "\n";
        
        // 장소 정보
        var location = $('.location').text();
        content += location;
        
        // 상대팀 정보
        var vsTeam = $('.schedule b').text();
        if (vsTeam) {
            content += "\n" + vsTeam;
        }
        
        // 참석 인원
        content += "\nAttending: <?php echo count($players); ?> players";
        
        // 스쿼드 정보 (영어 DOM에서 추출)
        if(squadData && Object.keys(squadData).length > 0) {
            var playerMap = {};
            players.forEach(function(p) {
                playerMap[p.sj_id] = p.number + ' ' + p.name;
            });
            
            for(var team in squadData) {
                <?php if($squad_config['mode'] == 'self'): ?>
                var teamName = (team == 'A') ? 'Team A' : 'Team B';
                content += "\n\n=== " + teamName + " ===";
                <?php endif; ?>
                
                var quarters = squadData[team];
                for(var quarter in quarters) {
                    var squad = quarters[quarter];
                    
                    // 쿼터 제목 (영어)
                    var quarterLabel = "Quarter " + quarter;
                    content += "\n\n【" + quarterLabel + " - " + squad.formation + "】";
                    
                    // 포지션별 선수
                    if(positionData[squad.sq_id]) {
                        var squadPositions = positionData[squad.sq_id];
                        var positionOrder = ['GK', 'LB', 'LCB', 'CB', 'RCB', 'RB', 'LWB', 'RWB', 'DM', 'LDM', 'RDM', 'LCM', 'CM', 'RCM', 'CAM', 'LAM', 'RAM', 'LM', 'RM', 'LW', 'RW', 'LST', 'ST', 'RST', 'F9', 'SS'];
                        
                        var sortedPositions = Object.keys(squadPositions).sort(function(a, b) {
                            var indexA = positionOrder.indexOf(a);
                            var indexB = positionOrder.indexOf(b);
                            if(indexA === -1) indexA = 999;
                            if(indexB === -1) indexB = 999;
                            return indexA - indexB;
                        });
                        
                        sortedPositions.forEach(function(pos) {
                            var playerId = squadPositions[pos];
                            if(playerMap[playerId]) {
                                content += "\n" + pos + " : " + playerMap[playerId];
                            }
                        });
                    } else {
                        content += "\nNo player assigned";
                    }
                }
            }
        } else {
            content += "\n\nSquad not configured";
        }
        
    } else {
        // 한국어 모드: 기존 PHP 방식 유지
        var title = "<?php echo $view[$prefix.'name']; ?> 스쿼드";
        var content = "<?php echo strip_tags($date); ?>\n";
        content += "<?php echo $view['ts_location']; ?>";
        
        <?php if($view[$prefix.'match_team']): ?>
        content += "\nVS <?php echo $view[$prefix.'match_team']; ?>";
        <?php endif; ?>
        
        content += "\n참석인원 : <?php echo count($players); ?>명";
        
        // 기존 한국어 스쿼드 정보 코드...
        if(squadData && Object.keys(squadData).length > 0) {
            var playerMap = {};
            players.forEach(function(p) {
                playerMap[p.sj_id] = p.number + ' ' + p.name;
            });
            
            for(var team in squadData) {
                <?php if($squad_config['mode'] == 'self'): ?>
                var teamName = (team == 'A') ? 'A팀' : 'B팀';
                content += "\n\n=== " + teamName + " ===";
                <?php endif; ?>
                
                var quarters = squadData[team];
                for(var quarter in quarters) {
                    var squad = quarters[quarter];
                    
                    <?php if($squad_config['quarter_labels']): ?>
                    var quarterLabels = <?php echo json_encode($squad_config['quarter_labels']); ?>;
                    var quarterLabel = quarterLabels[quarter] || (quarter + "쿼터");
                    <?php else: ?>
                    var quarterLabel = quarter + "쿼터";
                    <?php endif; ?>
                    
                    content += "\n\n【" + quarterLabel + " - " + squad.formation + "】";
                    
                    if(positionData[squad.sq_id]) {
                        var squadPositions = positionData[squad.sq_id];
                        var positionOrder = ['GK', 'LB', 'LCB', 'CB', 'RCB', 'RB', 'LWB', 'RWB', 'DM', 'LDM', 'RDM', 'LCM', 'CM', 'RCM', 'CAM', 'LAM', 'RAM', 'LM', 'RM', 'LW', 'RW', 'LST', 'ST', 'RST', 'F9', 'SS'];
                        
                        var sortedPositions = Object.keys(squadPositions).sort(function(a, b) {
                            var indexA = positionOrder.indexOf(a);
                            var indexB = positionOrder.indexOf(b);
                            if(indexA === -1) indexA = 999;
                            if(indexB === -1) indexB = 999;
                            return indexA - indexB;
                        });
                        
                        sortedPositions.forEach(function(pos) {
                            var playerId = squadPositions[pos];
                            if(playerMap[playerId]) {
                                content += "\n" + pos + " : " + playerMap[playerId];
                            }
                        });
                    } else {
                        content += "\n선수 배치 없음";
                    }
                }
            }
        } else {
            content += "\n\n스쿼드 미구성";
        }
    }
    
    return {
        title: title,
        description: content,
        url: "<?php echo G5_URL; ?>/page/team_schedule/match_record.php?ts_id=<?php echo $schedule_id; ?>&te_id=<?php echo $team_id; ?>"
    };
}

// 카톡 공유 함수 (변경 없음)
function Kakao_sendSquad() {
    var kakaoData = generateKakaoContent();
    
    Kakao.Link.sendDefault({
        objectType: 'text',
        text: kakaoData.title + '\n\n' + kakaoData.description,
        link: {
            webUrl: kakaoData.url,
            mobileWebUrl: kakaoData.url
        },
        buttonTitle: isEnglishMode() ? "View Details" : "자세히 보기"
    });
}

</script>

<?php
include_once(G5_THEME_MOBILE_PATH.'/tail.php');
?>



