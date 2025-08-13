// MapGeneratorView Logic
import { Furnace } from '../../models/Furnace.js';
import { Trap } from '../../models/Trap.js';
import { MiscObject } from '../../models/MiscObject.js';

export class MapGeneratorViewLogic {
  constructor(component) {
    this.component = component;
    this.furnaces = [];
    this.traps = [];
    this.miscObjects = [];
    this.occupied = new Set();
    this.newOccupied = new Set();
    this.cellSize = 50;
    this.originalSavedGearData = {};
    this.currentPrioritySettings = {
      mode: 'simple',
      simpleOrder: ['participation', 'level', 'rank', 'power'],
      weightedCriteria: [
        { criteria: 'power', weight: 1.0 },
        { criteria: 'level', weight: 1.0 },
        { criteria: 'rank', weight: 1.0 },
        { criteria: 'participation', weight: 1.0 },
        { criteria: 'chief_gear_and_charms', weight: 1.0 }
      ]
    };
  }

  async loadMaps() {
    this.component.loading = true;
    try {
      const response = await fetch('/api.php?action=get_all_maps', { cache: 'no-store' });
      const data = await response.json();
      
      if (data.status === 'success') {
        this.component.maps = data.data || [];
        
        // Auto-select first map if only one exists
        if (this.component.maps.length === 1) {
          this.component.currentMap = this.component.maps[0];
          await this.loadVersions();
          await this.loadObjects();
        }
      } else {
        console.error('Error loading maps:', data.message);
        this.component.maps = [];
      }
    } catch (error) {
      console.error('Failed to load maps:', error);
      this.component.maps = [];
    } finally {
      this.component.loading = false;
    }
  }

  async loadVersions() {
    if (!this.component.currentMap) return;
    
    try {
      const response = await fetch(`/api.php?action=get_versions&map_id=${this.component.currentMap.id}`, { cache: 'no-store' });
      const data = await response.json();
      
      if (data.status === 'success') {
        this.component.versions = data.data || [];
      }
    } catch (error) {
      console.error('Failed to load versions:', error);
    }
  }

  async loadObjects() {
    if (!this.component.currentMap) {
      this.clearMapData();
      return;
    }

    try {
      let url = `/api.php?action=get_objects&map_id=${this.component.currentMap.id}`;
      if (this.component.currentVersion) {
        url += `&version=${this.component.currentVersion}`;
      }

      const response = await fetch(url, { cache: 'no-store' });
      const data = await response.json();

      if (data.status === 'success') {
        const mapData = data.data;
        
        // Convert raw data to model instances
        this.furnaces = mapData.furnaces.map(f => {
          const furnace = new Furnace(
            f.name, f.level, f.power, f.rank, f.participation, f.trap_pref,
            f.x, f.y, f.id, f.status, f.locked,
            f.cap_level, f.watch_level, f.vest_level, f.pants_level, f.ring_level, f.cane_level,
            f.cap_charms, f.watch_charms, f.vest_charms, f.pants_charms, f.ring_charms, f.cane_charms
          );
          
          // Auto-assign status to "assigned" if both X and Y are set but status is empty
          if (furnace.x && furnace.y && !furnace.status) {
            furnace.status = 'assigned';
          }
          
          return furnace;
        });

        this.traps = mapData.traps.map(t => new Trap(t.x, t.y, t.id));
        this.miscObjects = mapData.misc.map(m => new MiscObject(m.name, m.x, m.y, m.size, m.id));

        this.occupied = new Set(Object.keys(mapData.occupied));
        this.newOccupied = new Set(this.occupied);
        this.cellSize = mapData.cellSize || 50;

        // Store original saved data for each furnace (for change tracking)
        this.originalSavedData = {};
        this.furnaces.forEach(furnace => {
          this.originalSavedData[furnace.id] = {
            name: this.normalizeForComparison(furnace.name),
            level: this.normalizeForComparison(furnace.level),
            power: this.normalizeForComparison(furnace.power),
            rank: this.normalizeForComparison(furnace.rank),
            participation: this.normalizeForComparison(furnace.participation),
            trapPref: this.normalizeForComparison(furnace.trapPref),
            x: this.normalizeForComparison(furnace.x),
            y: this.normalizeForComparison(furnace.y),
            status: this.normalizeForComparison(furnace.status),
            locked: this.normalizeForComparison(furnace.locked),
            capLevel: this.normalizeForComparison(furnace.capLevel),
            capCharms: this.normalizeForComparison(furnace.capCharms),
            watchLevel: this.normalizeForComparison(furnace.watchLevel),
            watchCharms: this.normalizeForComparison(furnace.watchCharms),
            vestLevel: this.normalizeForComparison(furnace.vestLevel),
            vestCharms: this.normalizeForComparison(furnace.vestCharms),
            pantsLevel: this.normalizeForComparison(furnace.pantsLevel),
            pantsCharms: this.normalizeForComparison(furnace.pantsCharms),
            ringLevel: this.normalizeForComparison(furnace.ringLevel),
            ringCharms: this.normalizeForComparison(furnace.ringCharms),
            caneLevel: this.normalizeForComparison(furnace.caneLevel),
            caneCharms: this.normalizeForComparison(furnace.caneCharms)
          };
        });

        // Update child components directly
        this.updateChildComponents();
        
        // Apply priority settings if available
        if (mapData.weightedCriteria) {
          console.log('Loading weighted criteria:', mapData.weightedCriteria);
          this.applyPrioritySettings({
            mode: 'weighted',
            weightedCriteria: mapData.weightedCriteria
          });
        } else {
          console.log('No weighted criteria found, using simple priority mode');
          this.applyPrioritySettings({
            mode: 'simple'
          });
        }
        
        // Load SVG map
        await this.loadSVGMap();
      }
    } catch (error) {
      console.error('Failed to load objects:', error);
    }
  }

