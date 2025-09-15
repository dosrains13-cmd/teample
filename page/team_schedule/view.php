<?php 
include_once "./_common.php";

if(!$te_id){
$sql = "select * from sweb_team_schedule where ts_id = '{$ts_id}' ";
$view = sql_fetch($sql);
$te_id = $view['te_id'];
}

include_once "./setting.php";

include_once "../team/team.common.php";
if($error_msg) alert($error_msg, G5_URL);
$code3 = "view";
$g5['title'] = $sweb['view_title'];

include_once(G5_THEME_MOBILE_PATH.'/head.php');

if(!$is_member){
	goto_url(G5_BBS_URL . "/login.php?url=/page/team_schedule/view.php?ts_id=".$ts_id);
}

if($parent['te_is_schedule'] || $is_admin_team || $is_member_team){

}else{
	alert("접근 권한이 없습니다.", G5_URL);
	exit;
}

//접근권한 체크
fn_authCheck($sweb['view_level'], "");

//데이터 조회
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


$date = "";
if($view[$prefix.'start_date'] == $view[$prefix.'end_date']){
	$date .= $view[$prefix.'start_date'] . " (".get_yoil($view[$prefix.'start_date']).") <span>" . $view[$prefix.'start_time'] . "~" . $view[$prefix.'end_time']."</span>";
}else{
	$date .= $view[$prefix.'start_date'] . " (".get_yoil($view[$prefix.'start_date']).") " . $view[$prefix.'start_time'] . " ~ " . $view[$prefix.'end_date'] . " (".get_yoil($view[$prefix.'end_date']).") " . $view[$prefix.'end_time'];
}




if (!$view) {
    alert(_t("일정 정보를 찾을 수 없습니다."));
    exit;
}

// 삭제 권한 체크
$can_delete = ($is_admin_team || $is_admin == 'super' || $view['mb_id'] == $member['mb_id']);
$can_edit_squad = ($is_admin_team || $is_admin == 'super' || $view['mb_id'] == $member['mb_id']);

$date = "";
if($view[$prefix.'start_date'] == $view[$prefix.'end_date']){
	$date .= $view[$prefix.'start_date'] . " (".get_yoil($view[$prefix.'start_date']).") <span>" . $view[$prefix.'start_time'] . "~" . $view[$prefix.'end_time']."</span>";
}else{
	$date .= $view[$prefix.'start_date'] . " (".get_yoil($view[$prefix.'start_date']).") " . $view[$prefix.'start_time'] . " ~ " . $view[$prefix.'end_date'] . " (".get_yoil($view[$prefix.'end_date']).") " . $view[$prefix.'end_time'];
}


//첨부파일
//$files = fileList($file_code, "", $key);


//참석자 리스트
$sql = "select * from ( 
	select * from sweb_team_join where {$parent_key_column} = '{$parent_key}'
) T1 
left outer join (
	select 
		file_id,
		file_table_idx,
		file_name,
		file_rename,
		file_desc
	from {$sweb['file_table']} 
	where file_code='team_join' and file_sub_code='image' and file_order=0
) T2 
ON T1.tj_id = T2.file_table_idx
left outer join (
	select * from {$table_name}_join where {$key_column} = '{$key}' 
) T3 
ON T1.mb_id = T3.mb_id
order by sj_id desc ";
$result = sql_query($sql);

$list = array();
$f_path = G5_DATA_PATH . "/file/team_join/";
$f_url = G5_DATA_URL . "/file/team_join/";
while($row = sql_fetch_array($result)){
	$thumb = thumbnail($row['file_rename'], $f_path, $f_path, 140, 0, false, false, 'center', false, '80/0.5/3');
	if($thumb){
		$row['img'] = "".$f_url . $thumb ."";
	}else{
		$row['img'] = G5_URL."/img/no_profile.gif";
	}

	if($row['sj_id'] && $row['sj_status'] == 1){
		$list['join'][] = $row;
	}else if($row['sj_id'] && $row['sj_status'] == 0){
		$list['nojoin'][] = $row;
	}else{
		$list['ignore'][] = $row;
	}
}



$sql = "select * from {$table_name}_join where {$key_column} = '{$key}' and sj_is_guest='1' ";
$result = sql_query($sql);
while($row = sql_fetch_array($result)){
	$row['img'] = G5_URL."/img/no_profile.gif";
	$list['join'][] = $row;
}



