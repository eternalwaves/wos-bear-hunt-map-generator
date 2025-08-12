import { LitElement, html, css, unsafeCSS } from 'https://esm.sh/lit@2.7.0';
import { unsafeHTML } from 'https://esm.sh/lit@2.7.0/directives/unsafe-html.js';
import { TemplateLoader } from '../utils/templateLoader.js';
import { MapGeneratorViewLogic } from './logic/MapGeneratorView.js';
import './MapManagementView.js';
import './FurnaceFormView.js';
import './BulkUploadView.js';
import './FurnaceTableView.js';
import './TrapTableView.js';
import './MiscObjectTableView.js';
import './PrioritySelectionView.js';
import './MapControlsView.js';
import './MapLegendView.js';
import './MapDisplayView.js';
import './GearModal.js';

export class MapGeneratorView extends LitElement {
  static properties = {
    currentMap: { type: Object },
    currentVersion: { type: String },
    maps: { type: Array },
    loading: { type: Boolean },
    mapGenerated: { type: Boolean },
    hasData: { type: Boolean },
    svgContent: { type: String }
  };

  constructor() {
    super();
    this.currentMap = null;
    this.currentVersion = null;
    this.maps = [];
    this.loading = false;
    this.mapGenerated = false;
    this.hasData = false;
    this.svgContent = '';
    this.logic = new MapGeneratorViewLogic(this);
    this.templateString = '';
    this.cssString = '';
  }

  static styles = css`
    /* Default styles - will be overridden by loaded CSS */
    :host {
      display: block;
    }
  `;

  async connectedCallback() {
    super.connectedCallback();
    await this._loadTemplates();
    await this.logic.loadMaps();
  }

  async _loadTemplates() {
    // Load HTML template and CSS separately
    this.templateString = await TemplateLoader.loadTemplate('/js/components/templates/MapGeneratorView.html');
    this.cssString = await TemplateLoader.loadCSS('/js/components/styles/MapGeneratorView.css');
    this.staticStyles = css`${unsafeCSS(this.cssString)}`;
  }

  render() {
    if (this.loading) {
      return html`
        <div class="loading">
          <h2>Loading...</h2>
        </div>
      `;
    }

    // Use the loaded template string
    return html`${unsafeHTML(this.templateString)}`;
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

  _onPriorityChanged(event) {
    this.logic.onPriorityChanged(event);
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