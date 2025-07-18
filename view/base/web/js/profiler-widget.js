/**
 * VelocityDev Developer Tools - JavaScript Profiler Widget
 * Handles dynamic profiler panel creation and updates
 */

class ProfilerWidget {
    constructor() {
        this.requests = [];
        this.currentRequest = null;
        this.storageKey = 'vel-dev-profiler-data';
        this.isDebugEnabled = true;
        this.toolbarVisible = true;
        this.currentPanel = null;
        this.isCollapsed = false; // Add collapsed state
        this.pendingInitialRequest = null; // For initial request before IndexedDB ready
        
        this.init();
    }

    /**
     * Initialize the profiler widget
     */
    init() {
        // Check for API key in URL first (initial handshake)
        this.extractApiKeyFromUrl();
        
        this.initStorage();
        this.interceptHttpRequests();
        this.bindEvents();
        this.debugLog('Profiler widget initialized');
    }

    /**
     * Initialize storage mechanism (IndexedDB > localStorage > cookies)
     */
    initStorage() {
        // Try IndexedDB first (highest capacity)
        if (window.indexedDB) {
            this.storageType = 'indexeddb';
            this.initIndexedDB();
        } else if (window.localStorage) {
            this.storageType = 'localStorage';
            this.loadFromStorage();
        } else {
            this.storageType = 'cookies';
            this.loadFromCookies();
        }
    }

