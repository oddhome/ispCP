<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2011 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 * Portions created by the ispCP Team are Copyright (C) 2006-2011 by
 * isp Control Panel. All Rights Reserved.
 */

require '../include/ispcp-lib.php';

$cfg = ispCP_Registry::get('Config');

check_login(__FILE__, $cfg->PREVENT_EXTERNAL_LOGIN_RESELLER);

$tpl = ispCP_TemplateEngine::getInstance();
$template = 'index.tpl';

// dynamic page data

generate_page_data($tpl, $_SESSION['user_id'], $_SESSION['user_logged']);

// Makes sure that the language selected is the reseller's language
if (!isset($_SESSION['logged_from']) && !isset($_SESSION['logged_from_id'])) {
	list($user_def_lang, $user_def_layout) = get_user_gui_props($sql, $_SESSION['user_id']);
} else {
	$user_def_layout = $_SESSION['user_theme'];
	$user_def_lang = $_SESSION['user_def_lang'];
}

// static page messages
gen_logged_from($tpl);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('ispCP - Reseller/Main Index'),
		'TR_SAVE' => tr('Save'),
		'TR_MESSAGES' => tr('Messages'),
		'TR_LANGUAGE' => tr('Language'),
		'TR_CHOOSE_DEFAULT_LANGUAGE' => tr('Choose default language'),
		'TR_CHOOSE_DEFAULT_LAYOUT' => tr('Choose default layout'),
		'TR_LAYOUT' => tr('Layout'),
		'TR_TRAFFIC_USAGE' => tr('Traffic usage'),
		'TR_DISK_USAGE' => tr ('Disk usage')
	)
);

gen_reseller_mainmenu($tpl, 'main_menu_general_information.tpl');
gen_reseller_menu($tpl, 'menu_general_information.tpl');

gen_messages_table($tpl, $_SESSION['user_id']);

gen_def_language($tpl, $sql, $user_def_lang);

gen_def_layout($tpl, $user_def_layout);

gen_system_message($tpl, $sql);

gen_page_message($tpl);

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug($tpl);
}

$tpl->display($template);

unset_messages();

// page functions

/**
 * @param ispCP_TemplateEngine $tpl
 * @param ispCP_Database $sql
 */
function gen_system_message($tpl, $sql) {
	$user_id = $_SESSION['user_id'];

	$query = "
		SELECT
			COUNT(`ticket_id`) AS cnum
		FROM
			`tickets`
		WHERE
			(`ticket_to` = ? OR `ticket_from` = ?)
		AND
			(`ticket_status` IN ('1', '4')
			AND
			`ticket_level` = 1) OR
			(`ticket_status` IN ('2')
			AND
			`ticket_level` = 2)
		AND
			`ticket_reply` = 0
	;";

	$rs = exec_query($sql, $query, array($user_id, $user_id));

	$num_question = $rs->fields('cnum');

	if ($num_question == 0) {
		$tpl->assign(array('MSG_ENTRY' => ''));
	} else {
		$tpl->assign(
			array(
				'TR_NEW_MSGS' => tr('You have <strong>%d</strong> new support questions', $num_question),
				'NEW_MSG_TYPE' => 'info',
				'TR_VIEW' => tr('View')
			)
		);

	}
}

/**
 * @param ispCP_TemplateEngine $tpl
 * @param float $usage
 * @param float $max_usage
 * @param float $bars_max
 */
function gen_traff_usage($tpl, $usage, $max_usage, $bars_max) {
	if (0 !== $max_usage) {
		list($percent, $bars) = calc_bars($usage, $max_usage, $bars_max);
		$traffic_usage_data = tr('%1$s%% [%2$s of %3$s]', $percent, sizeit($usage), sizeit($max_usage));
	} else {
		$percent = 0;
		$bars = 0;
		$traffic_usage_data = tr('%1$s%% [%2$s of unlimited]', $percent, sizeit($usage), sizeit($max_usage));
	}

	$tpl->assign(
		array(
			'TRAFFIC_USAGE_DATA' => $traffic_usage_data,
			'TRAFFIC_BARS'       => $bars,
			'TRAFFIC_PERCENT'    => $percent,
		)
	);
}

/**
 * @param ispCP_TemplateEngine $tpl
 * @param float $usage
 * @param float $max_usage
 * @param float $bars_max
 */
