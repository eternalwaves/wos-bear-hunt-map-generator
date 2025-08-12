import { LitElement, html, css, unsafeCSS } from 'https://esm.sh/lit@2.7.0';
import { unsafeHTML } from 'https://esm.sh/lit@2.7.0/directives/unsafe-html.js';
import { TemplateLoader } from '../utils/templateLoader.js';
import { FurnaceTableViewLogic } from './logic/FurnaceTableView.js';
import './FurnaceTableRow.js';
import './FurnaceFormView.js';

export class FurnaceTableView extends LitElement {
  static properties = {
    map: { type: Object },
    furnaces: { type: Array },
    showGearColumns: { type: Boolean },
    showForm: { type: Boolean }
  };

  constructor() {
    super();
    this.map = null;
    this.furnaces = [];
    this.showGearColumns = false;
    this.showForm = false;
    this.logic = new FurnaceTableViewLogic(this);
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
    this.templateString = await TemplateLoader.loadTemplate('/js/components/templates/FurnaceTableView.html');
    this.cssString = await TemplateLoader.loadCSS('/js/components/styles/FurnaceTableView.css');
    this.staticStyles = css`${unsafeCSS(this.cssString)}`;
  }

  render() {
    // Use the loaded template string
    return html`${unsafeHTML(this.templateString)}`;
  }

  updated(changedProperties) {
    if (changedProperties.has('map') && this.map) {
      this.logic.updateFurnacesFromMap();
    }
  }

  // Event handlers that delegate to logic class
  _toggleGearColumns() {
    this.logic.toggleGearColumns();
  }

  _toggleForm() {
    this.logic.toggleForm();
  }

  _onFurnaceSubmitted(event) {
    this.logic.onFurnaceSubmitted(event);
  }

  _onFormCancelled() {
    this.logic.onFormCancelled();
  }

  _onFurnaceUpdated(event) {
    this.logic.onFurnaceUpdated(event);
  }

  _onFurnaceDeleted(event) {
    this.logic.onFurnaceDeleted(event);
  }

  _onGearEdit(event) {
    this.logic.onGearEdit(event);
  }
}

customElements.define('furnace-table-view', FurnaceTableView); 