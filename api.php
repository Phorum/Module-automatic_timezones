<?php

global $PHORUM;

/**
 * The maximum number of entries to store in the ip_to_offset cache.
 */
define('AUTOMATIC_TIMEZONES_IP_CACHE_SIZE', 100);

/**
 * The maximum time (in minutes) to cache the detected timezones.
 */
define('AUTOMATIC_TIMEZONES_CACHE_TIME', 24 * 60);

/**
 * The maximum time (in minutes) to cache the detected timezones when
 * using IP-based caching (smaller value than the general cache time,
 * just in case we are dealing with roaming / dynamic IP addresses
 * that cross time zones ... fat chance really ;-)
 */
define('AUTOMATIC_TIMEZONES_IP_CACHE_TIME', 2 * 60);

// Initialize the module settings data array.
if (empty($PHORUM["mod_automatic_timezones"])) {
  $PHORUM["mod_automatic_timezones"] = array();
}
if (empty($PHORUM['mod_automatic_timezones']['offset_by_ip'])) {
  $PHORUM['mod_automatic_timezones']['offset_by_ip'] = array();
}
if (!isset($PHORUM['mod_automatic_timezones']['allow_user_override'])) {
  $PHORUM['mod_automatic_timezones']['allow_user_override'] = TRUE;
}

/**
 * Store the TZ offset for the current visitor.
 *
 * @param float $offset
 *     The offset in hours.
 */
function automatic_timezones_set_offset($offset)
{
    global $PHORUM;

    settype($offset, 'float');

    // When a cookie is set, then we can use that one for tracking the
    // timezone offset. There is no need to use another local store
    // in such case.
    if (isset($_COOKIE['auto_tz_offset']))
    {
        // To make sure that the offset matches the $offset parameter,
        // we (re)set the cookie here. This should always match the
        // existing cookie.
        setcookie(
            'auto_tz_offset', $offset,
            time() + AUTOMATIC_TIMEZONES_CACHE_TIME * 60
        );
    }
    // For authenticated users, we store the offset in the
    // settings data for the user.
    elseif ($PHORUM['user']['user_id'])
    {
        phorum_api_user_save_settings(array(
            'automatic_timezone' => array($offset, time())
        ));
    }
    // For anonymous users, we store the offset based on the IP address
    // in the Phorum settings table.
    else
    {
        // Do garbage collection on the IP cache, which prevents the
        // stored data from overflowing the available database storage size.
        automatic_timezones_collect_garbage();

        $map =& $PHORUM['mod_automatic_timezones']['offset_by_ip'];
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!isset($map[$ip]) || $map[$ip] !== $offset)
        {
            $map[$ip] = array($offset, time());
            phorum_db_update_settings(array(
                'mod_automatic_timezones' =>
                $PHORUM['mod_automatic_timezones']
            ));
        }
    }
}

/**
 * Retrieve the TZ offset for the current visitor.
 *
 * @return array()
 *     An array containing two values:
 *     - the timezone offset
 *     - whether or not DST is active
 *     These values can be NULL when the offset is unknown.
 */
function automatic_timezones_get_offset()
{
    global $PHORUM;

    // If the admin has allowed users to set their timezone manually,
    // then check if a specific timezone is set for the active user.
    if (!empty($PHORUM['mod_automatic_timezones']['allow_user_override']))
    {
        if ($PHORUM['user']['user_id'])
        {
            if ($PHORUM['user']['tz_offset'] != -99) {
                return array(
                    $PHORUM['user']['tz_offset'],
                    $PHORUM['user']['is_dst']
                );
            }
        }
    }

    // Check for an offset in the module cookie.
    if (isset($_COOKIE['auto_tz_offset'])) {
        return array($_COOKIE['auto_tz_offset'], FALSE);
    }

    // Check for an offset in the user settings.
    if ($PHORUM['user']['user_id'])
    {
        list ($offset, $time) =
            phorum_api_user_get_setting('automatic_timezone');
        if (($time + AUTOMATIC_TIMEZONES_CACHE_TIME * 60) > time()) {
            return array($map[$ip], FALSE);
        }
    }

    // Check for an offset in the offset_by_ip map.
    $map =& $PHORUM['mod_automatic_timezones']['offset_by_ip'];
    $ip = $_SERVER['REMOTE_ADDR'];
    if (isset($map[$ip])) {
        list ($offset, $time) = $map[$ip];
        if (($time + AUTOMATIC_TIMEZONES_IP_CACHE_TIME * 60) > time()) {
            return array($map[$ip], FALSE);
        }
    }

    return array(NULL, NULL);
}

/**
 * Garbage collection: cleanup entries from the offset_by_ip map.
 *
 * This keeps the map size limited and will prevent overflowing the
 * maximum storage size in the database for the settings field.
 */
function automatic_timezones_collect_garbage()
{
    $map =& $PHORUM['mod_automatic_timezones']['offset_by_ip'];
    if (count($map) > AUTOMATIC_TIMEZONES_IP_CACHE_SIZE)
    {
        $delete = floor(count($map) - AUTOMATIC_TIMEZONES_IP_CACHE_SIZE * 0.8);
        foreach ($map as $ip => $offset) {
            unset($map[$ip]);
            if (--$delete == 0) break;
        }

        phorum_db_update_settings(array(
            'mod_automatic_timezones' =>
            $PHORUM['mod_automatic_timezones']
        ));
    }
}

?>
