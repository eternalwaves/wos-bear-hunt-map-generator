let occupied = new Set();
let newOccupied = new Set();

let furnaces = [];
let cellSize = null;
let currentMapId = null;
let currentVersion = null;
let originalSavedGearData = {}; // Store original saved gear data for all furnaces

// Authentication check
// Check authentication on page load
async function checkAuthentication() {
    // Load session manager if not already loaded
    if (!window.sessionManager) {
        const script = document.createElement('script');
        script.src = 'js/session-manager.js';
        await new Promise((resolve) => {
            script.onload = resolve;
            document.head.appendChild(script);
        });
    }
    
    await window.sessionManager.initialize();
    
    if (!window.sessionManager.isUserAuthenticated()) {
        window.location.href = 'login.html';
        return false;
    }
    
    return true;
}

// Logout function
async function logout() {
    if (window.sessionManager) {
        await window.sessionManager.logout();
    }
    window.location.href = 'login.html';
}

document.addEventListener("DOMContentLoaded", async function() {
    // Check authentication first
    if (!await checkAuthentication()) {
        return;
    }
    
    // Load user info
    await loadUserInfo();
    
    // Setup event listeners
    setupEventListeners();
    
    // Load initial data
    loadMaps();
    // loadObjects() will be called by loadMaps() if a map is auto-selected
});

function setupEventListeners() {
    // Create Map Form
    document.getElementById("createMapForm").addEventListener("submit", function (e) {
        e.preventDefault();
        let formData = new FormData(this);
        createMap(formData);
    });

    // Save Version Form
    document.getElementById("saveVersionForm").addEventListener("submit", function (e) {
        e.preventDefault();
        let formData = new FormData(this);
        saveVersion(formData);
    });

    // Add Trap Form
    document.getElementById("addTrapForm").addEventListener("submit", function (e) {
        e.preventDefault();
        let formData = new FormData(this);
        addObject("add_trap", formData);
    });

    // Add Object Form
    document.getElementById("addObjectForm").addEventListener("submit", function (e) {
        e.preventDefault();
        let formData = new FormData(this);
        addObject("add_object", formData);
    });

    // Add Furnace Form
    document.getElementById("addFurnaceForm").addEventListener("submit", function (e) {
        e.preventDefault();
        let formData = new FormData(this);
        addObject("add_furnace", formData);
    });

    // Upload Form
    document.getElementById("uploadForm").addEventListener("submit", function (e) {
        e.preventDefault();
        let fileInput = document.getElementById("csvFile").files[0];

        if (!fileInput) {
            alert("Please select a CSV file to upload.");
            return;
        }

        if (!currentMapId) {
            alert("Please select a map first.");
            return;
        }

        let formData = new FormData();
        formData.append("csv_file", fileInput);
        formData.append("map_id", currentMapId);
        if (currentVersion) {
            formData.append("version", currentVersion);
        }

        fetch("api.php?action=upload_excel", {
            method: "POST",
            cache: "no-store",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                alert("Excel file uploaded successfully!");
                loadObjects();
            } else {
                alert("Error: " + data.message);
            }
        });
    });

    // Add event listeners for priority mode switching
    document.querySelectorAll('input[name="priorityMode"]').forEach(radio => {
        radio.addEventListener('change', togglePriorityMode);
    });

    // Add event listeners for weight sliders
    document.querySelectorAll('.weight-slider').forEach(slider => {
        slider.addEventListener('input', updateWeightValue);
    });

    // Map selector change event
    document.getElementById("mapSelect").addEventListener("change", loadSelectedMap);

    // Version selector change event
    document.getElementById("versionSelect").addEventListener("change", loadSelectedVersion);
}

function loadMaps() {
    let loader = document.getElementById("loader");
    loader.style.display = "block";

    fetch("api.php?action=get_all_maps", { cache: "no-store" })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.data.length > 0) {
                    populateMapSelector(data.data);
                    document.getElementById("mapSelector").style.display = "block";
                    
                    // Auto-select first map if only one exists
                    if (data.data.length === 1) {
                        currentMapId = data.data[0].id;
                        loadVersions();
                        loadObjects();
                    } else {
                        // Multiple maps exist - show selector but don't auto-select
                        clearMapData();
                    }
                } else {
                    // No maps found - show UI for creating new maps
                    document.getElementById("mapSelector").style.display = "none";
                    clearMapData();
                }
            } else {
                console.error("Error loading maps:", data.message);
                clearMapData();
            }
        })
        .catch(error => {
            console.log("No maps found or error loading maps:", error);
            // Show UI even if there's an error, so user can create maps
            document.getElementById("mapSelector").style.display = "none";
            clearMapData();
        })
        .finally(() => {
            loader.style.display = "none";
        });
}

function populateMapSelector(maps) {
    const mapSelect = document.getElementById("mapSelect");
    mapSelect.innerHTML = '<option value="">Choose a map...</option>';
    
    maps.forEach(map => {
        const option = document.createElement('option');
        option.value = map.id;
        option.textContent = map.name;
        mapSelect.appendChild(option);
    });
}

function createMap(formData) {
    fetch("api.php?action=create_map", {
        method: "POST",
        cache: "no-store",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            alert("Map created successfully!");
            currentMapId = data.map_id;
            loadMaps();
            loadObjects();
        } else {
            alert("Error: " + data.message);
        }
    });
}

function loadSelectedMap() {
    const mapSelect = document.getElementById("mapSelect");
    currentMapId = mapSelect.value;
    currentVersion = null;
    
    if (currentMapId) {
        loadVersions();
        loadObjects();
    } else {
        document.getElementById("versionControls").style.display = "none";
        clearMapData();
    }
}

function loadVersions() {
    if (!currentMapId) return;
    
    fetch(`api.php?action=get_versions&map_id=${currentMapId}`, { cache: "no-store" })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                populateVersionSelector(data.data);
                document.getElementById("versionControls").style.display = "block";
            }
        });
}

function populateVersionSelector(versions) {
    const versionSelect = document.getElementById("versionSelect");
    versionSelect.innerHTML = '<option value="">Latest</option>';
    
    versions.forEach(version => {
        const option = document.createElement('option');
        option.value = version.version;
        option.textContent = `${version.version} (${new Date(version.created_at).toLocaleDateString()})`;
        versionSelect.appendChild(option);
    });
}

function loadSelectedVersion() {
    const versionSelect = document.getElementById("versionSelect");
    currentVersion = versionSelect.value;
    
    if (currentVersion) {
        loadObjects();
    }
}

function saveVersion(formData) {
    if (!currentMapId) {
        alert("Please select a map first.");
        return;
    }
    
    // Extract version from form data
    const version = formData.get('version');
    if (!version) {
        alert("Please enter a version name.");
        return;
    }
    
    // Create JSON request body to match other PUT requests
    const requestBody = {
        map_id: currentMapId,
        version: version
    };
    
    fetch("api.php?action=save_version", {
        method: "PUT",
        cache: "no-store",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(requestBody)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            alert("Version saved successfully!");
            loadVersions();
        } else {
            alert("Error: " + data.message);
        }
    });
}

