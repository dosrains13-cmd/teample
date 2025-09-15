<?php 
include_once "./_common.php";

include_once "./setting.php";
$code3 = "write";
include_once "../team/team.common.php";
if($error_msg) alert($error_msg, G5_URL);

$g5['title'] = $sweb['write_title'];

include_once(G5_THEME_MOBILE_PATH.'/head.php');


if($parent['te_is_schedule'] || $is_admin_team || $is_member_team){

}else{
	alert(_t("접근 권한이 없습니다."));
	exit;
}


if($key){
	$w = "u";
	// 상세정보
	$sql = "select * from {$table_name} where {$key_column}='{$key}'  ";
	$view = sql_fetch($sql);

	if (!$view) alert(_t("등록된 자료가 없습니다."));

	//첨부파일
	$files = fileList($file_code, "", $key);

	//접근권한 체크
	fn_authCheck($sweb['write_level'], $view['mb_id']);
}else{
	$w = "";
	//접근권한 체크
	fn_authCheck($sweb['write_level'], "");

	$sql = "select * from {$table_name} where {$parent_key_column}='{$parent_key}' order by {$key_column} desc";
	
	$view = sql_fetch($sql);

	$view[$prefix.'start_date'] = date("Y-m-d");
	$view[$prefix.'end_date'] = date("Y-m-d");
}


?>

<!-- form start -->
<?php include "../team/tab.php"; ?>

