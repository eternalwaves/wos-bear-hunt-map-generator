// MiscObjectForm Logic
export class MiscObjectFormLogic {
  constructor(component) {
    this.component = component;
  }

  onNameChange(event) {
    this.component.name = event.target.value;
  }

  onXChange(event) {
    this.component.x = parseInt(event.target.value) || null;
  }

  onYChange(event) {
    this.component.y = parseInt(event.target.value) || null;
  }

  onSizeChange(event) {
    this.component.size = parseInt(event.target.value) || 1;
  }

  onSubmit(event) {
    event.preventDefault();
    
    if (this.component.x === null || this.component.y === null) {
      alert('Please enter both X and Y coordinates');
      return;
    }

    if (this.component.size < 1 || this.component.size > 10) {
      alert('Size must be between 1 and 10');
      return;
    }

    this.component.dispatchEvent(new CustomEvent('misc-object-submitted', {
      detail: {
        name: this.component.name,
        x: this.component.x,
        y: this.component.y,
        size: this.component.size
      }
    }));

    // Reset form
    this.component.name = '';
    this.component.x = null;
    this.component.y = null;
    this.component.size = 1;
  }

  onCancel() {
    this.component.dispatchEvent(new CustomEvent('form-cancelled'));
  }
}
