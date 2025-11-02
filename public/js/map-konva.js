/**
 * Konva-based map rendering module
 * Replaces SVG generation with canvas-based rendering
 */

const CELL_SIZE = 50;
const TRAP_SIZE = 3;
const FURNACE_SIZE = 2;

let stage = null;
let layer = null;
let mapObjects = [];
let occupiedPositions = new Set();

// Map view state
let offsetX = 0;
let offsetY = 0;
let scale = 1;
const minScale = 0.25;
const maxScale = 4.0;

// Dragging state
let isDragging = false;
let lastPointerPosition = { x: 0, y: 0 };
let draggedObject = null;

export function initMap(containerId) {
    const container = document.getElementById(containerId);
    if (!container) {
        console.error('Map container not found:', containerId);
        return;
    }

    const width = container.offsetWidth || 800;
    const height = container.offsetHeight || 600;

    stage = new Konva.Stage({
        container: containerId,
        width: width,
        height: height,
    });

    layer = new Konva.Layer();
    stage.add(layer);

    // Setup pan/zoom interactions
    setupPanZoom();

    // Setup object dragging
    setupDragAndDrop();

    // Handle window resize
    window.addEventListener('resize', () => {
        const newWidth = container.offsetWidth || 800;
        const newHeight = container.offsetHeight || 600;
        stage.width(newWidth);
        stage.height(newHeight);
        centerMapView();
    });

    return stage;
}

function setupPanZoom() {
    stage.on('wheel', (e) => {
        e.evt.preventDefault();

        const oldScale = scale;
        const pointer = stage.getPointerPosition();

        const mousePointTo = {
            x: (pointer.x - offsetX) / oldScale,
            y: (pointer.y - offsetY) / oldScale,
        };

        // Zoom
        const scaleBy = 1.1;
        let direction = e.evt.deltaY > 0 ? -1 : 1;
        const newScale = Math.max(minScale, Math.min(maxScale, scale * Math.pow(scaleBy, direction)));
        
        scale = newScale;
        stage.scale({ x: scale, y: scale });

        // Adjust offset to zoom at mouse position
        const newPos = {
            x: pointer.x - mousePointTo.x * scale,
            y: pointer.y - mousePointTo.y * scale,
        };
        
        offsetX = newPos.x;
        offsetY = newPos.y;
        stage.position({ x: offsetX, y: offsetY });
    });

    // Pan with mouse drag (when not dragging objects)
    stage.on('mousedown touchstart', (e) => {
        if (e.target === stage || e.target === layer) {
            isDragging = true;
            lastPointerPosition = stage.getPointerPosition();
        }
    });

    stage.on('mouseup touchend', () => {
        isDragging = false;
    });

    stage.on('mousemove touchmove', (e) => {
        if (isDragging && !draggedObject) {
            e.evt.preventDefault();
            const pos = stage.getPointerPosition();
            const dx = pos.x - lastPointerPosition.x;
            const dy = pos.y - lastPointerPosition.y;

            offsetX += dx;
            offsetY += dy;
            stage.position({ x: offsetX, y: offsetY });

            lastPointerPosition = pos;
        }
    });
}

function setupDragAndDrop() {
    layer.on('dragstart', (e) => {
        draggedObject = e.target;
        e.target.moveToTop();
        layer.draw();
    });

    layer.on('dragmove', (e) => {
        // Update visual position during drag (no snapping yet)
        layer.batchDraw();
    });

    layer.on('dragend', (e) => {
        const obj = e.target;
        const objData = obj.getAttr('objData');

        if (!objData) {
            draggedObject = null;
            return;
        }

        // Get current position of the object relative to the layer
        // Objects are positioned in "screen space" (grid coordinates converted to pixels)
        const pos = obj.position();
        
        // Convert to grid coordinates using screen position and object size
        const gridPos = screenToGrid(pos.x, pos.y, objData.size);

        // Check collision and snap to grid
        if (!checkCollision(gridPos.x, gridPos.y, objData.size, obj.id())) {
            // Update object data
            objData.x = gridPos.x;
            objData.y = gridPos.y;

            // Snap to grid using correct object size
            const screenPos = gridToScreen(gridPos.x, gridPos.y, objData.size);
            obj.position(screenPos);

            // Trigger update callback
            if (window.onMapObjectMoved) {
                window.onMapObjectMoved(objData);
            }
        } else {
            // Revert to original position using correct object size
            const originalPos = gridToScreen(objData.x, objData.y, objData.size);
            obj.position(originalPos);
        }

        draggedObject = null;
        layer.draw();
    });
}

