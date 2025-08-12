import { html } from 'https://esm.sh/lit@2.7.0';

export function MapManagementViewTemplate(component) {
  return html`
    <div class="map-management">
      <h3>Map Management</h3>
      
      <div class="map-selector">
        <label for="mapSelect">Select Map:</label>
        <select id="mapSelect" @change=${component._onMapSelected}>
          <option value="">Choose a map...</option>
          ${component.maps.map(map => html`
            <option value="${map.id}" ?selected=${component.currentMap && component.currentMap.id === map.id}>
              ${map.name}
            </option>
          `)}
        </select>
      </div>
      
      ${component.currentMap ? html`
        <div class="version-controls">
          <label for="versionSelect">Version:</label>
          <select id="versionSelect" @change=${component._onVersionSelected}>
            <option value="">Latest</option>
            ${component.versions.map(version => html`
              <option value="${version.version}" ?selected=${component.currentVersion === version.version}>
                ${version.version} (${new Date(version.created_at).toLocaleDateString()})
              </option>
            `)}
          </select>
          <button class="btn" @click=${component._onSaveVersion}>Save Version</button>
          ${component.currentVersion ? html`
            <button class="btn btn-secondary" @click=${component._onDeleteVersion}>Delete Version</button>
          ` : ''}
        </div>
      ` : ''}
    </div>
  `;
}
