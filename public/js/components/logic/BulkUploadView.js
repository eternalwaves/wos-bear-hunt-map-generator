export class BulkUploadViewLogic {
  constructor(component) {
    this.component = component;
  }

  onSubmit(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const file = formData.get('csv_file');
    
    if (file) {
      this.component.uploading = true;
      
      const reader = new FileReader();
      reader.onload = (e) => {
        const content = e.target.result;
        this.parseAndUpload(content);
      };
      reader.readAsText(file);
    }
  }

  parseAndUpload(content) {
    try {
      const lines = content.split('\n');
      const headers = lines[0].split(',').map(h => h.trim());
      const furnaces = [];
      
      for (let i = 1; i < lines.length; i++) {
        if (lines[i].trim()) {
          const values = lines[i].split(',').map(v => v.trim());
          const furnace = {};
          
          headers.forEach((header, index) => {
            furnace[header.toLowerCase()] = values[index] || '';
          });
          
          furnaces.push(furnace);
        }
      }
      
      this.component.dispatchEvent(new CustomEvent('furnaces-uploaded', {
        detail: furnaces,
        bubbles: true,
        composed: true
      }));
      
    } catch (error) {
      console.error('Error parsing file:', error);
      alert('Error parsing file. Please check the format.');
    } finally {
      this.component.uploading = false;
    }
  }

  onDownloadTemplate() {
    const template = `Name,Level,Power,Rank,Participation,TrapPref,X,Y
Furnace1,50,1000,1,80,Fire,10.5,20.3
Furnace2,45,900,2,75,Ice,15.2,25.1`;
    
    const blob = new Blob([template], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'furnace_template.csv';
    a.click();
    URL.revokeObjectURL(url);
  }
}
