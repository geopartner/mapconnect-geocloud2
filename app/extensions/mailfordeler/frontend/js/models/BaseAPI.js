
/**
 * BaseAPI class provides methods for CRUD operations.
 * This class can be extended by specific API implementations.
 */
class BaseAPI {
    /**
     * @param {string} baseUrl - The base URL for API endpoints
     */
    constructor(baseUrl = '') {
        this.baseUrl = baseUrl;
        this.headers = {
            'Content-Type': 'application/json'
        };
        
        // Include PHPSESSID cookie with requests
        this.cookieName = 'PHPSESSID';
        this.getCookie = () => {
            const cookies = document.cookie.split(';');
            for (let cookie of cookies) {
                const [name, value] = cookie.trim().split('=');
                if (name === this.cookieName) {
                    return value;
                }
            }
            return '';
        };

        // Automatically add the cookie to headers
        const sessionId = this.getCookie();
        if (sessionId) {
            this.headers['Cookie'] = `${this.cookieName}=${sessionId}`;
        }
    }

    /**
     * Fetch data from API
     * @param {string} endpoint - API endpoint
     * @param {Object} options - Fetch options
     * @returns {Promise<any>} Response data
     */
    async fetchData(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const response = await fetch(url, {
            headers: this.headers,
            ...options
        });
        
        if (!response.ok) {
            throw new Error(`API error: ${response.status}`);
        }
        
        return await response.json();
    }

    /**
     * Create a resource
     * @param {string} endpoint - API endpoint
     * @param {Object} data - Resource data
     * @returns {Promise<any>} Created resource
     */
    async create(endpoint, data) {
        return this.fetchData(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    /**
     * Get a resource or list of resources
     * @param {string} endpoint - API endpoint
     * @param {string|number} id - Optional resource ID
     * @returns {Promise<any>} Resource data
     */
    async read(endpoint, id = '') {
        const path = id ? `${endpoint}/${id}` : endpoint;
        return this.fetchData(path, {
            method: 'GET'
        });
    }

    /**
     * Update a resource
     * @param {string} endpoint - API endpoint
     * @param {string|number} id - Resource ID
     * @param {Object} data - Updated resource data
     * @returns {Promise<any>} Updated resource
     */
    async update(endpoint, id, data) {
        return this.fetchData(`${endpoint}/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    /**
     * Partially update a resource
     * @param {string} endpoint - API endpoint
     * @param {string|number} id - Resource ID
     * @param {Object} data - Updated resource data
     * @returns {Promise<any>} Updated resource
     */
    async patch(endpoint, id, data) {
        return this.fetchData(`${endpoint}/${id}`, {
            method: 'PATCH',
            body: JSON.stringify(data)
        });
    }

    /**
     * Delete a resource
     * @param {string} endpoint - API endpoint
     * @param {string|number} id - Resource ID
     * @returns {Promise<any>} Deletion result
     */
    async delete(endpoint, id) {
        return this.fetchData(`${endpoint}/${id}`, {
            method: 'DELETE'
        });
    }
}