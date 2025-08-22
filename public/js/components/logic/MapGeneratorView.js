// MapGeneratorView Logic
import { Furnace } from '../../models/Furnace.js';
import { Trap } from '../../models/Trap.js';
import { MiscObject } from '../../models/MiscObject.js';
import { Map } from '../../models/Map.js';

export class MapGeneratorViewLogic {
  constructor(component) {
    this.component = component;
    this.originalSavedData = {};
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
      const maps = await Map.getAll();
      this.component.maps = maps;
      
      // Auto-select first map if only one exists
      if (maps.length === 1) {
        this.component.currentMap = maps[0];
        await this.loadVersions();
        await this.loadObjects();
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
      const versions = await this.component.currentMap.getVersions();
      this.component.versions = versions;
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
      await this.component.currentMap.loadObjects(this.component.currentVersion);
      
      // Store original saved data for each furnace (for change tracking)
      this.originalSavedData = {};
      this.component.currentMap.getFurnaces().forEach(furnace => {
        this.originalSavedData[furnace.id] = {
          name: furnace.name, // Store raw value, not normalized
          level: furnace.level,
          power: furnace.power,
          rank: furnace.rank,
          participation: furnace.participation,
          trapPref: furnace.trapPref,
          x: furnace.x,
          y: furnace.y,
          status: furnace.status,
          locked: furnace.locked,
          capLevel: furnace.capLevel,
          capCharms: furnace.capCharms,
          watchLevel: furnace.watchLevel,
          watchCharms: furnace.watchCharms,
          vestLevel: furnace.vestLevel,
          vestCharms: furnace.vestCharms,
          pantsLevel: furnace.pantsLevel,
          pantsCharms: furnace.pantsCharms,
          ringLevel: furnace.ringLevel,
          ringCharms: furnace.ringCharms,
          caneLevel: furnace.caneLevel,
          caneCharms: furnace.caneCharms
        };
      });

      // Update child components directly
      this.updateChildComponents();
      
      // Apply priority settings if available
      const weightedCriteria = this.component.currentMap.getWeightedCriteria();
      if (weightedCriteria) {
        this.applyPrioritySettings({
          mode: 'weighted',
          weightedCriteria: weightedCriteria
        });
      } else {
        this.applyPrioritySettings({
          mode: 'simple'
        });
      }
      
      // Load SVG map
      await this.loadSVGMap();
    } catch (error) {
      console.error('Failed to load objects:', error);
    }
  }

  async loadSVGMap() {
    if (!this.component.currentMap) return;

    try {
      const svg = await this.component.currentMap.getSVG(this.component.currentVersion);
      this.component.svgContent = svg;
      this.component.mapGenerated = true;
    } catch (error) {
      console.error('Failed to load SVG map:', error);
    }
  }

  updateChildComponents() {
    // Directly update component properties from the Map model
    this.component.furnaces = this.component.currentMap.getFurnaces();
    this.component.traps = this.component.currentMap.getTraps();
    this.component.miscObjects = this.component.currentMap.getMiscObjects();
    this.component.hasData = this.component.currentMap.getFurnaces().length > 0 || 
                            this.component.currentMap.getTraps().length > 0 || 
                            this.component.currentMap.getMiscObjects().length > 0;
    
    // Force a re-render of the MapDisplayView to update unsaved classes
    this.component.requestUpdate();
  }

  // Helper function to normalize values for comparison
  normalizeForComparison(value) {
    // Treat null and empty string as equivalent
    if (value === null || value === '') {
      return '';
    }
    
    return value;
  }

  // Helper function to normalize numeric values for comparison
  normalizeNumericForComparison(value) {
    // Treat null, empty string, NaN and 0 as equivalent for numeric fields
    if (value === null || value === '' || isNaN(value) || value === 0) {
      return 0;
    }
    
    return value;
  }

