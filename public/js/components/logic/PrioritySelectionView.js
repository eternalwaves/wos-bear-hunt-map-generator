export class PrioritySelectionViewLogic {
  constructor(component) {
    this.component = component;
  }

  onPriorityModeChange(event) {
    this.component.priorityMode = event.target.value;
    this.component.requestUpdate();
  }

  onWeightChange(event) {
    const criteria = event.target.dataset.criteria;
    const value = parseFloat(event.target.value);
    
    this.component.weightedCriteria[criteria] = value;
    
    // Update the display value
    const valueSpan = event.target.parentElement.querySelector('.weight-value');
    if (valueSpan) {
      valueSpan.textContent = value;
    }
    
    // Dispatch event to notify parent
    this.component.dispatchEvent(new CustomEvent('priority-changed', {
      detail: {
        mode: this.component.priorityMode,
        weightedCriteria: this.component.weightedCriteria
      },
      bubbles: true,
      composed: true
    }));
  }
}
