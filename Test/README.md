# Developer Tools Module - Unit Tests

This directory contains unit tests for the Developer Tools Magento 2 module.

## Test Structure

```
Test/
├── Unit/
│   └── Model/
│       ├── DebugInfoTest.php
│       └── Config/
│           └── ProfilerConfigTest.php
├── Coverage/
│   ├── html/
│   ├── coverage.txt
│   ├── clover.xml
│   └── junit.xml
└── README.md
```

## Running Tests

### Prerequisites

1. Ensure you have PHPUnit 10.0+ installed (already included in composer.json)
2. Make sure Magento 2 framework is properly set up
3. Run `composer install` to install dependencies

### Running All Tests

```bash
# From the module root directory
vendor/bin/phpunit

# Or if phpunit is installed globally
phpunit
```

### Running Specific Test Files

```bash
# Run only DebugInfo tests
vendor/bin/phpunit Test/Unit/Model/DebugInfoTest.php

# Run only ProfilerConfig tests
vendor/bin/phpunit Test/Unit/Model/Config/ProfilerConfigTest.php
```

### Running Tests with Coverage

```bash
# Generate coverage report
vendor/bin/phpunit --coverage-html Test/Coverage/html

# Generate coverage text report
vendor/bin/phpunit --coverage-text
```

## Test Classes

### DebugInfoTest

Tests the `DebugInfo` singleton class functionality:

- ✅ Singleton pattern implementation
- ✅ Message adding with default and custom parameters
- ✅ Multiple message handling
- ✅ Message clearing
- ✅ JSON serialization
- ✅ Data retrieval
- ✅ Singleton protection (cloning and unserialization)
- ✅ Timestamp ordering

### ProfilerConfigTest

Tests the `ProfilerConfig` class functionality:

- ✅ All configuration flag methods
- ✅ Configuration value retrieval with defaults
- ✅ API key validation logic
- ✅ API key generation
- ✅ Request profiling conditions
- ✅ Developer mode restrictions
- ✅ Dependency injection with mocks

## Test Coverage

The tests aim to achieve comprehensive coverage of:

- All public methods
- Edge cases and error conditions
- Configuration defaults and custom values
- Dependency interactions
- Security validations

## Mocking Strategy

The tests use PHPUnit's mocking capabilities to isolate units under test:

- **ScopeConfigInterface**: Mocked to simulate Magento configuration
- **State**: Mocked to simulate application state
- **Random**: Mocked to test API key generation
- **Request**: Mocked to simulate HTTP requests
- **ApiKeyCookieManagerService**: Mocked to simulate cookie management

## Configuration Constants

The tests reference configuration constants from `ProfilerConfigInterface`:

- XML paths for various settings
- Default values for configuration options
- API key header constants

## Running Tests in CI/CD

The phpunit.xml configuration is designed to work in continuous integration environments:

```bash
# Basic CI run
phpunit --log-junit Test/Coverage/junit.xml

# With coverage for quality gates
phpunit --coverage-clover Test/Coverage/clover.xml
```

## Troubleshooting

### Common Issues

1. **Class not found errors**: Ensure Magento 2 is properly installed and autoloaded
2. **Missing dependencies**: Run `composer install` in the Magento root
3. **Permission issues**: Check file permissions on Test/Coverage directory

### Debug Mode

To run tests with more verbose output:

```bash
vendor/bin/phpunit --verbose --debug
```

## Test Best Practices

The tests follow these principles:

1. **Isolation**: Each test is independent and doesn't rely on others
2. **Mocking**: External dependencies are mocked to ensure unit testing
3. **Coverage**: Both positive and negative test cases are included
4. **Clarity**: Test names clearly describe what is being tested
5. **Setup/Teardown**: Proper test setup and cleanup 