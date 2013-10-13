#!/bin/sh

for FILE in $1
do
FILE=`echo $FILE | sed -e 's/gpx\///'`
viking viking gpx/"$FILE" &
PID=$!
MAP=`echo $FILE | sed -e 's/gpx\///' -e 's/gpx/png/'`
sleep 3
import -window root -crop 1396x791+202+87 maps/$MAP
kill $PID
MAP_MINI="mini-$MAP"
convert -resize 20% maps/$MAP maps/$MAP_MINI
done
