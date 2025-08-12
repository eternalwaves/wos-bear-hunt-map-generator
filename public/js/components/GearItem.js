import { LitElement, html, css, unsafeCSS } from 'https://esm.sh/lit@2.7.0';
import { unsafeHTML } from 'https://esm.sh/lit@2.7.0/directives/unsafe-html.js';
import { TemplateLoader } from '../utils/templateLoader.js';
import { GearItemLogic } from './logic/GearItem.js';

export class GearItem extends LitElement {
  static properties = {
    level: { type: String },
    charms: { type: String },
    gearType: { type: String }
  };

  constructor() {
    super();
    this.level = '';
    this.charms = '';
    this.gearType = '';
    this.logic = new GearItemLogic(this);
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
    this.templateString = await TemplateLoader.loadTemplate('/js/components/templates/GearItem.html');
    this.cssString = await TemplateLoader.loadCSS('/js/components/styles/GearItem.css');
    this.staticStyles = css`${unsafeCSS(this.cssString)}`;
  }

  render() {
    // Use the loaded template string
    return html`${unsafeHTML(this.templateString)}`;
  }

  _getPreviewContent() {
    return this.logic.getPreviewContent();
  }

  _onLevelChange(event) {
    this.logic.onLevelChange(event);
  }

  _onCharmsChange(event) {
    this.logic.onCharmsChange(event);
  }
}

customElements.define('gear-item', GearItem); 