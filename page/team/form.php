<?php 
include_once "./_common.php";

include_once "./setting.php";
include_once "../team/team.common.php";

$code3 = "write";
$g5['title'] = $sweb['write_title'];

include_once(G5_THEME_MOBILE_PATH.'/head.php');


if($key){
	$w = "u";
	$view = $parent;
	if (!$view) alert("등록된 자료가 없습니다.");

	//첨부파일
	$files = fileList($file_code, "", $key);

	//접근권한 체크
	fn_authCheck($sweb['write_level'], $view['mb_id']);
}else{
	$w = "";
	//접근권한 체크
	fn_authCheck($sweb['write_level'], "");

	$view[$prefix.'is_tel'] = 1;
	$view[$prefix.'is_player'] = 1;
	$view[$prefix.'is_join'] = 1;
	$view[$prefix.'is_schedule'] = 1;
}

?>


<div class="form_write">
<form method="post" id="submitForm" name="submitForm" action="<?php echo $sweb['action_url']; ?>" autocomplete="off">
	<input type="hidden" id="<?php echo $key_column; ?>" name="<?php echo $key_column; ?>" value="<?php echo $key; ?>" />
	<input type="hidden" name="w" value="<?php echo $w; ?>" />
	<input type="hidden" name="page" value="<?php echo $page; ?>">
	<?php foreach($sweb['list']['qstr'] as $key_qstr => $value_qstr){ ?>
	<input type="hidden" name="<?php echo $key_qstr; ?>" value="<?php echo $value_qstr; ?>">
	<?php } ?>

	<div class="eden_form_type2">
		<?php if($w == "u"){ ?>
		<div class="t-adm">
			<h3><?php echo _t('Administrator'); ?></h3>
			<div class="">
				<div class="form_div school-adm-box">
					<p><?php echo _t('현재 관리자 ID'); ?> : <b><?php echo $view['mb_id']; ?></b></p>			
					<p> <a href="#none" class="move-adm-btn"><?php echo _t('관리자 변경하기'); ?></a></p>
				</div>

				<div class="form_div move-adm-box" style="display:none">
					<label for=""><?php echo _t('변경할 관리자 아이디를 직접 입력해 주세요.'); ?></label>
					<div class="flex gap10">
						<input type="text" id="admin_id" name="admin_id" placeholder="<?php echo _t('관리자 ID 입력'); ?>">
						<a href="#none" onclick="fn_changeAdmin();return false;"><?php echo _t('변경'); ?></a>
					</div>
				</div>

				<script>
				$('.move-adm-btn').click(function(){
				  $('.move-adm-box').toggle();
				});
				</script>

			</div>
		</div>
		<?php }else{ ?>
		<div class="form_div agree">
			<label for=""><?php echo _t('이용약관'); ?></label>
			
			<div class="textarea terms" style="max-height:150px; overflow:auto; ">
				<!--<textarea readonly="readonly"></textarea>-->
				<pre>
					<?php include "./terms.php"; ?>
				</pre>
			</div>
			<div class="agree-chk">
				<div class="checkbox-wrapper-15">
				  <input class="inp-cbx" id="cbx-15" type="checkbox" name="agree1"  value="1" style="display: none;"/>
				  <label class="cbx" for="cbx-15">
					<span>
					  <svg width="12px" height="9px" viewbox="0 0 12 9">
						<polyline points="1 5 4 8 11 1"></polyline>
					  </svg>
					</span>
					<span><?php echo _t('동의합니다'); ?></span>
				  </label>
				</div>
			</div>


		</div>
		<?php } ?>

		<?php $column = $sweb['column']['name']; ?>
		<div class="form_01 form_div">
			<label for="<?php echo $prefix . $column['name']; ?>"><?php echo _t($column['name_kor']); ?></label>
			<input type="text" id="<?php echo $prefix . $column['name']; ?>" name="<?php echo $prefix . $column['name']; ?>" value="<?php echo $view[$prefix.$column['name']]; ?>" class="frm_input <?php echo $column['class'] . " " . $column['required']; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> size="100" placeholder="<?php echo _t($column['msg']); ?>" />
		</div>


		<?php $column = $sweb['column']['name_eng']; ?>
		<div class="form_01 form_div">
			<label for="<?php echo $prefix . $column['name']; ?>"><?php echo _t($column['name_kor']); ?></label>
			<input type="text" id="<?php echo $prefix . $column['name']; ?>" name="<?php echo $prefix . $column['name']; ?>" value="<?php echo $view[$prefix.$column['name']]; ?>" class="frm_input <?php echo $column['class'] . " " . $column['required']; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> size="100" placeholder="<?php echo _t($column['msg']); ?>" />
		</div>

		<div class="form_div  ">
			<ul>
				<li>
					<?php $column = $sweb['column']['tel']; ?>
					<label for="<?php echo $prefix . $column['name']; ?>"><?php echo _t($column['name_kor']); ?></label>
					<input type="tel"  inputmode="numeric" pattern="[0-9]*"  id="<?php echo $prefix . $column['name']; ?>" name="<?php echo $prefix . $column['name']; ?>" value="<?php echo $view[$prefix.$column['name']]; ?>" class="frm_input <?php echo $column['class'] . " " . $column['required']; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> placeholder="<?php echo _t($column['msg']); ?>" />
				</li>
				<li>
				</li>
			</ul>
		</div>

		<?php $column = $sweb['column']['content']; ?>
		<div class="form_01 form_div">
			<label for="<?php echo $prefix . $column['name']; ?>"><?php echo _t($column['name_kor']); ?></label>
			 <?php //echo editor_html($prefix.$column['name'], $view[$prefix.$column['name']], 0); ?>
			 <textarea id="<?php echo $prefix . $column['name']; ?>" name="<?php echo $prefix . $column['name']; ?>" class="frm_input <?php echo $column['class'] . " " . $column['required']; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> placeholder="<?php echo _t($column['msg']); ?>"><?php echo $view[$prefix.$column['name']]; ?></textarea>
		</div>


