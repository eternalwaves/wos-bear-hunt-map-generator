import { LitElement, html, css, unsafeCSS } from 'https://esm.sh/lit@2.7.0';
import { unsafeHTML } from 'https://esm.sh/lit@2.7.0/directives/unsafe-html.js';
import { TemplateLoader } from '../utils/templateLoader.js';
import { TrapFormLogic } from './logic/TrapForm.js';

export class TrapForm extends LitElement {
  static properties = {
    x: { type: Number },
    y: { type: Number }
  };

  constructor() {
    super();
    this.x = null;
    this.y = null;
    this.logic = new TrapFormLogic(this);
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
    this.templateString = await TemplateLoader.loadTemplate('/js/components/templates/TrapForm.html');
    this.cssString = await TemplateLoader.loadCSS('/js/components/styles/TrapForm.css');
    this.staticStyles = css`${unsafeCSS(this.cssString)}`;
  }

  render() {
    // Use the loaded template string
    return html`${unsafeHTML(this.templateString)}`;
  }

  _onXChange(event) {
    this.logic.onXChange(event);
  }

  _onYChange(event) {
    this.logic.onYChange(event);
  }

  _onSubmit(event) {
    this.logic.onSubmit(event);
  }

  _onCancel() {
    this.logic.onCancel();
  }
}

customElements.define('trap-form', TrapForm);
