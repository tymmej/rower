#!/bin/sh

#get parameters
FILE="$1"
DESC="$2"

NAME=`grep "<name>" "$FILE" | wc -l`
if [ $NAME -eq 0 ]; then
	sed -i 's/<trk>/<trk><name>'$2'<\/name>/g' "$FILE"
fi

#create screenshot
viking ~/documents/gpx/viking "$FILE" &
PID=$!
FILE=`echo $FILE | sed -e 's/gpx/png/g'`
scrot -d 2 ~/documents/gpx/"$FILE"
kill $PID

#convert screenshot
convert -crop 1396x791+202+87 "$FILE" "$FILE"
FILE_MINI=`echo "$FILE" | sed 's/^/mini-/'`
convert -resize 20% "$FILE" "$FILE_MINI"

#move screenshot to folder
mv "$FILE" www/maps/
mv "$FILE_MINI" www/maps/

FILE=`echo "$FILE" | sed -e 's/png/gpx/g'`

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
FILE=`echo $FILE | sed -e 's/gpx/png/g'`
scp -P 23 www/maps/"$FILE" tymmej@tymejczyk.pl:/www/dane/tymmej/rower/maps/
FILE=`echo "$FILE" | sed -e 's/png/gpx/g'`
scp -P 23 www/maps/"$FILE_MINI" tymmej@tymejczyk.pl:/www/dane/tymmej/rower/maps/
scp -P 23 www/gpx.json tymmej@tymejczyk.pl:/www/dane/tymmej/rower/
scp -P 23 "$FILE" tymmej@tymejczyk.pl:/www/dane/tymmej/rower/gpx/
