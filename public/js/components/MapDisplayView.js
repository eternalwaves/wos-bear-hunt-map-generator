import { LitElement, html, css, unsafeCSS } from 'https://esm.sh/lit@2.7.0';
import { unsafeHTML } from 'https://esm.sh/lit@2.7.0/directives/unsafe-html.js';
import { TemplateLoader } from '../utils/templateLoader.js';
import { MapDisplayViewLogic } from './logic/MapDisplayView.js';

export class MapDisplayView extends LitElement {
  static properties = {
    map: { type: Object },
    version: { type: String },
    svgContent: { type: String }
  };

  constructor() {
    super();
    this.map = null;
    this.version = null;
    this.svgContent = '';
    this.logic = new MapDisplayViewLogic(this);
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
    this.templateString = await TemplateLoader.loadTemplate('/js/components/templates/MapDisplayView.html');
    this.cssString = await TemplateLoader.loadCSS('/js/components/styles/MapDisplayView.css');
    this.staticStyles = css`${unsafeCSS(this.cssString)}`;
  }

  render() {
    // Use the loaded template string
    return html`${unsafeHTML(this.templateString)}`;
  }

  updated(changedProperties) {
    if (changedProperties.has('map') || changedProperties.has('version')) {
      this.logic.updateMapDisplay();
    }
  }
}

customElements.define('map-display-view', MapDisplayView);
