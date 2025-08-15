import { LitElement, html, css } from 'https://esm.sh/lit@2.7.0';
import { GearModalTemplate } from './templates/GearModalTemplate.js';

export class GearModal extends LitElement {
  static properties = {
    visible: { type: Boolean },
    furnaceId: { type: String },
    gearData: { type: Object }
  };

  constructor() {
    super();
    this.visible = false;
    this.furnaceId = '';
    this.gearData = {};
  }

  static styles = css`
    :host {
      display: block;
    }
    
    .gear-modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(2px);
    }
    
    .gear-modal.show {
      display: flex;
      justify-content: center;
      align-items: center;
    }
    
    .gear-modal-content {
      background-color: #fefefe;
      margin: 5% auto;
      padding: 20px;
      border: 1px solid #888;
      border-radius: 8px;
      width: 90%;
      max-width: 600px;
      max-height: 80vh;
      overflow-y: auto;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }
    
    .gear-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid #007BFF;
    }
    
    .gear-modal-title {
      font-size: 18px;
      font-weight: bold;
      color: #333;
      margin: 0;
    }
    
    .gear-modal-close {
      color: #aaa;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
      line-height: 1;
    }
    
    .gear-modal-close:hover {
      color: #000;
    }
    
    .gear-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 15px;
      margin-bottom: 20px;
    }
    
    .gear-item {
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 15px;
      background-color: #f9f9f9;
    }
    
    .gear-item-header {
      font-weight: bold;
      color: #007BFF;
      margin-bottom: 10px;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .gear-item select,
    .gear-item input {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 14px;
      margin-bottom: 8px;
    }
    
    .gear-item select:focus,
    .gear-item input:focus {
      outline: none;
      border-color: #007BFF;
      box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
    }
    
    .gear-modal-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      padding-top: 15px;
      border-top: 1px solid #eee;
    }
    
    .gear-modal-actions button {
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 500;
    }
    
    .gear-modal-save {
      background-color: #007BFF;
      color: white;
    }
    
    .gear-modal-save:hover {
      background-color: #0056b3;
    }
    
    .gear-modal-cancel {
      background-color: #6c757d;
      color: white;
    }
    
    .gear-modal-cancel:hover {
      background-color: #545b62;
    }
    
    /* Gear grid layout in table */
    .gear-grid-container {
      padding: 8px;
      min-width: 300px;
    }
    
    .gear-grid {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      grid-template-rows: 1fr 1fr;
      gap: 8px;
      width: 100%;
    }
    
    .gear-item-display {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 6px;
      border: 1px solid #e0e0e0;
      border-radius: 4px;
      background-color: #f8f9fa;
      min-height: 60px;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }
    
    .gear-item-display:hover {
      background-color: #e9ecef;
    }
    
    .gear-name {
      font-weight: bold;
      font-size: 10px;
      color: #495057;
      margin-bottom: 2px;
      text-transform: uppercase;
    }
    
    .gear-display-level {
      font-weight: bold;
      color: #007BFF;
      font-size: 9px;
      text-align: center;
      margin-bottom: 1px;
      line-height: 1.2;
    }
    
    .gear-display-charms {
      color: #666;
      font-size: 8px;
      text-align: center;
      line-height: 1.1;
    }
    
    /* Responsive design */
    @media (max-width: 768px) {
      .gear-modal-content {
        margin: 10% auto;
        width: 95%;
        padding: 15px;
      }
      
      .gear-grid {
        grid-template-columns: 1fr;
        gap: 10px;
      }
      
      .gear-item {
        padding: 12px;
      }
      
      .gear-modal-actions {
        flex-direction: column;
      }
      
      .gear-modal-actions button {
        width: 100%;
      }
    }
    
    @media (max-width: 480px) {
      .gear-modal-content {
        margin: 5% auto;
        width: 98%;
        padding: 10px;
      }
      
      .gear-modal-header {
        flex-direction: column;
        gap: 10px;
        text-align: center;
      }
    }
    
    /* Gear change feedback animation */
    @keyframes slideInRight {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
  `;

  render() {
    return GearModalTemplate(this);
  }

  show(furnaceId, gearData) {
    this.furnaceId = furnaceId;
    this.gearData = gearData;
    this.visible = true;
  }

  hide() {
    this.visible = false;
  }

  _onSave() {
    this.dispatchEvent(new CustomEvent('gear-save', {
      detail: {
        furnaceId: this.furnaceId,
        gearData: this.gearData
      }
    }));
    this.hide();
  }

  _onCancel() {
    this.hide();
  }

  _onClose() {
    this.hide();
  }
}

customElements.define('gear-modal', GearModal); 