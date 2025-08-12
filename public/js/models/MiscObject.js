import { MapObject } from './MapObject.js';

export class MiscObject extends MapObject {
  constructor(x, y, size, name = '', id = null) {
    if (size < 1) {
      throw new Error('Size must be at least 1');
    }
    
    super(x, y, size, id);
    this.name = name;
  }

  getName() {
    return this.name;
  }

  setName(name) {
    this.name = name;
  }

  getType() {
    return 'misc';
  }

  toArray() {
    return {
      ...super.toArray(),
      name: this.name
    };
  }
} 