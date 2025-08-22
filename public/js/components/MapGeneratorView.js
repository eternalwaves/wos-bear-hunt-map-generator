import { LitElement, html, css } from 'https://esm.sh/lit@2.7.0';
import { MapGeneratorViewLogic } from './logic/MapGeneratorView.js';
import { MapGeneratorViewTemplate } from './templates/MapGeneratorViewTemplate.js';
import './MapManagementView.js';
import './FurnaceFormView.js';
import './BulkUploadView.js';
import './PrioritySelectionView.js';
import './MapControlsView.js';
import './MapLegendView.js';
import './MapDisplayView.js';
import './GearModal.js';
import './FurnaceTableView.js';
import './TrapTableView.js';
import './MiscObjectTableView.js';

export class MapGeneratorView extends LitElement {
  static properties = {
    currentMap: { type: Object },
    currentVersion: { type: String },
    maps: { type: Array },
    versions: { type: Array },
    furnaces: { type: Array },
    traps: { type: Array },
    miscObjects: { type: Array },
    hasData: { type: Boolean },
    loading: { type: Boolean },
    mapGenerated: { type: Boolean },
    svgContent: { type: String }
  };

  constructor() {
    super();
    this.currentMap = null;
    this.currentVersion = '';
    this.maps = [];
    this.versions = [];
    this.furnaces = [];
    this.traps = [];
    this.miscObjects = [];
    this.hasData = false;
    this.loading = false;
    this.mapGenerated = false;
    this.svgContent = '';
    this.logic = new MapGeneratorViewLogic(this);
  }

  static styles = css`
    :host {
      display: block;
    }
    
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid #e1e5e9;
      text-align: left;
    }
    
    .header h1 {
      margin: 0;
      color: #333;
    }
    
    .header p {
      margin: 5px 0 0 0;
      color: #666;
    }
    
    .loading {
      text-align: center;
      padding: 40px;
      color: #666;
    }
    
    .loading h2 {
      margin: 0;
      color: #333;
    }
    
    /* Section styles for components */
    .furnace-section,
    .trap-section,
    .misc-object-section {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      text-align: left;
    }
    
    .furnace-section h3,
    .trap-section h3,
    .misc-object-section h3 {
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
    
    .toggle-gear-btn {
      background: #6c757d;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
    }
    
    .toggle-gear-btn:hover {
      background: #545b62;
    }
    
    .add-furnace-btn,
    .add-trap-btn,
    .add-object-btn {
      background: #28a745;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
    }
    
    .add-furnace-btn:hover,
    .add-trap-btn:hover,
    .add-object-btn:hover {
      background: #218838;
    }
    
    .furnace-table,
    .trap-table,
    .misc-object-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    
    .furnace-table th,
    .furnace-table td,
    .trap-table th,
    .trap-table td,
    .misc-object-table th,
    .misc-object-table td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    
    .furnace-table th,
    .trap-table th,
    .misc-object-table th {
      background-color: #f8f9fa;
      font-weight: 600;
      color: #495057;
    }
    
    .furnace-table tr:hover,
    .trap-table tr:hover,
    .misc-object-table tr:hover {
      background-color: #f8f9fa;
    }
    
    /* Map controls and legend */
    .map-controls,
    .map-legend {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      text-align: left;
    }
    
    .map-controls h3,
    .map-legend h3 {
      margin: 0 0 15px 0;
      color: #333;
    }
    
    .control-buttons {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }
    
    .control-buttons button {
      flex: 1;
      min-width: 120px;
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      background: #007bff;
      color: white;
    }
    
    .control-buttons button:hover {
      background: #0056b3;
    }
    
    .control-buttons button:disabled {
      background: #6c757d;
      cursor: not-allowed;
    }
    
    .legend-items {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 10px;
    }
    
    .legend-item {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .legend-color {
      width: 20px;
      height: 20px;
      border-radius: 3px;
    }
    
    /* Priority selection */
    .priority-selection {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      text-align: left;
    }
    
    .priority-selection h3 {
      margin: 0 0 15px 0;
      color: #333;
    }
    
    /* Bulk upload */
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
    
    /* Map management */
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
  `;

  async connectedCallback() {
    super.connectedCallback();
    await this.logic.loadMaps();
  }

  render() {
    return MapGeneratorViewTemplate(this);
  }

  // Event handlers that delegate to logic class
  _onMapCreated(event) {
    this.logic.onMapCreated(event);
  }

  _onMapSelected(event) {
    this.logic.onMapSelected(event);
  }

  _onVersionSelected(event) {
    this.logic.onVersionSelected(event);
  }

  _onVersionSaved(event) {
    this.logic.onVersionSaved(event);
  }

  _onVersionDeleted(event) {
    this.logic.onVersionDeleted(event);
  }

  _onFurnaceAdded(event) {
    this.logic.onFurnaceAdded(event);
  }

  _onFurnaceUpdated(event) {
    this.logic.onFurnaceUpdated(event);
  }

  _onFurnaceDeleted(event) {
    this.logic.onFurnaceDeleted(event);
  }

  _onFurnaceSave(event) {
    const furnace = event.detail.furnace;
    this.logic.markFurnaceAsSaved(furnace);
    this.logic.onFurnaceUpdated(event);
  }

  _onPriorityChanged(event) {
    this.logic.onPriorityChanged(event);
  }

  _onFurnaceDataChanged(event) {
    // Update the SVG display when furnace data changes
    const mapDisplayView = this.shadowRoot?.querySelector('map-display-view');
    if (mapDisplayView) {
      mapDisplayView.updateUnsavedClasses();
    }
  }

  _onFurnaceStatusUpdated(event) {
    this.logic.onFurnaceStatusUpdated(event.detail);
  }

  _onFurnacesUploaded(event) {
    this.logic.onFurnacesUploaded(event);
  }

  _onTrapAdded(event) {
    this.logic.onTrapAdded(event);
  }

  _onTrapDeleted(event) {
    this.logic.onTrapDeleted(event);
  }

  _onObjectAdded(event) {
    this.logic.onObjectAdded(event);
  }

  _onObjectDeleted(event) {
    this.logic.onObjectDeleted(event);
  }

  _onGenerateMap() {
    this.logic.onGenerateMap();
  }

  _onDownloadCSV() {
    this.logic.onDownloadCSV();
  }

  _onDownloadSVG() {
    this.logic.onDownloadSVG();
  }

  _onDownloadPNG() {
    this.logic.onDownloadPNG();
  }

  _onResetData() {
    this.logic.onResetData();
  }

  _onGearSaved(event) {
    this.logic.onGearSaved(event);
  }
}

customElements.define('map-generator-view', MapGeneratorView); 