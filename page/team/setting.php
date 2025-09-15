<?php
$code1 = "team";
$code2 = "team";

//키값, 테이블 설정
$prefix = "te_";
$key_column = $prefix."id";
$key = $$key_column;
$table_name = "sweb_team";
$table_name_good = "sweb_team_good";
$table_name_join = "sweb_team_join";


//페이지 URL 설정
$page_prefix = "/page/".$code1;
$sweb['list_url'] = G5_URL . $page_prefix."/list.php";
$sweb['write_url'] = G5_URL . $page_prefix."/form.php";
$sweb['modify_url'] = G5_URL . $page_prefix."/form.php";
$sweb['view_url'] = G5_URL . $page_prefix."/view.php";
$sweb['action_url'] = G5_URL . $page_prefix."/update.php";

$sweb['join_url'] = G5_URL . "/page/team_join/form.php";
$sweb['join_list_url'] = G5_URL . "/page/team_join/list.php";

$sweb['default_url'] = $sweb['list_url'];
$sweb['write_after_url'] = $sweb['join_list_url'];
$sweb['modify_after_url'] = $sweb['join_list_url'];
$sweb['delete_after_url'] = G5_URL;

$sweb['list_title'] = "Team List";
$sweb['view_title'] = "Team View";
$sweb['write_title'] = "Team Edit";

//페이지 접근권한 설정최고관리자
$sweb['list_level'] = 1;
$sweb['view_level'] = 1;
$sweb['write_level'] = 2;
$sweb['delete_level'] = 2;

//처리파일 메세지
$sweb['write_msg'] = "저장되었습니다.";
$sweb['modify_msg'] = "수정되었습니다.";
$sweb['delete_msg'] = "삭제되었습니다.";

//첨부파일 관련 설정
$file_code = $code1;
$file_sub_code = $code2;
$file_path = G5_DATA_PATH . "/file/" . $file_code . "/";
$file_url = G5_DATA_URL . "/file/" . $file_code . "/";

//배열설정
$sweb['status'] = array("1" => "사용함", "0" => "사용안함");

//$arr_economy = fn_getCodeList(260);
//$arr_stage = fn_getCodeList(250);
//$arr_type = fn_getCodeList(254);
//$arr_phone_prefix = fn_getCodeList(260);


//리스트 설정
$sweb['list']['orderby'] = "T1.".$key_column." asc";
$sweb['list']['rows'] = 10;
$sweb['list']['is_paging'] = "1";
$sweb['list']['sfl'] = array($prefix."name" => $page_name."명");
$sweb['list']['qstr'] = array("sfl" => $sfl, "stx" => $stx);
$qstr = "";
foreach($sweb['list']['qstr'] as $key_qstr => $value_qstr){
	$qstr .= "&".$key_qstr."=".$value_qstr;
}


/********************************************************* DB, column 설정 start ***************************************************************/
//DB column 설정
$sweb['column'] = array(
	"name" => array("name" => "name", "name_kor" => "팀명", "msg" => "팀명을 입력해주세요.", "type" => "varchar(50)", "required" => "required", "class" => "", "readonly" => ""),
	"name_eng" => array("name" => "name_eng", "name_kor" => "영문 팀명", "msg" => "영문 팀명을 입력해주세요.", "type" => "varchar(50)", "required" => "required", "class" => "", "readonly" => ""),
	"content" => array("name" => "content", "name_kor" => "설명", "msg" => "설명을 입력해주세요.", "type" => "text", "required" => "required", "class" => "", "readonly" => ""),
	"day" => array("name" => "day", "name_kor" => "모임일", "msg" => "모임일을 입력해주세요. ex)매주 일요일 21:00 ~ 23:00", "type" => "tinyint(2)", "required" => "", "class" => "", "readonly" => ""),
	"stadium1" => array("name" => "stadium1", "name_kor" => "이용 경기장1", "msg" => "이용경기장을 입력해주세요.", "type" => "varchar(255)", "required" => "", "class" => "", "readonly" => ""),
	"stadium2" => array("name" => "stadium2", "name_kor" => "이용 경기장2", "msg" => "이용경기장을 입력해주세요.", "type" => "varchar(255)", "required" => "", "class" => "", "readonly" => ""),
	"location1" => array("name" => "location1", "name_kor" => "지역1", "msg" => "지역을 선택해주세요.", "type" => "int", "required" => "required", "class" => "", "readonly" => "",
					"arr" => 1, "defaultValue" => "지역을 선택해주세요.",
	),
	"location2" => array("name" => "location2", "name_kor" => "지역2", "msg" => "동네을 선택해주세요.", "type" => "int", "required" => "", "class" => "", "readonly" => ""),
	"skill" => array("name" => "skill", "name_kor" => "실력", "msg" => "실력을 선택해주세요.", "type" => "varchar(10)", "required" => "required", "class" => "", "readonly" => "", "arr" => array("상", "중", "하", "하하")),
	"age_group" => array("name" => "age_group", "name_kor" => "연령대", "msg" => "연령대를 선택해주세요.", "type" => "varchar(20)", "required" => "required", "class" => "", "readonly" => "", "arr" => array("10대 이하", "20대", "30대", "40대", "50대", "60대 이상")),
	"tel" => array("name" => "tel", "name_kor" => "대표 연락처", "msg" => "연락처를 입력해주세요.", "type" => "varchar(20)", "required" => "", "class" => "", "readonly" => ""),
	"is_tel" => array("name" => "is_tel", "name_kor" => "관리자 연락처 노출", "msg" => "관리자의 연락처 노출 할 수 있습니다.", "type" => "tinyint(1)", "required" => "", "class" => "", "readonly" => "",
	"arr" => $sweb['status'], "defaultValue" => "1", "is_use" => "1"),
	"is_use" => array("name" => "is_use", "name_kor" => "검색허용", "msg" => "검색 허용", "type" => "tinyint(1)", "required" => "", "class" => "", "readonly" => "",
	"arr" => $sweb['status'], "defaultValue" => "1", "is_use" => "1"),
	"is_join" => array("name" => "is_join", "name_kor" => "가입 허용", "msg" => "가입 신청을 허용 할 수 있습니다.", "type" => "tinyint(1)", "required" => "", "class" => "", "readonly" => "",
	"arr" => $sweb['status'], "defaultValue" => "1", "is_use" => "1"),
	"is_autojoin" => array("name" => "is_autojoin", "name_kor" => "가입 자동 승인", "msg" => "가입 신청시 승인이 자동으로 이뤄 집니다.", "type" => "tinyint(1)", "required" => "", "class" => "", "readonly" => "",
	"arr" => $sweb['status'], "defaultValue" => "1", "is_use" => "1"),
	"is_player" => array("name" => "is_player", "name_kor" => "선수정보 노출", "msg" => "가입된 회원 리스트를 다른 회원이 볼 수 있습니다.", "type" => "tinyint(1)", "required" => "", "class" => "", "readonly" => "",
	"arr" => $sweb['status'], "defaultValue" => "1", "is_use" => "1"),
	"is_schedule" => array("name" => "is_schedule", "name_kor" => "일정 노출", "msg" => "팀의 일정을 다른 회원이 볼 수 있습니다.", "type" => "tinyint(1)", "required" => "", "class" => "", "readonly" => "",
	"arr" => $sweb['status'], "defaultValue" => "1", "is_use" => "1"),
);

/********************************************************* DB, column 설정 end ***************************************************************/


include_once(G5_EDITOR_LIB);
include_once(G5_LIB_PATH.'/thumbnail.lib.php');
?>