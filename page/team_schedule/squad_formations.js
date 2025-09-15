/**
 * 팀플렉스 축구/풋살 포메이션 시스템
 * 파일명: squad_formations.js
 */

/**
 * 현대 축구에서 사용되는 주요 포메이션 정의
 * 각 포메이션별로 실제 필드 위치에 맞게 배치
 */

// 포메이션 정의 (필드 위에서 아래로: 공격수 → 미드필더 → 수비수 → 골키퍼)
var formations = {
    // 4백 시스템
    '4-3-3': {
        name: '4-3-3',
        description: '가장 인기 있는 공격적 포메이션',
        rows: [
            ['LW', 'ST', 'RW'],           // 공격수 라인
            ['LCM', 'CM', 'RCM'],         // 미드필더 라인  
            ['LB', 'LCB', 'RCB', 'RB'],   // 수비수 라인
            ['GK']                        // 골키퍼
        ]
    },
    '4-4-2': {
        name: '4-4-2',
        description: '클래식한 밸런스 포메이션',
        rows: [
            ['LST', 'RST'],               // 공격수 라인
            ['LM', 'LCM', 'RCM', 'RM'],   // 미드필더 라인
            ['LB', 'LCB', 'RCB', 'RB'],   // 수비수 라인
            ['GK']                        // 골키퍼
        ]
    },
    '4-2-3-1': {
        name: '4-2-3-1',
        description: '현대적인 수비형 미드필더 활용',
        rows: [
            ['ST'],                       // 공격수 라인
            ['LAM', 'CAM', 'RAM'],        // 공격형 미드필더 라인
            ['LDM', 'RDM'],               // 수비형 미드필더 라인
            ['LB', 'LCB', 'RCB', 'RB'],   // 수비수 라인
            ['GK']                        // 골키퍼
        ]
    },
    '4-1-4-1': {
        name: '4-1-4-1',
        description: '수비적 안정성을 중시하는 포메이션',
        rows: [
            ['ST'],                       // 공격수 라인
            ['LM', 'LCM', 'RCM', 'RM'],   // 미드필더 라인
            ['DM'],                       // 수비형 미드필더
            ['LB', 'LCB', 'RCB', 'RB'],   // 수비수 라인
            ['GK']                        // 골키퍼
        ]
    },
    '4-3-1-2': {
        name: '4-3-1-2',
        description: '트레콰르티스타를 활용하는 포메이션',
        rows: [
            ['LST', 'RST'],               // 공격수 라인
            ['CAM'],                      // 공격형 미드필더
            ['LCM', 'CM', 'RCM'],         // 미드필더 라인
            ['LB', 'LCB', 'RCB', 'RB'],   // 수비수 라인
            ['GK']                        // 골키퍼
        ]
    },
    '4-5-1': {
        name: '4-5-1',
        description: '수비적인 카운터 어택 포메이션',
        rows: [
            ['ST'],                       // 공격수 라인
            ['LM', 'LCM', 'CM', 'RCM', 'RM'], // 미드필더 라인
            ['LB', 'LCB', 'RCB', 'RB'],   // 수비수 라인
            ['GK']                        // 골키퍼
        ]
    },

    // 3백 시스템
    '3-5-2': {
        name: '3-5-2',
        description: '윙백을 활용한 공격적 포메이션',
        rows: [
            ['LST', 'RST'],               // 공격수 라인
            ['LWB', 'LCM', 'CM', 'RCM', 'RWB'], // 미드필더/윙백 라인
            ['LCB', 'CB', 'RCB'],         // 수비수 라인
            ['GK']                        // 골키퍼
        ]
    },
    '3-4-3': {
        name: '3-4-3',
        description: '현대적인 3백 공격 시스템',
        rows: [
            ['LW', 'ST', 'RW'],           // 공격수 라인
            ['LCM', 'CM', 'RCM', 'RM'],   // 미드필더 라인
            ['LCB', 'CB', 'RCB'],         // 수비수 라인
            ['GK']                        // 골키퍼
        ]
    },
    '3-4-1-2': {
        name: '3-4-1-2',
        description: '3백 시스템의 변형',
        rows: [
            ['LST', 'RST'],               // 공격수 라인
            ['CAM'],                      // 공격형 미드필더
            ['LM', 'LCM', 'RCM', 'RM'],   // 미드필더 라인
            ['LCB', 'CB', 'RCB'],         // 수비수 라인
            ['GK']                        // 골키퍼
        ]
    },
    '3-6-1': {
        name: '3-6-1',
        description: '극도로 수비적인 3백 시스템',
        rows: [
            ['ST'],                       // 공격수 라인
            ['LM', 'LCM', 'CM', 'RCM', 'RM', 'DM'], // 미드필더 라인
            ['LCB', 'CB', 'RCB'],         // 수비수 라인
            ['GK']                        // 골키퍼
        ]
    },

    // 5백 시스템
    '5-3-2': {
        name: '5-3-2',
        description: '수비적 안정성을 극대화',
        rows: [
            ['LST', 'RST'],               // 공격수 라인
            ['LCM', 'CM', 'RCM'],         // 미드필더 라인
            ['LWB', 'LCB', 'CB', 'RCB', 'RWB'], // 수비수/윙백 라인
            ['GK']                        // 골키퍼
        ]
    },
    '5-4-1': {
        name: '5-4-1',
        description: '극수비 포메이션',
        rows: [
            ['ST'],                       // 공격수 라인
            ['LM', 'LCM', 'RCM', 'RM'],   // 미드필더 라인
            ['LWB', 'LCB', 'CB', 'RCB', 'RWB'], // 수비수/윙백 라인
            ['GK']                        // 골키퍼
        ]
    },
    '5-2-3': {
        name: '5-2-3',
        description: '수비적이지만 공격진은 강화',
        rows: [
            ['LW', 'ST', 'RW'],           // 공격수 라인
            ['LCM', 'RCM'],               // 미드필더 라인
            ['LWB', 'LCB', 'CB', 'RCB', 'RWB'], // 수비수/윙백 라인
            ['GK']                        // 골키퍼
        ]
    },

    // 특수 포메이션
    '4-1-2-1-2': {
        name: '4-1-2-1-2 (다이아몬드)',
        description: '다이아몬드 미드필드',
        rows: [
            ['LST', 'RST'],               // 공격수 라인
            ['CAM'],                      // 공격형 미드필더
            ['LCM', 'RCM'],               // 측면 미드필더
            ['DM'],                       // 수비형 미드필더
            ['LB', 'LCB', 'RCB', 'RB'],   // 수비수 라인
            ['GK']                        // 골키퍼
        ]
    },
    '4-4-1-1': {
        name: '4-4-1-1',
        description: '세컨드 스트라이커 활용',
        rows: [
            ['ST'],                       // 공격수 라인
            ['SS'],                       // 세컨드 스트라이커
            ['LM', 'LCM', 'RCM', 'RM'],   // 미드필더 라인
            ['LB', 'LCB', 'RCB', 'RB'],   // 수비수 라인
            ['GK']                        // 골키퍼
        ]
    },
    '4-6-0': {
        name: '4-6-0 (False 9)',
        description: '펄스 나인 전술',
        rows: [
            ['LW', 'F9', 'RW'],           // 윙어와 펄스 나인
            ['LCM', 'CM', 'RCM'],         // 미드필더 라인
            ['LB', 'LCB', 'RCB', 'RB'],   // 수비수 라인
            ['GK']                        // 골키퍼
        ]
    }
};

