#!/bin/bash

# Temporary text file for list of XML files
xml_list='temp/xmls.txt'

# Create of list of XML files
ls oracle_xml > $xml_list
length=`wc -l < $xml_list`

# Loop through XML files
for (( i = 1; i <= $length; i++ ))
do
    # Get each diff file and its length
    xml_file=`head -$i $xml_list | tail -1`

    # Create copies of XML files
    cp oracle_xml/$xml_file oracle_xml_reduced/$xml_file
    cp mariadb_xml/$xml_file mariadb_xml_reduced/$xml_file

    sed -i '/<GOCDB_PORTAL_URL>/d' oracle_xml_reduced/$xml_file
    sed -i '/<GOCDB_PORTAL_URL>/d' mariadb_xml_reduced/$xml_file

    # Get length of original and new XML files
    original_lines=`wc -l < oracle_xml/${xml_file}`
    new_lines=`wc -l < oracle_xml_reduced/${xml_file}`

    # Number of lines deleted
    deleted_lines=$((original_lines - new_lines))

    printf "\r%5d lines deleted for %s\n" $deleted_lines $xml_file

done

rm $xml_list