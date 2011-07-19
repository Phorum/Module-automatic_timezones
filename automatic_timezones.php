<?php

if (!defined("PHORUM")) return;

require_once dirname(__FILE__) . '/api.php';

function phorum_mod_automatic_timezones_javascript_register($data)
{
    $data[] = array(
        "module" => "automatic_timezones",
        "source" => "file(mods/automatic_timezones/automatic_timezones.js)"
    );

    return $data;
}

// In the common hook, we will add a call to the javascript code
// when the timezone offset needs to be detected for the visitor.
function phorum_mod_automatic_timezones_common()
{
    global $PHORUM;

    // If users are not allowed to override the automatic time zones,
    // then we will not show the timezone selection.
    if (empty($PHORUM['mod_automatic_timezones']['allow_user_override'])) {
        $PHORUM["user_time_zone"] = 0;
    }

    // Skip adding the time zone handling code to pages that
    // output raw data instead of an HTML page.
    if (phorum_page == 'css'        ||
        phorum_page == 'javascript' ||
        phorum_page == 'feed'       ||
        phorum_page == 'file') return;

    // No time zone handling when we are not running in the browser.
    if (!isset($_SERVER['REMOTE_ADDR'])) return;

    // No time zone handling when we are processing a form POST.
    if (!empty($_POST)) return;

    // Check if we need to autodetect the visitor's timezone.
    // If yes, then we add some triggering code for this to the
    // page's <head> section.
    list($tz_offset, $is_dst) = automatic_timezones_get_offset();
    if ($tz_offset === NULL)
    {
        // Generate a URL for the addon functionality in this module.
        // The placeholder %redir% and %offset% will be used by
        // the javascript code that is called from the code below.
        $url = addslashes(phorum_get_url(
            PHORUM_ADDON_URL,
            'module=automatic_timezones',
            'redir=%redir%',
            'offset=%offset%'
        ));

        $cache_time = AUTOMATIC_TIMEZONES_CACHE_TIME;
        $PHORUM['DATA']['HEAD_TAGS'] .=
            "<script type=\"text/javascript\">\n" .
            "  phorum_mod_automatic_timezones('$url', $cache_time);\n" .
            "</script>\n";
    }
    else
    {
        // Setup the correct timezone offset for the user.
        $PHORUM["user"]["tz_offset"] = $tz_offset;
        $PHORUM["user"]["is_dst"]    = $is_dst;
    }
}

// This method is triggered by the javascript code which detects the
// timezone offset for the visitor. In the $PHORUM['args'], two
// parameters will be set:
//
// - offset : the timezone offset in hours
// - redir  : the URL to redirect the user to after processing the offset
//
function phorum_mod_automatic_timezones_addon()
{
    global $PHORUM;
    if (isset($PHORUM['args']['offset'])) {
        automatic_timezones_set_offset($PHORUM['args']['offset']);
    }

    $redir = isset($PHORUM['args']['redir'])
           ? $PHORUM['args']['redir'] : phorum_get_url(PHORUM_INDEX_URL);
    phorum_redirect_by_url($redir);
}

?>
