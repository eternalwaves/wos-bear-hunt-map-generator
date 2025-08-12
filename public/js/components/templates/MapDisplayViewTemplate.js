import { html } from 'https://esm.sh/lit@2.7.0';
import { unsafeHTML } from 'https://esm.sh/lit@2.7.0/directives/unsafe-html.js';

export function MapDisplayViewTemplate(component) {
  return html`
    <div class="map-display">
      <h3>Map Display</h3>
      <div id="map">
        ${component.svgContent ? html`
          ${unsafeHTML(component.svgContent)}
        ` : html`
          <div class="no-map">
            ${component.map ? 'No map generated yet. Click "Generate Map" to create one.' : 'Select a map to begin.'}
          </div>
        `}
      </div>
    </div>
  `;
}
