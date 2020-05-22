# tflori/envparser

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

# variable replacement (assuming user=thomas)
VAR1=$user                        # string 'thomas'
VAR2="$user's profile"            # string 'thomas\'s profile'
VAR3="${APP_ENV-production}"      # string 'production'

# json parsing
NULL=null                         # null
INT=42                            # int 42
FLOAT=23.2                        # float 23.2
BOOL_TRUE=true                    # bool true
BOOL_FALSE=false                  # bool false
OBJECT='{"foo":"bar"}'            # object

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