    /**
     * Initialize IndexedDB
     */
    initIndexedDB() {
        const request = indexedDB.open('VelocityDevTools', 1);
        
        request.onerror = () => {
            this.debugLog('IndexedDB failed, falling back to localStorage');
            this.storageType = 'localStorage';
            this.loadFromStorage();
        };
        
        request.onsuccess = (event) => {
            this.db = event.target.result;
            // Clear all previous requests on every page refresh
            const transaction = this.db.transaction(['profilerData'], 'readwrite');
            const store = transaction.objectStore('profilerData');
            store.clear();
            transaction.oncomplete = () => {
                this.debugLog('Cleared all previous requests from IndexedDB on refresh');
                this.loadFromIndexedDB();
                // Save pending initial request if it exists
                if (this.pendingInitialRequest) {
                    this.requests = [this.pendingInitialRequest];
                    this.currentRequest = this.pendingInitialRequest;
                    this.saveToIndexedDB();
                    this.pendingInitialRequest = null;
                    this.updateDisplay();
                }
            };
        };
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('profilerData')) {
                db.createObjectStore('profilerData', { keyPath: 'id' });
            }
        };
    }

    /**
     * Load data from IndexedDB
     */
    loadFromIndexedDB() {
        const transaction = this.db.transaction(['profilerData'], 'readonly');
        const store = transaction.objectStore('profilerData');
        const request = store.getAll();
        
        request.onsuccess = () => {
            const loadedData = request.result || [];
            // Only overwrite existing data if we don't have current data or the loaded data is newer
            if (!this.currentRequest || loadedData.length > 0) {
                this.requests = loadedData;
                // Set current request to the first one if we have data
                if (this.requests.length > 0 && !this.currentRequest) {
                    this.currentRequest = this.requests[0];
                }
                this.debugLog(`Loaded ${this.requests.length} requests from IndexedDB`);
                this.updateDisplay();
            } else {
                this.debugLog(`Skipped IndexedDB load - current data exists and storage is empty`);
            }
        };
    }

    /**
     * Save data to IndexedDB
     */
    saveToIndexedDB() {
        if (!this.db) return;
        
        const transaction = this.db.transaction(['profilerData'], 'readwrite');
        const store = transaction.objectStore('profilerData');
        
        // Clear existing data
        store.clear();
        
        // Save all requests
        this.requests.forEach(request => {
            store.add(request);
        });
        
        this.debugLog(`Saved ${this.requests.length} requests to IndexedDB`);
    }

    /**
     * Load from localStorage
     */
    loadFromStorage() {
        try {
            const data = localStorage.getItem(this.storageKey);
            const loadedData = data ? JSON.parse(data) : [];
            
            // Only overwrite existing data if we don't have current data or the loaded data is newer
            if (!this.currentRequest || loadedData.length > 0) {
                this.requests = loadedData;
                // Set current request to the first one if we have data
                if (this.requests.length > 0 && !this.currentRequest) {
                    this.currentRequest = this.requests[0];
                }
                this.debugLog(`Loaded ${this.requests.length} requests from localStorage`);
                this.updateDisplay();
            } else {
                this.debugLog(`Skipped localStorage load - current data exists and storage is empty`);
            }
        } catch (e) {
            this.debugLog('Failed to load from localStorage: ' + e.message);
            if (!this.currentRequest) {
                this.requests = [];
            }
        }
    }

    /**
     * Save to localStorage
     */
    saveToStorage() {
        try {
            localStorage.setItem(this.storageKey, JSON.stringify(this.requests));
            this.debugLog(`Saved ${this.requests.length} requests to localStorage`);
        } catch (e) {
            this.debugLog('Failed to save to localStorage: ' + e.message);
        }
    }

    /**
     * Load from cookies (fallback)
     */
    loadFromCookies() {
        // Simple cookie storage for minimal data - only reset if no current data
        if (!this.currentRequest) {
            this.requests = [];
        }
        this.debugLog('Using cookie storage (limited functionality)');
    }

    /**
     * Save profiler data
     */
    saveData() {
        // Only save if we have data to save
        if (this.requests.length === 0) {
            this.debugLog('No data to save - skipping');
            return;
        }
        
        switch (this.storageType) {
            case 'indexeddb':
                this.saveToIndexedDB();
                break;
            case 'localStorage':
                this.saveToStorage();
                break;
            case 'cookies':
                // Implement cookie saving if needed
                break;
        }
    }

    /**
     * Add initial page profiler data
     */
    addInitialPageData(profilerData) {
        const requestData = {
            id: 'main-request',
            type: 'initial',
            method: profilerData.request?.method || 'GET',
            url: profilerData.request?.uri || window.location.href,
            timestamp: Date.now(),
            profilerData: profilerData
        };
        
        // Check if we already have this data (avoid duplicates)
        const existingIndex = this.requests.findIndex(req => req.id === requestData.id);
        if (existingIndex >= 0) {
            // Update existing data
            this.requests[existingIndex] = requestData;
        } else {
            // Reset with initial data for new page load
            this.requests = [requestData];
        }
        
        this.currentRequest = requestData;
        // If IndexedDB is not ready, queue the request
        if (this.storageType === 'indexeddb' && !this.db) {
            this.pendingInitialRequest = requestData;
        } else {
            this.saveData();
        }
        
        // Force create/update toolbar
        this.forceUpdateToolbar();
        
        this.debugLog('Added initial page profiler data');
        this.debugLog(`Current requests count: ${this.requests.length}`);
        this.debugLog(`Current request ID: ${this.currentRequest?.id}`);
    }

    /**
     * Add AJAX request profiler data
     */
    addAjaxRequestData(requestInfo, profilerData) {
        const requestData = {
            id: requestInfo.id || `ajax-${Date.now()}`,
            type: 'ajax',
            method: requestInfo.method,
            url: requestInfo.url,
            status: requestInfo.status,
            timestamp: Date.now(),
            duration: requestInfo.duration,
            profilerData: profilerData
        };
        this.requests.push(requestData);
        this.saveData();
        this.updateDisplay(); // update badge and selector together
        this.debugLog(`Added AJAX request profiler data: ${requestData.method} ${requestData.url}`);
    }

    /**
     * Create the main toolbar HTML
     */
    createToolbar() {
        if (document.getElementById('vel-dev-toolbar')) {
            return; // Already exists
        }

        const toolbar = document.createElement('div');
        toolbar.id = 'vel-dev-toolbar';
        toolbar.className = 'vel-dev-toolbar';
        toolbar.innerHTML = this.getToolbarHTML();
        
        document.body.appendChild(toolbar);
        this.bindToolbarEvents();
        
        this.debugLog('Toolbar created');
    }

    /**
     * Get toolbar HTML template
     */
    getToolbarHTML() {
        if (this.isCollapsed) {
            // Show only the floating debugger icon
            return `
                <div class="vel-debugger-icon" style="position: fixed; bottom: 24px; left: 24px; z-index: 1000000; cursor: pointer; background: #2563eb; border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.15);" onclick="velocityDevProfiler.toggleCollapse()" title="Show Profiler">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="#fff" stroke-width="2" stroke-linejoin="round"></path><path d="M2 17L12 22L22 17" stroke="#fff" stroke-width="2" stroke-linejoin="round"></path><path d="M2 12L12 17L22 12" stroke="#fff" stroke-width="2" stroke-linejoin="round"></path></svg>
                </div>
            `;
        }

        const data = this.currentRequest?.profilerData;
        if (!data) return '<div>No profiler data available</div>';

        const overview = data.overview || {};
        const database = data.database || {};
        const performance = data.performance || {};
        const memory = data.memory || {};
        const request = data.request || {};
        const environment = data.environment || {};
        const metadata = data.metadata || {};

        const statusColor = this.getStatusColor(overview.status);

        return `
            <div class="vel-toolbar-header">
                <div class="vel-toolbar-main">
                    <div class="vel-toolbar-title">
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"></path><path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linejoin="round"></path><path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linejoin="round"></path></svg>
                            <div>
                                <strong style="font-size: 1.1em;">Magento Developer Tools</strong><br>
                                <span style="font-size: 0.95em; color: #64748b;">Comprehensive API Profiler</span>
                            </div>
                        </span>
                    </div>
                    <div class="vel-toolbar-panels">
                        <div class="vel-panel-item${this.currentPanel === 'queries' ? ' selected' : ''}" onclick="velocityDevProfiler.togglePanel('queries')">
                            <strong class="vel-panel-count" id="current-queries-count">${database.total_queries || 0}</strong>
                            <span class="vel-panel-label">queries</span>
                            <small class="vel-panel-time" id="current-queries-time">(${database.total_time_formatted || '0ms'})</small>
                        </div>
                        <div class="vel-panel-item${this.currentPanel === 'performance' ? ' selected' : ''}" onclick="velocityDevProfiler.togglePanel('performance')">
                            <strong class="vel-panel-icon">⏱️</strong>
                            <span class="vel-panel-label" id="current-app-time">${performance.application_time || '0ms'}</span>
                            <small class="vel-panel-time">total</small>
                        </div>
                        <div class="vel-panel-item${this.currentPanel === 'memory' ? ' selected' : ''}" onclick="velocityDevProfiler.togglePanel('memory')">
                            <strong class="vel-panel-icon">🧠</strong>
                            <span class="vel-panel-label" id="current-memory-peak">${memory.peak_usage_formatted || '0B'}</span>
                            <small class="vel-panel-time">peak</small>
                        </div>
                        <div class="vel-panel-item${this.currentPanel === 'request' ? ' selected' : ''}" onclick="velocityDevProfiler.togglePanel('request')">
                            <strong class="vel-panel-icon">🌐</strong>
                            <span class="vel-panel-label" id="current-request-method">${request.method || 'GET'}</span>
                            <small class="vel-panel-time" id="current-request-uri">${this.truncateUrl(request.uri)}</small>
                        </div>
                        <div class="vel-panel-item${this.currentPanel === 'environment' ? ' selected' : ''}" onclick="velocityDevProfiler.togglePanel('environment')">
                            <strong class="vel-panel-icon">🖥️</strong>
                            <span class="vel-panel-label">PHP ${environment.php_version || 'N/A'}</span>
                            <small class="vel-panel-time">env</small>
                        </div>
                        <div class="vel-panel-item${this.currentPanel === 'opcache' ? ' selected' : ''}" onclick="velocityDevProfiler.togglePanel('opcache')">
                            <strong class="vel-panel-icon">⚡</strong>
                            <span class="vel-panel-label">${performance.opcache?.enabled ? 'ON' : 'OFF'}</span>
                            <small class="vel-panel-time">opcache</small>
                        </div>
                        <div class="vel-panel-item${this.currentPanel === 'debug' ? ' selected' : ''}" onclick="velocityDevProfiler.togglePanel('debug')">
                            <strong class="vel-panel-icon">🐛</strong>
                            <span class="vel-panel-label">${data.debug_info?.messages?.length || 0}</span>
                            <small class="vel-panel-time">debug</small>
                        </div>
                    </div>
                </div>
                <div class="vel-toolbar-controls">
                    <div class="vel-request-selector-container" style="position: relative;">
                        <select id="vel-request-selector" class="vel-select vel-select-compact">
                            ${this.getRequestSelectorOptions()}
                        </select>
                        <span class="vel-request-badge">${this.requests.length}</span>
                    </div>
                    <button onclick="velocityDevProfiler.clearAllRequests()" class="vel-btn vel-btn-danger" title="Clear HTTP Requests" aria-label="Clear">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                    </button>
                    <button onclick="velocityDevProfiler.toggleCollapse()" class="vel-btn vel-btn-primary" id="toolbar-toggle-btn" title="Collapse Toolbar" aria-label="Collapse">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                </div>
            </div>
            <!-- Collapsible Content -->
            <div id="toolbar-content" class="vel-toolbar-content" style="display: ${this.toolbarVisible ? 'block' : 'none'};">
                <div id="queries-panel" class="vel-profiler-panel" style="display: none;">
                    <div class="vel-panel-header">
                        <h4>🔵 Database Queries (<span id="panel-queries-count">${database.total_queries || 0}</span>)</h4>
                        <button class="vel-panel-toggle" onclick="velocityDevProfiler.togglePanelCollapse('queries-panel')">−</button>
                    </div>
                    <div class="vel-panel-content" id="queries-content">${this.getQueriesPanel(database)}</div>
                </div>
                
                <div id="performance-panel" class="vel-profiler-panel" style="display: none;">
                    <div class="vel-panel-header">
                        <h4>🟢 Performance Metrics</h4>
                        <button class="vel-panel-toggle" onclick="velocityDevProfiler.togglePanelCollapse('performance-panel')">−</button>
                    </div>
                    <div class="vel-panel-content" id="performance-content">${this.getPerformancePanel(performance, data.timers || [])}</div>
                </div>
                
                <div id="memory-panel" class="vel-profiler-panel" style="display: none;">
                    <div class="vel-panel-header">
                        <h4>🟡 Memory Usage</h4>
                        <button class="vel-panel-toggle" onclick="velocityDevProfiler.togglePanelCollapse('memory-panel')">−</button>
                    </div>
                    <div class="vel-panel-content" id="memory-content">${this.getMemoryPanel(memory)}</div>
                </div>
                
                <div id="request-panel" class="vel-profiler-panel" style="display: none;">
                    <div class="vel-panel-header">
                        <h4>🟣 Request Information</h4>
                        <button class="vel-panel-toggle" onclick="velocityDevProfiler.togglePanelCollapse('request-panel')">−</button>
                    </div>
                    <div class="vel-panel-content" id="request-content">${this.getRequestPanel(request)}</div>
                </div>
                
                <div id="environment-panel" class="vel-profiler-panel" style="display: none;">
                    <div class="vel-panel-header">
                        <h4>🖥️ Environment & Configuration</h4>
                        <button class="vel-panel-toggle" onclick="velocityDevProfiler.togglePanelCollapse('environment-panel')">−</button>
                    </div>
                    <div class="vel-panel-content" id="environment-content">${this.getEnvironmentPanel(environment, metadata)}</div>
                </div>
                
                <div id="opcache-panel" class="vel-profiler-panel" style="display: none;">
                    <div class="vel-panel-header">
                        <h4>⚡ OPcache & Server Info</h4>
                        <button class="vel-panel-toggle" onclick="velocityDevProfiler.togglePanelCollapse('opcache-panel')">−</button>
                    </div>
                    <div class="vel-panel-content" id="opcache-content">${this.getOpcachePanel(performance)}</div>
                </div>
                
                <div id="debug-panel" class="vel-profiler-panel" style="display: none;">
                    <div class="vel-panel-header">
                        <h4>🐛 Debug Information (<span id="panel-debug-count">${data.debug_info?.messages?.length || 0}</span> messages)</h4>
                        <button class="vel-panel-toggle" onclick="velocityDevProfiler.togglePanelCollapse('debug-panel')">−</button>
                    </div>
                    <div class="vel-panel-content" id="debug-content">${this.getDebugPanel(data.debug_info || {})}</div>
                </div>
            </div>
        `;
    }

    /**
     * Generate queries panel HTML with full details
     */
    getQueriesPanel(database) {
        if (!database.queries || database.queries.length === 0) {
            return '<div class="vel-empty-state">No queries executed</div>';
        }

        let summaryHtml = `
            <div class="vel-section">
                <h5>Summary</h5>
                <div class="vel-stats-grid">
                    <div class="vel-stat">
                        <span class="vel-stat-label">Total Queries:</span>
                        <span class="vel-stat-value">${database.total_queries || 0}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Total Time:</span>
                        <span class="vel-stat-value">${database.total_time_formatted || '0ms'}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Slow Queries:</span>
                        <span class="vel-stat-value">${database.queries.reduce((count, query) => count + (query.is_slow ? 1 : 0), 0)}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Threshold:</span>
                        <span class="vel-stat-value">${database.slow_query_threshold || '0ms'}</span>
                    </div>
                </div>
            </div>
        `;

        let queriesHtml = '<div class="vel-section"><h5>All Queries</h5>';
        database.queries.forEach((query, index) => {
            const cssClass = query.is_slow ? 'vel-query-slow' : 'vel-query-normal';
            const textColor = query.is_slow ? '#ef4444' : '#3b82f6';
            console.log('query.params',query.params);
            queriesHtml += `
                <div class="vel-query-item ${cssClass}">
                    <div class="vel-query-header">
                        <span class="vel-query-number">#${index + 1}</span>
                        <span style="font-weight: bold;">${query.type || 'QUERY'}</span>
                        <span>${query.time_formatted || (query.time + 'ms')}</span>
                        
                    </div>
                    <div class="vel-query-sql">
                        <pre>${this.escapeHtml(query.query)}</pre>
                    </div>
                    ${query.params ? `
                        <div class="vel-query-params">
                            <strong style="color: ${textColor};">Parameters:</strong> <pre style="color:#000;">${JSON.stringify(query.params)}</pre>
                        </div>
                    ` : ''}
                </div>
            `;
        });
        queriesHtml += '</div>';

        // Query types breakdown
        let typesHtml = '';
        if (database.queries_by_type) {
            typesHtml = `
                <div class="vel-section">
                    <h5>Query Types</h5>
                    <div class="vel-stats-grid">
                        ${Object.entries(database.queries_by_type).map(([type, count]) => `
                            <div class="vel-stat">
                                <span class="vel-stat-label">${type}:</span>
                                <span class="vel-stat-value">${count}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        return summaryHtml + typesHtml + queriesHtml;
    }

    /**
     * Generate enhanced performance panel HTML
     */
    getPerformancePanel(performance, timers) {
        const serverLoad = performance.server_load ? 
            performance.server_load.map(load => Number(load).toFixed(2)).join(', ') : 'N/A';
        
        let timersHtml = '';
        if (timers && Object.keys(timers).length > 0) {
            timersHtml = `
                <div class="vel-section">
                    <h5>Timers</h5>
                    <div class="vel-timers-list">
                        ${Object.entries(timers).map(([name, timer]) => `
                            <div class="vel-timer-item">
                                <span class="vel-timer-name">${name}:</span>
                                <span class="vel-timer-duration">${timer.duration_formatted || timer.duration + 'ms'}</span>
                                <small class="vel-timer-time">${timer.started_at} - ${timer.ended_at}</small>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }
        
        return `
            <div class="vel-section">
                <h5>Application Performance</h5>
                <div class="vel-stats-grid">
                    <div class="vel-stat">
                        <span class="vel-stat-label">Total Time:</span>
                        <span class="vel-stat-value">${performance.application_time || '0ms'}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Bootstrap:</span>
                        <span class="vel-stat-value">${performance.bootstrap_time || '0ms'}</span>
                </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">PHP Version:</span>
                        <span class="vel-stat-value">${performance.php_version || 'N/A'}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Magento Mode:</span>
                        <span class="vel-stat-value">${performance.magento_mode || 'N/A'}</span>
                </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Server Load:</span>
                        <span class="vel-stat-value">${serverLoad}</span>
            </div>
                </div>
            </div>
            ${timersHtml}
        `;
    }

    /**
     * Generate enhanced memory panel HTML
     */
    getMemoryPanel(memory) {
        return `
            <div class="vel-section">
                <h5>Memory Usage Details</h5>
                <div class="vel-stats-grid">
                    <div class="vel-stat">
                        <span class="vel-stat-label">Current Usage:</span>
                        <span class="vel-stat-value" style="color: #f59e0b;">${memory.current_usage_formatted || 'N/A'}</span>
                </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Peak Usage:</span>
                        <span class="vel-stat-value" style="color: #ef4444;">${memory.peak_usage_formatted || 'N/A'}</span>
                </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Memory Limit:</span>
                        <span class="vel-stat-value">${memory.limit || 'N/A'}</span>
                </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Real Usage:</span>
                        <span class="vel-stat-value">${memory.real_usage_formatted || 'N/A'}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Raw Current:</span>
                        <span class="vel-stat-value">${this.formatBytes(memory.current_usage || 0)}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Raw Peak:</span>
                        <span class="vel-stat-value">${this.formatBytes(memory.peak_usage || 0)}</span>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Generate enhanced request panel HTML with full details
     */
    getRequestPanel(request) {
        let headersHtml = '';
        if (request.headers && Object.keys(request.headers).length > 0) {
            headersHtml = `
                <div class="vel-section">
                    <h5>Request Headers</h5>
                    <div class="vel-headers-list">
                        ${Object.entries(request.headers).map(([name, value]) => `
                            <div class="vel-header-item">
                                <span class="vel-header-name">${this.escapeHtml(name)}:</span>
                                <span class="vel-header-value">${this.escapeHtml(String(value))}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        let parametersHtml = '';
        if (request.parameters) {
            const { GET, POST, FILES } = request.parameters;
            parametersHtml = `
                <div class="vel-section">
                    <h5>Request Parameters</h5>
                    ${GET && Object.keys(GET).length > 0 ? `
                        <div class="vel-subsection">
                            <h6>GET Parameters</h6>
                            <div class="vel-params-list">
                                ${Object.entries(GET).map(([key, value]) => `
                                    <div class="vel-param-item">
                                        <span class="vel-param-key">${this.escapeHtml(key)}:</span>
                                        <span class="vel-param-value">${this.escapeHtml(JSON.stringify(value))}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : '<div class="vel-empty-state">No GET parameters</div>'}
                    
                    ${POST && Object.keys(POST).length > 0 ? `
                        <div class="vel-subsection">
                            <h6>POST Parameters</h6>
                            <div class="vel-params-list">
                                ${Object.entries(POST).map(([key, value]) => `
                                    <div class="vel-param-item">
                                        <span class="vel-param-key">${this.escapeHtml(key)}:</span>
                                        <span class="vel-param-value">${this.escapeHtml(JSON.stringify(value))}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : '<div class="vel-empty-state">No POST parameters</div>'}
                    
                    ${FILES && Object.keys(FILES).length > 0 ? `
                        <div class="vel-subsection">
                            <h6>File Uploads</h6>
                            <div class="vel-params-list">
                                ${Object.entries(FILES).map(([key, value]) => `
                                    <div class="vel-param-item">
                                        <span class="vel-param-key">${this.escapeHtml(key)}:</span>
                                        <span class="vel-param-value">${this.escapeHtml(JSON.stringify(value))}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : '<div class="vel-empty-state">No file uploads</div>'}
                </div>
            `;
        }

        let sessionHtml = '';
        if (request.session && Object.keys(request.session).length > 0) {
            sessionHtml = `
                <div class="vel-section">
                    <h5>Session Data</h5>
                    <div class="vel-session-list">
                        ${Object.entries(request.session).map(([key, value]) => `
                            <div class="vel-session-item">
                                <span class="vel-session-key">${this.escapeHtml(key)}:</span>
                                <div class="vel-session-value">
                                    <pre>${this.escapeHtml(JSON.stringify(value, null, 2))}</pre>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        return `
            <div class="vel-section">
                <h5>Request Information</h5>
                <div class="vel-stats-grid">
                    <div class="vel-stat">
                        <span class="vel-stat-label">Method:</span>
                        <span class="vel-stat-value" style="color: #8b5cf6;">${request.method || 'GET'}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">URI:</span>
                        <span class="vel-stat-value">${this.escapeHtml(request.uri || window.location.pathname)}</span>
                </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Full URL:</span>
                        <span class="vel-stat-value">${this.escapeHtml(request.url || window.location.href)}</span>
                </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">IP Address:</span>
                        <span class="vel-stat-value">${request.ip || 'N/A'}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Content Type:</span>
                        <span class="vel-stat-value">${request.content_type || 'N/A'}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">User Agent:</span>
                        <span class="vel-stat-value" style="word-break: break-all;">${this.escapeHtml(request.user_agent || 'N/A')}</span>
                    </div>
                </div>
            </div>
            ${headersHtml}
            ${parametersHtml}
            ${sessionHtml}
        `;
    }

    /**
     * Generate environment panel HTML
     */
    getEnvironmentPanel(environment, metadata) {
        let extensionsHtml = '';
        if (environment.extensions && environment.extensions.length > 0) {
            const chunked = this.chunkArray(environment.extensions, 6);
            extensionsHtml = `
                <div class="vel-section">
                    <h5>PHP Extensions (${environment.extensions.length})</h5>
                    <div class="vel-extensions-grid">
                        ${environment.extensions.map(ext => `
                            <span class="vel-extension-item">${ext}</span>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        return `
            <div class="vel-section">
                <h5>Environment Details</h5>
                <div class="vel-stats-grid">
                    <div class="vel-stat">
                        <span class="vel-stat-label">PHP Version:</span>
                        <span class="vel-stat-value">${environment.php_version || 'N/A'}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Server Software:</span>
                        <span class="vel-stat-value">${environment.server_software || 'N/A'}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Operating System:</span>
                        <span class="vel-stat-value">${environment.operating_system || 'N/A'}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Max Execution Time:</span>
                        <span class="vel-stat-value">${environment.max_execution_time || 'N/A'}s</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Timezone:</span>
                        <span class="vel-stat-value">${environment.timezone || 'N/A'}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Locale:</span>
                        <span class="vel-stat-value" style="word-break: break-all;">${environment.locale || 'N/A'}</span>
                    </div>
                </div>
            </div>
            
            <div class="vel-section">
                <h5>Metadata</h5>
                <div class="vel-stats-grid">
                    <div class="vel-stat">
                        <span class="vel-stat-label">Generated At:</span>
                        <span class="vel-stat-value">${metadata.generated_at || 'N/A'}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Request ID:</span>
                        <span class="vel-stat-value">${metadata.request_id || 'N/A'}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Profiler Version:</span>
                        <span class="vel-stat-value">${metadata.profiler_version || 'N/A'}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Memory Limit Exceeded:</span>
                        <span class="vel-stat-value">${metadata.memory_limit_exceeded ? 'Yes' : 'No'}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Timestamp:</span>
                        <span class="vel-stat-value">${metadata.timestamp || 'N/A'}</span>
                    </div>
                </div>
            </div>
            
            ${extensionsHtml}
        `;
    }

    /**
     * Generate OPcache panel HTML
     */
    getOpcachePanel(performance) {
        const opcache = performance.opcache || {};
        const memory = opcache.memory_usage || {};
        
        return `
            <div class="vel-section">
                <h5>OPcache Status</h5>
                <div class="vel-stats-grid">
                    <div class="vel-stat">
                        <span class="vel-stat-label">Enabled:</span>
                        <span class="vel-stat-value" style="color: ${opcache.enabled ? '#10b981' : '#ef4444'};">
                            ${opcache.enabled ? 'Yes' : 'No'}
                        </span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Hit Rate:</span>
                        <span class="vel-stat-value">${opcache.hit_rate || 'N/A'}%</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Used Memory:</span>
                        <span class="vel-stat-value">${this.formatBytes(memory.used_memory || 0)}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Free Memory:</span>
                        <span class="vel-stat-value">${this.formatBytes(memory.free_memory || 0)}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Wasted Memory:</span>
                        <span class="vel-stat-value">${this.formatBytes(memory.wasted_memory || 0)}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Wasted Percentage:</span>
                        <span class="vel-stat-value">${memory.current_wasted_percentage || 0}%</span>
                    </div>
                </div>
            </div>
            
            <div class="vel-section">
                <h5>Server Load</h5>
                <div class="vel-stats-grid">
                    ${performance.server_load ? performance.server_load.map((load, index) => `
                        <div class="vel-stat">
                            <span class="vel-stat-label">${index === 0 ? '1 min' : index === 1 ? '5 min' : '15 min'}:</span>
                            <span class="vel-stat-value">${Number(load).toFixed(2)}</span>
                        </div>
                    `).join('') : '<div class="vel-empty-state">Server load data not available</div>'}
                </div>
            </div>
        `;
    }

    /**
     * Generate Debug Info panel HTML
     */
    getDebugPanel(debugInfo) {
        const messages = debugInfo.messages || [];

        // Calculate totals and group by level on frontend
        const messagesByLevel = {};
        messages.forEach(msg => {
            const level = msg.level || 'info';
            if (!messagesByLevel[level]) {
                messagesByLevel[level] = [];
            }
            messagesByLevel[level].push(msg);
        });

        let messagesHtml = '';
        if (messages.length === 0) {
            messagesHtml = '<div class="vel-empty-state">No debug messages recorded</div>';
        } else {
            // Render messages by level
            Object.entries(messagesByLevel).forEach(([level, levelMessages]) => {
                const levelColors = {
                    'error': '#ef4444',
                    'warning': '#f59e0b', 
                    'info': '#10b981',
                    'debug': '#6366f1'
                };
                const color = levelColors[level] || '#6b7280';
                const icons = {
                    'error': '🔴',
                    'warning': '🟡',
                    'info': '🔵', 
                    'debug': '🟣'
                };
                const icon = icons[level] || '⚪';
                
                messagesHtml += `
                    <div class="vel-debug-section">
                        <h6 style="color: ${color}; margin: 8px 0 4px 0;">${icon} ${level.toUpperCase()} (${levelMessages.length})</h6>
                        ${this.renderDebugMessages(levelMessages, level)}
                    </div>
                `;
            });
        }

        return `
            <div class="vel-section">
                <h5>📊 Debug Summary</h5>
                <div class="vel-stats-grid">
                    <div class="vel-stat">
                        <span class="vel-stat-label">Total Messages:</span>
                        <span class="vel-stat-value">${messages.length}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Message Levels:</span>
                        <span class="vel-stat-value">${Object.keys(messagesByLevel).length}</span>
                    </div>
                    <div class="vel-stat">
                        <span class="vel-stat-label">Most Common Level:</span>
                        <span class="vel-stat-value">${Object.keys(messagesByLevel).length > 0 ? 
                            Object.entries(messagesByLevel).sort((a, b) => b[1].length - a[1].length)[0][0].toUpperCase() : 'N/A'}</span>
                    </div>
                </div>
            </div>

            <div class="vel-section">
                <h5>📝 Debug Messages</h5>
                <div class="vel-debug-messages">
                    ${messagesHtml}
                </div>
            </div>
        `;
    }

    /**
     * Render debug messages for a specific level
     */
    renderDebugMessages(messages, level) {
        return messages.map((msg, index) => {
            const contextId = `debug-context-${level}-${index}`;
            const hasContext = msg.context && Object.keys(msg.context).length > 0;
            
            return `
            <div class="vel-debug-message ${level}">
                <div class="vel-debug-message-header">
                    <span class="vel-debug-message-level ${level}">${msg.level.toUpperCase()}</span>
                    <span class="vel-debug-message-time">${new Date(msg.timestamp * 1000).toLocaleTimeString()}</span>
                </div>
                <div class="vel-debug-message-text">${this.escapeHtml(msg.message)}</div>
                ${hasContext ? `
                    <div class="vel-debug-context">
                        <div class="vel-context-header" onclick="this.parentElement.classList.toggle('expanded')">
                            <span class="vel-context-toggle">▶</span>
                            <span class="vel-context-label">Context Data (${Object.keys(msg.context).length} items)</span>
                        </div>
                        <div class="vel-context-json">
                            <div class="vel-json-content">${this.renderJsonAsHtml(msg.context)}</div>
                        </div>
                    </div>
                ` : ''}
            </div>
        `}).join('');
    }

    /**
     * Render interactive JSON tree
     * @param {Object} data
     * @returns {string}
     */
    renderJsonAsHtml(data) {
        let html = '<ul class="vel-json-tree">';

        const createNode = (key, value, isArrayElement = false) => {
            const isObject = typeof value === 'object' && value !== null;
            const hasChildren = isObject && Object.keys(value).length > 0;
            let liClass = isObject ? 'vel-json-parent vel-json-leaf' : 'vel-json-leaf';
            if (hasChildren) {
                liClass += ' vel-json-collapsible';
            }

            let nodeHtml = `<li class="${liClass}">`;

            if (hasChildren) {
                nodeHtml += '<span class="vel-json-toggler" onclick="velocityDevProfiler.toggleJsonNode(this.parentElement)"></span>';
            } else {
                nodeHtml += '<span class="vel-json-no-toggler"></span>';
            }

            if (!isArrayElement) {
                nodeHtml += `<span class="vel-json-key">"${this.escapeHtml(key)}": </span>`;
            }

            if (isObject) {
                const isArray = Array.isArray(value);
                nodeHtml += isArray ? '[' : '{';
                if (hasChildren) {
                    nodeHtml += '<ul class="vel-json-nested">';
                    for (const childKey in value) {
                        nodeHtml += createNode(childKey, value[childKey], isArray);
                    }
                    nodeHtml += '</ul>';
                }
                nodeHtml += isArray ? ']' : '}';
            } else {
                if (typeof value === 'string') {
                    nodeHtml += `<span class="vel-json-string">"${this.escapeHtml(value)}"</span>`;
                } else {
                    nodeHtml += `<span class="vel-json-value">${this.escapeHtml(String(value))}</span>`;
                }
            }

            nodeHtml += '</li>';
            return nodeHtml;
        };

        for (const key in data) {
            html += createNode(key, data[key]);
        }

        html += '</ul>';
        return html;
    }

    /**
     * Toggle JSON tree node visibility
     */
    toggleJsonNode(element) {
        element.classList.toggle('expanded');
    }

    /**
     * Get request selector options HTML
     */
    getRequestSelectorOptions() {
        this.debugLog(`Generating selector options for ${this.requests.length} requests`);
        
        if (this.requests.length === 0) {
            this.debugLog('No requests available for selector');
            return '<option value="">No requests available</option>';
        }
        
        return this.requests.map((request, index) => {
            const label = request.type === 'initial' ? 
                'Main Request (Current Page)' : 
                `${request.method} ${this.truncateUrl(request.url)}`;
            
            const isSelected = this.currentRequest && request.id === this.currentRequest.id;
            this.debugLog(`Request ${index}: ${request.id} - ${label} ${isSelected ? '(selected)' : ''}`);
            
            return `<option value="${request.id}" ${isSelected ? 'selected' : ''}>${label}</option>`;
        }).join('');
    }

    /**
     * Bind toolbar events
     */
    bindToolbarEvents() {
        const selector = document.getElementById('vel-request-selector');
        if (selector) {
            selector.addEventListener('change', (e) => {
                this.selectRequest(e.target.value);
            });
            this.debugLog('Toolbar events bound successfully');
        } else {
            this.debugLog('Request selector element not found');
        }
    }

    /**
     * Bind global events
     */
    bindEvents() {
        // Handle window resize to maintain toolbar position
        window.addEventListener('resize', () => {
            this.adjustToolbarPosition();
        });
    }

    /**
     * Intercept HTTP requests to capture profiler data
     */
    interceptHttpRequests() {
        // Intercept XMLHttpRequest
        const originalXhrOpen = XMLHttpRequest.prototype.open;
        const originalXhrSend = XMLHttpRequest.prototype.send;
        
        XMLHttpRequest.prototype.open = function(method, url, ...args) {
            this._method = method;
            this._url = url;
            this._startTime = Date.now();
            this._requestHeaders = {};
            return originalXhrOpen.apply(this, [method, url, ...args]);
        };
        
        XMLHttpRequest.prototype.send = function(body) {
            const xhr = this;
            
            xhr.addEventListener('loadend', function() {
                const endTime = Date.now();
                const requestInfo = {
                    id: `xhr-${endTime}-${Math.random().toString(36).substr(2, 9)}`,
                    method: xhr._method,
                    url: xhr._url,
                    status: xhr.status,
                    duration: endTime - xhr._startTime,
                    startTime: xhr._startTime,
                    endTime: endTime
                };
                
                // Try to extract profiler data from response
                try {
                    const responseText = xhr.responseText;
                    if (responseText) {
                        const responseJson = JSON.parse(responseText);
                        if (responseJson._profiler) {
                            velocityDevProfiler.addAjaxRequestData(requestInfo, responseJson._profiler);
                        }
                    }
                } catch (e) {
                    // Response is not JSON or doesn't contain profiler data
                }
            });
            
            return originalXhrSend.apply(this, [body]);
        };

        // Intercept fetch
        if (window.fetch) {
            const originalFetch = window.fetch;
            window.fetch = function(input, init = {}) {
                const startTime = Date.now();
                const url = typeof input === 'string' ? input : input.url;
                const method = init.method || 'GET';
                
                return originalFetch(input, init).then(response => {
                    const endTime = Date.now();
                    const requestInfo = {
                        id: `fetch-${endTime}-${Math.random().toString(36).substr(2, 9)}`,
                        method: method,
                        url: url,
                        status: response.status,
                        duration: endTime - startTime,
                        startTime: startTime,
                        endTime: endTime
                    };
                    
                    // Try to extract profiler data from response
                    const clonedResponse = response.clone();
                    clonedResponse.text().then(responseText => {
                        try {
                            const responseJson = JSON.parse(responseText);
                            if (responseJson._profiler) {
                                velocityDevProfiler.addAjaxRequestData(requestInfo, responseJson._profiler);
                            }
                        } catch (e) {
                            // Response is not JSON or doesn't contain profiler data
                        }
                    });
                    
                    return response;
                });
            };
        }
    }

    /**
     * Toggle toolbar visibility
     */
    toggleToolbar() {
        this.toolbarVisible = !this.toolbarVisible;
        const content = document.getElementById('toolbar-content');
        if (content) {
            content.style.display = this.toolbarVisible ? 'block' : 'none';
        }
    }

    /**
     * Toggle profiler panel with proper collapse support
     */
    togglePanel(panelName) {
        this.currentPanel = this.currentPanel === panelName ? null : panelName;
        this.updateDisplay();
    }

    /**
     * Toggle panel collapse state
     */
    togglePanelCollapse(panelId) {
        const panel = document.getElementById(panelId);
        if (panel) {
            const content = panel.querySelector('.vel-panel-content');
            const toggle = panel.querySelector('.vel-panel-toggle');
            
            if (content && toggle) {
                const isCollapsed = content.style.display === 'none';
                content.style.display = isCollapsed ? 'block' : 'none';
                toggle.textContent = isCollapsed ? '−' : '+';
            }
        }
    }

    /**
     * Select a request to display
     */
    selectRequest(requestId) {
        const request = this.requests.find(r => r.id === requestId);
        if (request) {
            this.currentRequest = request;
            this.updateDisplay();
        }
    }

    /**
     * Update the display with current request data
     */
    updateDisplay() {
        if (!this.currentRequest) {
            this.debugLog('No current request to display');
            return;
        }
        const toolbar = document.getElementById('vel-dev-toolbar');
        if (toolbar) {
            this.debugLog('Updating toolbar display');
            toolbar.innerHTML = this.getToolbarHTML();
            this.bindToolbarEvents();
        } else {
            this.debugLog('Toolbar element not found, creating new one');
            this.createToolbar();
        }
        
        if (this.currentPanel) {
            const panel = document.getElementById(`${this.currentPanel}-panel`);
            if (panel) {
                panel.style.display = 'block';
            }
        }
    }

    /**
     * Update request selector
     */
    updateRequestSelector() {
        const selector = document.getElementById('vel-request-selector');
        const counter = document.getElementById('vel-request-count');
        
        if (selector) {
            const options = this.getRequestSelectorOptions();
            selector.innerHTML = options;
            this.debugLog('Request selector updated');
        } else {
            this.debugLog('Request selector element not found for update');
        }
        
        if (counter) {
            counter.textContent = this.requests.length;
            this.debugLog(`Request counter updated to ${this.requests.length}`);
        } else {
            this.debugLog('Request counter element not found');
        }
    }

    /**
     * Clear all requests
     */
    clearAllRequests() {
        this.requests = this.requests.filter(r => r.type === 'initial'); // Keep initial request
        this.currentRequest = this.requests[0] || null;
        this.saveData();
        this.updateDisplay();
        this.debugLog('Cleared all AJAX requests');
    }

    /**
     * Utility functions
     */
    getStatusColor(status) {
        switch(status) {
            case 'good': return '#10b981';
            case 'warning': return '#f59e0b';
            case 'error': return '#ef4444';
            default: return '#6b7280';
        }
    }

    truncateUrl(url, maxLength = 20) {
        if (!url) return '';
        return url.length > maxLength ? url.substring(0, maxLength) + '...' : url;
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    adjustToolbarPosition() {
        // Handle responsive adjustments if needed
    }

    debugLog(message) {
        if (this.isDebugEnabled && console) {
            console.log(`[Profiler] ${message}`);
        }
    }

    /**
     * Utility function to format bytes
     */
    formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    /**
     * Utility function to chunk array
     */
    chunkArray(array, chunkSize) {
        const chunks = [];
        for (let i = 0; i < array.length; i += chunkSize) {
            chunks.push(array.slice(i, i + chunkSize));
        }
        return chunks;
    }

    /**
     * Force update/create toolbar
     */
    forceUpdateToolbar() {
        const existingToolbar = document.getElementById('vel-dev-toolbar');
        if (existingToolbar) {
            // Remove existing toolbar
            existingToolbar.remove();
            this.debugLog('Removed existing toolbar for refresh');
        }
        
        // Create new toolbar
        this.createToolbar();
        this.debugLog('Forced toolbar update completed');
    }

    /**
     * Get stored API key from localStorage
     * @returns {string|null}
     */
    getStoredApiKey() {
        try {
            return localStorage.getItem('profiler_api_key');
        } catch (e) {
            console.warn('Failed to read API key from localStorage:', e);
            return null;
        }
    }

    /**
     * Store API key in localStorage
     * @param {string} apiKey 
     */
    storeApiKey(apiKey) {
        try {
            if (apiKey && apiKey.length > 0) {
                localStorage.setItem('profiler_api_key', apiKey);
                this.debugLog('API key stored successfully');
            }
        } catch (e) {
            console.warn('Failed to store API key in localStorage:', e);
        }
    }

    /**
     * Clear stored API key
     */
    clearStoredApiKey() {
        try {
            localStorage.removeItem('profiler_api_key');
            this.debugLog('API key cleared');
        } catch (e) {
            console.warn('Failed to clear API key:', e);
        }
    }

    /**
     * Extract API key from current URL if present
     * @returns {string|null}
     */
    extractApiKeyFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        const apiKey = urlParams.get('api_key');
        
        if (apiKey) {
            // Store it for future use
            this.storeApiKey(apiKey);
            
            // Clean URL by removing api_key parameter (security)
            this.cleanUrlFromApiKey();
        }
        
        return apiKey;
    }

    /**
     * Remove API key from URL for security
     */
    cleanUrlFromApiKey() {
        try {
            const url = new URL(window.location);
            if (url.searchParams.has('api_key')) {
                url.searchParams.delete('api_key');
                window.history.replaceState({}, document.title, url.toString());
                this.debugLog('API key removed from URL');
            }
        } catch (e) {
            console.warn('Failed to clean URL:', e);
        }
    }

    /**
     * Get secure headers for AJAX requests
     * @returns {Object}
     */
    getSecureHeaders() {
        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        };

        // Add CSRF token if available (Magento's built-in security)
        const csrfToken = document.querySelector('input[name="form_key"]')?.value;
        if (csrfToken) {
            headers['X-Magento-Form-Key'] = csrfToken;
        }

        // Add debug mode header (safe to expose)
        headers['X-Debug-Mode'] = '1';

        // Add API key if available
        const apiKey = this.getStoredApiKey();
        if (apiKey) {
            headers['X-Debug-Api-Key'] = apiKey;
        }

        return headers;
    }

    /**
     * Make authenticated AJAX request
     * @param {string} url 
     * @param {Object} options 
     * @returns {Promise}
     */
    async makeSecureRequest(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: this.getSecureHeaders(),
            credentials: 'same-origin' // Include session cookies
        };

        const mergedOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        };

        try {
            const response = await fetch(url, mergedOptions);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        } catch (error) {
            console.error('Secure request failed:', error);
            throw error;
        }
    }

    // Collapse/expand logic
    toggleCollapse() {
        this.isCollapsed = !this.isCollapsed;
        this.updateDisplay();
    }
}

// Initialize global profiler instance
window.velocityDevProfiler = new ProfilerWidget(); 