<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018 John BOTELLA
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    admin/setup.php
 * \ingroup discountrules
 * \brief   discountrules setup page.
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/discountrules.lib.php';
//require_once "../class/myclass.class.php";
// Translations
$langs->load("discountrules@discountrules");

// Access control
if (! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';


/*
 * View
 */

$page_name = "discountrulesSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, "super_atm.gif@discountrules");

// Configuration header
$head = discountrulesAdminPrepareHead();
dol_fiche_head(
	$head,
	'settings',
	$langs->trans("ModulediscountrulesName"),
	0,
	"discountrules@discountrules"
);


$var=0;

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';


print load_fiche_titre($langs->trans("SearchDiscountAllreadyApplied"), '', '');
print '<table class="noborder" width="100%">';


_printOnOff('DISCOUNTRULES_SEARCH_IN_ORDERS');
_printOnOff('DISCOUNTRULES_SEARCH_IN_PROPALS');
_printOnOff('DISCOUNTRULES_SEARCH_IN_INVOICES');
_printOnOff('DISCOUNTRULES_SEARCH_QTY_EQUIV');

$metas = array( 'type' => 'number', 'step' => '1', 'min' => 0 );
_printInputFormPart('DISCOUNTRULES_SEARCH_DAYS', '', '', $metas);

print '</table>';

_updateBtn();

print '</form>';

dol_fiche_end();

// End of page
llxFooter();
$db->close();

/**
 * Print an update button
 *
 * @return void
 */
function _updateBtn()
{
    global $langs;
    print '<div style="text-align: right;" >';
    print '<input type="submit" class="butAction" value="'.$langs->trans("Save").'">';
    print '</div>';
}

/**
 * Print a On/Off button
 *
 * @param string $confkey the conf key
 * @param bool   $title   Title of conf
 * @param string $desc    Description
 *
 * @return void
 */
function _printOnOff($confkey, $title = false, $desc = '')
{
    global $var, $bc, $langs;
    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print '<td>'.($title?$title:$langs->trans($confkey));
    if (!empty($desc)) {
        print '<br><small>'.$langs->trans($desc).'</small>';
    }
    print '</td>';
    print '<td class="center" width="20">&nbsp;</td>';
    print '<td class="right" width="300">';
    print ajax_constantonoff($confkey);
    print '</td></tr>';
}


/**
 * Print a form part
 *
 * @param string $confkey the conf key
 * @param bool   $title   Title of conf
 * @param string $desc    Description of
 * @param array  $metas   html meta
 * @param string $type    type of input textarea or input
 * @param bool   $help    help description
 *
 * @return void
 */
function _printInputFormPart($confkey, $title = false, $desc = '', $metas = array(), $type = 'input', $help = false)
{
    global $var, $bc, $langs, $conf, $db, $inputCount;
    $var=!$var;
    $inputCount = empty($inputCount)?1:($inputCount+1);
    $form=new Form($db);

    $defaultMetas = array(
        'name' => 'value'.$inputCount
    );

    if ($type!='textarea') {
        $defaultMetas['type']   = 'text';
        $defaultMetas['value']  = $conf->global->{$confkey};
    }


    $metas = array_merge($defaultMetas, $metas);
    $metascompil = '';
    foreach ($metas as $key => $values) {
        $metascompil .= ' '.$key.'="'.$values.'" ';
    }

    print '<tr '.$bc[$var].'>';
    print '<td>';

    if (!empty($help)) {
        print $form->textwithtooltip(($title?$title:$langs->trans($confkey)), $langs->trans($help), 2, 1, img_help(1, ''));
    } else {
        print $title?$title:$langs->trans($confkey);
    }

    if (!empty($desc)) {
        print '<br><small>'.$langs->trans($desc).'</small>';
    }

    print '</td>';
    print '<td class="center" width="20">&nbsp;</td>';
    print '<td class="right" width="300">';
    print '<input type="hidden" name="param'.$inputCount.'" value="'.$confkey.'">';

    print '<input type="hidden" name="action" value="setModuleOptions">';
    if ($type=='textarea') {
        print '<textarea '.$metascompil.'  >'.dol_htmlentities($conf->global->{$confkey}).'</textarea>';
    } else {
        print '<input '.$metascompil.'  />';
    }
    print '</td></tr>';
}
