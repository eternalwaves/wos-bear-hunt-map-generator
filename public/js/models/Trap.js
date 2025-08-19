import { MapObject } from './MapObject.js';

export class Trap extends MapObject {
  constructor(x, y, id = null) {
    super(x, y, 1, id);
  }

  getType() {
    return 'trap';
  }

  // API Methods
  async save(mapId, version = null) {
    const formData = new FormData();
    formData.append('map_id', mapId);
    formData.append('x', this.x);
    formData.append('y', this.y);
    
    if (version) {
      formData.append('version', version);
    }

    const response = await fetch('/api.php?action=add_trap', {
      method: 'POST',
      cache: 'no-store',
      body: formData
    });

    const data = await response.json();
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to save trap');
    }

    return data;
  }

  async delete(mapId, version = null) {
    const formData = new FormData();
    formData.append('map_id', mapId);
    formData.append('trap_id', this.id);
    
    if (version) {
      formData.append('version', version);
    }

    const response = await fetch('/api.php?action=delete_trap', {
      method: 'DELETE',
      cache: 'no-store',
      body: formData
    });

    const data = await response.json();
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to delete trap');
    }

    return data;
  }
} 