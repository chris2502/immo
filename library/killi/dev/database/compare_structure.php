#!/usr/bin/env php
<?php

function get_conf_value($options, $cnx_datas, $key, $default_value)
{
        if(!empty($options[$key]))
        {
            return $options[$key];
        }
        elseif(!empty($cnx_datas[$key]))
        {
            return $cnx_datas[$key];
        }
        else
        {
            return $default_value;
        }

}

function structure($options=false)
{
    $table_schema  = (!empty($options['database']))?"'".$options['database']."'":"database()";
    $exclude_table = (!empty($options['exclude_table']))?explode(',',$options['exclude_table']):array();
    $mysql_file    = (!empty($options['mysql_file']))?$options['mysql_file']:'mysql.inc.php';
    $cnx_datas     = parse_ini_file($mysql_file);

    if(!is_array($cnx_datas))
    {
        require_once($mysql_file);
    }
    else
    {
        $mysql_base = get_conf_value($options , $cnx_datas , 'database' , '');

        if($mysql_handle=mysql_connect(get_conf_value($options , $cnx_datas , 'host' , 'localhost'),get_conf_value($options , $cnx_datas , 'user' , getenv('USER')),get_conf_value($options , $cnx_datas , 'password' , '')))
        {
            if(!mysql_select_db($mysql_base,$mysql_handle))
            {
                file_put_contents('php://stderr',"Impossible de sélectionner la base '".$mysql_base."'.\n");
                exit(1);
            }
        }
        else
        {
            file_put_contents('php://stderr',"Impossible de se connecter via '".$mysql_file."'.\n");
            exit(1);
        }
        //*/
    }
    $structure=array();
    $unordered=array();
    $query="
    select
        table_schema,
        table_name,
        column_name,
        column_type,
        is_nullable,
        column_default
    from
        information_schema.columns
    where
        table_schema=".$table_schema."
    and
        table_name not in('".implode("','",$exclude_table)."')
    order by
        table_schema,
        table_name,
        ordinal_position
    ;";
    $result=mysql_query($query);
    if(!$result){
        die("Erreur SQL, (".__LINE__.") : ".mysql_error());
    }
    $rows=array();
    $nb_rows=mysql_num_rows($result);
    if($nb_rows>=1){
        while($row=mysql_fetch_assoc($result)){
            if(empty($unordered[$row['table_schema']][$row['table_name']]['create_table']))
            {
                $create_table_query="show create table `".$row['table_schema']."`.`".$row['table_name']."`;";
                $create_table_result=mysql_query($create_table_query);
                if(!$create_table_result){
                    die("Erreur SQL, (".__LINE__.") : ".mysql_error()."\n".$create_table_query);
                }
                $create_table_rows=array();
                $create_table_nb_rows=mysql_num_rows($create_table_result);
                $create_table_row=mysql_fetch_assoc($create_table_result);
                if(!array_key_exists('Create Table',$create_table_row))
                {
                    if(!array_key_exists('Create View',$create_table_row))
                    {
                        file_put_contents('php://stderr',"Impossible de déterminer la structure de '".$row['table_name']."'.\nL'entrée a été ignoré !\n");
                        continue;
                    }
                    else
                    {
                        $create_table=$create_table_row['Create View'];
                    }
                }
                else
                {
                    $create_table=$create_table_row['Create Table'];
                }
                $create_table=ereg_replace(' AUTO_INCREMENT=[0-9]*',null,$create_table);
                $unordered[$row['table_schema']][$row['table_name']]['create_table']=$create_table;
                // preg_match('/CONSTRAINT `.*` FOREIGN KEY \(`.*`\) REFERENCES `(?P<dependece>.*)` \(`.*`\)/mUS',$create_table,$matchs);
                $dependency=array();
                foreach(explode("\n",$create_table) as $line)
                {
                    if(preg_match('/CONSTRAINT `.*` FOREIGN KEY \(`.*`\) REFERENCES `(.*)` \(`.*`\)/',$line,$matchs))
                    {
                        array_push($dependency,$matchs[1]);
                    }
                }
                $unordered[$row['table_schema']][$row['table_name']]['dependency']=$dependency;
            }
            $unordered[$row['table_schema']][$row['table_name']][$row['column_name']]=array('column_type'=>$row['column_type'],'is_nullable'=>$row['is_nullable'],'column_default'=>$row['column_default']);
        }
    }
    return $unordered;
}