<?php $column = $sweb['column']['skill']; ?>
<div class="form_01 form_div">
    <label><?php echo _t($column['name_kor']); ?></label>
    <div class="flex gap10 radio" style="flex-wrap: wrap">
        <?php 
        $skill_options = $column['arr'];
        $current_skill = $view[$prefix.$column['name']];
        if(empty($current_skill)) {
            $current_skill = "하";
        }
        foreach($skill_options as $skill) {
            $checked = ($current_skill == $skill) ? "checked" : "";
        ?>
            <div class="radio-wrapper-16">
                <input type="radio" 
                       id="<?php echo $prefix . $column['name'] . '_' . $skill; ?>" 
                       name="<?php echo $prefix . $column['name']; ?>" 
                       value="<?php echo $skill; ?>" 
                       <?php echo $checked; ?> 
                       class="<?php echo $column['class'] . " " . $column['required']; ?>" 
                       <?php echo $column['readonly'] ? "disabled" : ""; ?>>
                <label for="<?php echo $prefix . $column['name'] . '_' . $skill; ?>"><?php echo _t($skill); ?></label>
            </div>
        <?php } ?>
    </div>
</div>

<?php $column = $sweb['column']['age_group']; ?>
<div class="form_01 form_div">
    <label><?php echo _t($column['name_kor']); ?></label>
    <div class="flex gap10 radio" style="flex-wrap: wrap">
        <?php 
        $age_options = $column['arr'];
        $current_age = $view[$prefix.$column['name']];
        
        if(empty($current_age)) {
            $current_age = "20대";
        }
        foreach($age_options as $age) {
            $checked = ($current_age == $age) ? "checked" : "";
        ?>
            <div class="radio-wrapper-16">
                <input type="radio" 
                       id="<?php echo $prefix . $column['name'] . '_' . preg_replace('/\s+/', '_', $age); ?>" 
                       name="<?php echo $prefix . $column['name']; ?>" 
                       value="<?php echo $age; ?>" 
                       <?php echo $checked; ?> 
                       class="<?php echo $column['class'] . " " . $column['required']; ?>" 
                       <?php echo $column['readonly'] ? "disabled" : ""; ?>>
                <label for="<?php echo $prefix . $column['name'] . '_' . preg_replace('/\s+/', '_', $age); ?>"><?php echo _t($age); ?></label>
            </div>
        <?php } ?>
    </div>
