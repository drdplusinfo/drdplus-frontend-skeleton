#!/bin/bash

HTTPDUSER=$(ps axo user,comm | grep -E '[a]pache' | grep -v root | head -1 | cut -d\  -f1)
echo web user: $HTTPDUSER
setfacl --default --recursive -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX ./versions
setfacl --recursive -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX ./versions
chgrp $HTTPDUSER ./versions
find ./versions -mindepth 1 -type d -exec chgrp --recursive $HTTPDUSER {} +
ls -al ./versions