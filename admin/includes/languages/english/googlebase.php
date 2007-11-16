<?php
/**
 * @package admin
 * @version $Id: googlebase.php 0 Sep 5, 2007 4:14:26 PM pablif@gmail.com $
 */

define('GB_HEADING_TITLE', 'Google Base');
define('GB_TABLE_HEADING_ACTION', 'Action');
define('GB_TABLE_HEADING_DESCRIPTION', 'Description');
define('GB_AUTH_BUTTON', 'authenticate');
define('GB_AUTH_DESCRIPTION', 'Authenticate in Google Base to be able to perform operations against your account');
define('GB_REVOKE_BUTTON', 'revoke authentication');
define('GB_REVOKE_DESCRIPTION', "Revoke the authentication token given by Google Base and erase it from the database, you'll have to re-authenticate to perform any operations that involve Google Base.");
define('GB_UPLOAD_BUTTON', 'upload');
define('GB_UPLOAD_DESCRIPTION', 'Upload your products to Google Base');
define('GB_OPTIONS_TITLE', 'Options');
define('GB_OPTIONS_ENABLED', 'Enable Google Base');
define('GB_OPTIONS_MAX_UPLOADS', 'Limit for the number of products to upload at once (0=no limit). Next time you upload your products it will continue from the  last product uploaded.');
define('GB_OPTIONS_AUTHOR_NAME', 'author name for the submitted products');
define('GB_OPTIONS_AUTHOR_EMAIL', 'author email for the submitted products');
define('GB_OPTIONS_DRAFT', 'submit products as draft');
define('GB_OPTIONS_UPC', 'submit upc and/or isbn (you must have a products_upc and/or products_isbn column in the products database table)');
define('GB_OPTIONS_SUBMIT', 'save');



?>