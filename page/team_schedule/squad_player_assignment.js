/**
* 팀플렉스 스쿼드 선수 배치 관리 JavaScript
* 파일명: squad_player_assignment.js
*/

// 전역 변수
var squadManager = {
   currentSquadId: null,
   players: [],
   isLoading: false
};

/**
* 스쿼드 매니저 초기화
*/
function initSquadManager(players, formations, squadData, positionData) {
   squadManager.players = players || [];
}

/**
* 포지션 클릭 이벤트 핸들러
*/
function onPositionClick(position) {
   if (squadManager.isLoading) return;
   
   var currentPlayerId = getCurrentPlayerInPosition(position);
   showPlayerSelectionModal(position, currentPlayerId);
}

/**
* 현재 포지션에 배치된 선수 ID 반환
*/
function getCurrentPlayerInPosition(position) {
   if (!squadManager.currentSquadId || !positionData[squadManager.currentSquadId]) {
       return null;
   }
   
   return positionData[squadManager.currentSquadId][position] || null;
}

/**
* 선수 선택 모달 표시
*/
function showPlayerSelectionModal(position, currentPlayerId) {
   $('#playerSelectionModal').remove();
   
   if (!squadManager.players || squadManager.players.length === 0) {
       alert(safeTranslate('no_assignable_players'));
       return;
   }
   
   var modalHtml = createModalHtml(position, squadManager.players, currentPlayerId);
   
   $('body').append(modalHtml);
   $('#playerSelectionModal').fadeIn(300);
   
   bindModalEvents(position);
}

/**
* 모달 HTML 생성
*/
function createModalHtml(position, players, currentPlayerId) {
   var playerGroups = categorizePlayersByPosition(players, currentPlayerId);
   
   return `
       <div id="playerSelectionModal" class="modal-overlay dosmodal" style="display: none;">
           <div class="modal-content">
               <div class="flex">
                   <div class="modal-header">
                       <h3>${safeTranslate('player_selection')} - ${position}</h3>
                       <button type="button" class="modal-close" onclick="closePlayerModal()">✕</button>
                   </div>
                   <div class="modal-footer">
                       <button type="button" class="btn-clear" onclick="clearPosition('${position}')">${safeTranslate('clear_position')}</button>
                       <button type="button" class="btn-cancel" onclick="closePlayerModal()">${safeTranslate('cancel')}</button>
                   </div>
               </div>
               <div class="modal-body">
                   ${generatePlayerSectionsHtml(playerGroups, currentPlayerId)}
               </div>
           </div>
       </div>
   `;
}

/**
* 선수들을 배치 상태별로 분류
*/
function categorizePlayersByPosition(players, currentPlayerId) {
   var unassigned = [];
   var assigned = [];
   
   players.forEach(function(player) {
       var positionInfo = getPlayerCurrentPosition(player.sj_id);
       
       if(player.sj_id == currentPlayerId) {
           unassigned.push(player);
       } else if(positionInfo) {
           player.currentPosition = positionInfo;
           assigned.push(player);
       } else {
           unassigned.push(player);
       }
   });
   
   return { unassigned: unassigned, assigned: assigned };
}

/**
* 특정 선수의 현재 배치 포지션 정보 반환
*/
function getPlayerCurrentPosition(playerId) {
   if(!squadManager.currentSquadId || !positionData[squadManager.currentSquadId]) {
       return null;
   }
   
   var positions = positionData[squadManager.currentSquadId];
   for(var position in positions) {
       if(positions[position] == playerId) {
           return {
               position: position,
               positionName: getPositionName(position) || position
           };
       }
   }
   
   return null;
}

/**
* 선수 섹션별 HTML 생성
*/
function generatePlayerSectionsHtml(playerGroups, currentPlayerId) {
   var html = '';
   
   if(playerGroups.unassigned.length > 0) {
       html += `
           <div class="player-section2 unassigned-section">
               <div class="section-header">
                   <h4>${safeTranslate('unassigned_players')} <span class="count">(${playerGroups.unassigned.length}${safeTranslate('people_count')})</span></h4>
               </div>
               <div class="player-selection-list">
                   ${generatePlayerListHtml(playerGroups.unassigned, currentPlayerId, 'unassigned')}
               </div>
           </div>
       `;
   }
   
   if(playerGroups.assigned.length > 0) {
       html += `
           <div class="player-section2 assigned-section">
               <div class="section-header">
                   <h4>${safeTranslate('assigned_players')} <span class="count">(${playerGroups.assigned.length}${safeTranslate('people_count')})</span></h4>
               </div>
               <div class="player-selection-list">
                   ${generatePlayerListHtml(playerGroups.assigned, currentPlayerId, 'assigned')}
               </div>
           </div>
       `;
   }
   
   if(playerGroups.unassigned.length === 0 && playerGroups.assigned.length === 0) {
       html += `<div class="no-players">${safeTranslate('no_assignable_players')}</div>`;
   }
   
   return html;
}