<div class="schedule-write-wrap">
	<form method="post" id="submitForm" name="submitForm" action="<?php echo $sweb['action_url']; ?>">
		<input type="hidden" id="<?php echo $parent_key_column; ?>" name="<?php echo $parent_key_column; ?>" value="<?php echo $parent[$parent_key_column]; ?>" />
		<input type="hidden" id="<?php echo $key_column; ?>" name="<?php echo $key_column; ?>" value="<?php echo $key; ?>" />
		<input type="hidden" id="w" name="w" value="<?php echo $w; ?>" />
		<input type="hidden" name="page" value="<?php echo $page; ?>">
		<input type="hidden" name="return_url" value="<?php echo $_SERVER['HTTP_REFERER']; ?>" />
		<?php foreach($sweb['list']['qstr'] as $key_qstr => $value_qstr){ ?>
		<input type="hidden" name="<?php echo $key_qstr; ?>" value="<?php echo $value_qstr; ?>">
		<?php } ?>

		<div class="schedule_form eden_form_type2 ">
			<?php $column = $sweb['column']['gubun']; ?>
			<div class="form_div">
				<label for=""><?php echo _t($column['name_kor']); ?></label>
				<select id="<?php echo $prefix . $column['name']; ?>" name="<?php echo $prefix . $column['name']; ?>" onchange="changeGubun2Options(this.value)">
					<?php echo fn_getSelectOption($column['arr'], $view[$prefix.$column['name']], $column['defaultValue']); ?>
				</select>

				<small><?php echo _t("* 유형에 따라 스쿼드 방식이 달라집니다.") ?></small>
			</div>
			
			<?php 
			$column = $sweb['column']['gubun2']; 
			// gubun2 옵션 필터링을 위한 배열 준비
			$arr_gubun2_filtered = array();
			if($view[$prefix.'gubun']) {
				// 수정 모드일 때 gubun에 맞는 옵션만 표시
				$gubun_value = $view[$prefix.'gubun'];
				if(in_array($gubun_value, array('254', '255'))) { // 축구, 풋살
					$arr_gubun2_filtered = array(
						'284' => $arr_gubun2['284'],
						'285' => $arr_gubun2['285'],
						'286' => $arr_gubun2['286']
					);
				} else if(in_array($gubun_value, array('256', '282'))) { // 친목, 기타
					$arr_gubun2_filtered = array(
						'287' => $arr_gubun2['287']
					);
				}
			}
			?>
			<div class="form_div" id="gubun2_wrapper">
				<label for=""><?php echo _t($column['name_kor']); ?></label>
				<select id="<?php echo $prefix . $column['name']; ?>" name="<?php echo $prefix . $column['name']; ?>" onchange="toggleMatchTeamField()">
					<option value=""><?php echo _t('선택하세요'); ?></option>
					<?php if(!empty($arr_gubun2_filtered)) { ?>
						<?php echo fn_getSelectOption($arr_gubun2_filtered, $view[$prefix.$column['name']], ''); ?>
					<?php } ?>
				</select>
				<small><?php echo _t("* 일반전, 리그전: 상대팀과의 경기 (우리팀 스쿼드만 구성)") ?></small><br>
				<small><?php echo _t("* 자체전: 우리팀 내부 경기 (A팀, B팀으로 나누어 구성)") ?></small>
			</div>

			<!-- 상대팀명 필드 추가 -->
			<?php $column = $sweb['column']['match_team']; ?>
			<div class="form_01 form_div" id="match_team_wrapper" style="display: none;">
				<label for="<?php echo $prefix . $column['name']; ?>"><?php echo _t($column['name_kor']); ?></label>
				<input type="text" id="<?php echo $prefix . $column['name']; ?>" name="<?php echo $prefix . $column['name']; ?>" value="<?php echo $view[$prefix.$column['name']]; ?>" class="frm_input <?php echo $column['class']; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> size="100" placeholder="<?php echo _t($column['msg']); ?>" />
			</div>
			
			<script>
			// gubun2 옵션 데이터
			var gubun2Options = {
				'254': { // 축구
					'284': '<?php echo _t($arr_gubun2['284']); ?>',
					'285': '<?php echo _t($arr_gubun2['285']); ?>',
					'286': '<?php echo _t($arr_gubun2['286']); ?>'
				},
				'255': { // 풋살
					'284': '<?php echo _t($arr_gubun2['284']); ?>',
					'285': '<?php echo _t($arr_gubun2['285']); ?>',
					'286': '<?php echo _t($arr_gubun2['286']); ?>'
				},
				'256': { // 친목
					'287': '<?php echo _t($arr_gubun2['287']); ?>'
				},
				'282': { // 기타
					'287': '<?php echo _t($arr_gubun2['287']); ?>'
				}
			};
			
			// gubun 변경 시 gubun2 옵션 변경
			function changeGubun2Options(gubunValue) {
				var gubun2Select = document.getElementById('<?php echo $prefix; ?>gubun2');
				
				// 기존 옵션 초기화
				gubun2Select.innerHTML = '<option value=""><?php echo _t("선택하세요"); ?></option>';
				
				// gubun 값이 없으면 종료
				if(!gubunValue) {
					$('#<?php echo $prefix; ?>gubun2').niceSelect('update');
					toggleMatchTeamField();
					return;
				}
				
				// 해당 gubun에 맞는 옵션 추가
				if(gubun2Options[gubunValue]) {
					for(var key in gubun2Options[gubunValue]) {
						var option = document.createElement('option');
						option.value = key;
						option.text = gubun2Options[gubunValue][key];
						gubun2Select.appendChild(option);
					}
				}
				
				// nice-select 업데이트
				$('#<?php echo $prefix; ?>gubun2').niceSelect('update');
				toggleMatchTeamField();
			}

			// 상대팀명 필드 표시/숨김 및 필수 여부 제어
			function toggleMatchTeamField() {
				var gubun2Value = $('#<?php echo $prefix; ?>gubun2').val();
				var matchTeamWrapper = $('#match_team_wrapper');
				var matchTeamInput = $('#<?php echo $prefix; ?>match_team');
				
				// 일반전(284) 또는 리그전(286)일 때만 표시 및 필수
				if(gubun2Value == '284' || gubun2Value == '286') {
					matchTeamWrapper.show();
					matchTeamInput.addClass('required');
					matchTeamInput.attr('required', 'required');
				} else {
					matchTeamWrapper.hide();
					matchTeamInput.removeClass('required');
					matchTeamInput.removeAttr('required');
					// 수정 모드가 아닐 때만 값 초기화
					if('<?php echo $w; ?>' === '') {
						matchTeamInput.val('');
					}
				}
			}
			
			// 페이지 로드 시 초기화
			$(document).ready(function(){
				<?php if(!$view[$prefix.'gubun']) { ?>
				// 신규 등록 시 초기값 설정
				$('#<?php echo $prefix; ?>gubun').val('254'); // 축구를 기본값으로
				changeGubun2Options('254');
				<?php } else { ?>
				// 수정 모드일 때 현재 gubun 값으로 초기화
				changeGubun2Options('<?php echo $view[$prefix.'gubun']; ?>');
				// 기존 gubun2 값 선택 및 상대팀명 필드 표시
				<?php if($view[$prefix.'gubun2']) { ?>
				setTimeout(function() {
					$('#<?php echo $prefix; ?>gubun2').val('<?php echo $view[$prefix.'gubun2']; ?>');
					$('#<?php echo $prefix; ?>gubun2').niceSelect('update');
					// 상대팀명 필드 상태 업데이트 (기존 값 보존)
					toggleMatchTeamField();
				}, 100);
				<?php } else { ?>
				// gubun2가 없으면 상대팀명 필드 숨김
				toggleMatchTeamField();
				<?php } ?>
				<?php } ?>
			});
			</script>

			<?php $column = $sweb['column']['name']; ?>
			<div class="form_01 form_div">
				<label for="<?php echo $prefix . $column['name']; ?>"><?php echo _t($column['name_kor']); ?></label>
				<input type="text" id="<?php echo $prefix . $column['name']; ?>" name="<?php echo $prefix . $column['name']; ?>" value="<?php echo $view[$prefix.$column['name']]; ?>" class="frm_input <?php echo $column['class']; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> size="100" placeholder="<?php echo _t($column['msg']); ?>" />
			</div>

			<div class="form_div  col-2 f_icon" style="margin-bottom: 10px;">
				<label for=""><?php echo _t('날짜'); ?></label>
				<ul>
					<?php $column = $sweb['column']['start_date']; ?>
					<li>
						<label for="<?php echo $prefix . $column['name']; ?>"><i class="fa fa-calendar" aria-hidden="true"></i><span class="sound_only"><?php echo _t('시작일'); ?> #01</span></label>
						<input type="text" id="<?php echo $prefix . $column['name']; ?>" name="<?php echo $prefix . $column['name']; ?>" value="<?php echo $view[$prefix.$column['name']]; ?>" class="frm_input <?php echo $column['class'] . " " . $column['required']; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> size="100" placeholder="<?php echo _t($column['msg']); ?>" />
					</li>
					<?php $column = $sweb['column']['start_time']; ?>
					<li>
						<label for="f_icon"><i class="fa fa-clock-o" aria-hidden="true"></i><span class="sound_only"><?php echo _t('시작시간'); ?> #02</span></label>
						<input type="time" id="<?php echo $prefix . $column['name']; ?>" name="<?php echo $prefix . $column['name']; ?>" value="<?php echo $view[$prefix.$column['name']]; ?>" class="frm_input <?php echo $column['class'] ; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> size="100" placeholder="<?php echo _t($column['msg']); ?>" />
					</li>
				</ul>
			</div>
			<script>
			$('#<?php echo $prefix;?>start_date').bind('change', function(){
				if($('#<?php echo $prefix;?>start_date').val() > $('#<?php echo $prefix;?>end_date').val()){
					$('#<?php echo $prefix;?>end_date').val($('#<?php echo $prefix;?>start_date').val());
				
				}
			});
			</script>
			<div class="form_div float-left col-2 f_icon">
				<ul>
					<?php $column = $sweb['column']['end_date']; ?>
					<li>
						<label for="<?php echo $prefix . $column['name']; ?>"><i class="fa fa-calendar" aria-hidden="true"></i><span class="sound_only"><?php echo _t('종료일'); ?> #01</span></label>
						<input type="text" id="<?php echo $prefix . $column['name']; ?>" name="<?php echo $prefix . $column['name']; ?>" value="<?php echo $view[$prefix.$column['name']]; ?>" class="frm_input <?php echo $column['class'] . " " . $column['required']; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> size="100" placeholder="<?php echo _t($column['msg']); ?>" />
					</li>
					<?php $column = $sweb['column']['end_time']; ?>
					<li>
						<label for="<?php echo $prefix . $column['name']; ?>"><i class="fa fa-clock-o" aria-hidden="true"></i><span class="sound_only"><?php echo _t('종료시간'); ?> #02</span></label>
						<input type="time" id="<?php echo $prefix . $column['name']; ?>" name="<?php echo $prefix . $column['name']; ?>" class="frm_input <?php echo $column['class'] ; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> size="100" placeholder="<?php echo _t($column['msg']); ?>"  value="<?php echo $view[$prefix.$column['name']]; ?>" />
					</li>
				</ul>
			</div>

			<?php $column = $sweb['column']['location']; ?>
			<div class="form_01 form_div">
				<label for="<?php echo $prefix . $column['name']; ?>"><?php echo _t($column['name_kor']); ?></label>
				<input type="text" id="<?php echo $prefix . $column['name']; ?>" name="<?php echo $prefix . $column['name']; ?>" value="<?php echo $view[$prefix.$column['name']]; ?>" class="frm_input <?php echo $column['class'] . " " . $column['required']; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> size="100" placeholder="<?php echo _t($column['msg']); ?>" />
			</div>

			<?php $column = $sweb['column']['address']; ?>
			<div class="form_01 form_div">
				<label for="<?php echo $prefix . $column['name']; ?>"><?php echo _t($column['name_kor']); ?></label>
				<input type="text" id="<?php echo $prefix . $column['name']; ?>" name="<?php echo $prefix . $column['name']; ?>" value="<?php echo $view[$prefix.$column['name']]; ?>" class="frm_input <?php echo $column['class'] . " " . $column['required']; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> size="100" placeholder="<?php echo _t($column['msg']); ?>" />
			</div>

		</div>

		<div class="btn2 col-2">
			<a href="#none;" onclick="fn_list();return false;" class="gray"><?php echo _t('취소'); ?></a>
			<button type="submit" class="submit_btn2" onclick="fn_submit();return false;" /><?php echo _t('확인'); ?></button>
		</div>
	</form>
