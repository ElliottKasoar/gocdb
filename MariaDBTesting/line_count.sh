#!/bin/bash

ls diffDB > diffs.txt
length=`wc -l < diffs.txt`

for (( i = 1; i <= $length; i++ ))
do
    diff_file=`head -$i diffs.txt | tail -1`
    diff_lines=`wc -l < diffDB/$diff_file`

    xml_file=`basename diffDB/$diff_file | cut -f 1 -d .`
    total_lines=`wc -l < originalDB/${xml_file}.xml`
    #total_lines+=`wc -l < newDB/${xml_file}.xml`

    percent=$((100 * $diff_lines / $total_lines))
    #percent=$((100 * $total_lines))

    echo "${diff_file}: ${diff_lines} lines. Total lines: ${total_lines} (${percent}%)"
done