</div>


		<?php $column = $sweb['column']['day']; ?>
		<div class="form_01 form_div">
			<label for="<?php echo $prefix . $column['name']; ?>"><?php echo _t($column['name_kor']); ?></label>
			<input type="text" id="<?php echo $prefix . $column['name']; ?>" name="<?php echo $prefix . $column['name']; ?>" value="<?php echo $view[$prefix.$column['name']]; ?>" class="frm_input <?php echo $column['class'] . " " . $column['required']; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> size="100" placeholder="<?php echo _t($column['msg']); ?>" />
		</div>

		
		<div class="form_01 form_div ">
			<ul class="flex col-2">
				<li>
					<?php $column = $sweb['column']['stadium1']; ?>
					<div class="form_01 form_div">
						<label for="<?php echo $prefix . $column['name']; ?>"><?php echo _t('이용 경기장 #1'); ?></label>
						<input type="text" id="<?php echo $prefix . $column['name']; ?>" name="<?php echo $prefix . $column['name']; ?>" value="<?php echo $view[$prefix.$column['name']]; ?>" class="frm_input <?php echo $column['class'] . " " . $column['required']; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> size="100" placeholder="<?php echo _t($column['msg']); ?>" />
					</div>
				</li>
				<li>
					<?php $column = $sweb['column']['stadium2']; ?>
					<div class="form_01 form_div">
						<label for="<?php echo $prefix . $column['name']; ?>"><?php echo _t('이용 경기장 #2'); ?></label>
						<input type="text" id="<?php echo $prefix . $column['name']; ?>" name="<?php echo $prefix . $column['name']; ?>" value="<?php echo $view[$prefix.$column['name']]; ?>" class="frm_input <?php echo $column['class'] . " " . $column['required']; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> size="100" placeholder="<?php echo _t($column['msg']); ?>" />
					</div>
				</li>
			</ul>
		</div>

		<div class="form_01 form_div ">
			<ul class="flex col-2">
				<li>
					<?php $column = $sweb['column']['location1']; ?>	
					<div class="form_div form_table">
						<label for="<?php echo $prefix.$column['name']; ?>"><?php echo _t($column['name_kor']); ?></label>
						<div class="form_div ">
							<select id="<?php echo $prefix.$column['name']; ?>" name="<?php echo $prefix.$column['name']; ?>" class="<?php echo $column['class'] . " " . $column['required']; ?>">
								<?php echo fn_getCodeListSelectOption($column['arr'], $view[$prefix.$column['name']], $column['is_use'], $column['defaultValue']); ?>
							</select>
						</div>
					</div>
				</li>
				<li>
					<?php $column = $sweb['column']['location2']; ?>
					<div class="form_div form_table">
						<label for="<?php echo $prefix.$column['name']; ?>"><?php echo _t($column['name_kor']); ?></label>
						<div class="form_div ">
							<select id="<?php echo $prefix.$column['name']; ?>" name="<?php echo $prefix.$column['name']; ?>" class="<?php echo $column['class'] . " " . $column['required']; ?>">
							</select>
						</div>
					</div>			
				</li>
			</ul>
		</div>




		<div class="form_div options">
			<label for=""><?php echo _t('option'); ?></label>
	
			<?php $column = $sweb['column']['is_join']; ?>
			<div class="form_div ch-toggle-box">
				<dl>
					<dt><?php echo _t($column['name_kor']); ?> <small>(<?php echo _t($column['msg']); ?>)</small></dt>
					<dd>
						<div class="checkbox-wrapper-8">
							<input type="checkbox" id="<?php echo $prefix.$column['name']; ?>" name="<?php echo $prefix.$column['name']; ?>" value="<?php echo $column['defaultValue']; ?>" <?php echo $view[$prefix.$column['name']] == $column['defaultValue'] ? "checked='checked'" : ""; ?> class="tgl tgl-skewed <?php echo $column['class'] . " " . $column['required']; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> />
							<label class="tgl-btn" data-tg-off="<?php echo _t('OFF'); ?>" data-tg-on="<?php echo _t('ON'); ?>" for="<?php echo $prefix . $column['name']; ?>"></label>
						</div>
					</dd>
				</dl>
			</div>

			<?php $column = $sweb['column']['is_autojoin']; ?>
			<div class="form_div ch-toggle-box">
				<dl>
					<dt><?php echo _t($column['name_kor']); ?> <small>(<?php echo _t($column['msg']); ?>)</small></dt>
					<dd>
						<div class="checkbox-wrapper-8">
							<input type="checkbox" id="<?php echo $prefix.$column['name']; ?>" name="<?php echo $prefix.$column['name']; ?>" value="<?php echo $column['defaultValue']; ?>" <?php echo $view[$prefix.$column['name']] == $column['defaultValue'] ? "checked='checked'" : ""; ?> class="tgl tgl-skewed <?php echo $column['class'] . " " . $column['required']; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> />
							<label class="tgl-btn" data-tg-off="<?php echo _t('OFF'); ?>" data-tg-on="<?php echo _t('ON'); ?>" for="<?php echo $prefix . $column['name']; ?>"></label>
						</div>
					</dd>
				</dl>
			</div>

			<?php $column = $sweb['column']['is_tel']; ?>
			<div class="form_div ch-toggle-box">	
				<dl>
					<dt><?php echo _t($column['name_kor']); ?> <small>(<?php echo _t($column['msg']); ?>)</small></dt>
					<dd>
						<div class="checkbox-wrapper-8">
							<input type="checkbox" id="<?php echo $prefix.$column['name']; ?>" name="<?php echo $prefix.$column['name']; ?>" value="<?php echo $column['defaultValue']; ?>" <?php echo $view[$prefix.$column['name']] == $column['defaultValue'] ? "checked='checked'" : ""; ?> class="tgl tgl-skewed <?php echo $column['class'] . " " . $column['required']; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> />
							<label class="tgl-btn" data-tg-off="<?php echo _t('OFF'); ?>" data-tg-on="<?php echo _t('ON'); ?>" for="<?php echo $prefix . $column['name']; ?>"></label>
						</div>

					</dd>
				</dl>
			</div>


			<?php $column = $sweb['column']['is_player']; ?>
			<div class="form_div ch-toggle-box">
				<dl>
					<dt><?php echo _t($column['name_kor']); ?> <small>(<?php echo _t($column['msg']); ?>)</small></dt>
					<dd>
						<div class="checkbox-wrapper-8">
							<input type="checkbox" id="<?php echo $prefix.$column['name']; ?>" name="<?php echo $prefix.$column['name']; ?>" value="<?php echo $column['defaultValue']; ?>" <?php echo $view[$prefix.$column['name']] == $column['defaultValue'] ? "checked='checked'" : ""; ?> class="tgl tgl-skewed <?php echo $column['class'] . " " . $column['required']; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> />
							<label class="tgl-btn" data-tg-off="<?php echo _t('OFF'); ?>" data-tg-on="<?php echo _t('ON'); ?>" for="<?php echo $prefix . $column['name']; ?>"></label>
						</div>

					</dd>
				</dl>
			</div>

			<?php $column = $sweb['column']['is_schedule']; ?>
			<div class="form_div ch-toggle-box">
				<dl>
					<dt><?php echo _t($column['name_kor']); ?> <small>(<?php echo _t($column['msg']); ?>)</small></dt>
					<dd>
						<div class="checkbox-wrapper-8">
							<input type="checkbox" id="<?php echo $prefix.$column['name']; ?>" name="<?php echo $prefix.$column['name']; ?>" value="<?php echo $column['defaultValue']; ?>" <?php echo $view[$prefix.$column['name']] == $column['defaultValue'] ? "checked='checked'" : ""; ?> class="tgl tgl-skewed <?php echo $column['class'] . " " . $column['required']; ?>" <?php echo $column['readonly'] ? "readonly='readonly'" : ""; ?> />
							<label class="tgl-btn" data-tg-off="<?php echo _t('OFF'); ?>" data-tg-on="<?php echo _t('ON'); ?>" for="<?php echo $prefix . $column['name']; ?>"></label>
						</div>
					</dd>
				</dl>
			</div>
		</div>

		<div class="form_div">
		  <label><?php echo _t('팀 로고 이미지'); ?></label>
		  <?php 
		  $fk = "logo";
		  $file = $files[$fk][0];
		  ?>
		  <input type="hidden" name="file_attach[]" value="<?php echo $fk; ?>" />
		  
		  <div class="custom-file-upload">
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






		<div class="form_01 form_div" style="display:none">
			<?php 
			$fk = "image"; 
			$max_count = 10;
			?>
			<input type="hidden" name="file_attach[]" value="<?php echo $fk; ?>" />
			<label for=""><?php echo _t('이미지'); ?>  <a href="#none;" onclick="fn_add<?php echo $fk; ?>();return false;"><?php echo _t('+'); ?></a></label>
        
			<div id="<?php echo $fk; ?>_area">
            <?php for($i = 0; $i < count($files[$fk]); $i++){ 
				$file = $files[$fk][$i]; ?>
				<script>$(document).ready(function(){fn_add<?php echo $fk; ?>("<?php echo $file['file_id']; ?>", "<?php echo $file['file_name']; ?>", "<?php echo $file['file_rename']; ?>");});</script>
			<?php } ?>

			<?php if($i == 0){ ?>
				<script>$(document).ready(function(){fn_add<?php echo $fk; ?>("", "", "");});</script>
			<?php } ?>

			<script>
			var <?php echo $fk; ?>_cnt = 0;
			function fn_add<?php echo $fk; ?>(file_id, file_name, file_rename){
				if(<?php echo $max_count; ?> <= <?php echo $fk; ?>_cnt){
					alert("<?php echo _t('더이상 추가가 불가능합니다.'); ?>");
					return false;
				}
				var html = '';
				html += '<div class="form_div file" id="li_<?php echo $fk; ?>_'+<?php echo $fk; ?>_cnt+'">';
				html += '<ul>';
				html += '<li>';
				html += '<input type="file" name="file_<?php echo $fk; ?>[]" id="file_<?php echo $fk; ?>_'+<?php echo $fk; ?>_cnt+'" />';
				
				if(file_id){
				html += '<a href="<?php echo G5_SWEB_MODULE_URL; ?>/file/download.php?file_id='+file_id+'">'+file_name+'</a> | <input type="checkbox" id="file_del_<?php echo $fk; ?>'+<?php echo $fk; ?>_cnt+'" name="file_del_<?php echo $fk; ?>[]" value="1" /><label for="file_del_<?php echo $fk; ?>'+<?php echo $fk; ?>_cnt+'"><?php echo _t('체크시 삭제'); ?></label>';
				}else{
				html += '<a href="#none;" onclick="fn_del<?php echo $fk; ?>('+<?php echo $fk; ?>_cnt+');return false;">x</a>';
				}
				html += '</li>';
				html += '</ul>';
				html += '</div>';

				$('#<?php echo $fk; ?>_area').append(html);
				<?php echo $fk; ?>_cnt++;
			}

			function fn_del<?php echo $fk; ?>(id){
				$('#li_<?php echo $fk; ?>_'+id).remove();
				<?php echo $fk; ?>_cnt--;
			}
			</script>
			</div>
		</div>
	</div>

	<div class="btn2 col-2">
		<a href="#none" onclick="history.back();return false;" class="gray"><?php echo _t('취소'); ?></a>
		<button type="submit" class="submit_btn2" value="Confirm" onclick="fn_submit();return false;"><?php echo _t('확인'); ?></button>
	</div>