//로그 리스트
$sql = "select * from {$table_name_log} T1 where T1.{$key_column} = '{$key}' order by sl_id desc ";
$result = sql_query($sql);

// list 배열에 저장
$list_log = array();
while($row = sql_fetch_array($result)){
	$list_log[] = $row;
}

//조회수 증가
//$sql = "update {$table_name} set {$prefix}cnt = {$prefix}cnt + 1 where {$key_column} = '{$key}' ";
//sql_query($sql);



$kakao_name = $parent['te_name'];
$kakao_title = strip_tags($date);
$kakao_content = "현재 참석인원 : " . number_format(count($list['join'])) . "명 / ";
$kakao_content .= "장소 : " . $view['ts_location']; 
$kakao_img = "";
$f_path = G5_DATA_PATH . "/file/team/";
$f_url = G5_DATA_URL . "/file/team/";
$thumb = thumbnail($parent['file_rename'], $f_path, $f_path, 600, 0, false, false, 'center', false, '80/0.5/3');
if($thumb) $kakao_img = $f_url . $thumb;
$kakao_url = "page/team_schedule/view.php?ts_id=".$ts_id."&te_id=".$te_id;


?>

<!-- view start -->
<?php include "../team/tab.php"; ?>


<div class="schedule-view">
	<div class="schedule-info round ">

		<div class="schedule">
			<ul>
				<li class="<?php echo $old; ?> ">
					<div>

                        <!-- 🔥 수정/삭제 버튼 영역 -->
                        <?php if($can_delete){ ?>
                        <div class="schedule_admin_btns">
                            <a href="#none;" onclick="fn_modify();" class="team_adm schedule_adm" title="<?php echo _t('일정 수정'); ?>">
                                <i class="fa fa-cog" aria-hidden="true"></i>
                            </a>
                            <a href="#none;" onclick="fn_delete();" class="team_adm schedule_delete" title="<?php echo _t('일정 삭제'); ?>">
                                <i class="fa fa-trash" aria-hidden="true"></i>
                            </a>
                        </div>
                        <?php } ?>

						<h4><?php echo $view[$prefix.'name']; ?></h4>				
						<?php if($arr_gubun[$view[$prefix.'gubun']]){ ?>
						<i>
							<?php echo _t($arr_gubun[$view[$prefix.'gubun']]); ?>
							<?php if($view[$prefix.'gubun2'] && $arr_gubun2[$view[$prefix.'gubun2']]){ ?>
								· <?php echo _t($arr_gubun2[$view[$prefix.'gubun2']]); ?>
							<?php } ?>
						</i>
						<?php } ?>

						<?php if($view[$prefix.'match_team']) { ?>
						<b>VS <?php echo $view[$prefix.'match_team']; ?></b>
						<?php } ?>

						<p><?php echo $date; ?></p>
						<div class="location">
							[<?php echo $view[$prefix.'location']; ?>]
							<?php echo $view['ts_address']; ?>					
						</div>
					</div>

				</li>
			</ul>
		</div>

		<form method="post" id="joinForm" name="joinForm" action="<?php echo $sweb['action_url']; ?>">
			<input type="hidden" name="w" value="j" />
			<input type="hidden" name="sj_id" value="" />
			<input type="hidden" name="sj_status" value="" />
			<input type="hidden" name="<?php echo $parent_key_column; ?>" value="<?php echo $parent_key; ?>" />
			<input type="hidden" name="<?php echo $key_column; ?>" value="<?php echo $key; ?>" />

			<div class="status ">
				<span class="ok">
					<button onclick="fn_join('1');return false;" ><span class="material-symbols-outlined">how_to_reg</span><?php echo _t('참석'); ?></button>
				</span>
				<span class="cancel">
					<button onclick="fn_join('0');return false;"><span class="material-symbols-outlined">cancel</span><?php echo _t('불참'); ?></button>
				</span>
				<span class="guest">
					<button class="modal-btn" type="button" data-target="guest_pop"><span class="material-symbols-outlined">person_add</span><?php echo _t('Guest'); ?></button>
				</span>

				<div class="modal" id="guest_pop">
					<div class="modal-close-btn"><span class="material-symbols-outlined">close</span></div>
					<div class="cont ">
						<div class="eden_form_type2">
							<span class="select"><input type="text" id="sj_name" name="sj_name" value="" placeholder="<?php echo _t('Guest name'); ?>"></span>
							<span class="join-btn"><button onclick="fn_guest();return false;"><?php echo _t('게스트 추가'); ?></button></span>
						</div>
					</div>
				</div>
			</div>

			<div class="menu">
				<a href="javascript:Kakao_sendLink('<?php echo $kakao_img; ?>', '<?php echo $kakao_name; ?>', '<?php echo $kakao_title; ?>', '<?php echo $kakao_content; ?>', '<?php echo $kakao_url; ?>');" class="share-btn"><img src="<?php echo G5_IMG_URL ?>/kakao.png" alt="<?php echo _t('카톡 공유'); ?>"><span><?php echo _t('일정 공유'); ?></span></a>			
			</div>
		</form>

	</div>


	<div class="player-section attend">
		<div class="tit">
			<h4><?php echo _t('참석'); ?> <span><?php echo number_format(count($list['join'])); ?><?php echo _t('명'); ?></span></h4>
			<?php if($view[$prefix.'gubun'] == '254' && $view[$prefix.'match_team']) { ?>
			<a href="./match_record.php?te_id=<?php echo $te_id;?>&ts_id=<?php echo $ts_id; ?>&quarter=1" class="team_result_btn"><?php echo _t('경기 기록'); ?></a>
			<?php } elseif($view[$prefix.'gubun'] == '255' && array_filter($list['join'], function($p) { return $p['sj_team'] !== ''; })) { ?>
			<a href="#none;" onclick="fn_teamResult();return false;" class="team_result_btn"><?php echo _t('팀 결과확인'); ?></a>
			<?php } ?>


			<div class="bottom-btn-right">
				<?php if($view[$prefix.'gubun'] == '254'){ // 축구 ?>
					<?php if($can_edit_squad){ ?>
						<!-- 관리자: 스쿼드 편집 -->
						<a href="./squad.php?te_id=<?php echo $te_id;?>&ts_id=<?php echo $ts_id; ?>">
							<span class="material-symbols-outlined">diversity_3</span>
						</a>
					<?php } else { ?>
						<!-- 일반 회원: 경기 기록 페이지로 -->
						<a href="./match_record.php?te_id=<?php echo $te_id;?>&ts_id=<?php echo $ts_id; ?>&quarter=1">
							<span class="material-symbols-outlined">diversity_3</span>
						</a>
					<?php } ?>
				<?php }elseif($view[$prefix.'gubun'] == '255'){ // 풋살 - 팀 배분 (기존 방식) ?>
					<a href="./team.php?te_id=<?php echo $te_id;?>&ts_id=<?php echo $ts_id; ?>">
						<span class="material-symbols-outlined">diversity_3</span>
					</a>
				<?php } ?>
			</div>
		</div>

		<div class="player-list">
			<ul class="grid">
				<?php for($i = 0; $i < count($list['join']); $i++){ 
					$row = $list['join'][$i];
					?>

				<li class="member-test round">
					<div>
						<i class="photo" style="background-image:url('<?php echo $row['img']; ?>');"></i>
						<small><?php echo $row['sj_is_guest'] ? _t("Guest") : _t($arr_join_gubun[$row['sj_gubun']]); ?></small>
						<p>
							<?php echo $row['tj_name'] ? $row['tj_name'] : $row['sj_name']; ?>
							<?php if($row['sj_is_guest'] && ($is_admin_team || $member['mb_id'] == $row['parent_mb_id'])){ ?>
							<a href="#none" onclick="fn_guestDelete(<?php echo $row['sj_id']; ?>);return false;"><i class="fa fa-times" aria-hidden="true"></i></a>
							<?php } ?>
						</p>
					</div>
				</li>

				<?php } ?>

			</ul>
		</div>
	</div>

	<div class="player-section">
		<div class="tit">
			<h4><?php echo _t('불참'); ?> <span><?php echo number_format(count($list['nojoin'])); ?><?php echo _t('명'); ?></span></h4>
		</div>

		<div class="player-list">
			<ul>
				<?php for($i = 0; $i < count($list['nojoin']); $i++){ 
					$row = $list['nojoin'][$i];
					?>
				<li class="round">
					<div>
						<i class="photo" style="background-image:url('<?php echo $row['img']; ?>');"></i>
						<small><?php echo _t($arr_join_gubun[$row['sj_gubun']]); ?></small>
						<p><?php echo $row['tj_name']; ?></p>
					</div>
				</li>
				<?php } ?>
			</ul>
		</div>
	</div>

	<div class="player-section">
		<div class="tit">
			<h4><?php echo _t('미응답'); ?> <span><?php echo number_format(count($list['ignore'])); ?><?php echo _t('명'); ?></span></h4>
		</div>

		<div class="player-list">
			<ul>
				<?php for($i = 0; $i < count($list['ignore']); $i++){ 
					$row = $list['ignore'][$i];
					?>
				<li class="round">
					<div>
						<i class="photo" style="background-image:url('<?php echo $row['img']; ?>');"></i>
						<small><?php echo _t($arr_join_gubun[$row['sj_gubun']]); ?></small>
						<p><?php echo $row['tj_name']; ?> </p>
					</div>
				</li>
				<?php } ?>
			</ul>
		</div>
	</div>

	<div class="attend_log round">
		<ul class="log">
			<?php for($i = 0; $i < count($list_log); $i++){ 
				$log_content = $list_log[$i]['sl_content'];
				
				// JSON 데이터인지 확인
				$log_data = json_decode($log_content, true);
				if($log_data && isset($log_data['type'])) {
					// 구조화된 데이터 - 실시간 번역
					switch($log_data['type']) {
						case 'attendance':
							$action_text = $log_data['action'] == 'attend' ? _t('참석') : _t('불참');
							$display_text = $log_data['player_name'] . _t('님이') . ' ' . $action_text . ' ' . _t('하였습니다') . '.';
							break;
						case 'guest_join':
							$display_text = $log_data['guest_name'] . ' ' . _t('님이') . ' [' . _t('참석') . '] ' . _t('하였습니다') . '.';
							break;
						default:
							$display_text = $log_content;
					}
				} else {
					// 기존 텍스트 데이터 - 그대로 출력 (하위 호환성)
					$display_text = $log_content;
				}
			?>
			<li>
				<p><?php echo $display_text; ?></p>
				<i><?php echo substr(str_replace("-", ".", $list_log[$i]['insert_date']), 2, 20); ?></i>
			</li>
			<?php } ?>
		</ul>
	</div>

