#!/bin/bash

# Temporary text file for list of diff files
diff_list='temp/diffs.txt'

# Create and count list of diffs
ls diff_xml > $diff_list
length=`wc -l < $diff_list`

# Count files with no differences
num_files_pass=0
num_files_invalid=0

# Loop through diffs
for (( i = 1; i <= $length; i++ ))
do
    # Get each diff file and its length
    diff_file=`head -$i $diff_list | tail -1`
    diff_lines=`wc -l < diff_xml/$diff_file`

    if [[ ${diff_lines} = 0 ]]; then
        num_files_pass=$((num_files_pass+1))
    fi

    # Get XML file name
    xml_file=`basename diff_xml/$diff_file | cut -f 1 -d .`

    # Get length of XML files
    total_lines_oracle=`wc -l < oracle_xml/${xml_file}.xml`
    total_lines_mariadb=`wc -l < mariadb_xml/${xml_file}.xml`

    # Check XML files are non-zero in length 
    if [[ ${total_lines_oracle} = 0 || ${total_lines_mariadb} = 0 ]]; then
        num_files_invalid=$((num_files_invalid+1))
        echo $diff_file
    else
        # Approx percentage difference (returns integer)
        percent=$((100 * $diff_lines / $total_lines_oracle))
        printf "\r%30s (%6d lines): %5d (%3d%%) lines different\n" $diff_file $total_lines_oracle $diff_lines $percent
    fi

done

num_files_fail=$((length - num_files_pass))

echo
echo Invalid files: $num_files_invalid
echo Files OK: $num_files_pass
echo Files with differences: $num_files_fail

rm $diff_list