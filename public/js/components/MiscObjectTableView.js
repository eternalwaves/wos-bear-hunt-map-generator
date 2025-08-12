import { LitElement, html, css } from 'https://esm.sh/lit@2.7.0';
import { MiscObjectTableViewLogic } from './logic/MiscObjectTableView.js';
import { MiscObjectTableViewTemplate } from './templates/MiscObjectTableViewTemplate.js';
import './MiscObjectForm.js';

export class MiscObjectTableView extends LitElement {
  static properties = {
    miscObjects: { type: Array },
    showForm: { type: Boolean }
  };

  constructor() {
    super();
    this.miscObjects = [];
    this.showForm = false;
    this.logic = new MiscObjectTableViewLogic(this);
  }

  static styles = css`
    :host {
      display: block;
    }
    
    .misc-object-section {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      text-align: left;
    }
    
    .misc-object-section h3 {
      margin: 0 0 15px 0;
      color: #333;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .add-object-btn {
      background: #28a745;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
    }
    
    .add-object-btn:hover {
      background: #218838;
    }
    
    .misc-object-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    
    .misc-object-table th,
    .misc-object-table td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    
    .misc-object-table th {
      background-color: #f8f9fa;
      font-weight: 600;
      color: #495057;
    }
    
    .misc-object-table tr:hover {
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
    return MiscObjectTableViewTemplate(this);
  }

  // Event handlers that delegate to logic class
  _toggleForm() {
    this.logic.toggleForm();
  }

  _onMiscObjectSubmitted(event) {
    this.logic.onMiscObjectSubmitted(event);
  }

  _onFormCancelled() {
    this.logic.onFormCancelled();
  }

  _onMiscObjectUpdated(event) {
    this.logic.onMiscObjectUpdated(event);
  }

  _onMiscObjectDeleted(event) {
    this.logic.onMiscObjectDeleted(event);
  }

  // Row-level event handlers
  _onEdit(object) {
    this.logic.onEdit(object);
  }

  _onDelete(object) {
    this.logic.onDelete(object);
  }
}

customElements.define('misc-object-table-view', MiscObjectTableView);