  hasUnsavedChanges(furnace) {
    
    if (!furnace || !furnace.id || !this.originalSavedData[furnace.id]) {
      return false;
    }

    const original = this.originalSavedData[furnace.id];
    
    // Compare string properties (name, level, rank, participation, trapPref, status)
    const nameChanged = this.normalizeForComparison(furnace.name) !== this.normalizeForComparison(original.name);
    const levelChanged = this.normalizeForComparison(furnace.level) !== this.normalizeForComparison(original.level);
    const rankChanged = this.normalizeForComparison(furnace.rank) !== this.normalizeForComparison(original.rank);
    const participationChanged = this.normalizeForComparison(furnace.participation) !== this.normalizeForComparison(original.participation);
    const trapPrefChanged = this.normalizeForComparison(furnace.trapPref) !== this.normalizeForComparison(original.trapPref);
    const statusChanged = this.normalizeForComparison(furnace.status) !== this.normalizeForComparison(original.status);
    
    // Compare numeric properties (power, x, y)
    const powerChanged = this.normalizeNumericForComparison(furnace.power) !== this.normalizeNumericForComparison(original.power);
    const xChanged = this.normalizeNumericForComparison(furnace.x) !== this.normalizeNumericForComparison(original.x);
    const yChanged = this.normalizeNumericForComparison(furnace.y) !== this.normalizeNumericForComparison(original.y);
    
    const hasChanges = (
      nameChanged ||
      levelChanged ||
      powerChanged ||
      rankChanged ||
      participationChanged ||
      trapPrefChanged ||
      xChanged ||
      yChanged ||
      statusChanged ||
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
      // Update the original saved data to match current values (raw, not normalized)
      this.originalSavedData[furnace.id] = {
        name: furnace.name,
        level: furnace.level,
        power: furnace.power,
        rank: furnace.rank,
        participation: furnace.participation,
        trapPref: furnace.trapPref,
        x: furnace.x,
        y: furnace.y,
        status: furnace.status,
        locked: furnace.locked,
        capLevel: furnace.capLevel,
        capCharms: furnace.capCharms,
        watchLevel: furnace.watchLevel,
        watchCharms: furnace.watchCharms,
        vestLevel: furnace.vestLevel,
        vestCharms: furnace.vestCharms,
        pantsLevel: furnace.pantsLevel,
        pantsCharms: furnace.pantsCharms,
        ringLevel: furnace.ringLevel,
        ringCharms: furnace.ringCharms,
        caneLevel: furnace.caneLevel,
        caneCharms: furnace.caneCharms
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
    this.component.currentMap = null;
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

  async onVersionSaved(event) {
    // Handle version save
    console.log('Version saved:', event.detail);
    try {
      await this.component.currentMap.saveVersion(event.detail.version);
      this.loadVersions();
    } catch (error) {
      console.error('Failed to save version:', error);
      alert('Error: ' + error.message);
    }
  }

  async onVersionDeleted(event) {
    // Handle version deletion
    console.log('Version deleted:', event.detail);
    try {
      await this.component.currentMap.deleteVersion(event.detail.version);
      this.component.currentVersion = null;
      this.loadVersions();
      this.loadObjects();
    } catch (error) {
      console.error('Failed to delete version:', error);
      alert('Error: ' + error.message);
    }
  }

  async onFurnaceAdded(event) {
    // Handle furnace added
    console.log('Furnace added:', event.detail);
    try {
      const furnace = new Furnace(
        event.detail.name, event.detail.level, event.detail.power, event.detail.rank, event.detail.participation, event.detail.trap_pref,
        event.detail.x, event.detail.y, null, event.detail.status, event.detail.locked,
        event.detail.cap_level, event.detail.watch_level, event.detail.vest_level, event.detail.pants_level, event.detail.ring_level, event.detail.cane_level,
        event.detail.cap_charms, event.detail.watch_charms, event.detail.vest_charms, event.detail.pants_charms, event.detail.ring_charms, event.detail.cane_charms
      );
      
      await furnace.save(this.component.currentMap.id, this.component.currentVersion);
      this.loadObjects();
    } catch (error) {
      console.error('Failed to add furnace:', error);
      alert('Error: ' + error.message);
    }
  }

  async onFurnaceUpdated(event) {
    // Handle furnace updated
    console.log('Furnace updated:', event.detail);
    try {
      // The event.detail contains the furnace object directly
      const furnace = event.detail;
      if (furnace && furnace.id) {
        await furnace.save(this.component.currentMap.id, this.component.currentVersion);
        this.loadObjects();
      }
    } catch (error) {
      console.error('Failed to update furnace:', error);
      alert('Error: ' + error.message);
    }
  }

  async onFurnaceDeleted(event) {
    // Handle furnace deleted
    console.log('Furnace deleted:', event.detail);
    try {
      const furnace = this.component.currentMap.furnaceCollection.find(event.detail.furnace_id);
      if (furnace) {
        await furnace.delete(this.component.currentMap.id, this.component.currentVersion);
        this.loadObjects();
      }
    } catch (error) {
      console.error('Failed to delete furnace:', error);
      alert('Error: ' + error.message);
    }
  }

  async onFurnacesUploaded(event) {
    // Handle furnaces uploaded
    console.log('Furnaces uploaded:', event.detail);
    this.loadObjects();
  }

  async onTrapAdded(event) {
    // Handle trap added
    console.log('Trap added:', event.detail);
    try {
      const trap = new Trap(event.detail.x, event.detail.y);
      await trap.save(this.component.currentMap.id, this.component.currentVersion);
      this.loadObjects();
    } catch (error) {
      console.error('Failed to add trap:', error);
      alert('Error: ' + error.message);
    }
  }

  async onTrapDeleted(event) {
    // Handle trap deleted
    console.log('Trap deleted:', event.detail);
    try {
      const trap = this.component.currentMap.getTraps().find(t => t.id === event.detail.trap_id);
      if (trap) {
        await trap.delete(this.component.currentMap.id, this.component.currentVersion);
        this.loadObjects();
      }
    } catch (error) {
      console.error('Failed to delete trap:', error);
      alert('Error: ' + error.message);
    }
  }

  async onObjectAdded(event) {
    // Handle object added
    console.log('Object added:', event.detail);
    try {
      const miscObject = new MiscObject(event.detail.name, event.detail.x, event.detail.y, event.detail.size);
      await miscObject.save(this.component.currentMap.id, this.component.currentVersion);
      this.loadObjects();
    } catch (error) {
      console.error('Failed to add object:', error);
      alert('Error: ' + error.message);
    }
  }

  async onObjectDeleted(event) {
    // Handle object deleted
    console.log('Object deleted:', event.detail);
    try {
      const miscObject = this.component.currentMap.getMiscObjects().find(o => o.id === event.detail.object_id);
      if (miscObject) {
        await miscObject.delete(this.component.currentMap.id, this.component.currentVersion);
        this.loadObjects();
      }
    } catch (error) {
      console.error('Failed to delete object:', error);
      alert('Error: ' + error.message);
    }
  }

  onPriorityChanged(event) {
    // Handle priority changed
    console.log('Priority changed:', event.detail);
    
    // Store the current priority settings for later use
    this.currentPrioritySettings = event.detail;
  }

  async onGenerateMap() {
    // Handle map generation
    console.log('Generate map');
    try {
      await this.component.currentMap.furnaceCollection.generateMap(
        this.component.currentMap.id, 
        this.component.currentVersion, 
        this.currentPrioritySettings
      );
      this.loadObjects();
    } catch (error) {
      console.error('Failed to generate map:', error);
      alert('Error: ' + error.message);
    }
  }

  async onDownloadCSV() {
    // Handle CSV download
    console.log('Download CSV');
    try {
      await this.component.currentMap.exportCSV(this.component.currentVersion);
    } catch (error) {
      console.error('Failed to download CSV:', error);
      alert('Error: ' + error.message);
    }
  }

  async onDownloadSVG() {
    // Handle SVG download
    console.log('Download SVG');
    try {
      await this.component.currentMap.exportSVG(this.component.currentVersion);
    } catch (error) {
      console.error('Failed to download SVG:', error);
      alert('Error: ' + error.message);
    }
  }

  async onDownloadPNG() {
    // Handle PNG download
    console.log('Download PNG');
    try {
      await this.component.currentMap.exportPNG(this.component.currentVersion);
    } catch (error) {
      console.error('Failed to download PNG:', error);
      alert('Error: ' + error.message);
    }
  }

  async onResetData() {
    // Handle data reset
    console.log('Reset data');
    try {
      await this.component.currentMap.resetData(this.component.currentVersion);
      this.clearMapData();
    } catch (error) {
      console.error('Failed to reset data:', error);
      alert('Error: ' + error.message);
    }
  }

  async onGearSaved(event) {
    // Handle gear saved
    console.log('Gear saved:', event.detail);
    try {
      const furnace = this.component.currentMap.furnaceCollection.find(event.detail.furnace_id);
      if (furnace) {
        await furnace.save(this.component.currentMap.id, this.component.currentVersion);
        this.loadObjects();
      }
    } catch (error) {
      console.error('Failed to save gear:', error);
      alert('Error: ' + error.message);
    }
  }

  // Helper methods for accessing current map and version
  getCurrentMapId() {
    return this.component.currentMap?.id;
  }

  getCurrentVersion() {
    return this.component.currentVersion;
  }

  async onFurnaceStatusUpdated(furnace) {
    // Handle furnace status updated - regenerate SVG and reload objects
    console.log('Furnace status updated:', furnace);
    try {
      // Regenerate SVG
      const svg = await this.component.currentMap.getSVG(this.component.currentVersion);
      this.component.svgContent = svg;
      
      // Reload objects to refresh the display
      await this.loadObjects();
      
      // Update buttons/UI state
      this.updateButtons();
    } catch (error) {
      console.error('Failed to update furnace status:', error);
      throw error;
    }
  }

  updateButtons() {
    // Update button states based on current data
    // This is a placeholder for any button state updates needed
    console.log('Updating button states');
  }
} 