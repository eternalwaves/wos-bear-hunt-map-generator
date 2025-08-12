import { LitElement, html, css, unsafeCSS } from 'https://esm.sh/lit@2.7.0';
import { unsafeHTML } from 'https://esm.sh/lit@2.7.0/directives/unsafe-html.js';
import { TemplateLoader } from '../utils/templateLoader.js';
import { GearModalLogic } from './logic/GearModal.js';
import './GearItem.js';

export class GearModal extends LitElement {
  static properties = {
    visible: { type: Boolean },
    furnace: { type: Object },
    gearData: { type: Object }
  };

  constructor() {
    super();
    this.visible = false;
    this.furnace = null;
    this.gearData = {
      cap: { level: '', charms: '' },
      watch: { level: '', charms: '' },
      vest: { level: '', charms: '' },
      pants: { level: '', charms: '' },
      ring: { level: '', charms: '' },
      cane: { level: '', charms: '' }
    };
    this.logic = new GearModalLogic(this);
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
    this.templateString = await TemplateLoader.loadTemplate('/js/components/templates/GearModal.html');
    this.cssString = await TemplateLoader.loadCSS('/js/components/styles/GearModal.css');
    this.staticStyles = css`${unsafeCSS(this.cssString)}`;
  }

  render() {
    // Use the loaded template string
    return html`${unsafeHTML(this.templateString)}`;
  }

  // Public methods that delegate to logic class
  show(furnace) {
    this.logic.show(furnace);
  }

  hide() {
    this.logic.hide();
  }

  // Event handlers that delegate to logic class
  _onOverlayClick(event) {
    this.logic.onOverlayClick(event);
  }

  _onContentClick(event) {
    this.logic.onContentClick(event);
  }

  _onClose() {
    this.logic.onClose();
  }

  _onGearChanged(event) {
    this.logic.onGearChanged(event);
  }

  _onSave() {
    this.logic.onSave();
  }
}

customElements.define('gear-modal', GearModal); 