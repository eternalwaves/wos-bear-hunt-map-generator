import { LitElement, html, css } from 'https://esm.sh/lit@2.7.0';
import { MapManagementViewTemplate } from './templates/MapManagementViewTemplate.js';

export class MapManagementView extends LitElement {
  static properties = {
    maps: { type: Array },
    currentMap: { type: Object },
    currentVersion: { type: String },
    versions: { type: Array }
  };

  constructor() {
    super();
    this.maps = [];
    this.currentMap = null;
    this.currentVersion = null;
    this.versions = [];
  }

  static styles = css`
    :host {
      display: block;
    }
    
    .map-management {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      text-align: left;
    }
    
    .map-management h3 {
      margin: 0 0 15px 0;
      color: #333;
    }
    
    .map-selector {
      display: flex;
      gap: 15px;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .map-selector select {
      flex: 1;
      max-width: 300px;
      padding: 8px;
      border: 1px solid #ced4da;
      border-radius: 4px;
      font-size: 14px;
    }
    
    .version-controls {
      display: flex;
      gap: 15px;
      align-items: center;
    }
    
    .version-controls select {
      flex: 1;
      max-width: 300px;
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
      background: #007bff;
      color: white;
    }
    
    .btn:hover {
      background: #0056b3;
    }
    
    .btn-secondary {
      background: #6c757d;
    }
    
    .btn-secondary:hover {
      background: #545b62;
    }
  `;

  render() {
    return MapManagementViewTemplate(this);
  }

  _onMapSelected(event) {
    const mapId = event.target.value;
    if (mapId) {
      const selectedMap = this.maps.find(map => map.id == mapId);
      this.dispatchEvent(new CustomEvent('map-selected', {
        detail: { map: selectedMap }
      }));
    }
  }

  _onVersionSelected(event) {
    const version = event.target.value;
    this.dispatchEvent(new CustomEvent('version-selected', {
      detail: { version: version || null }
    }));
  }

  _onSaveVersion() {
    // This would typically open a modal or form to enter version name
    const versionName = prompt('Enter version name:');
    if (versionName) {
      this.dispatchEvent(new CustomEvent('version-saved', {
        detail: { version: versionName }
      }));
    }
  }

  _onDeleteVersion() {
    if (confirm(`Are you sure you want to delete version "${this.currentVersion}"?`)) {
      this.dispatchEvent(new CustomEvent('version-deleted', {
        detail: { version: this.currentVersion }
      }));
    }
  }
}

customElements.define('map-management-view', MapManagementView);
