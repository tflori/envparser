# tflori/envparser

[![Build Status](https://travis-ci.org/tflori/envparser.svg?branch=master)](https://travis-ci.org/tflori/envparser)
[![Test Coverage](https://api.codeclimate.com/v1/badges/7f1a0e438b1f9981aa59/test_coverage)](https://codeclimate.com/github/tflori/envparser/test_coverage)
[![Maintainability](https://api.codeclimate.com/v1/badges/9434ba649634cb577b87/maintainability)](https://codeclimate.com/github/tflori/envparser/maintainability)
[![Latest Stable Version](https://poser.pugx.org/tflori/envparser/v/stable.svg)](https://packagist.org/packages/tflori/envparser) 
[![Total Downloads](https://poser.pugx.org/tflori/envparser/downloads.svg)](https://packagist.org/packages/tflori/envparser) 
[![License](https://poser.pugx.org/tflori/envparser/license.svg)](https://packagist.org/packages/tflori/envparser)

A small library that is just reading an environment file in bash syntax and returns an array equal to that what you
would get sourcing this file in a bash environment.

## Features

###  Declarations of variables

```bash
# a string without spaces
STRING1=foo

# a string with spaces surrounded by quotes
STRING2="foo's bar"
STRING3='"$quote" - $author'      # string '"$quote" - $author'
STRING4=foo"' "bar                # string 'foo\' bar'

# variable replacement (assuming user=thomas)
VAR1=$user                        # string 'thomas'
VAR2="$user's profile"            # string 'thomas\'s profile'
VAR3="${APP_ENV-production}"      # string 'production'

# array handling
ARRAY1=(foo bar)                  # array ['foo', 'bar']
ARRAY2=($int 6 7)                 # array [42, 6, 7]
ARRAY3=()                         # array []
ARRAY5[0]=foo                     # array ['foo']
ARRAY5[1]=bar                     # array ['foo', 'bar']
```

A lot of things available in bash are not possible in this library but the important ones will be. When you find
something is missing please open a feature request on github or create a merge request.

### Using Environment

All variables available in `$_ENV` and `getenv()` (or a passed array) are available inside the parsed file.

### Conversion of variables

In bash every variable is a string like in *nix every variable is a file and inside is a string. While it is very useful
on this level we don't want that inside an application.

```bash
### null (case insensitive)
NULL1=                            # null (bash typical)
NULL2=null                        # null
NULL3="null"                      # null
STRING1='null'                    # string 'null'

### numbers (checked with is_numeric)
INT=42                            # int 42
INT2="23"                         # int 23
STRING2='42'                      # string '42'
FLOAT=23.2                        # float 23.2
FLOAT2="42.1"                     # float 42.1
STRING3='23.2'                    # string '23.2'

### true (case insensitive)
BOOL_TRUE=true                    # bool true
STRING4='true'                    # string 'true'

### ATTENTION! In php: ('false' != false)
BOOL_FALSE=false                  # bool false
STRING5='false'                   # string 'false'

### json (you should avoid json in environment files. it is just sugar; ext-json required)
OBJECT='json:{"foo":"bar"}'       # \stdobj ['foo' => 'bar']
ARRAY='jsonArray:{"foo":"bar"}'   # array ['foo' => 'bar']

### base64 (use it for unprintable characters)
APP_KEY="base64:dzN0ICJzZWNyZXQiCg=="
```

### Concatenation

Strings are concatenated in bash by just connecting the strings together.

```bash
STRING1="hello"' $user'           # string 'hello $user'

### everything is a string when it gets concatenated
STRING2=True" Warrior"            # string 'True Warrior'
```

### Escaping

Escaping quotes works exactly the same as in bash:

```bash
STRING1="foo's \"bar\""
STRING2='foo'"'"'s "bar"'
STRING3='foo'\''s "bar"'
STRING4=$'foo\'s "bar"'
```
