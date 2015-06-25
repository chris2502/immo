<?php

function __setDefaultDefine($define_name, $default_value = null)
{
	if(!defined($define_name))
	{
		define($define_name, $default_value);
	}
}

DEFINE('KILLI_VERSION', 1.4);

/**
 * Killi configuration
 * Valeurs par défaut
 */

__setDefaultDefine('FIREPHP_ENABLE', FALSE);
__setDefaultDefine('CHECK_SECURITY_ISSUE', FALSE);
__setDefaultDefine('LOG_FILE', './log/error.log');
__setDefaultDefine('SEARCH_VIEW_NUM_RECORDS', 200);
__setDefaultDefine('DISPLAY_ERRORS', TRUE);
__setDefaultDefine('HOME_PAGE','user.edit');
__setDefaultDefine('KILLI_SCRIPT',FALSE);
__setDefaultDefine('LOG_USER_ACTION',FALSE);
__setDefaultDefine('TOLERATE_MISMATCH',FALSE);
__setDefaultDefine('LOCK_DIR','./log');
__setDefaultDefine('LOCK_SUFFIX','.lock');
__setDefaultDefine('LOCK_TIMEOUT',300);
__setDefaultDefine('LOCAL_FILESTORE','/doc');
__setDefaultDefine('TRACE_SLOW_QUERIES_TIME_TOLERANCE',100);
__setDefaultDefine('TRACE_DUPLICATE_QUERIES_TOLERANCE',50);
__setDefaultDefine('DONT_SET_NAMES',FALSE);
__setDefaultDefine('HEADER_DESCRIPTION','');
__setDefaultDefine('HEADER_MESSAGE','Killi app');
__setDefaultDefine('DISABLE_CACHE',FALSE);
__setDefaultDefine('UI_THEME',NULL);
__setDefaultDefine('ERROR_EMAILS',NULL);
__setDefaultDefine('ERROR_MAIL_SUBJECT','ERROR');
__setDefaultDefine('ERROR_PAGE_FILE',null);
__setDefaultDefine('PUBLIC_ERROR_MESSAGE',null);
__setDefaultDefine('THROW_SQL_WARNINGS', TRUE);
__setDefaultDefine('DO_NOT_LOCK_SESSION_ON_EXTRACT', FALSE);
__setDefaultDefine('DISABLE_APC_COUNT_CACHE', FALSE);
__setDefaultDefine('NO_FAKE_PASSWORD', DISPLAY_ERRORS);
__setDefaultDefine('CURL_INTERNALCOUNTER_LIMIT', 8);
__setDefaultDefine('MAINTENANCE', FALSE);
__setDefaultDefine('ALLOWED_USER_MAINTENANCE', '');

// pour phpunit uniquement
__setDefaultDefine('TESTS_DATABASE','killi_testsuite');

__setDefaultDefine('APP_MAIL_FROM','noreply@free-infra.fr');


/* Node type */
__setDefaultDefine('NODE_TYPE_INTERFACE',1);
__setDefaultDefine('NODE_TYPE_SCRIPT',2);
__setDefaultDefine('NODE_TYPE_ENTRY_POINT',3);
__setDefaultDefine('NODE_TYPE_END',4);
__setDefaultDefine('NODE_TYPE_QTY',5);
__setDefaultDefine('NODE_TYPE_MESSAGE',6);

/* Profils */
__setDefaultDefine('ADMIN_PROFIL_ID',1);
__setDefaultDefine('READONLY_PROFIL_ID',null); // profil lecture seule désactivé par défaut
__setDefaultDefine('SYSTEM_PROFIL_ID',null); // profil SYSTEM désactivé par défaut

/* etats document */
__setDefaultDefine('ETAT_DOCUMENT_NON_VERIFIE',1);
__setDefaultDefine('ETAT_DOCUMENT_CONFORME',2);
__setDefaultDefine('ETAT_DOCUMENT_NON_CONFORME',3);


/* server config */
mb_internal_encoding("UTF-8");
date_default_timezone_set( 'Europe/Paris' ) ;