<?php if($w == "u" && ($is_admin || $member['mb_id'] == $view['mb_id'])){ ?>
<!-- 팀 삭제 버튼 영역 -->
<div class="btn2 col-1 team-delete-section" style="margin-top: 20px; border-top: 1px solid #ddd; padding-top: 20px;">
    <button type="button" class="btn-delete-team" onclick="showDeleteModal();return false;" 
            style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 4px;">
        <?php echo _t('팀 영구삭제'); ?>
    </button>
    <p style="font-size: 12px; color: #666; margin-top: 5px;">
        <?php echo _t('주의: 삭제된 팀은 복구할 수 없습니다.'); ?>
    </p>
</div>
<?php } ?>




</form>
</div>
<!-- // form end -->




<!-- 팀 삭제 확인 모달 -->
<?php if($w == "u" && ($is_admin || $member['mb_id'] == $view['mb_id'])){ ?>
<div id="deleteTeamModal" class="delete-modal" style="display: none;">
    <div class="delete-modal-overlay" onclick="closeDeleteModal()"></div>
    <div class="delete-modal-content">
        <div class="delete-modal-header">
            <h3><?php echo _t('팀 영구삭제 확인'); ?></h3>
            <button type="button" class="delete-modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        
        <div class="delete-modal-body">
            <div class="delete-warning">
                <p><strong><?php echo _t('경고: 이 작업은 되돌릴 수 없습니다!'); ?></strong></p>
                <p><?php echo _t('다음 데이터가 모두 삭제됩니다:'); ?></p>
                <ul>
                    <li><?php echo _t('• 모든 팀원 정보'); ?></li>
                    <li><?php echo _t('• 모든 일정 및 경기 기록'); ?></li>
                    <li><?php echo _t('• 팀 게시판 및 게시글'); ?></li>
                    <li><?php echo _t('• 업로드된 파일들'); ?></li>
                    <li><?php echo _t('• 기타 모든 관련 데이터'); ?></li>
                </ul>
            </div>

            <div class="delete-verification">
                <div class="form-group">
                    <label><?php echo _t('팀명을 정확히 입력하세요'); ?>:</label>
                    <input type="text" id="teamNameVerify" placeholder="<?php echo $view['te_name']; ?>" 
                           style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <small style="color: #666;"><?php echo _t('현재 팀명'); ?>: <strong><?php echo $view['te_name']; ?></strong></small>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label><?php echo _t('DELETE를 입력하세요'); ?>:</label>
                    <input type="text" id="deleteConfirm" placeholder="DELETE" 
                           style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label><?php echo _t('삭제 사유 (선택사항)'); ?>:</label>
                    <textarea id="deleteReason" placeholder="<?php echo _t('삭제 사유를 입력하세요...'); ?>" 
                              style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; height: 60px;"></textarea>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="deleteAgreement" style="margin: 0;">
                        <label for="deleteAgreement" style="margin: 0; font-size: 14px;">
                            <?php echo _t('위 내용을 모두 이해했으며, 데이터 복구가 불가능함을 동의합니다.'); ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="delete-modal-footer">
            <button type="button" onclick="closeDeleteModal()" 
                    style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 4px; margin-right: 10px;">
                <?php echo _t('취소'); ?>
            </button>
            <button type="button" id="confirmDeleteBtn" onclick="executeDelete()" disabled
                    style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 4px;">
                <?php echo _t('영구삭제 실행'); ?>
            </button>
        </div>
    </div>
</div>

<!-- 팀 삭제 처리 폼 -->
<form method="post" id="deleteTeamForm" name="deleteTeamForm" action="<?php echo G5_URL; ?>/page/team/delete.php">
    <input type="hidden" name="te_id" value="<?php echo $key; ?>" />
    <input type="hidden" name="team_name_verify" id="teamNameVerifyHidden" />
    <input type="hidden" name="delete_confirm" id="deleteConfirmHidden" />
    <input type="hidden" name="delete_reason" id="deleteReasonHidden" />
</form>
<?php } ?>




