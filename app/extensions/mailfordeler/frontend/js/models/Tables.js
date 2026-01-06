/**
 * Tables.js
 * Model for handling table operations using the BaseAPI
 */

define([
    'models/BaseAPI'
], function (BaseAPI) {
    'use strict';

    /**
     * Tables API model
     * @extends BaseAPI
     */
    const TablesAPI = BaseAPI.extend({
        /**
         * Initialize the Tables API
         * @param {Object} options - Configuration options
         */
        initialize: function (options) {
            this.options = options || {};
            BaseAPI.prototype.initialize.call(this, this.options);
            
            // Set the base endpoint for table operations
            this.endpoint = 'tables';
        },

        /**
         * Get all tables
         * @returns {Promise} - Promise resolving with tables data
         */
        getTables: function () {
            return this.get();
        },

        /**
         * Get a specific table by ID
         * @param {string|number} id - Table ID
         * @returns {Promise} - Promise resolving with table data
         */
        getTable: function (id) {
            return this.get(id);
        },

        /**
         * Create a new table
         * @param {Object} tableData - Table data to create
         * @returns {Promise} - Promise resolving with created table
         */
        createTable: function (tableData) {
            return this.post('', tableData);
        },

        /**
         * Update an existing table
         * @param {string|number} id - Table ID to update
         * @param {Object} tableData - Updated table data
         * @returns {Promise} - Promise resolving with updated table
         */
        updateTable: function (id, tableData) {
            return this.put(id, tableData);
        },

        /**
         * Delete a table
         * @param {string|number} id - Table ID to delete
         * @returns {Promise} - Promise resolving with deletion result
         */
        deleteTable: function (id) {
            return this.delete(id);
        }
    });

    return TablesAPI;
});

