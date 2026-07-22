/**
 * Certificate Designer - Complete Drag & Drop System
 * Includes: Canvas Dragging + Side Panel Reordering
 */

// SYSTEM 1: CANVAS DRAGGING (Move elements on certificate)
document.addEventListener('DOMContentLoaded', function() {
    const previewContainer = document.getElementById('previewContainer');
    if (!previewContainer) {
        console.log('[Canvas Drag] No preview container found');
    } else {
        console.log('[Canvas Drag] Initializing...');
        initCanvasDragging();
    }
});

function initCanvasDragging() {
    const previewContainer = document.getElementById('previewContainer');
    if (!previewContainer) return;

    const previewTitle = document.getElementById('previewTitle');
    const previewAwardText = document.getElementById('previewAwardText');
    const previewCertName = document.getElementById('previewCertName');
    const previewSignatureLine = document.getElementById('previewSignatureLine');
    const previewDates = document.getElementById('previewDates');
    const previewQR = document.getElementById('previewQR');
    const previewBg = document.getElementById('previewBg');
    const previewLogo = document.getElementById('previewLogo');
    const previewSignature = document.getElementById('previewSignature');

    const elementMap = [
        {el: previewTitle, field: 'title'},
        {el: previewAwardText, field: 'award'},
        {el: previewCertName, field: 'certname'},
        {el: previewSignatureLine, field: 'signatureline'},
        {el: previewLogo, field: 'logo'},
        {el: previewSignature, field: 'signature'},
        {el: previewDates, field: 'dates'},
        {el: previewQR, field: 'qr'}
    ];

    let editMode = false;
    let draggedElement = null;
    let offset = {x: 0, y: 0};

    function pageDims() {
        const orientationInput = document.querySelector('select[name="orientation"]');
        const portrait = orientationInput && orientationInput.value === 'P';
        return portrait ? {w: 210, h: 297} : {w: 297, h: 210};
    }

    function fieldFor(el) {
        for (let i = 0; i < elementMap.length; i++) {
            if (elementMap[i].el === el) {
                return elementMap[i].field;
            }
        }
        return null;
    }

    function normalize(el) {
        if (!el || el.dataset.normalized === '1') return;
        try {
            const rect = el.getBoundingClientRect();
            const containerRect = previewContainer.getBoundingClientRect();
            el.style.transform = 'none';
            el.style.right = 'auto';
            el.style.bottom = 'auto';
            el.style.left = (rect.left - containerRect.left) + 'px';
            el.style.top = (rect.top - containerRect.top) + 'px';
            el.dataset.normalized = '1';
        } catch (e) {
            console.error('[Canvas Drag] Error:', e);
        }
    }

    function applyMm(el, xMm, yMm) {
        if (!el) return;
        try {
            const dims = pageDims();
            el.style.transform = 'none';
            el.style.right = 'auto';
            el.style.bottom = 'auto';
            el.style.left = ((xMm / dims.w) * previewContainer.offsetWidth) + 'px';
            el.style.top = ((yMm / dims.h) * previewContainer.offsetHeight) + 'px';
            el.dataset.normalized = '1';
        } catch (e) {
            console.error('[Canvas Drag] Error:', e);
        }
    }

    function saveElementPosition(el) {
        if (!el) return;
        const field = fieldFor(el);
        if (!field) return;
        try {
            const x = parseFloat(el.style.left) || 0;
            const y = parseFloat(el.style.top) || 0;
            const dims = pageDims();
            const xMm = (x / previewContainer.offsetWidth) * dims.w;
            const yMm = (y / previewContainer.offsetHeight) * dims.h;
            const xField = document.querySelector('input[name="' + field + '_x"]');
            const yField = document.querySelector('input[name="' + field + '_y"]');
            if (xField) xField.value = xMm.toFixed(2);
            if (yField) yField.value = yMm.toFixed(2);
        } catch (e) {
            console.error('[Canvas Drag] Error:', e);
        }
    }

    function startDrag(e) {
        if (!editMode || e.button !== 0) return;
        try {
            e.preventDefault();
            draggedElement = this;
            normalize(draggedElement);
            draggedElement.classList.add('dragging');
            const rect = draggedElement.getBoundingClientRect();
            offset.x = e.clientX - rect.left;
            offset.y = e.clientY - rect.top;
            document.addEventListener('mousemove', dragMove);
            document.addEventListener('mouseup', stopDrag);
        } catch (e) {
            console.error('[Canvas Drag] Error:', e);
        }
    }

    function dragMove(e) {
        if (!draggedElement) return;
        try {
            const containerRect = previewContainer.getBoundingClientRect();
            let x = e.clientX - containerRect.left - offset.x;
            let y = e.clientY - containerRect.top - offset.y;
            x = Math.max(0, Math.min(x, containerRect.width - draggedElement.offsetWidth));
            y = Math.max(0, Math.min(y, containerRect.height - draggedElement.offsetHeight));
            draggedElement.style.left = x + 'px';
            draggedElement.style.top = y + 'px';
            saveElementPosition(draggedElement);
        } catch (e) {
            console.error('[Canvas Drag] Error:', e);
        }
    }

    function stopDrag() {
        if (!draggedElement) return;
        try {
            draggedElement.classList.remove('dragging');
            saveElementPosition(draggedElement);
            draggedElement = null;
            document.removeEventListener('mousemove', dragMove);
            document.removeEventListener('mouseup', stopDrag);
        } catch (e) {
            console.error('[Canvas Drag] Error:', e);
        }
    }

    elementMap.forEach(function(item) {
        if (item.el) {
            item.el.addEventListener('mousedown', startDrag);
        }
    });

    const editToggle = document.createElement('div');
    editToggle.className = 'edit-mode-toggle';
    editToggle.innerHTML = '<input type="checkbox" id="editModeToggle"> ' +
        '<label for="editModeToggle"><strong>Edit Element Positions</strong></label>';
    previewContainer.parentElement.insertBefore(editToggle, previewContainer);

    const editCheckbox = document.getElementById('editModeToggle');
    editCheckbox.addEventListener('change', function() {
        try {
            editMode = this.checked;
            previewContainer.classList.toggle('edit-mode', editMode);
            if (editMode) {
                elementMap.forEach(function(item) {
                    if (item.el) {
                        normalize(item.el);
                        saveElementPosition(item.el);
                    }
                });
                console.log('[Canvas Drag] Edit mode ON');
            } else {
                console.log('[Canvas Drag] Edit mode OFF');
            }
        } catch (e) {
            console.error('[Canvas Drag] Error:', e);
        }
    });

    const titleInput = document.querySelector('input[name="certificatetitle"]');
    const awardInput = document.querySelector('textarea[name="awardtext"]');
    const fontInput = document.querySelector('select[name="font"]');
    const colorInput = document.querySelector('input[name="fontcolor"]');
    const signatureInput = document.querySelector('input[name="signatureline"]');
    const qrcodeCheckbox = document.querySelector('input[name="showqrcode"]');

    if (titleInput) {
        titleInput.addEventListener('input', function() {
            if (previewTitle) previewTitle.textContent = this.value || 'Certificate';
        });
    }
    if (awardInput) {
        awardInput.addEventListener('input', function() {
            if (previewAwardText) previewAwardText.textContent = this.value || 'This certifies that...';
        });
    }
    if (signatureInput) {
        signatureInput.addEventListener('input', function() {
            if (previewSignatureLine) previewSignatureLine.textContent = this.value || 'Director';
        });
    }
    if (qrcodeCheckbox) {
        qrcodeCheckbox.addEventListener('change', function() {
            if (previewQR) previewQR.style.display = this.checked ? 'flex' : 'none';
        });
    }

    console.log('[Canvas Drag] ✓ Ready');
}

