#!/bin/bash

#variables
ADDRESS="tymejczyk.pl"
USERNAME="tymmej"
OPTIONS="-a --stats --delete --delete-excluded --chmod=Du=rwx,Dg=rx,Do=rx,Fu=rw,Fg=r,Fo=r  --password-file=/home/tymmej/documents/rower/pass --exclude=pass"

#which enabled, start at 0
#look at paths()
enabled=(1 1)

#get options from cli
#before paths, becouse of $ADDRESS
while getopts "hldp" options
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
		ADDRESS="192.168.1.6"
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
paths=("$USERNAME@$ADDRESS::www/rower/users"
	"/home/tymmej/documents/rower"
)

#dest paths
rpaths=("/home/tymmej/documents/rower"
	"$USERNAME@$ADDRESS::www"
)

for index in ${!paths[*]}
do
	if [ ${enabled[$index]} -eq 1 ]; then
	echo ""
		echo "<===============================================================================>"
	echo "${paths[$index]} -> ${rpaths[$index]}"
		rsync $OPTIONS $extra \
			"${paths[$index]}" \
			"${rpaths[$index]}"
	echo ">===============================================================================<"
	fi
done