</div>

<!-- // view end -->

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
$(document).ready(function(){
	
});

// list 호출
function fn_list(){
	document.moveForm.<?php echo $key_column; ?>.value = '<?php echo $key; ?>';
	$('#moveForm').attr("action", "<?php echo $sweb['list_url']; ?>");
	$('#moveForm').submit();
}

// form 호출
function fn_modify(){
	document.moveForm.<?php echo $key_column; ?>.value = '<?php echo $key; ?>';
	$('#moveForm').attr("action", "<?php echo $sweb['modify_url']; ?>");
	$('#moveForm').submit();
}



// 🔥 삭제 함수 추가
function fn_delete(){
    if(!confirm("<?php echo _t('정말 이 일정을 삭제하시겠습니까?'); ?>\n\n<?php echo _t('참석 정보, 스쿼드 정보 등 모든 관련 데이터가 함께 삭제됩니다.'); ?>")){
        return false;
    }
    
    var formData = new FormData();
    formData.append('w', 'd');
    formData.append('<?php echo $key_column; ?>', '<?php echo $key; ?>');
    formData.append('<?php echo $parent_key_column; ?>', '<?php echo $parent_key; ?>');
    
    $.ajax({
        url: "<?php echo $sweb['action_url']; ?>",
        type: 'POST',
        data: formData,
        dataType: 'json',
        async: true,
        cache: false,
        contentType: false,
        processData: false,
        beforeSend: function(){
            loadingStart();
        },
        success: function(data){
            if(data.status){
                alert(data.msg);
                if(data.url){
                    location.href = data.url;
                } else {
                    fn_list();
                }
            } else {
                alert(data.msg);
            }
        },
        error: function(xhr, status, error){
            console.error('삭제 오류:', error);
            alert('<?php echo _t("처리 중 오류가 발생했습니다."); ?>');
        },
        complete: function(){
            loadingEnd();
        }
    });
    
    return false;
}