function deleteCurrentVersion() {
    if (!currentMapId || !currentVersion) {
        alert("Please select a map and version to delete.");
        return;
    }
    
    if (!confirm(`Are you sure you want to delete version "${currentVersion}"?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('map_id', currentMapId);
    formData.append('version', currentVersion);
    
    fetch("api.php?action=delete_version", {
        method: "DELETE",
        cache: "no-store",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            alert("Version deleted successfully!");
            currentVersion = null;
            loadVersions();
            loadObjects();
        } else {
            alert("Error: " + data.message);
        }
    });
}

function clearMapData() {
    furnaces = [];
    occupied = new Set();
    newOccupied = new Set();
    originalSavedGearData = {}; // Clear stored original saved gear data
    updateTable("trapTable", [], ["x", "y"], "deleteTrap");
    updateTable("objectTable", [], ["x", "y", "size", "name"], "deleteObject");
    updateTable("furnaceTable", [], ["name", "level", "power", "rank", "participation", "trap_pref", "x", "y"], "deleteFurnace");
    document.getElementById('map').innerHTML = '';
    updateButtons();
}

function addLockListeners() {
    const unlockIcon = '🔓';
    const lockIcon = '🔒';

    document.querySelectorAll('.lockBtn').forEach((el) => {
        let newNode = el.cloneNode(true);
        el.parentNode.replaceChild(newNode, el);
        
        el = null;
        if (newNode.dataset.locked === 'true') {
            newNode.innerHTML = lockIcon;
            newNode.addEventListener('mouseover', (e) => {
                newNode.innerHTML = unlockIcon;
            });
            newNode.addEventListener('focus', (e) => {
                newNode.innerHTML = unlockIcon;
            });
            newNode.addEventListener('mouseout', (e) => {
                newNode.innerHTML = lockIcon;
            });
            newNode.addEventListener('blur', (e) => {
                newNode.innerHTML = lockIcon;
            });
        } else {
            newNode.innerHTML = unlockIcon;
            newNode.addEventListener('mouseover', (e) => {
                newNode.innerHTML = lockIcon;
            });
            newNode.addEventListener('focus', (e) => {
                newNode.innerHTML = lockIcon;
            });
            newNode.addEventListener('mouseout', (e) => {
                newNode.innerHTML = unlockIcon;
            });
            newNode.addEventListener('blur', (e) => {
                newNode.innerHTML = unlockIcon;
            });
        }
    });
}

function loadObjects(loadMap = true) {
    if (!currentMapId) {
        clearMapData();
        return;
    }

    let loader = document.getElementById("loader");
    loader.style.display = "block";

    let url = `api.php?action=get_objects&map_id=${currentMapId}`;
    if (currentVersion) {
        url += `&version=${currentVersion}`;
    }

    fetch(url, { cache: "no-store" })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const mapData = data.data;
                updateTable("trapTable", mapData.traps, ["x", "y"], "deleteTrap");
                updateTable("objectTable", mapData.misc, ["x", "y", "size", "name"], "deleteObject");
                updateTable("furnaceTable", mapData.furnaces, ["name", "level", "power", "rank", "participation", "trap_pref", "x", "y"], "deleteFurnace");

                furnaces = mapData.furnaces;
                occupied = new Set(Object.keys(mapData.occupied));
                newOccupied = new Set(occupied);
                cellSize = mapData.cellSize || 50;
                
                // Store original saved gear data for each furnace
                originalSavedGearData = {};
                furnaces.forEach(furnace => {
                    originalSavedGearData[furnace.id] = {
                        cap_level: furnace.cap_level || '',
                        cap_charms: furnace.cap_charms || '',
                        watch_level: furnace.watch_level || '',
                        watch_charms: furnace.watch_charms || '',
                        vest_level: furnace.vest_level || '',
                        vest_charms: furnace.vest_charms || '',
                        pants_level: furnace.pants_level || '',
                        pants_charms: furnace.pants_charms || '',
                        ring_level: furnace.ring_level || '',
                        ring_charms: furnace.ring_charms || '',
                        cane_level: furnace.cane_level || '',
                        cane_charms: furnace.cane_charms || ''
                    };
                });
                
                // Apply weighted criteria if available
                if (mapData.weightedCriteria) {
                    console.log('Loading weighted criteria:', mapData.weightedCriteria);
                    applyWeightedCriteriaToUI(mapData.weightedCriteria);
                } else {
                    console.log('No weighted criteria found in map data');
                }
                
                addLockListeners();
            } else {
                console.error("Error loading objects:", data.message);
            }
        })
        .then(() => {
            if (loadMap) {
                // Use the dynamic map.svg PHP endpoint
                let svgUrl = `map.svg?map_id=${currentMapId}`;
                if (currentVersion) {
                    svgUrl += `&version=${currentVersion}`;
                }

                return fetch(svgUrl, { cache: "no-store" });
            }
            return Promise.reject();
        })
        .then(response => {
            if (response.ok) {
                return response.text();
            }
            return Promise.reject();
        })
        .then(svg => {
            renderSVG(svg);
        })
        .catch(() => { return; })
        .finally(() => {
            updateButtons();
            loader.style.display = "none";
        });

    let list = document.getElementById("sortPriorityList");

    // Enable drag-and-drop sorting
    new Sortable(list, {
        animation: 150,
        ghostClass: 'sortable-ghost'
    });
}

function applyWeightedCriteriaToUI(weightedCriteria) {
    console.log('Applying weighted criteria to UI:', weightedCriteria);
    
    if (!weightedCriteria || !Array.isArray(weightedCriteria)) {
        console.log('Weighted criteria is invalid or not an array');
        return;
    }
    
    // Switch to weighted mode
    const weightedRadio = document.querySelector('input[name="priorityMode"][value="weighted"]');
    if (weightedRadio) {
        weightedRadio.checked = true;
        // Trigger the change event to properly update the UI
        const changeEvent = new Event('change', { bubbles: true });
        weightedRadio.dispatchEvent(changeEvent);
        console.log('Switched to weighted mode');
    } else {
        console.log('Weighted radio button not found');
    }
    
    // Apply weights to sliders
    weightedCriteria.forEach(criteria => {
        const slider = document.querySelector(`.weight-slider[data-criteria="${criteria.criteria}"]`);
        if (slider) {
            slider.value = criteria.weight;
            const valueDisplay = slider.parentElement.querySelector('.weight-value');
            if (valueDisplay) {
                valueDisplay.textContent = parseFloat(criteria.weight).toFixed(1);
            }
            console.log(`Set ${criteria.criteria} to ${criteria.weight}`);
        } else {
            console.log(`Slider for ${criteria.criteria} not found`);
        }
    });
}

function updateButtons() {
    const resetFurnaceBtn = document.getElementById('resetFurnaceBtn');
    const updateFurnacesBtn = document.getElementById('updateFurnacesBtn');
    const generateMapBtn = document.getElementById('generateMapBtn');
    const downloadDataBtn = document.getElementById('downloadDataBtn');
    const downloadSVGBtn = document.getElementById('downloadSVGBtn');
    const downloadPNGBtn = document.getElementById('downloadPNGBtn');
    const resetBtn = document.getElementById('resetBtn');
    
    if (document.querySelectorAll('#furnaceTable .unsaved').length) {
        updateFurnacesBtn.disabled = false;
    } else {
        updateFurnacesBtn.disabled = true;
    }
    
    if (document.querySelectorAll('#furnaceTable tbody tr').length) {
        resetFurnaceBtn.disabled = false;
    } else {
        resetFurnaceBtn.disabled = true;
    }

    if (document.querySelectorAll('#trapTable tbody tr').length && document.querySelectorAll('#furnaceTable tbody tr').length) {
        generateMapBtn.disabled = false;
    } else {
        generateMapBtn.disabled = true;
    }

    if (document.querySelectorAll('#trapTable tbody tr').length || document.querySelectorAll('#objectTable tbody tr').length || document.querySelectorAll('#furnaceTable tbody tr').length) {
        resetBtn.disabled = false;
    } else {
        resetBtn.disabled = true;
    }
    
    if (hasMap()) {
        downloadDataBtn.disabled = false;
        downloadSVGBtn.disabled = false;
        downloadPNGBtn.disabled = false;
    } else {
        downloadDataBtn.disabled = true;
        downloadSVGBtn.disabled = true;
        downloadPNGBtn.disabled = true;
    }
}

function hasMap() {
    if (document.querySelectorAll('#map svg').length) {
        return true;
    }
    
    return false;
}

function isOccupied(oldX, oldY, newX, newY, size) {
    let tempNew = new Set(newOccupied);
    oldX = Number(oldX);
    oldY = Number(oldY);
    newX = Number(newX);
    newY = Number(newY);
    size = Number(size);

    // first remove the original object    
    for (let dx1 = 0; dx1 < size; dx1++) {
        for (let dy1 = 0; dy1 < size; dy1++) {
            let coords = `${oldX + dx1},${oldY + dy1}`;
            if (tempNew.has(coords)) {
                tempNew.delete(coords);
            }
        }
    }
    
    // then check the new coordinates
    for (let dx2 = 0; dx2 < size; dx2++) {
        for (let dy2 = 0; dy2 < size; dy2++) {
            let coords = `${newX + dx2},${newY + dy2}`;
            if (tempNew.has(coords)) {
                return true;
            }
        }
    }
    
    return false;
}

function markNewOccupied(oldX, oldY, newX, newY, size) {
    oldX = Number(oldX);
    oldY = Number(oldY);
    newX = Number(newX);
    newY = Number(newY);
    size = Number(size);

    // first remove the original object    
    for (let dx1 = 0; dx1 < size; dx1++) {
        for (let dy1 = 0; dy1 < size; dy1++) {
            let coords = `${oldX + dx1},${oldY + dy1}`;
            newOccupied.delete(coords);
        }
    }
    
    // then update to the new coordinates
    for (let dx2 = 0; dx2 < size; dx2++) {
        for (let dy2 = 0; dy2 < size; dy2++) {
            let coords = `${newX + dx2},${newY + dy2}`;
            newOccupied.add(coords);
        }
    }
}

function shiftMapPosition(id, newX, newY, name) {
    const pattern = /\((?<x>\d+),(?<y>\d+)\)/;
    
    let oldX, oldY, dx, dy;
    
    newX = Number(newX);
    newY = Number(newY);

    document.querySelectorAll(`#map svg .coords[data-obj-id="${id}"]`).forEach((el) => {
        matches = el.innerHTML.match(pattern);
        oldX = Number(matches.groups.x);
        oldY = Number(matches.groups.y);
        el.innerHTML = `(${newX},${newY})`;
    });
    
    document.querySelectorAll(`#map svg .label[data-obj-id="${id}"]`).forEach((el) => {
        el.innerHTML = name;
    });
    
    dx = newX - oldX;
    dy = newY - oldY;

    document.querySelectorAll(`#map svg [data-obj-id="${id}"]`).forEach((el) => {
        el.setAttribute('x', Number(el.getAttribute('x')) + (dx * cellSize));
        el.setAttribute('y', Number(el.getAttribute('y')) + (-dy * cellSize));
    });
}

