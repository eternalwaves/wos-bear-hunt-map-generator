export class MapControlsViewLogic {
  constructor(component) {
    this.component = component;
  }

  onGenerateMap() {
    this.component.dispatchEvent(new CustomEvent('generate-map', {
      bubbles: true,
      composed: true
    }));
  }

  onDownloadCSV() {
    this.component.dispatchEvent(new CustomEvent('download-csv', {
      bubbles: true,
      composed: true
    }));
  }

  onDownloadSVG() {
    this.component.dispatchEvent(new CustomEvent('download-svg', {
      bubbles: true,
      composed: true
    }));
  }

  onDownloadPNG() {
    this.component.dispatchEvent(new CustomEvent('download-png', {
      bubbles: true,
      composed: true
    }));
  }

  onResetData() {
    if (confirm('Are you sure you want to reset all data? This action cannot be undone.')) {
      this.component.dispatchEvent(new CustomEvent('reset-data', {
        bubbles: true,
        composed: true
      }));
    }
  }
}
