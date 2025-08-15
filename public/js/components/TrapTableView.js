import { LitElement, html, css } from 'https://esm.sh/lit@2.7.0';
import { TrapTableViewLogic } from './logic/TrapTableView.js';
import { TrapTableViewTemplate } from './templates/TrapTableViewTemplate.js';
import './TrapForm.js';

export class TrapTableView extends LitElement {
  static properties = {
    traps: { type: Array },
    showForm: { type: Boolean }
  };

  constructor() {
    super();
    this.traps = [];
    this.showForm = false;
    this.logic = new TrapTableViewLogic(this);
  }

  static styles = css`
    :host {
      display: block;
    }
    
    .trap-section {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      text-align: left;
    }
    
    .trap-section h3 {
      margin: 0 0 15px 0;
      color: #333;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .add-trap-btn {
      background: #28a745;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
    }
    
    .add-trap-btn:hover {
      background: #218838;
    }
    
    .trap-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    
    .trap-table th,
    .trap-table td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    
    .trap-table th {
      background-color: #f8f9fa;
      font-weight: 600;
      color: #495057;
    }
    
    .trap-table tr:hover {
      background-color: #f8f9fa;
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
  `;

  render() {
    return TrapTableViewTemplate(this);
  }

  // Event handlers that delegate to logic class
  _toggleForm() {
    this.logic.toggleForm();
  }

  _onTrapSubmitted(event) {
    this.logic.onTrapSubmitted(event);
  }

  _onFormCancelled() {
    this.logic.onFormCancelled();
  }

  _onTrapUpdated(event) {
    this.logic.onTrapUpdated(event);
  }

  _onTrapDeleted(event) {
    this.logic.onTrapDeleted(event);
  }

  // Row-level event handlers
  _onEdit(trap) {
    this.logic.onEdit(trap);
  }

  _onDelete(trap) {
    this.logic.onDelete(trap);
  }
}

customElements.define('trap-table-view', TrapTableView);
