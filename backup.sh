#!/bin/bash

#variables
ADDRESS="tymejczyk.pl"
PORT="23"
USER="root"

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
paths=("$USER@$ADDRESS:/media/a1f63e22-1c18-4ff1-b63c-f4fcda0408eb/www/rower/gpx"
	"$USER@$ADDRESS:/media/a1f63e22-1c18-4ff1-b63c-f4fcda0408eb/www/rower/maps"
	"$USER@$ADDRESS:/media/a1f63e22-1c18-4ff1-b63c-f4fcda0408eb/www/rower/gpx.json"
	"/home/tymmej/documents/rower"
)

#dest paths
rpaths=("/home/tymmej/documents/rower"
	"/home/tymmej/documents/rower"
	"/home/tymmej/documents/rower"
	"$USER@$ADDRESS:/media/a1f63e22-1c18-4ff1-b63c-f4fcda0408eb/www"
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
