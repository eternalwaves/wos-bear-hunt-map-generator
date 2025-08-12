import { LitElement, html, css, unsafeCSS } from 'https://esm.sh/lit@2.7.0';
import { unsafeHTML } from 'https://esm.sh/lit@2.7.0/directives/unsafe-html.js';
import { TemplateLoader } from '../utils/templateLoader.js';
import { TrapTableRowLogic } from './logic/TrapTableRow.js';

export class TrapTableRow extends LitElement {
  static properties = {
    trap: { type: Object },
    index: { type: Number }
  };

  constructor() {
    super();
    this.trap = null;
    this.index = 0;
    this.logic = new TrapTableRowLogic(this);
    this.templateString = '';
    this.cssString = '';
  }

  static styles = css`/* Default styles - will be overridden by loaded CSS */ :host { display: block; }`;

  async connectedCallback() {
    super.connectedCallback();
    await this._loadTemplates();
  }

  async _loadTemplates() {
    // Load HTML template and CSS separately
    this.templateString = await TemplateLoader.loadTemplate('/js/components/templates/TrapTableRow.html');
    this.cssString = await TemplateLoader.loadCSS('/js/components/styles/TrapTableRow.css');
    this.staticStyles = css`${unsafeCSS(this.cssString)}`;
  }

  render() {
    if (!this.trap) return html``;
    // Use the loaded template string
    return html`${unsafeHTML(this.templateString)}`;
  }

  _onEdit() {
    this.logic.onEdit();
  }

  _onDelete() {
    this.logic.onDelete();
  }
}

customElements.define('trap-table-row', TrapTableRow);
