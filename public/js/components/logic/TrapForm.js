// TrapForm Logic
export class TrapFormLogic {
  constructor(component) {
    this.component = component;
  }

  onXChange(event) {
    this.component.x = parseInt(event.target.value) || null;
  }

  onYChange(event) {
    this.component.y = parseInt(event.target.value) || null;
  }

  onSubmit(event) {
    event.preventDefault();
    
    if (this.component.x === null || this.component.y === null) {
      alert('Please enter both X and Y coordinates');
      return;
    }

    this.component.dispatchEvent(new CustomEvent('trap-submitted', {
      detail: {
        x: this.component.x,
        y: this.component.y
      }
    }));

    // Reset form
    this.component.x = null;
    this.component.y = null;
  }

  onCancel() {
    this.component.dispatchEvent(new CustomEvent('form-cancelled'));
  }
}