function getInputValuesByFurnaceId(id) {
    const name = document.getElementById(`furnace-name-${id}`).value.trim();
    const level = document.getElementById(`furnace-level-${id}`).value.trim();
    const power = document.getElementById(`furnace-power-${id}`).value.trim();
    const rank = document.getElementById(`furnace-rank-${id}`).value.trim();
    const participation = document.getElementById(`furnace-participation-${id}`).value.trim();
    const trap_pref = document.getElementById(`furnace-trap-pref-${id}`).value.trim();
    const x = document.getElementById(`furnace-x-${id}`).value.trim();
    const y = document.getElementById(`furnace-y-${id}`).value.trim();
    const status = document.getElementById(`furnace-status-${id}`).value;

    // Process participation: blank becomes 0, otherwise convert to number
    const processedParticipation = participation === '' ? 0 : parseInt(participation) || 0;
    
    // Process coordinates: blank becomes null, otherwise convert to number
    const processedX = x === '' ? null : parseInt(x) || null;
    const processedY = y === '' ? null : parseInt(y) || null;

    // Get gear data from the furnaces array since gear is now edited via modal
    const furnace = furnaces.find(f => f.id === id);
    const gearData = {};
    
    if (furnace) {
        const gearTypes = ['cap', 'watch', 'vest', 'pants', 'ring', 'cane'];
        gearTypes.forEach(gearType => {
            gearData[`${gearType}_level`] = furnace[`${gearType}_level`] || '';
            gearData[`${gearType}_charms`] = furnace[`${gearType}_charms`] || '';
        });
    }

    return [
        name,
        level,
        power,
        rank,
        processedParticipation,
        trap_pref,
        processedX,
        processedY,
        status,
        gearData
    ];
}

function markRowUnsaved(id, newX, newY, oldX, oldY) {
    const xInput = document.getElementById(`furnace-x-${id}`);
    const yInput = document.getElementById(`furnace-y-${id}`);
    const nameInput = document.getElementById(`furnace-name-${id}`);

    const [
        name,
        level,
        power,
        rank,
        processedParticipation,
        trap_pref,
        processedX,
        processedY,
        status,
        gearData
    ] = getInputValuesByFurnaceId(id);

    if (!newX || !newY || !oldX || !oldY) {
        newX = Number(processedX);
        newY = Number(processedY);
        oldX = Number(xInput.getAttribute('data-x'));
        oldY = Number(yInput.getAttribute('data-y'));

        if (isOccupied(oldX, oldY, newX, newY, 2)) {
            alert("Cannot move to occupied space!");
            
            xInput.value = oldX;
            yInput.value = oldY;
            return;
        }
    }


    for (let n = 0; n < furnaces.length; n++) {
        let furnace = furnaces[n];
        if (furnace.id === id) {
            // Check if gear data matches
            let gearMatches = true;
            const gearTypes = ['cap', 'watch', 'vest', 'pants', 'ring', 'cane'];
            gearTypes.forEach(gearType => {
                if (gearData[`${gearType}_level`] !== (furnace[`${gearType}_level`] || '') ||
                    gearData[`${gearType}_charms`] !== (furnace[`${gearType}_charms`] || '')) {
                    gearMatches = false;
                }
            });

            if (
                name === furnace.name
                && level === furnace.level
                && Number(power) === Number(furnace.power)
                && rank === furnace.rank
                && Number(processedParticipation) === Number(furnace.participation)
                && trap_pref === furnace.trap_pref
                && Number(newX) === Number(furnace.x)
                && Number(newY) === Number(furnace.y)
                && status === furnace.status
                && gearMatches
            ) {
                document.getElementById(`furnace-${id}`).classList.remove("unsaved");
                if (hasMap()) {
                    document.querySelectorAll(`#map svg [data-obj-id="${id}"]`).forEach((el) => el.classList.remove('unsaved'));
                }
            } else {
                document.getElementById(`furnace-${id}`).classList.add("unsaved");
                if (hasMap()) {
                    document.querySelectorAll(`#map svg [data-obj-id="${id}"]`).forEach((el) => el.classList.add('unsaved'));
                }
            }
                
                    
            if (
                hasMap()
                && (
                    Number(newX) !== Number(oldX)
                    || Number(newY) !== Number(oldY)
                    || name !== nameInput.dataset.name
                )
            ) {
                shiftMapPosition(id, newX, newY, name);
            }
            break;
        }
    }

    markNewOccupied(oldX, oldY, newX, newY, 2);
    xInput.setAttribute('data-x', newX);
    yInput.setAttribute('data-y', newY);
    nameInput.setAttribute('data-name', name);
    updateButtons();
}

