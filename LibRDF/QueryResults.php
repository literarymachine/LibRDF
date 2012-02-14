<?php
/* $Id: QueryResults.php 171 2006-06-15 23:24:18Z das-svn $ */
/**
 * LibRDF_QueryResults, the answer to a LibRDF_Query.
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
require_once(dirname(__FILE__) . '/URI.php');
require_once(dirname(__FILE__) . '/StreamIterator.php');
require_once(dirname(__FILE__) . '/Node.php');

/**
 * A wrapper around the librdf_query_results datatype.
 *
 * This is the generic query results wrapper.  There are three possible types
 * of query results--boolean (those returned by SPARQL "ASK"), bindings
 * (returned by "SELECT" in SPARQL and RDQL) and graph (such as those returned
 * by SPARQL "CONSTRUCT" and "DESCRIBE")--each with a specialized class, but
 * each is an iterable object.  This creates an odd case for booleans, which
 * are an iterator containing one element.  As a special concession for this
 * single-result case, {@link LibRDF_BooleanQueryResults} objects also have a 
 * method to simply retrieve the boolean value without iteration.
 *
 * @package     LibRDF
 * @author      David Shea <david@gophernet.org>
 * @copyright   2006 David Shea
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 * @link        http://www.gophernet.org/projects/redland-php/
 */
abstract class LibRDF_QueryResults implements Iterator
{
    /**
     * The wrapped librdf_query_results datatype.
     *
     * This resource must be set by the concrete query results classes.
     *
     * @var     resource
     * @access  private
     */
    protected $query_results;

    /**
     * Free the query result resources.
     *
     * @return  void
     * @access  public
     */
    public function __destruct()
    {
        if ($this->query_results) {
            librdf_free_query_results($this->query_results);
        }
    }

    /**
     * Clone the query.
     *
     * Clonining a query is not supported, so this function disables the use
     * of the clone keyword by setting the underlying resource to NULL and
     * throwing an exception.
     *
     * @return  void
     * @throws  LibRDF_Error    Always
     * @access  public
     */
    public function __clone()
    {
        // destroying the results instead of resetting them
        // since there's no way to duplicate the resource or prevent
        // a parent object from freeing the results before its clone
        $this->query_results = NULL;
        throw new LibRDF_Error("Cloning query results is not supported");
    }

    /**
     * Return the query results as a string.
     *
     * The language of the results depends on the query type.
     *
     * @return  string  The query results as a string
     * @access  public
     */
    public function __toString()
    {
        return $this->to_string();
    }

    /**
     * Serialize the results to a string.
     *
     * @param   string      $uri        The uri of the target syntax or NULL
     * @param   string      $base_uri   The base URI for the output or NULL
     * @return  string                  The results as a string
     * @throws  LibRDF_Error            If unable to create a string from the results
     * @access  public
     */
    public function to_string($uri=NULL, $base_uri=NULL)
    {
        if ($uri) {
            $uri = new LibRDF_URI($uri);
        }

        if ($base_uri) {
            $base_uri = new LibRDF_URI($base_uri);
        }

        $ret = librdf_query_results_to_string($this->query_results,
            ($uri ? $uri->getURI() : NULL),
            ($base_uri ? $base_uri->getURI() : NULL));

        if ($ret) {
            return $ret;
        } else {
            throw new LibRDF_Error("Unable to convert the query results to a string");
        }
    }

    /**
     * Make a specialized query results object.
     *
     * This function is intended for use by {@link LibRDF_Query}, allowing
     * the creating of a specific query results object from a
     * librdf_query_results resource.
     *
     * @param   resource    $query_results  The librdf_query_results resource to wrap
     * @return  LibRDF_QueryResults         The wrapped query results
     * @throws  LibRDF_Error                If unable to wrap the object
     * @access  public
     * @static
     */
    public static function makeQueryResults($query_results)
    {
        if (!is_resource($query_results)) {
            throw new LibRDF_Error("Argument must be a librdf_query_results resource");
        }

        if (librdf_query_results_is_bindings($query_results)) {
            return new LibRDF_BindingsQueryResults($query_results);
        } elseif (librdf_query_results_is_boolean($query_results)) {
            return new LibRDF_BooleanQueryResults($query_results);
        } elseif (librdf_query_results_is_graph($query_results)) {
            return new LibRDF_GraphQueryResults($query_results);
        } else {
            throw new LibRDF_Error("Unknown query results type");
        }
    }
}

