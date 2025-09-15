<?php 
include_once "./_common.php";
include_once "./setting.php";
$code3 = "write";
include_once "../team/team.common.php";
if($error_msg) alert($error_msg, G5_URL);

$g5['title'] = $sweb['write_title'];
include_once(G5_THEME_MOBILE_PATH.'/head.php');

//설정에서 가입차단
if(!$is_admin_team && !$is_member_team && !$parent['te_is_join']){
    alert(_t("팀가입 신청이 불가능한 팀입니다."));
    exit;
}

if($tj_id && ($is_admin == 'super' || $parent['mb_id'] == $member['mb_id'])) {
    // 최고관리자 또는 팀 관리자가 다른 사람 정보 수정
    $sql = "select * from {$table_name} where {$key_column}='{$tj_id}'";
    $view = sql_fetch($sql);
    
    if(!$view) {
	    alert(_t("해당 팀원을 찾을 수 없습니다."));

    }
    
    $w = 'u';
    $key = $view[$key_column];
    $files = fileList($file_code, "", $key);
    
} else {
    // 본인 정보 수정/등록하는 경우
    $sql = "select * from {$table_name} where {$parent_key_column}='{$parent_key}' and mb_id='".$member['mb_id']."' ";
    $view = sql_fetch($sql);
    
    if($view[$key_column]){
        $w = 'u';
        $key = $view[$key_column];
        fn_authCheck($sweb['write_level'], $view['mb_id']);
        $files = fileList($file_code, "", $key);
    } else {
        fn_authCheck($sweb['write_level'], '', G5_URL . "/page/team_join/form.php?".$parent_key_column.'='.$parent_key);
        $w = '';
        $view[$prefix.'name'] = $member['mb_name'];
        $view[$prefix.'phone'] = str_replace("-", "", $member['mb_hp']);
    }
}
?>

<!-- form start -->
<?php include "../team/tab.php"; ?>