function compare($options=false)
{
    if(empty($options['compare_file']))
    {
        echo "Le chemin du fichier de comparaison ( option -c ) ne peu pas être vide !\n";
        echo($usage);
        exit(1);
    }
    if(!file_exists($options['compare_file']) && $options['compare_file']!='php://stdin')
    {
        echo "Le fichier de comparaison '".$options['compare_file']."' n'existe pas !\n";
        exit(1);
    }
    $compare=json_decode(file_get_contents($options['compare_file']),true);
    if(!$compare)
    {
        echo "Impossible de charger '".$options['compare_file']."' !\n";
        exit(1);
    }
    if(!empty($options['structure_file']))
    {
        if($options['structure_file']==$options['compare_file'])
        {
            echo "Les fichiers de structure et de comparaison ne peuvent pas être identique !\n";
            exit(1);
        }
        if(!file_exists($options['structure_file']))
        {
            echo "Le fichier de structure '".$options['structure_file']."' n'existe pas !\n";
            exit(1);
        }
        $structure=json_decode(file_get_contents($options['structure_file']),true);
        if(!$structure)
        {
            echo "Impossible de charger '".$options['structure_file']."' !\n";
            exit(1);
        }
    }
    else
    {
        $structure=structure($options);
    }
    foreach($structure as $table_schema=>$schema)
    {
        foreach($schema as $table_name=>$table)
        {
            $allready_done=false;
            if(empty($compare[$table_schema][$table_name]))
            {
                file_put_contents($options['output_file'],$structure[$table_schema][$table_name]['create_table'].";\n");
                $allready_done=true;
            }else{
                foreach($table as $column_name=>$column_data)
                {
                    if($column_name!='create_table' && $column_name!='dependency' && $allready_done==false)
                    {
                        if(empty($compare[$table_schema][$table_name][$column_name]))
                        {
                            $column_definition=trim($column_data['column_type'].' '.((empty($column_data['column_default']))?(($column_data['is_nullable']=='YES')?'default null':''):"default '".$column_data['column_default']."'"));
                            file_put_contents($options['output_file'],"alter table `".$table_schema."`.`".$table_name."` add column `".$column_name."` ".$column_definition.";\n");
                        }
                        elseif($compare[$table_schema][$table_name][$column_name]!=$structure[$table_schema][$table_name][$column_name])
                        {
                            // print_r($structure[$table_schema][$table_name][$column_name]);
                            $column_definition=trim($column_data['column_type'].' '.((empty($column_data['column_default']))?(($column_data['is_nullable']=='YES')?'default null':''):"default '".$column_data['column_default']."'"));
                            file_put_contents($options['output_file'],"alter table `".$table_schema."`.`".$table_name."` change column `".$column_name."` `".$column_name."` ".$column_definition.";\n");
                        }
                    }
                }
            }
            foreach($table['dependency'] as $dependency)
            {
                /*
                file_put_contents($options['output_file'],print_r(array($table_name=>array(
                    'compare.'.$dependency=>array_key_exists($dependency,$compare[$table_schema]),
                    'structure.'.$dependency=>array_key_exists($dependency,$structure[$table_schema])
                )),true));
                */
                if(!array_key_exists($dependency,$compare[$table_schema]) && !array_key_exists($dependency,$structure[$table_schema]))
                {
                    file_put_contents($options['output_file'],"# /!\ La dépendance entre '".$table_name."' et '".$dependency."' semble ne pas être satisfaite /!\\\n");
                }
            }
        }
    }
    if($options['drop']==true)
    {
        foreach($compare as $table_schema=>$schema)
        {
            foreach($schema as $table_name=>$table)
            {
                $allready_done=false;
                if(empty($structure[$table_schema][$table_name]))
                {
                    file_put_contents($options['output_file'],"drop table if exists ".$table_name.";\n");
                    $allready_done=true;
                }else{
                    foreach($table as $column_name=>$column_data)
                    {
                        if($column_name!='create_table' && $column_name!='dependency' && $allready_done==false)
                        {
                            if(empty($structure[$table_schema][$table_name][$column_name]))
                            {
                                file_put_contents($options['output_file'],"alter table `".$table_schema."`.`".$table_name."` drop column `".$column_name."`;\n");
                            }
                        }
                    }
                }
            }
        }
    }
    return true;
}

$usage="Usage :
".$_SERVER['PHP_SELF']." -a|--action (extract|compare) -b|--base 'nom de la base de données' -s|--structure 'fichier.json' -c|--compare 'fichier.json' -o|--output 'fichier.json' -d|--drop -m|--mysql 'fichier.php|.my.cnf' -x|--exclude-table 'table,table' -h|--help
";
$help=$_SERVER['PHP_SELF']." est un systeme de dump d'une structure mysql a des fins de comparaison.
Le principe étant de créer et de comparer des sérialization json de structure mysql pour produire un jeu de requêtes capable de mettre a niveau une structure défficiente.

