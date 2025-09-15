/**
 * 팀플렉스 스쿼드 관리 공통 JavaScript 함수
 */

// 포메이션 정의
const FORMATIONS = {
    soccer: {
        // 백3 포메이션
        '3-5-2': {
            name: '3-5-2',
            description: '3명의 수비수, 5명의 미드필더, 2명의 공격수'
        },
        '3-4-3': {
            name: '3-4-3',
            description: '3명의 수비수, 4명의 미드필더, 3명의 공격수'
        },
        '3-3-3-1': {
            name: '3-3-3-1',
            description: '3명의 수비수, 두 층의 미드필더(3-3), 1명의 공격수'
        },
        '3-4-1-2': {
            name: '3-4-1-2',
            description: '3명의 수비수, 4명의 미드필더, 1명의 공격형 미드필더, 2명의 공격수'
        },
        '3-6-1': {
            name: '3-6-1',
            description: '3명의 수비수, 6명의 미드필더, 1명의 공격수'
        },
        '3-4-2-1': {
            name: '3-4-2-1',
            description: '3명의 수비수, 4명의 미드필더, 2명의 공격형 미드필더, 1명의 공격수'
        },
        '3-2-4-1': {
            name: '3-2-4-1',
            description: '3명의 수비수, 2명의 수비형 미드필더, 4명의 공격형 미드필더, 1명의 공격수'
        },
        
        // 백4 포메이션
        '4-4-2': {
            name: '4-4-2',
            description: '4명의 수비수, 4명의 미드필더, 2명의 공격수'
        },
        '4-3-3': {
            name: '4-3-3',
            description: '4명의 수비수, 3명의 미드필더, 3명의 공격수'
        },
        '4-2-3-1': {
            name: '4-2-3-1',
            description: '4명의 수비수, 2명의 수비형 미드필더, 3명의 공격형 미드필더, 1명의 공격수'
        },
        '4-3-1-2': {
            name: '4-3-1-2',
            description: '4명의 수비수, 3명의 미드필더, 1명의 공격형 미드필더, 2명의 공격수'
        },
        '4-2-2-2': {
            name: '4-2-2-2',
            description: '4명의 수비수, 2명의 수비형 미드필더, 2명의 공격형 미드필더, 2명의 공격수'
        },
        '4-3-2-1': {
            name: '4-3-2-1',
            description: '4명의 수비수, 3명의 미드필더, 2명의 공격형 미드필더, 1명의 공격수'
        },
        '4-1-4-1': {
            name: '4-1-4-1',
            description: '4명의 수비수, 1명의 수비형 미드필더, 4명의 미드필더, 1명의 공격수'
        },
        '4-1-2-3': {
            name: '4-1-2-3',
            description: '4명의 수비수, 1명의 수비형 미드필더, 2명의 미드필더, 3명의 공격수'
        },
        '4-5-1': {
            name: '4-5-1',
            description: '4명의 수비수, 5명의 미드필더, 1명의 공격수'
        },
        '4-4-1-1': {
            name: '4-4-1-1',
            description: '4명의 수비수, 4명의 미드필더, 1명의 공격형 미드필더, 1명의 공격수'
        },
        '4-6-0': {
            name: '4-6-0',
            description: '4명의 수비수, 6명의 미드필더, 스트라이커 없음'
        },
        '4-2-4': {
            name: '4-2-4',
            description: '4명의 수비수, 2명의 미드필더, 4명의 공격수'
        },
        
        // 백5 포메이션
        '5-3-2': {
            name: '5-3-2',
            description: '5명의 수비수, 3명의 미드필더, 2명의 공격수'
        },
        '5-4-1': {
            name: '5-4-1',
            description: '5명의 수비수, 4명의 미드필더, 1명의 공격수'
        },
        '5-2-3': {
            name: '5-2-3',
            description: '5명의 수비수, 2명의 미드필더, 3명의 공격수'
        }
    },
    futsal: {
        '1-2-1': {
            name: '1-2-1 (다이아몬드)',
            description: '1명의 수비수, 2명의 미드필더, 1명의 공격수'
        },
        '2-2': {
            name: '2-2 (스퀘어)',
            description: '2명의 수비수, 2명의 공격수'
        },
        '3-1': {
            name: '3-1',
            description: '3명의 수비수, 1명의 공격수'
        },
        '1-3': {
            name: '1-3',
            description: '1명의 수비수, 3명의 공격수'
        },
        '2-1-1': {
            name: '2-1-1 (삼각형)',
            description: '2명의 수비수, 1명의 미드필더, 1명의 공격수'
        }
    }
};

/**
 * 포메이션 레이아웃 생성
 * @param {string} type 스쿼드 타입 (soccer/futsal)
 * @param {string} formation 포메이션 코드
 * @param {HTMLElement} container 포메이션을 표시할 컨테이너
 * @param {Object} positions 포지션별 선수 정보
 * @param {Function} clickCallback 포지션 클릭 시 콜백 함수 (선택 사항)
 */
