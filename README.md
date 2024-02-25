# XhprofTrace

XhprofTrace is a PHP library that provides an interface for the XHProf profiling tool. It allows you to enable and
disable profiling, and generate and display a report of the profiling data.

## Requirements

- PHP 8.1 or higher
- XHProf PHP extension

## Installation

Before installing the XhprofTrace library, you need to ensure that the XHProf PHP extension is installed and enabled. Check your PHP version compatibility and ensure the XHProf extension is compatible with your PHP version. For non-Docker environments, follow the instructions specific to your operating system.

### Docker
For Docker users, specific Dockerfiles are provided for PHP versions 8.1, 8.2, and 8.3, located in the docker/ directory. Use the corresponding Dockerfile (Dockerfile81, Dockerfile82, Dockerfile83) for your PHP version. You can add the following lines to your Dockerfile:

```dockerfile
RUN pecl install xhprof && docker-php-ext-enable xhprof
``` 

```bash
composer require marjovanlier/xhproftrace
```

## Usage

Here is a basic example of how to use the library:

```php
use MarjovanLier\XhprofTrace\Trace;

// Enable XHProf profiling
Trace::enableXhprof();

// Your application code here...

// Disable XHProf profiling
Trace::disableXhprof();

// Display the profiling report in the console
Trace::displayReportCLI();
```

## Documentation

The library provides the following static methods:

- `enableXhprof()`: Enables XHProf profiling.
- `disableXhprof()`: Disables XHProf profiling and saves the profiling data to a file.
- `displayReportCLI()`: Generates a report from the profiling data and displays it in the console.

## License
### Linux

For Linux users, you can install the XHProf extension using the following commands:

```bash
sudo pecl install xhprof
echo "extension=xhprof.so" | sudo tee -a /etc/php/{your-php-version}/mods-available/xhprof.ini
sudo phpenmod xhprof
```

Replace `{your-php-version}` with your PHP version (e.g., `8.1`, `8.2`, `8.3`).

### Windows

For Windows users, download the XHProf DLL from the official PECL website and add it to your `php.ini`:

1. Download the XHProf DLL compatible with your PHP version from [PECL](https://pecl.php.net/package/xhprof).
2. Add the following line to your `php.ini`:

```ini
extension=php_xhprof.dll
```

### macOS

For macOS users, you can install the XHProf extension using Homebrew:

```bash
brew install php@{your-php-version}-xhprof
```

Replace `{your-php-version}` with your PHP version without dots (e.g., `81` for PHP 8.1).

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.