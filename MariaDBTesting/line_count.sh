#!/bin/bash

# Create and count list of diffs
ls xmlDiff > diffs.txt
length=`wc -l < diffs.txt`

# Loop through diffs
for (( i = 1; i <= $length; i++ ))
do
    # Get each diff file and its length
    diff_file=`head -$i diffs.txt | tail -1`
    diff_lines=`wc -l < xmlDiff/$diff_file`

    # Get XML file name
    xml_file=`basename xmlDiff/$diff_file | cut -f 1 -d .`

    # Get legnth of XML file
    total_lines=`wc -l < oracle/${xml_file}.xml`

    # Approx percentage difference (returns integer)
    percent=$((100 * $diff_lines / $total_lines))

    printf "\r%30s (%6d lines): %5d (%3d%%) lines different\n" $diff_file $total_lines $diff_lines $percent
done

