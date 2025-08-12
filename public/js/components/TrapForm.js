import { LitElement, html, css } from 'https://esm.sh/lit@2.7.0';
import { TrapFormLogic } from './logic/TrapForm.js';
import { TrapFormTemplate } from './templates/TrapFormTemplate.js';

export class TrapForm extends LitElement {
  static properties = {
    x: { type: Number },
    y: { type: Number }
  };

  constructor() {
    super();
    this.x = null;
    this.y = null;
    this.logic = new TrapFormLogic(this);
  }

  static styles = css`
    :host {
      display: block;
    }
    
    form {
      background: white;
      padding: 15px;
      margin: 10px auto;
      width: 50%;
      box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
      border-radius: 5px;
    }
    
    h3 {
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
  `;

  render() {
    return TrapFormTemplate(this);
  }

  _onXChange(event) {
    this.logic.onXChange(event);
  }

  _onYChange(event) {
    this.logic.onYChange(event);
  }

  _onSubmit(event) {
    this.logic.onSubmit(event);
  }

  _onCancel() {
    this.logic.onCancel();
  }
}

customElements.define('trap-form', TrapForm);
