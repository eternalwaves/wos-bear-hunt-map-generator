import { LitElement, html, css, unsafeCSS } from 'https://esm.sh/lit@2.7.0';
import { unsafeHTML } from 'https://esm.sh/lit@2.7.0/directives/unsafe-html.js';
import { TemplateLoader } from '../utils/templateLoader.js';
import { MiscObjectTableViewLogic } from './logic/MiscObjectTableView.js';
import './MiscObjectTableRow.js';
import './MiscObjectForm.js';

export class MiscObjectTableView extends LitElement {
  static properties = {
    map: { type: Object },
    miscObjects: { type: Array },
    showForm: { type: Boolean }
  };

  constructor() {
    super();
    this.map = null;
    this.miscObjects = [];
    this.showForm = false;
    this.logic = new MiscObjectTableViewLogic(this);
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
    this.templateString = await TemplateLoader.loadTemplate('/js/components/templates/MiscObjectTableView.html');
  }

  render() {
    // Use the loaded template string
    return html`${unsafeHTML(this.templateString)}`;
  }

  updated(changedProperties) {
    if (changedProperties.has('map') && this.map) {
      this.logic.updateMiscObjectsFromMap();
    }
  }

  _toggleForm() {
    this.logic.toggleForm();
  }

  _onMiscObjectSubmitted(event) {
    this.logic.onMiscObjectSubmitted(event);
  }

  _onFormCancelled() {
    this.logic.onFormCancelled();
  }

  _onMiscObjectEdit(event) {
    this.logic.onMiscObjectEdit(event);
  }

  _onMiscObjectDelete(event) {
    this.logic.onMiscObjectDelete(event);
  }
}

customElements.define('misc-object-table-view', MiscObjectTableView);
