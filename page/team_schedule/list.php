<?php
include_once "./_common.php";
include_once "./setting.php";
include_once "../team/team.common.php";
if($error_msg) alert($error_msg, G5_URL);
$code3 = "list";
$g5['title'] = $sweb['list_title'];

include_once(G5_THEME_MOBILE_PATH.'/head.php');

//접근권한 체크
fn_authCheck($sweb['list_level'], "");
?>
<?php include "../team/tab.php"; ?>

<div id="result_area">
    <!-- AJAX로 로드된 데이터가 여기에 표시됩니다 -->
</div>

<form name="listForm" id="listForm" action="<?php echo $sweb['action_url']; ?>" method="post">
    <input type="hidden" name="<?php echo $parent_key_column; ?>" value="<?php echo $parent_key; ?>" />
    <input type="hidden" name="page" value="<?php echo $page; ?>">
    <input type="hidden" name="rows" value="<?php echo $sweb['list']['rows']; ?>">
    <?php foreach($sweb['list']['qstr'] as $key_qstr => $value_qstr){ ?>
    <input type="hidden" name="<?php echo $key_qstr; ?>" value="<?php echo $value_qstr; ?>">
    <?php } ?>
</form>

<!-- page 이동 form start -->
<form method="get" id="moveForm" name="moveForm">
    <input type="hidden" name="page" value="<?php echo $page; ?>">
    <input type="hidden" name="<?php echo $parent_key_column; ?>" value="<?php echo $parent_key; ?>" />
    <input type="hidden" name="<?php echo $key_column; ?>" value="<?php echo $key; ?>" />
    <?php foreach($sweb['list']['qstr'] as $key_qstr => $value_qstr){ ?>
    <input type="hidden" name="<?php echo $key_qstr; ?>" value="<?php echo $value_qstr; ?>">
    <?php } ?>
</form>
<!-- // page 이동 form end -->

<?php 
include_once(G5_THEME_MOBILE_PATH.'/tail.php');
?>

<!-- InfiniteScroll.js 라이브러리 로드 -->
<script src="<?php echo G5_URL; ?>/js/infiniteScroll.js"></script>

<script>
// InfiniteScroll 초기화
$(document).ready(function() {
    // 디버그 모드 설정 (개발 시에만 true)
    var isDebugMode = <?php echo defined('DEBUG_MODE') && DEBUG_MODE ? 'true' : 'false'; ?>;
    
    // InfiniteScroll 초기화
    var initSuccess = InfiniteScroll.init({
        ajaxUrl: 'ajax.list.php',                     // AJAX 요청 URL
        targetContainer: '#item_area',                // 데이터가 추가될 컨테이너 (ajax.list.php에서 생성)
        loadingContainer: '#result_area',             // 로딩 표시할 컨테이너
        formSelector: '#listForm',                    // 검색 폼 셀렉터
        itemsPerPage: <?php echo $sweb['list']['rows']; ?>, // 페이지당 아이템 수
        scrollThreshold: 300,                         // 스크롤 감지 임계값
        throttleDelay: 300,                          // 스크롤 쓰로틀링 지연시간
        debugMode: isDebugMode,                      // 디버그 모드
        resetPageOnSearch: true,                     // 검색 시 페이지 리셋
        fadeInAnimation: true,                      // 기존 구조에서는 애니메이션 비활성화
        animationDelay: 30                          // 아이템별 애니메이션 지연
    });
    
    // 초기화 실패 시 에러 처리
    if (!initSuccess) {
        console.error('InfiniteScroll 초기화에 실패했습니다.');
        // 초기화 실패 시 기존 방식으로 폴백
        console.log('기존 방식으로 폴백합니다.');
        loadMoreDataFallback();
    }
});

// 기존 함수들과의 호환성 유지
function loadMoreData(resetPage) {
    if (typeof InfiniteScroll !== 'undefined' && InfiniteScroll.getState) {
        // InfiniteScroll이 정상적으로 로드된 경우
        if (resetPage) {
            InfiniteScroll.refresh();
        } else {
            InfiniteScroll.loadNext();
        }
    } else {
        // 폴백: 기존 방식 사용
        loadMoreDataFallback(resetPage);
    }
}

