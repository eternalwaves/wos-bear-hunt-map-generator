// TrapTableView Logic
import { Trap } from '../../models/Trap.js';

export class TrapTableViewLogic {
  constructor(component) {
    this.component = component;
  }

  updateTrapsFromMap() {
    // This method is called when the traps property changes
    // The traps are already set on the component
    // No additional processing needed since we're using the traps array directly
  }

  toggleForm() {
    this.component.showForm = !this.component.showForm;
  }

  onTrapSubmitted(event) {
    // Handle trap form submission
    console.log('Trap submitted:', event.detail);
    this.component.showForm = false;
    
    // Dispatch event to parent
    this.component.dispatchEvent(new CustomEvent('trap-added', {
      detail: event.detail
    }));
  }

  onFormCancelled() {
    this.component.showForm = false;
  }

  onTrapUpdated(event) {
    // Handle trap update
    console.log('Trap updated:', event.detail);
    
    // Dispatch event to parent
    this.component.dispatchEvent(new CustomEvent('trap-updated', {
      detail: event.detail
    }));
  }

  onTrapDeleted(event) {
    // Handle trap deletion
    console.log('Trap deleted:', event.detail);
    
    // Dispatch event to parent
    this.component.dispatchEvent(new CustomEvent('trap-deleted', {
      detail: event.detail
    }));
  }

  // Row-level methods
  onEdit(trap) {
    this.component.dispatchEvent(new CustomEvent('trap-edit', {
      detail: trap
    }));
  }

  onDelete(trap) {
    if (confirm(`Are you sure you want to delete this trap?`)) {
      this.component.dispatchEvent(new CustomEvent('trap-deleted', {
        detail: trap
      }));
    }
  }
}
