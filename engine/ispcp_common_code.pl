#!/usr/bin/perl

# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (C) 2001-2006 by moleSoftware GmbH - http://www.molesoftware.com
# Copyright (C) 2006-2011 by ispCP | http://ispcp.net
#
# Version: $Id$
#
# The contents of this file are subject to the Mozilla Public License
# Version 1.1 (the "License"); you may not use this file except in
# compliance with the License. You may obtain a copy of the License at
# http://www.mozilla.org/MPL/
#
# Software distributed under the License is distributed on an "AS IS"
# basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
# License for the specific language governing rights and limitations
# under the License.
#
# The Original Code is "VHCS - Virtual Hosting Control System".
#
# The Initial Developer of the Original Code is moleSoftware GmbH.
# Portions created by Initial Developer are Copyright (C) 2001-2006
# by moleSoftware GmbH. All Rights Reserved.
# Portions created by the ispCP Team are Copyright (C) 2006-2011 by
# isp Control Panel. All Rights Reserved.

BEGIN {

	my %needed 	= (
		'strict' => '',
		'warnings' => '',
		'IO::Socket'=> '',
		'DBI'=> '',
		DBD::mysql => '',
		MIME::Entity => '',
		MIME::Parser => '',
		Crypt::CBC => '',
		Crypt::Blowfish => '',
		Crypt::PasswdMD5 => '',
		MIME::Base64 => '',
		Term::ReadPassword => '',
		File::Basename => '',
		File::Path => '',
		HTML::Entities=> '',
		File::Temp => 'qw(tempdir)',
		File::Copy::Recursive => 'qw(rcopy)',
		Net::LibIDN => 'qw(idn_to_ascii idn_to_unicode)'
	);

	my ($mod, $mod_err, $mod_missing) = ('', '_off_', '');

	for $mod (keys %needed) {

		if (eval "require $mod") {

			eval "use $mod $needed{$mod}";

		} else {

			print STDERR "\n[FATAL] Module [$mod] WAS NOT FOUND !\n" ;

			$mod_err = '_on_';

			if ($mod_missing eq '') {
				$mod_missing .= $mod;
			} else {
				$mod_missing .= ", $mod";
			}
		}
	}

	if ($mod_err eq '_on_') {
		print STDERR "\nModules [$mod_missing] WAS NOT FOUND in your system...\n";

		exit 1;

	} else {
		$| = 1;
	}
}

use strict;
use warnings;

# Hide the "used only once: possible typo" warnings
no warnings qw(once);

$main::engine_debug = undef;

require 'ispcp_common_methods.pl';

################################################################################
# Load ispCP configuration from the ispcp.conf file

if(-e '/usr/local/etc/ispcp/ispcp.conf'){
	$main::cfg_file = '/usr/local/etc/ispcp/ispcp.conf';
	$main::ispcp_etc_dir = '/usr/local/etc/ispcp';
} else {
	$main::cfg_file = '/etc/ispcp/ispcp.conf';
	$main::ispcp_etc_dir = '/etc/ispcp';
}

require 'ispcp-load-db-keys.pl';

my $rs = get_conf($main::cfg_file);
die("FATAL: Can't load the ispcp.conf file") if($rs != 0);

################################################################################
# Enable debug mode if needed
if ($main::cfg{'DEBUG'} != 0) {
	$main::engine_debug = '_on_';
}

################################################################################
# Generating ispCP Db key and initialization vector if needed
#
if ($main::db_pass_key eq '{KEY}' || $main::db_pass_iv eq '{IV}') {

	print STDOUT "\tGenerating database keys, it may take some time, please ".
		"wait...\n";

	print STDOUT "\tIf it takes to long, please check: ".
	 "http://isp-control.net/documentation/frequently_asked_questions/what".
	 "_does_generating_database_keys_it_may_take_some_time_please_wait..._on_".
	 "setup_mean\n";

	map {s/'/\\'/g, chop}
		my $db_pass_key = generateRandomChars(32, ''),
		my $db_pass_iv = generateRandomChars(8, '');

	$main::db_pass_key = $db_pass_key;
	$main::db_pass_iv = $db_pass_iv;

	$rs = write_ispcp_key_cfg();

	die('FATAL: Error during database keys generation!') if ($rs != 0);
}

