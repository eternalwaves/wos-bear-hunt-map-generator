import { Furnace } from './Furnace.js';

export class FurnaceCollection {
  constructor() {
    this.furnaces = [];
  }

  add(furnace) {
    if (furnace instanceof Furnace) {
      this.furnaces.push(furnace);
    } else {
      // Convert raw data to Furnace instance
      this.furnaces.push(new Furnace(
        furnace.name, furnace.level, furnace.power, furnace.rank, furnace.participation, furnace.trap_pref,
        furnace.x, furnace.y, furnace.id, furnace.status, furnace.locked,
        furnace.cap_level, furnace.watch_level, furnace.vest_level, furnace.pants_level, furnace.ring_level, furnace.cane_level,
        furnace.cap_charms, furnace.watch_charms, furnace.vest_charms, furnace.pants_charms, furnace.ring_charms, furnace.cane_charms
      ));
    }
  }

  remove(furnaceId) {
    const index = this.furnaces.findIndex(f => f.id === furnaceId);
    if (index !== -1) {
      this.furnaces.splice(index, 1);
    }
  }

  find(furnaceId) {
    return this.furnaces.find(f => f.id === furnaceId);
  }

  getAll() {
    return this.furnaces;
  }

  clear() {
    this.furnaces = [];
  }

  // Bulk API Methods
  async saveAll(mapId, version = null) {
    const updates = this.furnaces.map(furnace => {
      const gearData = {};
      const gearTypes = ['cap', 'watch', 'vest', 'pants', 'ring', 'cane'];
      gearTypes.forEach(gearType => {
        gearData[`${gearType}_level`] = furnace[`${gearType}Level`] || '';
        gearData[`${gearType}_charms`] = furnace[`${gearType}Charms`] || '';
      });

      return {
        id: furnace.id,
        name: furnace.name,
        level: furnace.level,
        power: furnace.power,
        rank: furnace.rank,
        participation: furnace.participation,
        trap_pref: furnace.trapPref,
        x: furnace.x,
        y: furnace.y,
        status: furnace.status,
        locked: furnace.locked,
        ...gearData
      };
    });

    const formData = new FormData();
    formData.append('furnace_updates', JSON.stringify(updates));
    formData.append('map_id', mapId);
    
    if (version) {
      formData.append('version', version);
    }

    const response = await fetch('/api.php?action=update_all_furnaces', {
      method: 'PUT',
      cache: 'no-store',
      body: formData
    });

    const data = await response.json();
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to save all furnaces');
    }

    return data;
  }

  async resetAll(mapId, version = null) {
    const formData = new FormData();
    formData.append('map_id', mapId);
    
    if (version) {
      formData.append('version', version);
    }

    const response = await fetch('/api.php?action=reset_furnaces', {
      method: 'PUT',
      cache: 'no-store',
      body: formData
    });

    const data = await response.json();
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to reset furnaces');
    }

    return data;
  }

  async generateMap(mapId, version = null, prioritySettings = null) {
    const formData = new FormData();
    formData.append('map_id', mapId);
    
    if (version) {
      formData.append('version', version);
    }

    if (prioritySettings) {
      if (prioritySettings.mode === 'simple') {
        formData.append('sort_priority', prioritySettings.simpleOrder.join(','));
      } else if (prioritySettings.mode === 'weighted') {
        formData.append('criteria_weights', JSON.stringify(prioritySettings.weightedCriteria));
      }
    }

    const response = await fetch('/api.php?action=generate_map', {
      method: 'POST',
      cache: 'no-store',
      body: formData
    });

    const data = await response.json();
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to generate map');
    }

    return data;
  }

  // Utility methods
  getUnsavedFurnaces() {
    return this.furnaces.filter(furnace => furnace.hasUnsavedChanges && furnace.hasUnsavedChanges());
  }

  hasUnsavedChanges() {
    return this.furnaces.some(furnace => furnace.hasUnsavedChanges && furnace.hasUnsavedChanges());
  }

  markAllAsSaved() {
    this.furnaces.forEach(furnace => {
      if (furnace.markAsSaved) {
        furnace.markAsSaved();
      }
    });
  }
}

