#!/bin/sh

cd /www/dane/tymmej/rower/tmp

#get parameters
FILE="$1"
DESC="$2"

if [ ! -e "$1" ]; then
    exit 1
fi

NAME=`grep "<name>" "$FILE" | wc -l`
if [ $NAME -eq 0 ]; then
	sed -i 's/<trk>/<trk><name>'$2'<\/name>/g' "$FILE"
fi

#create screenshot
viking --display=:1 ../viking "$FILE" &
PID=$!
MAP=`echo $FILE | sed -e 's/gpx\///' -e 's/gpx/png/'`
sleep 6
DISPLAY=:1 import -window root -crop 1400x795+200+78 ../maps/$MAP
kill $PID

MAP_MINI="mini-$MAP"
convert -resize 20% ../maps/$MAP ../maps/$MAP_MINI

#get stats from gpx file
STATS=`../python/gpxstats.py "$FILE"`
DIST=`echo "$STATS" | awk '{print $1}'`
TIME=`echo "$STATS" | awk '{print $2}'`

#add stats to json
NAME=`echo $FILE | sed -e 's/gpx\///'`
head -n -2 ../gpx.json > ../gpx.old.json
echo ",
{
\"name\": \"$NAME\",
\"desc\": \"$DESC\",
\"dist\": \"$DIST\",
\"time\": \"$TIME\",
\"tags\": \"\"
}
]}
" >> ../gpx.old.json
mv ../gpx.old.json ../gpx.json
mv $FILE ../gpx/$FILE
