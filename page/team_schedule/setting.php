<?php
$code1 = "team";
$code2 = "team_schedule";

//키값, 테이블 설정
$prefix = "ts_";
$key_column = $prefix."id";
$key = $$key_column;
$table_name = "sweb_team_schedule";
$table_name_join = "sweb_team_schedule_join";
$table_name_log = "sweb_team_schedule_log";

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

$sweb['team_url'] = G5_URL . $page_prefix."/team.php";
$sweb['team_result_url'] = G5_URL . $page_prefix."/team_result.php";

$sweb['default_url'] = $sweb['write_url'];
$sweb['write_after_url'] = G5_URL . "/page/team/view.php";
$sweb['modify_after_url'] = G5_URL . "/page/team/view.php";
$sweb['delete_after_url'] = G5_URL . "/page/team/view.php";

$sweb['list_title'] = "Schedule List";
$sweb['view_title'] = "Schedule View";
$sweb['write_title'] = "Schedule Edit";

//페이지 접근권한 설정
$sweb['list_level'] = 1;
$sweb['view_level'] = 2;
$sweb['write_level'] = 2;
$sweb['delete_level'] = 2;

//처리파일 메세지
$sweb['write_msg'] = "일정이 저장되었습니다.";
$sweb['modify_msg'] = "일정이 수정되었습니다.";
$sweb['delete_msg'] = "일정이 삭제되었습니다.";


//첨부파일 관련 설정
$file_code = $code2;
$file_sub_code = $code2;
$file_path = G5_DATA_PATH . "/file/" . $file_code . "/";
$file_url = G5_DATA_URL . "/file/" . $file_code . "/";

//배열설정
$sweb['status'] = array("1" => "사용함", "0" => "사용안함");

$arr_gender = fn_getCodeList(250);
$arr_level = fn_getCodeList(257);
$arr_gubun = fn_getCodeList(253);
$arr_gubun2 = fn_getCodeList(283);
$arr_join_gubun = fn_getCodeList(257);

$arr_gubun2_social = array('284' => '일반');


//리스트 설정
$sweb['list']['orderby'] = "T1.".$key_column." desc";
$sweb['list']['rows'] = 10;
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
	"name" => array("name" => "name", "name_kor" => "일정명", "msg" => "일정명을 입력해주세요.", "type" => "varchar(50)", "required" => "", "class" => "", "readonly" => ""),
	"start_date" => array("name" => "start_date", "name_kor" => "시작일", "msg" => "시작일을 입력해 주세요", "type" => "varchar(10)", "required" => "required", "class" => "input_date", "readonly" => ""),
	"start_time" => array("name" => "start_time", "name_kor" => "시작시간", "msg" => "시작시간을 입력해 주세요", "type" => "varchar(10)", "required" => "required", "class" => "", "readonly" => ""),
	"end_date" => array("name" => "end_date", "name_kor" => "종료일", "msg" => "종료일을 입력해 주세요", "type" => "varchar(10)", "required" => "required", "class" => "input_date", "readonly" => ""),
	"end_time" => array("name" => "end_time", "name_kor" => "종료시간", "msg" => "종료시간을 입력해 주세요", "type" => "varchar(10)", "required" => "required", "class" => "", "readonly" => ""),
	"location" => array("name" => "location", "name_kor" => "장소", "msg" => "장소명을 입력해주세요", "type" => "varchar(255)", "required" => "required", "class" => "", "readonly" => ""),
	"address" => array("name" => "address", "name_kor" => "주소", "msg" => "상세 주소를 입력해주세요", "type" => "varchar(255)", "required" => "", "class" => "", "readonly" => ""),
	"gubun" => array(
		"name" => "gubun", 
		"name_kor" => "일정구분", 
		"msg" => "일정구분을 선택해주세요", 
		"type" => "int", 
		"required" => "required", 
		"class" => "", 
		"readonly" => "",
		"arr" => $arr_gubun, 
		"defaultValue" => "", 
		"is_use" => "1",
		"show_empty_option" => true
	),
	"gubun2" => array(
		"name" => "gubun2", 
		"name_kor" => "상세구분", 
		"msg" => "상세구분을 선택해주세요", 
		"type" => "int", 
		"required" => "", 
		"class" => "", 
		"readonly" => "",
		"arr" => $arr_gubun2, 
		"defaultValue" => "", 
		"is_use" => "1"
	),
	"match_team" => array("name" => "match_team", "name_kor" => "상대팀", "msg" => "상대팀명을 입력해주세요", "type" => "varchar(50)", "required" => "", "class" => "", "readonly" => ""),
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



