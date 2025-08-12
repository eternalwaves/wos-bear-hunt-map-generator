// FurnaceTableRow Logic
export class FurnaceTableRowLogic {
  constructor(component) {
    this.component = component;
  }

  getStatusClass() {
    if (this.component.furnace.isLocked()) {
      return 'status-locked';
    }
    return this.component.furnace.hasPosition() ? 'status-placed' : 'status-unplaced';
  }

  getStatusText() {
    if (this.component.furnace.isLocked()) {
      return 'Locked';
    }
    return this.component.furnace.hasPosition() ? 'Placed' : 'Unplaced';
  }

  onGearClick(event) {
    // Handle individual gear cell clicks
    const { gearType, furnaceId } = event.detail;
    this.component.dispatchEvent(new CustomEvent('gear-cell-click', {
      detail: { gearType, furnaceId, furnace: this.component.furnace }
    }));
  }

  onGearEdit() {
    this.component.dispatchEvent(new CustomEvent('gear-edit', {
      detail: this.component.furnace
    }));
  }

  onEdit() {
    this.component.dispatchEvent(new CustomEvent('furnace-edit', {
      detail: this.component.furnace
    }));
  }

  onDelete() {
    if (confirm(`Are you sure you want to delete furnace "${this.component.furnace.getName()}"?`)) {
      this.component.dispatchEvent(new CustomEvent('furnace-deleted', {
        detail: this.component.furnace
      }));
    }
  }
}
