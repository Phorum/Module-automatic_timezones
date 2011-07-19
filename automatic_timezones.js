/**
 * @param string redirect_url
 *   The URL that we can use for redirecting the user to the addon for
 *   the automatic timezones module.
 * @param integer cache_time
 *   The time for caching the automatic timezone in minutes.
 */
function phorum_mod_automatic_timezones(redirect_url, cache_time)
{
    // Determine the timezone offset for the client.
    var today  = new Date();
    var timezoneOffset = today.getTimezoneOffset() / 60;

    // flip the time zone offset as javascript uses values opposite to php
    timezoneOffset *= -1;

    // Reset the cookie every day in case the user has changed zones.
    var expire = new Date();
    expire.setTime(today.getTime() + cache_time * 60 * 1000);

    // Place the timezone offset in a cookie.
    document.cookie =
        'auto_tz_offset=' + timezoneOffset +
        ';expires=' + expire.toGMTString();

    // In case the browser does not have cookies enabled, we send
    // the timezone in a request to the server. The URL to redirect
    // to, is provided as the parameter for this function. This URL
    // must contain the %redir% and %offset% placeholders, which we will
    // fill with appropriate data here.
    var redir = escape(document.location.href);
    redirect_url = redirect_url.replace('%offset%', timezoneOffset);
    redirect_url = redirect_url.replace('%redir%',  redir);
    document.location.href = redirect_url;
}

