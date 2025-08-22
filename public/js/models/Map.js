import { Furnace } from './Furnace.js';
import { Trap } from './Trap.js';
import { MiscObject } from './MiscObject.js';
import { FurnaceCollection } from './FurnaceCollection.js';

export class Map {
  constructor(name, version = '1.0', id = null, cellSize = 50, weightedCriteria = null) {
    this.id = id || this.generateId();
    this.name = name;
    this.version = version;
    this.cellSize = cellSize;
    this.weightedCriteria = weightedCriteria;
    this.traps = [];
    this.miscObjects = [];
    this.furnaceCollection = new FurnaceCollection();
    this.occupiedPositions = new Set();
  }

  generateId() {
    return `map_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }

  // Getters
  getId() { return this.id; }
  getName() { return this.name; }
  getVersion() { return this.version; }
  getCellSize() { return this.cellSize; }
  getWeightedCriteria() { return this.weightedCriteria; }
  getTraps() { return this.traps; }
  getMiscObjects() { return this.miscObjects; }
  getFurnaces() { return this.furnaceCollection.getAll(); }
  getOccupiedPositions() { return this.occupiedPositions; }

  // Setters
  setName(name) { this.name = name; }
  setVersion(version) { this.version = version; }
  setCellSize(cellSize) { this.cellSize = cellSize; }
  setWeightedCriteria(weightedCriteria) { this.weightedCriteria = weightedCriteria; }

  // Object management
  addTrap(trap) {
    if (this.canPlaceObject(trap)) {
      this.traps.push(trap);
      this.markPositionsAsOccupied(trap);
      return true;
    }
    return false;
  }

  addMiscObject(miscObject) {
    if (this.canPlaceObject(miscObject)) {
      this.miscObjects.push(miscObject);
      this.markPositionsAsOccupied(miscObject);
      return true;
    }
    return false;
  }

  addFurnace(furnace) {
    if (this.canPlaceObject(furnace)) {
      this.furnaceCollection.add(furnace);
      this.markPositionsAsOccupied(furnace);
      return true;
    }
    return false;
  }

  removeTrap(trapId) {
    const trap = this.traps.find(t => t.getId() === trapId);
    if (trap) {
      this.traps = this.traps.filter(t => t.getId() !== trapId);
      this.markPositionsAsUnoccupied(trap);
      return true;
    }
    return false;
  }

  removeMiscObject(objectId) {
    const object = this.miscObjects.find(o => o.getId() === objectId);
    if (object) {
      this.miscObjects = this.miscObjects.filter(o => o.getId() !== objectId);
      this.markPositionsAsUnoccupied(object);
      return true;
    }
    return false;
  }

  removeFurnace(furnaceId) {
    const furnace = this.furnaceCollection.find(furnaceId);
    if (furnace) {
      this.furnaceCollection.remove(furnaceId);
      this.markPositionsAsUnoccupied(furnace);
      return true;
    }
    return false;
  }

  canPlaceObject(object) {
    const coordinates = object.getOccupiedCoordinates();
    for (const coord of coordinates) {
      const key = `${coord.x},${coord.y}`;
      if (this.occupiedPositions.has(key)) {
        return false;
      }
    }
    return true;
  }

  markPositionsAsOccupied(object) {
    const coordinates = object.getOccupiedCoordinates();
    for (const coord of coordinates) {
      this.occupiedPositions.add(`${coord.x},${coord.y}`);
    }
  }

  markPositionsAsUnoccupied(object) {
    const coordinates = object.getOccupiedCoordinates();
    for (const coord of coordinates) {
      this.occupiedPositions.delete(`${coord.x},${coord.y}`);
    }
  }

  getAllObjects() {
    return [
      ...this.traps,
      ...this.miscObjects,
      ...this.furnaceCollection.getAll()
    ];
  }

  getObjectAt(x, y) {
    const allObjects = this.getAllObjects();
    return allObjects.find(obj => {
      const coords = obj.getOccupiedCoordinates();
      return coords.some(coord => coord.x === x && coord.y === y);
    });
  }

  toArray() {
    return {
      id: this.id,
      name: this.name,
      version: this.version,
      cellSize: this.cellSize,
      weightedCriteria: this.weightedCriteria,
      traps: this.traps.map(trap => trap.toArray()),
      miscObjects: this.miscObjects.map(obj => obj.toArray()),
      furnaces: this.furnaceCollection.getAll().map(furnace => furnace.toArray())
    };
  }

  // Static methods for API interaction
  static async getAll() {
    const response = await fetch('/api.php?action=get_all_maps', { cache: 'no-store' });
    const data = await response.json();
    
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to load maps');
    }
    
    return data.data.map(mapData => new Map(
      mapData.name,
      mapData.version,
      mapData.id,
      mapData.cellSize,
      mapData.weightedCriteria
    ));
  }

  static async create(name, cellSize = 50) {
    const response = await fetch('/api.php?action=create_map', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, cellSize })
    });
    
    const data = await response.json();
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to create map');
    }
    
    return new Map(name, '1.0', data.data.id, cellSize);
  }

  async getVersions() {
    const response = await fetch(`/api.php?action=get_versions&map_id=${this.id}`, { cache: 'no-store' });
    const data = await response.json();
    
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to load versions');
    }
    
    return data.data || [];
  }

  async loadObjects(version = null) {
    let url = `/api.php?action=get_objects&map_id=${this.id}`;
    if (version) {
      url += `&version=${version}`;
    }
    
    const response = await fetch(url, { cache: 'no-store' });
    const data = await response.json();
    
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to load objects');
    }
    
    const mapData = data.data;
    
    // Clear existing objects
    this.traps = [];
    this.miscObjects = [];
    this.furnaceCollection.clear(); // Clear furnace collection
    this.occupiedPositions = new Set();
    
    // Load furnaces
    if (mapData.furnaces) {
      const furnaceInstances = mapData.furnaces.map(furnaceData => new Furnace(
        furnaceData.name,
        furnaceData.level,
        furnaceData.power,
        furnaceData.rank,
        furnaceData.participation,
        furnaceData.trap_pref,
        furnaceData.x,
        furnaceData.y,
        furnaceData.id,
        furnaceData.status,
        furnaceData.locked,
        furnaceData.cap_level,
        furnaceData.watch_level,
        furnaceData.vest_level,
        furnaceData.pants_level,
        furnaceData.ring_level,
        furnaceData.cane_level,
        furnaceData.cap_charms,
        furnaceData.watch_charms,
        furnaceData.vest_charms,
        furnaceData.pants_charms,
        furnaceData.ring_charms,
        furnaceData.cane_charms
      ));
      this.furnaceCollection.addMany(furnaceInstances);
    }
    
    // Load traps
    if (mapData.traps) {
      this.traps = mapData.traps.map(trapData => new Trap(trapData.x, trapData.y, trapData.id));
    }
    
    // Load misc objects
    if (mapData.misc) {
      this.miscObjects = mapData.misc.map(objData => new MiscObject(
        objData.name,
        objData.x,
        objData.y,
        objData.size,
        objData.id
      ));
    }
    
    // Update occupied positions
    this.occupiedPositions = new Set(Object.keys(mapData.occupied || {}));
    this.cellSize = mapData.cellSize || 50;
    this.weightedCriteria = mapData.weightedCriteria;
  }

  async getSVG(version = null) {
    let url = `/map.svg?map_id=${this.id}`;
    if (version) {
      url += `&version=${version}`;
    }
    
    const response = await fetch(url, { cache: 'no-store' });
    if (!response.ok) {
      throw new Error('Failed to load SVG');
    }
    
    return await response.text();
  }

  async saveVersion(versionName) {
    const response = await fetch('/api.php?action=save_version', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        map_id: this.id,
        version: versionName
      })
    });
    
    const data = await response.json();
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to save version');
    }
  }

  async deleteVersion(versionName) {
    const response = await fetch('/api.php?action=delete_version', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        map_id: this.id,
        version: versionName
      })
    });
    
    const data = await response.json();
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to delete version');
    }
  }

  async resetData(version = null) {
    let url = `/api.php?action=reset_furnaces&map_id=${this.id}`;
    if (version) {
      url += `&version=${version}`;
    }
    
    const response = await fetch(url, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' }
    });
    
    const data = await response.json();
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to reset data');
    }
  }

  async exportCSV(version = null) {
    let url = `/api.php?action=export_csv&map_id=${this.id}`;
    if (version) {
      url += `&version=${version}`;
    }
    
    window.open(url, '_blank');
  }

  async exportSVG(version = null) {
    let url = `/api.php?action=export_svg&map_id=${this.id}`;
    if (version) {
      url += `&version=${version}`;
    }
    
    window.open(url, '_blank');
  }

  async exportPNG(version = null) {
    let url = `/api.php?action=export_png&map_id=${this.id}`;
    if (version) {
      url += `&version=${version}`;
    }
    
    window.open(url, '_blank');
  }
} 