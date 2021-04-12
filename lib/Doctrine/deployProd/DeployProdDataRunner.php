<?php

# May not need?
require_once __DIR__."/AddUtils.php";

if(isset($argv[1])) {
    $GLOBALS['dataDir'] = $argv[1];
} else {
    die("Please specify your data directory (data) \n");
}

print_r("Deploying Data\n");

# "Required" data:

require __DIR__."/AddInfrastructures.php";
echo "Added Infrastructures OK\n";

require __DIR__."/AddCountries.php";
echo "Added Countries OK\n";

//require __DIR__."/AddTimezones.php";
//echo "Added Timezones OK\n";

require __DIR__."/AddTiers.php";
echo "Added Tiers OK\n";

require __DIR__."/AddRoleTypes.php";
echo "Added Roles OK\n";

require __DIR__."/AddCertificationStatuses.php";
echo "Added Certification Statuses OK\n";

require __DIR__."/AddServiceTypes.php";
echo "Added Service Types OK\n";

# "Sample" data:

require __DIR__."/AddProjects.php";
echo "Added Projects OK\n";

require __DIR__."/AddScopes.php";
echo "Added Scopes OK\n";

require __DIR__."/AddNGIs.php";
echo "Added NGIs OK\n";

require __DIR__."/AddSites.php";
echo "Added Sites and JOINED to NGIs OK\n";

require __DIR__."/AddServiceEndpoints.php";
echo "Added Services, EndpointLocations and JOINED associations OK\n";

require __DIR__."/AddUsers.php";
echo "Added Users OK\n";

require __DIR__."/AddSiteRoles.php";
echo "Added Site level Roles OK\n";

require __DIR__."/AddGroupRoles.php";
echo "Added NGI level Roles OK\n";

require __DIR__."/AddEgiRoles.php";
echo "Added EGI level Roles OK\n";

require __DIR__."/AddDowntimes.php";
echo "Added Downtimes OK\n";
