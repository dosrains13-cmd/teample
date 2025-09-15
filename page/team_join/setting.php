<?php
$code1 = "team";
$code2 = "team_join";

//키값, 테이블 설정
$prefix = "tj_";
$key_column = $prefix."id";
$key = $$key_column;
$table_name = "sweb_team_join";

//parent 키값, 테이블 설정
$parent_prefix = "te_";
$parent_key_column = $parent_prefix."id";
$parent_key = $$parent_key_column;
$parent_table_name = "sweb_team";

//페이지 URL 설정
$page_prefix = "/page/".$code2;
$sweb['list_url'] = G5_URL . $page_prefix."/list.php";
$sweb['write_url'] = G5_URL . $page_prefix."/form.php";
$sweb['modify_url'] = G5_URL . $page_prefix."/form.php";
$sweb['view_url'] = G5_URL . $page_prefix."/view.php";
$sweb['action_url'] = G5_URL . $page_prefix."/update.php";

$sweb['default_url'] = $sweb['write_url'];
$sweb['write_after_url'] = G5_URL . "/page/team_join/list.php";
$sweb['modify_after_url'] = G5_URL . "/page/team_join/list.php";
$sweb['delete_after_url'] = G5_URL . "/page/team_join/list.php";

$sweb['list_title'] = "Player List";
$sweb['view_title'] = "Player View";
$sweb['write_title'] = "Player Edit";

//페이지 접근권한 설정
$sweb['list_level'] = 1;
$sweb['view_level'] = 2;
$sweb['write_level'] = 2;
$sweb['delete_level'] = 2;

//처리파일 메세지
$sweb['write_msg'] = "신청되었습니다.";
$sweb['modify_msg'] = "정보가 수정되었습니다.";
$sweb['delete_msg'] = "탈퇴 되었습니다.";
$sweb['write_overlap_msg'] = "이미 신청한 팀입니다.";

//첨부파일 관련 설정
$file_code = $code2;
$file_sub_code = $code2;
$file_path = G5_DATA_PATH . "/file/" . $file_code . "/";
$file_url = G5_DATA_URL . "/file/" . $file_code . "/";

//배열설정
$sweb['status'] = array("1" => "사용함", "0" => "사용안함");

$arr_gender = fn_getCodeList(250);
$arr_level = fn_getCodeList(257);
$arr_position_df = fn_getCodeList(263);
$arr_position_mf = fn_getCodeList(264);
$arr_position_fw = fn_getCodeList(265);


//리스트 설정
$sweb['list']['orderby'] = "T1.".$key_column." desc";
$sweb['list']['rows'] = 15;
$sweb['list']['is_paging'] = "1";
$sweb['list']['sfl'] = array($prefix."name" => "name");
$sweb['list']['qstr'] = array("sfl" => $sfl, "stx" => $stx);
$qstr = "";
foreach($sweb['list']['qstr'] as $key_qstr => $value_qstr){
	$qstr .= "&".$key_qstr."=".$value_qstr;
}


/********************************************************* DB, column 설정 start ***************************************************************/
//DB column 설정
$sweb['column'] = array(
	"name" => array("name" => "name", "name_kor" => "닉네임", "msg" => "닉네임을 입력해주세요.", "type" => "varchar(50)", "required" => "required", "class" => "", "readonly" => ""),
	"phone" => array("name" => "phone", "name_kor" => "연락처", "msg" => "연락처를 입력해주세요.", "type" => "text", "required" => "required", "class" => "", "readonly" => ""),
	"gender" => array("name" => "gender", "name_kor" => "성별", "msg" => "성별을 선택해주세요", "type" => "tinyint(1)", "required" => "", "class" => "", "readonly" => "",
	"arr" => $arr_gender, "defaultValue" => "", "is_use" => "1"),
	"level" => array("name" => "level", "name_kor" => "타입", "msg" => "타입을 선택해주세요", "type" => "tinyint(1)", "required" => "", "class" => "", "readonly" => "",
	"arr" => $arr_level, "defaultValue" => "", "is_use" => "1"),
	"number" => array("name" => "number", "name_kor" => "등번호", "msg" => "등번호를 입력해주세요.", "type" => "varchar(255)", "required" => "", "class" => "", "readonly" => ""),
	"content" => array("name" => "content", "name_kor" => "한줄소개", "msg" => "한줄소개를 입력해주세요.", "type" => "varchar(255)", "required" => "required", "class" => "", "readonly" => ""),
);

/*
$sql = "show tables like '".$table_name."'";
$result = sql_fetch($sql);
if(!$result){
	$sql = "
		create table if not exists
		".$table_name."(
		{$prefix}id int auto_increment,";
		foreach($sweb['column'] as $k => $v){
			$sql .= $prefix.$v['name'] . " " . $v['type'] . " comment '".$v['name_kor']."',";
		}
		$sql .= "
		co_id int comment '카테고리',
		mb_id varchar(50) comment '회원ID',
		insert_date datetime comment '등록일',
		insert_ip varchar(20) comment '등록IP',
		modify_date datetime comment '수정일',
		modify_ip varchar(20) comment '수정IP',
		primary key({$prefix}id)
		) default character set utf8 collate utf8_general_ci
	";
	sql_query($sql);
}
*/
/********************************************************* DB, column 설정 end ***************************************************************/



include_once(G5_LIB_PATH.'/thumbnail.lib.php');

?>