<!-- page 이동 form start -->
<form method="get" id="moveForm" name="moveForm">
	<input type="hidden" name="page" value="<?php echo $page; ?>">
	<input type="hidden" name="<?php echo $key_column; ?>" value="<?php echo $key; ?>" />
	<?php foreach($sweb['list']['qstr'] as $key_qstr => $value_qstr){ ?>
	<input type="hidden" name="<?php echo $key_qstr; ?>" value="<?php echo $value_qstr; ?>">
	<?php } ?>
</form>
<!-- // page 이동 form end -->

<!-- delete form start -->
<form method="post" id="deleteForm" name="deleteForm" action="<?php echo $sweb['action_url']; ?>">
	<input type="hidden" id="<?php echo $key_column; ?>" name="<?php echo $key_column; ?>" value="<?php echo $key; ?>" />
	<input type="hidden" name="w" value="d" />
</form>
<!-- // delete form start -->



<!-- CSS와 JavaScript를 별도 섹션으로 분리 -->
<?php if($w == "u" && ($is_admin || $member['mb_id'] == $view['mb_id'])){ ?>
<style>
/* 모달 스타일 */
.delete-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
}

.delete-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
}

.delete-modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 8px;
    min-width: 400px;
    max-width: 90vw;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.delete-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #ddd;
    background: #f8f9fa;
}

