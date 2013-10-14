#!/bin/sh

BASE_PATH="/media/a1f63e22-1c18-4ff1-b63c-f4fcda0408eb/www/rower/"
cd $BASE_PATH

FILE="$1"

#create screenshot
viking --display=:1 viking gpx/$FILE &
PID=$!
MAP=`echo $FILE | sed -e 's/gpx\///' -e 's/gpx/png/'`
sleep 6
DISPLAY=:1 import -window root -crop 1375x790+220+85 maps/$MAP
sleep 2
DISPLAY=:1 import -window root -crop 1375x790+220+85 maps/$MAP
kill $PID

MAP_MINI="mini-$MAP"
convert -resize 20% maps/$MAP maps/$MAP_MINI
