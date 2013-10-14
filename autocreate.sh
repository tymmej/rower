#!/bin/sh

for FILE in gpx/*.gpx
do
FILE=`echo $FILE | sed -e 's/gpx\///'`
NUMBER=`grep -n $FILE new.json | awk -F: '{print $1}'`
NUMBER=$(($NUMBER + 1))

if [ $NUMBER -ne 1 ]; then
echo $NUMBER
DESC=`sed -n "${NUMBER}{p;q;}" new.json`
php autoprocess.php $FILE "$DESC"
fi
done
