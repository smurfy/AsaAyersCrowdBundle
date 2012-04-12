#!/bin/bash

# I know many people say that spaces should be used for indentaion.
# I disagree, if tabs are used I can use a width of 4, but I see
# other people like 2 spaces. I've used editors that default to 8.
# With spaces only one of these can be accomodated, with tabs you
# can change your own settings and use any width you like.
find * -type f -iname '*.php' -exec vim -s retab.input {} \;