# Exit script execution if Database Parameters are not set
die("FATAL: Cannot load database parameters")  if (setup_main_vars() != 0);

################################################################################
# Lock file system variables
#
$main::lock_file = $main::cfg{'MR_LOCK_FILE'};
$main::fh_lock_file = undef;

$main::log_dir = $main::cfg{'LOG_DIR'};
$main::root_dir = $main::cfg{'ROOT_DIR'};

$main::ispcp = "$main::log_dir/ispcp-rqst-mngr.el";

################################################################################
# ispcp_rqst_mngr variables
#
$main::ispcp_rqst_mngr = "$main::root_dir/engine/ispcp-rqst-mngr";
$main::ispcp_rqst_mngr_el = "$main::log_dir/ispcp-rqst-mngr.el";
$main::ispcp_rqst_mngr_stdout = "$main::log_dir/ispcp-rqst-mngr.stdout";
$main::ispcp_rqst_mngr_stderr = "$main::log_dir/ispcp-rqst-mngr.stderr";

################################################################################
# ispcp_dmn_mngr variables
#
$main::ispcp_dmn_mngr = "$main::root_dir/engine/ispcp-dmn-mngr";
$main::ispcp_dmn_mngr_el = "$main::log_dir/ispcp-dmn-mngr.el";
$main::ispcp_dmn_mngr_stdout = "$main::log_dir/ispcp-dmn-mngr.stdout";
$main::ispcp_dmn_mngr_stderr = "$main::log_dir/ispcp-dmn-mngr.stderr";

################################################################################
# ispcp_sub_mngr variables
#
$main::ispcp_sub_mngr = "$main::root_dir/engine/ispcp-sub-mngr";
$main::ispcp_sub_mngr_el = "$main::log_dir/ispcp-sub-mngr.el";
$main::ispcp_sub_mngr_stdout = "$main::log_dir/ispcp-sub-mngr.stdout";
$main::ispcp_sub_mngr_stderr = "$main::log_dir/ispcp-sub-mngr.stderr";

################################################################################
# ispcp_alssub_mngr variables
#
$main::ispcp_alssub_mngr = "$main::root_dir/engine/ispcp-alssub-mngr";
$main::ispcp_alssub_mngr_el = "$main::log_dir/ispcp-alssub-mngr.el";
$main::ispcp_alssub_mngr_stdout = "$main::log_dir/ispcp-alssub-mngr.stdout";
$main::ispcp_alssub_mngr_stderr = "$main::log_dir/ispcp-alssub-mngr.stderr";

################################################################################
# ispcp_als_mngr variables
#
$main::ispcp_als_mngr = "$main::root_dir/engine/ispcp-als-mngr";
$main::ispcp_als_mngr_el = "$main::log_dir/ispcp-als-mngr.el";
$main::ispcp_als_mngr_stdout = "$main::log_dir/ispcp-als-mngr.stdout";
$main::ispcp_als_mngr_stderr = "$main::log_dir/ispcp-als-mngr.stderr";

################################################################################
# ispcp_mbox_mngr variables
#
$main::ispcp_mbox_mngr = "$main::root_dir/engine/ispcp-mbox-mngr";
$main::ispcp_mbox_mngr_el = "$main::log_dir/ispcp-mbox-mngr.el";
$main::ispcp_mbox_mngr_stdout = "$main::log_dir/ispcp-mbox-mngr.stdout";
$main::ispcp_mbox_mngr_stderr = "$main::log_dir/ispcp-mbox-mngr.stderr";