/**
 * A specialized librdf_query_results wrapper for boolean results.
 *
 * Boolean results are returned when using an "ASK" query form to test
 * whether triples exist that satisfy certain conditions.  For example,
 *
 * <samp>PREFIX dc: <http://purl.org/dc/elements/1.1/><br>
 * ASK WHERE { ?book dc:creator ?author }</samp>
 *
 * in SPARQL will return a boolean result representing whether there is any
 * triple with the http://purl.org/dc/elements/1.1/creator predicate.
 *
 * In addition to iteration (which will iterate over a single boolean element),
 * a function {@link getValue} is provided to simply retrieve the result the
 * query.
 *
 * @package     LibRDF
 * @author      David Shea <david@gophernet.org>
 * @copyright   2006 David Shea
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 * @link        http://www.gophernet.org/projects/redland-php/
 */
class LibRDF_BooleanQueryResults extends LibRDF_QueryResults
{
    /**
     * Whether the iterator is still valid; i.e., whether next() has not been
     * called.
     *
     * @var     boolean
     * @access  private
     */
    private $isvalid;

    /**
     * Create a new boolean query result object.
     *
     * @param   resource    $query_results  The query results to wrap
     * @return  void
     * @throws  LibRDF_Error                If unable to wrap the query results
     * @access  public
     */
    public function __construct($query_results)
    {
        if ((!is_resource($query_results)) or 
            (!librdf_query_results_is_boolean($query_results))) {
            throw new LibRDF_Error("Argument must be a boolean librdf_query_results resource");
        }
        $this->query_results = $query_results;
        $this->isvalid = true;
    }