.delete-modal-header h3 {
    margin: 0;
    color: #dc3545;
}

.delete-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.delete-modal-body {
    padding: 20px;
}

.delete-warning {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
}

.delete-warning p {
    margin: 0 0 10px 0;
    color: #721c24;
}

.delete-warning ul {
    margin: 10px 0 0 20px;
    color: #721c24;
}

.delete-verification .form-group {
    margin-bottom: 15px;
}

.delete-verification label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}

.delete-modal-footer {
    padding: 20px;
    border-top: 1px solid #ddd;
    background: #f8f9fa;
    text-align: right;
}

.team-delete-section {
    text-align: center;
}

@media (max-width: 768px) {
    .delete-modal-content {
        min-width: auto;
        margin: 20px 0;
        width: calc(100% - 40px);
    }
}
</style>

<script>
// 삭제 모달 관련 함수들 (기존 함수명과 충돌 방지를 위해 이름 변경)
function showDeleteModal() {
    document.getElementById('deleteTeamModal').style.display = 'block';
    checkDeleteForm(); // 초기 상태 체크
}

function closeDeleteModal() {
    document.getElementById('deleteTeamModal').style.display = 'none';
    // 폼 초기화
    document.getElementById('teamNameVerify').value = '';
    document.getElementById('deleteConfirm').value = '';
    document.getElementById('deleteReason').value = '';
    document.getElementById('deleteAgreement').checked = false;
    document.getElementById('confirmDeleteBtn').disabled = true;
}

