import { LitElement, html, css, unsafeCSS } from 'https://esm.sh/lit@2.7.0';
import { unsafeHTML } from 'https://esm.sh/lit@2.7.0/directives/unsafe-html.js';
import { TemplateLoader } from '../utils/templateLoader.js';
import { MiscObjectFormLogic } from './logic/MiscObjectForm.js';

export class MiscObjectForm extends LitElement {
  static properties = {
    name: { type: String },
    x: { type: Number },
    y: { type: Number },
    size: { type: Number }
  };

  constructor() {
    super();
    this.name = '';
    this.x = null;
    this.y = null;
    this.size = 1;
    this.logic = new MiscObjectFormLogic(this);
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
    this.templateString = await TemplateLoader.loadTemplate('/js/components/templates/MiscObjectForm.html');
    this.cssString = await TemplateLoader.loadCSS('/js/components/styles/MiscObjectForm.css');
    this.staticStyles = css`${unsafeCSS(this.cssString)}`;
  }

  render() {
    // Use the loaded template string
    return html`${unsafeHTML(this.templateString)}`;
  }

  _onNameChange(event) {
    this.logic.onNameChange(event);
  }

  _onXChange(event) {
    this.logic.onXChange(event);
  }

  _onYChange(event) {
    this.logic.onYChange(event);
  }

  _onSizeChange(event) {
    this.logic.onSizeChange(event);
  }

  _onSubmit(event) {
    this.logic.onSubmit(event);
  }

  _onCancel() {
    this.logic.onCancel();
  }
}

customElements.define('misc-object-form', MiscObjectForm);
