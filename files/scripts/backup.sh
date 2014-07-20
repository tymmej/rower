#!/bin/bash

#variables
address="tymejczyk.pl"	#address visible from wan
sshport=23				#ssh port
laddress="192.168.1.6"	#address in lan for faster transfer
username="www-data"		#name of rsync user
options="-a --stats --delete --delete-excluded --chmod=Du=rwx,Dg=rx,Do=rx,Fu=rw,Fg=r,Fo=r"	#rsync options
local=0					#0-wan, 1-lan
usb=0					#0-backup to rsync, 1-backup to usb drive
lpath="/mnt/backup"		#path to usb drive
rpath="/data/external"	#path in remote drive


#get options from cli
#before paths, because of $address
while getopts "hldpu" args
do
case $args in
	h)
		echo "Backup
Usage:
-l [local]
-d [dry-run]
-p [progress]"
		exit 1
		;;
	l)
		address=$laddress
		sshport=22
		local=1
		;;
	d)
		options="$options -n"
		;;
	p)
		options="$options --stats --progress"
		;;
	u)
		usb=1
		;;
	esac
done

#check if we are on local network
if [ $local -eq 0 ]; then
	response=`curl -s -k --connect-timeout 2 https://$username@$laddress/rower | sed '2q;d' | grep 301 | wc -l`
	if [ $response -eq 1 ]; then
		address=$laddress
		sshport=22
		local=1
	fi
fi

enabled=(1 1)

spaths=("$username@$address:/www/rower/users"
	"/home/tymmej/documents/documents/Programowanie/rower/files/"
)

dpaths=("/home/tymmej/documents/documents/Programowanie/rower/files"
	"$username@$address:/www/rower/"
)

if [ $local -eq 0 -a $usb -eq 0 ]; then
	sshopt="-p $sshport"
else
	sshopt=""
fi

for index in ${!spaths[*]}
do
	if [ ${enabled[$index]} -eq 1 ]; then
	echo ""
		echo "<===============================================================================>"
		echo "${spaths[$index]} -> ${dpaths[$index]}"
			rsync $options -e "ssh $sshopt" "${extra[@]}" \
				"${spaths[$index]}" \
				"${dpaths[$index]}"
		echo ">===============================================================================<"
	fi
done
