import { LitElement, html, css } from 'https://esm.sh/lit@2.7.0';
import { PrioritySelectionViewTemplate } from './templates/PrioritySelectionViewTemplate.js';

export class PrioritySelectionView extends LitElement {
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
  `;

  render() {
    return PrioritySelectionViewTemplate(this);
  }
}

customElements.define('priority-selection-view', PrioritySelectionView);
