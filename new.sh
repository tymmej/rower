#!/bin/sh

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
viking ~/documents/gpx/viking "$FILE" &
PID=$!
MAP=`echo $FILE | sed -e 's/gpx\///' -e 's/gpx/png/'`
scrot -d 2 ~/documents/gpx/"$MAP"
kill $PID

#convert screenshot
convert -crop 1396x791+202+87 "$MAP" "$MAP"
MAP_MINI="mini-$MAP"
convert -resize 20% "$MAP" "$MAP_MINI"

#move screenshot to folder
mv "$MAP" maps/
mv "$MAP_MINI" maps/

#get stats from gpx file
STATS=`./gpxstats.py "$FILE"`
DIST=`echo "$STATS" | awk '{print $1}'`
TIME=`echo "$STATS" | awk '{print $2}'`

#add stats to json
head -n -2 www/gpx.json > www/gpx.old.json
echo ",
{
\"name\": \"$FILE\",
\"desc\": \"$DESC\",
\"dist\": \"$DIST\",
\"time\": \"$TIME\",
\"tags\": \"\"
}
]}
" >> www/gpx.old.json
mv www/gpx.old.json www/gpx.json

#transfer files
scp -P 23 maps/"$MAP" tymmej@tymejczyk.pl:/www/dane/tymmej/rower/maps/
scp -P 23 maps/"$MAP_MINI" tymmej@tymejczyk.pl:/www/dane/tymmej/rower/maps/
scp -P 23 www/gpx.json tymmej@tymejczyk.pl:/www/dane/tymmej/rower/
scp -P 23 "$FILE" tymmej@tymejczyk.pl:/www/dane/tymmej/rower/gpx/
