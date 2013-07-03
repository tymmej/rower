#!/bin/sh

FILE="$1"
DESC="$2"
DIST="$3"
TIME="$4"
viking ~/documents/gpx/viking "$FILE" &
PID=$!
FILE=`echo $FILE | sed -e 's/gpx/png/g'`
scrot -d 2 ~/documents/gpx/"$FILE"
kill $PID

convert -crop 1396x791+202+87 "$FILE" "$FILE"
FILE_MINI=`echo "$FILE" | sed 's/^/mini-/'`
convert -resize 20% "$FILE" "$FILE_MINI"
mv "$FILE" www/maps/
mv "$FILE_MINI" www/maps/

scp -P 23 www/maps/"$FILE" tymmej@tymejczyk.pl:/www/dane/tymmej/rower/maps/
scp -P 23 www/maps/"$FILE_MINI" tymmej@tymejczyk.pl:/www/dane/tymmej/rower/maps/

FILE=`echo "$FILE" | sed -e 's/png/gpx/g'`

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

scp -P 23 www/gpx.json tymmej@tymejczyk.pl:/www/dane/tymmej/rower/
