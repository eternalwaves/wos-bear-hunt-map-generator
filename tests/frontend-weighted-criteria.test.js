/**
 * @jest-environment jsdom
 */

// Mock the session manager
const mockSessionManager = {
    isUserAuthenticated: jest.fn(() => true),
    getUserInfo: jest.fn(() => ({ username: 'testuser', is_master: false })),
    makeAuthenticatedRequest: jest.fn(() => Promise.resolve({ status: 'success' }))
};

// Mock global variables
global.currentMapId = 'test-map-id';
global.currentVersion = 'current';

// Mock DOM elements
document.body.innerHTML = `
    <div class="priority-controls">
        <div class="priority-mode">
            <label>
                <input type="radio" name="priorityMode" value="simple" checked> Simple Priority
            </label>
            <label>
                <input type="radio" name="priorityMode" value="weighted"> Weighted Criteria
            </label>
        </div>
        
        <div id="simplePriorityMode">
            <ol id="sortPriorityList">
                <li data-value="participation">Participation</li>
                <li data-value="level">Level</li>
                <li data-value="rank">Rank</li>
                <li data-value="power">Power</li>
            </ol>
        </div>
        
        <div id="weightedPriorityMode" style="display: none;">
            <div id="weightedCriteriaList">
                <div class="criteria-item">
                    <label>Power:</label>
                    <input type="range" min="0" max="10" value="1" step="0.1" data-criteria="power" class="weight-slider">
                    <span class="weight-value">1.0</span>
                </div>
                <div class="criteria-item">
                    <label>Level:</label>
                    <input type="range" min="0" max="10" value="1" step="0.1" data-criteria="level" class="weight-slider">
                    <span class="weight-value">1.0</span>
                </div>
                <div class="criteria-item">
                    <label>Rank:</label>
                    <input type="range" min="0" max="10" value="1" step="0.1" data-criteria="rank" class="weight-slider">
                    <span class="weight-value">1.0</span>
                </div>
                <div class="criteria-item">
                    <label>Participation:</label>
                    <input type="range" min="0" max="10" value="1" step="0.1" data-criteria="participation" class="weight-slider">
                    <span class="weight-value">1.0</span>
                </div>
                <div class="criteria-item">
                    <label>Chief Gear and Charms:</label>
                    <input type="range" min="0" max="10" value="1" step="0.1" data-criteria="chief_gear_and_charms" class="weight-slider">
                    <span class="weight-value">1.0</span>
                </div>
            </div>
        </div>
    </div>
`;

// Mock fetch
global.fetch = jest.fn();

// Mock window.sessionManager
Object.defineProperty(window, 'sessionManager', {
    value: mockSessionManager,
    writable: true
});

// Mock confirm
global.confirm = jest.fn(() => true);

// Mock alert
global.alert = jest.fn();

// Import the functions we want to test
// Note: In a real setup, these would be imported from the actual script.js file
// For testing purposes, we'll define them here

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
        if (weight > 0) {
            criteriaWeights.push({
                criteria: criteria,
                weight: weight
            });
        }
    });
    return criteriaWeights;
}

