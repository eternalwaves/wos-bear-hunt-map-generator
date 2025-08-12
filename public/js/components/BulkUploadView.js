import { LitElement, html, css } from 'https://esm.sh/lit@2.7.0';
import { BulkUploadViewTemplate } from './templates/BulkUploadViewTemplate.js';

export class BulkUploadView extends LitElement {
  static styles = css`
    :host {
      display: block;
    }
    
    .bulk-upload {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      text-align: left;
    }
    
    .bulk-upload h3 {
      margin: 0 0 15px 0;
      color: #333;
    }
    
    .upload-form {
      display: flex;
      gap: 15px;
      align-items: center;
    }
    
    .upload-form input[type="file"] {
      flex: 1;
      padding: 8px;
      border: 1px solid #ced4da;
      border-radius: 4px;
      font-size: 14px;
    }
    
    .btn {
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      background: #28a745;
      color: white;
    }
    
    .btn:hover {
      background: #218838;
    }
  `;

  render() {
    return BulkUploadViewTemplate(this);
  }

  _onSubmit(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    this.dispatchEvent(new CustomEvent('furnaces-uploaded', {
      detail: { formData }
    }));
  }
}

customElements.define('bulk-upload-view', BulkUploadView);
