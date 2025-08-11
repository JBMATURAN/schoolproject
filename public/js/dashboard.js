/**
 * Computer Lab Inventory Dashboard JavaScript
 * Handles all client-side functionality with AJAX operations
 */

class InventoryDashboard {
    constructor() {
        this.currentSection = 'dashboard';
        this.equipmentData = [];
        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.filters = {};
        this.charts = {};
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadDashboardData();
        this.setupCharts();
    }
    
    setupEventListeners() {
        // Navigation
        document.querySelectorAll('[data-section]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = e.target.closest('[data-section]').dataset.section;
                this.showSection(section);
            });
        });
        
        // Search functionality
        const searchInput = document.getElementById('search-equipment');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce((e) => {
                this.filters.search = e.target.value;
                this.loadEquipmentData();
            }, 300));
        }
        
        // Filter changes
        ['filter-status', 'filter-category', 'filter-room'].forEach(filterId => {
            const element = document.getElementById(filterId);
            if (element) {
                element.addEventListener('change', (e) => {
                    const filterType = filterId.replace('filter-', '');
                    this.filters[filterType] = e.target.value;
                    this.loadEquipmentData();
                });
            }
        });
        
        // Form submissions
        const addEquipmentForm = document.getElementById('add-equipment-form');
        if (addEquipmentForm) {
            addEquipmentForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveEquipment();
            });
        }
    }
    
    showSection(sectionName) {
        // Update navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        
        document.querySelector(`[data-section="${sectionName}"]`).classList.add('active');
        
        // Show section content
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.remove('active');
        });
        
        document.getElementById(`${sectionName}-section`).classList.add('active');
        
        this.currentSection = sectionName;
        
        // Load section-specific data
        switch (sectionName) {
            case 'equipment':
                this.loadEquipmentData();
                break;
            case 'maintenance':
                this.loadMaintenanceData();
                break;
            case 'reports':
                this.loadReportsData();
                break;
        }
    }
    
    async loadDashboardData() {
        try {
            this.showLoading(['total-equipment', 'available-equipment', 'inuse-equipment', 'attention-equipment']);
            
            const response = await this.apiCall('/api/dashboard-stats.php');
            
            if (response.success) {
                const stats = response.data;
                
                document.getElementById('total-equipment').textContent = stats.total || 0;
                document.getElementById('available-equipment').textContent = stats.by_status?.available || 0;
                document.getElementById('inuse-equipment').textContent = stats.by_status?.in_use || 0;
                
                const attention = (stats.by_status?.maintenance || 0) + 
                                (stats.by_status?.repair || 0) + 
                                (stats.by_status?.broken || 0);
                document.getElementById('attention-equipment').textContent = attention;
                
                this.updateStatusChart(stats.by_status || {});
            }
        } catch (error) {
            console.error('Error loading dashboard data:', error);
            this.showError('Failed to load dashboard data');
        }
    }
    
    async loadEquipmentData() {
        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                limit: this.itemsPerPage,
                ...this.filters
            });
            
            const response = await this.apiCall(`/api/equipment.php?${params}`);
            
            if (response.success) {
                this.equipmentData = response.data.equipment || [];
                this.renderEquipmentTable();
                this.renderPagination(response.data.pagination || {});
            }
        } catch (error) {
            console.error('Error loading equipment data:', error);
            this.showError('Failed to load equipment data');
        }
    }
    
    renderEquipmentTable() {
        const tbody = document.getElementById('equipment-table-body');
        if (!tbody) return;
        
        if (this.equipmentData.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        No equipment found
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = this.equipmentData.map(equipment => `
            <tr>
                <td>
                    <strong>${this.escapeHtml(equipment.asset_tag)}</strong>
                    ${equipment.qr_code ? `<i class="bi bi-qr-code ms-1" title="QR Code Available"></i>` : ''}
                </td>
                <td>
                    <div class="fw-bold">${this.escapeHtml(equipment.name)}</div>
                    <small class="text-muted">${this.escapeHtml(equipment.model || '')}</small>
                </td>
                <td>${this.escapeHtml(equipment.category_name || '')}</td>
                <td>${this.escapeHtml(equipment.room_name || 'Unassigned')}</td>
                <td>
                    <span class="status-badge status-${equipment.status}">
                        ${this.formatStatus(equipment.status)}
                    </span>
                </td>
                <td>
                    <span class="status-badge condition-${equipment.condition_status}">
                        ${this.formatCondition(equipment.condition_status)}
                    </span>
                </td>
                <td>${this.escapeHtml(equipment.assigned_to_name || 'Unassigned')}</td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-outline-primary btn-sm" onclick="inventory.viewEquipment(${equipment.id})" title="View Details">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="inventory.editEquipment(${equipment.id})" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-success btn-sm" onclick="inventory.moveEquipment(${equipment.id})" title="Move">
                            <i class="bi bi-arrow-right"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="inventory.deleteEquipment(${equipment.id})" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }
    
    renderPagination(pagination) {
        const paginationContainer = document.getElementById('equipment-pagination');
        if (!paginationContainer || !pagination.total_pages) return;
        
        const { current_page, total_pages, has_prev, has_next } = pagination;
        
        let paginationHtml = '';
        
        // Previous button
        paginationHtml += `
            <li class="page-item ${!has_prev ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="inventory.changePage(${current_page - 1})">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
        `;
        
        // Page numbers
        const startPage = Math.max(1, current_page - 2);
        const endPage = Math.min(total_pages, current_page + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `
                <li class="page-item ${i === current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="inventory.changePage(${i})">${i}</a>
                </li>
            `;
        }
        
        // Next button
        paginationHtml += `
            <li class="page-item ${!has_next ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="inventory.changePage(${current_page + 1})">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        `;
        
        paginationContainer.innerHTML = paginationHtml;
    }
    
    changePage(page) {
        this.currentPage = page;
        this.loadEquipmentData();
    }
    
    clearFilters() {
        this.filters = {};
        this.currentPage = 1;
        
        // Reset form elements
        document.getElementById('search-equipment').value = '';
        document.getElementById('filter-status').value = '';
        document.getElementById('filter-category').value = '';
        document.getElementById('filter-room').value = '';
        
        this.loadEquipmentData();
    }
    
    showAddEquipmentModal() {
        const modal = new bootstrap.Modal(document.getElementById('addEquipmentModal'));
        modal.show();
    }
    
    async saveEquipment() {
        const form = document.getElementById('add-equipment-form');
        const formData = new FormData(form);
        
        try {
            this.showButtonLoading('save-equipment-btn');
            
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            const response = await this.apiCall('/api/equipment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            if (response.success) {
                this.showSuccess('Equipment added successfully');
                bootstrap.Modal.getInstance(document.getElementById('addEquipmentModal')).hide();
                form.reset();
                this.loadEquipmentData();
                this.loadDashboardData();
            } else {
                this.showError(response.message || 'Failed to add equipment');
            }
        } catch (error) {
            console.error('Error saving equipment:', error);
            this.showError('Failed to save equipment');
        } finally {
            this.hideButtonLoading('save-equipment-btn');
        }
    }
    
    async viewEquipment(id) {
        try {
            const response = await this.apiCall(`/api/equipment.php?id=${id}`);
            
            if (response.success) {
                this.showEquipmentModal(response.data);
            }
        } catch (error) {
            console.error('Error viewing equipment:', error);
            this.showError('Failed to load equipment details');
        }
    }
    
    async editEquipment(id) {
        // Implementation for edit equipment
        console.log('Edit equipment:', id);
    }
    
    async moveEquipment(id) {
        // Implementation for move equipment
        console.log('Move equipment:', id);
    }
    
    async deleteEquipment(id) {
        if (!confirm('Are you sure you want to delete this equipment?')) {
            return;
        }
        
        try {
            const response = await this.apiCall(`/api/equipment.php?id=${id}`, {
                method: 'DELETE'
            });
            
            if (response.success) {
                this.showSuccess('Equipment deleted successfully');
                this.loadEquipmentData();
                this.loadDashboardData();
            } else {
                this.showError(response.message || 'Failed to delete equipment');
            }
        } catch (error) {
            console.error('Error deleting equipment:', error);
            this.showError('Failed to delete equipment');
        }
    }
    
    exportEquipment() {
        const params = new URLSearchParams({
            export: 'csv',
            ...this.filters
        });
        
        window.open(`/api/equipment.php?${params}`, '_blank');
    }
    
    setupCharts() {
        const ctx = document.getElementById('statusChart');
        if (!ctx) return;
        
        this.charts.statusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Available', 'In Use', 'Maintenance', 'Repair', 'Retired'],
                datasets: [{
                    data: [0, 0, 0, 0, 0],
                    backgroundColor: [
                        '#1cc88a',
                        '#f6c23e',
                        '#36b9cc',
                        '#e74a3b',
                        '#858796'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }
    
    updateStatusChart(statusData) {
        if (!this.charts.statusChart) return;
        
        const data = [
            statusData.available || 0,
            statusData.in_use || 0,
            statusData.maintenance || 0,
            statusData.repair || 0,
            statusData.retired || 0
        ];
        
        this.charts.statusChart.data.datasets[0].data = data;
        this.charts.statusChart.update();
    }
    
    async apiCall(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        const response = await fetch(url, { ...defaultOptions, ...options });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    }
    
    // Utility functions
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    
    formatStatus(status) {
        const statusMap = {
            'available': 'Available',
            'in_use': 'In Use',
            'maintenance': 'Maintenance',
            'repair': 'Repair',
            'retired': 'Retired',
            'lost': 'Lost',
            'stolen': 'Stolen'
        };
        return statusMap[status] || status;
    }
    
    formatCondition(condition) {
        const conditionMap = {
            'excellent': 'Excellent',
            'good': 'Good',
            'fair': 'Fair',
            'poor': 'Poor',
            'broken': 'Broken'
        };
        return conditionMap[condition] || condition;
    }
    
    showLoading(elementIds) {
        if (!Array.isArray(elementIds)) {
            elementIds = [elementIds];
        }
        
        elementIds.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.innerHTML = '<i class="bi bi-hourglass-split"></i>';
            }
        });
    }
    
    showButtonLoading(buttonId) {
        const button = document.getElementById(buttonId);
        if (button) {
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
            button.disabled = true;
        }
    }
    
    hideButtonLoading(buttonId) {
        const button = document.getElementById(buttonId);
        if (button) {
            button.innerHTML = '<i class="bi bi-save me-1"></i>Save Equipment';
            button.disabled = false;
        }
    }
    
    showSuccess(message) {
        this.showToast(message, 'success');
    }
    
    showError(message) {
        this.showToast(message, 'danger');
    }
    
    showToast(message, type = 'info') {
        // Create toast container if it doesn't exist
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }
        
        // Create toast
        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast" role="alert">
                <div class="toast-header bg-${type} text-white">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                    <strong class="me-auto">${type === 'success' ? 'Success' : type === 'danger' ? 'Error' : 'Info'}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        // Remove toast after it's hidden
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }
    
    loadMaintenanceData() {
        // Placeholder for maintenance data loading
        console.log('Loading maintenance data...');
    }
    
    loadReportsData() {
        // Placeholder for reports data loading
        console.log('Loading reports data...');
    }
}

// Global functions for inline event handlers
window.showAddEquipmentModal = () => inventory.showAddEquipmentModal();
window.saveEquipment = () => inventory.saveEquipment();
window.clearFilters = () => inventory.clearFilters();
window.exportEquipment = () => inventory.exportEquipment();
window.logout = () => {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '/logout.php';
    }
};

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.inventory = new InventoryDashboard();
});

// Additional utility functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount || 0);
}

function formatDate(dateString) {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString();
}

function formatDateTime(dateString) {
    if (!dateString) return '';
    return new Date(dateString).toLocaleString();
}

// Service Worker registration for offline support (optional)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('SW registered: ', registration);
            })
            .catch(registrationError => {
                console.log('SW registration failed: ', registrationError);
            });
    });
}