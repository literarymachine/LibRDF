<?php
/* $Id: Statement.php 171 2006-06-15 23:24:18Z das-svn $ */
/**
 * LibRDF_Statement, a representation of a single triple.
 *
 * Statements are a pair of nodes and an arc.  Both nodes and the arc are
 * represented as {@link LibRDF_Node} objects.  Statements are collected into
 * a graph using {@link LibRDF_Model}.
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
require_once(dirname(__FILE__) . '/Node.php');

/**
 * A wrapper around the librdf_statement datatype.
 *
 * @package     LibRDF
 * @author      David Shea <david@gophernet.org>
 * @copyright   2006 David Shea
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 * @link        http://www.gophernet.org/projects/redland-php/
 */
class LibRDF_Statement
{
    /**
     * The wrapped librdf_statement resource.
     *
     * @var     resource
     * @access  public
     */
    private $statement;

    /**
     * Create a new Statement.
     *
     * The subject must be either a URINode or a BlankNode.  The predicate
     * must be a URINode.
     *
     * @param   mixed       $statement  The librdf_statement to copy or the source Node of a statement
     * @param   LibRDF_Node $predicate  The statement's predicate
     * @param   LibRDF_Node $object     The statement's object
     * @return  void
     * @throws  LibRDF_Error            If unable to create a new statement
     * @access  public
     */
    public function __construct()
    {
        $num_args = func_num_args();
        if ($num_args == 1) {
            $statement = func_get_arg(0);
            if (!is_resource($statement)) {
                throw new LibRDF_Error("Single parameter must be a librdf_statement");
            } else {
                $this->statement = $statement;
            }
        } elseif ($num_args == 3) {
            $subject = func_get_arg(0);
            $predicate = func_get_arg(1);
            $object = func_get_arg(2);

            if (($subject instanceof LibRDF_Node) and
                ($predicate instanceof LibRDF_Node) and
                ($object instanceof LibRDF_Node)) {

                $this->statement = librdf_new_statement_from_nodes(librdf_php_get_world(),
                    librdf_new_node_from_node($subject->getNode()), 
                    librdf_new_node_from_node($predicate->getNode()), 
                    librdf_new_node_from_node($object->getNode()));
            } else {
                throw new LibRDF_Error("Arguments must be of type LibRDF_Node");
            }
        }

        if (!$this->statement) {
            throw new LibRDF_Error("Uanble to create new statement");
        }
    }

    /**
     * Free a Statement's resources.
     *
     * @return  void
     * @access  public
     */
    public function __destruct()
    {
        if ($this->statement) {
            librdf_free_statement($this->statement);
        }
    }

    /**
     * Return a string representation of a statement
     *
     * @return  string  The statement as a string
     * @access  public
     */
    public function __toString()
    {
        $rs = librdf_statement_to_string($this->statement);
        if ("1.0.11" > librdf_version_string_get()) {
            $s = $this->getSubject();
            $p = $this->getPredicate();
            $o = $this->getObject();
            $rs = "$s $p $o";
        }
        return $rs;
    }

    /**
     * Clone a Statement
     *
     * @return  void
     * @throws  LibRDF_Error        If unable to create a new statement
     * @access  public
     */
    public function __clone()
    {
        $this->statement = librdf_new_statement_from_statement($this->statement);

        if (!$this->statement) {
            throw new LibRDF_Error("Unable to create new statement from statement");
        }
    }

    /**
     * Get the underlying librdf_statement resource.
     *
     * This function is intended for other LibRDF classes and should not
     * be called.
     *
     * @return  resource    The wrapped librdf_statement
     * @access  public
     */
    public function getStatement()
    {
        return $this->statement;
    }

    /**
     * Get the statement's subject.
     *
     * @return  LibRDF_Node The statement's subject
     * @access  public
     */
    public function getSubject()
    {
        $node = librdf_statement_get_subject($this->statement);
        // create a copy of the node to avoid double frees when the Node
        // object gets garbage collected
        $object = LibRDF_Node::makeNode(librdf_new_node_from_node($node));
        return $object;
    }

    /**
     * Get the statement's predicate
     *
     * @return  LibRDF_Node The statement's predicate
     * @access  public
     */
    public function getPredicate()
    {
        $node = librdf_statement_get_predicate($this->statement);
        $object = LibRDF_Node::makeNode(librdf_new_node_from_node($node));
        return $object;
    }

    /**
     * Get the statement's object.
     *
     * @return  LibRDF_Node The statement's object
     * @access  public
     */
    public function getObject()
    {
        $node = librdf_statement_get_object($this->statement);
        $object = LibRDF_Node::makeNode(librdf_new_node_from_node($node));
        return $object;
    }

    /**
     * Compare this statement with another statement.
     *
     * Two statements are equal if each of the three nodes in a statement
     * are equal to the corresponding nodes in the other statement.
     *
     * @param   LibRDF_Statement    $statement  The statement against which to compare
     * @return  boolean             Whether the statements are equal
     * @access  public
     */
    public function isEqual(LibRDF_Statement $statement)
    {
        return ((boolean) librdf_statement_equals($this->statement,
            $statement->getStatement()));
    }
}
?>