  async loadSVGMap() {
    if (!this.component.currentMap) return;

    try {
      let svgUrl = `/map.svg?map_id=${this.component.currentMap.id}`;
      if (this.component.currentVersion) {
        svgUrl += `&version=${this.component.currentVersion}`;
      }

      const response = await fetch(svgUrl, { cache: 'no-store' });
      if (response.ok) {
        const svg = await response.text();
        this.component.svgContent = svg;
        this.component.mapGenerated = true;
      }
    } catch (error) {
      console.error('Failed to load SVG map:', error);
    }
  }

  updateChildComponents() {
    // Directly update component properties instead of dispatching events
    this.component.furnaces = this.furnaces;
    this.component.traps = this.traps;
    this.component.miscObjects = this.miscObjects;
    this.component.hasData = this.furnaces.length > 0 || this.traps.length > 0 || this.miscObjects.length > 0;
  }

  // Helper function to normalize values for comparison
  normalizeForComparison(value) {
    // Treat null and empty string as equivalent
    if (value === null || value === '') {
      return '';
    }
    
    // Treat NaN and 0 as equivalent
    if (isNaN(value) || value === 0) {
      return 0;
    }
    
    return value;
  }

  hasUnsavedChanges(furnace) {
    if (!furnace || !furnace.id || !this.originalSavedData[furnace.id]) {
      return false;
    }

    const original = this.originalSavedData[furnace.id];
    
    // Compare all properties using normalized values
    const hasChanges = (
      this.normalizeForComparison(furnace.name) !== this.normalizeForComparison(original.name) ||
      this.normalizeForComparison(furnace.level) !== this.normalizeForComparison(original.level) ||
      this.normalizeForComparison(furnace.power) !== this.normalizeForComparison(original.power) ||
      this.normalizeForComparison(furnace.rank) !== this.normalizeForComparison(original.rank) ||
      this.normalizeForComparison(furnace.participation) !== this.normalizeForComparison(original.participation) ||
      this.normalizeForComparison(furnace.trapPref) !== this.normalizeForComparison(original.trapPref) ||
      this.normalizeForComparison(furnace.x) !== this.normalizeForComparison(original.x) ||
      this.normalizeForComparison(furnace.y) !== this.normalizeForComparison(original.y) ||
      this.normalizeForComparison(furnace.status) !== this.normalizeForComparison(original.status) ||
      this.normalizeForComparison(furnace.locked) !== this.normalizeForComparison(original.locked) ||
      this.normalizeForComparison(furnace.capLevel) !== this.normalizeForComparison(original.capLevel) ||
      this.normalizeForComparison(furnace.capCharms) !== this.normalizeForComparison(original.capCharms) ||
      this.normalizeForComparison(furnace.watchLevel) !== this.normalizeForComparison(original.watchLevel) ||
      this.normalizeForComparison(furnace.watchCharms) !== this.normalizeForComparison(original.watchCharms) ||
      this.normalizeForComparison(furnace.vestLevel) !== this.normalizeForComparison(original.vestLevel) ||
      this.normalizeForComparison(furnace.vestCharms) !== this.normalizeForComparison(original.vestCharms) ||
      this.normalizeForComparison(furnace.pantsLevel) !== this.normalizeForComparison(original.pantsLevel) ||
      this.normalizeForComparison(furnace.pantsCharms) !== this.normalizeForComparison(original.pantsCharms) ||
      this.normalizeForComparison(furnace.ringLevel) !== this.normalizeForComparison(original.ringLevel) ||
      this.normalizeForComparison(furnace.ringCharms) !== this.normalizeForComparison(original.ringCharms) ||
      this.normalizeForComparison(furnace.caneLevel) !== this.normalizeForComparison(original.caneLevel) ||
      this.normalizeForComparison(furnace.caneCharms) !== this.normalizeForComparison(original.caneCharms)
    );
    
    return hasChanges;
  }