".$usage."
Les options disponibles sont :
-a|--action action                    Action a effectuer ( par défaut extract  ).
-b|--base 'database name'             Nom de la base de donnée a partir de laquelle créer la structure ( par défaut la base courante, la base doit exiter ! ).
-s|--structure 'fichier.json'         Nom du fichier de structure a importer en lieu et place de l'analyse courante (fichier|stdin|-).
-c|--compare 'fichier.json'           Nom du fichier de structure avec lequel comparer la structure courante ou la structure importé (fichier|stdin|-)
-o|--output 'fichier.json'            Nom du fichier de sortie vers lequel serat enregistré la sérialization courante ou le delta sql (fichier|stdout*|-).
-d|--drop                             Afficher les requêtes 'drop' afin de néttoyer les tables ou champs qui ne sont plus nécessaires.
-m|--mysql 'fichier php'              Nom du fichier de connexion mysql ou fichier .my.cnf ( par défaut .my.cnf ).
-x|--exclude-table 'table,table'      Liste des tables séparées par des virgulent et ne devant pas être analysées / exportées.
-h|--host                             Host de connexion mysql /!\ n'est pris en compte que si -m n'est pas précisé /!\
-u|--user                             Utilisateur de connexion mysql /!\ n'est pris en compte que si -m n'est pas précisé /!\
-p|--password                         Mot de passe de connexion mysql /!\ n'est pris en compte que si -m n'est pas précisé /!\
--help                                Ce message d'aide.

exemple de pipe : ssh -t root@fmlog /var/www/compare_structure.php -a extract -b fmlogistique 2> /dev/null | ./compare_structure.php -b fmlogistique -m ../includes/mysql.inc.php -a compare -c stdin

La structure de référence est toujours la base actuelle ( ou le fichier structure --structure ) et doit permettre de mettre a niveau la base comparée ( --compare )
Dans notre exemple la partie de gauche du pipe est celle qui vas être comparé a notre machine local '-c stdin' ( qui se peuple avec le contenue du pipe )
Le résultat renvois les modifications a faire sur le serveur de gauche ( fmlog ) pour le mettre a niveau avec la machine de droite ( locale ).

";
$args['action']='extract';
$args['output_file']='php://stdout';
$args['drop']=false;
$args['mysql_file']=getenv('HOME').'/.my.cnf';

for($a=1;$a<$_SERVER['argc'];$a++)
{
    switch($_SERVER['argv'][$a])
    {
        case '-a' :
        case '--action' :
            $args['action']=$_SERVER['argv'][++$a];
            break;

        case '-b' :
        case '--base' :
            $args['database']=$_SERVER['argv'][++$a];
            break;

        case '-c' :
        case '--compare' :
            $args['compare_file']=$_SERVER['argv'][++$a];
            break;

        case '-d' :
        case '--drop' :
            $args['drop']=true;
            break;

        case '-s' :
        case '--structure' :
            $args['structure_file']=$_SERVER['argv'][++$a];
            break;

        case '-o' :
        case '--output' :
            $args['output_file']=$_SERVER['argv'][++$a];
            break;

        case '-x' :
        case '--exclude-table' :
            $args['exclude_table']=$_SERVER['argv'][++$a];
            break;
        case '-m' :
        case '--mysql' :
            $args['mysql_file']=$_SERVER['argv'][++$a];
            break;

        case '-h' :
        case '--host' :
            $args['host']=$_SERVER['argv'][++$a];
            break;

        case '-u' :
        case '--user' :
            $args['user']=$_SERVER['argv'][++$a];
            break;

        case '-p' :
        case '--password' :
            $args['password']=$_SERVER['argv'][++$a];
            break;

        case '--help' :
            echo($help);
            exit(0);
            break;
        default :
            file_put_contents('php://stderr',"'".$_SERVER['argv'][$a]."' n'est pas reconnus comme une option valide !\n");
            echo($usage);
            exit(1);
            break;
    }
}

if(!empty($args['structure_file']) && ( $args['structure_file']=='stdin' || $args['structure_file']=='-' )){
    $args['structure_file']='php://stdin';
}
if(!empty($args['compare_file']) && ( $args['compare_file']=='stdin' || $args['compare_file']=='-' )){
    $args['compare_file']='php://stdin';
}
if(!empty($args['output_file']) && ( $args['output_file']=='stdout' || $args['output_file']=='-' )){
    $args['output_file']='php://stdout';
}


switch($args['action'])
{
    case 'extract' :
        exit((file_put_contents($args['output_file'], json_encode(structure($args)))?0:1));
    break;
    case 'compare' :
        exit((compare($args))?0:1);
    break;
    default :
        echo "'".$args['action']."' n'est pas reconnus comme une action valide !\n";
        echo($usage);
        exit(1);
    break;
}