</div>
<!-- // form end -->

<!-- page 이동 form start -->
<form method="get" id="moveForm" name="moveForm">
	<input type="hidden" name="w" value="" />
	<input type="hidden" name="page" value="<?php echo $page; ?>">
	<input type="hidden" name="<?php echo $key_column; ?>" value="<?php echo $key; ?>" />
	<input type="hidden" name="<?php echo $parent_key_column; ?>" value="<?php echo $parent_key; ?>" />
	<?php foreach($sweb['list']['qstr'] as $key_qstr => $value_qstr){ ?>
	<input type="hidden" name="<?php echo $key_qstr; ?>" value="<?php echo $value_qstr; ?>">
	<?php } ?>
</form>
<!-- // page 이동 form end -->

<?php
include_once(G5_THEME_PATH.'/tail.php');
?>

<script>
$(document).ready(function(){
	
});

// view 호출
function fn_list(){
	document.moveForm.w.value = '';
	document.moveForm.<?php echo $parent_key_column; ?>.value = '<?php echo $parent_key; ?>';
	$('#moveForm').attr("action", "<?php echo $sweb['list_url']; ?>");
	$('#moveForm').submit();
}

// 저장
function fn_submit(){

    var gubun2Value = $('#<?php echo $prefix; ?>gubun2').val();
    var matchTeamValue = $('#<?php echo $prefix; ?>match_team').val().trim();
    
    // 일반전(284) 또는 리그전(286)일 때 상대팀명 필수
    if((gubun2Value == '284' || gubun2Value == '286') && !matchTeamValue) {
        alert('<?php echo _t("상대팀명을 입력해주세요"); ?>');
        $('#<?php echo $prefix; ?>match_team').focus();
        return false; // 🔥 여기서 전송 중단
    }
	
	var formData = new FormData($('#submitForm')[0]);
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
				alert(data.msg);
				// update.php에서 지정한 URL로 이동, 없으면 목록으로
				if(data.url) {
					location.href = data.url;
				} else {
					fn_list();
				}

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
</script>