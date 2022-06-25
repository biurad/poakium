CHANGELOG
=========

1.4
===

* [BR BREAK] Renamed the `app` directory to `src`
* Updated coding standard config and added a few more rules
* Updated php file header comment block
* Updated PHPUnit files for better testing of application
* Updated the configuration array in the resource bootstrap file to be most all commented out
* Added a default `self-update` command to rade cli in order to update the project to the latest version

1.2
===

* [BR BREAK] Updated symfony/cache, symfony/event-dispatcher, and symfony/dotenv as suggest instead of requiring.
* Updated phpunit configuration to latest versions of PHPUnit.
* Updated cache path of compiled container to `var/app` instead of `var/cache`
* Added `tracy/tracy` debug bar panels support
* Added parameter support for `%project.var_dir%` instead of using `%project_dir%/var`
* Removed extra config from composer.json file

1.1
===

* [BC BREAK] Updated application's dependency injection library
* [BC BREAK] Updated application's router library
* [BC BREAK] Improved application's performance
* [BC BREAK] Renamed console dispatch file to  `rade`
* Added `tracy/tracy` error and debugger library as default exceptions handler
* Added minor changes in application's structure
* Improved application's performance and security
* Updated php fixer coding standard rules
* Set PHP minimum version to 8.0
