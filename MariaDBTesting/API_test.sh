#!/bin/bash

oracle_dir=/usr/share/GOCDB5/MariaDBTesting/originalDB
maria_dir=/usr/share/GOCDB5/MariaDBTesting/newDB
diff_dir=/usr/share/GOCDB5/MariaDBTesting/diffDB

oracle_URL="https://goc.egi.eu"
maria_URL="https://host-172-16-102-162.nubes.stfc.ac.uk"

# Declare a string array
arrMethods=()
arrFiles=()
arrPermissions=()

while test $# -gt 0; do
    case "$1" in
        -h|--help)
            echo "$package - attempt to capture frames"
            echo " "
            echo "$package [options] application [arguments]"
            echo " "
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
            arrMethods+=("get_site" "get_site_list" "get_site_contacts" "get_site_security_info" "get_roc_list" "get_roc_contacts" "get_downtime" "get_downtime_nested_services" "get_service_endpoint" "get_service" "get_service_types" "get_user" "get_downtime_to_broadcast" "get_cert_status_changes" "get_cert_status_date" "get_service_group" "get_service_group_role" "get_ngi" "get_project_contacts" "get_site_count_per_country")
            arrFiles+=("sites" "sites_list" "site_contacts" "site_security_info" "roc_list" "roc_contacts" "downtimes" "downtime_services" "service_endpoints" "services" "service_types" "users" "downtimes_to_broadcast" "cert_status_changes" "cert_status_dates" "service_groups" "service_group_roles" "ngis" "project_contacts" "site_counts")
            arrPermissions+=(2 1 2 2 1 2 1 1 1 1 1 2 1 2 2 2 2 2 2 1)
            ;;
        --sites)
            shift
            arrMethods+=("get_site")
            arrFiles+=("sites")
            arrPermissions+=(2)
            ;;
        --sites_list)
            shift
            arrMethods+=("get_site_list")
            arrFiles+=("sites_list")
            arrPermissions+=(1)
            ;;
        --site_contacts)
            shift
            arrMethods+=("get_site_contacts")
            arrFiles+=("site_contacts")
            arrPermissions+=(2)
            ;;
        --site_security_info)
            shift
            arrMethods+=("get_site_security_info")
            arrFiles+=("site_security_info")
            arrPermissions+=(2)
            ;;
        --roc_list)
            shift
            arrMethods+=("get_roc_list")
            arrFiles+=("roc_list")
            arrPermissions+=(1)
            ;;
        --roc_contacts)
            shift
            arrMethods+=("get_roc_contacts")
            arrFiles+=("roc_contacts")
            arrPermissions+=(2)
            ;;
        --downtimes)
            shift
            arrMethods+=("get_downtime")
            arrFiles+=("downtimes")
            arrPermissions+=(1)
            ;;
        --downtime_services)
            shift
            arrMethods+=("get_downtime_nested_services")
            arrFiles+=("downtime_services")
            arrPermissions+=(1)
            ;;
        --service_endpoints)
            shift
            arrMethods+=("get_service_endpoint")
            arrFiles+=("service_endpoints")
            arrPermissions+=(1)
            ;;
        --services)
            shift
            arrMethods+=("get_service")
            arrFiles+=("services")
            arrPermissions+=(1)
            ;;
        --service_types)
            shift
            arrMethods+=("get_service_types")
            arrFiles+=("service_types")
            arrPermissions+=(1)
            ;;
        --users)
            shift
            arrMethods+=("get_user")
            arrFiles+=("users")
            arrPermissions+=(2)
            ;;
        --downtimes_to_broadcast)
            shift
            arrMethods+=("get_downtime_to_broadcast")
            arrFiles+=("downtimes_to_broadcast")
            arrPermissions+=(1)
            ;;
        --cert_status_changes)
            shift
            arrMethods+=("get_cert_status_changes")
            arrFiles+=("cert_status_changes")
            arrPermissions+=(2)
            ;;
        --cert_status_dates)
            shift
            arrMethods+=("get_cert_status_date")
            arrFiles+=("cert_status_dates")
            arrPermissions+=(2)
            ;;
        --service_groups)
            shift
            arrMethods+=("get_service_group")
            arrFiles+=("service_groups")
            arrPermissions+=(2)
            ;;
        --service_group_roles)
            shift
            arrMethods+=("get_service_group_role")
            arrFiles+=("service_group_roles")
            arrPermissions+=(2)
            ;;
        --ngis)
            shift
            arrMethods+=("get_ngi")
            arrFiles+=("ngis")
            arrPermissions+=(2)
            ;;
        --project_contacts)
            shift
            arrMethods+=("get_project_contacts")
            arrFiles+=("project_contacts")
            arrPermissions+=(2)
            ;;
        --site_counts)
            shift
            arrMethods+=("get_site_count_per_country")
            arrFiles+=("site_counts")
            arrPermissions+=(1)
            ;;
        *)
            break
            ;;
    esac
done

searchString="<?xml version=\"1.0\" encoding=\"UTF-8\"?>"

# Get all required data from API

# Iterate the loop to read and print each array element
for i in "${!arrMethods[@]}"
do
   
    if [[ ${arrPermissions[i]} = 2 ]]; then
        wgetOptionsOracle="--ca-certificate=/etc/grid-security/hostcert.pem  --certificate=/etc/grid-security/hostcert/hostcert.pem --private-key=/etc/grid-security/hostcert/hostkey.pem --private-key-type=PEM"
        wgetOptionsMaria="--ca-certificate=/etc/grid-security/hostcert.pem  --certificate=/etc/grid-security/hostcert/hostcert.pem --private-key=/etc/grid-security/hostcert/hostkey.pem --private-key-type=PEM"
        public="private"
    else
        wgetOptionsOracle="--no-check-certificate"
        wgetOptionsMaria=""
        public="public"
    fi
 
    wget $wgetOptionsOracle -O $oracle_dir/${arrFiles[i]}.xml "${oracle_URL}/gocdbpi/${public}/?method=${arrMethods[i]}"
    wget --no-check-certificate $wgetOptionsMaria -O $maria_dir/${arrFiles[i]}.xml "${maria_URL}/gocdbpi/${public}/?method=${arrMethods[i]}"

    if grep -q "$searchString"  "$oracle_dir/${arrFiles[i]}.xml"; then
        echo ${arrFiles[i]}.xml downloaded successfully 
    else
        echo ${arrFiles[i]}.xml download failed
    fi

    if grep -q "$searchString"  "$maria_dir/${arrFiles[i]}.xml"; then
        echo ${arrFiles[i]}.xml downloaded successfully 
    else
        echo ${arrFiles[i]}.xml download failed
    fi

    diff $oracle_dir/${arrFiles[i]}.xml $maria_dir/${arrFiles[i]}.xml > $diff_dir/${arrFiles[i]}.txt

done