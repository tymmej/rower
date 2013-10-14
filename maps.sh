#!/bin/sh

for FILE in gpx/*.gpx
do
FILE=`echo $FILE | sed -e 's/gpx\///'`
URL=`echo $FILE | sed -e 's/.gpx//'`
firefox --display=:1 gpx-auto.html?file=$URL &
PID=$!
MAP=`echo $FILE | sed -e 's/gpx\///' -e 's/gpx/png/'`
sleep 20
DISPLAY=:1 import -window root -crop 1550x800+25+50 maps/$MAP
kill $PID
MAP_MINI="mini-$MAP"
convert -resize 20% maps/$MAP maps/$MAP_MINI
done
