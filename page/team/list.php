<?php 
include_once "./_common.php";

include_once "./setting.php";
$code3 = "list";
$g5['title'] = $sweb['list_title'];

include_once(G5_THEME_MOBILE_PATH.'/head.php');
?>

<style>
#filter-box > ul{margin-bottom: 10px;}
#filter-box.eden_form_type2{padding: 0; margin:0; background: none;}

/* 로딩 인디케이터 스타일 */
.loading-indicator {
    text-align: center; 
    padding: 15px; 
    transition: opacity 0.3s ease;
}
.loading-indicator div {
    display: inline-block; 
    width: 24px; 
    height: 24px; 
    border: 3px solid #f3f3f3; 
    border-top: 3px solid #3498db; 
    border-radius: 50%; 
    animation: spin 1s linear infinite;
}
@keyframes spin { 
    0% { transform: rotate(0deg); } 
    100% { transform: rotate(360deg); } 
}
</style>

<div class="team-wrap">
    <form name="listForm" id="listForm" method="post">
        <input type="hidden" name="page" value="<?php echo $page; ?>">
        <input type="hidden" name="rows" value="<?php echo $sweb['list']['rows']; ?>">
        <input type="hidden" name="sc_mine" value="">
        <?php foreach($sweb['list']['qstr'] as $key_qstr => $value_qstr){ ?>
        <input type="hidden" name="<?php echo $key_qstr; ?>" value="<?php echo $value_qstr; ?>">
        <?php } ?>

        <div id="filter-box" class="eden_form_type2">
            <ul class="flex col-2 gap10">
                <li>
                    <select name="sc_location1" id="sc_location1" class="nice-select">
                        <?php echo fn_getCodeListSelectOption(1, $sc_location1, 1, '지역선택'); ?>
                    </select>				
                </li>
                <li>
                    <select name="sc_location2" id="sc_location2" class="nice-select">
                        <option value="">지역2 선택</option>
                        <!-- 지역2 옵션은 JavaScript로 채워짐 -->
                    </select>				
                </li>
            </ul>
            <ul class="flex col-2 gap10">
                <li>
                    <select name="sc_skill" id="sc_skill" class="nice-select">
                        <option value="">실력 선택</option>
                        <?php
                        $skill_options = $sweb['column']['skill']['arr'];
                        foreach($skill_options as $skill) {
                            $selected = ($sc_skill == $skill) ? 'selected' : '';
                            echo '<option value="'.$skill.'" '.$selected.'>'.$skill.'</option>';
                        }
                        ?>
                    </select>
                </li>
                <li>
                    <select name="sc_age_group" id="sc_age_group" class="nice-select">
                        <option value="">연령대 선택</option>
                        <?php
                        $age_options = $sweb['column']['age_group']['arr'];
                        foreach($age_options as $age) {
                            $selected = ($sc_age_group == $age) ? 'selected' : '';
                            echo '<option value="'.$age.'" '.$selected.'>'.$age.'</option>';
                        }
                        ?>
                    </select>
                </li>
            </ul>

            <ul class="flex col-2 gap10">
                <li>
                    <input type="text" id="stx" name="stx" placeholder="팀 이름을 검색하세요." value="<?php echo $stx; ?>">
                </li>
                <li>
                    <button type="button" onclick="loadMoreData(true);return false;">검색</button>
                </li>
            </ul>
        </div>
    </form>

    <div id="list_area" class="team_list">
        <!-- 검색 결과가 여기에 표시됩니다 -->
    </div>
</div>

<!-- page 이동 form start -->
<form method="get" id="moveForm" name="moveForm">
    <input type="hidden" name="page" value="<?php echo $page; ?>">
    <input type="hidden" name="<?php echo $key_column; ?>" value="<?php echo $key; ?>" />
    <?php foreach($sweb['list']['qstr'] as $key_qstr => $value_qstr){ ?>
    <input type="hidden" name="<?php echo $key_qstr; ?>" value="<?php echo $value_qstr; ?>">
    <?php } ?>
</form>
<!-- // page 이동 form end -->

<!-- 구독 호출 form start -->
<form method="post" id="subscribeForm" name="subscribeForm" action="<?php echo $sweb['action_url']; ?>">
    <input type="hidden" name="w" value="s" />
    <input type="hidden" name="<?php echo $key_column; ?>" value="<?php echo $key; ?>" />
