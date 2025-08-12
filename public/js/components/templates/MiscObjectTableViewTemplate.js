import { html } from 'https://esm.sh/lit@2.7.0';

export function MiscObjectTableViewTemplate(component) {
  return html`
    <div class="misc-object-section">
      <h3>
        Miscellaneous Objects
        <button class="add-object-btn" @click=${component._toggleForm}>
          ${component.showForm ? 'Cancel' : 'Add Object'}
        </button>
      </h3>

      ${component.showForm ? html`
        <misc-object-form @misc-object-submitted=${component._onMiscObjectSubmitted} @form-cancelled=${component._onFormCancelled}></misc-object-form>
      ` : ''}

      <table class="misc-object-table">
        <thead>
          <tr>
            <th>#</th>
            <th>X</th>
            <th>Y</th>
            <th>Size</th>
            <th>Name</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          ${component.miscObjects.length === 0 ? html`
            <tr>
              <td colspan="6" style="text-align: center; color: #6c757d; font-style: italic; padding: 40px;">
                No miscellaneous objects added yet
              </td>
            </tr>
          ` : component.miscObjects.map((object, index) => html`
            <tr>
              <td>${index + 1}.</td>
              <td>${object.x}</td>
              <td>${object.y}</td>
              <td>${object.size}</td>
              <td>${object.name}</td>
              <td class="actionBtns">
                <button @click=${() => component._onEdit(object)}>Edit</button>
                <button @click=${() => component._onDelete(object)}>Delete</button>
              </td>
            </tr>
          `)}
        </tbody>
      </table>
    </div>
  `;
}
