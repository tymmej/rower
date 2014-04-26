#!/bin/sh

BASE_PATH="/media/a1f63e22-1c18-4ff1-b63c-f4fcda0408eb/www/rower/"
cd $BASE_PATH

cp example/gpx.json.empty users/tymmej/gpx.json
chown php:www-data users/tymmej/gpx.json

for FILE in users/tymmej/gpx/*.gpx
do
	echo $FILE
	DESC=`grep \<name\> $FILE | sed -e 's/<trk><name>//' -e 's/<\/name>//' -e 's/\s//'`
	FILE=`echo $FILE | sed -e 's/users\/tymmej\/gpx\///'`
	php process.php $FILE "$DESC"
	sleep 4
done
