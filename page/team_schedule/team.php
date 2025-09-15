<?php 
include_once "./_common.php";

include_once "./setting.php";
include_once "../team/team.common.php";
if($error_msg) alert($error_msg, G5_URL);
$code3 = "view";
$g5['title'] = $sweb['view_title'];

include_once(G5_THEME_MOBILE_PATH.'/head.php');

if($parent['te_is_schedule'] || $is_admin_team || $is_member_team){

}else{
	alert("접근 권한이 없습니다.");
	exit;
}

//접근권한 체크
fn_authCheck($sweb['view_level'], "");

// 스쿼드 수정 권한 체크
$can_edit_squad = ($is_admin_team || $is_admin == 'super' || $view['mb_id'] == $member['mb_id']);


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





//첨부파일
//$files = fileList($file_code, "", $key);


//참석자 count
$sql = "select count(*) as cnt from {$table_name_join} T1 where {$key_column} = '{$key}' and (sj_status='1' OR sj_is_guest = '1')";
$result = sql_fetch($sql);
$total_cnt = $result['cnt'];

?>

<link rel="stylesheet" href="./style.css">

<!-- view start -->
<?php include "../team/tab.php"; ?>

<div id="wrap" >


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
                        <p><?php echo $date; ?></p>
                        <div class="location"><?php echo $view[$prefix.'location']; ?></div>
                        <h4><?php echo $view[$prefix.'name']; ?></h4>
						<p>현재 참석 : <?php echo number_format($total_cnt); ?> 명</p>

                    </div>
                </li>
            </ul>
        </div>
    </div>

	<div class="team-add-wrap">	
		<form method="post" id="joinForm" name="joinForm" action="<?php echo $sweb['action_url']; ?>">
			<input type="hidden" name="w" value="j" />
			<input type="hidden" name="sj_id" value="" />
			<input type="hidden" name="sj_status" value="" />
			<input type="hidden" name="team_order" value="" />
			<input type="hidden" name="<?php echo $parent_key_column; ?>" value="<?php echo $parent_key; ?>" />
			<input type="hidden" name="<?php echo $key_column; ?>" value="<?php echo $key; ?>" />


			<div class="set team-qty">

				<dl>
					<dt>팀 수</dt>
					<dd>
						<select name="team_cnt" onchange="fn_team('');">
						<?php for($i = 2; $i <= 5; $i++){ ?>
							<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
						<?php } ?>
						</select>
					</dd>
				</dl>
			</div>
		
			<div id="result_area">
				
			</div>
		</form>
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
$(document).ready(function(){
	fn_team('');
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

// view 호출
function fn_view(){
	document.moveForm.<?php echo $key_column; ?>.value = '<?php echo $key; ?>';
	$('#moveForm').attr("action", "<?php echo $sweb['view_url']; ?>");
	$('#moveForm').submit();
}

function fn_result(){
	$('#moveForm').attr("action", "team_result.php");
	$('#moveForm').submit();
}

function fn_team(team_order){
	

	if($('#team_cnt').val() > <?php echo $total_cnt; ?>){
		alert("참여인원보다 팀 수가 많습니다.");
		return false;
	}
	
	document.joinForm.team_order.value = team_order;
	var formData = new FormData($('#joinForm')[0]);
		
	$.ajax({
		url:"ajax.team.php",
		type : 'POST',
		data : formData,
		dataType : 'html',
		asynsc : true,
		cache : false,
		contentType : false,
		processData : false,
		beforeSend:function(){
			loadingStart();
		},
		success : function(data){
			$("#result_area").html(data);
		},
		complete : function(){
			loadingEnd();
		}
	});
	
}


function fn_submit(){
	document.joinForm.w.value = "t";
	var formData = new FormData($('#joinForm')[0]);

	$.ajax({
		url:"./update.php",
		type : 'POST',
		data : formData,
		dataType : 'json',
		asynsc : true,
		cache : false,
		contentType : false,
		processData : false,
		beforeSend:function(){
			loadingStart();
		},
		success : function(data){
			fn_result();
			
		},
		complete : function(){
			loadingEnd();
		}
	});
	
}


</script>