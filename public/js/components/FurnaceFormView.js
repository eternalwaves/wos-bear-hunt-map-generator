import { LitElement, html, css } from 'https://esm.sh/lit@2.7.0';
import { FurnaceFormViewLogic } from './logic/FurnaceFormView.js';
import { FurnaceFormViewTemplate } from './templates/FurnaceFormViewTemplate.js';

export class FurnaceFormView extends LitElement {
  static properties = {
    furnace: { type: Object },
    visible: { type: Boolean },
    loading: { type: Boolean }
  };

  constructor() {
    super();
    this.furnace = null;
    this.visible = false;
    this.loading = false;
    this.logic = new FurnaceFormViewLogic(this);
  }

  static styles = css`
    :host {
      display: block;
    }
    
    .furnace-form {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      text-align: left;
    }
    
    .furnace-form h3 {
      margin: 0 0 15px 0;
      color: #333;
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
      padding: 8px;
      border: 1px solid #ced4da;
      border-radius: 4px;
      font-size: 14px;
    }
    
    .form-actions {
      display: flex;
      gap: 10px;
      margin-top: 15px;
    }
    
    .btn {
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
    }
    
    .btn-primary {
      background: #007bff;
      color: white;
    }
    
    .btn-primary:hover {
      background: #0056b3;
    }
    
    .btn-secondary {
      background: #6c757d;
      color: white;
    }
    
    .btn-secondary:hover {
      background: #545b62;
    }
    
    /* Chief Gear Section */
    .gear-section {
      border: 1px solid #ddd;
      padding: 10px;
      margin: 10px 0;
      border-radius: 4px;
      background-color: #f9f9f9;
    }
    
    .gear-section h3 {
      margin: 0 0 10px 0;
      color: #555;
      font-size: 16px;
    }
    
    .gear-row {
      display: flex;
      gap: 10px;
      margin-bottom: 8px;
    }
    
    .gear-row input,
    .gear-row select {
      flex: 1;
      margin: 0;
    }
    
    .gear-row select[name$="_level"] {
      flex: 1;
    }
    
    .gear-row input[name$="_charms"] {
      flex: 1;
    }
  `;

  render() {
    return FurnaceFormViewTemplate(this);
  }

  show(furnace = null) {
    this.furnace = furnace || {};
    this.visible = true;
  }

  hide() {
    this.visible = false;
    this.furnace = null;
  }

  _onSubmit(event) {
    event.preventDefault();
    this.logic.onSubmit();
  }

  _onCancel() {
    this.hide();
  }
}

customElements.define('furnace-form-view', FurnaceFormView);