function checkDeleteForm() {
    const teamName = document.getElementById('teamNameVerify').value.trim();
    const deleteConfirm = document.getElementById('deleteConfirm').value.trim();
    const agreement = document.getElementById('deleteAgreement').checked;
    const expectedTeamName = '<?php echo addslashes($view['te_name']); ?>';
    
    // 모든 조건을 만족하는지 확인
    const isValid = (
        teamName === expectedTeamName &&
        deleteConfirm === 'DELETE' &&
        agreement
    );
    
    document.getElementById('confirmDeleteBtn').disabled = !isValid;
    
    // 버튼 스타일 변경
    if (isValid) {
        document.getElementById('confirmDeleteBtn').style.opacity = '1';
    } else {
        document.getElementById('confirmDeleteBtn').style.opacity = '0.6';
    }
}

function executeDelete() {
    if (!confirm('<?php echo _t("정말로 팀을 영구삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다!"); ?>')) {
        return false;
    }
    
    // 폼 데이터 설정
    document.getElementById('teamNameVerifyHidden').value = document.getElementById('teamNameVerify').value;
    document.getElementById('deleteConfirmHidden').value = document.getElementById('deleteConfirm').value;
    document.getElementById('deleteReasonHidden').value = document.getElementById('deleteReason').value;
    
    // AJAX로 처리
    var formData = new FormData(document.getElementById('deleteTeamForm'));
    
    fetch(document.getElementById('deleteTeamForm').action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.status) {
            alert(data.msg);
            if(data.url) {
                location.href = data.url;
            } else {
                location.href = '<?php echo G5_URL; ?>';
            }
        } else {
            alert('<?php echo _t("오류: "); ?>' + data.msg);
            document.getElementById('confirmDeleteBtn').disabled = false;
            document.getElementById('confirmDeleteBtn').innerText = '<?php echo _t("영구삭제 실행"); ?>';
        }
    })
    .catch(error => {
        alert('<?php echo _t("처리 중 오류가 발생했습니다."); ?>');
        document.getElementById('confirmDeleteBtn').disabled = false;
        document.getElementById('confirmDeleteBtn').innerText = '<?php echo _t("영구삭제 실행"); ?>';
    });
    
    document.getElementById('confirmDeleteBtn').disabled = true;
    document.getElementById('confirmDeleteBtn').innerText = '<?php echo _t("삭제 중..."); ?>';
    
    return false;
}

