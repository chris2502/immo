#!/bin/bash

SIMULATE=0
FAIL=0
DIR=

for param in "$@"
do
	case $param in
	'-s')
		SIMULATE=1
		;;
	*)
		if [[ -d $param ]]
		then
			DIR=$param
		else
			FAIL=1
			echo 'FAIL!'
		fi
		;;
	esac
done

if [[ -z $DIR ]]
then
	FAIL=1
fi

if [[ $# -lt 1 ]]
then
	FAIL=1
fi

if [[ $FAIL -eq 1 ]]
then
	echo -e "Usage: $0 [-s] DIR"
#	echo -e "\tDIR : Répertoire contenant les fichiers à traiter."
	echo -e "Ce script permet de convertir une application utilisant une ancienne version de Killi afin"
	echo -e "d'utiliser les nouvelles fonctionnalités de l'ORM permettant le chargement dynamique."
	echo -e "Les fichiers originaux sont sauvegardés avec l'extension .sav."
	echo -e ""
	echo -e "Action:"
	echo -e "\tRemplace les occurences de 'new ORM(...)' par 'ORM::getORMInstance(...)'."
	echo -e "\tSupprime les occurences inutiles de 'instance_object_list'."
	echo -e "\tSupprime les occurences inutiles de 'extended_instance_object_list'."
	echo -e "\tRemplace les récupérations d'objets par 'ORM::getObjectInstance(...)'."
	echo -e "\tSupprime les instanciations d'objets dans '\$object_list'."
	echo -e "\tRemplace les instanciations des controleurs (objetMethod) par 'ORM::getControllerInstance(...)'."
	echo -e ""
	echo -e "Options:"
	echo -e "\t-s\tSimule : Affiche les lignes concernés par des modifications."
	echo -e ""
	exit 1
fi

PATTERN_1='new\( \+\)ORM(\( *\)\$\(this->\)\?\(extended_\)\?instance_object_list\[\(.*\)\]'
PATTERN_2='global\( \+\)\$\(extended_\)\?instance_object_list\( *\);'
PATTERN_3='global\( \+\)\$\(extended_\)\?instance_object_list,\( *\)\$\(.*\);'
PATTERN_4='global\( \+\)\$\(.*\),\( *\)\$\(extended_\)\?instance_object_list;'
PATTERN_5='public\( \+\)\$\(extended_\)\?instance_object_list;'
PATTERN_6='\$\(this->\)\?\(extended_\)\?instance_object_list\( \+\)=\( \+\)\(&\)\?$\(extended_\)\?instance_object_list\( *\);'
PATTERN_7='\$object_list\( *\)\[\]\( \+\)=\( \+\)new\( \+\)\(.*\)();'
PATTERN_8='new\( \)*\([a-zA-Z]\)*Method('

if [[ $SIMULATE -eq 1 ]]
then
	grep -n --color -e "$PATTERN_1" -e "$PATTERN_2" -e "$PATTERN_3" -e "$PATTERN_4" -e "$PATTERN_5" -e "$PATTERN_6" -e "$PATTERN_7" -e "$PATTERN_8" $DIR/class.*.php
	exit 0
fi

for file in `ls $DIR/class.*.php`
do
 echo "Conversion de $file..."
 OLDFILE="$file.sav"
 #echo $OLDFILE
 if [[ -f $OLDFILE ]]
 then
	echo "Fichier de destination existant : $OLDFILE"
	exit 1;
 fi

 cp $file $OLDFILE
 if [[ $? -eq 0 ]]
 then
	 sed -i 's/new\( \+\)ORM(\( *\)\$\(this->\)\?\(extended_\)\?instance_object_list\[\(.*\)\]/ORM::getORMInstance(\5/' $file
	 sed -i 's/global\( \+\)\$\(extended_\)\?instance_object_list\( *\);//' $file
         sed -i 's/global\( \+\)\$\(extended_\)\?instance_object_list,\( *\)\$\(.*\);/global \$\4;/' $file
	 sed -i 's/global\( \+\)\$\(.*\),\( *\)\$\(extended_\)\?instance_object_list;/global \$\2;/' $file
	 sed -i 's/public\( \+\)\$\(extended_\)\?instance_object_list;//' $file
	 sed -i 's/\$\(this->\)\?\(extended_\)\?instance_object_list\( \+\)=\( \+\)\(&\)\?$\(extended_\)\?instance_object_list\( *\);//' $file
	 if [[ -z `echo $file | grep Method` ]]
	 then
	 	sed -i 's/\$object_list\( *\)\[\]\( \+\)=\( \+\)new\( \+\)\(.*\)();//' $file
	 fi
	sed -i 's/new\( \)*\([a-zA-Z]*\)Method(/ORM::getControllerInstance("\2"/' $file
 fi
done
