# BitBabit Developer Tools for Magento 2

[![Latest Stable Version](https://poser.pugx.org/bitbabit/magento2-dev-tools/v/stable)](https://packagist.org/packages/bitbabit/magento2-dev-tools)
[![Total Downloads](https://poser.pugx.org/bitbabit/magento2-dev-tools/downloads)](https://packagist.org/packages/bitbabit/magento2-dev-tools)
[![License](https://poser.pugx.org/bitbabit/magento2-dev-tools/license)](https://packagist.org/packages/bitbabit/magento2-dev-tools)
[![PHP Version Require](https://poser.pugx.org/bitbabit/magento2-dev-tools/require/php)](https://packagist.org/packages/bitbabit/magento2-dev-tools)
[![Magento](https://img.shields.io/badge/magento-2.4+-orange.svg)](https://magento.com)
[![GitHub Stars](https://img.shields.io/github/stars/bitbabit/magento2-dev-tools.svg)](https://github.com/bitbabit/magento2-dev-tools/stargazers)
[![GitHub Issues](https://img.shields.io/github/issues/bitbabit/magento2-dev-tools.svg)](https://github.com/bitbabit/magento2-dev-tools/issues)

## Overview

BitBabit Developer Tools is a comprehensive development and debugging suite for Magento 2 that provides real-time performance monitoring, database query profiling, and advanced debugging capabilities. It features a modern, interactive web-based toolbar similar to Symfony's DebugBar or Laravel Debugbar.

## Key Features

### 🔍 **Database Query Profiling**
- Real-time SQL query monitoring with execution times
- Slow query detection and highlighting
- Query parameter binding visualization
- Query type analysis (SELECT, INSERT, UPDATE, DELETE)
- Comprehensive query statistics

### ⚡ **Performance Monitoring**
- Application execution time tracking
- Memory usage monitoring (current, peak, real usage)
- Bootstrap time measurement
- Server load monitoring
- OPcache status and statistics

### 🛡️ **Security & Authentication**
- API key-based authentication system
- Secure header validation (`X-Debug-Api-Key`)
- Developer mode restrictions
- Cookie-based session management

### 🌐 **Request Analysis**
- HTTP request/response monitoring
- Header inspection
- Parameter analysis (GET, POST, FILES)
- Session data examination
- AJAX request interception

### 🖥️ **Environment Information**
- PHP version and configuration
- Server software details
- Operating system information
- PHP extensions listing
- Timezone and locale settings

### 🐛 **Advanced Debugging**
- Multi-level debug logging (info, warning, error, debug)
- Contextual debug messages
- Interactive JSON tree visualization
- Real-time message collection

### 📱 **Interactive Web Interface**
- Modern, responsive toolbar widget
- Real-time data updates
- Collapsible panels
- Multi-request tracking
- Mobile-friendly design

## Installation

### Via Composer (Recommended)

```bash
composer require bitbabit/magento2-dev-tools
bin/magento module:enable BitBabit_DeveloperTools
bin/magento setup:upgrade
php bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
```

If the package is not yet published to Packagist, you can install with explicit version:

```bash
composer require bitbabit/magento2-dev-tools:^1.2.0
bin/magento module:enable BitBabit_DeveloperTools
bin/magento setup:upgrade
php bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
```

### Manual Installation

1. Download the module files
2. Extract to `app/code/BitBabit/DeveloperTools/`
3. Run the following commands:

```bash
bin/magento module:enable BitBabit_DeveloperTools
bin/magento setup:upgrade
php bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
```

## Quick Setup

After installation, follow these steps to get started:

```bash
# Enable the profiler
bin/magento bitbabit:bitbabit:profiler:enable

# Generate API key for frontend widget
bin/magento bitbabit:devtools:generate-api-key

# Copy the generated API key for frontend widget usage

# Clear cache
bin/magento cache:flush
```

### Monolithic Frontend Widget Activation

To enable the profiler widget and save the API key to cookies for persistent profiling:

```
https://YOUR_FRONTEND_URL?api_key=YOUR_GENERATED_API_KEY
```

This will:
- Enable the profiler widget on your frontend
- Save the API key to cookies for automatic profiling on subsequent requests
- Allow you to see the profiler widget on all pages

**Note**: Boot time and memory management features are currently under development, so data may not be 100% accurate.

## Configuration

### Admin Configuration

Navigate to **Stores → Configuration → BitBabit → Developer Tools**

#### Basic Settings
- **Enable Developer Tools**: Master toggle for the entire module
- **Profiler Header Key**: HTTP header to trigger profiling (default: `X-Debug-Mode`)
- **Enable HTML Output**: Show profiler data for web requests
- **Enable JSON Injection**: Inject profiler data into API responses
- **Developer Mode Only**: Restrict profiling to developer mode
- **Slow Query Threshold**: Highlight queries exceeding this time (ms)
- **Toolbar Widget**: Enable/disable the floating toolbar
- **Memory Limit**: Auto-disable threshold to prevent memory issues

#### API Security Settings
- **Enable API Key Validation**: Require API key for access
- **API Key**: Encrypted storage of the authentication key

### Console Commands

#### Enable/Disable Profiler
```bash
# Enable profiler
bin/magento bitbabit:profiler:enable

# Disable profiler  
bin/magento bitbabit:profiler:disable

# Check status
bin/magento bitbabit:profiler:status
```

#### API Key Management
```bash
# Generate new API key
bin/magento bitbabit:devtools:generate-api-key

# Regenerate existing API key
bin/magento bitbabit:devtools:generate-api-key --regenerate
```

## Usage

### Basic Usage

1. **Enable the module** in admin configuration
2. **Generate an API key** using the console command
3. **Configure your browser** to send the debug header:
   - Header: `X-Debug-Api-Key`
   - Value: Your generated API key
4. **Visit any frontend page** to see the toolbar

### Browser Extension Integration

The module is designed to work with browser extensions that can inject custom headers:

```javascript
// Example browser extension configuration
headers: {
  'X-Debug-Mode': '1',
  'X-Debug-Api-Key': 'your-generated-api-key'
}
```

### Programmatic Debug Logging

```php
<?php
use BitBabit\DeveloperTools\Helper\Debug;

// Basic logging
Debug::info('Processing order', ['order_id' => 123]);
Debug::warning('Low stock detected', ['product_id' => 456]);
Debug::error('Payment failed', ['error' => $exception->getMessage()]);

// Variable dumping
Debug::dump($complexArray, 'Order Data');

// Timer usage (legacy methods)
Debug::startTimer('product-load');
// ... your code ...
Debug::endTimer('product-load', 'Product loading completed');
```

### API Integration

For AJAX requests, profiler data is automatically injected into JSON responses:

```javascript
// AJAX response with profiler data
{
  "data": { /* your response data */ },
  "_profiler": {
    "database": { /* query information */ },
    "performance": { /* timing data */ },
    "memory": { /* memory usage */ }
  }
}
```

## Architecture

### Core Components

#### Interfaces
- **`ProfilerConfigInterface`**: Main configuration contract defining all settings and validation methods

#### Models
- **`ProfilerConfig`**: Configuration implementation with scope integration
- **`DebugInfo`**: Singleton for collecting debug messages

#### Services
- **`ComprehensiveProfilerService`**: Main profiling engine collecting all data
- **`ApiKeyCookieManagerService`**: Secure API key management with cookie support

#### Console Commands
- **`EnableCommand`**: Enable profiler via CLI
- **`DisableCommand`**: Disable profiler via CLI  
- **`StatusCommand`**: Show current profiler status
- **`GenerateApiKeyCommand`**: API key generation and management

#### Observers & Plugins
- **`ResponseObserver`**: Injects profiler data into responses
- **`HttpLaunchPlugin`**: Initializes profiling for each request

#### Frontend Components
- **`profiler-widget.js`**: Interactive JavaScript toolbar
- **`profiler-widget.css`**: Responsive styling

### Data Flow

1. **Request Initialization**: `HttpLaunchPlugin` checks if profiling should be enabled
2. **Data Collection**: `ComprehensiveProfilerService` gathers metrics throughout request
3. **Response Injection**: `ResponseObserver` adds profiler data to output
4. **Frontend Display**: JavaScript widget renders interactive toolbar

### Security Features

- **API Key Authentication**: Secure access control
- **Timing-Safe Comparison**: Prevents timing attacks
- **Developer Mode Restriction**: Production safety
- **Cookie Security**: Secure, HTTP-only cookie handling

## Data Structure

### Profiler Data Schema

```json
{
  "overview": {
    "status": "good|warning|error",
    "total_queries": 25,
    "total_time": "1.234s"
  },
  "database": {
    "total_queries": 25,
    "total_time_formatted": "145.67ms",
    "slow_query_threshold": "100ms",
    "queries": [
      {
        "query": "SELECT * FROM catalog_product_entity WHERE ...",
        "time": 45.67,
        "time_formatted": "45.67ms",
        "type": "SELECT",
        "is_slow": false,
        "params": ["value1", "value2"]
      }
    ],
    "queries_by_type": {
      "SELECT": 20,
      "INSERT": 3,
      "UPDATE": 2
    }
  },
  "performance": {
    "application_time": "1.234s",
    "bootstrap_time": "0.123s",
    "php_version": "8.3.0",
    "magento_mode": "developer",
    "server_load": [0.5, 0.7, 0.9],
    "opcache": {
      "enabled": true,
      "hit_rate": 95.5,
      "memory_usage": {
        "used_memory": 67108864,
        "free_memory": 33554432,
        "wasted_memory": 1048576
      }
    }
  },
  "memory": {
    "current_usage": 16777216,
    "current_usage_formatted": "16.0 MB",
    "peak_usage": 20971520,
    "peak_usage_formatted": "20.0 MB",
    "real_usage_formatted": "18.5 MB",
    "limit": "512M"
  },
  "request": {
    "method": "GET",
    "uri": "/catalog/product/view/id/123",
    "url": "https://example.com/catalog/product/view/id/123",
    "ip": "192.168.1.100",
    "content_type": "text/html",
    "user_agent": "Mozilla/5.0...",
    "headers": {
      "Host": "example.com",
      "X-Debug-Mode": "1"
    },
    "parameters": {
      "GET": {"id": "123"},
      "POST": {},
      "FILES": {}
    },
    "session": {
      "session_id": "abc123",
      "customer_id": null
    }
  },
  "environment": {
    "php_version": "8.3.0",
    "server_software": "nginx/1.20.0",
    "operating_system": "Linux",
    "max_execution_time": 30,
    "timezone": "UTC",
    "locale": "en_US.UTF-8",
    "extensions": ["json", "mysqli", "gd", "curl"]
  },
  "metadata": {
    "generated_at": "2024-01-15 10:30:45",
    "request_id": "req_123456789",
    "profiler_version": "1.2.0",
    "memory_limit_exceeded": false,
    "timestamp": 1705315845
  },
  "debug_info": {
    "messages": [
      {
        "message": "Order processing started",
        "level": "info",
        "context": {"order_id": 123},
        "timestamp": 1705315845.123
      }
    ]
  },
  "timers": {
    "product-load": {
      "duration": 45.67,
      "duration_formatted": "45.67ms",
      "started_at": "10:30:45.123",
      "ended_at": "10:30:45.169"
    }
  }
}
```

## Frontend Interface

### Toolbar Panels

1. **Database Queries**: SQL query analysis with parameters and timing
2. **Performance**: Execution times, server load, OPcache status  
3. **Memory**: Current, peak, and real memory usage statistics
4. **Request**: HTTP request details, headers, parameters, session
5. **Environment**: PHP info, server details, extensions
6. **OPcache**: Cache statistics and memory usage
7. **Debug**: Custom debug messages with context data

### Interactive Features

- **Collapsible panels** for organized data viewing
- **Multi-request tracking** for AJAX monitoring
- **Real-time updates** without page refresh
- **JSON tree visualization** for complex data
- **Responsive design** for mobile debugging
- **Request selector** to switch between tracked requests

## Browser Extension Support

### Chrome Extension Integration

```javascript
// manifest.json permissions
"permissions": [
  "webRequest",
  "webRequestBlocking",
  "*://*/*"
]

// Background script example
chrome.webRequest.onBeforeSendHeaders.addListener(
  function(details) {
    details.requestHeaders.push({
      name: 'X-Debug-Mode',
      value: '1'
    });
    details.requestHeaders.push({
      name: 'X-Debug-Api-Key', 
      value: localStorage.getItem('debug_api_key')
    });
    return {requestHeaders: details.requestHeaders};
  },
  {urls: ["*://*/*"]},
  ["blocking", "requestHeaders"]
);
```

## Performance Considerations

### Memory Management
- Automatic profiler disable when memory limit exceeded
- Configurable memory thresholds
- Efficient data structure usage
- Storage cleanup on page refresh

### Storage Options
- **IndexedDB**: Primary storage (highest capacity)
- **localStorage**: Fallback option
- **Cookies**: Minimal fallback for basic functionality

### Query Optimization
- Minimal performance overhead
- Optional profiling activation
- Selective data collection
- Efficient query parameter handling

## Security Considerations

### API Key Security
- Cryptographically secure key generation
- Timing-safe string comparison
- Secure cookie attributes
- URL parameter cleanup

### Access Control
- Developer mode restrictions
- API key authentication
- Request header validation

### Data Privacy
- Limited sensitive data collection (session data capped at 10 items)
- Configurable data exposure levels
- Secure transmission headers
- Memory usage limits to prevent resource exhaustion

## Troubleshooting

### Common Issues

#### Toolbar Not Appearing
1. Check if module is enabled: `bin/magento module:status BitBabit_DeveloperTools`
2. Verify API key configuration
3. Ensure correct headers are sent
4. Check browser console for JavaScript errors

#### Performance Issues
1. Increase memory limits
2. Disable in production environments
3. Reduce slow query threshold
4. Monitor server resources

#### API Key Problems
1. Regenerate API key: `bin/magento bitbabit:devtools:generate-api-key --regenerate`
2. Clear browser storage
3. Verify header transmission
4. Check admin configuration

### Debug Mode

Enable debug logging in the JavaScript widget:

```javascript
// In browser console
DevProfiler.isDebugEnabled = true;
```

### Log Files

Check Magento logs for profiler-related issues:
- `var/log/developer_tools.log`
- `var/log/system.log`
- `var/log/exception.log`

## Development

### Extending the Module

#### Adding Custom Collectors

```php
<?php
// Custom data collector
class CustomDataCollector
{
    public function collect(): array
    {
        return [
            'custom_metric' => $this->calculateMetric(),
            'additional_data' => $this->gatherData()
        ];
    }
}
```

#### Frontend Customization

```javascript
// Extend the profiler widget
class ExtendedProfilerWidget extends ProfilerWidget {
    getCustomPanel(data) {
        return `<div class="custom-panel">${data}</div>`;
    }
}
```

### Testing

Run the included PHPUnit tests:

```bash
cd DeveloperTools
../../../vendor/bin/phpunit
```

Test coverage includes:
- Configuration management
- Console commands
- API key generation
- Debug info collection

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Commit your changes: `git commit -m 'Add amazing feature'`
4. Push to the branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

## Support

- **Documentation**: This README and inline code comments
- **Issues**: GitHub Issues tracker
- **Email**: babitkumar6@gmail.com

## Changelog

### v1.2.0
- Initial public release
- Complete profiling suite
- API key authentication
- Interactive web interface
- Console command integration
- Comprehensive test coverage

---

**⚠️ Important**: This module is designed for development environments. While it includes security features, it should be thoroughly tested before any production use. 