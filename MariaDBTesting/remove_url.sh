#!/bin/bash

# Create of list of XML files
ls oracle > xmls.txt
length=`wc -l < xmls.txt`

# Loop through XML files
for (( i = 1; i <= $length; i++ ))
do
    # Get each diff file and its length
    xml_file=`head -$i xmls.txt | tail -1`

    # Create copies of XML files
    cp oracle/$xml_file oracle_reduced/$xml_file
    cp mariadb/$xml_file mariadb_reduced/$xml_file

    sed -i '/<GOCDB_PORTAL_URL>/d' oracle_reduced/$xml_file
    sed -i '/<GOCDB_PORTAL_URL>/d' mariadb_reduced/$xml_file

    # Get length of original and new XML files
    original_lines=`wc -l < oracle/${xml_file}`
    new_lines=`wc -l < oracle_reduced/${xml_file}`

    # Number of lines deleted
    deleted_lines=$((original_lines - new_lines))

    echo $deleted_lines lines deleted for $xml_file
done