<?php
/**
 * 스쿼드 관리 공통 함수들
 */

/**
 * 일정 구분 코드로 경기 타입 결정
 * @param string $gubun 일정 구분 코드
 * @return string 경기 타입 ('self', 'match', 'league')
 */
function getMatchType($gubun) {
    switch($gubun) {
        case '256': // 친목
            return 'self';   // 자체 경기 (팀 나누기)
        case '280': // 리그경기  
            return 'league'; // 리그 경기 (고정 2쿼터)
        case '254': // 축구
        case '282': // 기타
        default:
            return 'match';  // 일반 경기 (vs 상대팀)
    }
}

/**
 * 경기 타입별 팀 구성 설정
 * @param string $match_type 경기 타입
 * @return array 팀 구성 배열
 */
function getTeamConfigs($match_type) {
    switch($match_type) {
        case 'self':
            return array(
                'A' => array('name' => 'A팀', 'label' => _t('A팀')),
                'B' => array('name' => 'B팀', 'label' => _t('B팀'))
            );
        case 'match':
        case 'league':
        default:
            return array(
                'our' => array('name' => '우리팀', 'label' => _t('우리팀'))
            );
    }
}

/**
 * 경기 타입별 쿼터 설정
 * @param string $match_type 경기 타입
 * @return array 쿼터 설정
 */
function getQuarterConfigs($match_type) {
    switch($match_type) {
        case 'league':
            return array(
                'is_fixed' => true,    // 고정 쿼터
                'max_quarters' => 2,   // 최대 2쿼터
                'min_quarters' => 2,   // 최소 2쿼터
                'default_quarters' => 2, // 기본 2쿼터
                'labels' => array(1 => _t('전반'), 2 => _t('후반'))
            );
        case 'self':
        case 'match':
        default:
            return array(
                'is_fixed' => false,   // 유동적 쿼터
                'max_quarters' => 6,   // 최대 6쿼터
                'min_quarters' => 1,   // 최소 1쿼터  
                'default_quarters' => 2, // 기본 2쿼터
                'labels' => array() // 동적 생성
            );
    }
}

/**
 * 스포츠 타입별 기본 포메이션
 * @param string $sport_type 스포츠 타입 ('soccer', 'futsal')
 * @return string 기본 포메이션
 */
function getDefaultFormation($sport_type) {
    switch($sport_type) {
        case 'futsal':
            return '2-2';
        case 'soccer':
        default:
            return '4-3-3';
    }
}

/**
 * 경기 타입별 설명 텍스트
 * @param string $match_type 경기 타입
 * @return string 설명 텍스트
 */
function getMatchTypeDescription($match_type) {
    switch($match_type) {
        case 'self':
            return _t('팀 내부 경기 - 참석자를 A팀, B팀으로 나누어 스쿼드를 구성합니다.');
        case 'league':
            return _t('리그 경기 - 전반/후반 2쿼터로 고정되어 스쿼드를 구성합니다.');
        case 'match':
        default:
            return _t('일반 경기 - 상대팀과의 경기로 우리팀 스쿼드를 구성합니다.');
    }
}

/**
 * 디버깅용 함수 - 경기 설정 정보 출력
 * @param array $match_info 경기 정보
 */
function debugMatchInfo($match_info) {
    if (!defined('DEBUG_MODE') || !DEBUG_MODE) return;
    
    echo "<div style='background:#f0f0f0; padding:10px; margin:10px; border-radius:5px;'>";
    echo "<h4>" . _t('스쿼드 설정 정보') . " (" . _t('디버깅') . ")</h4>";
    echo _t('경기 타입') . ": " . $match_info['match_type'] . "<br>";
    echo _t('팀 구성') . ": " . implode(', ', array_column($match_info['teams'], 'name')) . "<br>";
    echo _t('쿼터 설정') . ": " . ($match_info['quarters']['is_fixed'] ? _t('고정') : _t('유동적')) . " (" . $match_info['quarters']['default_quarters'] . _t('쿼터') . ")<br>";
    echo _t('설명') . ": " . $match_info['description'] . "<br>";
    echo "</div>";
}
?>