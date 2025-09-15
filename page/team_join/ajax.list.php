<?php
include_once "./_common.php";
include_once "./setting.php";
include_once "../team/team.common.php";
$code3 = "list";
$g5['title'] = $sweb['list_title'];

// 가입 대기 중인 회원인지 확인
$is_pending_member = false;
if ($member['mb_id']) {
    $check_sql = "select count(*) as cnt from {$table_name} where {$parent_key_column} = '{$parent_key}' and {$prefix}status = '0' and mb_id = '{$member['mb_id']}'";
    $check_row = sql_fetch($check_sql);
    $is_pending_member = ($check_row['cnt'] > 0);
}

// 접근 권한 체크
if ($parent['te_is_player'] || $is_admin_team || $is_member_team || $is_pending_member) {
    // 접근 허용
} else {
    $html = "<div class='join-please' style='min-height:100vh; text-align: center; font-size: 16px; padding-top: 3vw;'><p>비공개된 정보입니다.</p><p>팀 가입 후 이용가능합니다.</p></div>";
    echo $html . "<div class='data_end' style='display:none;'>data_end</div>";
    exit;
}

// 사용자 유형 확인
$is_normal_member = $is_member_team && !$is_pending_member;

// 페이징 관련 변수 설정 - 먼저 정의
if ($rows < 1) $rows = $sweb['list']['rows'];
if ($page < 1) $page = 1;
$start_row = ($page - 1) * $rows;

// 쿼리 기본 부분
$sql_common = " 
from {$table_name} T1 
left outer join 
(
    select file_id, file_table_idx, file_name, file_rename, file_desc
    from {$sweb['file_table']} 
    where file_code='{$file_code}' and file_sub_code='image' and file_order=0
) T2 
ON T1.{$key_column} = T2.file_table_idx 
";

$sql_search = " where {$parent_key_column} = '{$parent_key}' ";
if ($stx) $sql_search .= " and ({$prefix}name like '%".$stx."%') ";
$sql_order = " order by T1.{$key_column} desc ";

// 전체 카운트를 위한 쿼리
$count_sql_pending = "select count(*) as cnt {$sql_common} {$sql_search} and {$prefix}status = '0'";
$count_sql_approved = "select count(*) as cnt {$sql_common} {$sql_search} and {$prefix}status = '1'";

// 대기 중인 회원 수 계산
$pending_count = 0;
if ($is_admin_team || $is_normal_member) {
    // 관리자나 일반 팀원은 모든 대기 중인 회원 수 조회
    $pending_count_row = sql_fetch($count_sql_pending);
    $pending_count = $pending_count_row['cnt'];
} elseif ($is_pending_member) {
    // 대기 중인 회원은 자신의 정보만 확인 가능
    $count_sql_pending .= " and mb_id = '{$member['mb_id']}'";
    $pending_count_row = sql_fetch($count_sql_pending);
    $pending_count = $pending_count_row['cnt'];
}

// 승인된 회원 수 계산
$approved_count = 0;
if ($is_admin_team || $is_normal_member || $parent['te_is_player'] == 1) {
    $approved_count_row = sql_fetch($count_sql_approved);
    $approved_count = $approved_count_row['cnt'];
}

$total_count = $pending_count + $approved_count;
$total_page = ceil($total_count / $rows);

// 페이지 범위 검사
if ($total_page < $page) {
    exit;
}

// 대기 중인 회원 목록 (권한별 쿼리 다르게) - 첫 페이지에만 표시
$pending_list = array();
if ($page == 1) {
    if ($is_admin_team || $is_normal_member) {
        // 관리자나 일반 팀원은 모든 대기 중인 회원 볼 수 있음
        $sql = "select * {$sql_common} {$sql_search} and {$prefix}status = '0' {$sql_order}";
        $result = sql_query($sql);
        while ($row = sql_fetch_array($result)) {
            $pending_list[] = $row;
        }
    } elseif ($is_pending_member) {
        // 대기 중인 회원은 자신의 정보만 볼 수 있음
        $sql = "select * {$sql_common} {$sql_search} and {$prefix}status = '0' and mb_id = '{$member['mb_id']}' {$sql_order}";
        $result = sql_query($sql);
        while ($row = sql_fetch_array($result)) {
            $pending_list[] = $row;
        }
    }
}

// 승인된 회원 목록 (페이징 적용)
$approved_list = array();
if ($is_admin_team || $is_normal_member || $parent['te_is_player'] == 1) {
    // 페이지네이션을 위한 LIMIT 적용
    //$sql = "select * {$sql_common} {$sql_search} and {$prefix}status = '1' {$sql_order} LIMIT {$start_row}, {$rows}";

	$sql = "select * {$sql_common} {$sql_search} and {$prefix}status = '1' 
        order by (case when T1.mb_id = '{$member['mb_id']}' then 0 else 1 end), T1.{$key_column} desc 
        LIMIT {$start_row}, {$rows}";

    $result = sql_query($sql);
    while ($row = sql_fetch_array($result)) {
        $approved_list[] = $row;
    }
}
?>







