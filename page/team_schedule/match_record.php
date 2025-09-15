<?php 
include_once "./_common.php";
include_once "./setting.php";
include_once "../team/team.common.php";

if($error_msg) alert($error_msg, G5_URL);
$code3 = "match_record";
$g5['title'] = "경기 기록";

$schedule_id = $_GET['ts_id'];
$team_id = $_GET['te_id'];
$quarter = (int)$_GET['quarter'] ?: 1;

include_once(G5_THEME_MOBILE_PATH.'/head.php');

// 권한 체크
if(!$is_member){
    $current_url = urlencode($_SERVER['REQUEST_URI']);
    goto_url(G5_BBS_URL . "/login.php?url=" . $current_url);
}

// 일정 데이터 조회
$sql = "SELECT * FROM {$table_name} WHERE {$key_column} = '{$schedule_id}'";
$view = sql_fetch($sql);

if (!$view) {
    alert(_t("일정 정보를 찾을 수 없습니다."));
}

// 날짜 형식 생성
$date = "";
if($view[$prefix.'start_date'] == $view[$prefix.'end_date']){
    $date .= $view[$prefix.'start_date'] . " (".get_yoil($view[$prefix.'start_date']).") <span>" . $view[$prefix.'start_time'] . "~" . $view[$prefix.'end_time']."</span>";
} else {
    $date .= $view[$prefix.'start_date'] . " ~ " . $view[$prefix.'end_date'];
}

// 상대팀명 확인
if(!$view['ts_match_team']) {
    alert(_t("상대팀명이 없어 경기를 시작할 수 없습니다."));
}

// 현재 쿼터 스쿼드 조회
$sql = "SELECT * FROM sweb_team_schedule_squad WHERE ts_id = '{$schedule_id}' AND sq_quarter = '{$quarter}'";
$squad = sql_fetch($sql);

if(!$squad) {
    alert(_t("해당 쿼터의 스쿼드가 없습니다."));
}


// 경기 기록 조회
$sql = "SELECT * FROM sweb_team_schedule_match WHERE ts_id = '{$schedule_id}' AND sm_quarter = '{$quarter}'";
$match = sql_fetch($sql);

$our_score = $match ? $match['sm_our_score'] : 0;
$opponent_score = $match ? $match['sm_opponent_score'] : 0;
$match_status = $match ? $match['sm_status'] : 'ready';




// 현재 쿼터 라인업 조회 (이제 $match를 사용할 수 있음)
$lineup = array();
if($squad) {

	
	// 초기 스쿼드 가져오기
	$sql = "SELECT DISTINCT p.sp_position, p.sj_id, j.sj_name, j.sj_is_guest, tj.tj_name, tj.tj_number 
			FROM sweb_team_schedule_position p
			LEFT JOIN sweb_team_schedule_join j ON p.sj_id = j.sj_id AND j.ts_id = '{$schedule_id}'
			LEFT JOIN sweb_team_join tj ON j.mb_id = tj.mb_id AND tj.te_id = '{$team_id}'
			WHERE p.sq_id = '{$squad['sq_id']}'";
	$result = sql_query($sql);
	while($row = sql_fetch_array($result)){
		// 🔥 동일한 선수명 결정 로직 적용
		$player_name = '';
		if(!empty($row['sj_is_guest']) && $row['sj_is_guest'] == '1') {
			$player_name = $row['sj_name'] ?: '게스트';
		} else {
			$player_name = $row['tj_name'] ?: $row['sj_name'] ?: '알 수 없음';
		}
		
		$lineup[$row['sp_position']] = array(
			'sj_id' => $row['sj_id'],
			'name' => $player_name,
			'number' => $row['tj_number']
		);
	}



    
    // 🔥 교체 기록 적용 (이제 $match 사용 가능)
    if($match) {
        $sql = "SELECT r_out.sj_id as out_id, r_out.sr_related_player as in_id,
                       j.sj_name, tj.tj_name, tj.tj_number
                FROM sweb_team_schedule_record r_out
                LEFT JOIN sweb_team_schedule_join j ON r_out.sr_related_player = j.sj_id
                LEFT JOIN sweb_team_join tj ON j.mb_id = tj.mb_id
                WHERE r_out.sm_id = '{$match['sm_id']}' AND r_out.sr_type = 'sub_out'
                ORDER BY r_out.sr_id ASC";
        
        $result = sql_query($sql);
        while($row = sql_fetch_array($result)) {
            // 라인업에서 OUT 선수를 찾아서 IN 선수로 교체
            foreach($lineup as $pos => $player) {
                if($player['sj_id'] == $row['out_id']) {
                    $lineup[$pos] = array(
                        'sj_id' => $row['in_id'],
                        'name' => ($row['tj_name'] ? $row['tj_name'] : $row['sj_name']),
                        'number' => $row['tj_number']
                    );
                    break;
                }
            }
        }
    }
}



// 벤치 선수 (참석했지만 현재 쿼터에 배치되지 않은 선수)
$bench = array();
$lineup_ids = array_column($lineup, 'sj_id');
$sql = "SELECT j.sj_id, j.sj_name, tj.tj_name, tj.tj_number
        FROM sweb_team_schedule_join j
        LEFT JOIN sweb_team_join tj ON j.mb_id = tj.mb_id AND tj.te_id = '{$team_id}'
        WHERE j.ts_id = '{$schedule_id}' AND (j.sj_status = '1' OR j.sj_is_guest = '1')";
if(!empty($lineup_ids)) {
    $sql .= " AND j.sj_id NOT IN (" . implode(',', $lineup_ids) . ")";
}
$result = sql_query($sql);
while($row = sql_fetch_array($result)){
    $bench[] = array(
        'sj_id' => $row['sj_id'],
        'name' => ($row['tj_name'] ? $row['tj_name'] : $row['sj_name']),
        'number' => $row['tj_number']
    );
}



