<?php if ($te_id) {?><style>	#container_title{display: none;}</style><?php } ?>


<div id="team_view">
	<div class="t-about round">
		<div class="t-name flex gap10">
			<div class="t-logo">
				<?php if($thumb){ ?>
				<img src="<?php echo $f_url . $thumb; ?>" alt="" />
				<?php } else { echo "<img src='".G5_URL."/img/symbol3.png' alt='"._t('기본 이미지')."' />"; }?>
			</div>
			<div class="info">
				<h3>
					<?php echo ($g5['lang'] != 'ko_KR' && !empty($parent['te_name_eng'])) ? $parent['te_name_eng'] : $parent['te_name']; ?>
					<?php if($is_admin_team){ ?>
					<a href="<?php echo G5_URL?>/page/team/form.php?te_id=<?php echo $te_id; ?>" class="team_adm">
						<i class="fa fa-cog" aria-hidden="true"></i>
					</a>
					<?php } ?>
				</h3>
				<i><?php echo $parent['te_location1'] ? fn_getCodeValue($parent['te_location1']) : ""; ?> <?php echo $parent['te_location2'] ? fn_getCodeValue($parent['te_location2']) : ""; ?></i>
				<p class="skill">
					<u><span class="material-symbols-outlined">sports_soccer</span><?php echo _t($parent['te_skill']); ?></u>
					<u><span class="material-symbols-outlined">groups_3</span><?php echo _t($parent['te_age_group']); ?></u>
				</p>
				<p><?php echo $parent['te_content']; ?></p>
			</div>
		</div>
	</div>
</div>

<div id="t-tab">
	<ul>
		<li class="<?php echo isset($_GET['bo_table']) ? "on" : ""; ?>"><a href="/bbs/board.php?bo_table=team_<?php echo $te_id; ?>"><i class="fa fa-info" aria-hidden="true"></i> <?php echo _t('게시판');?></a></li>
		<li class="<?php echo $code2 == "team_schedule" ? "on" : ""; ?>"><a href="<?php echo G5_URL?>/page/team_schedule/list.php?te_id=<?php echo $te_id; ?>"><i class="fa fa-calendar" aria-hidden="true"></i> <?php echo _t('일정');?></a></li>
		<li class="<?php echo $code2 == "team_join" && $code3 == "list" ? "on" : ""; ?>"><a href="<?php echo G5_URL?>/page/team_join/list.php?te_id=<?php echo $te_id; ?>"><i class="fa fa-users" aria-hidden="true"></i> <?php echo _t('팀원');?></a></li>	

	</ul>
</div>





<!-- 좋아요 호출 form start -->
<form method="post" id="goodForm" name="goodForm" action="">
	<input type="hidden" name="w" value="g" />
	<input type="hidden" name="te_id" value="<?php echo $te_id; ?>" />
</form>
<!-- // 좋아요 호출 form end -->


<script>
$('.team-toggle').click(function(){
	$( this ).parent().parent().toggleClass( 'close' )
});

// 좋아요 클릭
function fn_good(){
	if(<?php echo $is_member ? "1" : "0"; ?>){
	
		var formData = new FormData($('#goodForm')[0]);
		var url = "../team/update.php";

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
					var html = '';
					
					if(data.flag == 'GOOD'){
						html += '<i class="fa fa-heart" aria-hidden="true"></i> Like ' + number_format(data.cnt);
					}else{
						html += '<i class="fa fa-heart-o" aria-hidden="true"></i> Like ' + number_format(data.cnt);
					}
					$('#good_area').html(html);
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

	}else{
		alert("Available after login.");
	}

	return false;
}


</script>