/**
* 선수 목록 HTML 생성
*/
function generatePlayerListHtml(players, currentPlayerId, sectionType) {
   if(!players || players.length === 0) {
       return `<div class="no-players">${safeTranslate('no_players')}</div>`;
   }
   
   var html = '<ul class="player-list">';
   
   players.forEach(function(player) {
       var isSelected = (currentPlayerId && player.sj_id == currentPlayerId);
       var selectedClass = isSelected ? 'selected' : '';
       var sectionClass = 'section-' + sectionType;
       var isGuest = (player.is_guest == '1' || player.is_guest === 1 || player.is_guest === true);
       
       html += `
           <li class="player-selection-item ${selectedClass} ${sectionClass}" 
               data-player-id="${player.sj_id}" 
               data-player-name="${player.name}" 
               data-player-number="${player.number || ''}">
               <div class="player-info">
                   <span class="player-number">${player.number || '-'}</span>
                   <span class="player-name">${player.name}</span>
                   <div class="player-badges">
                       ${isGuest ? '<span class="guest-badge">' + safeTranslate('guest') + '</span>' : ''}
                       ${isSelected ? '<span class="current-badge">' + safeTranslate('current_assigned') + '</span>' : ''}
                       ${player.currentPosition ? '<span class="position-badge">' + player.currentPosition.position + '</span>' : ''}
                   </div>
               </div>
           </li>
       `;
   });
   
   html += '</ul>';
   return html;
}

/**
* 모달 이벤트 바인딩
*/
function bindModalEvents(position) {
   $('#playerSelectionModal').on('click', function(e) {
       if (e.target === this) closePlayerModal();
   });
   
   $('.player-selection-item').on('click', function() {
       var playerId = $(this).data('player-id');
       var playerName = $(this).data('player-name');
       var playerNumber = $(this).data('player-number');
       
       assignPlayerToPosition(position, playerId, playerName, playerNumber);
       closePlayerModal();
   });
}

/**
* 선수를 포지션에 배치
*/
function assignPlayerToPosition(position, playerId, playerName, playerNumber) {
   if (squadManager.isLoading) return;
   
   if (!squadManager.currentSquadId) {
       createNewSquad(function(squadId) {
           squadManager.currentSquadId = squadId;
           executePlayerPositionSave(position, playerId, playerName, playerNumber);
       });
   } else {
       executePlayerPositionSave(position, playerId, playerName, playerNumber);
   }
}

/**
* 새 스쿼드 생성
*/
function createNewSquad(callback) {
   var formData = {
       w: 'create_squad',
       ts_id: scheduleId,
       sq_type: getCurrentTeam(),
       sq_quarter: getCurrentQuarter(),
       sq_formation: getCurrentFormation()
   };
   
   squadManager.isLoading = true;
   showLoading();
   
   $.ajax({
       url: './update_squad.php',
       type: 'POST',
       data: formData,
       dataType: 'json',
       success: function(response) {
           if (response.status && response.squad_id) {
               if(!squadData[currentTeam]) squadData[currentTeam] = {};
               squadData[currentTeam][currentQuarter] = {
                   sq_id: response.squad_id,
                   formation: getCurrentFormation()
               };
               
               callback(response.squad_id);
           } else {
               alert(response.msg || safeTranslate('error_squad_creation'));
           }
       },
       error: function() {
           alert(safeTranslate('error_squad_creation'));
       },
       complete: function() {
           squadManager.isLoading = false;
           hideLoading();
       }
   });
}

