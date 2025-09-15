<?php
include_once "./_common.php";

include_once "./setting.php";
include_once "../team/team.common.php";
//if($error_msg) alert($error_msg, G5_URL);
$code3 = "list";
$g5['title'] = $sweb['list_title'];

if($parent['te_is_schedule'] || $is_admin_team || $is_member_team){
}else{
   if($page == 1){
   	$html = "
   	<div class='join-please'>
   		<p>" . _t('비공개된 정보입니다.') . "</p>
   		<p>" . _t('팀 가입 후 이용가능합니다.') . "</p>
   		<a href='/page/team_join/form.php?te_id={$parent_key}' class='link_btn'>" . _t('팀에 가입하기') . "</a>
   	</div>";
   } else {
   	$html = "";
   }
   
   $html .= "<div class='data_end'></div>";
   echo $html;
   exit;
}


//list 기본 쿼리
$sql_common = " 
from {$table_name} T1 
left outer join 
(
	select 
		file_id,
		file_table_idx,
		file_name,
		file_rename,
		file_desc
	from {$sweb['file_table']} 
	where file_code='{$file_code}' and file_sub_code='image' and file_order=0
) T2 
ON T1.{$key_column} = T2.file_table_idx 
";

//검색어 조건
$sql_search = " where {$parent_key_column} = '{$parent_key}'  ";
if($stx) $sql_search .= " and ({$prefix}name like '%".$stx."%') ";

// orderby 정렬 설정
if (!$orderby) {
	$orderby = "T1.{$prefix}end_date desc, T1.{$prefix}end_time desc";
}
$sql_order = " order by {$orderby} ";

// list 갯수 쿼리
$sql = " select count(*) as cnt {$sql_common} {$sql_search} {$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

// paging 관련 변수 설정
if($rows < 1) $rows = $sweb['list']['rows'];
$total_page  = ceil($total_count / $rows);
if ($page < 1) $page = 1;
$from_record = ($page - 1) * $rows;


//list 쿼리 검색
$sql = " select * {$sql_common} {$sql_search} and {$prefix}end_date >= '".date("Y-m-d")."' order by {$prefix}end_date asc limit {$from_record}, {$rows} ";
$result = sql_query($sql);

// list 배열에 저장
$list = array();
while($row = sql_fetch_array($result)){
	$list[] = $row;
}

$sql = " select * {$sql_common} {$sql_search} and {$prefix}end_date < '".date("Y-m-d")."' order by {$prefix}end_date desc limit {$from_record}, {$rows} ";
$result = sql_query($sql);
while($row = sql_fetch_array($result)){
	$list[] = $row;
}

$sql = "select count(*) as cnt from {$table_name} where {$parent_key_column} = '{$parent_key}' and {$prefix}status=1";
$row = sql_fetch($sql);
$approval_cnt = $row['cnt'];



?>



<?php if($page == 1){ ?>
<div class="schedule-title">
	<?php if($is_admin_team || $is_member_team){ ?>
	<div class="bottom-btn-right">
		<a href="#none;" onclick="fn_write('');return false;"><span class="material-symbols-outlined">edit_calendar</span></a>
	</div>
	<?php } ?>
</div>

<div class="schedule">
	<ul class="grid" id="item_area">
<?php } ?>
		<?php 
		for($i = 0; $i < count($list); $i++){ 
			$row = $list[$i];
			$row['num'] = $total_count - (($page-1) * $rows) - $i;

			$date = "";
			if($row[$prefix.'start_date'] == $row[$prefix.'end_date']){
				$date .= $row[$prefix.'start_date'] . " (".get_yoil($row[$prefix.'start_date']).")<span>" . $row[$prefix.'start_time'] . "~" . $row[$prefix.'end_time']."</span>";
			}


			$old = "";
			if($row[$prefix.'end_date'] < date("Y-m-d")){
				$old = "old";
			}
						
		?>
		<li class="<?php echo $old; ?> round">

			<a href="#none;" onclick="fn_view(<?php echo $row[$key_column]; ?>);return false;">

				<div>
					<h4><?php echo _t($row[$prefix.'name']); ?></h4>				
					<?php if($arr_gubun[$row[$prefix.'gubun']]){ ?>
					<i>
						<?php echo _t($arr_gubun[$row[$prefix.'gubun']]); ?>
						<?php if($row[$prefix.'gubun2'] && $arr_gubun2[$row[$prefix.'gubun2']]){ ?>
							· <?php echo _t($arr_gubun2[$row[$prefix.'gubun2']]); ?>
						<?php } ?>
					</i>
					<?php } ?>

					<?php if($row[$prefix.'match_team']) { ?>
					<b><?php echo _t('VS') . ' ' . _t($row[$prefix.'match_team']); ?></b>
					<?php } ?>

					<p><?php echo _t($date); ?></p>
					<div class="location">
						[<?php echo _t($row[$prefix.'location']); ?>]
						<?php echo _t($row['ts_address']); ?>					
					</div>
				</div>



			</a>
		</li>
		<?php } ?>
	



		<?php if($page == 1){ ?>
		<?php if($i == 0){ ?>
		<li class="nodata" style="text-align:center; margin:50px 0">No Data</li>
		<?php } ?>
	</ul>
</div>
<?php } ?>

<?php if($total_page <= $page){ ?>
<?php if($page > 1){ ?>
<li class="scroll_end"></li>
<?php } ?>
<div class="data_end"></div>
<?php } ?>
