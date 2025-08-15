import { html } from 'https://esm.sh/lit@2.7.0';

export function MapGeneratorViewTemplate(component) {
  if (component.loading) {
    return html`
      <div class="loading">
        <h2>Loading...</h2>
      </div>
    `;
  }

  return html`
    <div class="header">
      <div>
        <h1>Bear Hunt Map Generator</h1>
        <p>Manage your map objects and generate optimized layouts</p>
      </div>
    </div>

    <!-- Map Management -->
    <map-management-view
      .maps=${component.maps}
      .currentMap=${component.currentMap}
      .currentVersion=${component.currentVersion}
      .versions=${component.versions}
      @map-created=${component._onMapCreated}
      @map-selected=${component._onMapSelected}
      @version-selected=${component._onVersionSelected}
      @version-saved=${component._onVersionSaved}
      @version-deleted=${component._onVersionDeleted}
    ></map-management-view>

    ${component.currentMap ? html`
      <!-- Add Traps -->
      <trap-table-view
        .traps=${component.traps}
        @trap-added=${component._onTrapAdded}
        @trap-deleted=${component._onTrapDeleted}
      ></trap-table-view>

      <!-- Add Miscellaneous Objects -->
      <misc-object-table-view
        .miscObjects=${component.miscObjects}
        @object-added=${component._onObjectAdded}
        @object-deleted=${component._onObjectDeleted}
      ></misc-object-table-view>

      <!-- Add Furnaces -->
      <furnace-form-view
        @furnace-added=${component._onFurnaceAdded}
      ></furnace-form-view>

      <!-- Bulk Upload -->
      <bulk-upload-view
        @furnaces-uploaded=${component._onFurnacesUploaded}
      ></bulk-upload-view>

      <!-- Furnace Table -->
      <furnace-table-view
        .furnaces=${component.furnaces}
        .hasUnsavedChanges=${(furnace) => component.logic.hasUnsavedChanges(furnace)}
        .markFurnaceAsSaved=${(furnace) => component.logic.markFurnaceAsSaved(furnace)}
        @furnace-added=${component._onFurnaceAdded}
        @furnace-updated=${component._onFurnaceUpdated}
        @furnace-deleted=${component._onFurnaceDeleted}
        @furnace-save=${component._onFurnaceSave}
        @furnace-data-changed=${component._onFurnaceDataChanged}
      ></furnace-table-view>

      <!-- Sorting Priority Selection -->
      <priority-selection-view
        id="prioritySelectionView"
        @priority-changed=${component._onPriorityChanged}
      ></priority-selection-view>

      <!-- Controls -->
      <map-controls-view
        .mapGenerated=${component.mapGenerated}
        .hasData=${component.hasData}
        @generate-map=${component._onGenerateMap}
        @download-csv=${component._onDownloadCSV}
        @download-svg=${component._onDownloadSVG}
        @download-png=${component._onDownloadPNG}
        @reset-data=${component._onResetData}
      ></map-controls-view>

      <!-- Legend -->
      <map-legend-view></map-legend-view>

      <!-- Map Display -->
      <map-display-view
        .svgContent=${component.svgContent}
        .furnaces=${component.furnaces}
        .hasUnsavedChanges=${(furnace) => component.logic.hasUnsavedChanges(furnace)}
      ></map-display-view>
    ` : html`
      <div class="loading">
        <h2>Please select a map to begin</h2>
      </div>
    `}

    <gear-modal
      id="gearModal"
      @gear-saved=${component._onGearSaved}
    ></gear-modal>
  `;
}