// 입력 필드 변경 시 실시간 검증
document.addEventListener('DOMContentLoaded', function() {
    const fields = ['teamNameVerify', 'deleteConfirm', 'deleteAgreement'];
    fields.forEach(function(fieldId) {
        const element = document.getElementById(fieldId);
        if(element) {
            element.addEventListener('input', checkDeleteForm);
            element.addEventListener('change', checkDeleteForm);
        }
    });
    
    // ESC 키로 모달 닫기
    document.addEventListener('keyup', function(e) {
        if (e.keyCode === 27) { // ESC
            closeDeleteModal();
        }
    });
});
</script>
<?php } ?>




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
				
			}else{
				alert(data.msg);

				return false;
			}

			if(data.reload){
				location.reload();
			}

			if(data.url){
				location.href= data.url;
			}
		},
		error : function(jqXHR, textStatus, errorThrown){
			//alert("<?php echo _t('처리 중 오류가 발생하였습니다. 다시 시도해주세요.'); ?>");
			alert(jqXHR.responseText + "//" + textStatus + "//" + errorThrown);
		},
		complete : function(){
			loadingEnd();
		}

	});
	return false;
}


function fn_changeAdmin(){
	if(confirm("<?php echo _t('작성하신 ID로 관리자권한을 위임하시겠습니까?'); ?>")){
		var formData = new FormData($('#submitForm')[0]);
		formData.append('w', 'c');
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
					
				}else{
					$('#error_area').val(data.msg + " " + data.error);
					$('#error_area').show();
					alert(data.msg);

					return false;
				}

				if(data.reload){
					location.reload();
				}

				if(data.url){
					location.href= data.url;
				}
			},
			error : function(jqXHR, textStatus, errorThrown){
				//alert("<?php echo _t('처리 중 오류가 발생하였습니다. 다시 시도해주세요.'); ?>");
				alert(jqXHR.responseText + "//" + textStatus + "//" + errorThrown);
			},
			complete : function(){
				loadingEnd();
			}

		});
	}
	return false;
}


// 삭제
function fn_delete(){
	if(confirm("<?php echo _t('삭제하시겠습니까?'); ?>")){
		var formData = new FormData($('#deleteForm')[0]);
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
					fn_list();

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
				//alert("<?php echo _t('처리 중 오류가 발생하였습니다. 다시 시도해주세요.'); ?>");
				alert(jqXHR.responseText + "//" + textStatus + "//" + errorThrown);
			},
			complete : function(){
				loadingEnd();
			}
		});
	}
	return false;
}



/*************************** select 주소 script start ***********************************/


function fn_getGugun(value){
	$.ajax({
		url : g5_url + '/sweb/module/juso/ajax.get_gugun.php',
		type : 'post',
		data : {"sido" : $('#<?php echo $prefix; ?>location1').val(), "gugun" : value},
		dataType : 'html',
		success : function(data, jqXHR, textStatus){
			$('#<?php echo $prefix; ?>location2').html(data);
			
			// Destroy and reinitialize nice-select after updating options
			if ($.fn.niceSelect) {
				$('#<?php echo $prefix; ?>location2').niceSelect('destroy');
				$('#<?php echo $prefix; ?>location2').niceSelect();
			}
		},
		error : function(jqXHR, textStatus, errorThrown){
			console.error("<?php echo _t('위치 데이터 로딩 오류:'); ?>", jqXHR.responseText);
		}
	});
}

$(document).ready(function(){
	fn_getGugun("<?php echo $view[$prefix.'location2']; ?>");
	
	$(".input_date").datepicker(datepicker_config);
	
	// Initialize nice-select on all select elements
	if ($.fn.niceSelect) {
		$('select').niceSelect();
	}
});

// When location1 changes, update location2
$('#<?php echo $prefix; ?>location1').on("change", function(){
	fn_getGugun("");
});


/*************************** select 주소 script end ***********************************/

</script>