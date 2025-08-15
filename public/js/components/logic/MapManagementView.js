export class MapManagementViewLogic {
  constructor(component) {
    this.component = component;
  }

  updateMapSelector() {
    // This method can be used to update the map selector display
    // when the maps array changes
  }

  async onCreateMapSubmit(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const mapData = {
      name: formData.get('name'),
      cell_size: formData.get('cell_size') || 50
    };

    try {
      const response = await fetch('/api/maps', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(mapData)
      });

      if (response.ok) {
        const newMap = await response.json();
        this.component.dispatchEvent(new CustomEvent('map-created', {
          detail: { map: newMap },
          bubbles: true,
          composed: true
        }));
        event.target.reset();
      } else {
        console.error('Failed to create map');
      }
    } catch (error) {
      console.error('Error creating map:', error);
    }
  }

  async onMapSelectChange(event) {
    const mapId = event.target.value;
    if (!mapId) {
      this.component.currentMap = null;
      this.component.currentVersion = null;
      this.component.dispatchEvent(new CustomEvent('map-selected', {
        detail: { map: null },
        bubbles: true,
        composed: true
      }));
      return;
    }

    try {
      const response = await fetch(`/api/maps/${mapId}`);
      if (response.ok) {
        const map = await response.json();
        this.component.currentMap = map;
        this.component.currentVersion = null;
        this.component.dispatchEvent(new CustomEvent('map-selected', {
          detail: { map },
          bubbles: true,
          composed: true
        }));
      } else {
        console.error('Failed to load map');
      }
    } catch (error) {
      console.error('Error loading map:', error);
    }
  }

  async onVersionSelectChange(event) {
    const version = event.target.value;
    this.component.currentVersion = version || null;
    
    this.component.dispatchEvent(new CustomEvent('version-selected', {
      detail: { version: this.component.currentVersion },
      bubbles: true,
      composed: true
    }));
  }

  async onSaveVersionSubmit(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const versionName = formData.get('version');

    if (!this.component.currentMap) {
      console.error('No map selected');
      return;
    }

    try {
      const response = await fetch(`/api/maps/${this.component.currentMap.id}/versions`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ version: versionName })
      });

      if (response.ok) {
        const updatedMap = await response.json();
        this.component.currentMap = updatedMap;
        this.component.dispatchEvent(new CustomEvent('version-saved', {
          detail: { version: versionName, map: updatedMap },
          bubbles: true,
          composed: true
        }));
        event.target.reset();
      } else {
        console.error('Failed to save version');
      }
    } catch (error) {
      console.error('Error saving version:', error);
    }
  }

  async onDeleteVersion() {
    if (!this.component.currentMap || !this.component.currentVersion) {
      console.error('No map or version selected');
      return;
    }

    if (!confirm(`Are you sure you want to delete version "${this.component.currentVersion}"?`)) {
      return;
    }

    try {
      const response = await fetch(`/api/maps/${this.component.currentMap.id}/versions/${this.component.currentVersion}`, {
        method: 'DELETE'
      });

      if (response.ok) {
        const updatedMap = await response.json();
        this.component.currentMap = updatedMap;
        this.component.currentVersion = null;
        this.component.dispatchEvent(new CustomEvent('version-deleted', {
          detail: { map: updatedMap },
          bubbles: true,
          composed: true
        }));
      } else {
        console.error('Failed to delete version');
      }
    } catch (error) {
      console.error('Error deleting version:', error);
    }
  }
}