function gen_disk_usage($tpl, $usage, $max_usage, $bars_max) {
	if (0 !== $max_usage) {
		list($percent, $bars) = calc_bars($usage, $max_usage, $bars_max);
		$traffic_usage_data = tr('%1$s%% [%2$s of %3$s]', $percent, sizeit($usage), sizeit($max_usage));
	} else {
		$percent = 0;
		$bars = 0;
		$traffic_usage_data = tr('%1$s%% [%2$s of unlimited]', $percent, sizeit($usage));
	}

	$tpl->assign(
		array(
			'DISK_USAGE_DATA' => $traffic_usage_data,
			'DISK_BARS'       => $bars,
			'DISK_PERCENT'    => $percent,
		)
	);
}

/**
 * @param ispCP_TemplateEngine $tpl
 * @param int $reseller_id
 * @param string $reseller_name
 */
function generate_page_data($tpl, $reseller_id, $reseller_name) {
	global $crnt_month, $crnt_year;

	$sql = ispCP_Registry::get('Db');

	$crnt_month = date("m");
	$crnt_year = date("Y");
	// global
	$tmpArr = get_reseller_default_props($sql, $reseller_id);
	if ($tmpArr != NULL) { // there are data in db
		list($rdmn_current, $rdmn_max,
			$rsub_current, $rsub_max,
			$rals_current, $rals_max,
			$rmail_current, $rmail_max,
			$rftp_current, $rftp_max,
			$rsql_db_current, $rsql_db_max,
			$rsql_user_current, $rsql_user_max,
			$rtraff_current, $rtraff_max,
			$rdisk_current, $rdisk_max
		) = $tmpArr;
	} else {
		list($rdmn_current, $rdmn_max,
			$rsub_current, $rsub_max,
			$rals_current, $rals_max,
			$rmail_current, $rmail_max,
			$rftp_current, $rftp_max,
			$rsql_db_current, $rsql_db_max,
			$rsql_user_current, $rsql_user_max,
			$rtraff_current, $rtraff_max,
			$rdisk_current, $rdisk_max
		) = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
	}

	list($udmn_current,,,$usub_current,,,$uals_current,,,$umail_current,,,
		$uftp_current,,,$usql_db_current,,,$usql_user_current,,,$utraff_current,
		,,$udisk_current
	) = generate_reseller_user_props($reseller_id);

	// Convert into MB values
	$rtraff_max = $rtraff_max * 1024 * 1024;
	$rtraff_current = $rtraff_current * 1024 * 1024;
	$rdisk_max = $rdisk_max * 1024 * 1024;
	$rdisk_current = $rdisk_current * 1024 * 1024;

	if ($rtraff_max != 0) {
		$traff_percent = sprintf("%.2f", 100 * $utraff_current / $rtraff_max);
	} else {
		$traff_percent = 0;
	}

	gen_traff_usage($tpl, $utraff_current, $rtraff_max, 400);

	gen_disk_usage($tpl, $udisk_current, $rdisk_max, 400);

	if ($rtraff_max > 0) {
		if ($utraff_current > $rtraff_max) {
			$tpl->assign('TR_TRAFFIC_WARNING', tr('You are exceeding your traffic limit!'));
		} else {
			$tpl->assign('TRAFF_WARN', '');
		}
	} else {
		$tpl->assign('TRAFF_WARN', '');
	}
	
	// warning HDD Usage
	if ($rdisk_max > 0) {
		if ($udisk_current > $rdisk_max) {
			$tpl->assign('TR_DISK_WARNING', tr('You are exceeding your disk limit!')
				);
		} else {
			$tpl->assign('DISK_WARN', '');
		}
	} else {
		$tpl->assign('DISK_WARN', '');
	}

	$tpl->assign(
		array(
			"ACCOUNT_NAME" => tr("Account name"),
			"GENERAL_INFO" => tr("General information"),
			"DOMAINS" => tr("User accounts"),
			"SUBDOMAINS" => tr("Subdomains"),
			"ALIASES" => tr("Aliases"),
			"MAIL_ACCOUNTS" => tr("Mail account"),
			"TR_FTP_ACCOUNTS" => tr("FTP account"),
			"SQL_DATABASES" => tr("SQL databases"),
			"SQL_USERS" => tr("SQL users"),
			"TRAFFIC" => tr("Traffic"),
			"DISK" => tr("Disk"),
			"TR_EXTRAS" => tr("Extras")
		)
	);

	$tpl->assign(
		array(
			'RESELLER_NAME' => tohtml($reseller_name),
			'TRAFF_PERCENT' => $traff_percent,
			'TRAFF_MSG' => ($rtraff_max)
				? tr('%1$s used / %2$s assigned of <strong>%3$s</strong>', sizeit($utraff_current), sizeit($rtraff_current), sizeit($rtraff_max))
				: tr('%1$s used / %2$s assigned of <strong>unlimited</strong>', sizeit($utraff_current), sizeit($rtraff_current)),
			'DISK_MSG' => ($rdisk_max)
				? tr('%1$s used / %2$s assigned of <strong>%3$s</strong>', sizeit($udisk_current), sizeit($rdisk_current), sizeit($rdisk_max))
				: tr('%1$s used / %2$s assigned of <strong>unlimited</strong>', sizeit($udisk_current), sizeit($rdisk_current)),
			'DMN_MSG' => ($rdmn_max)
				? tr('%1$d used / %2$d assigned of <strong>%3$d</strong>', $udmn_current, $rdmn_current, $rdmn_max)
				: tr('%1$d used / %2$d assigned of <strong>unlimited</strong>', $udmn_current, $rdmn_current),
			'SUB_MSG' => ($rsub_max > 0)
				? tr('%1$d used / %2$d assigned of <strong>%3$d</strong>', $usub_current, $rsub_current, $rsub_max)
				: (($rsub_max === "-1") ? tr('<strong>disabled</strong>') : tr('%1$d used / %2$d assigned of <strong>unlimited</strong>', $usub_current, $rsub_current)),
			'ALS_MSG' => ($rals_max > 0)
				? tr('%1$d used / %2$d assigned of <strong>%3$d</strong>', $uals_current, $rals_current, $rals_max)
				: (($rals_max === "-1") ? tr('<strong>disabled</strong>') : tr('%1$d used / %2$d assigned of <strong>unlimited</strong>', $uals_current, $rals_current)),
			'MAIL_MSG' => ($rmail_max > 0)
				? tr('%1$d used / %2$d assigned of <strong>%3$d</strong>', $umail_current, $rmail_current, $rmail_max)
				: (($rmail_max === "-1") ? tr('<strong>disabled</strong>') : tr('%1$d used / %2$d assigned of <strong>unlimited</strong>', $umail_current, $rmail_current)),
			'FTP_MSG' => ($rftp_max > 0)
				? tr('%1$d used / %2$d assigned of <strong>%3$d</strong>', $uftp_current, $rftp_current, $rftp_max)
				: (($rftp_max === "-1") ? tr('<strong>disabled</strong>') : tr('%1$d used / %2$d assigned of <strong>unlimited</strong>', $uftp_current, $rftp_current)),
			'SQL_DB_MSG' => ($rsql_db_max > 0)
				? tr('%1$d used / %2$d assigned of <strong>%3$d</strong>', $usql_db_current, $rsql_db_current, $rsql_db_max)
				: (($rsql_db_max === "-1") ? tr('<strong>disabled</strong>') : tr('%1$d used / %2$d assigned of <strong>unlimited</strong>', $usql_db_current, $rsql_db_current)),
			'SQL_USER_MSG' => ($rsql_user_max > 0)
				? tr('%1$d used / %2$d assigned of <strong>%3$d</strong>', $usql_user_current, $rsql_user_current, $rsql_user_max)
				: (($rsql_user_max === "-1") ? tr('<strong>disabled</strong>') : tr('%1$d used / %2$d assigned of <strong>unlimited</strong>', $usql_user_current, $rsql_user_current)),
			'EXTRAS' => ''
		)
	);
}

/**
 * @param ispCP_TemplateEngine $tpl
 * @param int $admin_id
 */
function gen_messages_table($tpl, $admin_id) {
	$sql = ispCP_Registry::get('Db');

	$query = "
		SELECT
			`ticket_id`
		FROM
			`tickets`
		WHERE
			(`ticket_from` = ? OR `ticket_to` = ?)
		AND
			`ticket_status` IN ('1', '4')
		AND
			`ticket_reply` = '0'
	;";
	$res = exec_query($sql, $query, array($admin_id, $admin_id));

	$questions = $res->rowCount();

	if ($questions == 0) {
		$tpl->assign(
			array(
				'TR_NO_NEW_MESSAGES' => tr('You have no new support questions!'),
				'MSG_ENTRY' => ''
			)
		);
	} else {
		$tpl->assign(
			array(
				'TR_NEW_MSGS' => tr('You have <strong>%d</strong> new support questions', $questions),
				'NO_MESSAGES' => '',
				'TR_VIEW' => tr('View')
			)
		);

	}
}
?>