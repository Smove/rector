<?php

namespace RectorPrefix20210923;

if (\class_exists('tx_install_report_InstallStatus')) {
    return;
}
class tx_install_report_InstallStatus
{
}
\class_alias('tx_install_report_InstallStatus', 'tx_install_report_InstallStatus', \false);