function shiftFurnace(id, dx, dy) {
    let xField = document.getElementById(`furnace-x-${id}`);
    let yField = document.getElementById(`furnace-y-${id}`);
    
    let oldX = parseInt(xField.value);
    let oldY = parseInt(yField.value);

    let newX = oldX + dx;
    let newY = oldY + dy;

    if (isOccupied(oldX, oldY, newX, newY, 2)) {
        alert("Cannot move to occupied space!");
        return;
    }
    
    xField.value = newX;
    yField.value = newY;

    markRowUnsaved(id, newX, newY, oldX, oldY);
}

function saveFurnaceUpdate(id) {
    const [
        name,
        level,
        power,
        rank,
        participation,
        trap_pref,
        x,
        y,
        status,
        gearData
    ] = getInputValuesByFurnaceId(id);

    let loader = document.getElementById("loader");
    loader.style.display = "block";

    // Include version if available
    const requestBody = { 
        map_id: currentMapId, 
        id, 
        name, 
        level, 
        power, 
        rank, 
        participation, 
        trap_pref, 
        x, 
        y,
        status,
        ...gearData
    };
    if (currentVersion) {
        requestBody.version = currentVersion;
    }

    fetch("api.php?action=update_furnace", {
        method: "PUT",
        cache: "no-store",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(requestBody)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            return regenerateSvg();
        } else {
            alert("Error: " + data.message);
            return Promise.reject();
        }
    })
    .then(svg => {
        document.getElementById(`furnace-${id}`).classList.remove("unsaved"); // Mark as saved
        
        // 6. Update saved data for this furnace when saved to server
        const furnaceIndex = furnaces.findIndex(f => f.id === id);
        if (furnaceIndex !== -1) {
            const furnace = furnaces[furnaceIndex];
            originalSavedGearData[id] = {
                cap_level: furnace.cap_level || '',
                cap_charms: furnace.cap_charms || '',
                watch_level: furnace.watch_level || '',
                watch_charms: furnace.watch_charms || '',
                vest_level: furnace.vest_level || '',
                vest_charms: furnace.vest_charms || '',
                pants_level: furnace.pants_level || '',
                pants_charms: furnace.pants_charms || '',
                ring_level: furnace.ring_level || '',
                ring_charms: furnace.ring_charms || '',
                cane_level: furnace.cane_level || '',
                cane_charms: furnace.cane_charms || ''
            };
        }
        
        renderSVG(svg);
        loadObjects(false);
        updateButtons();
    })
    .catch(error => console.error("Update failed:", error))
    .finally(() => {
        loader.style.display = "none"
    });
}

function setFurnaceLocked(id) {
    const lockedEl = document.getElementById(`furnace-locked-${id}`);
    let locked = lockedEl.dataset.locked === 'true' ? true : false;

    // Include version if available
    const requestBody = { map_id: currentMapId, furnace_id: id, locked: !locked };
    if (currentVersion) {
        requestBody.version = currentVersion;
    }

    fetch("api.php?action=set_furnace_locked", {
        method: "PUT",
        cache: "no-store",
        body: JSON.stringify(requestBody),
        headers: { "Content-Type": "application/json" }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            loadObjects(false);
            updateButtons();
        } else {
            alert("Error saving lock status!");
            
        }
    });
}

function updateFurnaceStatus(id) {
    const statusEl = document.getElementById(`furnace-status-${id}`);
    const oldStatus = statusEl.dataset.status;
    let newStatus = statusEl.value;

    let loader = document.getElementById("loader");
    loader.style.display = "block";

    // Include version if available
    const requestBody = { map_id: currentMapId, furnace_id: id, status: newStatus };
    if (currentVersion) {
        requestBody.version = currentVersion;
    }

    fetch("api.php?action=update_furnace_status", {
        method: "PUT",
        cache: "no-store",
        body: JSON.stringify(requestBody),
        headers: { "Content-Type": "application/json" }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            return regenerateSvg();
        } else {
            alert("Error: " + data.message);
            return Promise.reject();
        }
    })
    .then(svg => {
        renderSVG(svg);
        loadObjects(false);
        updateButtons();
    })
    .catch(error => {
        statusEl.value = oldStatus;
    })
    .finally(() => {
        loader.style.display = "none"
    });
}

function updateFurnaces() {
    let updates = [];

    let loader = document.getElementById("loader");
    loader.style.display = "block";

    document.querySelectorAll("#furnaceTable tbody tr.unsaved").forEach((row) => {
        let id = row.dataset.furnaceId; // Get unique ID
        let [
            name,
            level,
            power,
            rank,
            processedParticipation,
            trap_pref,
            processedX,
            processedY,
            status,
            gearData
        ] = getInputValuesByFurnaceId(id);
    

        updates.push({ id, name, level, power, rank, participation: processedParticipation, trap_pref, x: processedX, y: processedY, status, ...gearData });
    });

    if (updates.length === 0) {
        alert("No changes to save.");
        loader.style.display = "none"
        return;
    }

    // Use URLSearchParams to send map_id as form data
    const formData = new FormData();
    formData.append('furnace_updates', JSON.stringify(updates));
    formData.append('map_id', currentMapId);
    if (currentVersion) {
        formData.append('version', currentVersion);
    }

    fetch("api.php?action=update_all_furnaces", {
        method: "PUT",
        cache: "no-store",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            return regenerateSvg();
        } else {
            alert("Error updating furnaces: " + data.message);
            return Promise.reject();
        }
    })
    .then(svg => {
        document.querySelectorAll("#furnaceTable tbody tr.unsaved").forEach((row) => row.classList.remove('unsaved'));
        
        // 6. Update saved data for all furnaces when saved to server
        furnaces.forEach(furnace => {
            originalSavedGearData[furnace.id] = {
                cap_level: furnace.cap_level || '',
                cap_charms: furnace.cap_charms || '',
                watch_level: furnace.watch_level || '',
                watch_charms: furnace.watch_charms || '',
                vest_level: furnace.vest_level || '',
                vest_charms: furnace.vest_charms || '',
                pants_level: furnace.pants_level || '',
                pants_charms: furnace.pants_charms || '',
                ring_level: furnace.ring_level || '',
                ring_charms: furnace.ring_charms || '',
                cane_level: furnace.cane_level || '',
                cane_charms: furnace.cane_charms || ''
            };
        });
        
        renderSVG(svg);
        loadObjects(false);
        updateButtons();
    })
    .catch(error => console.error("Update failed:", error))
    .finally(() => {
        loader.style.display = "none"
    });
}

