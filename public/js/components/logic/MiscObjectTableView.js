// MiscObjectTableView Logic
import { MiscObject } from '../../models/MiscObject.js';

export class MiscObjectTableViewLogic {
  constructor(component) {
    this.component = component;
  }

  updateMiscObjectsFromMap() {
    // This method is called when the miscObjects property changes
    // The misc objects are already set on the component
    // No additional processing needed since we're using the miscObjects array directly
  }

  toggleForm() {
    this.component.showForm = !this.component.showForm;
  }

  onMiscObjectSubmitted(event) {
    // Handle misc object form submission
    console.log('Misc object submitted:', event.detail);
    this.component.showForm = false;
    
    // Dispatch event to parent
    this.component.dispatchEvent(new CustomEvent('misc-object-added', {
      detail: event.detail
    }));
  }

  onFormCancelled() {
    this.component.showForm = false;
  }

  onMiscObjectUpdated(event) {
    // Handle misc object update
    console.log('Misc object updated:', event.detail);
    
    // Dispatch event to parent
    this.component.dispatchEvent(new CustomEvent('misc-object-updated', {
      detail: event.detail
    }));
  }

  onMiscObjectDeleted(event) {
    // Handle misc object deletion
    console.log('Misc object deleted:', event.detail);
    
    // Dispatch event to parent
    this.component.dispatchEvent(new CustomEvent('misc-object-deleted', {
      detail: event.detail
    }));
  }

  // Row-level methods
  onEdit(object) {
    this.component.dispatchEvent(new CustomEvent('misc-object-edit', {
      detail: object
    }));
  }

  onDelete(object) {
    if (confirm(`Are you sure you want to delete this object?`)) {
      this.component.dispatchEvent(new CustomEvent('misc-object-deleted', {
        detail: object
      }));
    }
  }
}
