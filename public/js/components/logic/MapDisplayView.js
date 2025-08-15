export class MapDisplayViewLogic {
  constructor(component) {
    this.component = component;
  }

  updateMapDisplay() {
    // This function is called when the map or version changes
    // The actual SVG content should be loaded by the parent MapGeneratorView
    // and passed down as a property
    if (this.component.svgContent) {
      // The SVG content is already available, just ensure it's displayed
      this.component.requestUpdate();
    }
  }

  onDownloadSVG() {
    if (this.component.svgContent) {
      const blob = new Blob([this.component.svgContent], { type: 'image/svg+xml' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'map.svg';
      a.click();
      URL.revokeObjectURL(url);
    }
  }

  onDownloadPNG() {
    if (this.component.svgContent) {
      // Convert SVG to PNG using canvas
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');
      const img = new Image();
      
      img.onload = () => {
        canvas.width = img.width;
        canvas.height = img.height;
        ctx.drawImage(img, 0, 0);
        
        canvas.toBlob((blob) => {
          const url = URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = 'map.png';
          a.click();
          URL.revokeObjectURL(url);
        });
      };
      
      const svgBlob = new Blob([this.component.svgContent], { type: 'image/svg+xml' });
      img.src = URL.createObjectURL(svgBlob);
    }
  }
}
