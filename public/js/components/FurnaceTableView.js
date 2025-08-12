import { LitElement, html, css } from 'https://esm.sh/lit@2.7.0';
import { FurnaceTableViewLogic } from './logic/FurnaceTableView.js';
import { FurnaceTableViewTemplate } from './templates/FurnaceTableViewTemplate.js';
import './FurnaceFormView.js';
import './GearCell.js';

export class FurnaceTableView extends LitElement {
  static properties = {
    furnaces: { type: Array },
    showForm: { type: Boolean },
    showGearColumns: { type: Boolean }
  };

  constructor() {
    super();
    this.furnaces = [];
    this.showForm = false;
    this.showGearColumns = false;
    this.logic = new FurnaceTableViewLogic(this);
  }

  static styles = css`
    :host {
      display: block;
    }
    
    .furnace-section {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      text-align: left;
    }
    
    .furnace-section h3 {
      margin: 0 0 15px 0;
      color: #333;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .furnace-section h3 div {
      display: flex;
      gap: 10px;
    }
    
    .toggle-gear-btn,
    .add-furnace-btn {
      background: #28a745;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
    }
    
    .toggle-gear-btn:hover,
    .add-furnace-btn:hover {
      background: #218838;
    }
    
    .furnace-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    
    .furnace-table th,
    .furnace-table td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    
    .furnace-table th {
      background-color: #f8f9fa;
      font-weight: 600;
      color: #495057;
    }
    
    .furnace-table tr:hover {
      background-color: #f8f9fa;
    }
    
    /* Input and select styles */
    .furnace-table input,
    .furnace-table select {
      width: 100%;
      padding: 6px 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
      box-sizing: border-box;
    }
    
    .furnace-table input:focus,
    .furnace-table select:focus {
      outline: none;
      border-color: #007bff;
      box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
    }
    
    .furnace-table select {
      background-color: white;
      cursor: pointer;
    }
    
    .edit-level,
    .edit-rank {
        width: 3.05em;
    }

    .edit-power {
        width: 7.25em;
    }

    .edit-participation,
    .edit-coord {
        width: 6em;
    }

    .edit-trap-pref {
        width: 4.5em;
    }

    /* Status classes */
    .unsaved {
      background-color: #FAD800 !important;
    }
    
    .wrong {
      background-color: #FF2A04 !important;
    }
    
    .messaged {
      background-color: #FFAF3D !important;
    }
    
    .moved {
      background-color: #00E200 !important;
    }
    
    .assigned {
      background-color: #2DCCFF !important;
    }
    
    /* Gear cell styles */
    .gear-cell {
      cursor: pointer;
      position: relative;
      transition: background-color 0.2s;
      padding: 2px;
      vertical-align: top;
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
    
    /* Gear grid layout in table */
    .gear-grid-container {
      width: 100%;
      height: 100%;
    }
    
    .gear-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      grid-template-rows: repeat(3, auto);
      gap: 2px;
      font-size: 10px;
      line-height: 1.2;
    }
    
    .gear-item-display {
      display: flex;
      flex-direction: column;
      padding: 1px;
      border: 1px solid #e0e0e0;
      border-radius: 2px;
      background: #f8f9fa;
      min-height: 30px;
    }
    
    .gear-item-display:hover {
      background: #e9ecef;
      border-color: #007bff;
    }
    
    .gear-name {
      font-weight: bold;
      color: #495057;
      font-size: 9px;
      text-align: center;
      border-bottom: 1px solid #dee2e6;
      padding: 1px;
    }
    
    .gear-display-level {
      font-weight: bold;
      color: #007BFF;
      font-size: 8px;
      text-align: center;
      padding: 1px;
    }
    
    .gear-display-charms {
      color: #666;
      font-size: 8px;
      text-align: center;
      padding: 1px;
      word-break: break-all;
    }
    
    .actionBtns {
      text-align: center;
      vertical-align: middle;
    }
    
    .actionBtns button {
      margin: 2px;
      padding: 4px 8px;
      font-size: 12px;
    }
    
    .shiftFurnaceBtns {
      min-width: 7.25em;
      text-align: center;
      vertical-align: middle;
    }
    
    .shiftFurnaceBtns button {
      margin: 1px;
      padding: 2px 4px;
      font-size: 10px;
      width: 20px;
      height: 20px;
    }
  `;

  render() {
    return FurnaceTableViewTemplate(this);
  }

  _getStatusClass(furnace) {
    if (!furnace) return '';
    
    const classes = [];
    if (furnace.unsaved) classes.push('unsaved');
    if (furnace.status) classes.push(furnace.status);
    
    return classes.join(' ');
  }

  // Event handlers that delegate to logic class
  _toggleForm() {
    this.logic.toggleForm();
  }

  _toggleGearColumns() {
    this.showGearColumns = !this.showGearColumns;
  }

  _onFurnaceSubmitted(event) {
    this.logic.onFurnaceSubmitted(event);
  }

  _onFormCancelled() {
    this.logic.onFormCancelled();
  }

  // Row-level event handlers
  _onNameChange(event, furnace) {
    if (furnace) {
      furnace.name = event.target.value;
      this.requestUpdate();
    }
  }

  _onLevelChange(event, furnace) {
    if (furnace) {
      furnace.level = event.target.value;
      this.requestUpdate();
    }
  }

  _onPowerChange(event, furnace) {
    if (furnace) {
      furnace.power = parseInt(event.target.value);
      this.requestUpdate();
    }
  }

  _onRankChange(event, furnace) {
    if (furnace) {
      furnace.rank = event.target.value;
      this.requestUpdate();
    }
  }

  _onParticipationChange(event, furnace) {
    if (furnace) {
      furnace.participation = parseInt(event.target.value);
      this.requestUpdate();
    }
  }

  _onTrapPrefChange(event, furnace) {
    if (furnace) {
      furnace.trapPref = event.target.value;
      this.requestUpdate();
    }
  }

  _onXChange(event, furnace) {
    if (furnace) {
      furnace.x = parseInt(event.target.value);
      // Auto-assign status to "assigned" if both X and Y are set
      if (furnace.x && furnace.y && !furnace.status) {
        furnace.status = 'assigned';
      }
      this.requestUpdate();
    }
  }

  _onYChange(event, furnace) {
    if (furnace) {
      furnace.y = parseInt(event.target.value);
      // Auto-assign status to "assigned" if both X and Y are set
      if (furnace.x && furnace.y && !furnace.status) {
        furnace.status = 'assigned';
      }
      this.requestUpdate();
    }
  }

  _onStatusChange(event, furnace) {
    if (furnace) {
      furnace.status = event.target.value;
      this.requestUpdate();
    }
  }

  _onGearClick(event, furnace) {
    this.logic.onGearClick(event, furnace);
  }

  _onSave(furnace) {
    this.logic.onSave(furnace);
  }

  _onEdit(furnace) {
    this.logic.onEdit(furnace);
  }

  _onDelete(furnace) {
    this.logic.onDelete(furnace);
  }

  _onShift(dx, dy, furnace) {
    this.logic.onShift(dx, dy, furnace);
  }
}

customElements.define('furnace-table-view', FurnaceTableView); 