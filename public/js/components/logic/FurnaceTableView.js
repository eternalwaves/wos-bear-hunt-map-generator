// FurnaceTableView Logic
import { Furnace } from '../../models/Furnace.js';

export class FurnaceTableViewLogic {
  constructor(component) {
    this.component = component;
  }

  updateFurnacesFromMap() {
    // This method is called when the furnaces property changes
    // The furnaces are already set on the component
    // No additional processing needed since we're using the furnaces array directly
  }

  toggleForm() {
    this.component.showForm = !this.component.showForm;
  }

  onFurnaceSubmitted(event) {
    // Handle furnace form submission
    console.log('Furnace submitted:', event.detail);
    this.component.showForm = false;
    
    // Dispatch event to parent
    this.component.dispatchEvent(new CustomEvent('furnace-added', {
      detail: event.detail
    }));
  }

  onFormCancelled() {
    this.component.showForm = false;
  }

  // Row-level methods
  onGearClick(event, furnace) {
    // Handle individual gear cell clicks
    this.component.dispatchEvent(new CustomEvent('gear-edit', {
      detail: furnace
    }));
  }

  onSave(furnace) {
    this.component.dispatchEvent(new CustomEvent('furnace-save', {
      detail: furnace
    }));
  }

  onEdit(furnace) {
    this.component.dispatchEvent(new CustomEvent('furnace-edit', {
      detail: furnace
    }));
  }

  onDelete(furnace) {
    if (confirm(`Are you sure you want to delete furnace "${furnace.name}"?`)) {
      this.component.dispatchEvent(new CustomEvent('furnace-deleted', {
        detail: furnace
      }));
    }
  }

  onShift(dx, dy, furnace) {
    this.component.dispatchEvent(new CustomEvent('furnace-shift', {
      detail: { furnace, dx, dy }
    }));
  }
} 