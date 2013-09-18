#!/bin/bash

#variables
ADDRESS="tymejczyk.pl"
PORT="23"
USER="tymmej"

#rsync options
OPTIONS="-rtDHxl --delete --delete-excluded"

#which enabled, start at 0
#look at paths()
enabled=(1 1 1 1)

#get options from cli
#before paths, becouse of $ADDRESS
while getopts "hwnaldp" options
do
case $options in
	h)
		echo "Backup
Usage:
-l [local]
-d [dry-run]
-p [progress]"
		exit 1
		;;
	l)
		ADDRESS="192.168.1.4"
		PORT="22"
		;;
	d)
		OPTIONS="$OPTIONS -n"
		;;
	p)
		OPTIONS="$OPTIONS --stats --progress"
		;;
	esac
done

#local paths
paths=("$USER@$ADDRESS:/www/dane/tymmej/rower/gpx"
	"$USER@$ADDRESS:/www/dane/tymmej/rower/maps"
	"$USER@$ADDRESS:/www/dane/tymmej/rower/gpx.json"
	"/home/tymmej/documents/gpx/"
)

#dest paths
rpaths=("/home/tymmej/documents/gpx/"
	"/home/tymmej/documents/gpx/"
	"/home/tymmej/documents/gpx/"
	"$USER@$ADDRESS:/www/dane/tymmej/rower"
)


#run rsync
for index in ${!paths[*]}
do
	extra=()
	if [ ${enabled[$index]} -eq 1 ]; then
        echo ""
		echo "<===============================================================================>"
        echo "${paths[$index]} -> ${rpaths[$index]}"
		cmd="rsync $OPTIONS ${extra[@]} \
			-e 'ssh -p '"$PORT" "${paths[$index]}" \
			"${rpaths[$index]}""
		eval $cmd
        echo ">===============================================================================<"
	fi
done
