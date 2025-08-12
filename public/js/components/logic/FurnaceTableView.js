// FurnaceTableView Logic
import { Furnace } from '../../models/Furnace.js';

export class FurnaceTableViewLogic {
  constructor(component) {
    this.component = component;
  }

  toggleGearColumns() {
    this.component.showGearColumns = !this.component.showGearColumns;
  }

  toggleForm() {
    this.component.showForm = !this.component.showForm;
  }

  async onFurnaceSubmitted(event) {
    const furnaceData = event.detail;
    
    try {
      const response = await fetch('/api.php?action=add_furnace', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          map_id: this.component.map.getId(),
          ...furnaceData
        })
      });

      if (response.ok) {
        const result = await response.json();
        const furnace = new Furnace(
          furnaceData.name,
          furnaceData.level,
          furnaceData.power,
          furnaceData.rank,
          furnaceData.participation,
          furnaceData.trap_pref,
          furnaceData.x,
          furnaceData.y,
          result.furnace_id,
          '',
          false,
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
        );

        this.component.map.addFurnace(furnace);
        this.component.furnaces = this.component.map.getFurnaces();
        this.component.showForm = false;

        this.component.dispatchEvent(new CustomEvent('furnace-added', {
          detail: furnace
        }));
      } else {
        throw new Error('Failed to add furnace');
      }
    } catch (error) {
      console.error('Error adding furnace:', error);
      // Handle error (show notification, etc.)
    }
  }

  onFormCancelled() {
    this.component.showForm = false;
  }

  async onFurnaceUpdated(event) {
    const { furnace, updates } = event.detail;
    
    try {
      const response = await fetch('/api.php?action=update_furnace', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          furnace_id: furnace.getId(),
          ...updates
        })
      });

      if (response.ok) {
        // Update the furnace object
        Object.keys(updates).forEach(key => {
          const setterName = `set${key.charAt(0).toUpperCase() + key.slice(1)}`;
          if (furnace[setterName]) {
            furnace[setterName](updates[key]);
          }
        });

        this.component.requestUpdate();

        this.component.dispatchEvent(new CustomEvent('furnace-updated', {
          detail: { furnace, updates }
        }));
      } else {
        throw new Error('Failed to update furnace');
      }
    } catch (error) {
      console.error('Error updating furnace:', error);
    }
  }

  async onFurnaceDeleted(event) {
    const furnace = event.detail;
    
    try {
      const response = await fetch('/api.php?action=delete_furnace', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          furnace_id: furnace.getId()
        })
      });

      if (response.ok) {
        this.component.map.removeFurnace(furnace.getId());
        this.component.furnaces = this.component.map.getFurnaces();

        this.component.dispatchEvent(new CustomEvent('furnace-deleted', {
          detail: furnace
        }));
      } else {
        throw new Error('Failed to delete furnace');
      }
    } catch (error) {
      console.error('Error deleting furnace:', error);
    }
  }

  onGearEdit(event) {
    const furnace = event.detail;
    this.component.dispatchEvent(new CustomEvent('gear-edit', {
      detail: furnace
    }));
  }

  updateFurnacesFromMap() {
    if (this.component.map) {
      this.component.furnaces = this.component.map.getFurnaces();
    }
  }
} 