import { html } from 'https://esm.sh/lit@2.7.0';

export function BulkUploadViewTemplate(component) {
  return html`
    <div class="bulk-upload">
      <h3>Bulk Upload Furnaces</h3>
      <form class="upload-form" @submit=${component._onSubmit}>
        <input type="file" id="csvFile" name="csvFile" accept=".csv,.xlsx,.xls" required>
        <button type="submit" class="btn">Upload</button>
      </form>
    </div>
  `;
}
