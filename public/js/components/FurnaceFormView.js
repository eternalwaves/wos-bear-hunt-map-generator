import { LitElement, html, css, unsafeCSS } from 'https://esm.sh/lit@2.7.0';
import { unsafeHTML } from 'https://esm.sh/lit@2.7.0/directives/unsafe-html.js';
import { TemplateLoader } from '../utils/templateLoader.js';
import { FurnaceFormViewLogic } from './logic/FurnaceFormView.js';

export class FurnaceFormView extends LitElement {
  static properties = {
    showForm: { type: Boolean }
  };

  constructor() {
    super();
    this.showForm = false;
    this.logic = new FurnaceFormViewLogic(this);
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
    this.templateString = await TemplateLoader.loadTemplate('/js/components/templates/FurnaceFormView.html');
    this.cssString = await TemplateLoader.loadCSS('/js/components/styles/FurnaceFormView.css');
    this.staticStyles = css`${unsafeCSS(this.cssString)}`;
  }

  render() {
    // Use the loaded template string
    return html`${unsafeHTML(this.templateString)}`;
  }

  _onSubmit(event) {
    this.logic.onSubmit(event);
  }
}

customElements.define('furnace-form-view', FurnaceFormView);
