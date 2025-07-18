# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
### Changed
### Deprecated
### Removed
### Fixed
### Security

## [1.2.0-beta1] - 2024-01-15

### Added
- Complete developer tools suite for Magento 2
- Database query profiling with real-time monitoring
- Performance tracking and memory analysis
- API key authentication system for secure access
- Interactive web-based toolbar widget with collapsible panels
- Console commands for profiler management (`profiler:enable`, `profiler:disable`, `profiler:status`)
- API key generation command (`velocity:devtools:generate-api-key`)
- Multi-level debug logging system (info, warning, error, debug)
- Request analysis with headers, parameters, and session inspection
- Environment information display (PHP, server, extensions)
- OPcache statistics and monitoring
- AJAX request interception and monitoring
- Multi-request tracking and selection
- JSON tree visualization for complex data structures
- Responsive design for mobile debugging
- Browser extension support with secure header injection
- Cookie-based API key management with fallback storage
- Comprehensive unit test coverage
- PHPUnit configuration with coverage reporting
- Admin configuration interface with security settings
- ACL permissions and role-based access control
- Memory limit monitoring with auto-disable functionality
- Slow query detection and highlighting
- Query parameter binding visualization
- Bootstrap time measurement
- Server load monitoring
- Timing-safe string comparison for security
- CSRF protection integration
- Singleton pattern for debug data collection
- Plugin/Observer architecture for request lifecycle hooks
- Service layer with comprehensive profiler service
- Magento 2.4+ compatibility
- PHP 8.1+ support

### Security
- API key-based authentication with cryptographically secure generation
- Timing-safe validation to prevent timing attacks
- Developer mode restrictions for production safety
- Secure cookie handling with proper attributes
- URL parameter cleanup for security
- Input validation and sanitization

## [1.0.0] - Initial Development

### Added
- Basic project structure
- Core interfaces and models
- Initial profiling capabilities

---

## Release Notes

### v1.2.0-beta1 - "Complete Developer Suite"

This is the first public beta release of VelocityDev Developer Tools, providing a comprehensive development and debugging experience for Magento 2.

**🎯 Key Highlights:**
- **Real-time Database Profiling**: Monitor SQL queries with execution times and parameter visualization
- **Interactive Web Interface**: Modern toolbar widget similar to Symfony's DebugBar
- **Security-First Design**: API key authentication with production-safe restrictions
- **Multi-Platform Support**: Works with browser extensions and standalone usage
- **Comprehensive Testing**: Full PHPUnit test suite with coverage reporting

**🔧 For Developers:**
- Console commands for easy management
- Extensive debugging capabilities
- Performance monitoring tools
- Environment information display
- AJAX request tracking

**🛡️ Security Features:**
- Cryptographically secure API key generation
- Timing-attack resistant validation
- Developer mode restrictions
- Secure session management

**📱 User Experience:**
- Responsive, mobile-friendly interface
- Real-time updates without page refresh
- Collapsible panels for organized data viewing
- JSON tree visualization for complex structures

This release establishes VelocityDev Developer Tools as a professional-grade debugging solution for Magento 2, providing developers with the insights they need to build better applications.

---

### Migration Guide

This is the initial public release. No migration is required.

### Compatibility

- **Magento**: 2.4.0 - 2.4.7+
- **PHP**: 8.1, 8.2, 8.3
- **Browsers**: Chrome 80+, Firefox 75+, Safari 13+, Edge 80+

### Known Issues

- None reported for this release

### Breaking Changes

- None (initial release)

---

For detailed upgrade instructions and compatibility information, see [README.md](README.md).

## Links

- [Repository](https://github.com/velocitydev/magento2-dev-tools)
- [Issues](https://github.com/velocitydev/magento2-dev-tools/issues)
- [Releases](https://github.com/velocitydev/magento2-dev-tools/releases)
- [Packagist](https://packagist.org/packages/velocitydev/magento2-dev-tools) 