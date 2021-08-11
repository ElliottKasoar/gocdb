#!/bin/bash

# Directories where XML will be saved
oracle_dir=/usr/share/GOCDB5/MariaDBTesting/oracle
mariadb_dir=/usr/share/GOCDB5/MariaDBTesting/mariadb

# Directory for diffs of XML files
diff_dir=/usr/share/GOCDB5/MariaDBTesting/xmlDiff

# Base URLs for databases
oracle_URL="https://goc.egi.eu"
mariadb_URL="https://host-172-16-102-162.nubes.stfc.ac.uk"

# Directories for certificates
grid_dir="/etc/grid-security/"
cert_dir="${grid_dir}hostcert/"

# Array for API method names (get_ not required)
arr_methods=()
# Array for XML/diff file names to save
arr_files=()
# Array for API method protection level (2 requires a authentication)
arr_permissions=()

# Number of diff files created
diff_count=0
# Array for XML files that failed to download
arr_file_failures=()

# Define full lists, used by default (no flags) or --all
arr_methods_all=("site" "site_list" "site_contacts" "site_security_info" "roc_list" "roc_contacts" "downtime&startdate=2021-01-01" "downtime_nested_services&startdate=2021-01-01" "service_endpoint" "service" "service_types" "user" "downtime_to_broadcast" "cert_status_changes" "cert_status_date" "service_group" "service_group_role" "ngi" "project_contacts" "site_count_per_country")
arr_files_all=("sites" "sites_list" "site_contacts" "site_security_info" "roc_list" "roc_contacts" "downtimes" "downtime_services" "service_endpoints" "services" "service_types" "users" "downtimes_to_broadcast" "cert_status_changes" "cert_status_dates" "service_groups" "service_group_roles" "ngis" "project_contacts" "site_counts")
arr_permissions_all=(2 1 2 2 1 2 1 1 1 1 1 2 1 2 2 2 2 2 2 1)

# Add methods and file names to arrays based on flags if no flags specified
if [[ -z "$1" ]]; then
    arr_methods=("${arr_methods_all[@]}")
    arr_files=("${arr_files_all[@]}")
    arr_permissions=("${arr_permissions_all[@]}")
fi

# Add methods and file names to arrays based on flags
while test $# -gt 0; do
    case "$1" in
        -h|--help)
            echo "options:"
            echo "-h, --help                show brief help"
            echo "--all                     get all data"
            echo "--sites                   get sites data"
            echo "--sites_list              get list of sites"
            echo "--site_contacts           get persons with site roles"
            echo "--site_security_info      get site security data"
            echo "--roc_list                get list of NGIs"
            echo "--roc_contacts            get NGI contact data"
            echo "--downtimes               get downtimes data"
            echo "--downtime_services       get downtimes data"
            echo "--service_endpoints       get service endpoints data"
            echo "--services                get service data"
            echo "--service_types           get servie types data"
            echo "--users                   get users data"
            echo "--downtimes_to_broadcast  get recently declated downtimes"
            echo "--cert_status_changes     get cert status changes data"
            echo "--cert_status_dates       get cert status dates data"
            echo "--service_groups          get service group data"
            echo "--service_group_roles     get service group role data"
            echo "--ngis                    get ngis data"
            echo "--project_contacts        get project contact data"
            echo "--site_counts             get count of sites per country"
            exit 0
            ;;
        --all)
            shift
            arr_methods=("${arr_methods_all[@]}")
            arr_files=("${arr_files_all[@]}")
            arr_permissions=("${arr_permissions_all[@]}")
            break
            ;;
        --sites)
            shift
            arr_methods+=("site")
            arr_files+=("sites")
            arr_permissions+=(2)
            ;;
        --sites_list)
            shift
            arr_methods+=("site_list")
            arr_files+=("sites_list")
            arr_permissions+=(1)
            ;;
        --site_contacts)
            shift
            arr_methods+=("site_contacts")
            arr_files+=("site_contacts")
            arr_permissions+=(2)
            ;;
        --site_security_info)
            shift
            arr_methods+=("site_security_info")
            arr_files+=("site_security_info")
            arr_permissions+=(2)
            ;;
        --roc_list)
            shift
            arr_methods+=("roc_list")
            arr_files+=("roc_list")
            arr_permissions+=(1)
            ;;
        --roc_contacts)
            shift
            arr_methods+=("roc_contacts")
            arr_files+=("roc_contacts")
            arr_permissions+=(2)
            ;;
        --downtimes)
            shift
            arr_methods+=("downtime&startdate=2021-01-01")
            arr_files+=("downtimes")
            arr_permissions+=(1)
            ;;
        --downtime_services)
            shift
            arr_methods+=("downtime_nested_services&startdate=2021-01-01")
            arr_files+=("downtime_services")
            arr_permissions+=(1)
            ;;
        --service_endpoints)
            shift
            arr_methods+=("service_endpoint")
            arr_files+=("service_endpoints")
            arr_permissions+=(1)
            ;;
        --services)
            shift
            arr_methods+=("service")
            arr_files+=("services")
            arr_permissions+=(1)
            ;;
        --service_types)
            shift
            arr_methods+=("service_types")
            arr_files+=("service_types")
            arr_permissions+=(1)
            ;;
        --users)
            shift
            arr_methods+=("user")
            arr_files+=("users")
            arr_permissions+=(2)
            ;;
        --downtimes_to_broadcast)
            shift
            arr_methods+=("downtime_to_broadcast")
            arr_files+=("downtimes_to_broadcast")
            arr_permissions+=(1)
            ;;
        --cert_status_changes)
            shift
            arr_methods+=("cert_status_changes")
            arr_files+=("cert_status_changes")
            arr_permissions+=(2)
            ;;
        --cert_status_dates)
            shift
            arr_methods+=("cert_status_date")
            arr_files+=("cert_status_dates")
            arr_permissions+=(2)
            ;;
        --service_groups)
            shift
            arr_methods+=("service_group")
            arr_files+=("service_groups")
            arr_permissions+=(2)
            ;;
        --service_group_roles)
            shift
            arr_methods+=("service_group_role")
            arr_files+=("service_group_roles")
            arr_permissions+=(2)
            ;;
        --ngis)
            shift
            arr_methods+=("ngi")
            arr_files+=("ngis")
            arr_permissions+=(2)
            ;;
        --project_contacts)
            shift
            arr_methods+=("project_contacts")
            arr_files+=("project_contacts")
            arr_permissions+=(2)
            ;;
        --site_counts)
            shift
            arr_methods+=("site_count_per_country")
            arr_files+=("site_counts")
            arr_permissions+=(1)
            ;;
        *)
            echo Invalid flag
            exit 1
            ;;
    esac
