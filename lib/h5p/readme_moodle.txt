H5P PHP library
---------------

Downloaded last release from: https://github.com/h5p/h5p-php-library/releases

Import procedure:

- Copy all the files from the folder repository in this directory.

Removed:
 * composer.json
 * .gitignore

Added:
 * readme_moodle.txt

Downloaded version: 1.23.1 release

=== 3.8 ===
* Commented extension_loaded('mbstring').
  In Moodle the extension mbstring is optional, so in order to not force install the extension on php
  we have to commented the setErrorMessage regarding extension_loaded('mbstring')in the next functions in h5p.classes.php file:
    * isValidPackage
    * checkSetupErrorMessage
    * validateText
    * validateContentFiles