    /**
     * Return the boolean value of the result.
     *
     * @return  boolean     The value of the query
     * @access  public
     */
    public function getValue()
    {
        if (librdf_query_results_get_boolean($this->query_results)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Rewind the iterator.
     *
     * @return  void
     * @access  public
     */
    public function rewind()
    {
        $this->isvalid = true;
    }

    /**
     * Return the current (and only) boolean value.
     *
     * @return  boolean     The current value
     * @access  public
     */
    public function current()
    {
        $ret = librdf_query_results_get_boolean($this->query_results);
        if ($ret) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return the iterator key (always 0).
     *
     * @return  integer     The current key
     * @access  public
     */
    public function key()
    {
        return 0;
    }

    /**
     * Advance the iterator.
     *
     * Since boolean results have only one result, this function renders the
     * iterator invalid.
     *
     * @return  void
     * @access  public
     */
    public function next()
    {
        $this->isvalid = false;
    }

    /**
     * Test whether the iterator is still valid.
     *
     * @return  boolean     Whether the iterator is valid
     * @access  public
     */
    public function valid()
    {
        return $this->isvalid;
    }
}

/**
 * A specialized librdf_query_results wrapper for graph results.
 *
 * Graph results are returned by queries that construct a graph based on
 * triples that meet certain conditions such as those using the "CONSTRUCT"
 * or "DESCRIBE" SPARQL keywords.
 *
 * Iterating over this class will result in a stream of
 * {@link LibRDF_Statement} objects, similar to the result of iterating over
 * a {@link LibRDF_Model}.
 *
 * @package     LibRDF
 * @author      David Shea <david@gophernet.org>
 * @copyright   2006 David Shea
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 * @link        http://www.gophernet.org/projects/redland-php/
 */
class LibRDF_GraphQueryResults extends LibRDF_QueryResults
{
    /**
     * The LibRDF_StreamIterator used for iterating over the statements.
     *
     * @var     LibRDF_StreamIterator
     * @access  private
     */
    private $iterator;

    /**
     * Create a new graph query result object.
     *
     * @param   resource    $query_results  The query results to wrap
     * @return  void
     * @throws  LibRDF_Error                If unable to wrap the query results
     * @access  public
     */
    public function __construct($query_results)
    {
        if ((!is_resource($query_results)) or
            (!librdf_query_results_is_graph($query_results))) {
            throw new LibRDF_Error("Argument must be a graph librdf_query_results resource");
        }
        $this->query_results = $query_results;
        $this->iterator = NULL;
    }

    /**
     * Reset the $iterator variable with a new librdf_stream.
     *
     * @return  void
     * @access  private
     */
    private function resetIterator()
    {
        if ($this->iterator === NULL) {
            $this->iterator = new LibRDF_StreamIterator(librdf_query_results_as_stream($this->query_results));
        }
    }

    /**
     * Rewind the iterator.
     *
     * @return  void
     * @access  public
     */
    public function rewind()
    {
        $this->iterator = NULL;
    }

    /**
     * Fetch the current statement on the iterator.
     *
     * @return  LibRDF_Statement    The current statement
     * @access  public
     */
    public function current()
    {
        $this->resetIterator();
        return $this->iterator->current();
    }

    /**
     * Fetch the iterator's current key.
     *
     * @return  integer             The current key
     * @access  public
     */
    public function key()
    {
        $this->resetIterator();
        return $this->iterator->key();
    }

    /**
     * Advance the iterator to the next statement.
     *
     * @return  void
     * @access  public
     */
    public function next()
    {
        $this->resetIterator();
        return $this->iterator->next();
    }

    /**
     * Return whether the iterator is still valid.
     *
     * @return  boolean     Whether the iterator is valid
     * @access  public
     */
    public function valid()
    {
        $this->resetIterator();
        return $this->iterator->valid();
    }
}

/**
 * A specialized librdf_query_results wrapper for bindings results.
 *
 * Bindings are returned by SELECT statements and associate result nodes with
 * names for each tuple in the result set.  For example, the query
 *
 * <samp>SELECT ?book, ?author WHERE (?book, dc:creator, ?author)<br>
 * USING dc for <http://purl.org/dc/elements/1.1/>"</samp>
 *
 * in RDQL would result in a set of tuples, each containing a value for
 * "author" and "book".  This results of iterating over this object are
 * associative arrays of the result names and values.  The iterator cannot
 * be rewound.
 *
 * @package     LibRDF
 * @author      David Shea <david@gophernet.org>
 * @copyright   2006 David Shea
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 * @link        http://www.gophernet.org/projects/redland-php/
 */
class LibRDF_BindingsQueryResults extends LibRDF_QueryResults
{
    /**
     * Whether the iterator is still valid.
     *
     * @var     boolean
     * @access  private
     */
    private $isvalid;

    /**
     * Whether the iterator is rewindable; i.e., whether the iterator has been
     * advanced.
     *
     * @var     boolean
     * @access  private
     */
    private $rewindable;

    /**
     * Create a new bindings query result object.
     *
     * @param   resource    $query_results  The query results to wrap
     * @return  void
     * @throws  LibRDF_Error                If unable to wrap the query results
     * @access  public
     */
    public function __construct($query_results)
    {
        if ((!is_resource($query_results)) or
            (!librdf_query_results_is_bindings($query_results))) {
            throw new LibRDF_Error("Argument must be a bindings librdf_query_results resource");
        }
        $this->query_results = $query_results;
        $this->isvalid = true;
        $this->rewindable = true; 
        $this->key = 0;
    }

    /**
     * Rewind the iterator.
     *
     * Rewinding is not supported, so this function will invalidate the
     * iterator unless it is still in the initial (rewound) position.
     *
     * @return  void
     * @access  public
     */
    public function rewind()
    {
        if (!($this->rewindable)) {
            $this->isvalid = false;
        }
    }

    /**
     * Return the current tuple of bindings.
     *
     * The result is an associative array using the binding names as the
     * indices.
     *
     * @return  array           The current bindings tuple
     * @throws  LibRDF_Error    If unable to get the current bindings tuple
     * @access  public
     */
    public function current()
    {
        if (($this->isvalid) and (!librdf_query_results_finished($this->query_results))) {
            $retarr = array();
            $numbindings = librdf_query_results_get_bindings_count($this->query_results);
            if ($numbindings < 0) {
                throw new LibRDF_Error("Unable to get number of bindings in result");
            }

            for ($i = 0; $i<$numbindings; $i++) {
                $key = librdf_query_results_get_binding_name($this->query_results, $i);
                $value = librdf_query_results_get_binding_value($this->query_results, $i);

                if ((!$key) or ((!$value))) {
                    throw new LibRDF_Error("Failed to get current binding $i");
                }
                $retarr[$key] = LibRDF_Node::makeNode(librdf_new_node_from_node($value));
            }

            return $retarr;
        } else {
            return NULL;
        }
    }

    /**
     * Return the current key.
     *
     * @return  integer     The current key
     * @access  public
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * Advance the iterator.
     *
     * @return  void
     * @access  public
     */
    public function next()
    {
        if ($this->isvalid) {
            $this->rewindable = false;
            $ret = librdf_query_results_next($this->query_results);
            if ($ret) {
                $this->isvalid = false;
            } else {
                $this->key++;
            }
        }
    }

    /**
     * Return whether the iterator is still valid.
     *
     * @return  boolean     Whether the iterator is valid
     * @access  public
     */
    public function valid()
    {
        if (($this->isvalid) and (!librdf_query_results_finished($this->query_results))) {
            return true;
        } else {
            return false;
        }
    }
}

?>
