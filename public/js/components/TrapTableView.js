import { LitElement, html, css, unsafeCSS } from 'https://esm.sh/lit@2.7.0';
import { unsafeHTML } from 'https://esm.sh/lit@2.7.0/directives/unsafe-html.js';
import { TemplateLoader } from '../utils/templateLoader.js';
import { TrapTableViewLogic } from './logic/TrapTableView.js';
import './TrapTableRow.js';
import './TrapForm.js';

export class TrapTableView extends LitElement {
  static properties = {
    map: { type: Object },
    traps: { type: Array },
    showForm: { type: Boolean }
  };

  constructor() {
    super();
    this.map = null;
    this.traps = [];
    this.showForm = false;
    this.logic = new TrapTableViewLogic(this);
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
    this.templateString = await TemplateLoader.loadTemplate('/js/components/templates/TrapTableView.html');
  }

  render() {
    // Use the loaded template string
    return html`${unsafeHTML(this.templateString)}`;
  }

  updated(changedProperties) {
    if (changedProperties.has('map') && this.map) {
      this.logic.updateTrapsFromMap();
    }
  }

  _toggleForm() {
    this.logic.toggleForm();
  }

  _onTrapSubmitted(event) {
    this.logic.onTrapSubmitted(event);
  }

  _onFormCancelled() {
    this.logic.onFormCancelled();
  }

  _onTrapEdit(event) {
    this.logic.onTrapEdit(event);
  }

  _onTrapDelete(event) {
    this.logic.onTrapDelete(event);
  }
}

customElements.define('trap-table-view', TrapTableView);