// SYSTEM 2: SIDE PANEL REORDERING (Drag in list)
(function() {
    'use strict';
    console.log('[Side Panel Reorder] Initializing...');

    function getSesskey() {
        const input = document.querySelector('input[name="sesskey"]');
        if (input && input.value) return input.value;
        const attr = document.querySelector('[data-sesskey]');
        if (attr && attr.dataset.sesskey) return attr.dataset.sesskey;
        const linkWithKey = document.querySelector('a[href*="sesskey="]');
        if (linkWithKey) {
            const match = linkWithKey.href.match(/sesskey=([a-z0-9]+)/i);
            if (match) return match[1];
        }
        return null;
    }

    function getPageUrl() {
        const currentUrl = new URL(window.location.href);
        const id = currentUrl.searchParams.get('id');
        if (!id) return null;
        return window.location.pathname + '?id=' + id;
    }

    function initElementListDragDrop() {
        const elementList = document.querySelector('.certmanager-el-list');
        if (!elementList) {
            console.log('[Side Panel Reorder] List not found');
            return false;
        }

        const sesskey = getSesskey();
        const pageUrl = getPageUrl();

        if (!sesskey || !pageUrl) {
            console.error('[Side Panel Reorder] Missing sesskey or pageUrl');
            return false;
        }

        console.log('[Side Panel Reorder] Ready with pageUrl:', pageUrl);
        elementList.classList.add('drag-enabled');

        let draggedItem = null;
        let dragOverPosition = null;
        let scheduledMoves = [];
        let isMoving = false;

        function getReorderableItems() {
            return Array.from(elementList.querySelectorAll('li:not(.is-background)'));
        }

        function buildOrderMap() {
            const map = {};
            getReorderableItems().forEach((item, idx) => {
                const eid = item.dataset.eid;
                if (eid) map[eid] = idx;
            });
            return map;
        }

        let initialOrderMap = buildOrderMap();

        function attachDragListeners(item) {
            if (item.classList.contains('is-background')) {
                item.draggable = false;
                return;
            }
            item.draggable = true;
            item.addEventListener('dragstart', handleDragStart);
            item.addEventListener('dragend', handleDragEnd);
            item.addEventListener('dragover', handleDragOver);
            item.addEventListener('dragenter', handleDragEnter);
            item.addEventListener('dragleave', handleDragLeave);
            item.addEventListener('drop', handleDrop);
        }

        function handleDragStart(e) {
            if (isMoving) {
                e.preventDefault();
                return;
            }
            draggedItem = this;
            if (draggedItem.classList.contains('is-background')) {
                e.preventDefault();
                return;
            }
            draggedItem.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            draggedItem.style.opacity = '0.5';
            console.log('[Side Panel Reorder] Drag started:', draggedItem.dataset.eid);
        }

        function handleDragEnd(e) {
            document.querySelectorAll('.certmanager-el-list li').forEach(li => {
                li.classList.remove('dragging', 'drag-over-top', 'drag-over-bottom');
                li.style.opacity = '';
            });
            draggedItem = null;
            dragOverPosition = null;
        }

        function handleDragOver(e) {
            if (e.preventDefault) e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            return false;
        }

        function handleDragEnter(e) {
            if (!draggedItem || this === draggedItem) return;
            if (this.classList.contains('is-background')) return;
            const rect = this.getBoundingClientRect();
            const midpoint = rect.top + rect.height / 2;
            const isAbove = e.clientY < midpoint;
            dragOverPosition = isAbove ? 'top' : 'bottom';
            this.classList.remove('drag-over-top', 'drag-over-bottom');
            this.classList.add('drag-over-' + dragOverPosition);
        }

        function handleDragLeave(e) {
            if (e.target === this) {
                this.classList.remove('drag-over-top', 'drag-over-bottom');
            }
        }

        function handleDrop(e) {
            if (e.stopPropagation) e.stopPropagation();
            if (!draggedItem || this === draggedItem) return false;
            if (this.classList.contains('is-background')) return false;
            this.classList.remove('drag-over-top', 'drag-over-bottom');
            if (dragOverPosition === 'top') {
                this.parentNode.insertBefore(draggedItem, this);
            } else {
                this.parentNode.insertBefore(draggedItem, this.nextSibling);
            }
            saveNewOrder();
            return false;
        }

        function saveNewOrder() {
            const items = getReorderableItems();
            const newOrderMap = {};
            items.forEach((item, idx) => {
                const eid = item.dataset.eid;
                if (eid) newOrderMap[eid] = idx;
            });
            scheduledMoves = [];
            Object.keys(newOrderMap).forEach(eid => {
                const oldPos = initialOrderMap[eid] || 0;
                const newPos = newOrderMap[eid] || 0;
                if (oldPos < newPos) {
                    for (let i = 0; i < (newPos - oldPos); i++) {
                        scheduledMoves.push({ eid, direction: 'movedown' });
                    }
                } else if (oldPos > newPos) {
                    for (let i = 0; i < (oldPos - newPos); i++) {
                        scheduledMoves.push({ eid, direction: 'moveup' });
                    }
                }
            });
            if (scheduledMoves.length > 0) {
                isMoving = true;
                executeNextMove();
            }
        }

        function executeNextMove() {
            if (scheduledMoves.length === 0) {
                console.log('[Side Panel Reorder] Moves completed');
                isMoving = false;
                initialOrderMap = buildOrderMap();
                return;
            }
            const move = scheduledMoves.shift();
            performMove(move.eid, move.direction, executeNextMove);
        }

        function performMove(eid, direction, callback) {
            const action = direction === 'moveup' ? 'moveup' : 'movedown';
            const url = pageUrl + '&action=' + action + '&eid=' + eid + '&sesskey=' + sesskey;
            console.log('[Side Panel Reorder] Move:', action, 'eid:', eid);
            fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
            .then(response => {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                console.log('[Side Panel Reorder] Success:', eid, action);
                if (callback) callback();
            })
            .catch(error => {
                console.error('[Side Panel Reorder] Failed:', error);
                if (callback) callback();
            });
        }

        function init() {
            const items = getReorderableItems();
            items.forEach(attachDragListeners);
            console.log('[Side Panel Reorder] Initialized -', items.length, 'items');
            return true;
        }

        return init();
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (initElementListDragDrop()) {
            console.log('[Side Panel Reorder] ✓ Ready');
        }
    });

    if (document.readyState === 'interactive' || document.readyState === 'complete') {
        setTimeout(function() {
            initElementListDragDrop();
        }, 100);
    }
})();