import { LitElement, html, css } from 'https://esm.sh/lit@2.7.0';
import { MapLegendViewTemplate } from './templates/MapLegendViewTemplate.js';

export class MapLegendView extends LitElement {
  static styles = css`
    :host {
      display: block;
    }
    
    .map-legend {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      text-align: left;
    }
    
    .map-legend h3 {
      margin: 0 0 15px 0;
      color: #333;
    }
    
    .legend-items {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 10px;
    }
    
    .legend-item {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .legend-color {
      width: 20px;
      height: 20px;
      border-radius: 3px;
    }
  `;

  render() {
    return MapLegendViewTemplate(this);
  }
}

customElements.define('map-legend-view', MapLegendView);