// team짜기 호출
function fn_team(){
	document.moveForm.<?php echo $key_column; ?>.value = '<?php echo $key; ?>';
	$('#moveForm').attr("action", "<?php echo $sweb['team_url']; ?>");
	$('#moveForm').submit();
}

function fn_teamResult(){
	document.moveForm.<?php echo $key_column; ?>.value = '<?php echo $key; ?>';
	$('#moveForm').attr("action", "<?php echo $sweb['team_result_url']; ?>");
	$('#moveForm').submit();
}

// 참석,불참 클릭
function fn_join(status){
	document.joinForm.sj_status.value = status;
	var formData = new FormData($('#joinForm')[0]);
	var url = "<?php echo $sweb['action_url']; ?>";

	$.ajax({
		url : url,
		type : 'post',
		data : formData,
		dataType : 'json',
		asynsc : true,
		cache : false,
		contentType : false,
		processData : false,
		beforeSend:function(){
			loadingStart();
		},
		success : function(data, jqXHR, textStatus){
			if(data.status){
				
			}else{
				alert(data.msg);
				$('#error_txt').val(data.msg);
				$('#error_area').show();
			}

			if(data.reload){
				location.reload();
			}
		},
		error : function(jqXHR, textStatus, errorThrown){
			alert("<?php echo _t('처리 중 오류가 발생하였습니다. 다시 시도해주세요.'); ?>");
		},
		complete : function(){
			loadingEnd();
		}
	});

	return false;
}

