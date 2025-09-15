<?php
include_once "./_common.php";
include_once "./setting.php";

include_once(G5_LIB_PATH.'/thumbnail.lib.php');

//접근권한 체크
if(!$sweb['is_admin']) fn_authCheck($sweb['list_level'], "");

// 팀의 회원 수를 가져오는 함수
function get_team_member_count($te_id) {
    $sql = "SELECT COUNT(*) as cnt FROM sweb_team_join WHERE te_id = '{$te_id}' AND tj_status = '1'";
    $row = sql_fetch($sql);
    return $row['cnt'];
}

// 팀의 가장 최근 일정을 가져오는 함수
function get_team_latest_schedule($te_id) {
    $sql = "SELECT ts_date FROM sweb_team_schedule WHERE te_id = '{$te_id}' AND ts_date >= '".date('Y-m-d')."' ORDER BY ts_date ASC LIMIT 1";
    $row = sql_fetch($sql);
    
    if ($row) {
        // 오늘부터 며칠 후인지 계산
        $today = new DateTime(date('Y-m-d'));
        $schedule_date = new DateTime($row['ts_date']);
        $diff = $today->diff($schedule_date);
        
        if ($diff->days == 0) {
            return "오늘";
        } else {
            return "D-" . $diff->days;
        }
    }
    
    return "-";
}

// 코드 값 가져오기 함수 (코드가 없을 경우 빈 문자열 반환)
function get_code_value($code) {
    if (!$code) return "";
    return fn_getCodeValue($code);
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
    where file_code='{$file_code}' and file_sub_code='logo' and file_order=0
) T2 
ON T1.{$key_column} = T2.file_table_idx  
left outer join(
    select * from {$table_name_join} where mb_id='".$member['mb_id']."'
) T3
ON T1.{$key_column} = T3.{$key_column}
left outer join (
    select {$key_column}, count(*) as cnt from {$table_name_good} group by {$key_column}
) T4
ON T1.{$key_column} = T4.{$key_column}
";

//검색어 조건
$sql_search = " where (1)  ";
if($stx) $sql_search .= " and ({$prefix}name like '%".$stx."%' OR {$prefix}name_eng like '%".$stx."%') ";
if($sc_location1) $sql_search .= " and {$prefix}location1 = '{$sc_location1}' ";
if($sc_location2) $sql_search .= " and {$prefix}location2 = '{$sc_location2}' ";

// 실력 검색 조건 추가
if($sc_skill) $sql_search .= " and {$prefix}skill = '{$sc_skill}' ";

// 연령대 검색 조건 추가
if($sc_age_group) $sql_search .= " and {$prefix}age_group = '{$sc_age_group}' ";

if($sc_mine){
    $sql_search .= " and (T1.mb_id = '{$member['mb_id']}' OR T3.tj_id > 0) ";
}

// orderby 정렬 설정
if (!$orderby) {
    $orderby = "T1.".$key_column." desc";
}
$sql_order = " order by {$orderby} ";

// list 갯수 쿼리
$sql = " select count(*) as cnt {$sql_common} {$sql_search} {$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

// paging 관련 변수 설정
if($rows < 1) $rows = $sweb['list']['rows'];
$total_page = ceil($total_count / $rows);
if ($page < 1) $page = 1;
$from_record = ($page - 1) * $rows;

//list 쿼리 검색
$sql = " select T1.*, T2.*, if(T3.tj_id > 0, 'Y', '') as is_join, T4.cnt as like_cnt {$sql_common} {$sql_search} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);

// list 배열에 저장
$list = array();
while($row = sql_fetch_array($result)){
    // 회원 수와 최근 일정 정보 추가
    $row['member_count'] = get_team_member_count($row[$key_column]);
    $list[] = $row;
}
?>

<?php if($page == 1){ ?>
<ul id="item_area" class="grid">
<?php } ?>

<?php 
/****************************** list 노출 start ******************************/
for($i = 0; $i < count($list); $i++){ 
    $row = $list[$i];
    $row['num'] = $total_count - (($page-1) * $rows) - $i;

    $thumb = thumbnail($row['file_rename'], $file_path, $file_path, 80, '', false, false, 'center', false, '80/0.5/3');
    if($thumb){
        $img = "<img src='".$file_url . $thumb ."' alt='".$row[$prefix.'name']."' />";
    }else{
        $img = "<img src='".G5_URL."/img/symbol_b.png' alt='기본 이미지' />";
    }

    // 애니메이션 클래스 설정
    $animateClass = " animate__animated animate__backInLeft delay" . sprintf("%02d", $i);
?>
<li class="li_<?php echo $row[$key_column]; ?><?php echo $animateClass; ?>">
    <div class="round">
        <a href="#none;" onclick="fn_view(<?php echo $row[$key_column]; ?>);return false;">
            <div class="thum"><?php echo $img; ?></div>
            <div class="info">
                <p><?php echo ($g5['lang'] != 'ko_KR' && !empty($row['te_name_eng'])) ? $row['te_name_eng'] : $row['te_name']; ?></p>
                <div class="s-info">
                    <i>
                        <span class="material-symbols-outlined">account_circle</span>
                        <?php echo $row['member_count']; ?>
                    </i>
                    <i>
                        <span class="material-symbols-outlined">update</span>
                        <?php echo get_team_latest_schedule($row['te_id']); ?>
                    </i>
                </div>
                <div class="location-info">
                    <?php if(!empty($row['te_location1'])): ?>
                        <i><span class="material-symbols-outlined">location_on</span> 
                        <?php echo get_code_value($row['te_location1']); ?>
                        <?php if(!empty($row['te_location2'])): ?>
                            <small><?php echo get_code_value($row['te_location2']); ?></small>
                        <?php endif; ?>
                        </i>
                    <?php endif; ?>
                </div>
            </div>
        </a>
    </div>
</li>
<?php } ?>

<?php if($i == 0){ ?>
<li class="nodata">검색 결과가 없습니다.</li>
<?php } ?>

<?php if($total_page <= $page){ ?>
<?php if($page > 1){ ?>
<li class="scroll_end"></li>
<?php } ?>
<div class="data_end"></div>
<?php } ?>

<?php if($page == 1){ ?>
</ul>
<?php } ?>