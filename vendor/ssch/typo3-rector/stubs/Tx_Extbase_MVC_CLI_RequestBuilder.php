<?php

namespace RectorPrefix20210923;

if (\class_exists('Tx_Extbase_MVC_CLI_RequestBuilder')) {
    return;
}
class Tx_Extbase_MVC_CLI_RequestBuilder
{
}
\class_alias('Tx_Extbase_MVC_CLI_RequestBuilder', 'Tx_Extbase_MVC_CLI_RequestBuilder', \false);