// 현재 일정의 설정된 쿼터 목록 조회
$sql = "SELECT DISTINCT sq_quarter FROM sweb_team_schedule_squad WHERE ts_id = '{$schedule_id}' ORDER BY sq_quarter ASC";
$result = sql_query($sql);
$available_quarters = array();
while($row = sql_fetch_array($result)) {
    $available_quarters[] = $row['sq_quarter'];
}


$substitution_in_players = array();
if($match) {
    $sql = "SELECT DISTINCT sr_related_player as player_id
            FROM sweb_team_schedule_record 
            WHERE sm_id = '{$match['sm_id']}' AND sr_type = 'sub_out'";
    $result = sql_query($sql);
    while($row = sql_fetch_array($result)) {
        $substitution_in_players[] = $row['player_id'];
    }
}

// 🔥 교체 아웃 리스트 조회 추가
$substitution_records = array();
if($match) {
	$sql = "SELECT DISTINCT
		r1.sj_id as out_player_id,
		r1.sr_minute,
		r1.sr_related_player as in_player_id,
		j1.sj_name as out_name, 
		j1.sj_is_guest as out_is_guest,
		tj1.tj_name as out_tj_name, 
		tj1.tj_number as out_number,
		j2.sj_name as in_name, 
		j2.sj_is_guest as in_is_guest,
		tj2.tj_name as in_tj_name, 
		tj2.tj_number as in_number,
		'Unknown' as position
	FROM sweb_team_schedule_record r1
	LEFT JOIN sweb_team_schedule_join j1 ON r1.sj_id = j1.sj_id AND j1.ts_id = '{$schedule_id}'
	LEFT JOIN sweb_team_join tj1 ON j1.mb_id = tj1.mb_id AND tj1.te_id = '{$team_id}'
	LEFT JOIN sweb_team_schedule_join j2 ON r1.sr_related_player = j2.sj_id AND j2.ts_id = '{$schedule_id}'
	LEFT JOIN sweb_team_join tj2 ON j2.mb_id = tj2.mb_id AND tj2.te_id = '{$team_id}'
	WHERE r1.sm_id = '{$match['sm_id']}' 
		AND r1.sr_type = 'sub_out'
	GROUP BY r1.sr_id, r1.sj_id, r1.sr_related_player
	ORDER BY r1.sr_id ASC";
    
    $result = sql_query($sql);
    while($row = sql_fetch_array($result)) {
        $substitution_records[] = array(
            'sj_id' => $row['out_player_id'],
            'name' => ($row['out_tj_name'] ? $row['out_tj_name'] : $row['out_name']),
            'number' => $row['out_number'],
            'position' => $row['position'],
            'substituted_with' => array(
                'sj_id' => $row['in_player_id'],
                'name' => ($row['in_tj_name'] ? $row['in_tj_name'] : $row['in_name']),
                'number' => $row['in_number']
            ),
            'time' => $row['sr_minute'] ? $row['sr_minute'] . '\'' : '0\''
        );
    }
}


?>

<link rel="stylesheet" href="./style.css?ver=2">
<link rel="stylesheet" href="./style_match.css?ver=4">

<?php include "../team/tab.php"; ?>


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


<div class="match-record-container">


<?php if(count($available_quarters) >= 2): ?>
<div class="quarter-navigation">
    <div class="quarter-tabs">
        <?php foreach($available_quarters as $q): ?>
        <button class="quarter-btn <?php echo ($q == $quarter) ? 'active' : ''; ?>" 
                onclick="location.href='./match_record.php?ts_id=<?php echo $schedule_id; ?>&te_id=<?php echo $team_id; ?>&quarter=<?php echo $q; ?>'">
            <?php echo $q; ?><?php echo _t('쿼터'); ?>
        </button>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>