################################################################################
# ispcp_serv_mngr variables
#
$main::ispcp_serv_mngr = "$main::root_dir/engine/ispcp-serv-mngr";
$main::ispcp_serv_mngr_el = "$main::log_dir/ispcp-serv-mngr.el";
$main::ispcp_serv_mngr_stdout = "$main::log_dir/ispcp-serv-mngr.stdout";
$main::ispcp_serv_mngr_stderr = "$main::log_dir/ispcp-serv-mngr.stderr";

################################################################################
# ispcp_net_interfaces_mngr variables
#
$main::ispcp_net_interfaces_mngr = "$main::root_dir/engine/tools/ispcp-net-interfaces-mngr";
$main::ispcp_net_interfaces_mngr_el = "$main::log_dir/ispcp-net-interfaces-mngr.el";
$main::ispcp_net_interfaces_mngr_stdout = "$main::log_dir/ispcp-net-interfaces-mngr.log";

################################################################################
# ispcp_htaccess_mngr variables
#
$main::ispcp_htaccess_mngr = "$main::root_dir/engine/ispcp-htaccess-mngr";
$main::ispcp_htaccess_mngr_el = "$main::log_dir/ispcp-htaccess-mngr.el";
$main::ispcp_htaccess_mngr_stdout = "$main::log_dir/ispcp-htaccess-mngr.stdout";
$main::ispcp_htaccess_mngr_stderr = "$main::log_dir/ispcp-htaccess-mngr.stderr";

################################################################################
# ispcp_htusers_mngr variables
#
$main::ispcp_htusers_mngr = "$main::root_dir/engine/ispcp-htusers-mngr";
$main::ispcp_htusers_mngr_el = "$main::log_dir/ispcp-htusers-mngr.el";
$main::ispcp_htusers_mngr_stdout = "$main::log_dir/ispcp-htusers-mngr.stdout";
$main::ispcp_htusers_mngr_stderr = "$main::log_dir/ispcp-htusers-mngr.stderr";

################################################################################
# ispcp_htgroups_mngr variables
#
$main::ispcp_htgroups_mngr = "$main::root_dir/engine/ispcp-htgroups-mngr";
$main::ispcp_htgroups_mngr_el = "$main::log_dir/ispcp-htgroups-mngr.el";
$main::ispcp_htgroups_mngr_stdout = "$main::log_dir/ispcp-htgroups-mngr.stdout";
$main::ispcp_htgroups_mngr_stderr = "$main::log_dir/ispcp-htgroups-mngr.stderr";


################################################################################
# ispcp_vrl_traff variables
#
$main::ispcp_vrl_traff = "$main::root_dir/engine/messenger/ispcp-vrl-traff";
$main::ispcp_vrl_traff_el = "$main::log_dir/ispcp-vrl-traff.el";
$main::ispcp_vrl_traff_stdout = "$main::log_dir/ispcp-vrl-traff.stdout";
$main::ispcp_vrl_traff_stderr = "$main::log_dir/ispcp-vrl-traff.stderr";

################################################################################
# ispcp_svr_traff variables
#
$main::ispcp_srv_traff_el = "$main::log_dir/ispcp-srv-traff.el";

################################################################################
# ispcp_httpd_logs variables
#
$main::ispcp_httpd_logs_mngr_el = "$main::log_dir/ispcp-httpd-logs-mngr.el";
$main::ispcp_httpd_logs_mngr_stdout = "$main::log_dir/ispcp-httpd-logs-mngr.stdout";
$main::ispcp_httpd_logs_mngr_stderr = "$main::log_dir/ispcp-httpd-logs-mngr.stderr";

################################################################################
# ispcp_bk variables
#
$main::ispcp_bk_task_el = "$main::log_dir/ispcp-bk-task.el";

################################################################################
# ispcp_dsk_quota variables
#
$main::ispcp_dsk_quota_el = "$main::log_dir/ispcp-dsk-quota.el";

1;
