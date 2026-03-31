# Shared Files #

Shared Files is a local Moodle plugin that provides a centralized file repository for managing and sharing files across the entire site.

The plugin allows administrators and authorized users to:

- Create and manage folders
- Upload and delete files
- Securely serve files from moodledata
- Browse repository contents

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/local/shared_files

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## Support and Feedback
- This plugin is developed and maintained by <a href="https://justaddwater.in/">JUSTADDWATER</a>. 
- You can also use <a href="https://justaddwater.in/contact/">contact page</a> on our website for reporting issues, support or any other feedback.  

## License
GPL v3
