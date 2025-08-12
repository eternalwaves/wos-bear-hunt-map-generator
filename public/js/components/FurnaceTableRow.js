import { LitElement, html, css, unsafeCSS } from 'https://esm.sh/lit@2.7.0';
import { unsafeHTML } from 'https://esm.sh/lit@2.7.0/directives/unsafe-html.js';
import { TemplateLoader } from '../utils/templateLoader.js';
import { FurnaceTableRowLogic } from './logic/FurnaceTableRow.js';
import './GearCell.js';

export class FurnaceTableRow extends LitElement {
  static properties = {
    furnace: { type: Object },
    index: { type: Number },
    showGearColumns: { type: Boolean }
  };

  constructor() {
    super();
    this.furnace = null;
    this.index = 0;
    this.showGearColumns = false;
    this.logic = new FurnaceTableRowLogic(this);
    this.templateString = '';
    this.cssString = '';
  }

  static styles = css`/* Default styles - will be overridden by loaded CSS */ :host { display: block; }`;

  async connectedCallback() {
    super.connectedCallback();
    await this._loadTemplates();
  }

  async _loadTemplates() {
    // Load HTML template (includes CSS in style tag)
    this.templateString = await TemplateLoader.loadTemplate('/js/components/templates/FurnaceTableRow.html');
  }

  render() {
    if (!this.furnace) return html``;
    
    // Use the loaded template string
    return html`${unsafeHTML(this.templateString)}`;
  }

  _getStatusClass() {
    return this.logic.getStatusClass();
  }

  _getStatusText() {
    return this.logic.getStatusText();
  }

  _onGearClick(event) {
    this.logic.onGearClick(event);
  }

  _onGearEdit() {
    this.logic.onGearEdit();
  }

  _onEdit() {
    this.logic.onEdit();
  }

  _onDelete() {
    this.logic.onDelete();
  }
}

customElements.define('furnace-table-row', FurnaceTableRow); 