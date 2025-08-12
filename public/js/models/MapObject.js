export class MapObject {
  constructor(x, y, size, id = null) {
    this.x = x;
    this.y = y;
    this.size = size;
    this.id = id || this.generateId();
  }

  getX() { return this.x; }
  getY() { return this.y; }
  getSize() { return this.size; }
  getId() { return this.id; }

  setPosition(x, y) {
    this.x = x;
    this.y = y;
  }

  getPosition() {
    return { x: this.x, y: this.y };
  }

  getOccupiedCoordinates() {
    const coordinates = [];
    for (let dx = 0; dx < this.size; dx++) {
      for (let dy = 0; dy < this.size; dy++) {
        coordinates.push({
          x: this.x + dx,
          y: this.y + dy
        });
      }
    }
    return coordinates;
  }

  toArray() {
    return {
      id: this.id,
      x: this.x,
      y: this.y,
      size: this.size,
      type: this.getType()
    };
  }

  getType() {
    throw new Error('getType() must be implemented by subclass');
  }

  generateId() {
    return `${this.getType()}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }
} 