function generateMap() {
    if (!confirm('This will reassign all furnace positions (besides locked furnaces)! Continue?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('map_id', currentMapId);

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

    return fetch("api.php?action=generate_map", {
        method: "POST",
        cache: "reload",
        body: formData
    });
}

describe('Weighted Criteria Frontend Tests', () => {
    beforeEach(() => {
        // Reset mocks
        jest.clearAllMocks();
        
        // Reset DOM state
        document.querySelector('input[value="simple"]').checked = true;
        document.getElementById('simplePriorityMode').style.display = 'block';
        document.getElementById('weightedPriorityMode').style.display = 'none';
        
        // Reset slider values
        document.querySelectorAll('.weight-slider').forEach(slider => {
            slider.value = '1';
            const valueDisplay = slider.parentElement.querySelector('.weight-value');
            valueDisplay.textContent = '1.0';
        });
    });

    describe('togglePriorityMode', () => {
        test('should show simple mode when simple radio is selected', () => {
            const simpleRadio = document.querySelector('input[value="simple"]');
            const simpleMode = document.getElementById('simplePriorityMode');
            const weightedMode = document.getElementById('weightedPriorityMode');
            
            simpleRadio.checked = true;
            togglePriorityMode.call(simpleRadio);
            
            expect(simpleMode.style.display).toBe('block');
            expect(weightedMode.style.display).toBe('none');
        });

        test('should show weighted mode when weighted radio is selected', () => {
            const weightedRadio = document.querySelector('input[value="weighted"]');
            const simpleMode = document.getElementById('simplePriorityMode');
            const weightedMode = document.getElementById('weightedPriorityMode');
            
            weightedRadio.checked = true;
            togglePriorityMode.call(weightedRadio);
            
            expect(simpleMode.style.display).toBe('none');
            expect(weightedMode.style.display).toBe('block');
        });
    });

    describe('updateWeightValue', () => {
        test('should update weight value display when slider changes', () => {
            const slider = document.querySelector('.weight-slider');
            const valueDisplay = slider.parentElement.querySelector('.weight-value');
            
            slider.value = '3.5';
            updateWeightValue.call(slider);
            
            expect(valueDisplay.textContent).toBe('3.5');
        });

        test('should format decimal values correctly', () => {
            const slider = document.querySelector('.weight-slider');
            const valueDisplay = slider.parentElement.querySelector('.weight-value');
            
            slider.value = '2.333333';
            updateWeightValue.call(slider);
            
            expect(valueDisplay.textContent).toBe('2.3');
        });
    });

    describe('getWeightedCriteria', () => {
        test('should return all criteria with positive weights', () => {
            const criteria = getWeightedCriteria();
            
            expect(criteria).toHaveLength(5);
            expect(criteria).toContainEqual({
                criteria: 'power',
                weight: 1.0
            });
            expect(criteria).toContainEqual({
                criteria: 'level',
                weight: 1.0
            });
            expect(criteria).toContainEqual({
                criteria: 'rank',
                weight: 1.0
            });
            expect(criteria).toContainEqual({
                criteria: 'participation',
                weight: 1.0
            });
            expect(criteria).toContainEqual({
                criteria: 'chief_gear_and_charms',
                weight: 1.0
            });
        });

        test('should exclude criteria with zero weight', () => {
            const slider = document.querySelector('.weight-slider');
            slider.value = '0';
            updateWeightValue.call(slider);
            
            const criteria = getWeightedCriteria();
            
            expect(criteria).toHaveLength(4);
            expect(criteria.find(c => c.criteria === 'power')).toBeUndefined();
        });

        test('should handle different weight values', () => {
            const sliders = document.querySelectorAll('.weight-slider');
            sliders[0].value = '2.5'; // power
            sliders[1].value = '3.0'; // level
            sliders[2].value = '0.5'; // rank
            sliders[3].value = '0';   // participation (should be excluded)
            sliders[4].value = '1.5'; // chief_gear_and_charms
            
            sliders.forEach(slider => updateWeightValue.call(slider));
            
            const criteria = getWeightedCriteria();
            
            expect(criteria).toHaveLength(4);
            expect(criteria).toContainEqual({
                criteria: 'power',
                weight: 2.5
            });
            expect(criteria).toContainEqual({
                criteria: 'level',
                weight: 3.0
            });
            expect(criteria).toContainEqual({
                criteria: 'rank',
                weight: 0.5
            });
            expect(criteria).toContainEqual({
                criteria: 'chief_gear_and_charms',
                weight: 1.5
            });
        });
    });

    describe('generateMap', () => {
        test('should use simple priority when simple mode is selected', async () => {
            const simpleRadio = document.querySelector('input[value="simple"]');
            simpleRadio.checked = true;
            
            fetch.mockResolvedValueOnce({
                json: () => Promise.resolve({ status: 'success' })
            });
            
            await generateMap();
            
            expect(fetch).toHaveBeenCalledWith(
                "api.php?action=generate_map",
                expect.objectContaining({
                    method: "POST",
                    body: expect.any(FormData)
                })
            );
            
            // Verify that sort_priority was added to FormData
            const formData = fetch.mock.calls[0][1].body;
            expect(formData.get('map_id')).toBe('test-map-id');
            expect(formData.get('sort_priority')).toBe('participation,level,rank,power');
        });

        test('should use weighted criteria when weighted mode is selected', async () => {
            const weightedRadio = document.querySelector('input[value="weighted"]');
            weightedRadio.checked = true;
            
            // Set some custom weights
            const sliders = document.querySelectorAll('.weight-slider');
            sliders[0].value = '2.5'; // power
            sliders[1].value = '1.5'; // level
            sliders[2].value = '0.5'; // rank
            sliders[3].value = '0';   // participation
            sliders[4].value = '3.0'; // chief_gear_and_charms
            
            sliders.forEach(slider => updateWeightValue.call(slider));
            
            fetch.mockResolvedValueOnce({
                json: () => Promise.resolve({ status: 'success' })
            });
            
            await generateMap();
            
            expect(fetch).toHaveBeenCalledWith(
                "api.php?action=generate_map",
                expect.objectContaining({
                    method: "POST",
                    body: expect.any(FormData)
                })
            );
            
            // Verify that criteria_weights was added to FormData
            const formData = fetch.mock.calls[0][1].body;
            expect(formData.get('map_id')).toBe('test-map-id');
            
            const criteriaWeights = JSON.parse(formData.get('criteria_weights'));
            expect(criteriaWeights).toHaveLength(4);
            expect(criteriaWeights).toContainEqual({
                criteria: 'power',
                weight: 2.5
            });
            expect(criteriaWeights).toContainEqual({
                criteria: 'level',
                weight: 1.5
            });
            expect(criteriaWeights).toContainEqual({
                criteria: 'rank',
                weight: 0.5
            });
            expect(criteriaWeights).toContainEqual({
                criteria: 'chief_gear_and_charms',
                weight: 3.0
            });
        });

        test('should not make request if user cancels confirmation', async () => {
            confirm.mockReturnValueOnce(false);
            
            await generateMap();
            
            expect(fetch).not.toHaveBeenCalled();
        });
    });

    describe('DOM Structure', () => {
        test('should have all required elements', () => {
            expect(document.querySelector('input[value="simple"]')).toBeTruthy();
            expect(document.querySelector('input[value="weighted"]')).toBeTruthy();
            expect(document.getElementById('simplePriorityMode')).toBeTruthy();
            expect(document.getElementById('weightedPriorityMode')).toBeTruthy();
            expect(document.getElementById('sortPriorityList')).toBeTruthy();
            expect(document.getElementById('weightedCriteriaList')).toBeTruthy();
        });

        test('should have correct number of weight sliders', () => {
            const sliders = document.querySelectorAll('.weight-slider');
            expect(sliders).toHaveLength(5);
        });

        test('should have correct criteria attributes on sliders', () => {
            const sliders = document.querySelectorAll('.weight-slider');
            const criteria = Array.from(sliders).map(slider => slider.getAttribute('data-criteria'));
            
            expect(criteria).toContain('power');
            expect(criteria).toContain('level');
            expect(criteria).toContain('rank');
            expect(criteria).toContain('participation');
            expect(criteria).toContain('chief_gear_and_charms');
        });

        test('should have correct slider attributes', () => {
            const sliders = document.querySelectorAll('.weight-slider');
            sliders.forEach(slider => {
                expect(slider.getAttribute('min')).toBe('0');
                expect(slider.getAttribute('max')).toBe('10');
                expect(slider.getAttribute('step')).toBe('0.1');
                expect(slider.getAttribute('value')).toBe('1');
            });
        });
    });
}); 