/**
* 선수 포지션 저장 실행
*/
function executePlayerPositionSave(position, playerId, playerName, playerNumber) {
   var formData = {
       w: 'save_position_move',
       sq_id: squadManager.currentSquadId,
       sp_position: position,
       sj_id: playerId,
	   ts_id: scheduleId
   };
   
   squadManager.isLoading = true;
   showLoading();
   
   $.ajax({
       url: './update_squad.php',
       type: 'POST',
       data: formData,
       dataType: 'json',
       success: function(response) {
           if (response.status) {
               // positionData 배열→객체 변환 처리
               if (Array.isArray(positionData)) {
                   var tempData = {};
                   positionData.forEach(function(item, index) {
                       if (item) tempData[index] = item;
                   });
                   positionData = tempData;
               }
               
               // 데이터 구조 초기화
               if (!positionData[squadManager.currentSquadId]) {
                   positionData[squadManager.currentSquadId] = {};
               }
               
               if (!squadData[currentTeam]) squadData[currentTeam] = {};
               if (!squadData[currentTeam][currentQuarter]) {
                   squadData[currentTeam][currentQuarter] = {
                       sq_id: squadManager.currentSquadId,
                       formation: currentFormation
                   };
               }
               
               // 기존 위치에서 제거
               for (var pos in positionData[squadManager.currentSquadId]) {
                   if (positionData[squadManager.currentSquadId][pos] == playerId) {
                       delete positionData[squadManager.currentSquadId][pos];
                       clearPositionDisplay(pos);
                   }
               }
               
               // 새 위치에 배치
               positionData[squadManager.currentSquadId][position] = playerId;
               updatePositionDisplay(position, playerName, playerNumber);
               
               if (typeof updatePlayerQuarterInfo === 'function') {
                   updatePlayerQuarterInfo();
               }
           } else {
               alert(response.msg || safeTranslate('error_player_assignment'));
           }
       },
       error: function() {
           alert(safeTranslate('error_player_assignment'));
       },
       complete: function() {
           squadManager.isLoading = false;
           hideLoading();
       }
   });
}

/**
* 포지션 비우기
*/
function clearPosition(position) {
   if (squadManager.isLoading || !squadManager.currentSquadId) return;
   
   if (!confirm(safeTranslate('confirm_clear_position'))) return;
   
   squadManager.isLoading = true;
   showLoading();
   
   $.ajax({
       url: './update_squad.php',
       type: 'POST',
       data: {
           w: 'clear_position',
           sq_id: squadManager.currentSquadId,
           sp_position: position
       },
       dataType: 'json',
       success: function(response) {
           if (response.status) {
               clearPositionDisplay(position);
               if (typeof updatePlayerQuarterInfo === 'function') {
                   updatePlayerQuarterInfo();
               }
               closePlayerModal();
           } else {
               alert(response.msg || safeTranslate('error_clear_position'));
           }
       },
       error: function() {
           alert(safeTranslate('error_clear_position'));
       },
       complete: function() {
           squadManager.isLoading = false;
           hideLoading();
       }
   });
}

/**
* 포지션 표시 업데이트
*/
function updatePositionDisplay(position, playerName, playerNumber) {
   var $position = $(`.position[data-position="${position}"]`);
   if ($position.length === 0) return;
   
   $position.find('.position-player').html(`
       <span class="number">${playerNumber || '-'}</span>
       <span class="name">${playerName}</span>
   `);
   $position.addClass('filled');
}

/**
* 포지션 표시 비우기
*/
function clearPositionDisplay(position) {
   var $position = $(`.position[data-position="${position}"]`);
   if ($position.length === 0) return;
   
   $position.find('.position-player').empty();
   $position.removeClass('filled');
}

/**
* 모달 닫기
*/
function closePlayerModal() {
   $('#playerSelectionModal').fadeOut(300, function() {
       $(this).remove();
   });
}

/**
* 현재 팀/쿼터/포메이션 반환
*/
function getCurrentTeam() {
   return $('#current_team').val() || 'our';
}

function getCurrentQuarter() {
   return parseInt($('#current_quarter').val()) || 1;
}

function getCurrentFormation() {
   return $('#formation').val() || '4-3-3';
}

/**
* 로딩 표시/숨김
*/
function showLoading() {
   squadManager.isLoading = true;
   if (typeof loadingStart === 'function') loadingStart();
}

function hideLoading() {
   squadManager.isLoading = false;
   if (typeof loadingEnd === 'function') loadingEnd();
}

/**
* 안전한 번역 함수
*/
function safeTranslate(key) {
   if (typeof window.squadTranslations !== 'undefined' && window.squadTranslations[key]) {
       return window.squadTranslations[key];
   }
   
   console.warn('[번역 누락]', key);
   return key;
}

/**
* jQuery ready 이벤트
*/
$(document).ready(function() {
   $(document).on('keydown', function(e) {
       if (e.key === 'Escape') closePlayerModal();
   });
});