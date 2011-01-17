<?php
/* $Id: Query.php 171 2006-06-15 23:24:18Z das-svn $ */
/**
 * LibRDF_Query, a representation of a query against a LibRDF_Model.
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
require_once(dirname(__FILE__) . '/Error.php');
require_once(dirname(__FILE__) . '/Model.php');
require_once(dirname(__FILE__) . '/QueryResults.php');

/**
 * A wrapper around the librdf_query datatype.
 * 
 * A query is created independent of any context and is then executed, using
 * {@link execute}, against a particular model.  A query can be executed
 * multiple times and against multiple models.  Results are represented as
 * a {@link LibRDF_QueryResults} object.
 *
 * @package     LibRDF
 * @author      David Shea <david@gophernet.org>
 * @copyright   2006 David Shea
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 * @link        http://www.gophernet.org/projects/redland-php/
 */
class LibRDF_Query
{
    /**
     * The underlying librdf_query resource.
     *
     * @var     resource
     * @access  private
     */
    private $query;

    /**
     * Create a new query.
     *
     * Query language is any language supported by rasqal, including "rdql",
     * "sparql" and "triples".
     *
     * The syntax of the query is not checked until it is executed.
     *
     * @param   string      $query_string   The contents of the query
     * @param   string      $base_uri       The base URI to use
     * @param   string      $query_language The language of the query (default rdql)
     * @param   string      $query_uri      The URI of the query language or NULL
     * @return  void
     * @throws  LibRDF_Error                If unable to create a new query
     * @access  public
     */
    public function __construct($query_string, $base_uri=NULL,
        $query_language="rdql", $query_uri=NULL)
    {
        if ($base_uri) {
            $base_uri = new LibRDF_URI($base_uri);
        }

        if ($query_uri) {
            $query_uri = new LibRDF_URI($query_uri);
        }

        $this->query = librdf_new_query(librdf_php_get_world(),
            $query_language, ($query_uri ? $query_uri->getURI() : $query_uri),
            $query_string, ($base_uri ? $base_uri->getURI() : $base_uri));

        if (!$this->query) {
            throw new LibRDF_Error("Unable to create a new query");
        }
    }

    /**
     * Free the query resources.
     *
     * @return  void
     * @access  public
     */
    public function __destruct()
    {
        if ($this->query) {
            librdf_free_query($this->query);
        }
    }

    /**
     * Clone the query.
     *
     * Cloning may not be supported for all query types.
     *
     * @return  void
     * @throws  LibRDF_Error    If unable to clone the query
     * @access  public
     */
    public function __clone()
    {
        $this->query = librdf_new_query_from_query($this->query);

        if (!$this->query) {
            throw new LibRDF_Error("Unable to create new query from query");
        }
    }

    /**
     * Run the query against a model.
     *
     * @param   LibRDF_Model    $model  The model to query
     * @return  LibRDF_QueryResults     The result of the query
     * @throws  LibRDF_Error            If unable to execute the query
     * @access  public
     */
    public function execute(LibRDF_Model $model)
    {
        $results = librdf_query_execute($this->query, $model->getModel());

        if (!$results) {
            throw new LibRDF_Error("Unable to execute query");
        }
        return LibRDF_QueryResults::makeQueryResults($results);
    }
}

?>
