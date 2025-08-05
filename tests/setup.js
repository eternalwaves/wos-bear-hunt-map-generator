require('@testing-library/jest-dom');

// Mock fetch globally
global.fetch = jest.fn();

// Mock localStorage
const localStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn(),
};
global.localStorage = localStorageMock;

// Mock URL.createObjectURL and URL.revokeObjectURL
global.URL.createObjectURL = jest.fn(() => 'mocked-url');
global.URL.revokeObjectURL = jest.fn();

// Mock canvas for PNG download tests
HTMLCanvasElement.prototype.getContext = jest.fn(() => ({
  drawImage: jest.fn(),
  toDataURL: jest.fn(() => 'data:image/png;base64,mocked-data'),
}));

// Mock Image for PNG download tests
global.Image = class {
  constructor() {
    setTimeout(() => {
      if (this.onload) {
        this.onload();
      }
    }, 0);
  }
};

// Helper function to create a mock DOM element
global.createMockElement = (tagName, attributes = {}) => {
  const element = document.createElement(tagName);
  Object.keys(attributes).forEach(key => {
    element.setAttribute(key, attributes[key]);
  });
  return element;
};

// Helper function to simulate API responses
global.mockApiResponse = (data, status = 200) => {
  fetch.mockResolvedValueOnce({
    ok: status >= 200 && status < 300,
    status,
    json: () => Promise.resolve(data),
    text: () => Promise.resolve(data),
  });
};

// Helper function to create test HTML structure
global.setupTestHTML = () => {
  document.body.innerHTML = `
    <div id="mapSelector">
      <select id="mapSelect">
        <option value="">Select a map...</option>
        <option value="map1">Test Map 1</option>
        <option value="map2">Test Map 2</option>
      </select>
    </div>
    
    <div id="versionSelector" style="display: none;">
      <select id="versionSelect">
        <option value="">Latest</option>
        <option value="1.0">Version 1.0</option>
        <option value="2.0">Version 2.0</option>
      </select>
    </div>
    
    <form id="addFurnaceForm">
      <input type="text" name="name" value="Test Furnace">
      <input type="text" name="level" value="FC1">
      <input type="number" name="power" value="100">
      <input type="text" name="rank" value="R1">
      <input type="number" name="participation" value="2">
      <input type="text" name="trap_pref" value="both">
      <input type="number" name="x" value="10">
      <input type="number" name="y" value="20">
      <select name="cap_level">
        <option value="">Select Cap Level</option>
        <option value="Epic" selected>Epic</option>
        <option value="Legendary">Legendary</option>
      </select>
      <input type="text" name="cap_charms" value="3,4,3">
      <button type="submit">Add Furnace</button>
    </form>
    
    <table id="furnaceTable">
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Level</th>
          <th>Power</th>
          <th class="gear-columns" style="display: none;">Cap</th>
          <th class="gear-columns" style="display: none;">Watch</th>
          <th class="gear-columns" style="display: none;">Vest</th>
          <th class="gear-columns" style="display: none;">Pants</th>
          <th class="gear-columns" style="display: none;">Ring</th>
          <th class="gear-columns" style="display: none;">Cane</th>
          <th>Rank</th>
          <th>Participation</th>
          <th>Trap Pref</th>
          <th>X</th>
          <th>Y</th>
          <th>Shift</th>
          <th>Actions</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
    
    <div class="table-controls">
      <button type="button" onclick="toggleGearColumns()" id="gearToggleBtn">Show Gear</button>
    </div>
    
    <div id="map"></div>
    <div id="loader" style="display: none;">Loading...</div>
    
    <!-- Gear Editor Modal -->
    <div id="gearModal" class="gear-modal">
        <div class="gear-modal-content">
            <div class="gear-modal-header">
                <h3 class="gear-modal-title">Edit Chief Gear</h3>
                <span class="gear-modal-close" onclick="closeGearModal()">&times;</span>
            </div>
            <div class="gear-grid" id="gearGrid">
                <!-- Gear items will be populated by JavaScript -->
            </div>
            <div class="gear-modal-actions">
                <button class="gear-modal-cancel" onclick="closeGearModal()">Cancel</button>
                <button class="gear-modal-save" onclick="saveGearChanges()">Apply Changes</button>
            </div>
        </div>
    </div>
    
    <datalist id="gear-levels">
      <option value="Uncommon">Uncommon</option>
      <option value="Epic">Epic</option>
      <option value="Legendary">Legendary</option>
    </datalist>
  `;
  
  // Mock session manager before loading script
  window.sessionManager = {
    initialize: jest.fn().mockResolvedValue(true),
    isUserAuthenticated: jest.fn().mockReturnValue(true),
    getUserInfo: jest.fn().mockReturnValue({ username: 'testuser', is_master: false }),
    logout: jest.fn().mockResolvedValue(true),
    makeAuthenticatedRequest: jest.fn().mockImplementation((url, options) => {
      return fetch(url, options);
    })
  };
  
  // Mock global variables that script.js expects
  window.currentMapId = null;
  window.currentVersion = null;
  
  // Load the actual script.js file to get all the functions
  const fs = require('fs');
  const path = require('path');
  const scriptPath = path.join(__dirname, '../public/script.js');
  
  if (fs.existsSync(scriptPath)) {
    const scriptContent = fs.readFileSync(scriptPath, 'utf8');
    
    // Wrap the script content to avoid DOMContentLoaded execution
    const wrappedScript = `
      // Prevent DOMContentLoaded from running during tests
      const originalAddEventListener = document.addEventListener;
      document.addEventListener = function(type, listener, options) {
        if (type === 'DOMContentLoaded') {
          // Don't add the DOMContentLoaded listener during tests
          return;
        }
        return originalAddEventListener.call(this, type, listener, options);
      };
      
      ${scriptContent}
      
      // Restore original addEventListener
      document.addEventListener = originalAddEventListener;
    `;
    
    // Execute the script in the global context
    eval(wrappedScript);
  }
}; 