/**
 * 풋살 포메이션 (4명 + 골키퍼)
 */
var futsalFormations = {
    '1-2-1': {
        name: '1-2-1 (다이아몬드)',
        description: '가장 기본적인 풋살 포메이션',
        rows: [
            ['ST'],                       // 공격수
            ['LM', 'RM'],                 // 측면 미드필더
            ['DM'],                       // 수비형 미드필더
            ['GK']                        // 골키퍼
        ]
    },
    '2-2': {
        name: '2-2 (스퀘어)',
        description: '균형잡힌 풋살 포메이션',
        rows: [
            ['LST', 'RST'],               // 공격수 라인
            ['LM', 'RM'],                 // 미드필더 라인
            ['GK']                        // 골키퍼
        ]
    },
    '3-1': {
        name: '3-1',
        description: '수비적 풋살 포메이션',
        rows: [
            ['ST'],                       // 공격수
            ['LM', 'CM', 'RM'],           // 미드필더 라인
            ['GK']                        // 골키퍼
        ]
    },
    '1-3': {
        name: '1-3',
        description: '공격적 풋살 포메이션',
        rows: [
            ['LW', 'ST', 'RW'],           // 공격수 라인
            ['DM'],                       // 수비형 미드필더
            ['GK']                        // 골키퍼
        ]
    },
    '2-1-1': {
        name: '2-1-1',
        description: '비대칭 풋살 포메이션',
        rows: [
            ['ST'],                       // 공격수
            ['CAM'],                      // 공격형 미드필더
            ['LM', 'RM'],                 // 수비형 미드필더
            ['GK']                        // 골키퍼
        ]
    }
};

/**
 * 포메이션별 필드 렌더링 함수 (ES5 호환 버전)
 */
function renderFormationField(formation, container, currentPositions) {
    container.empty();
    
    var formationData = formations[formation] || futsalFormations[formation];
    if (!formationData) {
        console.error('포메이션을 찾을 수 없습니다:', formation);
        return;
    }
    
    // 필드 컨테이너에 포메이션 클래스 추가
    container.removeClass().addClass('soccer-field formation-' + formation.replace(/[^a-zA-Z0-9]/g, ''));
    
    // 각 라인별로 포지션 생성
    formationData.rows.forEach(function(row, rowIndex) {
        var $row = $('<div class="position-row">').addClass('row-' + rowIndex);
        
        // 골키퍼 라인 표시 (ES5 호환)
        if (row.indexOf('GK') !== -1) {
            $row.addClass('goalkeeper-row');
        }
        
        row.forEach(function(position) {
            var $position = createPositionElement(position, currentPositions);
            $row.append($position);
        });
        
        container.append($row);
    });
}

