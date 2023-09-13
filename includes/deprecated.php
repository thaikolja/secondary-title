<?php
/**
 * (C) Copyright 2021 by Kolja Nolte
 * kolja.nolte@gmail.com
 * https://www.kolja-nolte.com
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * @see        https://wordpress.org/plugins/secondary-title/
 * @author     Kolja Nolte <kolja.nolte@gmail.com>
 *
 * This file contains deprecated functions.
 *
 * @package    Secondary Title
 * @subpackage Administration
 */

/** Stop script when the file is called directly */
if ( ! function_exists( "add_action" ) ) {
	die( "403 - You are not authorized to view this page." );
}