// 기존 방식 폴백 함수 (InfiniteScroll 실패 시 사용)
function loadMoreDataFallback(resetPage) {
    // 전역 변수 선언 (폴백용)
    if (typeof window.fallbackState === 'undefined') {
        window.fallbackState = {
            page: 1,
            loading: false,
            hasMoreData: true
        };
    }
    
    var state = window.fallbackState;
    
    if (resetPage) {
        state.page = 1;
        state.hasMoreData = true;
        $('#result_area').empty();
    }
    
    if (state.loading || !state.hasMoreData) return;
    
    state.loading = true;
    console.log("Fallback: Loading data for page " + state.page);
    
    // 폼 데이터 구성
    $('#listForm input[name="page"]').val(state.page);
    var formData = new FormData($('#listForm')[0]);
    
    // AJAX 요청
    $.ajax({
        url: 'ajax.list.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        cache: false,
        beforeSend: function() {
            if (typeof loadingStart === 'function') {
                loadingStart();
            }
        },
        success: function(data) {
            if (state.page == 1) {
                $("#result_area").html(data);
            } else {
                $('#item_area').append(data);
            }
            
            // 데이터 종료 체크
            if (data.indexOf('data_end') > -1) {
                state.hasMoreData = false;
            }
            
            state.page++;
        },
        error: function(xhr, status, error) {
            console.error("Fallback AJAX 오류:", error);
        },
        complete: function() {
            state.loading = false;
            if (typeof loadingEnd === 'function') {
                loadingEnd();
            }
        }
    });
}

// 기존 fn_paging 함수 (호환성 유지)
function fn_paging(page) {
    loadMoreData(true);
}

// 일정 등록 페이지로 이동
function fn_write(key) {
    var url = "<?php echo $sweb['write_url']; ?>";
    if (key) url += "?<?php echo $key_column; ?>=" + key;
    
    document.moveForm.<?php echo $key_column; ?>.value = key || '';
    document.moveForm.action = url;
    document.moveForm.submit();
}

// 일정 상세보기 페이지로 이동
function fn_view(key) {
    if (!key) {
        alert('잘못된 요청입니다.');
        return false;
    }
    
    document.moveForm.<?php echo $key_column; ?>.value = key;
    document.moveForm.action = "<?php echo $sweb['view_url']; ?>";
    document.moveForm.submit();
}

// 승인 처리 함수 (일정에서는 사용하지 않을 수 있음)
function fn_confirm(key) {
    if (!key) {
        alert('잘못된 요청입니다.');
        return false;
    }
    
    var url = "<?php echo $sweb['action_url']; ?>";
    
    $.ajax({
        url: url,
        type: 'post',
        data: {w: 'a', <?php echo $key_column; ?>: key},
        dataType: 'json',
        beforeSend: function() {
            if (typeof loadingStart === 'function') {
                loadingStart();
            }
        },
        success: function(data) {
            if (data.status) {
                // 성공 시 새로고침
                loadMoreData(true);
            } else {
                alert(data.msg || '처리 중 오류가 발생했습니다.');
            }
            
            if (data.reload) {
                location.reload();
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('승인 처리 중 오류:', textStatus, errorThrown);
            alert('처리 중 오류가 발생했습니다. 다시 시도해주세요.');
        },
        complete: function() {
            if (typeof loadingEnd === 'function') {
                loadingEnd();
            }
        }
    });
    
    return false;
}

// 일정 삭제 함수
function fn_delete(key) {
    if (!key) {
        alert('잘못된 요청입니다.');
        return false;
    }
    
    if (!confirm("해당 일정을 삭제하시겠습니까?")) {
        return false;
    }
    
    var url = "<?php echo $sweb['action_url']; ?>";
    
    $.ajax({
        url: url,
        type: 'post',
        data: {w: 'd', <?php echo $key_column; ?>: key},
        dataType: 'json',
        beforeSend: function() {
            if (typeof loadingStart === 'function') {
                loadingStart();
            }
        },
        success: function(data) {
            if (data.status) {
                alert(data.msg || '일정이 삭제되었습니다.');
                // 성공 시 새로고침
                loadMoreData(true);
            } else {
                alert(data.msg || '처리 중 오류가 발생했습니다.');
            }
            
            if (data.reload) {
                location.reload();
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('삭제 처리 중 오류:', textStatus, errorThrown);
            alert('처리 중 오류가 발생했습니다. 다시 시도해주세요.');
        },
        complete: function() {
            if (typeof loadingEnd === 'function') {
                loadingEnd();
            }
        }
    });
    
    return false;
}

// 페이지 언로드 시 InfiniteScroll 정리
$(window).on('beforeunload', function() {
    if (typeof InfiniteScroll !== 'undefined' && InfiniteScroll.destroy) {
        InfiniteScroll.destroy();
    }
});
</script>