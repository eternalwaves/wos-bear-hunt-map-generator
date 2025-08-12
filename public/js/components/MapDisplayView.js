import { LitElement, html, css } from 'https://esm.sh/lit@2.7.0';
import { MapDisplayViewLogic } from './logic/MapDisplayView.js';
import { MapDisplayViewTemplate } from './templates/MapDisplayViewTemplate.js';

export class MapDisplayView extends LitElement {
  static properties = {
    svgContent: { type: String },
    loading: { type: Boolean }
  };

  constructor() {
    super();
    this.svgContent = '';
    this.loading = false;
    this.logic = new MapDisplayViewLogic(this);
  }

  static styles = css`
    :host {
      display: block;
    }
    
    .map-display {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      text-align: center;
    }
    
    .map-display h3 {
      margin: 0 0 15px 0;
      color: #333;
    }
    
    .no-map {
      padding: 40px;
      color: #666;
      font-style: italic;
    }
    
    #map {
      margin-top: 20px;
      padding: 10px;
      background: white;
      display: block;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      overflow-x: auto;
    }
    
    #map svg:not(.zoomed) {
      cursor: -webkit-zoom-in;
      cursor: zoom-in;
      height: auto;
      max-width: 100%;
    }
    
    #map svg.zoomed {
      cursor: -webkit-zoom-out;
      cursor: zoom-out;
      height: auto;
      width: auto;
    }
    
    /* Drag and Drop Styles */
    #map rect[data-obj-id^='furnace_'] {
      transition: stroke 0.2s ease, stroke-width 0.2s ease;
      cursor: grab;
    }
    
    #map rect[data-obj-id^='furnace_']:hover {
      stroke-width: 2px;
      stroke: #007BFF;
    }
    
    #map rect[data-obj-id^='furnace_'].dragging {
      cursor: grabbing;
      stroke-width: 3px;
      stroke: #007BFF;
      opacity: 0.8;
    }
    
    #map rect[data-obj-id^='furnace_'].collision {
      stroke: #FF2A04 !important;
      stroke-width: 3px;
      animation: pulse 0.5s ease-in-out;
    }
    
    #map rect[data-obj-id^='furnace_'].valid {
      stroke: #00FF00 !important;
      stroke-width: 3px;
    }
    
    #map rect[data-obj-id^='furnace_'][data-locked='true'] {
      cursor: not-allowed;
    }
    
    #map rect[data-obj-id^='furnace_'][data-locked='true']:hover {
      stroke: #FF6B6B;
      stroke-width: 2px;
    }
    
    /* Map Changes Buttons Container */
    .map-changes-container {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1000;
      display: flex;
      gap: 10px;
      background: rgba(255, 255, 255, 0.95);
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      backdrop-filter: blur(10px);
    }
    
    #saveMapChangesBtn {
      background-color: #28a745;
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 6px;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    #saveMapChangesBtn:hover {
      background-color: #218838;
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    }
    
    #saveMapChangesBtn:active {
      transform: translateY(0);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }
    
    #discardMapChangesBtn {
      background-color: #dc3545;
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 6px;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    #discardMapChangesBtn:hover {
      background-color: #c82333;
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    }
    
    #discardMapChangesBtn:active {
      transform: translateY(0);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }
    
    /* Pulse animation for collision feedback */
    @keyframes pulse {
      0% { stroke-width: 3px; }
      50% { stroke-width: 5px; }
      100% { stroke-width: 3px; }
    }
    
    /* Prevent text selection during drag */
    body.dragging {
      user-select: none;
      -webkit-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
    }
  `;

  render() {
    return MapDisplayViewTemplate(this);
  }

  updateMapDisplay(svgContent) {
    this.svgContent = svgContent;
  }
}

customElements.define('map-display-view', MapDisplayView);
