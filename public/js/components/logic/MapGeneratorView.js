// MapGeneratorView Logic
export class MapGeneratorViewLogic {
  constructor(component) {
    this.component = component;
  }

  async loadMaps() {
    this.component.loading = true;
    try {
      const response = await fetch('/api.php?action=get_all_maps');
      const data = await response.json();
      this.component.maps = data.maps || [];
    } catch (error) {
      console.error('Failed to load maps:', error);
    } finally {
      this.component.loading = false;
    }
  }

  onMapSelected(event) {
    this.component.currentMap = event.detail.map;
    this.component.currentVersion = null;
  }

  onVersionSelected(event) {
    this.component.currentVersion = event.detail.version;
  }

  onFurnaceAdded(event) {
    // Handle furnace added
    console.log('Furnace added:', event.detail);
  }

  onFurnaceUpdated(event) {
    // Handle furnace updated
    console.log('Furnace updated:', event.detail);
  }

  onFurnaceDeleted(event) {
    // Handle furnace deleted
    console.log('Furnace deleted:', event.detail);
  }

  onTrapAdded(event) {
    // Handle trap added
    console.log('Trap added:', event.detail);
  }

  onTrapDeleted(event) {
    // Handle trap deleted
    console.log('Trap deleted:', event.detail);
  }

  onObjectAdded(event) {
    // Handle misc object added
    console.log('Object added:', event.detail);
  }

  onObjectDeleted(event) {
    // Handle misc object deleted
    console.log('Object deleted:', event.detail);
  }

  onGearSaved(event) {
    // Handle gear saved
    console.log('Gear saved:', event.detail);
  }
} 