import { LitElement, html, css } from 'https://esm.sh/lit@2.7.0';
import { PrioritySelectionViewTemplate } from './templates/PrioritySelectionViewTemplate.js';

export class PrioritySelectionView extends LitElement {
  static properties = {
    priorityMode: { type: String },
    simplePriorityOrder: { type: Array },
    weightedCriteria: { type: Array }
  };

  constructor() {
    super();
    this.priorityMode = 'simple';
    this.simplePriorityOrder = ['participation', 'level', 'rank', 'power'];
    this.weightedCriteria = [
      { criteria: 'power', weight: 1.0 },
      { criteria: 'level', weight: 1.0 },
      { criteria: 'rank', weight: 1.0 },
      { criteria: 'participation', weight: 1.0 },
      { criteria: 'chief_gear_and_charms', weight: 1.0 }
    ];
  }

  static styles = css`
    :host {
      display: block;
    }
    
    .priority-selection {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      text-align: left;
    }
    
    .priority-selection h3 {
      margin: 0 0 15px 0;
      color: #333;
    }

    .priority-controls {
      margin-top: 15px;
    }

    .priority-mode {
      margin-bottom: 20px;
    }

    .priority-mode label {
      display: block;
      margin-bottom: 10px;
      cursor: pointer;
    }

    .priority-mode input[type="radio"] {
      margin-right: 8px;
    }

    #simplePriorityMode p {
      margin-bottom: 10px;
      color: #666;
    }

    #sortPriorityList {
      list-style: none;
      padding: 0;
      margin: 0;
      border: 1px solid #ddd;
      border-radius: 4px;
    }

    #sortPriorityList li {
      padding: 10px 15px;
      background: #f8f9fa;
      border-bottom: 1px solid #ddd;
      cursor: move;
      user-select: none;
    }

    #sortPriorityList li:last-child {
      border-bottom: none;
    }

    #sortPriorityList li:hover {
      background: #e9ecef;
    }

    #weightedPriorityMode p {
      margin-bottom: 15px;
      color: #666;
    }

    .criteria-item {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
      gap: 10px;
    }

    .criteria-item label {
      min-width: 120px;
      font-weight: 500;
    }

    .weight-slider {
      flex: 1;
      max-width: 200px;
    }

    .weight-value {
      min-width: 40px;
      text-align: right;
      font-weight: 500;
      color: #007bff;
    }

    .sortable-ghost {
      opacity: 0.5;
      background: #e9ecef !important;
    }
  `;

  render() {
    return PrioritySelectionViewTemplate(this);
  }

  connectedCallback() {
    super.connectedCallback();
    
    // Initialize Sortable for drag-and-drop functionality
    this.updateComplete.then(() => {
      this._initializeSortable();
    });
  }

  _initializeSortable() {
    const list = this.shadowRoot?.querySelector('#sortPriorityList');
    if (list && window.Sortable) {
      new window.Sortable(list, {
        animation: 150,
        ghostClass: 'sortable-ghost',
        onEnd: (evt) => {
          // Update the simple priority order based on the new order
          const newOrder = Array.from(list.querySelectorAll('li')).map(li => li.getAttribute('data-value'));
          this.simplePriorityOrder = newOrder;
          
          // Dispatch event to parent
          this.dispatchEvent(new CustomEvent('priority-changed', {
            detail: {
              mode: this.priorityMode,
              simpleOrder: this.simplePriorityOrder,
              weightedCriteria: this.weightedCriteria
            }
          }));
        }
      });
    }
  }

  _getPriorityItemLabel(item) {
    const labels = {
      'participation': 'Participation',
      'level': 'Level',
      'rank': 'Rank',
      'power': 'Power',
      'chief_gear_and_charms': 'Chief Gear and Charms'
    };
    return labels[item] || item;
  }

  _onPriorityModeChange(event) {
    this.priorityMode = event.target.value;
    this.requestUpdate();
    
    // Dispatch event to parent
    this.dispatchEvent(new CustomEvent('priority-changed', {
      detail: {
        mode: this.priorityMode,
        simpleOrder: this.simplePriorityOrder,
        weightedCriteria: this.weightedCriteria
      }
    }));
  }

  _onWeightSliderChange(event, criteria) {
    const weight = parseFloat(event.target.value);
    
    // Update the weight in the weightedCriteria array
    const criteriaItem = this.weightedCriteria.find(item => item.criteria === criteria);
    if (criteriaItem) {
      criteriaItem.weight = weight;
    }
    
    // Update the display value
    const valueDisplay = event.target.parentElement.querySelector('.weight-value');
    if (valueDisplay) {
      valueDisplay.textContent = weight.toFixed(1);
    }
    
    // Dispatch event to parent
    this.dispatchEvent(new CustomEvent('priority-changed', {
      detail: {
        mode: this.priorityMode,
        simpleOrder: this.simplePriorityOrder,
        weightedCriteria: this.weightedCriteria
      }
    }));
  }

  // Method to set priority mode and criteria from external data
  setPrioritySettings(settings) {
    if (settings.mode) {
      this.priorityMode = settings.mode;
    }
    
    if (settings.simpleOrder) {
      this.simplePriorityOrder = settings.simpleOrder;
    }
    
    if (settings.weightedCriteria) {
      this.weightedCriteria = settings.weightedCriteria;
    }
    
    this.requestUpdate();
    
    // Reinitialize Sortable after the update
    this.updateComplete.then(() => {
      this._initializeSortable();
    });
  }
}

customElements.define('priority-selection-view', PrioritySelectionView);
