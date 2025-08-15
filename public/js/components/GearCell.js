import { LitElement, html, css } from 'https://esm.sh/lit@2.7.0';
import { GearCellTemplate } from './templates/GearCellTemplate.js';

export class GearCell extends LitElement {
  static properties = {
    level: { type: String },
    charms: { type: String },
    gearType: { type: String },
    furnaceId: { type: String }
  };

  constructor() {
    super();
    this.level = '';
    this.charms = '';
    this.gearType = '';
    this.furnaceId = '';
  }

  static styles = css`
    :host {
      display: block;
    }
    
    .gear-cell {
      font-size: 12px;
      max-width: 80px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      padding: 2px;
      vertical-align: top;
      cursor: pointer;
      position: relative;
      transition: background-color 0.2s;
    }
    
    .gear-cell:hover {
      background-color: #f0f8ff;
    }
    
    .gear-cell::after {
      content: "✏️";
      position: absolute;
      top: 2px;
      right: 2px;
      font-size: 10px;
      opacity: 0.6;
    }
    
    .gear-cell:hover::after {
      opacity: 1;
    }
    
    .gear-level {
      font-weight: bold;
      color: #007BFF;
      font-size: 11px;
      display: block;
      margin-bottom: 2px;
    }
    
    .gear-charms {
      color: #666;
      font-size: 10px;
      display: block;
    }
    
    .gear-cell .gear-level,
    .gear-cell .gear-charms {
      margin-bottom: 2px;
    }
  `;

  render() {
    return GearCellTemplate(this);
  }

  _getLevelClass() {
    return this.level ? 'has-level' : 'no-level';
  }

  _getCharmsClass() {
    return this.charms ? 'has-charms' : 'no-charms';
  }

  _formatCharms() {
    return this.charms || '-';
  }

  _onClick() {
    this.dispatchEvent(new CustomEvent('gear-click', {
      detail: {
        gearType: this.gearType,
        furnaceId: this.furnaceId,
        level: this.level,
        charms: this.charms
      }
    }));
  }
}

customElements.define('gear-cell', GearCell); 