function renderFormation(type, formation, container, positions, clickCallback) {
    // 컨테이너 초기화
    container.innerHTML = '';
    
    // 포메이션 정보 가져오기
    const formationData = FORMATIONS[type][formation];
    if (!formationData) return;
    
    // 각 포지션 행 생성
    formationData.rows.forEach((row, rowIndex) => {
        const rowDiv = document.createElement('div');
        rowDiv.className = 'position-row' + (rowIndex === 0 ? ' gk-row' : '');
        
        // 각 포지션 생성
        row.forEach(position => {
            const posDiv = document.createElement('div');
            posDiv.className = 'position';
            posDiv.dataset.position = position;
            
            // 포지션 아이콘
            const iconDiv = document.createElement('div');
            iconDiv.className = 'player-icon';
            iconDiv.textContent = position;
            posDiv.appendChild(iconDiv);
            
            // 선수 정보 컨테이너
            const infoDiv = document.createElement('div');
            infoDiv.className = 'player-info';
            posDiv.appendChild(infoDiv);
            
            // 선수 정보 추가
            if (positions && positions[position]) {
                const player = positions[position];
                posDiv.classList.add('filled');
                
                // 등번호
                if (player.number) {
                    const numSpan = document.createElement('span');
                    numSpan.className = 'number';
                    numSpan.textContent = player.number;
                    infoDiv.appendChild(numSpan);
                }
                
                // 이름
                const nameSpan = document.createElement('span');
                nameSpan.className = 'name';
                nameSpan.textContent = player.name;
                infoDiv.appendChild(nameSpan);
            }
            
            // 클릭 이벤트 핸들러 추가
            if (typeof clickCallback === 'function') {
                posDiv.addEventListener('click', () => clickCallback(position));
            }
            
            rowDiv.appendChild(posDiv);
        });
        
        container.appendChild(rowDiv);
    });
}

/**
 * 선수 선택 모달 창 생성
 * @param {Array} players 선수 목록
 * @param {Function} selectCallback 선수 선택 시 콜백 함수
 * @param {Function} clearCallback 포지션 비우기 콜백 함수
 * @param {Function} cancelCallback 취소 콜백 함수
 * @param {number|null} selectedPlayerId 현재 선택된 선수 ID
 */
function createPlayerSelectionModal(players, selectCallback, clearCallback, cancelCallback, selectedPlayerId = null) {
    // 기존 모달 제거
    const existingModal = document.getElementById('playerSelectionModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // 모달 컨테이너 생성
    const modal = document.createElement('div');
    modal.id = 'playerSelectionModal';
    modal.className = 'player-selection-modal';
    
    // 모달 내용 생성
    const modalContent = document.createElement('div');
    modalContent.className = 'modal-content';
    
    // 제목
    const title = document.createElement('h3');
    title.textContent = '선수 선택';
    modalContent.appendChild(title);
    
    // 선수 목록
    const playerList = document.createElement('div');
    playerList.className = 'player-list';
    
    const ul = document.createElement('ul');
    players.forEach(player => {
        const li = document.createElement('li');
        li.dataset.sjId = player.sj_id;
        
        // 선택된 선수인 경우 하이라이트
        if (selectedPlayerId && player.sj_id == selectedPlayerId) {
            li.className = 'selected';
        }
        
        // 등번호
        const numSpan = document.createElement('span');
        numSpan.className = 'player-number';
        numSpan.textContent = player.number || '-';
        li.appendChild(numSpan);
        
        // 이름
        const nameSpan = document.createElement('span');
        nameSpan.className = 'player-name';
        nameSpan.textContent = player.name;
        li.appendChild(nameSpan);
        
        // 게스트 배지
        if (player.is_guest) {
            const guestBadge = document.createElement('span');
            guestBadge.className = 'guest-badge';
            guestBadge.textContent = '게스트';
            li.appendChild(guestBadge);
        }
        
        // 클릭 이벤트
        li.addEventListener('click', () => {
            selectCallback(player.sj_id, player.name, player.number);
        });
        
        ul.appendChild(li);
    });
    
    playerList.appendChild(ul);
    modalContent.appendChild(playerList);
    
    // 버튼 영역
    const buttonDiv = document.createElement('div');
    buttonDiv.className = 'modal-buttons';
    
    // 취소 버튼
    const cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.textContent = '취소';
    cancelBtn.addEventListener('click', cancelCallback);
    buttonDiv.appendChild(cancelBtn);
    
    // 비우기 버튼
    const clearBtn = document.createElement('button');
    clearBtn.type = 'button';
    clearBtn.textContent = '비우기';
    clearBtn.addEventListener('click', clearCallback);
    buttonDiv.appendChild(clearBtn);
    
    modalContent.appendChild(buttonDiv);
    modal.appendChild(modalContent);
    
    // 문서에 모달 추가
    document.body.appendChild(modal);
    
    return modal;
}

/**
 * AJAX를 사용한 스쿼드 저장 함수
 * @param {string} url 저장 URL
 * @param {Object} data 저장할 데이터
 * @param {Function} successCallback 성공 시 콜백 함수
 * @param {Function} errorCallback 실패 시 콜백 함수
 */
function saveSquadData(url, data, successCallback, errorCallback) {
    // 로딩 표시 함수가 있으면 호출
    if (typeof loadingStart === 'function') {
        loadingStart();
    }
    
    // AJAX 요청
    $.ajax({
        url: url,
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response.status) {
                if (typeof successCallback === 'function') {
                    successCallback(response);
                }
            } else {
                if (typeof errorCallback === 'function') {
                    errorCallback(response.msg);
                } else {
                    alert(response.msg);
                }
            }
        },
        error: function(xhr, status, error) {
            if (typeof errorCallback === 'function') {
                errorCallback('오류가 발생했습니다: ' + error);
            } else {
                alert('오류가 발생했습니다: ' + error);
            }
        },
        complete: function() {
            // 로딩 종료 함수가 있으면 호출
            if (typeof loadingEnd === 'function') {
                loadingEnd();
            }
        }
    });
}