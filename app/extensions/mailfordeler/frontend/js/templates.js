const tableList = `
  <div class="list has-overflow-ellipsis has-hoverable-list-items" style="--length: {{length}}">
    {{#each tables}}
    <div class="list-item" data-table="{{this.id}}">
      <div class="list-item-content">
        <div class="list-item-title">{{this.name}}</div>
      </div>
      <div class="list-item-controls">
        <div class="buttons is-right">
          <button class="button">
            <span class="icon is-small">
              <i class="fas fa-edit"></i>
            </span>
          </button>
        </div>
      </div>
    </div>
    {{/each}}
  </div>
    
  </div>
`;

const addWizardModal = `
  <div class="modal" id="add-wizard-modal">
    <div class="modal-background"></div>
    <div class="modal-card">
      <header class="modal-card-head">
        <p class="modal-card-title">Add Wizard</p>
        <button class="delete" aria-label="close"></button>

      </header>
      <section class="modal-card-body">
        <div class="content">
          <p>Content goes here...</p>
          <div class="field">
            <label class="label">Name</label>
            <div class="control">
              <input class="input" type="text" placeholder="Enter name">
            </div>
          </div>
          <div class="field">
            <label class="label">Description</label>
            <div class="control">
              <textarea class="textarea" placeholder="Enter description"></textarea>
            </div>
          </div>
        </div>
      </section>
      <footer class="modal-card-foot">
        <button class="button is-success">Save changes</button>
        <button class="button">Cancel</button>
      </footer>
    </div>
    <button class="modal-close is-large" aria-label="close"></button>
  </div>
`;

// Export the templates for use in other modules
export { tableList, addWizardModal };