  markFurnaceAsSaved(furnace) {
    if (furnace && furnace.id) {
      // Update the original saved data to match current values (normalized)
      this.originalSavedData[furnace.id] = {
        name: this.normalizeForComparison(furnace.name),
        level: this.normalizeForComparison(furnace.level),
        power: this.normalizeForComparison(furnace.power),
        rank: this.normalizeForComparison(furnace.rank),
        participation: this.normalizeForComparison(furnace.participation),
        trapPref: this.normalizeForComparison(furnace.trapPref),
        x: this.normalizeForComparison(furnace.x),
        y: this.normalizeForComparison(furnace.y),
        status: this.normalizeForComparison(furnace.status),
        locked: this.normalizeForComparison(furnace.locked),
        capLevel: this.normalizeForComparison(furnace.capLevel),
        capCharms: this.normalizeForComparison(furnace.capCharms),
        watchLevel: this.normalizeForComparison(furnace.watchLevel),
        watchCharms: this.normalizeForComparison(furnace.watchCharms),
        vestLevel: this.normalizeForComparison(furnace.vestLevel),
        vestCharms: this.normalizeForComparison(furnace.vestCharms),
        pantsLevel: this.normalizeForComparison(furnace.pantsLevel),
        pantsCharms: this.normalizeForComparison(furnace.pantsCharms),
        ringLevel: this.normalizeForComparison(furnace.ringLevel),
        ringCharms: this.normalizeForComparison(furnace.ringCharms),
        caneLevel: this.normalizeForComparison(furnace.caneLevel),
        caneCharms: this.normalizeForComparison(furnace.caneCharms)
      };
    }
  }

  applyPrioritySettings(settings) {
    // Find the priority selection view component
    const priorityView = this.component.shadowRoot?.querySelector('#prioritySelectionView');
    if (priorityView) {
      priorityView.setPrioritySettings(settings);
    }
  }

  clearMapData() {
    this.furnaces = [];
    this.traps = [];
    this.miscObjects = [];
    this.occupied = new Set();
    this.newOccupied = new Set();
    this.originalSavedData = {};
    this.component.svgContent = '';
    this.component.mapGenerated = false;
    this.updateChildComponents();
  }

  onMapCreated(event) {
    // Handle map creation
    console.log('Map created:', event.detail);
    this.loadMaps();
  }

  onMapSelected(event) {
    this.component.currentMap = event.detail.map;
    this.component.currentVersion = null;
    this.loadVersions();
    this.loadObjects();
  }

  onVersionSelected(event) {
    this.component.currentVersion = event.detail.version;
    this.loadObjects();
  }

  onVersionSaved(event) {
    // Handle version save
    console.log('Version saved:', event.detail);
    this.loadVersions();
  }

  onVersionDeleted(event) {
    // Handle version deletion
    console.log('Version deleted:', event.detail);
    this.component.currentVersion = null;
    this.loadVersions();
    this.loadObjects();
  }

  onFurnaceAdded(event) {
    // Handle furnace added
    console.log('Furnace added:', event.detail);
    this.loadObjects();
  }

  onFurnaceUpdated(event) {
    // Handle furnace updated
    console.log('Furnace updated:', event.detail);
    this.loadObjects();
  }

  onFurnaceDeleted(event) {
    // Handle furnace deleted
    console.log('Furnace deleted:', event.detail);
    this.loadObjects();
  }

  onFurnacesUploaded(event) {
    // Handle furnaces uploaded
    console.log('Furnaces uploaded:', event.detail);
    this.loadObjects();
  }

  onTrapAdded(event) {
    // Handle trap added
    console.log('Trap added:', event.detail);
    this.loadObjects();
  }

  onTrapDeleted(event) {
    // Handle trap deleted
    console.log('Trap deleted:', event.detail);
    this.loadObjects();
  }

  onObjectAdded(event) {
    // Handle object added
    console.log('Object added:', event.detail);
    this.loadObjects();
  }

  onObjectDeleted(event) {
    // Handle object deleted
    console.log('Object deleted:', event.detail);
    this.loadObjects();
  }

  onPriorityChanged(event) {
    // Handle priority changed
    console.log('Priority changed:', event.detail);
    
    // Store the current priority settings for later use
    this.currentPrioritySettings = event.detail;
  }

  onGenerateMap() {
    // Handle map generation
    console.log('Generate map');
  }

  onDownloadCSV() {
    // Handle CSV download
    console.log('Download CSV');
  }

  onDownloadSVG() {
    // Handle SVG download
    console.log('Download SVG');
  }

  onDownloadPNG() {
    // Handle PNG download
    console.log('Download PNG');
  }

  onResetData() {
    // Handle data reset
    console.log('Reset data');
    this.clearMapData();
  }

  onGearSaved(event) {
    // Handle gear saved
    console.log('Gear saved:', event.detail);
    this.loadObjects();
  }
} 