function regenerateSvg() {
    // Use the dynamic map.svg PHP endpoint
    let svgUrl = `map.svg?map_id=${currentMapId}`;
    if (currentVersion) {
        svgUrl += `&version=${currentVersion}`;
    }

    return fetch(svgUrl, { cache: "no-store" })
    .then(response => {
        if (response.ok) {
            return response.text();
        } else {
            throw new Error('Failed to fetch SVG');
        }
    });
}

function generateMap() {
    if (hasMap() && !confirm('This will reassign all furnace positions (besides locked furnaces)! Continue?')) {
        return;
    }
    
    let loader = document.getElementById("loader");
    loader.style.display = "block";

    const formData = new FormData();
    formData.append('map_id', currentMapId);
    if (currentVersion) {
        formData.append('version', currentVersion);
    }

    // Check which priority mode is selected
    const priorityMode = document.querySelector('input[name="priorityMode"]:checked').value;
    
    if (priorityMode === 'simple') {
        // Simple priority mode - use drag and drop order
        let selectedOrder = Array.from(document.querySelectorAll("#sortPriorityList li"))
            .map(li => li.getAttribute("data-value"))
            .join(',');
        formData.append('sort_priority', selectedOrder);
    } else {
        // Weighted criteria mode - use weighted criteria
        const criteriaWeights = getWeightedCriteria();
        formData.append('criteria_weights', JSON.stringify(criteriaWeights));
    }

    fetch("api.php?action=generate_map", {
        method: "POST",
        cache: "no-store",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            return regenerateSvg();
        } else {
            alert("Error generating map: " + data.message);
            return Promise.reject();
        }
    })
    .then(svg => {
        renderSVG(svg);
        loadObjects(false);
        updateButtons();
    })
    .catch(error => console.error("Map generation failed:", error))
    .finally(() => {
        loader.style.display = "none"
    });
}

function updateTable(tableId, items, fields, deleteAction) {
    const table = document.getElementById(tableId);
    const tbody = table.getElementsByTagName('tbody')[0];
    
    // Check current gear column visibility state before clearing the table
    let gearColumnsVisible = false;
    if (tableId === "furnaceTable") {
        const existingGearColumns = document.querySelectorAll('.gear-columns');
        if (existingGearColumns.length > 0) {
            gearColumnsVisible = existingGearColumns[0].style.display !== 'none';
        }
    }
    
    tbody.innerHTML = "";

    items.forEach((item, index) => {
        if (tableId === "furnaceTable") {
            let classList = [];
            let status = item.status || '';

            // Set status to 'assigned' only if coordinates are provided
            if (!status && item.x && item.y) {
                status = 'assigned';
            }
            
            if (item.locked) {
                classList.push('locked');
            }
            if (status) {
                classList.push(status);
            }
            
            // Create gear cells (moved to left of Rank column) - now clickable for modal editing
            // Preserve the current visibility state
            const gearDisplayStyle = gearColumnsVisible ? 'table-cell' : 'none';
            const gearCells = `
                    <td class="gear-columns" style="display: ${gearDisplayStyle};" onclick="openGearModal('${item.id}')" title="Click to edit gear">
                        <div class="gear-grid-container">
                            <div class="gear-grid">
                                <div class="gear-item-display">
                                    <span class="gear-name">Cap</span>
                                    <span class="gear-display-level">${item.cap_level || 'Not Set'}</span>
                                    <span class="gear-display-charms">${item.cap_charms || 'No Charms'}</span>
                                </div>
                                <div class="gear-item-display">
                                    <span class="gear-name">Watch</span>
                                    <span class="gear-display-level">${item.watch_level || 'Not Set'}</span>
                                    <span class="gear-display-charms">${item.watch_charms || 'No Charms'}</span>
                                </div>
                                <div class="gear-item-display">
                                    <span class="gear-name">Vest</span>
                                    <span class="gear-display-level">${item.vest_level || 'Not Set'}</span>
                                    <span class="gear-display-charms">${item.vest_charms || 'No Charms'}</span>
                                </div>
                                <div class="gear-item-display">
                                    <span class="gear-name">Pants</span>
                                    <span class="gear-display-level">${item.pants_level || 'Not Set'}</span>
                                    <span class="gear-display-charms">${item.pants_charms || 'No Charms'}</span>
                                </div>
                                <div class="gear-item-display">
                                    <span class="gear-name">Ring</span>
                                    <span class="gear-display-level">${item.ring_level || 'Not Set'}</span>
                                    <span class="gear-display-charms">${item.ring_charms || 'No Charms'}</span>
                                </div>
                                <div class="gear-item-display">
                                    <span class="gear-name">Cane</span>
                                    <span class="gear-display-level">${item.cane_level || 'Not Set'}</span>
                                    <span class="gear-display-charms">${item.cane_charms || 'No Charms'}</span>
                                </div>
                            </div>
                        </div>
                    </td>
                `;
            
            tbody.innerHTML += `
                <tr id="furnace-${item.id}" data-furnace-id="${item.id}" class="${classList.join(' ')}">
                    <td>${index + 1}.</td>
                    <td><input data-name="${item.name}" value="${item.name}" id="furnace-name-${item.id}" class="edit-name" oninput="markRowUnsaved('${item.id}')"></td>
                    <td><input data-level="${item.level}" value="${item.level}" id="furnace-level-${item.id}" class="edit-level" oninput="markRowUnsaved('${item.id}')"></td>
                    <td><input type="number" min="0" data-power="${item.power}" value="${item.power}" id="furnace-power-${item.id}" class="edit-power" oninput="markRowUnsaved('${item.id}')"></td>
                    ${gearCells}
                    <td><input data-rank="${item.rank}" value="${item.rank}" id="furnace-rank-${item.id}" class="edit-rank" oninput="markRowUnsaved('${item.id}')"></td>
                    <td><input type="number" min="0" max="4" data-participation="${item.participation !== null && item.participation !== undefined ? item.participation : ''}" value="${item.participation !== null && item.participation !== undefined ? item.participation : ''}" id="furnace-participation-${item.id}" class="edit-participation" oninput="markRowUnsaved('${item.id}')"></td>
                    <td><input data-trap-pref="${item.trap_pref || ''}" value="${item.trap_pref || ''}" id="furnace-trap-pref-${item.id}" class="edit-trap-pref" oninput="markRowUnsaved('${item.id}')"></td>
                    <td><input type="number" min="0" data-x="${item.x || ''}" value="${item.x || ''}" id="furnace-x-${item.id}" class="edit-coord" oninput="markRowUnsaved('${item.id}')" ${item.locked ? 'title="Unlock furnace to enable coordinates" disabled' : ''}></td>
                    <td><input type="number" min="0" data-y="${item.y || ''}" value="${item.y || ''}" id="furnace-y-${item.id}" class="edit-coord" oninput="markRowUnsaved('${item.id}')" ${item.locked ? 'title="Unlock furnace to enable coordinates" disabled' : ''}></td>
                    <td class="shiftFurnaceBtns" ${item.locked ? 'title="Unlock furnace to enable buttons" disabled' : ''}>
                        <button onclick="shiftFurnace('${item.id}', -1, 0)"  ${item.locked ? 'disabled' : ''}>←</button>
                        <button onclick="shiftFurnace('${item.id}', 1, 0)"  ${item.locked ? 'disabled' : ''}>→</button>
                        <button onclick="shiftFurnace('${item.id}', 0, 1)"  ${item.locked ? 'disabled' : ''}>↑</button>
                        <button onclick="shiftFurnace('${item.id}', 0, -1)"  ${item.locked ? 'disabled' : ''}>↓</button>
                    </td>
                    <td class="actionBtns">
                        <button onclick="saveFurnaceUpdate('${item.id}')">Save</button>
                        <button onclick="${deleteAction}('${item.id}')">Delete</button>
                        <button data-locked="${item.locked || false}" onclick="setFurnaceLocked('${item.id}')" class="lockBtn" id="furnace-locked-${item.id}">${item.locked ? '🔒' : '🔓'}</button>
                    </td>
                    <td>
                        <select id="furnace-status-${item.id}" data-status="${status}" onchange="updateFurnaceStatus('${item.id}')">
                            <option value="" ${status === '' ? 'selected' : ''}></option>
                            <option value="assigned" ${status === 'assigned' ? 'selected' : ''}>Assigned</option>
                            <option value="moved" ${status === 'moved' ? 'selected' : ''}>Moved</option>
                            <option value="messaged" ${status === 'messaged' ? 'selected' : ''}>Messaged</option>
                            <option value="wrong" ${status === 'wrong' ? 'selected' : ''}>Wrong Spot</option>
                        </select>
                    </td>
                </tr>
            `;
        } else {
            let row = `<tr><td>${index + 1}.</td>${fields.map(f => `<td>${item[f]}</td>`).join("")}
                       <td><button onclick="${deleteAction}('${item.id}')">Delete</button></td></tr>`;
            tbody.innerHTML += row;
        }
    });
    
    // Update the gear toggle button text to match the current state
    if (tableId === "furnaceTable") {
        const gearToggleBtn = document.getElementById('gearToggleBtn');
        if (gearToggleBtn) {
            gearToggleBtn.textContent = gearColumnsVisible ? 'Hide Gear' : 'Show Gear';
        }
    }
}

