<?php

if (!defined('PHORUM_ADMIN')) return;

global $PHORUM;

if (count($_POST)) 
{
    // Create the settings array for this module.
    $PHORUM['mod_automatic_timezones'] = array(
        'allow_user_override' => empty($_POST['allow_user_override']) ? 0 : 1
    );

    phorum_db_update_settings(array(
        'mod_automatic_timezones' => $PHORUM['mod_automatic_timezones']
    ));
    phorum_admin_okmsg('The settings were updated successfully');
}

include_once './include/admin/PhorumInputForm.php';
$frm = new PhorumInputForm ('', 'post', 'Save');
$frm->hidden('module', 'modsettings');
$frm->hidden('mod', 'automatic_timezones'); 

$frm->addbreak('Edit settings for the Automatic Time Zones module');

$row = $frm->addrow(
  'Allow users to override the automatic time zones from their Control Center',
  $frm->checkbox(
      'allow_user_override', '1', '',
      $PHORUM['mod_automatic_timezones']['allow_user_override']
  )
);

$frm->show();
?>
