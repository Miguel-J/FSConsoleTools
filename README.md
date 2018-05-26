[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](https://github.com/shawe/FSConsoleTools/issues?utf8=✓&q=is%3Aopen%20is%3Aissue)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/shawe/FSConsoleTools/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/shawe/FSConsoleTools/?branch=master)

# FSConsoleTools for FacturaScripts 2018

*Some useful console tools to simplify dev work with [FacturaScripts](https://github.com/NeoRazorX/facturascripts).*

## Installation

Inside your FacturaScripts run this:
```
composer require shawe/fs-console-tools
```

Now you can use some useful commands as:
- Reorder content of XML for tables with:
   - ```./vendor/bin/order-xml-tables```
- Reorder content of JSON files:
   - ```./vendor/bin/order-json-files```
- *Highly recommended before doing a Pull Request*: Run **order-xml-tables**, **order-json-files**, **phpcbf**, **phpcs** and **phpunit** at once:
  - ```./vendor/bin/run-before-pull-request```
- Add custom commands you need

## Available options

- **bin/order-xml-tables:** *This is a little utility to reorder XML table items.*
- **bin/order-json-files:** *This is a little utility to reorder JSON items.*
- **bin/run-before-pull-request:** *This is a little utility to run all common needed checks before do a Pull Request.*

## Pull Request are welcome

If you think something is useful for you, it may be useful for the rest of the developers.

Fork me, add the new feature and send me a Pull Request to be added for all of us.

[![Throughput Graph](https://graphs.waffle.io/shawe/FSConsoleTools/throughput.svg)](https://waffle.io/shawe/FSConsoleTools/metrics/throughput)
