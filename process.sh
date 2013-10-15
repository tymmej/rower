#!/bin/sh

BASE_PATH="/media/a1f63e22-1c18-4ff1-b63c-f4fcda0408eb/www/rower/"
cd $BASE_PATH

FILE="$1"

#create screenshot
URL=`echo $FILE | sed -e 's/.gpx//'`
sudo -u root firefox --display=:1 gpx-auto.html?file=$URL &
PID=$!
MAP=`echo $FILE | sed -e 's/gpx\///' -e 's/gpx/png/'`
sleep 14
DISPLAY=:1 import -window root -crop 1550x800+25+50 maps/$MAP
sleep 2
DISPLAY=:1 import -window root -crop 1550x800+25+50 maps/$MAP
sudo -u root kill $PID

MAP_MINI="mini-$MAP"
convert -resize 20% maps/$MAP maps/$MAP_MINI
