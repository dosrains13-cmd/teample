<?php 
include_once "./_common.php";

include_once "./setting.php";
include_once "../team/team.common.php";

if($error_msg) alert($error_msg, G5_URL);
$code3 = "view";
$g5['title'] = $sweb['view_title'];

include_once(G5_THEME_MOBILE_PATH.'/head.php');

//접근권한 체크
fn_authCheck($sweb['view_level'], "");

//데이터 조회
$view = $parent;

//첨부파일
$files = fileList($file_code, "", $key);

//조회수 증가
$sql = "update {$table_name} set {$prefix}cnt = {$prefix}cnt + 1 where {$key_column} = '{$key}' ";
sql_query($sql);

$f_path = G5_DATA_PATH . "/file/team/";
$f_url = G5_DATA_URL . "/file/team/";
$thumb = thumbnail($parent['file_rename'], $f_path, $f_path, 300, 0, false, false, 'center', false, '80/0.5/3');

?>



<!-- view start -->
<?php include "../team/tab.php"; ?>


<div class="view-wrap">
	<div class="gal-view">

		<?php
		$co_code = "team";
		$co_key = $key;
		$is_comment_write = ($is_member_team || $is_admin_team) ? "1" : "";
		include G5_SWEB_PATH . "/module/comment/comment.php";
		?>
	</div>
</div>
<!-- // view end -->



<!-- page 이동 form start -->
<form method="get" id="moveForm" name="moveForm">
	<input type="hidden" name="page" value="<?php echo $page; ?>">
	<input type="hidden" name="<?php echo $key_column; ?>" value="<?php echo $key; ?>" />
	<?php foreach($sweb['list']['qstr'] as $key_qstr => $value_qstr){ ?>
	<input type="hidden" name="<?php echo $key_qstr; ?>" value="<?php echo $value_qstr; ?>">
	<?php } ?>
</form>
<!-- // page 이동 form end -->

<!-- join member 호출 form start -->
<form method="post" id="listForm" name="listForm">
	<input type="hidden" name="page" value="<?php echo $page; ?>">
	<input type="hidden" name="<?php echo $key_column; ?>" value="<?php echo $key; ?>" />
	<?php foreach($sweb['list']['qstr'] as $key_qstr => $value_qstr){ ?>
	<input type="hidden" name="<?php echo $key_qstr; ?>" value="<?php echo $value_qstr; ?>">
	<?php } ?>
</form>
<!-- // join member 호출 form end -->


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

// join 호출
function fn_join(){
	document.moveForm.<?php echo $key_column; ?>.value = '<?php echo $key; ?>';
	$('#moveForm').attr("action", "<?php echo $sweb['join_url']; ?>");
	$('#moveForm').submit();
}







</script>