export function clearMap() {
    if (layer) {
        layer.destroyChildren();
        layer.draw();
    }
    mapObjects = [];
    occupiedPositions.clear();
}

export function renderMap(mapData) {
    if (!layer) {
        console.error('Layer not initialized. Call initMap first.');
        return;
    }

    clearMap();
    
    const { traps = [], miscObjects = [], furnaces = [] } = mapData;

    // Calculate bounds and create grid
    calculateOccupiedPositions(mapData);
    const bounds = calculateBounds(mapData);
    drawGrid(bounds);

    // Draw traps
    traps.forEach((trap, index) => {
        addTrap(trap.x, trap.y, index + 1);
    });

    // Draw misc objects
    miscObjects.forEach((obj) => {
        addMiscObject(obj.x, obj.y, obj.size, obj.name);
    });

    // Draw furnaces
    furnaces.forEach((furnace) => {
        if (furnace.x !== null && furnace.y !== null) {
            addFurnace(furnace.x, furnace.y, furnace.name, furnace.id, furnace.status);
        }
    });

    centerMapView();
    layer.draw();
}

function calculateOccupiedPositions(mapData) {
    occupiedPositions.clear();
    
    mapData.traps?.forEach(trap => {
        markOccupied(trap.x, trap.y, TRAP_SIZE);
    });

    mapData.miscObjects?.forEach(obj => {
        markOccupied(obj.x, obj.y, obj.size);
    });

    mapData.furnaces?.forEach(furnace => {
        if (furnace.x !== null && furnace.y !== null) {
            markOccupied(furnace.x, furnace.y, FURNACE_SIZE);
        }
    });
}

function calculateBounds(mapData) {
    let minX = Infinity, minY = Infinity;
    let maxX = -Infinity, maxY = -Infinity;

    const updateBounds = (x, y, gridSize) => {
        // Use gridToScreen to ensure consistency with positioning
        const screenPos = gridToScreen(x, y, gridSize);
        const pixelSize = gridSize * CELL_SIZE;
        minX = Math.min(minX, screenPos.x);
        maxX = Math.max(maxX, screenPos.x + pixelSize);
        minY = Math.min(minY, screenPos.y);
        maxY = Math.max(maxY, screenPos.y + pixelSize);
    };

    mapData.traps?.forEach(trap => updateBounds(trap.x, trap.y, TRAP_SIZE));
    mapData.miscObjects?.forEach(obj => updateBounds(obj.x, obj.y, obj.size));
    mapData.furnaces?.forEach(furnace => {
        if (furnace.x !== null && furnace.y !== null) {
            updateBounds(furnace.x, furnace.y, FURNACE_SIZE);
        }
    });

    return { minX, minY, maxX, maxY };
}

function drawGrid(bounds) {
    // Simple grid background - could be enhanced
    const padding = 100;
    const gridStartX = bounds.minX - padding;
    const gridEndX = bounds.maxX + padding;
    const gridStartY = bounds.minY - padding;
    const gridEndY = bounds.maxY + padding;

    // Draw subtle grid lines
    for (let x = gridStartX; x <= gridEndX; x += CELL_SIZE) {
        const line = new Konva.Line({
            points: [x, gridStartY, x, gridEndY],
            stroke: '#e0e0e0',
            strokeWidth: 1,
            listening: false,
        });
        layer.add(line);
    }

    for (let y = gridStartY; y <= gridEndY; y += CELL_SIZE) {
        const line = new Konva.Line({
            points: [gridStartX, y, gridEndX, y],
            stroke: '#e0e0e0',
            strokeWidth: 1,
            listening: false,
        });
        layer.add(line);
    }
}

