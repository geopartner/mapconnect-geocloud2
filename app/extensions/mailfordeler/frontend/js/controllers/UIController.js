import tableList from '../templates.js';

class UIController {
    constructor() {
        this.mainPane = document.getElementById('main-pane');
        this.tablePane = document.getElementById('table-pane');
        this.eventListeners = [];
        this.init();
    }

    init() {
        // Initialize event listeners
        this.setupEventListeners();

        // Initialize panes
        this.updateMainPane('<button class="js-modal-trigger" data-target="modal">Open JS example modal</button>');
        this.updateTablePane({
            tables: [
                { name: 'Item 1', selected: true },
                { name: 'Item 2' },
                { name: 'Item 3' }
            ]
        });
    }

    setupEventListeners() {
        // Example: Listen for custom events to update panes
        document.addEventListener('update-main-pane', (e) => this.updateMainPane(e.detail));
        document.addEventListener('update-table-pane', (e) => this.updateTablePane(e.detail));
    }

    // Method to update the main pane with new content
    updateMainPane(content) {
        if (!this.mainPane) {
            console.error('Main pane element not found');
            return;
        }
        
        this.mainPane.innerHTML = content;
    }

    // Method to update the table pane with new content
    updateTablePane(data) {
        if (!this.tablePane) {
            console.error('Table pane element not found');
            return;
        }
        
        // Clear existing table content
        this.tablePane.innerHTML = '';
        
        // Compile template and push to table pane
        this.tablePane.innerHTML = Handlebars.compile(tableList)(data);

    }

    // Add a custom event listener
    addListener(eventName, callback) {
        const listener = { eventName, callback };
        document.addEventListener(eventName, callback);
        this.eventListeners.push(listener);
        return listener;
    }

    // Remove a previously added event listener
    removeListener(listener) {
        const index = this.eventListeners.findIndex(l => 
            l.eventName === listener.eventName && l.callback === listener.callback);
        
        if (index !== -1) {
            document.removeEventListener(
                this.eventListeners[index].eventName, 
                this.eventListeners[index].callback
            );
            this.eventListeners.splice(index, 1);
        }
    }

    // Clean up all event listeners
    destroy() {
        this.eventListeners.forEach(listener => {
            document.removeEventListener(listener.eventName, listener.callback);
        });
        this.eventListeners = [];
    }

    // Modal handling methods
    openModal(el) {
        el.classList.add('is-active');
    }

    closeModal(el) {
        el.classList.remove('is-active');
    }

    closeAllModals() {
        (document.querySelectorAll('.modal') || []).forEach((modal) => {
            this.closeModal(modal);
        });
    }

    initModals() {
        // Add click events on buttons to open specific modals
        (document.querySelectorAll('.js-modal-trigger') || []).forEach((trigger) => {
            const modalId = trigger.dataset.target;
            const target = document.getElementById(modalId);

            trigger.addEventListener('click', () => {
                this.openModal(target);
            });
        });

        // Add click events to close parent modals
        (document.querySelectorAll('.modal-background, .modal-close, .modal-card-head .delete, .modal-card-foot .button') || []).forEach((closeEl) => {
            const target = closeEl.closest('.modal');

            closeEl.addEventListener('click', () => {
                this.closeModal(target);
            });
        });

        // Add keyboard event to close all modals
        const escapeListener = (event) => {
            if (event.key === "Escape") {
                this.closeAllModals();
            }
        };
        
        document.addEventListener('keydown', escapeListener);
        this.addListener('keydown', escapeListener);
    }
}

// Export the class
export default UIController;