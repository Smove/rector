<?php

namespace RectorPrefix20210923;

if (\class_exists('t3lib_DB')) {
    return;
}
class t3lib_DB
{
}
\class_alias('t3lib_DB', 't3lib_DB', \false);
