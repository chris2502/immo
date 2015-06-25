#################################
#                               #
# Exécution des tests unitaires #
#                               #
#################################

- Suite de tests unitaires

$ ./runtest.sh -v --colors tests.php

- Couverture de code

$ ./runtest.sh -v --colors --coverage-html <coverage> tests.php

<coverage> : Répertoire de destination des fichiers de couverture.

Exemple : $ ./runtest.sh -v --colors --coverage-html html tests.php


--

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
-----------------------------------------
/***************************************/

           ANCIENNE METHODE



####################################
#                                  #
# Installation des tests unitaires #
#                                  #
####################################


$ sudo apt-get install php5-xdebug
$ sudo apt-get remove phpunit
$ sudo pear channel-discover pear.phpunit.de
$ sudo pear channel-discover pear.symfony-project.com
$ sudo pear channel-discover components.ez.no
$ sudo pear channel-discover pear.symfony.com
$ sudo pear update-channels
$ sudo pear upgrade-all
$ sudo pear install --alldeps phpunit/PHPUnit
$ sudo pear install --alldeps phpunit/PHPUnit_Selenium

#################################
#                               #
# Exécution des tests unitaires #
#                               #
#################################

- Suite de tests unitaires

$ phpunit -v --colors tests.php

- Couverture de code

$ phpunit -v --colors --coverage-html <coverage> tests.php

<coverage> : Répertoire de destination des fichiers de couverture.

Exemple : $ phpunit -v --colors --coverage-html html tests.php


