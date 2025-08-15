import { MapObject } from './MapObject.js';

export class Trap extends MapObject {
  constructor(x, y, id = null) {
    super(x, y, 1, id);
  }

  getType() {
    return 'trap';
  }

  toArray() {
    return {
      ...super.toArray()
    };
  }
} 