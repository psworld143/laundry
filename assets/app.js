/**
 * AJAX Application Framework
 * Modern, Clean, Simple
 */

class Ajax {
    static async request(url, options = {}) {
        try {
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                }
            });
            return await response.json();
        } catch (error) {
            console.error('Ajax Error:', error);
            throw error;
        }
    }

    static async get(url) {
        return this.request(url, { method: 'GET' });
    }

    static async post(url, data) {
        return this.request(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    static async put(url, data) {
        return this.request(url, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    static async delete(url, data = {}) {
        return this.request(url, {
            method: 'DELETE',
            body: JSON.stringify(data)
        });
    }
}

// Alert Helper
function alert(message, type = 'success') {
    const colors = {
        success: 'bg-green-50 border-green-500 text-green-800',
        error: 'bg-red-50 border-red-500 text-red-800',
        warning: 'bg-yellow-50 border-yellow-500 text-yellow-800',
        info: 'bg-blue-50 border-blue-500 text-blue-800'
    };
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    const div = document.createElement('div');
    div.className = `${colors[type]} border-l-4 p-4 rounded mb-4 flex items-center`;
    div.innerHTML = `
        <i class="fas ${icons[type]} mr-3"></i>
        <span>${message}</span>
    `;
    
    const container = document.getElementById('page-content');
    container.insertBefore(div, container.firstChild);
    
    setTimeout(() => div.remove(), 5000);
}

// Form Helper
function formData(form) {
    const data = {};
    new FormData(form).forEach((value, key) => {
        data[key] = value;
    });
    return data;
}

// Loading State
function loading(show = true) {
    let loader = document.getElementById('ajax-loader');
    if (!loader) {
        loader = document.createElement('div');
        loader.id = 'ajax-loader';
        loader.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        loader.innerHTML = '<div class="bg-white p-6 rounded-lg"><i class="fas fa-spinner fa-spin text-4xl text-blue-500"></i></div>';
        document.body.appendChild(loader);
    }
    loader.style.display = show ? 'flex' : 'none';
}

// Modal Helper
function modal(id, show = true) {
    const el = document.getElementById(id);
    if (el) {
        el.style.display = show ? 'flex' : 'none';
    }
}

// Export
window.Ajax = Ajax;
window.showAlert = alert;
window.formData = formData;
window.loading = loading;
window.modal = modal;

