<?php 
include_once "./_common.php";
include_once "./setting.php";
include_once "../team/team.common.php";

if($error_msg) echo $error_msg;

if($parent['te_is_schedule'] || $is_admin_team || $is_member_team){
}else{
	echo "접근 권한이 없습니다.";
	exit;
}

//접근권한 체크
fn_authCheck($sweb['view_level'], "");

$sql = "select count(*) as cnt from {$table_name_join} T1 where {$key_column} = '{$key}' and (sj_status='1' OR sj_is_guest = '1')";
$result = sql_fetch($sql);
$total_cnt = $result['cnt'];

$sql_order = " order by ";
if($team_order == "random"){
	$sql_order .= " rand() ";
}else{
	$sql_order .= " sj_id asc ";
}

$sql = "select * from {$table_name_join} T1 left outer join (
	select * from sweb_team_join where {$parent_key_column} = '{$parent_key}'
) T2 ON T1.mb_id = T2.mb_id
where {$key_column} = '{$key}' and (sj_status='1' OR sj_is_guest = '1')
{$sql_order}
";
$result = sql_query($sql);




$team = array();
for($i = 0; $i < $team_cnt; $i++){
	$team[$i]['name'] = chr(65+$i);
}

$cnt = 0;
for($i = 0; $i < $total_cnt; $i++){
	$cnt = floor($i % $team_cnt);
	if(!$team[$cnt]['cnt']) $team[$cnt]['cnt'] = 0;
	$team[$cnt]['cnt']++;
	$cnt++;
}


$cnt = 0;
$team_check = 0;
$list = array();
while($row = sql_fetch_array($result)){
	
	if($cnt >= $team[$team_check]['cnt']){
		$cnt = 0;
		$team_check++;
	}

	$row['sj_team'] = $team_check;
	$list[] = $row;

	$cnt++;
}

?>



<div class="teams-info">


	<div class="add-team-txt">
		<ul>
		<?php for($i = 0 ; $i < count($team); $i++){ ?>
			<li>
				<span>
					<i><?php echo $team[$i]['name']; ?></i>팀
					<b class="cnt_<?php echo $i; ?>"><?php echo number_format($team[$i]['cnt']); ?></b>명
				</span>
			</li>
		<?php } ?>
		</ul>
	</div>

	<div class="add-btn">
		<a href="#none" onclick="fn_team('random');"><span class="material-symbols-outlined">shuffle</span>랜덤으로</a>
		<a href="#none" onclick="fn_team('asc');"><span class="material-symbols-outlined">arrow_cool_down</span>순서대로</a>
	</div>




</div>


<div class="team-add">
	<?php for($i = 0 ; $i < count($list); $i++){ 
		$row = $list[$i];

		//print_r2($row);
	?>
		<dl class="player team_<?php echo $row['sj_team']; ?>">
			<dt>
				<i><?php echo ($row['tj_number']) ? $row['tj_number'] : '-'; ?></i>
				<span><?php echo $row['tj_name'] ? $row['tj_name'] : $row['sj_name']; ?></span>
				<b>(<?php echo $arr_gender[$row['tj_gender']] ? _t($arr_gender[$row['tj_gender']]) : '-'; ?>)</b>				
			</dt>
			<dd>
				<div class="form_div radio2">
					<ul>
						<?php for($k = 0; $k < $team_cnt; $k++){ ?>
						<li>
							<div class="radio-wrapper-8">
							  <label class="radio-wrapper-8" for="team<?php echo $row['sj_id'].$k; ?>">
								<input type="radio" class="chk_team" id="team<?php echo $row['sj_id'].$k; ?>" name="team[<?php echo $row['sj_id']; ?>]" value="<?php echo $k; ?>" <?php echo ($team_order && $row['sj_team'] == $k) ? "checked='checked'" : ""; ?>>
								<span><?php echo chr(65+$k); ?></span>
							  </label>
							</div>
						</li>
						<?php } ?>
					</ul>
				</div>
			</dd>
		</dl>
	<?php } ?>
	
</div>

<div class="btn-box">
	<button onclick="fn_view();return false;" class="dont">이전으로</button>
	<button onclick="fn_submit();return false;">현재 팀으로 저장</button>
</div>

<script>
$('.chk_team').bind('click', function(){
	
	<?php for($k = 0; $k < $team_cnt; $k++){ ?>
	$(this).parent().parent().parent().parent().parent().removeClass('team_<?php echo $k; ?>');
	<?php } ?>
	if(!$(this).parent().parent().parent().parent().parent().hasClass('team_'+$(this).val())){
		$(this).parent().parent().parent().parent().parent().addClass('team_'+$(this).val());

	}
	

	check_count();
});

function check_count(){
	<?php for($k = 0; $k < $team_cnt; $k++){ ?>
	var cnt = $('.team_<?php echo $k; ?>').length;
	$('.cnt_<?php echo $k; ?>').html(cnt);
	<?php } ?>
}
</script>