function toggleGearColumns() {
    const gearColumns = document.querySelectorAll('.gear-columns');
    const gearToggleBtn = document.getElementById('gearToggleBtn');
    
    if (gearColumns.length > 0) {
        const isVisible = gearColumns[0].style.display !== 'none';
        
        gearColumns.forEach(cell => {
            cell.style.display = isVisible ? 'none' : 'table-cell';
        });
        
        gearToggleBtn.textContent = isVisible ? 'Show Gear' : 'Hide Gear';
    }
}

function addObject(action, formData) {
    if (!currentMapId) {
        alert("Please select a map first.");
        return;
    }
    
    formData.append('map_id', currentMapId);
    
    fetch(`api.php?action=${action}`, { method: "POST", cache: "no-store", body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                loadObjects();
            } else {
                alert("Error: " + data.message);
            }
        });
}

// 🚀 Download Template
function downloadTemplate() {
    if (!currentMapId) {
        alert("Please select a map first.");
        return;
    }
    
    window.location.href = `api.php?action=download_template&map_id=${currentMapId}`;
}

// 🚀 Delete a Trap
function deleteTrap(trapId) {
    if (!confirm('This will delete the trap from the list and map! Continue?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('map_id', currentMapId);
    formData.append('trap_id', trapId);
    if (currentVersion) {
        formData.append('version', currentVersion);
    }
    
    fetch(`api.php?action=delete_trap`, {
        method: "DELETE",
        cache: "no-store",
        body: formData
    })
    .then(response => response.json())
    .then((data) => {
        if (data.status === 'success') {
            if (hasMap()) {
                return regenerateSvg();
            }
            return;
        }
        return Promise.reject();
    })
    .then(svg => {
        renderSVG(svg);
    })
    .catch(() => { return; })
    .finally(() => {
        loadObjects(false);
        updateButtons();
    });
}

// 🚀 Delete a Miscellaneous Object
function deleteObject(objectId) {
    if (!confirm('This will delete the object from the list and map! Continue?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('map_id', currentMapId);
    formData.append('object_id', objectId);
    if (currentVersion) {
        formData.append('version', currentVersion);
    }
    
    fetch(`api.php?action=delete_object`, {
        method: "DELETE",
        cache: "no-store",
        body: formData
    })
    .then(response => response.json())
    .then((data) => {
        if (data.status === 'success') {
            if (hasMap()) {
                return regenerateSvg();
            }
            return;
        }
        return Promise.reject();
    })
    .then(svg => {
        renderSVG(svg);
    })
    .catch(() => { return; })
    .finally(() => {
        loadObjects(false);
        updateButtons();
    });
}

// 🚀 Delete a Furnace
function deleteFurnace(id) {
    if (!confirm('This will delete the furnace from the list and map! Continue?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('map_id', currentMapId);
    formData.append('furnace_id', id);
    if (currentVersion) {
        formData.append('version', currentVersion);
    }
    
    fetch(`api.php?action=delete_furnace`, {
        method: "DELETE",
        cache: "no-store",
        body: formData
    })
    .then(response => response.json())
    .then((data) => {
        if (data.status === 'success') {
            if (hasMap()) {
                return regenerateSvg();
            }
            return;
        }
        return Promise.reject();
    })
    .then(svg => {
        renderSVG(svg);
    })
    .catch(() => { return; })
    .finally(() => {
        loadObjects(false);
        updateButtons();
    });
}

// 🚀 Reset Furnaces
function resetFurnaces() {
    if (!confirm('This will delete all furnaces! Continue?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('map_id', currentMapId);
    if (currentVersion) {
        formData.append('version', currentVersion);
    }
    
    fetch(`api.php?action=reset_furnaces`, {
        method: "PUT",
        cache: "no-store",
        body: formData
    })
    .then(response => response.json())
    .then((data) => {
        if (data.status === 'success') {
            document.getElementById('map').innerHTML = '';
            loadObjects();
        }
    });
}

function renderSVG(svg) {
    document.getElementById("map").innerHTML = svg;
    const mapSVG = document.querySelector("#map svg");
    const classes = mapSVG.classList;
    
    mapSVG.addEventListener('click', () => classes.toggle('zoomed'));
}

// 🚀 Download CSV of Furnaces
function downloadCSV() {
    if (!currentMapId) {
        alert("Please select a map first.");
        return;
    }
    
    let url = `api.php?action=export_furnaces&map_id=${currentMapId}`;
    if (currentVersion) {
        url += `&version=${currentVersion}`;
    }
    
    window.location.href = url;
}

// 🚀 Download Map as SVG
function downloadSVG() {
    if (!currentMapId) {
        alert("Please select a map first.");
        return;
    }

    // Use the dynamic map.svg PHP endpoint
    let svgUrl = `map.svg?map_id=${currentMapId}`;
    if (currentVersion) {
        svgUrl += `&version=${currentVersion}`;
    }

    fetch(svgUrl, { cache: "no-store" })
    .then(response => {
        if (response.ok) {
            return response.text();
        } else {
            throw new Error('Failed to fetch SVG');
        }
    })
    .then(svgData => {
        const blob = new Blob([svgData], { type: "image/svg+xml;charset=utf-8" });
        const url = URL.createObjectURL(blob);

        const a = document.createElement("a");
        a.href = url;
        a.download = `map${currentVersion ? '_' + currentVersion : ''}.svg`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    })
    .catch(error => {
        console.error("Download failed:", error);
        alert("Error downloading SVG: " + error.message);
    });
}

// 🚀 Download Map as PNG
function downloadPNG() {
    if (!currentMapId) {
        alert("Please select a map first.");
        return;
    }

    // Use the dynamic map.svg PHP endpoint
    let svgUrl = `map.svg?map_id=${currentMapId}`;
    if (currentVersion) {
        svgUrl += `&version=${currentVersion}`;
    }

    fetch(svgUrl, { cache: "no-store" })
    .then(response => {
        if (response.ok) {
            return response.text();
        } else {
            throw new Error('Failed to fetch SVG');
        }
    })
    .then(svgData => {
        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");
        const img = new Image();
        const DOMURL = window.URL || window.webkitURL || window;

        const blob = new Blob([svgData], { type: "image/svg+xml;charset=utf-8" });
        const url = DOMURL.createObjectURL(blob);

        img.onload = function () {
            // Set canvas size based on SVG dimensions or use default
            const svgElement = document.querySelector("#map svg");
            if (svgElement) {
                canvas.width = svgElement.clientWidth || 800;
                canvas.height = svgElement.clientHeight || 600;
            } else {
                canvas.width = 800;
                canvas.height = 600;
            }
            
            ctx.drawImage(img, 0, 0);
            DOMURL.revokeObjectURL(url);

            // Create PNG download link
            const a = document.createElement("a");
            a.href = canvas.toDataURL("image/png");
            a.download = `map${currentVersion ? '_' + currentVersion : ''}.png`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        };
        
        img.onerror = function() {
            DOMURL.revokeObjectURL(url);
            alert("Error converting SVG to PNG. Please try downloading as SVG instead.");
        };
        
        img.src = url;
    })
    .catch(error => {
        console.error("PNG download failed:", error);
        alert("Error downloading PNG: " + error.message);
    });
}

// 🚀 Reset All Data
function resetData() {
    if (!confirm('This will delete all traps, objects, and furnaces! Continue?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('map_id', currentMapId);
    
    fetch("api.php?action=reset", { 
        method: "PUT",
        cache: "no-store",
        body: formData
    })
        .then(() => loadObjects());
}

// Gear Modal Functions
// Global variables for gear modal
let currentEditingFurnaceId = null;
let originalGearData = {}; // Current state when modal opens (saved or unsaved)
let savedGearData = {}; // The saved data from server for the current furnace
let modalGearData = {}; // The data in the modal (what user is editing)

function openGearModal(furnaceId) {
    currentEditingFurnaceId = furnaceId;
    
    // Find the furnace data (current state - saved or unsaved)
    const furnace = furnaces.find(f => f.id === furnaceId);
    if (!furnace) {
        console.error('Furnace not found:', furnaceId);
        return;
    }
    
    // 1. Store the current state of the furnace (whether saved or unsaved)
    originalGearData = {
        cap_level: furnace.cap_level || '',
        cap_charms: furnace.cap_charms || '',
        watch_level: furnace.watch_level || '',
        watch_charms: furnace.watch_charms || '',
        vest_level: furnace.vest_level || '',
        vest_charms: furnace.vest_charms || '',
        pants_level: furnace.pants_level || '',
        pants_charms: furnace.pants_charms || '',
        ring_level: furnace.ring_level || '',
        ring_charms: furnace.ring_charms || '',
        cane_level: furnace.cane_level || '',
        cane_charms: furnace.cane_charms || ''
    };
    
    // 2. Get the saved data from server for this specific furnace
    if (originalSavedGearData && originalSavedGearData[furnaceId]) {
        savedGearData = originalSavedGearData[furnaceId];
    } else {
        // Fallback to current data if no saved data is stored for this furnace
        savedGearData = { ...originalGearData };
    }
    
    // 3. Initialize modal data with current state
    modalGearData = { ...originalGearData };
    
    // Populate the modal with current state
    populateGearModal(furnace);
}

function populateGearModal(furnace) {
    // Update modal title
    document.querySelector('.gear-modal-title').textContent = `Edit Chief Gear - ${furnace.name}`;
    
    const gearGrid = document.getElementById('gearGrid');
    gearGrid.innerHTML = '';
    
    const gearTypes = [
        { key: 'cap', name: 'Cap' },
        { key: 'watch', name: 'Watch' },
        { key: 'vest', name: 'Vest' },
        { key: 'pants', name: 'Pants' },
        { key: 'ring', name: 'Ring' },
        { key: 'cane', name: 'Cane' }
    ];
    
    gearTypes.forEach(gearType => {
        const level = modalGearData[`${gearType.key}_level`] || '';
        const charms = modalGearData[`${gearType.key}_charms`] || '';
        
        const gearItem = document.createElement('div');
        gearItem.className = 'gear-item';
        gearItem.innerHTML = `
            <div class="gear-item-header">
                <h4>${gearType.name}</h4>
            </div>
            <div class="gear-item-content">
                <div class="gear-input-group">
                    <label for="${gearType.key}_level">Level:</label>
                    <select id="${gearType.key}_level" name="${gearType.key}_level">
                        <option value="">Not Set</option>
                        <option value="Uncommon">Uncommon</option>
                        <option value="Uncommon *">Uncommon *</option>
                        <option value="Rare">Rare</option>
                        <option value="Rare *">Rare *</option>
                        <option value="Rare **">Rare **</option>
                        <option value="Rare ***">Rare ***</option>
                        <option value="Epic">Epic</option>
                        <option value="Epic *">Epic *</option>
                        <option value="Epic **">Epic **</option>
                        <option value="Epic ***">Epic ***</option>
                        <option value="Epic T1">Epic T1</option>
                        <option value="Epic T1 *">Epic T1 *</option>
                        <option value="Epic T1 **">Epic T1 **</option>
                        <option value="Epic T1 ***">Epic T1 ***</option>
                        <option value="Mythic">Mythic</option>
                        <option value="Mythic *">Mythic *</option>
                        <option value="Mythic **">Mythic **</option>
                        <option value="Mythic ***">Mythic ***</option>
                        <option value="Mythic T1">Mythic T1</option>
                        <option value="Mythic T1 *">Mythic T1 *</option>
                        <option value="Mythic T1 **">Mythic T1 **</option>
                        <option value="Mythic T1 ***">Mythic T1 ***</option>
                        <option value="Mythic T2">Mythic T2</option>
                        <option value="Mythic T2 *">Mythic T2 *</option>
                        <option value="Mythic T2 **">Mythic T2 **</option>
                        <option value="Mythic T2 ***">Mythic T2 ***</option>
                        <option value="Legendary">Legendary</option>
                        <option value="Legendary *">Legendary *</option>
                        <option value="Legendary **">Legendary **</option>
                        <option value="Legendary ***">Legendary ***</option>
                        <option value="Legendary T1">Legendary T1</option>
                        <option value="Legendary T1 *">Legendary T1 *</option>
                        <option value="Legendary T1 **">Legendary T1 **</option>
                        <option value="Legendary T1 ***">Legendary T1 ***</option>
                        <option value="Legendary T2">Legendary T2</option>
                        <option value="Legendary T2 *">Legendary T2 *</option>
                        <option value="Legendary T2 **">Legendary T2 **</option>
                        <option value="Legendary T2 ***">Legendary T2 ***</option>
                        <option value="Legendary T3">Legendary T3</option>
                        <option value="Legendary T3 *">Legendary T3 *</option>
                        <option value="Legendary T3 **">Legendary T3 **</option>
                        <option value="Legendary T3 ***">Legendary T3 ***</option>
                    </select>
                </div>
                <div class="gear-input-group">
                    <label for="${gearType.key}_charms">Charms:</label>
                    <input type="text" id="${gearType.key}_charms" name="${gearType.key}_charms" 
                           placeholder="e.g., 3,4,3" value="${charms}">
                </div>
            </div>
        `;
        
        gearGrid.appendChild(gearItem);
        
        // Set the selected value for the level dropdown
        const levelSelect = gearItem.querySelector(`#${gearType.key}_level`);
        levelSelect.value = level;
        
        // Add event listeners to track changes
        levelSelect.addEventListener('change', function() {
            modalGearData[`${gearType.key}_level`] = this.value;
        });
        
        const charmsInput = gearItem.querySelector(`#${gearType.key}_charms`);
        charmsInput.addEventListener('input', function() {
            modalGearData[`${gearType.key}_charms`] = this.value;
        });
    });
    
    // Show the modal
    document.getElementById('gearModal').style.display = 'flex';
}

function closeGearModal() {
    document.getElementById('gearModal').style.display = 'none';
    currentEditingFurnaceId = null;
    originalGearData = {};
    savedGearData = {}; // Clear the saved data
    modalGearData = {}; // Clear the modal data
}

function saveGearChanges() {
    if (!currentEditingFurnaceId) return;
    
    // Collect current data from modal
    const gearTypes = ['cap', 'watch', 'vest', 'pants', 'ring', 'cane'];
    const gearData = {};
    
    gearTypes.forEach(gearType => {
        const levelSelect = document.getElementById(`${gearType}_level`);
        const charmsInput = document.getElementById(`${gearType}_charms`);
        
        gearData[`${gearType}_level`] = levelSelect ? levelSelect.value : '';
        gearData[`${gearType}_charms`] = charmsInput ? charmsInput.value : '';
    });
    
    // Update the furnace data in the furnaces array
    const furnaceIndex = furnaces.findIndex(f => f.id === currentEditingFurnaceId);
    if (furnaceIndex !== -1) {
        gearTypes.forEach(gearType => {
            furnaces[furnaceIndex][`${gearType}_level`] = gearData[`${gearType}_level`];
            furnaces[furnaceIndex][`${gearType}_charms`] = gearData[`${gearType}_charms`];
        });
    }
    
    // 5. Compare modal changes with saved data to determine if unsaved
    let hasChangesFromSaved = false;
    gearTypes.forEach(gearType => {
        if (gearData[`${gearType}_level`] !== savedGearData[`${gearType}_level`] ||
            gearData[`${gearType}_charms`] !== savedGearData[`${gearType}_charms`]) {
            hasChangesFromSaved = true;
        }
    });
    
    // Store gear columns visibility state before updateTable
    const gearColumnsVisible = document.querySelectorAll('.gear-columns').length > 0 && 
                              document.querySelector('.gear-columns').style.display !== 'none';
    
    // Update the table display
    updateTable("furnaceTable", furnaces, ["name", "level", "power", "rank", "participation", "trap_pref", "x", "y"], "deleteFurnace");
    
    // Force gear columns to be visible if they were visible before
    if (gearColumnsVisible) {
        document.querySelectorAll('.gear-columns').forEach(cell => {
            cell.style.display = 'table-cell';
        });
        document.getElementById('gearToggleBtn').textContent = 'Hide Gear';
    }
    
    // Mark as unsaved or remove unsaved status based on comparison with saved data
    if (hasChangesFromSaved) {
        // Mark as unsaved if there are changes from saved state
        markRowUnsaved(currentEditingFurnaceId);
    } else {
        // Remove unsaved status if changes match saved state
        const furnaceRow = document.getElementById(`furnace-${currentEditingFurnaceId}`);
        if (furnaceRow) {
            furnaceRow.classList.remove("unsaved");
            // Also remove unsaved status from SVG elements
            if (hasMap()) {
                document.querySelectorAll(`#map svg [data-obj-id="${currentEditingFurnaceId}"]`).forEach((el) => el.classList.remove('unsaved'));
            }
        }
    }
    
    // Close the modal
    closeGearModal();
    
    // Show feedback
    showGearChangeFeedback(currentEditingFurnaceId);
}

function showGearChangeFeedback(furnaceId) {
    // Find the furnace row
    const furnaceRow = document.getElementById(`furnace-${furnaceId}`);
    if (!furnaceRow) return;
    
    // Add a temporary highlight effect that works with existing styling
    const originalBackground = furnaceRow.style.backgroundColor;
    const originalTransition = furnaceRow.style.transition;
    
    furnaceRow.style.transition = 'background-color 0.3s ease';
    furnaceRow.style.backgroundColor = '#e8f5e8'; // Very light green background
    
    // Show a brief success message
    const successMessage = document.createElement('div');
    successMessage.className = 'gear-change-feedback';
    successMessage.textContent = '✓ Gear changes applied';
    successMessage.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: #28a745;
        color: white;
        padding: 10px 15px;
        border-radius: 4px;
        font-size: 14px;
        z-index: 1001;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        animation: slideInRight 0.3s ease;
    `;
    
    document.body.appendChild(successMessage);
    
    // Remove the highlight and message after 2 seconds
    setTimeout(() => {
        furnaceRow.style.backgroundColor = originalBackground;
        furnaceRow.style.transition = originalTransition;
        if (successMessage.parentNode) {
            successMessage.parentNode.removeChild(successMessage);
        }
    }, 2000);
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    const modal = document.getElementById('gearModal');
    if (event.target === modal) {
        closeGearModal();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeGearModal();
    }
});

async function loadUserInfo() {
    if (!window.sessionManager) {
        return;
    }

    const userInfo = window.sessionManager.getUserInfo();
    if (userInfo) {
        document.getElementById('username').textContent = userInfo.username;
        
        // Show admin link only for master users
        if (userInfo.is_master) {
            document.getElementById('adminLink').style.display = 'inline';
        } else {
            document.getElementById('adminLink').style.display = 'none';
        }
    }
}

// Update all fetch calls to include authentication
async function makeAuthenticatedRequest(url, options = {}) {
    if (!window.sessionManager) {
        window.location.href = 'login.html';
        return Promise.reject('No session manager');
    }

    try {
        return await window.sessionManager.makeAuthenticatedRequest(url, options);
    } catch (error) {
        if (error.message === 'Session expired') {
            window.location.href = 'login.html';
        }
        throw error;
    }
}

// Weighted Criteria Functions
function togglePriorityMode() {
    const simpleMode = document.getElementById('simplePriorityMode');
    const weightedMode = document.getElementById('weightedPriorityMode');
    
    if (this.value === 'simple') {
        simpleMode.style.display = 'block';
        weightedMode.style.display = 'none';
    } else {
        simpleMode.style.display = 'none';
        weightedMode.style.display = 'block';
    }
}

function updateWeightValue() {
    const value = this.value;
    const valueDisplay = this.parentElement.querySelector('.weight-value');
    valueDisplay.textContent = parseFloat(value).toFixed(1);
}

function getWeightedCriteria() {
    const criteriaWeights = [];
    document.querySelectorAll('.weight-slider').forEach(slider => {
        const criteria = slider.getAttribute('data-criteria');
        const weight = parseFloat(slider.value);
        // Include all criteria with their weights, even if weight is 0
        criteriaWeights.push({
            criteria: criteria,
            weight: weight
        });
    });
    return criteriaWeights;
}
