import { Furnace } from './Furnace.js';
import { Trap } from './Trap.js';
import { MiscObject } from './MiscObject.js';

export class Map {
  constructor(name, version = '1.0', id = null, cellSize = 50, weightedCriteria = null) {
    this.id = id || this.generateId();
    this.name = name;
    this.version = version;
    this.cellSize = cellSize;
    this.weightedCriteria = weightedCriteria;
    this.traps = [];
    this.miscObjects = [];
    this.furnaces = [];
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
  getFurnaces() { return this.furnaces; }
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
      this.furnaces.push(furnace);
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
    const furnace = this.furnaces.find(f => f.getId() === furnaceId);
    if (furnace) {
      this.furnaces = this.furnaces.filter(f => f.getId() !== furnaceId);
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
      ...this.furnaces
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
      furnaces: this.furnaces.map(furnace => furnace.toArray())
    };
  }
} 