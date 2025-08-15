import { html } from 'https://esm.sh/lit@2.7.0';

export function MapControlsViewTemplate(component) {
  return html`
    <div class="map-controls">
      <h3>Map Controls</h3>
      <div class="control-buttons">
        <button @click=${component._onGenerateMap} ?disabled=${!component.hasData}>
          Generate Map
        </button>
        <button @click=${component._onDownloadCSV} ?disabled=${!component.mapGenerated}>
          Download CSV
        </button>
        <button @click=${component._onDownloadSVG} ?disabled=${!component.mapGenerated}>
          Download SVG
        </button>
        <button @click=${component._onDownloadPNG} ?disabled=${!component.mapGenerated}>
          Download PNG
        </button>
        <button @click=${component._onResetData} ?disabled=${!component.hasData}>
          Reset Data
        </button>
      </div>
    </div>
  `;
}