function addTrap(x, y, index) {
    const screenPos = gridToScreen(x, y, TRAP_SIZE);
    const size = TRAP_SIZE * CELL_SIZE;

    // Check if dragging should be enabled (default true for private view)
    const dragEnabled = window.mapDragEnabled !== false;

    const group = new Konva.Group({
        x: screenPos.x,
        y: screenPos.y,
        draggable: dragEnabled,
    });

    // Rectangle
    const rect = new Konva.Rect({
        width: size,
        height: size,
        x: 0,
        y: 0,
        fill: 'brown',
        stroke: 'black',
        strokeWidth: 1,
    });

    // Label
    const label = new Konva.Text({
        x: size / 2,
        y: size / 2,
        text: `Trap ${index}`,
        fontSize: 12,
        fill: 'black',
        align: 'center',
        verticalAlign: 'middle',
        offsetX: 25,
        offsetY: 6,
    });

    // Coordinates
    const coords = new Konva.Text({
        x: 5,
        y: size - 15,
        text: `(${x},${y})`,
        fontSize: 10,
        fill: 'black',
    });

    group.add(rect);
    group.add(label);
    group.add(coords);
    group.setAttr('objData', { type: 'trap', x, y, size: TRAP_SIZE, index });
    group.id(`trap-${index}`);

    layer.add(group);
    mapObjects.push(group);
}

function addMiscObject(x, y, size, name) {
    const screenPos = gridToScreen(x, y, size);
    const pixelSize = size * CELL_SIZE;

    // Check if dragging should be enabled (default true for private view)
    const dragEnabled = window.mapDragEnabled !== false;

    const group = new Konva.Group({
        x: screenPos.x,
        y: screenPos.y,
        draggable: dragEnabled,
    });

    const rect = new Konva.Rect({
        width: pixelSize,
        height: pixelSize,
        x: 0,
        y: 0,
        fill: 'darkgrey',
        stroke: 'black',
        strokeWidth: 1,
    });

    const label = new Konva.Text({
        x: pixelSize / 2,
        y: pixelSize / 2,
        text: name || 'Object',
        fontSize: 12,
        fill: 'black',
        align: 'center',
        verticalAlign: 'middle',
        offsetX: 25,
        offsetY: 6,
    });

    const coords = new Konva.Text({
        x: 5,
        y: pixelSize - 15,
        text: `(${x},${y})`,
        fontSize: 10,
        fill: 'black',
    });

    group.add(rect);
    group.add(label);
    group.add(coords);
    group.setAttr('objData', { type: 'misc', x, y, size, name });
    group.id(`misc-${x}-${y}`);

    layer.add(group);
    mapObjects.push(group);
}

function addFurnace(x, y, name, id, status) {
    const screenPos = gridToScreen(x, y, FURNACE_SIZE);
    const size = FURNACE_SIZE * CELL_SIZE;

    // Check if dragging should be enabled (default true for private view)
    const dragEnabled = window.mapDragEnabled !== false;

    const group = new Konva.Group({
        x: screenPos.x,
        y: screenPos.y,
        draggable: dragEnabled,
    });

    // Color based on status
    let color = '#2DCCFF'; // default blue
    switch (status) {
        case 'messaged':
            color = '#FFAF3D';
            break;
        case 'moved':
            color = '#00E200';
            break;
        case 'wrong':
            color = '#FF2A04';
            break;
        case 'assigned':
        default:
            color = '#2DCCFF';
            break;
    }

    const rect = new Konva.Rect({
        width: size,
        height: size,
        x: 0,
        y: 0,
        fill: color,
        stroke: 'black',
        strokeWidth: 1,
    });

    const label = new Konva.Text({
        x: size / 2,
        y: size / 2,
        text: name || 'Furnace',
        fontSize: 12,
        fill: 'black',
        align: 'center',
        verticalAlign: 'middle',
        offsetX: 25,
        offsetY: 6,
    });

    const coords = new Konva.Text({
        x: 5,
        y: size - 15,
        text: `(${x},${y})`,
        fontSize: 10,
        fill: 'black',
    });

    group.add(rect);
    group.add(label);
    group.add(coords);
    group.setAttr('objData', { type: 'furnace', x, y, size: FURNACE_SIZE, name, id, status });
    group.id(`furnace-${id}`);

    layer.add(group);
    mapObjects.push(group);
}