// 게스트 등록
function fn_guest(){
	if($('#sj_name').val() == ""){
		alert('<?php echo _t('게스트명을 입력해주세요.'); ?>');
		return false;
	}
	document.joinForm.sj_status.value = '1';
	document.joinForm.w.value = 'jg';
	var formData = new FormData($('#joinForm')[0]);
	var url = "<?php echo $sweb['action_url']; ?>";

	$.ajax({
		url : url,
		type : 'post',
		data : formData,
		dataType : 'json',
		asynsc : true,
		cache : false,
		contentType : false,
		processData : false,
		beforeSend:function(){
			loadingStart();
		},
		success : function(data, jqXHR, textStatus){
			if(data.status){
				
			}else{
				alert(data.msg);
				$('#error_txt').val(data.msg);
				$('#error_area').show();
			}

			if(data.reload){
				location.reload();
			}
		},
		error : function(jqXHR, textStatus, errorThrown){
			alert("<?php echo _t('처리 중 오류가 발생하였습니다. 다시 시도해주세요.'); ?>");
		},
		complete : function(){
			loadingEnd();
		}
	});

	return false;
}

// 게스트 삭제
function fn_guestDelete(sj_id){
	if(confirm("<?php echo _t('게스트를 삭제 하시겠습니까?'); ?>")){
		document.joinForm.sj_id.value = sj_id;
		document.joinForm.w.value = 'dg';
		var formData = new FormData($('#joinForm')[0]);
		var url = "<?php echo $sweb['action_url']; ?>";

		$.ajax({
			url : url,
			type : 'post',
			data : formData,
			dataType : 'json',
			asynsc : true,
			cache : false,
			contentType : false,
			processData : false,
			beforeSend:function(){
				loadingStart();
			},
			success : function(data, jqXHR, textStatus){
				if(data.status){

				}else{
					alert(data.msg);
					$('#error_txt').val(data.msg);
					$('#error_area').show();
				}

				if(data.reload){
					location.reload();
				}
			},
			error : function(jqXHR, textStatus, errorThrown){
				alert("<?php echo _t('처리 중 오류가 발생하였습니다. 다시 시도해주세요.'); ?>");
			},
			complete : function(){
				loadingEnd();
			}
		});
	}
	return false;
}

</script>

<script src="//developers.kakao.com/sdk/js/kakao.min.js" charset="utf-8"></script>
<script src="<?php echo G5_JS_URL; ?>/kakaolink.js" charset="utf-8"></script>
<script type='text/javascript'>
//<![CDATA[
// 사용할 앱의 Javascript 키를 설정해 주세요.
Kakao.init("bbff4333946e27c2b17ba7ee2533d7cf");

function Kakao_sendLink(img, name, title, description, link) {
	Kakao.Link.sendCustom({
		templateId: 36866,
		templateArgs: {
			'THU': img,
			'name': name,
			'title': title,
			'description': description,
			'link' : link
		}
	});
}

function Kakao_sendMsg(img, title, description, link) {
	Kakao.Link.sendDefault({
		objectType: 'feed',
		content: {
			title: title,
			description: description,
			imageUrl: img,
			link: {
				mobileWebUrl: link,
				webUrl: link
			}
		},
		buttons: [{
			title: '<?php echo _t('자세히 보기'); ?>',
			link: {
				mobileWebUrl: link,
				webUrl: link
			}
		}]
	});
}
//]]>
</script>