<!-- 스코어보드 -->
<div id="scboard">
    <div class="round">
		<div class="team-score out-team">
			<?php 
			// 우리팀 로고
			$our_team_logo = '';
			if($parent && $parent['file_rename']) {
				$f_path = G5_DATA_PATH . "/file/team/";
				$f_url = G5_DATA_URL . "/file/team/";
				$our_team_logo = $f_url . $parent['file_rename'];
			}
			?>
			<img src="<?php echo $our_team_logo ? $our_team_logo : '/img/symbol3.png'; ?>" alt="<?php echo $parent['te_name']; ?> 로고">
			<p><?php echo $parent['te_name']; ?></p>
		</div>
        <div class="info">
            <p>
                <span><?php echo $quarter; ?><?php echo _t('쿼터'); ?></span> 
                <span id="match_timer">
                    <?php 
                    if($match && $match['sm_start_time']) {
                        $start_time = strtotime($match['sm_start_time']);
                        $current_time = $match['sm_end_time'] ? strtotime($match['sm_end_time']) : time();
                        $elapsed = $current_time - $start_time;
                        echo sprintf('%02d:%02d', floor($elapsed/60), $elapsed%60);
                    } else {
                        echo '00:00';
                    }
                    ?>
                </span>
            </p>	
            <div class="score">
                <div>
                    <span class="score" id="our_score"><?php echo $our_score; ?></span>
                    <span>
                        <button onclick="addScore('our', 'plus')" class="score-btn plus">+</button>
                        <button onclick="addScore('our', 'minus')" class="score-btn minus">-</button>						
                    </span>
                </div>

                <small>-</small>

                <div>
                    <span class="score" id="opponent_score"><?php echo $opponent_score; ?></span>
                    <span>
                        <button onclick="addScore('opponent', 'plus')" class="score-btn plus">+</button>
                        <button onclick="addScore('opponent', 'minus')" class="score-btn minus">-</button>						
                    </span>				
                </div>
            </div>

            <div class="state">
                <span class="match-status" id="match_status_text">
                    <?php 
                    $status_text = array(
                        'ready' => _t('준비중'),
                        'playing' => _t('진행중'), 
                        'finished' => _t('종료')
                    );
                    echo $status_text[$match_status];
                    ?>
                </span>
                
                <div class="match-controls">
                    <?php if($match_status == 'ready'): ?>
                        <button onclick="updateMatchStatus('playing')" class="start-btn">
                            <span class="material-symbols-outlined">play_arrow</span>
                            <?php echo _t('시작'); ?>
                        </button>
                    <?php elseif($match_status == 'playing'): ?>
                        <button onclick="updateMatchStatus('finished')" class="end-btn">
                            <span class="material-symbols-outlined">stop</span>
                            <?php echo _t('종료'); ?>
                        </button>
                        <button onclick="updateMatchStatus('ready')" class="pause-btn">
                            <span class="material-symbols-outlined">pause</span>
                            <?php echo _t('정지'); ?>
                        </button>
                    <?php elseif($match_status == 'finished'): ?>
                        <button onclick="updateMatchStatus('playing')" class="resume-btn">
                            <span class="material-symbols-outlined">refresh</span>
                            <?php echo _t('재시작'); ?>
                        </button>
                        <button onclick="goToNextQuarter()" class="next-btn">
                            <span class="material-symbols-outlined">skip_next</span>
                            <?php echo _t('다음 쿼터'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
		<div class="team-score opponent-team">
			<?php 
			// 상대팀 로고 (현재는 기본 이미지, 향후 확장 가능)
			$opponent_logo = '/img/symbol3.png';
			
			// 향후 상대팀 로고 기능 확장 시 사용할 코드
			// if($view['ts_opponent_logo']) {
			//     $opponent_logo = $view['ts_opponent_logo'];
			// }
			?>
			<img src="<?php echo $opponent_logo; ?>" alt="<?php echo $view['ts_match_team']; ?> 로고">
			<p><?php echo $view['ts_match_team']; ?></p>
		</div>
    </div>
</div>



    <!-- 라인업 섹션 -->
    <div class="lineup-section">
        <h3>
			<?php echo _t('라인업'); ?>
			<span><?php echo $squad['sq_formation']; ?></span>
		</h3>
        <div class="soccer-field" id="field_container">
            <!-- 포메이션 렌더링 -->
        </div>
    </div>


	<!-- 교체 아웃 리스트 -->
	<div class="substituted-out-section" id="substituted_out_section" style="display: none;">
		<h3><?php echo _t('교체 아웃'); ?></h3>
		<div class="substituted-players" id="substituted_players_list">
			<!-- 동적 생성 -->
		</div>
	</div>

    <!-- 벤치 섹션 -->
    <?php if(!empty($bench)): ?>
    <div class="bench-section">
        <h3><?php echo _t('벤치'); ?></h3>
        <div class="bench-players">
            <?php foreach($bench as $player): ?>
            <div class="bench-player" data-sjid="<?php echo $player['sj_id']; ?>" onclick="selectPlayer(<?php echo $player['sj_id']; ?>, '<?php echo $player['name']; ?>')">
                <span class="number"><?php echo $player['number']; ?></span>
                <span class="name"><?php echo $player['name']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 최근 기록 -->
	<div class="recent-records">
		<div class="record-list" id="record_list">
			<!-- AJAX로 로드 -->
		</div>
	</div>

</div>

<!-- 선수 액션 모달 -->
<div id="playerActionModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="selected_player_name"></h3>
            <button type="button" class="modal-close" onclick="closeActionModal()">×</button>
        </div>
        <div class="modal-body">
			<div class="action-buttons">
				<button class="action-btn goal-btn" data-action="goal">
					<span class="material-symbols-outlined">sports_soccer</span>
					<?php echo _t('골'); ?>
				</button>
				<button class="action-btn assist-btn" data-action="assist">
					<span class="material-symbols-outlined">emoji_people</span>
					<?php echo _t('어시스트'); ?>
				</button>
				<button class="action-btn yellow-btn" data-action="yellow">
					<span class="material-symbols-outlined">warning</span>
					<?php echo _t('경고'); ?>
				</button>
				<button class="action-btn red-btn" data-action="red">
					<span class="material-symbols-outlined">block</span>
					<?php echo _t('퇴장'); ?>
				</button>
				<button class="action-btn sub-btn" data-action="sub_out">
					<span class="material-symbols-outlined">swap_horiz</span>
					<?php echo _t('교체 OUT'); ?>
				</button>
			</div>
        </div>
    </div>
</div>



<!-- 교체 선수 선택 모달 -->
<div id="substitutionModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php echo _t('교체할 선수 선택'); ?></h3>
            <span id="sub_out_player_name" style="color: #666;"></span>
            <button type="button" class="modal-close" onclick="closeSubstitutionModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="substitution-info">
                <p><strong><?php echo _t('OUT'); ?>:</strong> <span id="out_player_display"></span></p>
                <p><strong><?php echo _t('IN'); ?>:</strong> <?php echo _t('아래에서 선택하세요'); ?></p>
            </div>
            
            <div class="bench-players-list">
                <h4><?php echo _t('벤치 선수 목록'); ?></h4>
                <div id="substitution_bench_list">
                    <!-- 동적 생성 -->
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeSubstitutionModal()" class="btn-cancel"><?php echo _t('취소'); ?></button>
        </div>
    </div>
</div>



<form name="recordListForm" id="recordListForm" style="display:none;">
    <input type="hidden" name="ts_id" value="<?php echo $schedule_id; ?>">
    <input type="hidden" name="quarter" value="<?php echo $quarter; ?>">
    <input type="hidden" name="page" value="1">
    <input type="hidden" name="rows" value="10">
</form>




<!-- InfiniteScroll.js 라이브러리 로드 -->
<script src="<?php echo G5_URL; ?>/js/infiniteScroll.js"></script>

<!-- 포메이션 시스템 JavaScript -->
<script src="./squad_formations.js"></script>
<script>


// 전역 변수
var lineup = <?php echo json_encode($lineup);?>;
var bench = <?php echo json_encode($bench); ?>;
var substitutionInPlayers = <?php echo json_encode($substitution_in_players); ?>;

var scheduleId = <?php echo $schedule_id;?>;
var currentQuarter = <?php echo $quarter;?>;
var formation = '<?php echo $squad ? $squad['sq_formation'] : '4-3-3';?>';
var matchStatus = '<?php echo $match_status; ?>';
var matchStartTime = <?php echo $match && $match['sm_start_time'] ? strtotime($match['sm_start_time']) : 'null'; ?>;
var serverCurrentTime = <?php echo time(); ?>;
var pauseTotalSeconds = <?php echo $match && isset($match['sm_pause_total']) ? $match['sm_pause_total'] : 0; ?>;
var pauseStart = <?php echo $match && $match['sm_pause_start'] ? strtotime($match['sm_pause_start']) : 'null'; ?>;

var substitutionData = {
    outPlayerId: null,
    outPlayerName: null,
    outPlayerNumber: null
};


var substitutedOutPlayers = <?php echo json_encode($substitution_records); ?>;

console.log('기존 교체 기록 로드됨:', substitutedOutPlayers);


// 타이머 관련
var matchTimer = null;
var matchStartTimestamp = null;


	$(function(){
		initializeEvents();
		renderField();
		loadPlayerIcons();
		initializeTimer();
		initializeRecordList();
		
		// 🔥 교체 관련 초기화
		if(substitutionInPlayers) substitutionInPlayers.forEach(id => addPlayerIcon(id, 'sub_in'));
		if(substitutedOutPlayers && substitutedOutPlayers.length > 0) updateSubstitutedOutList();
	});


    // 이벤트 초기화
    function initializeEvents(){
        $(document).off('click.match').on('click.match', '.position, .bench-player, .action-btn', function(e){
            e.preventDefault();
            var $el = $(this);
            
            if ($el.hasClass('position')) {
                var pos = $el.data('position');
                if(lineup[pos]) selectPlayer(lineup[pos].sj_id, lineup[pos].name);
            }
            else if ($el.hasClass('bench-player')) {
                selectPlayer($el.data('sjid'), $el.find('.name').text());
            }
            else if ($el.hasClass('action-btn')) {
                recordAction($el.data('action'));
            }
        });

        // 모달 배경 클릭 시 닫기
        $(document).on('click', '#playerActionModal', function(e){
            if (e.target.id === 'playerActionModal') {
                closeActionModal();
            }
        });
    }

	function initializeSubstitutionList() {
		console.log('교체 리스트 초기화:', substitutedOutPlayers);
		
		if(substitutedOutPlayers && substitutedOutPlayers.length > 0) {
			// 중복 제거
			var uniqueSubstitutions = {};
			substitutedOutPlayers.forEach(function(sub) {
				uniqueSubstitutions[sub.sj_id] = sub;
			});
			
			substitutedOutPlayers = Object.values(uniqueSubstitutions);
			
			// 🔥 현재 라인업에 있는 교체 IN 선수만 필터링
			var validSubstitutions = substitutedOutPlayers.filter(function(sub) {
				if(!sub.substituted_with || !sub.substituted_with.sj_id) return false;
				
				var foundInLineup = false;
				for(var pos in lineup) {
					if(lineup[pos] && lineup[pos].sj_id == sub.substituted_with.sj_id) {
						foundInLineup = true;
						break;
					}
				}
				
				if(!foundInLineup) {
					console.log('⚠️ 교체 IN 선수가 현재 라인업에 없어서 제외:', sub.substituted_with);
				}
				
				return foundInLineup;
			});
			
			console.log('현재 라인업 기준 유효한 교체 기록:', validSubstitutions);
			
			// 유효한 교체만 아이콘 추가
			validSubstitutions.forEach(function(sub) {
				console.log('교체 IN 아이콘 추가:', sub.substituted_with.sj_id);
				addPlayerIcon(sub.substituted_with.sj_id, 'sub_in');
			});
			
			// 전체 교체 아웃 리스트는 그대로 표시
			updateSubstitutedOutList();
		}
	}

    // 기록 리스트 초기화
    function initializeRecordList(){
        var ok = InfiniteScroll.init({
            ajaxUrl: './ajax_match_records.php',
            targetContainer: '#record_item_area',
            loadingContainer: '#record_list',
            formSelector: '#recordListForm',
            itemsPerPage: 10,
            scrollThreshold: 300,
            throttleDelay: 300,
            resetPageOnSearch: true,
            fadeInAnimation: true,
            animationDelay: 30
        });
        
        if (!ok) loadMoreFallback(true);
    }

	// 타이머 초기화 (수정)
	function initializeTimer(){
		console.log('=== 타이머 초기화 디버깅 ===');
		console.log('상태:', matchStatus);
		console.log('시작시간:', matchStartTime);
		console.log('서버현재시간:', serverCurrentTime);
		console.log('총 일시정지:', pauseTotalSeconds);
		console.log('일시정지 시작:', pauseStart);
		
		if (matchStatus === 'playing' && matchStartTime) {
			// 진행중: 실제 경과 시간 계산
			var totalElapsed = serverCurrentTime - matchStartTime;
			var actualElapsed = totalElapsed - pauseTotalSeconds;
			
			console.log('총 경과시간:', totalElapsed, '초');
			console.log('실제 경과시간(일시정지 제외):', actualElapsed, '초');
			
			matchStartTimestamp = new Date().getTime() - (actualElapsed * 1000);
			console.log('계산된 클라이언트 시작시간:', new Date(matchStartTimestamp));
			
			startMatchTimer();
			
		} else if (matchStatus === 'ready' && pauseStart && matchStartTime) {
			// 🔥 수정: 일시정지 중 - pause_start 시점의 시간으로 고정
			var pausedElapsed = pauseStart - matchStartTime - pauseTotalSeconds;
			pausedElapsed = Math.max(0, pausedElapsed);
			
			console.log('일시정지된 시점 경과시간:', pausedElapsed, '초');
			displayTime(pausedElapsed);
		} else {
			console.log('타이머 시작 조건 불충족');
		}
	}

    // 필드 렌더링
    function renderField(){
        if (typeof renderFormationField === 'function') {
            var positionMap = {};
            $.each(lineup, function(pos, player){
                positionMap[pos] = player.sj_id;
            });
            renderFormationField(formation, $('#field_container'), positionMap);
        }
    }

    // 선수 아이콘 로드
    function loadPlayerIcons(){
        $.getJSON('./ajax_match_records.php', {
            ts_id: scheduleId,
            quarter: currentQuarter,
            get_player_records: true
        }, function(data){
            if (data.status && data.player_records) {
                $.each(data.player_records, function(playerId, records){
                    $.each(records, function(type, count){
                        for (var i = 0; i < count; i++) {
                            addPlayerIcon(playerId, type);
                        }
                    });
                });
            }
        });
    }



    // InfiniteScroll 폴백
    function loadMoreFallback(reset){
        window._fb = window._fb || { page:1, loading:false, hasMore:true };
        var state = window._fb;
        
        if (reset) {
            state.page = 1;
            state.hasMore = true;
            $('#record_list').empty();
        }
        
        if (state.loading || !state.hasMore) return;
        
        state.loading = true;
        $('#recordListForm input[name="page"]').val(state.page);
        
        $.ajax({
            url: './ajax_match_records.php',
            type: 'POST',
            data: new FormData($('#recordListForm')[0]),
            processData: false,
            contentType: false,
            success: function(html){
                if (state.page === 1) {
                    $('#record_list').html(html);
                } else {
                    $('#record_item_area').append(html);
                }
                
                if (html.indexOf('data_end') > -1) {
                    state.hasMore = false;
                }
                state.page++;
            },
            complete: function(){
                state.loading = false;
            }
        });
    }

    // ===== 타이머 관련 함수 =====
    function startMatchTimer(){
        if (matchTimer) clearInterval(matchTimer);
        
        matchTimer = setInterval(updateTimerDisplay, 1000);
        updateTimerDisplay();
    }

    function stopMatchTimer(){
        if (matchTimer) {
            clearInterval(matchTimer);
            matchTimer = null;
        }
    }

    function updateTimerDisplay(){
        if (!matchStartTimestamp) return;
        
        var elapsed = Math.floor((new Date().getTime() - matchStartTimestamp) / 1000);
        displayTime(Math.max(0, elapsed));
    }

    function displayTime(seconds){
        var minutes = Math.floor(seconds / 60);
        var secs = seconds % 60;
        
        $('#match_timer').text(
            String(minutes).padStart(2, '0') + ':' + 
            String(secs).padStart(2, '0')
        );
    }

    // ===== 선수 및 기록 관련 =====
    function selectPlayer(playerId, playerName){
        window._selectedPlayer = { id: playerId, name: playerName };
        $('#selected_player_name').text(playerName);
        $('#playerActionModal').fadeIn(300);
    }

	function recordAction(actionType){
		var player = window._selectedPlayer;
		if (!player) return;
		
		if(actionType === 'sub_out') {
			// 교체 OUT - 교체 모달 열기
			showSubstitutionModal(player.id, player.name);
			closeActionModal();
			return;
		}
		
		// 기존 코드 유지
		sendMatchAjax('add_record', { 
			sj_id: player.id, 
			action_type: actionType 
		}, function(response){
			if (response.status) {
				if (['goal','assist','yellow','red'].includes(actionType)) {
					addPlayerIcon(player.id, actionType);
				}
				if (actionType === 'goal') {
					$('#our_score').text(response.our_score);
				}
				closeActionModal();
				loadMoreFallback(true);
			} else {
				alert(response.msg);
			}
		});
	}













    // ===== 전역 함수들 =====
    window.updateMatchStatus = function(newStatus){
        // 시작할 때만 확인
        if (newStatus === 'playing') {
            var message = matchStatus === 'ready' && pauseStart ? 
                '<?php echo _t('경기를 재시작하시겠습니까?'); ?>' : 
                '<?php echo _t('쿼터를 시작하시겠습니까?'); ?>';
            
            if (!confirm(message)) return;
        }
        
        sendMatchAjax('update_match_status', { status: newStatus }, function(response){
            if (response.status) {
                updateMatchUI(response.match_data);
                console.log('경기 상태 변경:', newStatus);
            } else {
                alert(response.msg);
            }
        });
    };

	function updateMatchUI(matchData){
		var statusText = {
			'ready': '<?php echo _t('준비중'); ?>',
			'playing': '<?php echo _t('진행중'); ?>',
			'finished': '<?php echo _t('종료'); ?>'
		};
		
		$('#match_status_text').text(statusText[matchData.status]);
		
		// 🔥 수정: 타이머 제어 로직
		if (matchData.status === 'playing') {
			if (matchData.start_time_timestamp) {
				var pauseTotal = matchData.pause_total || 0;
				var serverElapsed = serverCurrentTime - matchData.start_time_timestamp;
				var actualElapsed = serverElapsed - pauseTotal;
				
				console.log('playing 상태 - 서버경과:', serverElapsed, '일시정지:', pauseTotal, '실제:', actualElapsed);
				
				matchStartTimestamp = new Date().getTime() - (actualElapsed * 1000);
				pauseTotalSeconds = pauseTotal;
			}
			startMatchTimer();
		} else if (matchData.status === 'ready' && matchData.pause_start) {
			// 🔥 수정: 일시정지 중 - 타이머 정지하고 고정된 시간 표시
			stopMatchTimer();
			
			// pause_start 시점까지의 경과시간을 서버에서 받아서 고정 표시
			var pauseStartTime = new Date(matchData.pause_start.replace(' ', 'T') + 'Z').getTime() / 1000;
			var frozenElapsed = pauseStartTime - matchData.start_time_timestamp - (matchData.pause_total || 0);
			frozenElapsed = Math.max(0, frozenElapsed);
			
			console.log('ready 상태 - 고정 시간:', frozenElapsed, '초');
			displayTime(frozenElapsed);
		} else {
			stopMatchTimer();
		}
		
		// 페이지 새로고침으로 버튼 상태 업데이트
		setTimeout(function(){ location.reload(); }, 1000);
	}

    window.addScore = function(team, action){
        sendMatchAjax('update_score', { team: team, action: action }, function(response){
            if (response.status) {
                $('#our_score').text(response.our_score);
                $('#opponent_score').text(response.opponent_score);
                loadMoreFallback(true);
            } else {
                alert(response.msg);
            }
        });
    };

    window.deleteRecord = function(recordId){
        if (!confirm('<?php echo _t('이 기록을 삭제하시겠습니까?');?>')) return;
        
        sendMatchAjax('delete_record', { sr_id: recordId }, function(response){
            if (response.status) {
                // 아이콘 제거
                var record = response.deleted_record;
                if (record && record.sj_id && record.sr_type) {
                    $('.position').each(function(){
                        var $position = $(this);
                        var position = $position.data('position');
                        
                        if (lineup[position] && lineup[position].sj_id == record.sj_id) {
                            var $icons = $position.find('.player-icons .icon[data-type="'+record.sr_type+'"]');
                            if ($icons.length) {
                                $icons.last().remove();
                                if (record.sr_type === 'red') {
                                    $position.removeClass('red-card');
                                }
                            }
                        }
                    });
                }
                
                // 점수 업데이트
                if (response.our_score !== undefined) {
                    $('#our_score').text(response.our_score);
                }
                
                loadMoreFallback(true);
            } else {
                alert(response.msg);
            }
        });
    };

    window.goToNextQuarter = function(){
        if (!confirm('<?php echo _t('다음 쿼터로 이동하시겠습니까?'); ?>')) return;
        
        var nextQuarter = currentQuarter + 1;
        var url = './match_record.php?ts_id=' + scheduleId + '&te_id=<?php echo $team_id; ?>&quarter=' + nextQuarter;
        location.href = url;
    };

    window.closeActionModal = function(){
        $('#playerActionModal').fadeOut(300);
        window._selectedPlayer = null;
    };





// 교체 모달 열기
function showSubstitutionModal(outPlayerId, outPlayerName) {
	console.log('교체 모달 열기:', outPlayerId, outPlayerName);
	
	// 퇴장당한 선수인지 확인
	var $playerPosition = $(`.position .position-player`).filter(function() {
		return $(this).closest('.position').hasClass('red-card');
	});
	
	if($playerPosition.length > 0) {
		var hasRedCard = false;
		$('.position').each(function() {
			var position = $(this).data('position');
			if(lineup[position] && lineup[position].sj_id == outPlayerId && $(this).hasClass('red-card')) {
				hasRedCard = true;
				return false;
			}
		});
		
		if(hasRedCard) {
			alert('<?php echo _t("퇴장당한 선수는 교체할 수 없습니다."); ?>');
			return;
		}
	}
	
	// 벤치 선수 목록 확인
	if(!bench || bench.length === 0) {
		alert('<?php echo _t("교체할 수 있는 벤치 선수가 없습니다."); ?>');
		return;
	}
	
	substitutionData.outPlayerId = outPlayerId;
	substitutionData.outPlayerName = outPlayerName;
	
	// 선수 번호 찾기
	var outPlayerNumber = '-';
	for(var pos in lineup) {
		if(lineup[pos] && lineup[pos].sj_id == outPlayerId) {
			outPlayerNumber = lineup[pos].number || '-';
			break;
		}
	}
	substitutionData.outPlayerNumber = outPlayerNumber;
	
	// 모달 정보 업데이트
	$('#out_player_display').text(`${outPlayerNumber} ${outPlayerName}`);
	
	// 벤치 선수 목록 생성
	generateSubstitutionBenchList();
	
	$('#substitutionModal').fadeIn(300);
}

// 교체용 벤치 선수 목록 생성
function generateSubstitutionBenchList() {
	var container = $('#substitution_bench_list');
	container.empty();
	
	if(!bench || bench.length === 0) {
		container.html('<p>' + '<?php echo _t("사용 가능한 벤치 선수가 없습니다."); ?>' + '</p>');
		return;
	}
	
	var html = '<ul class="substitution-player-list">';
	bench.forEach(function(player) {
		html += `
			<li class="substitution-player-item" onclick="executeSubstitution(${player.sj_id}, '${player.name}', '${player.number}')">
				<span class="player-number">${player.number || '-'}</span>
				<span class="player-name">${player.name}</span>
			</li>
		`;
	});
	html += '</ul>';
	
	container.html(html);
}



function updateSubstitutionUIManual(inPlayerId, inPlayerName, inPlayerNumber) {
    console.log('수동 UI 업데이트 시작');
    
    // 라인업에서 OUT 선수의 포지션 찾기
    var outPosition = null;
    for(var pos in lineup) {
        if(lineup[pos] && lineup[pos].sj_id == substitutionData.outPlayerId) {
            outPosition = pos;
            break;
        }
    }
    
    if(outPosition) {
        console.log('포지션 발견:', outPosition);
        
        // 라인업 데이터 업데이트
        lineup[outPosition] = {
            sj_id: inPlayerId,
            name: inPlayerName,
            number: inPlayerNumber
        };
        
        // 포지션 표시 업데이트
        updatePositionDisplay(outPosition, inPlayerName, inPlayerNumber);
        
        // 🔥 교체 IN 아이콘 추가
        addPlayerIcon(inPlayerId, 'sub_in');
        
        console.log('라인업 업데이트 완료');
    }
    
    // 벤치 목록 업데이트
    updateBenchListManual(inPlayerId);
    
    // 🔥 교체 아웃 리스트에 실시간 추가
    if(typeof substitutedOutPlayers === 'undefined') {
        window.substitutedOutPlayers = [];
    }
    
    // 현재 시간 계산
    var currentTime = getCurrentMatchTime();
    
    // 교체 아웃 선수 데이터 생성
    var substitutionData_copy = {
        sj_id: substitutionData.outPlayerId,
        name: substitutionData.outPlayerName,
        number: substitutionData.outPlayerNumber,
        position: outPosition || 'Unknown',
        substituted_with: {
            sj_id: inPlayerId,
            name: inPlayerName,
            number: inPlayerNumber
        },
        time: currentTime
    };
    
    // 교체 아웃 리스트에 추가
    substitutedOutPlayers.push(substitutionData_copy);
    console.log('교체 아웃 선수 추가:', substitutionData_copy);
    
    // 🔥 교체 아웃 리스트 실시간 업데이트
    updateSubstitutedOutList();
}



// 현재 경기 시간 가져오기
function getCurrentMatchTime() {
    if(!matchStartTimestamp) return '0\'';
    
    var elapsed = Math.floor((new Date().getTime() - matchStartTimestamp) / 1000);
    var minutes = Math.floor(elapsed / 60);
    return minutes + '\'';
}

// 교체 아웃 선수 추가
function addSubstitutedOutPlayer(playerData) {
    if(typeof substitutedOutPlayers === 'undefined') {
        window.substitutedOutPlayers = [];
    }
    substitutedOutPlayers.push(playerData);
    console.log('교체 아웃 선수 추가:', playerData);
}

// 교체 아웃 리스트 업데이트
function updateSubstitutedOutList() {
    console.log('교체 아웃 리스트 업데이트:', substitutedOutPlayers);
    
    var container = $('#substituted_players_list');
    var section = $('#substituted_out_section');
    
    if(!substitutedOutPlayers || substitutedOutPlayers.length === 0) {
        section.hide();
        return;
    }
    
    section.show();
    container.empty();
    
    substitutedOutPlayers.forEach(function(player) {
        var playerDiv = $(`
            <div class="substituted-player">
                <div class="player-info">
                    <span class="player-number">${player.number || '-'}</span>
                    <span class="player-name">${player.name}</span>
                    <span class="position-label">(${player.position})</span>
                </div>
                <div class="substitution-info">
                    <span class="substitution-arrow">OUT</span> ${player.time}
                    <br>
                    <span class="substitution-arrow">↔</span> ${player.substituted_with.number || '-'} ${player.substituted_with.name}
                </div>
            </div>
        `);
        container.append(playerDiv);
    });
    
    console.log('교체 아웃 리스트 업데이트 완료');
}

function updateBenchListManual(inPlayerId) {
	console.log('벤치 목록 업데이트');
	
	// 기존 벤치에서 IN 선수 제거
	bench = bench.filter(function(player) {
		return player.sj_id != inPlayerId;
	});
	
	// OUT 선수를 벤치에 추가
	bench.push({
		sj_id: substitutionData.outPlayerId,
		name: substitutionData.outPlayerName,
		number: substitutionData.outPlayerNumber
	});
	
	console.log('업데이트된 벤치:', bench);
	
	// 벤치 섹션 다시 렌더링
	renderBenchSection();
}

// 교체 후 UI 업데이트
function updateSubstitutionUI(subData) {
	console.log('교체 UI 업데이트:', subData);
	
	// 라인업에서 OUT 선수의 포지션 찾기
	var outPosition = null;
	for(var pos in lineup) {
		if(lineup[pos] && lineup[pos].sj_id == subData.out_player_id) {
			outPosition = pos;
			break;
		}
	}
	
	if(outPosition) {
		// 라인업 데이터 업데이트
		lineup[outPosition] = {
			sj_id: subData.in_player_id,
			name: subData.in_player_name,
			number: subData.in_player_number
		};
		
		// 포지션 표시 업데이트
		updatePositionDisplay(outPosition, subData.in_player_name, subData.in_player_number);
	}
	
	// 벤치 목록 업데이트
	updateBenchList(subData);
}

// 벤치 목록 업데이트
function updateBenchList(subData) {
	// 기존 벤치에서 IN 선수 제거, OUT 선수 추가
	bench = bench.filter(function(player) {
		return player.sj_id != subData.in_player_id;
	});
	
	bench.push({
		sj_id: subData.out_player_id,
		name: subData.out_player_name,
		number: subData.out_player_number
	});
	
	// 벤치 섹션 다시 렌더링
	renderBenchSection();
}

// 벤치 섹션 렌더링
function renderBenchSection() {
	var benchContainer = $('.bench-players');
	benchContainer.empty();
	
	if(bench.length === 0) {
		benchContainer.html('<p><?php echo _t("벤치에 선수가 없습니다."); ?></p>');
		return;
	}
	
	bench.forEach(function(player) {
		var playerDiv = $(`
			<div class="bench-player" data-sjid="${player.sj_id}" onclick="selectPlayer(${player.sj_id}, '${player.name}')">
				<span class="number">${player.number || '-'}</span>
				<span class="name">${player.name}</span>
			</div>
		`);
		benchContainer.append(playerDiv);
	});
	
	console.log('벤치 섹션 렌더링 완료');
}

// 모달 배경 클릭 시 닫기 (기존 이벤트에 추가)
$(document).on('click', '#substitutionModal', function(e) {
	if (e.target.id === 'substitutionModal') {
		closeSubstitutionModal();
	}
});


function addPlayerIcon(playerId, iconType){
    console.log('=== addPlayerIcon 호출 ===');
    console.log('선수 ID:', playerId, '아이콘 타입:', iconType);
    
    var iconMap = { 
        goal:'⚽', 
        assist:'🅰️', 
        yellow:'🟨', 
        red:'🟥',
        sub_in:'🔄'
    };
    var icon = iconMap[iconType] || '';
    
    console.log('사용할 아이콘:', icon);
    
    var iconAdded = false;
    
    $('.position').each(function(){
        var $position = $(this);
        var position = $position.data('position');
        
        console.log('포지션 체크:', position, lineup[position]);
        
        if (lineup[position] && lineup[position].sj_id == playerId) {
            console.log('✅ 포지션에서 선수 발견:', position, lineup[position]);
            
            var $icons = $position.find('.player-icons');
            if (!$icons.length) {
                console.log('player-icons 컨테이너 생성');
                $icons = $('<div class="player-icons"></div>').appendTo($position.find('.position-player'));
            }
            
            var $newIcon = $('<span>').addClass('icon ' + iconType)
                .attr('data-type', iconType)
                .text(icon);
            
            $icons.append($newIcon);
            
            console.log('아이콘 추가됨:', iconType, icon);
            
            if (iconType === 'red') {
                $position.addClass('red-card');
            } else if (iconType === 'sub_in') {
                $position.addClass('substituted-in');
                console.log('substituted-in 클래스 추가됨');
            }
            
            iconAdded = true;
            return false; // 루프 종료
        }
    });
    
    if(!iconAdded) {
        console.log('❌ 아이콘을 추가할 포지션을 찾지 못함:', playerId);
    }
}


// AJAX 헬퍼
function sendMatchAjax(action, params, onSuccess, onError){
	params.w = action;
	params.ts_id = scheduleId;
	params.quarter = currentQuarter;
	
	$.ajax({
		url: './update_match.php',
		type: 'POST',
		data: params,
		dataType: 'json',
		success: onSuccess,
		error: onError || function(xhr){
			console.error('AJAX 오류:', xhr);
			alert('<?php echo _t('통신 오류가 발생했습니다.');?>');
		}
	});
}


function executeSubstitution(inPlayerId, inPlayerName, inPlayerNumber) {
    console.log('executeSubstitution 호출됨:', inPlayerId, inPlayerName, inPlayerNumber);
    
    if(!substitutionData || !substitutionData.outPlayerId) {
        alert('<?php echo _t("교체할 선수 정보가 없습니다."); ?>');
        return;
    }
    
    var confirmMsg = substitutionData.outPlayerName + ' OUT ↔ ' + inPlayerName + ' IN\n\n<?php echo _t("교체하시겠습니까?"); ?>';
    
    if(!confirm(confirmMsg)) {
        return;
    }
    
    console.log('교체 AJAX 전송');
    
    // 실제 AJAX 요청
    sendMatchAjax('substitution', {
        out_player_id: substitutionData.outPlayerId,
        in_player_id: inPlayerId,
        out_player_name: substitutionData.outPlayerName,
        in_player_name: inPlayerName,
        in_player_number: inPlayerNumber
    }, function(response) {
        console.log('교체 응답:', response);
        
        if(response.status) {
            // UI 업데이트
            updateSubstitutionUIManual(inPlayerId, inPlayerName, inPlayerNumber);
            closeSubstitutionModal();
            loadMoreFallback(true);
            
            alert(response.msg || '<?php echo _t("교체가 완료되었습니다."); ?>');
        } else {
            alert(response.msg || '<?php echo _t("교체 중 오류가 발생했습니다."); ?>');
        }
    }, function(error) {
        console.error('교체 AJAX 오류:', error);
        alert('<?php echo _t("교체 중 통신 오류가 발생했습니다."); ?>');
    });
}


function closeSubstitutionModal() {
    console.log('교체 모달 닫기');
    $('#substitutionModal').fadeOut(300);
    if(typeof substitutionData !== 'undefined') {
        substitutionData = {
            outPlayerId: null,
            outPlayerName: null,
            outPlayerNumber: null
        };
    }
}


function updatePositionDisplay(position, playerName, playerNumber) {
    console.log('포지션 업데이트:', position, playerName, playerNumber);
    
    var $position = $('.position[data-position="' + position + '"]');
    if ($position.length === 0) {
        console.log('포지션 요소를 찾을 수 없음:', position);
        return;
    }
    
    var $playerDiv = $position.find('.position-player');
    if ($playerDiv.length === 0) {
        console.log('position-player 요소를 찾을 수 없음');
        return;
    }
    
    // HTML 업데이트
    var html = '<span class="number">' + (playerNumber || '-') + '</span>' +
               '<span class="name">' + playerName + '</span>';
    
    $playerDiv.html(html);
    $position.addClass('filled');
    
    console.log('포지션 표시 업데이트 완료');
}


</script>


<?php
include_once(G5_THEME_MOBILE_PATH.'/tail.php');
?>