/**
 * 포지션 요소 생성 (ES5 호환 버전)
 */
function createPositionElement(position, currentPositions) {
    var $position = $('<div class="position">').attr('data-position', position);
    
    // 포지션 라벨
    var $label = $('<div class="position-label">').text(position);
    $position.append($label);
    
    // 선수 정보 컨테이너
    var $player = $('<div class="position-player">');
    $position.append($player);
    
    // 현재 배치된 선수 정보 표시
    if (currentPositions && currentPositions[position]) {
        var playerId = currentPositions[position];
        var player = null;
        
        // squadManager가 있으면 사용, 없으면 전역 lineup 변수 사용
        if (typeof squadManager !== 'undefined' && squadManager && squadManager.players) {
            // ES5 호환 find 함수
            for (var i = 0; i < squadManager.players.length; i++) {
                if (squadManager.players[i].sj_id == playerId) {
                    player = squadManager.players[i];
                    break;
                }
            }
        } else if (typeof lineup !== 'undefined' && lineup[position]) {
            player = lineup[position];
        }
        
        // 기본 처리: playerId만으로 간단한 객체 생성
        if (!player) {
            player = {
                sj_id: playerId,
                name: 'Player' + playerId,
                number: '-'
            };
        }
        
        if (player) {
            // ES5 호환 문자열 연결
            $player.html(
                '<span class="number">' + (player.number || '-') + '</span>' +
                '<span class="name">' + (player.name || 'Player') + '</span>'
            );
            $position.addClass('filled');
        }
    }
    
    // 클릭 이벤트 (onPositionClick 함수가 있을 때만)
    if (typeof onPositionClick === 'function') {
        $position.on('click', function() {
            onPositionClick(position);
        });
    }
    
    // 포지션 타입별 스타일 클래스 추가 (ES5 호환)
    if (position.indexOf('GK') !== -1) {
        $position.addClass('goalkeeper');
    } else if (position.indexOf('CB') !== -1 || position.indexOf('LB') !== -1 || position.indexOf('RB') !== -1 || position.indexOf('WB') !== -1) {
        $position.addClass('defender');
    } else if (position.indexOf('DM') !== -1 || position.indexOf('CM') !== -1 || position.indexOf('AM') !== -1 || position.indexOf('M') !== -1) {
        $position.addClass('midfielder');
    } else if (position.indexOf('ST') !== -1 || position.indexOf('W') !== -1 || position.indexOf('F') !== -1) {
        $position.addClass('forward');
    }
    
    return $position;
}

/**
 * 포지션 약어 설명
 */
var positionNames = {
    // 골키퍼
    'GK': '골키퍼',
    
    // 수비수
    'CB': '센터백',
    'LCB': '좌측 센터백',
    'RCB': '우측 센터백',
    'LB': '좌측 풀백',
    'RB': '우측 풀백',
    'LWB': '좌측 윙백',
    'RWB': '우측 윙백',
    
    // 미드필더
    'DM': '수비형 미드필더',
    'LDM': '좌측 수비형 미드필더',
    'RDM': '우측 수비형 미드필더',
    'CM': '센터 미드필더',
    'LCM': '좌측 센터 미드필더',
    'RCM': '우측 센터 미드필더',
    'CAM': '공격형 미드필더',
    'LAM': '좌측 공격형 미드필더',
    'RAM': '우측 공격형 미드필더',
    'LM': '좌측 미드필더',
    'RM': '우측 미드필더',
    
    // 공격수
    'ST': '스트라이커',
    'LST': '좌측 스트라이커',
    'RST': '우측 스트라이커',
    'LW': '좌측 윙어',
    'RW': '우측 윙어',
    'SS': '세컨드 스트라이커',
    'F9': '펄스 나인'
};

/**
 * 포지션 이름 반환
 */
function getPositionName(position) {
    return positionNames[position] || position;
}

/**
 * 포메이션 유효성 검사
 */
function isValidFormation(formation) {
    return formations.hasOwnProperty(formation) || futsalFormations.hasOwnProperty(formation);
}

/**
 * 포메이션 타입 확인 (축구/풋살)
 */
function getFormationType(formation) {
    if (formations.hasOwnProperty(formation)) {
        return 'soccer';
    } else if (futsalFormations.hasOwnProperty(formation)) {
        return 'futsal';
    }
    return null;
}

/**
 * 포메이션별 포지션 목록 반환
 */
function getFormationPositions(formation) {
    var formationData = formations[formation] || futsalFormations[formation];
    if (!formationData) return [];
    
    var positions = [];
    formationData.rows.forEach(function(row) {
        positions = positions.concat(row);
    });
    
    return positions;
}

/**
 * 포지션 개수 반환
 */
function getPositionCount(formation) {
    return getFormationPositions(formation).length;
}

// 포메이션 시스템이 로드되었음을 알림
console.log('팀플렉스 포메이션 시스템 로드 완료 - 축구:', Object.keys(formations).length, '개, 풋살:', Object.keys(futsalFormations).length, '개');