<div class="join-wrap">
	<form method="post" id="submitForm" name="submitForm" action="<?php echo $sweb['action_url']; ?>">
		<input type="hidden" id="<?php echo $parent_key_column; ?>" name="<?php echo $parent_key_column; ?>" value="<?php echo $parent[$parent_key_column]; ?>" />
		<input type="hidden" id="<?php echo $key_column; ?>" name="<?php echo $key_column; ?>" value="<?php echo $key; ?>" />
		<input type="hidden" id="w" name="w" value="<?php echo $w; ?>" />
		<input type="hidden" name="page" value="<?php echo $page; ?>">
		<?php foreach($sweb['list']['qstr'] as $key_qstr => $value_qstr){ ?>
		<input type="hidden" name="<?php echo $key_qstr; ?>" value="<?php echo $value_qstr; ?>">
		<?php } ?>

		<div class="eden_form_type2">
			<div class="form_div ">
				<ul>
					<?php $column = $sweb['column']['name']; ?>
					<li class="row">
						<label class="label_name" for="<?php echo $prefix . $column['name']; ?>"><?php echo _t($column['name_kor']); ?></label>
						<div class="input">
							<input type="text" id="<?php echo $prefix . $column['name']; ?>" name="<?php echo $prefix . $column['name']; ?>" value="<?php echo $view[$prefix.$column['name']]; ?>" class="frm_input <?php echo $column['class'] . " " . $column['required']; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> size="100" placeholder="<?php echo $column['msg']; ?>" />
						</div>
					</li>
					<?php $column = $sweb['column']['phone']; ?>
					<li class="row">
						<label class="label_name" for="<?php echo $prefix . $column['name']; ?>"><?php echo _t($column['name_kor']); ?></label>
						<div class="input">
							<input type="tel" inputmode="numeric" pattern="[0-9]*" id="<?php echo $prefix . $column['name']; ?>" name="<?php echo $prefix . $column['name']; ?>" value="<?php echo $view[$prefix.$column['name']]; ?>" class="frm_input <?php echo $column['class'] . " " . $column['required']; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> size="100" placeholder="<?php echo $column['msg']; ?>" />
						</div>
					</li>
					<?php $column = $sweb['column']['gender']; ?>
					<li class="row">
						<label class="label_name" for="<?php echo $prefix . $column['name']; ?>"><?php echo _t($column['name_kor']); ?></label>
						<div class="input">
							<select id="<?php echo $prefix . $column['name']; ?>" name="<?php echo $prefix . $column['name']; ?>">
								<?php echo fn_getSelectOption($column['arr'], $view[$prefix.$column['name']], $column['defaultValue']); ?>
							</select>
						</div>
					</li>
					<?php $column = $sweb['column']['level']; ?>
					<li class="row">
						<label class="label_name" for="<?php echo $prefix . $column['name']; ?>"><?php echo _t($column['name_kor']); ?></label>
						<div class="input">
							<select id="<?php echo $prefix . $column['name']; ?>" name="<?php echo $prefix . $column['name']; ?>">
								<?php echo fn_getSelectOption($column['arr'], $view[$prefix.$column['name']], $column['defaultValue']); ?>
							</select>
						</div>
					</li>

					<li class="row position">
						<label class="label_name"><?php echo _t('포지션')?> <small><?php echo _t('(최대 2개 까지만 선택 할 수 있습니다.)')?></small></label>
						<div class="input form_div checkbox">
							<div class="position-group">
								<p><?php echo _t('DF(수비수)'); ?></p>
								<div class="chk-group">
									<?php foreach($arr_position_df as $k => $v){ ?>
									<div class="checkbox-wrapper-15">
										<input class="inp-cbx" id="<?php echo $prefix; ?>position<?php echo $k; ?>" type="checkbox" name="<?php echo $prefix; ?>position[]" value="<?php echo $k; ?>" <?php echo in_array($k, explode(",", $view[$prefix.'position'])) ? "checked='checked'" : ""; ?> style="display: none;"/>
										<label class="cbx" for="<?php echo $prefix; ?>position<?php echo $k; ?>">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php echo _t($v); ?></span>
										</label>
									</div>
									<?php } ?>
								</div>
							</div>
							
							<div class="position-group">
								<p><?php echo _t('MF(미드필더)'); ?></p>
								<div class="chk-group">
									<?php foreach($arr_position_mf as $k => $v){ ?>
									<div class="checkbox-wrapper-15">
										<input class="inp-cbx" id="<?php echo $prefix; ?>position<?php echo $k; ?>" type="checkbox" name="<?php echo $prefix; ?>position[]" value="<?php echo $k; ?>" <?php echo in_array($k, explode(",", $view[$prefix.'position'])) ? "checked='checked'" : ""; ?> style="display: none;"/>
										<label class="cbx" for="<?php echo $prefix; ?>position<?php echo $k; ?>">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php echo _t($v); ?></span>
										</label>
									</div>
									<?php } ?>
								</div>
							</div>
							
							<div class="position-group">
								<p><?php echo _t('FW(공격수)'); ?></p>
								<div class="chk-group">
									<?php foreach($arr_position_fw as $k => $v){ ?>
									<div class="checkbox-wrapper-15">
										<input class="inp-cbx" id="<?php echo $prefix; ?>position<?php echo $k; ?>" type="checkbox" name="<?php echo $prefix; ?>position[]" value="<?php echo $k; ?>" <?php echo in_array($k, explode(",", $view[$prefix.'position'])) ? "checked='checked'" : ""; ?> style="display: none;"/>
										<label class="cbx" for="<?php echo $prefix; ?>position<?php echo $k; ?>">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php echo _t($v); ?></span>
										</label>
									</div>
									<?php } ?>
								</div>
							</div>
						</div>
					</li>
								
					<li class="row">
						<label class="label_name" for="<?php echo $prefix; ?>number"><?php echo _t('등번호'); ?></label>
						<div class="input">
							<input type="number" pattern="\d*" class="required" id="<?php echo $prefix; ?>number" name="<?php echo $prefix; ?>number" value="<?php echo $view[$prefix.'number']; ?>" />
						</div>
					</li>

					<li class="row">
						<label class="label_name" for="<?php echo $prefix; ?>content"><?php echo _t('한줄소개'); ?></label>
						<div class="input">
							<input type="text" class="required" id="<?php echo $prefix; ?>content" name="<?php echo $prefix; ?>content" value="<?php echo $view[$prefix.'content']; ?>" />
						</div>
					</li>


					<li class="row">
						<label class="label_name" for="file_<?php echo $fk; ?>"><?php echo _t('선수 사진'); ?></label>
						<div class="input">
							<?php 
							$fk = "image";
							$file = $files[$fk][0];
							?>
							<input type="hidden" name="file_attach[]" value="<?php echo $fk; ?>" />
							
							<div class="custom-file-upload" data-file-key="<?php echo $fk; ?>">
								<div class="preview-box">
									<div class="preview-placeholder" id="preview_placeholder_<?php echo $fk; ?>" <?php echo $file['file_name'] ? 'style="display:none"' : ''; ?>>
										<i class="fa fa-user-circle"></i>
									</div>
									<div class="preview-image" id="preview_image_<?php echo $fk; ?>" <?php echo $file['file_name'] ? '' : 'style="display:none"'; ?>>
										<img src="<?php echo $file['file_name'] ? G5_SWEB_MODULE_URL.'/file/download.php?file_id='.$file['file_id'] : '#'; ?>" id="img_preview_<?php echo $fk; ?>" alt="">
									</div>
								</div>
								
								<div class="file-actions">
									<div class="file-info" id="file_info_<?php echo $fk; ?>" <?php echo $file['file_name'] ? '' : 'style="display:none"'; ?>>
										<span id="file_name_<?php echo $fk; ?>"><?php echo $file['file_name']; ?></span>
									</div>
									<div class="btn-group">
										<label for="file_<?php echo $fk; ?>" class="btn btn-upload">
											<i class="fa fa-upload"></i> <?php echo _t('이미지 업로드'); ?>
										</label>
										<input type="file" name="file_<?php echo $fk; ?>[]" id="file_<?php echo $fk; ?>" style="display:none" accept="image/*" onchange="previewFile('<?php echo $fk; ?>')">
										
										<button type="button" class="btn btn-delete" id="btn_delete_<?php echo $fk; ?>" onclick="deleteFile('<?php echo $fk; ?>')" <?php echo $file['file_name'] ? '' : 'style="display:none"'; ?>>
											<?php echo _t('삭제'); ?>
										</button>
										<input type="checkbox" id="file_del_<?php echo $fk; ?>" name="file_del_<?php echo $fk; ?>[]" value="1" style="display:none">
									</div>
								</div>
							</div>
						</div>
					</li>


							<script>
							$(function(){
							  // 파일 업로드 기능을 설정하는 함수
							  function setupFileUpload(fileKey) {
								const $fileInput = $('#file_' + fileKey);
								const $preview = $('#img_preview_' + fileKey);
								const $fileInfo = $('#file_info_' + fileKey);
								const $fileName = $('#file_name_' + fileKey);
								const $previewImage = $('#preview_image_' + fileKey);
								const $placeholder = $('#preview_placeholder_' + fileKey);
								const $deleteBtn = $('#btn_delete_' + fileKey);
								const $deleteCheck = $('#file_del_' + fileKey);
								
								// 파일 선택 시 미리보기 처리
								$fileInput.on('change', function() {
								  const file = this.files[0];
								  
								  if(file) {
									const reader = new FileReader();
									
									reader.onload = function(e) {
									  $preview.attr('src', e.target.result);
									  $fileName.text(file.name);
									  $fileInfo.show();
									  $previewImage.show();
									  $placeholder.hide();
									  $deleteBtn.show();
									};
									
									reader.readAsDataURL(file);
								  }
								});
								
								// 미리보기 영역 클릭 시 파일 선택 창 열기
								$placeholder.on('click', function() {
								  $fileInput.trigger('click');
								});
								
								// 삭제 버튼 클릭 처리
								$deleteBtn.on('click', function() {
								  $fileInput.val('');
								  $preview.attr('src', '#');
								  $fileInfo.hide();
								  $previewImage.hide();
								  $placeholder.show();
								  $deleteBtn.hide();
								  $deleteCheck.prop('checked', true);
								});
							  }
							  
							  // 페이지에 있는 모든 파일 업로드 요소 초기화
							  // 여러 파일 필드가 있을 경우 자동으로 적용
							  $('.custom-file-upload').each(function() {
								const fileKey = $(this).data('file-key');
								if(fileKey) {
								  setupFileUpload(fileKey);
								}
							  });
							  
							  // 또는 특정 필드만 초기화
							  setupFileUpload('logo');
							});
							</script>


				</ul>
			</div>


			<?php if($w == ''){ ?>
			<div class="join-msg">
				선수등록 요청하시겠습니까?
			</div>
			<?php } ?>


			<div class="btn2 col-2">
				<a href="#" onclick="history.back(); return false;" class="gray"><?php echo _t('취소'); ?></a>
				<button type="submit" class="submit_btn2" value="Confirm" onclick="fn_submit();return false;"><?php echo _t('확인'); ?></button>
				<?php if($w == 'u'){ ?>
				<input type="button" class="withdrawal_btn" value="<?php echo _t('팀 탈퇴'); ?>" onclick="if(confirm('정말 탈퇴하시겠습니까?')) {$('#w').val('d'); fn_submit();} return false;" />
				<?php } ?>

			</div>

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
function fn_parent_view(){
	document.moveForm.w.value = '';
	document.moveForm.<?php echo $parent_key_column; ?>.value = '<?php echo $parent_key; ?>';
	$('#moveForm').attr("action", "<?php echo $sweb['write_after_url']; ?>");
	$('#moveForm').submit();
}

// 저장
function fn_submit(){
	
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
				fn_parent_view();

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
			//alert("처리 중 오류가 발생하였습니다. 다시 시도해주세요.");
			alert(jqXHR.responseText + "//" + textStatus + "//" + errorThrown);
		},
		complete : function(){
			loadingEnd();
		}
	});
	
	return false;
}
</script>