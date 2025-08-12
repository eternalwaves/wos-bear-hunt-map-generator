// MiscObjectTableRow Logic
export class MiscObjectTableRowLogic {
  constructor(component) {
    this.component = component;
  }

  onEdit() {
    this.component.dispatchEvent(new CustomEvent('misc-object-edit', {
      detail: this.component.miscObject
    }));
  }

  onDelete() {
    if (confirm(`Are you sure you want to delete "${this.component.miscObject.getName() || 'this object'}"?`)) {
      this.component.dispatchEvent(new CustomEvent('misc-object-delete', {
        detail: this.component.miscObject
      }));
    }
  }
}
