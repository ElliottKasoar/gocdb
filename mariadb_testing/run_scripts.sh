#!/bin/bash

# #Ignore lines containing "<GOCDB_PORTAL_URL>" for diff? 
reduced_xml=${reduced_xml:-false}

# Add methods and file names to arrays based on flags
while test $# -gt 0; do
    case "$1" in
        -h|--help)
            echo "options:"
            echo "-h, --help                show brief help"
            echo "--reduced_xml             diff XML files with certain tags moved"
            exit 0
            ;;
        --reduced_xml)
            shift
            reduced_xml=true
            ;;
        *)
            echo Invalid flag: "$1"
            exit 1
            ;;
    esac
done

#Directories that must exist
dirs=('temp' 'oracle_xml' 'mariadb_xml' 'diff_xml')

if $reduced_xml; then
    dirs+=('oracle_xml_reduced' 'mariadb_xml_reduced')
fi

# Create directories if do not already exist
for i in "${!dirs[@]}"
do
    if [ ! -d "${dirs[i]}" ]; then
        echo "Creating directory ${dirs[i]}"
        mkdir ./${dirs[i]}
        echo "Directory created"
    fi  
done

# Get XML files and save in oracle_xml or mariadb_xml
echo
echo Calling get_xml.sh
echo
./get_xml.sh

# Reduce XML files and save in oracle_xml_reduced or mariadb_xml_reduced
if $reduced_xml; then
    echo
    echo Calling remove_tags.sh
    echo
    ./remove_tags.sh
    
    # diff XML files and save in diff_xml
    echo
    echo Calling get_diff.sh --reduced_xml
    echo
    ./get_diff.sh --reduced_xml
else
    # diff XML files and save in diff_xml
    echo
    echo Calling get_diff.sh
    echo
    ./get_diff.sh
fi

# Count differences from length of diff files
echo
echo Calling line_count.sh
echo
./line_count.sh