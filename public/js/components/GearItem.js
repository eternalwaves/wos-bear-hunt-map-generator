import { LitElement, html, css } from 'https://esm.sh/lit@2.7.0';
import { GearItemLogic } from './logic/GearItem.js';
import { GearItemTemplate } from './templates/GearItemTemplate.js';

export class GearItem extends LitElement {
  static properties = {
    level: { type: String },
    charms: { type: String },
    gearType: { type: String }
  };

  constructor() {
    super();
    this.level = '';
    this.charms = '';
    this.gearType = '';
    this.logic = new GearItemLogic(this);
  }

  static styles = css`
    :host {
      display: block;
    }
    
    .gear-item {
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 15px;
      background-color: #f9f9f9;
    }
    
    .gear-item h4 {
      font-weight: bold;
      color: #007BFF;
      margin-bottom: 10px;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .form-row {
      display: flex;
      gap: 15px;
      margin-bottom: 10px;
    }
    
    .form-group {
      flex: 1;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      color: #495057;
    }
    
    .form-group input,
    .form-group select {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 14px;
    }
    
    .form-group select:focus,
    .form-group input:focus {
      outline: none;
      border-color: #007BFF;
      box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
    }
  `;

  render() {
    return GearItemTemplate(this);
  }

  _getPreviewContent() {
    return this.logic.getPreviewContent();
  }

  _onLevelChange(event) {
    this.logic.onLevelChange(event);
  }

  _onCharmsChange(event) {
    this.logic.onCharmsChange(event);
  }
}

customElements.define('gear-item', GearItem); 