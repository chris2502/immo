#!/bin/bash

FAIL=0

if [[ $# -lt 1 ]]
then
	FAIL=1
fi

if [[ -d $1 ]]
then
	DIR=$1
else
	FAIL=1
fi

if [[ $FAIL -eq 1 ]]
then
        echo -e "Usage: $0 DIR"
	echo -e "Rétabli les fichiers préalablement converti avec le script convertToNewORM.sh"
	exit 0
fi

for savfile in `ls $DIR/class.*.php.sav`
do
 FILE=${savfile:0:-4}
 echo $FILE
 cp $savfile $FILE
 rm -f $savfile
done
