<?php
/* $Id: LibRDF.php 171 2006-06-15 23:24:18Z das-svn $ */
/**
 * Constants and default includes for the LibRDF package.
 *
 * This file can be included by code using the LibRDF package to include
 * all necessary classes.
 *
 * PHP version 5
 *
 * Copyright (C) 2006, David Shea <david@gophernet.org>
 *
 * LICENSE: This package is Free Software and a derivative work of Redland
 * http://librdf.org/.  This package is not endorsed by Dave Beckett or the 
 * University of Bristol. It is licensed under the following three licenses as 
 * alternatives:
 *   1. GNU Lesser General Public License (LGPL) V2.1 or any newer version
 *   2. GNU General Public License (GPL) V2 or any newer version
 *   3. Apache License, V2.0 or any newer version
 *
 * You may not use this file except in compliance with at least one of the
 * above three licenses.
 *
 * See LICENSE.txt at the top of this package for the complete terms and futher
 * detail along with the license tests for the licenses in COPYING.LIB, COPYING
 * and LICENSE-2.0.txt repectively.
 *
 * @package     LibRDF
 * @author      David Shea <david@gophernet.org>
 * @copyright   2006 David Shea
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 * @link        http://www.gophernet.org/projects/redland-php/
 */

/**
 */
require_once(dirname(__FILE__) . '/Model.php');
require_once(dirname(__FILE__) . '/Node.php');
require_once(dirname(__FILE__) . '/Parser.php');
require_once(dirname(__FILE__) . '/Query.php');
require_once(dirname(__FILE__) . '/Serializer.php');
require_once(dirname(__FILE__) . '/Statement.php');
require_once(dirname(__FILE__) . '/Storage.php');
require_once(dirname(__FILE__) . '/URI.php');

/**
 * The URI of the RDF/XML namespace.
 */
define("RDF_BASE_URI", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");

?>
