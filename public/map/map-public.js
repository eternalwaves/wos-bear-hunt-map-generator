let currentMapId = null;
let currentVersion = null;
let currentMapData = null;

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    // Check for URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const mapIdParam = urlParams.get('map_id');
    const versionParam = urlParams.get('version');
    
    if (mapIdParam) {
        currentMapId = mapIdParam;
        currentVersion = versionParam;
        loadMapData();
    } else {
        // No map ID provided, get the latest map
        loadLatestMap();
    }
    
    setupTableSorting();
});

function loadLatestMap() {
    const loader = document.getElementById("loader");
    loader.style.display = "block";

    fetch("api.php?action=get_maps", {
        method: "GET",
        cache: "no-store"
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success" && data.data.length > 0) {
            // Get the first (latest) map
            currentMapId = data.data[0].id;
            currentVersion = null; // Use latest version
            loadMapData();
        } else {
            // No maps available
            document.getElementById("mapContent").style.display = "none";
        }
    })
    .catch(error => {
        console.error("Error:", error);
        document.getElementById("mapContent").style.display = "none";
    })
    .finally(() => {
        loader.style.display = "none";
    });
}

function loadMapData() {
    const loader = document.getElementById("loader");
    loader.style.display = "block";

    let url = `api.php?action=get_public_map_data&map_id=${currentMapId}`;
    if (currentVersion) {
        url += `&version=${currentVersion}`;
    }

    fetch(url, {
        method: "GET",
        cache: "no-store"
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            currentMapData = data.data;
            displayMapData(data.data);
            document.getElementById("mapContent").style.display = "block";
        } else {
            alert("Error loading map data: " + data.message);
            document.getElementById("mapContent").style.display = "none";
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("Error loading map data");
        document.getElementById("mapContent").style.display = "none";
    })
    .finally(() => {
        loader.style.display = "none";
    });
}

function displayMapData(mapData) {
    displayChiefsTable(mapData.furnaces);
    displayMap(mapData);
}

function displayChiefsTable(furnaces) {
    const tableBody = document.getElementById("chiefsTableBody");
    tableBody.innerHTML = "";
    
    furnaces.forEach(furnace => {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${escapeHtml(furnace.name)}</td>
            <td>${escapeHtml(furnace.trap_pref)}</td>
            <td>${escapeHtml(furnace.coordinates)}</td>
            <td>${escapeHtml(furnace.power)}</td>
            <td>${escapeHtml(furnace.participation)}</td>
        `;
        tableBody.appendChild(row);
    });
}

function displayMap(mapData) {
    // Use the same SVG rendering as the private page
    renderSVG();
}

function renderSVG() {
    if (!currentMapId) {
        return;
    }
    
    // Use the same SVG endpoint as the private page
    let svgUrl = `../map.svg?map_id=${currentMapId}`;
    if (currentVersion) {
        svgUrl += `&version=${currentVersion}`;
    }
    
    fetch(svgUrl, { cache: "no-store" })
    .then(response => {
        if (response.ok) {
            return response.text();
        }
        return Promise.reject();
    })
    .then(svg => {
        document.getElementById("mapDisplay").innerHTML = svg;
        const mapSVG = document.querySelector("#mapDisplay svg");
        if (mapSVG) {
            const classes = mapSVG.classList;
            mapSVG.addEventListener('click', () => classes.toggle('zoomed'));
        }
    })
    .catch(() => { 
        console.error("Failed to load map SVG");
    });
}


function setupTableSorting() {
    const table = document.getElementById("chiefsTable");
    const headers = table.querySelectorAll("th.sortable");
    
    headers.forEach(header => {
        header.addEventListener("click", function() {
            const sortBy = this.dataset.sort;
            const currentOrder = this.dataset.order || "asc";
            const newOrder = currentOrder === "asc" ? "desc" : "asc";
            
            // Reset all headers
            headers.forEach(h => {
                h.dataset.order = "";
                h.querySelector(".sort-indicator").textContent = "↕";
            });
            
            // Set current header
            this.dataset.order = newOrder;
            this.querySelector(".sort-indicator").textContent = newOrder === "asc" ? "↑" : "↓";
            
            // Sort the table
            sortTable(sortBy, newOrder);
        });
    });
}

function sortTable(sortBy, order) {
    const table = document.getElementById("chiefsTable");
    const tbody = table.querySelector("tbody");
    const rows = Array.from(tbody.querySelectorAll("tr"));
    
    rows.sort((a, b) => {
        const aValue = getCellValue(a, sortBy);
        const bValue = getCellValue(b, sortBy);
        
        // Handle numeric values
        if (sortBy === "power" || sortBy === "participation") {
            const aNum = parseFloat(aValue) || 0;
            const bNum = parseFloat(bValue) || 0;
            return order === "asc" ? aNum - bNum : bNum - aNum;
        }
        
        // Handle string values
        const comparison = aValue.localeCompare(bValue);
        return order === "asc" ? comparison : -comparison;
    });
    
    // Re-append sorted rows
    rows.forEach(row => tbody.appendChild(row));
}

function getCellValue(row, sortBy) {
    const cellIndex = {
        "name": 0,
        "trap_pref": 1,
        "coordinates": 2,
        "power": 3,
        "participation": 4
    };
    
    const cell = row.cells[cellIndex[sortBy]];
    return cell ? cell.textContent.trim() : "";
}

function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
}