</form>
<!-- // 구독 호출 form end -->

<?php
include_once(G5_THEME_MOBILE_PATH.'/tail.php');
?>

<script>
// 전역 변수 선언
var page = 1;
var loading = false;
var hasMoreData = true;
var scrollThrottleTimer = null;
var lastScrollTime = 0;
var scrollEndTimer = null;
var itemsPerPage = <?php echo $sweb['list']['rows']; ?>; // setting.php에서 설정된 rows 값 사용

// 페이지 로드 시 실행
$(function(){
    // 페이지 로드시 첫 데이터 가져오기
    loadMoreData();
    
    // 지역1 변경 시 이벤트
    $('#sc_location1').on('change', function(){
        fn_getGugun("");
    });
    
    // 엔터키 검색 이벤트 처리
    $('#stx').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            loadMoreData(true);
        }
    });
    
    // 스크롤 이벤트에 쓰로틀링 적용
    $(window).scroll(function() {
        var now = Date.now();
        
        // 스크롤 종료 감지 타이머 리셋
        clearTimeout(scrollEndTimer);
        
        // 300ms마다 한 번만 실행 (쓰로틀링)
        if (!scrollThrottleTimer && now - lastScrollTime > 300) {
            lastScrollTime = now;
            
            // 화면 하단에 가까워지면 추가 데이터 로드
            checkScrollPosition();
            
            scrollThrottleTimer = setTimeout(function() {
                scrollThrottleTimer = null;
            }, 300);
        }
        
        // 스크롤 종료 감지 (모바일 터치 스크롤 대응)
        scrollEndTimer = setTimeout(function() {
            checkScrollPosition();
        }, 200);
    });
    
    // 모바일 터치 이벤트 처리 추가
    $(document).on('touchend', function() {
        setTimeout(function() {
            checkScrollPosition();
        }, 100);
    });
});

// 스크롤 위치 체크 함수 (중복 코드 방지)
function checkScrollPosition() {
    if (hasMoreData && !loading && $(window).scrollTop() + $(window).height() > $(document).height() - 300) {
        page++;
        loadMoreData();
    }
}