done

searchString="<?xml version=\"1.0\" encoding=\"UTF-8\"?>"

# Get all required data from API

# Iterate the loop to read and print each array element
for i in "${!arr_methods[@]}"
do
    if [[ ${arr_permissions[i]} = 2 ]]; then
        wgetOptionsOracle="--ca-certificate=${grid_dir}hostcert.pem  --certificate=/${cert_dir}hostcert.pem --private-key=${cert_dir}hostkey.pem --private-key-type=PEM"
        wgetOptionsMaria=$wgetOptionsOracle
        privacy="private"
    else
        wgetOptionsOracle="--no-check-certificate"
        wgetOptionsMaria=""
        privacy="public"
    fi

    oracle_file=$oracle_dir/${arr_files[i]}.xml
    mariadb_file=$mariadb_dir/${arr_files[i]}.xml
    diff_file=$diff_dir/${arr_files[i]}.txt

    success=true

    echo
    echo Attempting to download ${arr_files[i]}.xml...

    wget -q $wgetOptionsOracle -O $oracle_file "${oracle_URL}/gocdbpi/${privacy}/?method=get_${arr_methods[i]}"

    if grep -q "$searchString"  "$oracle_file"; then
        echo $oracle_file downloaded successfully!
    else
        echo $oracle_file download failed
        success=false
        arr_file_failures+=($oracle_file)
    fi

    wget -q --no-check-certificate $wgetOptionsMaria -O $mariadb_file "${mariadb_URL}/gocdbpi/${privacy}/?method=get_${arr_methods[i]}"

    if grep -q "$searchString"  "$mariadb_dir/${arr_files[i]}.xml"; then
        echo $mariadb_file downloaded successfully!
    else
        echo $mariadb_file download failed
        success=false
        arr_file_failures+=($mariadb_file)
    fi

    if $success; then
        diff $oracle_file $mariadb_file > $diff_file
        diff_count=$((diff_count+1))
        echo ${arr_files[i]}.xml diff performed!
    else
        echo Unable to perform ${arr_files[i]}.xml diff
    fi

done

echo
echo diffs created successfully: $diff_count
echo

if [[ ${#arr_file_failures[@]} = 0 ]]; then
    echo All XML downloads successful!
else
    echo XML download failures:
    for i in "${!arr_file_failures[@]}"
    do
        echo ${arr_file_failures[i]}
    done
fi
echo