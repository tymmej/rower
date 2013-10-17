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

if [ $2 -eq 1 ]; then
	grep -E 'desc|name' gpx.json.bak | sed -e 's/\s//g' -e 's/name//g' -e 's/desc//g' -e 's/[,:"]//g' > new.json
fi

cp example/gpx.json.empty gpx.json

for FILE in gpx/*.gpx
do
	DESC=`grep \<name\> $FILE | sed -e 's/<trk><name>//' -e 's/<\/name>//' -e 's/\s//'`
	FILE=`echo $FILE | sed -e 's/gpx\///'`
	NUMBER=`grep -n $FILE new.json | awk -F: '{print $1}'`
	NUMBER=$(($NUMBER + 1))

	#if number equals 1 number was 0 or unset->no record in new.json
	if [ $NUMBER -ne 1 ]; then
		echo $NUMBER
		DESC=`sed -n "${NUMBER}{p;q;}" new.json`
		if [ $STATS -eq 1 ]; then
			php process.php $FILE "$DESC"
		fi
		if [ $MAPS -eq 1 ]; then
			./process.sh $FILE
		fi
	fi
done
