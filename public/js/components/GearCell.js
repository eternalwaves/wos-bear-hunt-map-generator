import { LitElement, html, css, unsafeCSS } from 'https://esm.sh/lit@2.7.0';
import { unsafeHTML } from 'https://esm.sh/lit@2.7.0/directives/unsafe-html.js';
import { TemplateLoader } from '../utils/templateLoader.js';
import { GearCellLogic } from './logic/GearCell.js';

export class GearCell extends LitElement {
  static properties = {
    level: { type: String },
    charms: { type: String },
    gearType: { type: String },
    furnaceId: { type: String }
  };

  constructor() {
    super();
    this.level = null;
    this.charms = null;
    this.gearType = '';
    this.furnaceId = '';
    this.logic = new GearCellLogic(this);
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
    this.templateString = await TemplateLoader.loadTemplate('/js/components/templates/GearCell.html');
    this.cssString = await TemplateLoader.loadCSS('/js/components/styles/GearCell.css');
    this.staticStyles = css`${unsafeCSS(this.cssString)}`;
  }

  render() {
    // Use the loaded template string
    return html`${unsafeHTML(this.templateString)}`;
  }

  _getLevelClass() {
    return this.logic.getLevelClass();
  }

  _getCharmsClass() {
    return this.logic.getCharmsClass();
  }

  _formatCharms() {
    return this.logic.formatCharms();
  }

  _onClick() {
    this.logic.onClick();
  }
}

customElements.define('gear-cell', GearCell); 