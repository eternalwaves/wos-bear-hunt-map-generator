import { LitElement, html, css, unsafeCSS } from 'https://esm.sh/lit@2.7.0';
import { unsafeHTML } from 'https://esm.sh/lit@2.7.0/directives/unsafe-html.js';
import { TemplateLoader } from '../utils/templateLoader.js';
import { MapManagementViewLogic } from './logic/MapManagementView.js';

export class MapManagementView extends LitElement {
  static properties = {
    maps: { type: Array },
    currentMap: { type: Object },
    currentVersion: { type: String },
    showCreateForm: { type: Boolean }
  };

  constructor() {
    super();
    this.maps = [];
    this.currentMap = null;
    this.currentVersion = null;
    this.showCreateForm = false;
    this.logic = new MapManagementViewLogic(this);
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
  }

  async _loadTemplates() {
    // Load HTML template and CSS separately
    this.templateString = await TemplateLoader.loadTemplate('/js/components/templates/MapManagementView.html');
    this.cssString = await TemplateLoader.loadCSS('/js/components/styles/MapManagementView.css');
    this.staticStyles = css`${unsafeCSS(this.cssString)}`;
  }

  render() {
    // Use the loaded template string
    return html`${unsafeHTML(this.templateString)}`;
  }

  updated(changedProperties) {
    if (changedProperties.has('maps')) {
      this.logic.updateMapSelector();
    }
  }

  _onCreateMapSubmit(event) {
    this.logic.onCreateMapSubmit(event);
  }

  _onMapSelectChange(event) {
    this.logic.onMapSelectChange(event);
  }

  _onVersionSelectChange(event) {
    this.logic.onVersionSelectChange(event);
  }

  _onSaveVersionSubmit(event) {
    this.logic.onSaveVersionSubmit(event);
  }

  _onDeleteVersion() {
    this.logic.onDeleteVersion();
  }
}

customElements.define('map-management-view', MapManagementView);