function gridToScreen(gridX, gridY, objectSize = FURNACE_SIZE) {
    // Convert grid coordinates to screen coordinates
    // Y-axis is flipped (like in SVG generator: pixelY = -(y * CELL_SIZE + size))
    // SVG: pixelX = x * CELL_SIZE
    // SVG: pixelY = -(y * CELL_SIZE + size)
    const size = objectSize * CELL_SIZE;
    return {
        x: gridX * CELL_SIZE,
        y: -(gridY * CELL_SIZE + size),
    };
}

function screenToGrid(screenX, screenY, objectSize = FURNACE_SIZE) {
    // Convert screen coordinates to grid coordinates
    // Reverse of gridToScreen:
    // gridX = pixelX / CELL_SIZE
    // gridY = -(pixelY / CELL_SIZE) - (size / CELL_SIZE)
    const gridX = Math.round(screenX / CELL_SIZE);
    const gridY = Math.round(-(screenY + objectSize * CELL_SIZE) / CELL_SIZE);
    return { x: gridX, y: gridY };
}

function markOccupied(x, y, size) {
    for (let dx = 0; dx < size; dx++) {
        for (let dy = 0; dy < size; dy++) {
            occupiedPositions.add(`${x + dx},${y + dy}`);
        }
    }
}

function checkCollision(x, y, size, excludeId = null) {
    for (let dx = 0; dx < size; dx++) {
        for (let dy = 0; dy < size; dy++) {
            const key = `${x + dx},${y + dy}`;
            // Check if position is occupied by another object
            for (const obj of mapObjects) {
                if (obj.id() === excludeId) continue;
                const objData = obj.getAttr('objData');
                if (!objData) continue;

                for (let odx = 0; odx < objData.size; odx++) {
                    for (let ody = 0; ody < objData.size; ody++) {
                        if (objData.x + odx === x + dx && objData.y + ody === y + dy) {
                            return true;
                        }
                    }
                }
            }
        }
    }
    return false;
}

export function centerMapView() {
    if (!stage) return;

    // Find bounds of all objects
    if (mapObjects.length === 0) {
        offsetX = stage.width() / 2;
        offsetY = stage.height() / 2;
        stage.position({ x: offsetX, y: offsetY });
        return;
    }

    let minX = Infinity, minY = Infinity;
    let maxX = -Infinity, maxY = -Infinity;

    mapObjects.forEach(obj => {
        const pos = obj.position();
        minX = Math.min(minX, pos.x);
        minY = Math.min(minY, pos.y);
        const objData = obj.getAttr('objData');
        if (objData) {
            const size = objData.size * CELL_SIZE;
            maxX = Math.max(maxX, pos.x + size);
            maxY = Math.max(maxY, pos.y + size);
        }
    });

    const centerX = (minX + maxX) / 2;
    const centerY = (minY + maxY) / 2;

    offsetX = (stage.width() / 2) - centerX * scale;
    offsetY = (stage.height() / 2) - centerY * scale;
    stage.position({ x: offsetX, y: offsetY });
}

export function getMapData() {
    const data = {
        traps: [],
        miscObjects: [],
        furnaces: [],
    };

    mapObjects.forEach(obj => {
        const objData = obj.getAttr('objData');
        if (!objData) return;

        // Use correct object size for coordinate conversion
        const gridPos = screenToGrid(obj.position().x, obj.position().y, objData.size);

        switch (objData.type) {
            case 'trap':
                data.traps.push({ x: gridPos.x, y: gridPos.y, index: objData.index });
                break;
            case 'misc':
                data.miscObjects.push({ x: gridPos.x, y: gridPos.y, size: objData.size, name: objData.name });
                break;
            case 'furnace':
                data.furnaces.push({
                    id: objData.id,
                    x: gridPos.x,
                    y: gridPos.y,
                    name: objData.name,
                    status: objData.status,
                });
                break;
        }
    });

    return data;
}

export function exportAsImage(format = 'png') {
    if (!stage) return null;
    return stage.toDataURL({ mimeType: `image/${format}`, quality: 1 });
}

export function setDragEnabled(enabled) {
    window.mapDragEnabled = enabled;
    // Update all existing objects
    if (layer) {
        layer.find('Group').forEach(group => {
            const objData = group.getAttr('objData');
            if (objData && (objData.type === 'furnace' || objData.type === 'trap' || objData.type === 'misc')) {
                group.draggable(enabled);
            }
        });
        layer.draw();
    }
}