// 데이터 로드 함수
function loadMoreData(resetPage) {
    if (resetPage) {
        page = 1;
        hasMoreData = true;
        $('#list_area').empty();
    }
    
    if (loading || !hasMoreData) return;
    
    loading = true;
    console.log("Loading data for page " + page + " with " + itemsPerPage + " items per page");
    
    // 개선된 로딩 인디케이터
    var loadingHtml = '<div class="loading-indicator" style="opacity: 0;">' +
                     '<div></div>' +
                     '<p style="margin-top: 8px;">데이터를 불러오는 중...</p></div>';
    
    // 로딩 인디케이터 표시
    if (page > 1) {
        if ($('.loading-indicator').length === 0) {
            $('#list_area').append(loadingHtml);
            setTimeout(function() {
                $('.loading-indicator').css('opacity', '1');
            }, 10);
        } else {
            $('.loading-indicator').show().css('opacity', '1');
        }
    }
    
    // form 데이터 구성
    $('#listForm input[name="page"]').val(page);
    $('#listForm input[name="rows"]').val(itemsPerPage);
    var formData = new FormData($('#listForm')[0]);
    
    // 디버깅 로그 추가
    console.log("검색 요청:", {
        page: page,
        rows: itemsPerPage,
        stx: $('#stx').val(),
        sc_location1: $('#sc_location1').val(),
        sc_location2: $('#sc_location2').val(),
        sc_skill: $('#sc_skill').val(),
        sc_age_group: $('#sc_age_group').val()
    });
    
    // FormData 내용 확인 (디버깅용)
    for (var pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    // AJAX 요청
    $.ajax({
        url: "ajax.list.php",
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        cache: false,
        beforeSend: function() {
            // 첫 페이지인 경우만 로딩 표시
            if (page === 1) {
                $('#list_area').html(loadingHtml);
                setTimeout(function() {
                    $('.loading-indicator').css('opacity', '1');
                }, 10);
            }
        },
        success: function(response) {
            console.log("Response received for page " + page + ", length: " + response.length);
            
            // 응답이 비어있는 경우에만 종료 처리
            if (response.trim() === '') {
                hasMoreData = false;
                console.log("Empty response, no more data");
                $('.loading-indicator p').text('더 이상 데이터가 없습니다');
                $('.loading-indicator').delay(800).fadeOut(400);
                return;
            }
            
            // 응답에 data_end가 포함되어 있으면 마지막 페이지로 처리하되, 받은 데이터는 표시
            var isLastPage = response.indexOf('data_end') > -1;
            
            // 첫 페이지인 경우 결과 영역에 HTML 삽입
            if (page === 1) {
                $('#list_area').html(response);
            } else {
                // 페이지 1 이후에는 item_area에 추가
                $('#item_area').append(response);
            }
            
            // 마지막 페이지인 경우에만 hasMoreData를 false로 설정
            if (isLastPage) {
                hasMoreData = false;
                console.log("Last page detected, no more data");
                $('.loading-indicator p').text('모든 데이터를 불러왔습니다');
                $('.loading-indicator').delay(800).fadeOut(400);
            } else {
                // 로딩 완료 시 약간의 지연 후 인디케이터 숨김
                $('.loading-indicator').fadeOut(300);
            }
            
            // 로딩 상태 해제
            loading = false;
            
            // 마지막 페이지가 아니고, 화면에 여전히 공간이 있으면 다음 페이지 즉시 로드
            if (!isLastPage && hasMoreData && $(window).scrollTop() + $(window).height() >= $(document).height() - 300) {
                console.log("Auto-loading next page");
                setTimeout(function() {
                    page++;
                    loadMoreData();
                }, 500);
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX 오류:", error);
            $('.loading-indicator p').text('오류가 발생했습니다. 다시 시도해주세요');
            $('.loading-indicator div').hide();
            
            loading = false;
        }
    });
}

// 내 팀 보기 토글 함수
function fn_mine(flag){
    document.listForm.sc_mine.value = flag;
    var html = '';
    if(flag == 'Y'){
        html = '<a href="#none" class="myschool" onclick="fn_mine(\'\');return false;">전체 팀 보기</a>';
    } else {
        html = '<a href="#none" class="myschool" onclick="fn_mine(\'Y\');return false;">나의 팀 보기</a>';
    }
    
    $('#mine_area').html(html);
    $('.searchs').remove();
    loadMoreData(true);
}

// 팀 등록 페이지로 이동
function fn_write(key){
    var url = "<?php echo $sweb['write_url']; ?>";
    if(key) url += "?<?php echo $key_column; ?>=" + key;
    document.moveForm.action = url;
    $('#moveForm').submit();
}

// 팀 상세 페이지로 이동
function fn_view(key){
    document.moveForm.action = "<?php echo $sweb['view_url']; ?>";
    document.moveForm.<?php echo $key_column; ?>.value = key;
    $('#moveForm').submit();
}

// 팀 가입 페이지로 이동
function fn_join(){
    document.moveForm.action = "<?php echo $sweb['join_url']; ?>";
    document.moveForm.<?php echo $key_column; ?>.value = '';
    $('#moveForm').submit();
}

// 지역2 옵션 가져오기
function fn_getGugun(value){
    $.ajax({
        url: g5_url + '/sweb/module/juso/ajax.get_gugun.php',
        type: 'post',
        data: {"sido": $('#sc_location1').val(), "gugun": value},
        dataType: 'html',
        success: function(data){
            // 데이터가 비어있거나 옵션이 없는 경우 기본 옵션 추가
            if(!data || data.trim() === "" || data.indexOf("<option") === -1) {
                data = '<option value="">지역2 선택</option>';
            }
            
            $('#sc_location2').html(data);
            
            // niceSelect 업데이트
            if ($.fn.niceSelect) {
                $('#sc_location2').niceSelect('destroy');
                $('#sc_location2').niceSelect();
            }
        },
        error: function(xhr, status, error){
            console.error("지역 데이터 로드 중 오류가 발생했습니다:", error);
            // 오류 발생시 기본 옵션 추가
            $('#sc_location2').html('<option value="">지역2 선택</option>');
            
            if ($.fn.niceSelect) {
                $('#sc_location2').niceSelect('destroy');
                $('#sc_location2').niceSelect();
            }
        }
    });
}
</script>