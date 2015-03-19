### Dynamic DNS updater ###

Bash script to be run when IP-changes or as a cronjob. Bash script starts a new webserver, posts to PHP script which then cURL's cPanel API to update the A entry with new IP address, then kills the webserver.
