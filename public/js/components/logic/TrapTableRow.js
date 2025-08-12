// TrapTableRow Logic
export class TrapTableRowLogic {
  constructor(component) {
    this.component = component;
  }

  onEdit() {
    this.component.dispatchEvent(new CustomEvent('trap-edit', {
      detail: this.component.trap
    }));
  }

  onDelete() {
    if (confirm(`Are you sure you want to delete this trap?`)) {
      this.component.dispatchEvent(new CustomEvent('trap-delete', {
        detail: this.component.trap
      }));
    }
  }
}
