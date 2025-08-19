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

  // API Methods
  async save(mapId, version = null) {
    const formData = new FormData();
    formData.append('map_id', mapId);
    formData.append('x', this.x);
    formData.append('y', this.y);
    formData.append('size', this.size);
    formData.append('name', this.name || '');
    
    if (version) {
      formData.append('version', version);
    }

    const response = await fetch('/api.php?action=add_object', {
      method: 'POST',
      cache: 'no-store',
      body: formData
    });

    const data = await response.json();
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to save object');
    }

    return data;
  }

  async delete(mapId, version = null) {
    const formData = new FormData();
    formData.append('map_id', mapId);
    formData.append('object_id', this.id);
    
    if (version) {
      formData.append('version', version);
    }

    const response = await fetch('/api.php?action=delete_object', {
      method: 'DELETE',
      cache: 'no-store',
      body: formData
    });

    const data = await response.json();
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to delete object');
    }

    return data;
  }

  toArray() {
    return {
      ...super.toArray(),
      name: this.name
    };
  }
} 