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
	goto_url(G5_BBS_URL . "/login.php?url=/page/team_schedule/team_result.php?ts_id=".$ts_id);
}

if($parent['te_is_schedule'] || $is_admin_team || $is_member_team){

}else{
	alert("접근 권한이 없습니다.");
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

$total_join = 0;
$date = "";
if($view[$prefix.'start_date'] == $view[$prefix.'end_date']){
	$date .= $view[$prefix.'start_date'] . " (".get_yoil($view[$prefix.'start_date']).") <span>" . $view[$prefix.'start_time'] . "~" . $view[$prefix.'end_time']."</span>";
}else{
	$date .= $view[$prefix.'start_date'] . " (".get_yoil($view[$prefix.'start_date']).") " . $view[$prefix.'start_time'] . " ~ " . $view[$prefix.'end_date'] . " (".get_yoil($view[$prefix.'end_date']).") " . $view[$prefix.'end_time'];
}


$sql = "select count(*) as cnt from {$table_name_join} T1 where {$key_column} = '{$key}' and (sj_status='1' OR sj_is_guest = '1')";
$result = sql_fetch($sql);
$total_cnt = $result['cnt'];


// 기존 작동하던 코드 (팀 배정 기준)
$sql_all = "select * from {$table_name_join} T1 left outer join (
	select * from sweb_team_join where {$parent_key_column} = '{$parent_key}'
) T2 ON T1.mb_id = T2.mb_id
where {$key_column} = '{$key}' and (sj_status='1' OR sj_is_guest = '1')
order by sj_team asc";
$result_all = sql_query($sql_all);

$list = array();      // Staff 
$list2 = array();     // Player (팀 배정된 사람들)

while($row = sql_fetch_array($result_all)){

	// 팀 배정이 되어있으면 Player로 처리
	if(isset($row['sj_team']) && $row['sj_team'] !== null && $row['sj_team'] !== ""){
		// 🔥 조건 수정: 0도 Player로 인정
		if($row['sj_gubun'] == '258' || $row['sj_gubun'] == '0' || $row['sj_is_guest'] == '1') {
			$list2[$row['sj_team']][] = $row;
			$total_join++;
		} else {
			// Coach, Manager 등
			$list[] = $row;
		}
	} else {
		// 팀 배정 안된 사람은 Staff
		$list[] = $row;
	}
}


$kakao_name = $parent['te_name'];
$kakao_title = "";
$kakao_content = $parent['te_name']."\\n" . strip_tags($date) . "\\n";
$kakao_content .= "장소 : " . $view['ts_location']."\\n"; 
$kakao_content .= "Staff 참석인원 : " . number_format(count($list)) . "명\\n";
$kakao_content .= "Player 참석인원 : " . number_format($total_join) . "명\\n";


if(count($list)){

	$kakao_content .= "\\n Staff ("  . count($list) . "명) : ";

	$cnt = 0;
	foreach($list as $k => $v){

		if($cnt > 0) $kakao_content .= ", ";
		if($v['sj_is_guest']){
			$kakao_content .= $v['sj_name'];
		}else{
			$kakao_content .= $v['tj_name'];
		}

		$cnt++;
	}
	$kakao_content .= "\\n";
}
foreach($list2 as $k => $v){
	$kakao_content .= "\\n" . chr($k + 65) . "팀(" . count($v) . "명) : ";

	$cnt = 0;
	foreach($v as $k2 => $v2){

		if($cnt > 0) $kakao_content .= ", ";
		if($v2['sj_is_guest']){
			$kakao_content .= $v2['sj_name'];
		}else{
			$kakao_content .= $v2['tj_name'];
		}

		$cnt++;
	}

	$kakao_content .= "\\n";
}
$kakao_img = "";
$f_path = G5_DATA_PATH . "/file/team/";
$f_url = G5_DATA_URL . "/file/team/";
$thumb = thumbnail($parent['file_rename'], $f_path, $f_path, 600, 0, false, false, 'center', false, '80/0.5/3');
if($thumb) $kakao_img = $f_url . $thumb;
$kakao_url = G5_URL . "/page/team_schedule/team_result.php?ts_id=".$ts_id."&te_id=".$te_id;


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


<style>
.team-wrap .btn-box{text-align: center; margin-top: 20px; display: flex; gap:10px;justify-content: center;}
.team-wrap .btn-box button{background: #fff; color: #000; border: 0; line-height: 40px; padding: 0 20px; border-radius:5px; display: flex; align-items: center; gap:5px}
.team-wrap .btn-box button img{width: 25px;}
.team-wrap .btn-box button.kakao-btn {background: #f2da00;color: #000;}

</style>
	<div class="team-wrap">
		<div class="team-box">

			<?php if(count($list)){ ?>
			<div class="staff team">
				<h2>Staff : <?php echo number_format(count($list)); ?></h2>
				<?php for($i = 0; $i < count($list); $i++){ 
					$row = $list[$i];
					?>
				<ul class="<?php echo strtolower($arr_join_gubun[$row['sj_gubun']]); ?>">
					<li>
						<span><?php echo $row['tj_number']; ?></span>
						<p><?php echo $row['tj_name']; ?></p>				
					</li>
				</ul>
				<?php } ?>
			</div>
			<?php } ?>


			<?php foreach($list2 as $k => $v){ ?>
			<div class="team">
				<h2><?php echo chr($k + 65); ?> team : <?php echo number_format(count($v)); ?></h2>
				<ul class="player">
					<?php for($i = 0; $i < count($v); $i++){ ?>
					<li class="<?php echo $i == 0 ? "captain" : ""; ?>">
						<span><?php echo $v[$i]['tj_number'] ? $v[$i]['tj_number'] : "-"; ?></span>
						<p><?php echo (!$v[$i]['sj_is_guest']) ? $v[$i]['tj_name'] : $v[$i]['sj_name']; ?></p>
					</li>
					<?php } ?>
				</ul>
			</div>
			<?php } ?>
		</div>


		<div class="btn-box">
			<button onclick="fn_team();return false;" class="dont"><span class="material-symbols-outlined">arrow_back</span> 다시 짜기</button>
			<button onclick="Kakao_sendMsg('<?php echo $kakao_img; ?>', '<?php echo $kakao_title; ?>', '<?php echo $kakao_content; ?>', '<?php echo $kakao_url; ?>');return false;" class="kakao-btn"><img src="<?php echo G5_IMG_URL ?>/kakao.png" alt="카톡 공유"> 카톡 공유</button>
		</div>


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

// team 호출
function fn_team(){
	document.moveForm.<?php echo $key_column; ?>.value = '<?php echo $key; ?>';
	$('#moveForm').attr("action", "<?php echo $sweb['team_url']; ?>");
	$('#moveForm').submit();
}


</script>

<script src="//developers.kakao.com/sdk/js/kakao.min.js" charset="utf-8"></script>
<script src="<?php echo G5_JS_URL; ?>/kakaolink.js" charset="utf-8"></script>
<script type='text/javascript'>
//<![CDATA[
// 사용할 앱의 Javascript 키를 설정해 주세요.
Kakao.init("bbff4333946e27c2b17ba7ee2533d7cf");

function Kakao_sendMsg(img, title, description, link) {
	Kakao.Link.sendDefault({
		objectType: 'text',
		text : description,
		link: {
			mobileWebUrl: link,
			webUrl: link
		}
		
	});
}









//]]>
</script>
