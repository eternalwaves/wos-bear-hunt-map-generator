import { LitElement, html, css } from 'https://esm.sh/lit@2.7.0';
import { MapControlsViewTemplate } from './templates/MapControlsViewTemplate.js';

export class MapControlsView extends LitElement {
  static properties = {
    mapGenerated: { type: Boolean },
    hasData: { type: Boolean }
  };

  constructor() {
    super();
    this.mapGenerated = false;
    this.hasData = false;
  }

  static styles = css`
    :host {
      display: block;
    }
    
    .map-controls {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      text-align: left;
    }
    
    .map-controls h3 {
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
  `;

  render() {
    return MapControlsViewTemplate(this);
  }

  _onGenerateMap() {
    this.dispatchEvent(new CustomEvent('generate-map'));
  }

  _onDownloadCSV() {
    this.dispatchEvent(new CustomEvent('download-csv'));
  }

  _onDownloadSVG() {
    this.dispatchEvent(new CustomEvent('download-svg'));
  }

  _onDownloadPNG() {
    this.dispatchEvent(new CustomEvent('download-png'));
  }

  _onResetData() {
    this.dispatchEvent(new CustomEvent('reset-data'));
  }
}

customElements.define('map-controls-view', MapControlsView);
