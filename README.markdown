# OPC

This is the source code of the OttawaPHPCommunity.ca website. A simple aggregator of what web developers in Ottawa have to share about PHP.

## Requirements

- webserver with support for PHP 5/Zend Framework
- MySQL
- Zend Framework 1.9+
- ZFDebug 1.5+ (optional, disable in application.ini if you don't have/want it)

## Installation

### Libraries

Zend and ZFDebug libraries needs to be copied into the `./Library` directory.

### Cache

Make sure the webserver have read/write access to the `./application/cache` directory

### Database

Run the script in `./data/sql` to create the database, and then run any migration script in `./data/migrations` to get to the latest version

Sample data available in `./data/fixtures` directory

## Contribute

Everybody can contribute, we want to make the web better. The only requirement is to follow the ZF Coding Standards, and as we're (as in @danceric) picky, everything should run properly with E_STRICT error level.
