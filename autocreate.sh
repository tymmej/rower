#!/bin/sh

BASE_PATH="/media/a1f63e22-1c18-4ff1-b63c-f4fcda0408eb/www/rower/"
cd $BASE_PATH

MODE=$1
STATS=0
MAPS=0

if [ $MODE -eq 1 -o $MODE -eq 3 ]; then
	STATS=1
fi

if [ $MODE -eq 2 -o $MODE -eq 3 ]; then
	MAPS=1
fi

cp example/gpx.json.empty gpx.json
chown php:www-data gpx.json

for FILE in gpx/*.gpx
do
	DESC=`grep \<name\> $FILE | sed -e 's/<trk><name>//' -e 's/<\/name>//' -e 's/\s//'`
	FILE=`echo $FILE | sed -e 's/gpx\///'`
	if [ $STATS -eq 1 ]; then
		php process.php $FILE "$DESC"
	fi
	if [ $MAPS -eq 1 ]; then
		./process.sh $FILE
	fi
done
