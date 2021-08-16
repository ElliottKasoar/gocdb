#!/bin/bash

# Default full diff files
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

# Directories where XML is saved
if $reduced_xml; then
    oracle_dir=oracle_xml_reduced
    mariadb_dir=mariadb_xml_reduced
else
    oracle_dir=oracle_xml
    mariadb_dir=mariadb_xml
fi

# Directory for diffs of XML files to be saved
diff_dir=diff_xml

# Number of diff files created
diff_count=0

# Array for diff files that were not created
arr_file_failures=()

# String to check XML files look ok
search_string="<?xml version=\"1.0\" encoding=\"UTF-8\"?>"

# Temporary text file for list of XML files
xml_list='temp/xmls.txt'

# Create and count list of XML files
ls oracle_xml > $xml_list
length=`wc -l < $xml_list`

# Iterate the loop to read and print each array element
for (( i = 1; i <= $length; i++ ))
do

    # Get XML file name
    xml_file=`head -$i $xml_list | tail -1`

    # Get path for Oracle and MariaDB XML files
    oracle_xml_file=${oracle_dir}/${xml_file}
    mariadb_xml_file=${mariadb_dir}/${xml_file}

    # Get XML file name
    xml_file_no_extension=`basename $xml_file | cut -f 1 -d .`
    diff_file=${diff_dir}/${xml_file_no_extension}.xml

    # Check if XML file contains the search string for basic validation
    if grep -q "$search_string" "$oracle_xml_file" && grep -q "$search_string" "$mariadb_xml_file"; then
        diff $oracle_xml_file $mariadb_xml_file > $diff_file
        diff_count=$((diff_count+1))
        echo $xml_file diff performed!
    else
        echo "Error: Cannot perform diff for $xml_file"
        arr_file_failures+=($diff_file)
    fi

done

echo
echo diffs created successfully: $diff_count
echo

if [[ ${#arr_file_failures[@]} = 0 ]]; then
    echo All diffs successful!
else
    echo XML download failures:
    for i in "${!arr_file_failures[@]}"
    do
        echo ${arr_file_failures[i]}
    done
fi

rm $xml_list