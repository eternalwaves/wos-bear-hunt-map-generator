import { LitElement, html, css, unsafeCSS } from 'https://esm.sh/lit@2.7.0';
import { unsafeHTML } from 'https://esm.sh/lit@2.7.0/directives/unsafe-html.js';
import { TemplateLoader } from '../utils/templateLoader.js';
import { MapControlsViewLogic } from './logic/MapControlsView.js';

export class MapControlsView extends LitElement {
  static properties = {
    mapGenerated: { type: Boolean },
    hasData: { type: Boolean }
  };

  constructor() {
    super();
    this.mapGenerated = false;
    this.hasData = false;
    this.logic = new MapControlsViewLogic(this);
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
    this.templateString = await TemplateLoader.loadTemplate('/js/components/templates/MapControlsView.html');
    this.cssString = await TemplateLoader.loadCSS('/js/components/styles/MapControlsView.css');
    this.staticStyles = css`${unsafeCSS(this.cssString)}`;
  }

  render() {
    // Use the loaded template string
    return html`${unsafeHTML(this.templateString)}`;
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
}

customElements.define('map-controls-view', MapControlsView);
