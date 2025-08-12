import { html } from 'https://esm.sh/lit@2.7.0';

export function MapLegendViewTemplate(component) {
  return html`
    <div class="map-legend">
      <h3>Map Legend</h3>
      <div class="legend-items">
        <div class="legend-item">
          <div class="legend-color" style="background-color: #2DCCFF;"></div>
          <span>Assigned</span>
        </div>
        <div class="legend-item">
          <div class="legend-color" style="background-color: #00E200;"></div>
          <span>Moved</span>
        </div>
        <div class="legend-item">
          <div class="legend-color" style="background-color: #FFAF3D;"></div>
          <span>Messaged</span>
        </div>
        <div class="legend-item">
          <div class="legend-color" style="background-color: #FF2A04;"></div>
          <span>Wrong</span>
        </div>
        <div class="legend-item">
          <div class="legend-color" style="background-color: #FAD800;"></div>
          <span>Unsaved</span>
        </div>
      </div>
    </div>
  `;
}