<?php if ($page == 1) { ?>

<div class="bottom-btn-right">
	<?php if($is_member_team){ ?>
		<a href="<?php echo G5_URL?>/page/team_join/form.php?te_id=<?php echo $te_id; ?>" class="modify"><span class="material-symbols-outlined">manage_accounts</span></a>
	<?php } else { ?>
		<a href="#" onclick="if(<?php echo $parent['te_is_join']; ?> == 1 || <?php echo $is_admin_team ? 'true' : 'false'; ?>) location.href='<?php echo G5_URL?>/page/team_join/form.php?te_id=<?php echo $te_id; ?>'; else alert('<?php echo _t('가입이 허용되어 있지 않습니다.'); ?>'); return false;" class="add"><span class="material-symbols-outlined">person_add</span></a>
	<?php } ?>
</div>


<div class="t-member-list">
    <?php if (!empty($pending_list)) { ?>
    <div class="member-section pending-section">
        <h6>대기 중(<?php echo count($pending_list); ?>)</h6>
        <ul class="list pending-list">
        <?php foreach ($pending_list as $row) { 
            $thumb = thumbnail($row['file_rename'], $file_path, $file_path, 200, 0, false, false, 'center', false, '80/0.5/3');
            $position = explode(",", $row[$prefix.'position']);
        ?>
            <li>
                <div class="round">
                    <a href="#none">
                        <div class="photo">
                            <?php if ($thumb) { ?>
                                <span class="img_no"><?php echo $row['tj_number']; ?></span>
                                <i><img src="<?php echo $file_url . $thumb; ?>" alt=""></i>
                            <?php } else { ?>
                                <span class="img_no"><?php echo $row[$prefix.'number']; ?></span>
                                <i class="noimg"><img src="<?php echo G5_IMG_URL?>/uniform.png" alt=""></i>
                            <?php } ?>
                        </div>
                        <div class="info">
                            <div class="flex">
                                <p class="name"><?php echo $row[$prefix.'name']; ?> <span class="status-badge pending">대기중</span></p>
                                <span class="position">
									<?php foreach ($position as $pos) { 
										$position_name = '';
										foreach (array($arr_position_df, $arr_position_mf, $arr_position_fw) as $position_array) {
											if (!empty($position_array[$pos])) {
												$position_name = explode("(", $position_array[$pos])[0];
												echo "<i>{$position_name}</i>";
												break;
											}
										}
									} ?>
                                </span>
                            </div>
                            <small class="gender"><?php echo $arr_gender[$row[$prefix.'gender']]; ?></small>
                            <small><?php echo $row['tj_content']; ?></small>
                        </div>
                    </a>
                    <?php if ($is_admin_team) { ?>
                    <div class="more">
                        <button><span class="material-symbols-outlined">more_vert</span></button>
                        <div class="floating">
                            <div class="set">
                                <a href="#none" class="approval" onclick="fn_confirm(<?php echo $row[$key_column]; ?>);return false;">가입 승인</a>
                                <a href="#none" class="del" onclick="fn_delete(<?php echo $row[$key_column]; ?>);">신청 거절</a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </li>
        <?php } ?>
        </ul>
    </div>
    <?php } ?>

    <?php if ($is_admin_team || $is_normal_member || $parent['te_is_player'] == 1) { ?>
    <div class="member-section approved-section">
        <h6>팀원(<?php echo $approved_count; ?>)</h6>
        <ul class="list approved-list" id="item_area">
        <?php 
        if (empty($approved_list)) { 
        ?>
            <li class="no-members">팀원이 없습니다.</li>
        <?php 
        } else {
            foreach ($approved_list as $row) { 
                $thumb = thumbnail($row['file_rename'], $file_path, $file_path, 200, 0, false, false, 'center', false, '80/0.5/3');
                $position = explode(",", $row[$prefix.'position']);
        ?>
            <li>
                <div class="round">
                    <a href="#none">
                        <div class="photo">
                            <?php if ($thumb) { ?>
                                <span class="img_no"><?php echo $row['tj_number']; ?></span>
                                <i><img src="<?php echo $file_url . $thumb; ?>" alt=""></i>
                            <?php } else { ?>
                                <span class="img_no"><?php echo $row[$prefix.'number']; ?></span>
                                <i class="noimg"><img src="<?php echo G5_IMG_URL?>/uniform.png" alt=""></i>
                            <?php } ?>
                        </div>
                        <div class="info">
                            <div class="flex">
                                <p class="name"><?php echo $row[$prefix.'name']; ?></p>
                                <span class="position">
									<?php foreach ($position as $pos) { 
										$position_name = '';
										foreach (array($arr_position_df, $arr_position_mf, $arr_position_fw) as $position_array) {
											if (!empty($position_array[$pos])) {
												$position_name = explode("(", $position_array[$pos])[0];
												echo "<i>{$position_name}</i>";
												break;
											}
										}
									} ?>
                                </span>
                            </div>
                            <small class="gender"><?php echo $arr_gender[$row[$prefix.'gender']]; ?></small>
                            <small><?php echo $row['tj_content']; ?></small>
                        </div>
                    </a>


					<?php if ($row['mb_id'] == $member['mb_id'] || $is_admin_team) { ?>
					<div class="more">
						<button><span class="material-symbols-outlined">more_vert</span></button>
						<div class="floating">
							<div class="set">
								<?php if ($row['mb_id'] == $member['mb_id']) { ?>
									<!-- 본인인 경우: 내 정보 수정 -->
									<a href="<?php echo G5_URL?>/page/team_join/form.php?te_id=<?php echo $te_id; ?>" class="edit">내 정보 수정</a>
								<?php } ?>
								
								<?php if ($is_admin_team) { ?>
									<!-- 관리자인 경우: 관리 메뉴들 -->
									<?php if ($row['mb_id'] != $member['mb_id']) { ?>
										<a href="<?php echo G5_URL?>/page/team_join/form.php?te_id=<?php echo $parent_key; ?>&tj_id=<?php echo $row[$key_column]; ?>" class="edit">정보 수정</a>

									<?php } ?>
									<a href="#none" class="approval" onclick="fn_confirm(<?php echo $row[$key_column]; ?>);return false;">승인 대기</a>
									<a href="#none" class="del" onclick="fn_delete(<?php echo $row[$key_column]; ?>);">강제 탈퇴</a>
								<?php } ?>
							</div>
						</div>
					</div>
					<?php } ?>



                </div>
            </li>
        <?php 
            }
        }
        ?>
        </ul>
    </div>
    <?php } ?>
</div>
<?php } else { ?>
<!-- 2페이지 이상일 때는 승인된 회원 목록만 표시 -->
<?php 
    if (!empty($approved_list)) {
        foreach ($approved_list as $row) { 
            $thumb = thumbnail($row['file_rename'], $file_path, $file_path, 200, 0, false, false, 'center', false, '80/0.5/3');
            $position = explode(",", $row[$prefix.'position']);
?>
        <li>
            <div class="round">
                <a href="#none">
                    <div class="photo">
                        <?php if ($thumb) { ?>
                            <span class="img_no"><?php echo $row['tj_number']; ?></span>
                            <i><img src="<?php echo $file_url . $thumb; ?>" alt=""></i>
                        <?php } else { ?>
                            <span class="img_no"><?php echo $row[$prefix.'number']; ?></span>
                            <i class="noimg"><img src="<?php echo G5_IMG_URL?>/uniform.png" alt=""></i>
                        <?php } ?>
                    </div>
                    <div class="info">
                        <div class="flex">
                            <p class="name"><?php echo $row[$prefix.'name']; ?></p>
                            <span class="position">
                                <?php foreach ($position as $pos) { 
                                    $position_name = '';
                                    foreach (array($arr_position_df, $arr_position_mf, $arr_position_fw) as $position_array) {
                                        if (!empty($position_array[$pos])) {
                                            $position_name = explode("(", $position_array[$pos])[0];
                                            echo "<i>{$position_name}</i>";
                                            break;
                                        }
                                    }
                                } ?>
                            </span>
                        </div>
                        <small class="gender"><?php echo $arr_gender[$row[$prefix.'gender']]; ?></small>
                        <small><?php echo $row['tj_content']; ?></small>
                    </div>
                </a>
				<?php if ($row['mb_id'] == $member['mb_id'] || $is_admin_team) { ?>
                <div class="more">
                    <button><span class="material-symbols-outlined">more_vert</span></button>
                    <div class="floating">
                        <div class="set">
                            <a href="#none" class="approval" onclick="fn_confirm(<?php echo $row[$key_column]; ?>);return false;">승인 대기</a>
                            <a href="#none" class="del" onclick="fn_delete(<?php echo $row[$key_column]; ?>);">강제 탈퇴</a>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </li>
<?php 
        }
    }
?>
<?php } ?>
		
<?php
// 마지막 페이지 또는 데이터가 없는 경우에만 data_end 표시
if ($total_page <= $page || (empty($pending_list) && empty($approved_list))) { 
?>
<div class="data_end"></div> 
<?php } ?>