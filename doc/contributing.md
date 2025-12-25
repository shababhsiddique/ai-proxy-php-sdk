# Contributing

Thank you for your interest in contributing to the AI Proxy PHP SDK! This document provides guidelines and instructions for contributing.

## Getting Started

### Prerequisites

- PHP 8.0 or higher
- Composer
- Git

### Setting Up Development Environment

1. **Clone the repository:**

```bash
git clone https://github.com/your-org/ai-proxy-php-sdk.git
cd ai-proxy-php-sdk
```

2. **Install dependencies:**

```bash
composer install
```

3. **Run tests:**

```bash
./vendor/bin/phpunit
```

## Development Workflow

### 1. Create a Branch

Create a feature branch from `main`:

```bash
git checkout -b feature/your-feature-name
```

Or for bug fixes:

```bash
git checkout -b fix/your-bug-description
```

### 2. Make Changes

- Write clean, readable code
- Follow PSR-12 coding standards
- Add PHPDoc comments for public methods
- Update documentation if needed

### 3. Write Tests

- Add tests for new features
- Ensure all existing tests pass
- Aim for good test coverage

### 4. Run Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test file
./vendor/bin/phpunit tests/ClientChatTest.php

# Run specific test method
./vendor/bin/phpunit --filter testChatBuildsPayloadAndSignature
```

### 5. Commit Changes

Write clear, descriptive commit messages:

```bash
git add .
git commit -m "Add feature: description of what was added"
```

### 6. Push and Create Pull Request

```bash
git push origin feature/your-feature-name
```

Then create a pull request on GitHub.

## Coding Standards

### PSR-12

This project follows [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards.

### Code Style Checks

You can check code style using PHP_CodeSniffer (if configured):

```bash
./vendor/bin/phpcs src/ tests/
```

### PHPDoc

All public methods should have PHPDoc comments:

```php
/**
 * Brief description.
 *
 * Longer description if needed.
 *
 * @param string $param Description
 * @return array Description
 * @throws \RuntimeException When something goes wrong
 */
public function method(string $param): array
{
    // ...
}
```

## Testing Guidelines

### Test Structure

- Tests should be in the `tests/` directory
- Test class names should end with `Test`
- Test methods should start with `test` or use `@test` annotation

### Writing Tests

```php
<?php

namespace AiProxy\Tests;

use PHPUnit\Framework\TestCase;
use AiProxy\Client;

class MyFeatureTest extends TestCase
{
    public function testMyFeature(): void
    {
        // Arrange
        $client = new Client('key', 'secret', 'https://example.com');
        
        // Act
        $result = $client->someMethod();
        
        // Assert
        $this->assertIsArray($result);
    }
}
```

### Mocking

For testing without making real HTTP requests, you can:

1. Extend the `Client` class and override `sendRequest()`
2. Use a mocking library if needed
3. Create a test double

Example from existing tests:

```php
$client = new class('key', 'secret', 'https://example.com') extends Client {
    protected function sendRequest(
        string $url,
        string $authHeader,
        string $payloadSignature,
        string $jsonBody
    ): array {
        // Return mock response
        return [200, '{"test": "response"}'];
    }
};
```

## Pull Request Process

### Before Submitting

1. ✅ All tests pass
2. ✅ Code follows PSR-12 standards
3. ✅ Documentation is updated
4. ✅ Commit messages are clear

### Pull Request Template

When creating a PR, include:

- **Description**: What changes were made and why
- **Type**: Feature, bug fix, documentation, etc.
- **Testing**: How the changes were tested
- **Breaking Changes**: Any breaking changes (if applicable)

### Review Process

- Maintainers will review your PR
- Address any feedback or requested changes
- Once approved, your PR will be merged

## Project Structure

```
ai-proxy-php-sdk/
├── src/
│   └── Client.php          # Main SDK class
├── tests/
│   └── ClientChatTest.php  # Test cases
├── doc/                    # Documentation
├── composer.json           # Dependencies
├── phpunit.xml.dist        # PHPUnit config
└── README.md               # Main README
```

## Areas for Contribution

We welcome contributions in these areas:

- **Bug fixes**: Report and fix bugs
- **Features**: Add new functionality (discuss first via issue)
- **Documentation**: Improve docs, add examples
- **Tests**: Increase test coverage
- **Performance**: Optimize code
- **Code quality**: Refactor and improve code

## Reporting Issues

### Bug Reports

When reporting bugs, include:

1. **Description**: Clear description of the bug
2. **Steps to Reproduce**: How to reproduce the issue
3. **Expected Behavior**: What should happen
4. **Actual Behavior**: What actually happens
5. **Environment**: PHP version, OS, etc.
6. **Code Example**: Minimal code that reproduces the issue

### Feature Requests

For feature requests, include:

1. **Use Case**: Why this feature is needed
2. **Proposed Solution**: How you envision it working
3. **Alternatives**: Other solutions you've considered

## Code of Conduct

- Be respectful and inclusive
- Welcome newcomers
- Focus on constructive feedback
- Help others learn and grow

## License

By contributing, you agree that your contributions will be licensed under the same license as the project (MIT License).

## Questions?

If you have questions about contributing:

- Open an issue on GitHub
- Check existing issues and discussions
- Review the documentation in the `doc/` folder

Thank you for contributing! 🎉

