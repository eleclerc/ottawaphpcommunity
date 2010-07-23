# OPC

This is the source code of the (now offline) OttawaPHPCommunity.ca website. A simple aggregator of what web developers in Ottawa have to share about PHP.

## Requirements

Of course you need this code, and a webserver with support for PHP 5 and MySQL, but also:

- [Doctrine](http://www.doctrine-project.org/projects/orm) 1.2.x
- [Zend Framework](http://framework.zend.com) 1.10.x
- [ZFDoctrine](http://github.com/beberlei/zf-doctrine)
- [ZFDebug](http://code.google.com/p/zfdebug/) 1.5 (optional, disable in application.ini if you don't have/want it)
    - [My Doctrine plugin](http://github.com/danceric/zfdebugdoctrine)

## Installation

### Libraries

`Danceric`, `Doctrine`, `Zend`, `ZFDebug`, and `ZFDoctrine` libraries needs to be copied into the `./Library` directory.

### Cache

Make sure the webserver have read/write access to the `./application/cache` directory

### Database

Create the database with the same name as in `application/config/application.ini`, the run the script in `./data/sql` to create the tables, then run any migration script in `./data/migrations` to get to the latest version.

Sample data available in `./data/fixtures` directory.

## Contribute

Everybody can contribute, we want to make the web better. The only requirement is to follow the ZF Coding Standards, and as we're (as in @danceric) picky, everything should run properly with E_STRICT error level.
