<?php
/* $Id: Model.php 171 2006-06-15 23:24:18Z das-svn $ */
/**
 * LibRDF_Model, a representation of an RDF graph.
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
 * @author      Felix Ostrowski <felix.ostrowski@googlemail.com>
 * @copyright   2006 David Shea
 * @copyright   2011, 2012 Felix Ostrowski
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 * @link        http://www.gophernet.org/projects/redland-php/
 */

/**
 */
require_once(dirname(__FILE__) . '/Error.php');
require_once(dirname(__FILE__) . '/URI.php');
require_once(dirname(__FILE__) . '/Storage.php');
require_once(dirname(__FILE__) . '/Statement.php');
require_once(dirname(__FILE__) . '/Node.php');
require_once(dirname(__FILE__) . '/StreamIterator.php');
require_once(dirname(__FILE__) . '/Iterator.php');
require_once(dirname(__FILE__) . '/LibRDF.php');
require_once(dirname(__FILE__) . '/Parser.php');
require_once(dirname(__FILE__) . '/Serializer.php');
require_once(dirname(__FILE__) . '/ARC2_getFormat.php');

/**
 * The exception type used for statement lookup failures.
 *
 * An object of this type is thrown by {@link LibRDF_Model} when getSource,
 * getArc or getTarget is called with nodes that do not match any statement.
 *
 * @package     LibRDF
 * @author      David Shea <david@gophernet.org>
 * @copyright   2006 David Shea
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 * @link        http://www.gophernet.org/projects/redland-php/
 */
class LibRDF_LookupError extends Exception
{
    /**
     * Create a new LibRDF_LookupError.
     *
     * @param   string  $message    The error message to use
     * @return  void
     * @access  public
     */
    public function __construct($message=NULL)
    {
        parent::__construct($message);
    }
}

/**
 * A wrapper around the librdf_model datatype.
 *
 * A LibRDF_Model is a collection of {@link LibRDF_Statement} objects using
 * a {@link LibRDF_Storage} object to save the statements.  Statements are
 * added using {@link addStatement} or through the use of a
 * {@link LibRDF_Parser} and {@link loadStatementsFromString} or
 * {@link loadStatementsFromURI}, and statements are removed using
 * {@link removeStatement}.  Statements can be queried through the use of
 * either {@link findStatements} or a {@link LibRDF_Query} object.  The
 * statements can be written to a stream using {@link LibRDF_Serializer} and
 * {@link serializeStatements} or {@link serializeStatementsToFile}.
 *
 * This object is iterable.  When used as part of a foreach statement, it
 * will iterate over every statement contained in the model.  For example,
 *
 * <code>foreach ($model as $statement) {
 *    echo $statement;
 * }</code>
 *
 * will echo each statement individually.  Unlike {@link LibRDF_StreamIterator},
 * the Model can be rewound and used for multiple iterations.
 *
 * @package     LibRDF
 * @author      David Shea <david@gophernet.org>
 * @copyright   2006 David Shea
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 * @link        http://www.gophernet.org/projects/redland-php/
 */
class LibRDF_Model implements Iterator
{
    /**
     * The underlying librdf_model.
     *
     * @var     resource
     * @access  private
     */
    private $model;

    /**
     * The stream iterator over the model's statements.
     *
     * This variable begins as NULL and is set by the iteration functions
     * using {@link resetIterator}.  {@link rewind} resets this variable to
     * null, causing subsequent calls of the other iteration function to
     * begin anew with a fresh iterator.
     *
     * @var     LibRDF_StreamIterator
     * @access  private
     */
    private $iterator;

    /**
     * Create a new librdf_model.
     *
     * See the {@link http://librdf.org/ librdf} documentation for information
     * on the possible options.
     *
     * @param   LibRDF_Storage  $storage    The storage on which this model should be built
     * @param   string          $options    Options to pass to librdf_new_model
     * @return  void
     * @throws  LibRDF_Error    If unable to create a new model
     * @access  public
     */
    public function __construct(LibRDF_Storage $storage, $options=NULL)
    {
        $this->model = librdf_new_model(librdf_php_get_world(),
            $storage->getStorage(), $options);

        if (!$this->model) {
            throw new LibRDF_Error("Unable to create new model");
        }

        $this->iterator = NULL;
    }

    /**
     * Free a model's resources.
     *
     * @return  void
     * @access  public
     */
    public function __destruct()
    {
        if ($this->model) {
            librdf_free_model($this->model);
        }
    }

    /**
     * Return a string representation of the model.
     *
     * This function can be used as a lazy form of serializtion.  Use
     * a {@link LibRDF_Serializer} if you care about the format of the output.
     *
     * @return  string  The model as a string
     * @access  public
     */
    public function __toString()
    {
        return librdf_model_to_string($this->model, NULL, NULL, NULL, NULL);
    }
    
    /**
     * Create a copy of the model.
     *
     * Whether a model can be copied depends upon the underlying model factory.
     * In-memory storages cannot be cloned, so a clone of models using this
     * form of storage will fail.
     *
     * @return  void
     * @throws  LibRDF_Error    If unable to copy the model
     * @access  public
     */
    public function __clone()
    {
        $this->model = librdf_new_model_from_model($this->model);

        if (!$this->model) {
            throw new LibRDF_Error("Unable to create new model from model");
        }
    }

    /**
     * Return the model resource.
     *
     * This function is intended for other LibRDF classes and should not
     * be called.
     *
     * @return  resource    The wrapped model resource
     * @access  public
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Add a statement to the model.
     *
     * A statement can be added more than once by adding it under different
     * contexts, otherwise adding a duplicate statement will have no effect.
     * Not all models support contexts.
     *
     * @param   LibRDF_Statement    $statement  The statement to add
     * @param   LibRDF_URINode      $context    An optional context under which to add the statement
     * @return  void
     * @throws  LibRDF_Error        If unable to add the statement
     * @access  public
     */
    public function addStatement(LibRDF_Statement $statement,
        LibRDF_URINode $context=NULL)
    {
        // This function raises some issues with threading and what to do 
        // with an active iterator.  I don't know if PHP even has a concept
        // of threads, so I'm just going to leave any active iterator alone
        // and hope that the underlying librdf_stream does the right thing,
        // whatever that may be.
        if ($context != NULL) {
            $context = $context->getNode();
            $ret = librdf_model_context_add_statement($this->model,
                $context, $statement->getStatement());
        } else {
            $ret = librdf_model_add_statement($this->model,
                $statement->getStatement());
        }

        if ($ret) {
            throw new LibRDF_Error("Unable to add statement");
        }
    }

    /**
     * Remove a statement from the model.
     *
     * @param   LibRDF_Statement    $statement  The statement to remove
     * @param   LibRDF_URINode      $context    The context from which to remove the statement
     * @return  void
     * @throws  LibRDF_Error        If unable to remove the statement
     * @access  public
     */
    public function removeStatement(LibRDF_Statement $statement,
        LibRDF_URINode $context=NULL)
    {
        if ($context != NULL) {
            $context = $context->getNode();
            $ret = librdf_model_context_remove_statement($this->model, $context,
                $statement->getStatement());
        } else {
            $ret = librdf_model_remove_statement($this->model, 
                $statement->getStatement());
        }

        if ($ret) {
            throw new LibRDF_Error("Unable to remove statement");
        }
    }

    /**
     * Return the number of statements in the model.
     *
     * @return  integer The number of statements
     * @access  public
     */
    public function size()
    {
        return librdf_model_size($this->model);
    }

    /**
     * Return a single source node that is part of a statement containing
     * the given predicate and target.
     *
     * This function is equivalent to 
     * <code>$model->findStatements(NULL, $predicate, $target)->current()->getSubject()</code>
     *
     * @param   LibRDF_Node     $arc    The predicate node for which to search
     * @param   LibRDF_Node     $target The target node for which to search
     * @return  LibRDF_Node             A node that matches the criteria
     * @throws  LibRDF_LookupError      If no statement with the given predicate and target is found
     * @access  public
     */
    public function getSource(LibRDF_Node $arc, LibRDF_Node $target)
    {
        $source = librdf_model_get_source($this->model,
            $arc->getNode(), $target->getNode());
        if ($source) {
            return LibRDF_Node::makeNode($source);
        } else {
            throw new LibRDF_LookupError("No such statement");
        }
    }

    /**
     * Return source nodes that are part of a statement containing the
     * given predicate and object.
     *
     * @param   LibRDF_Node     $arc    The arc node for which to search
     * @param   LibRDF_Node     $target The target node for which to search
     * @return  LibRDF_Iterator         An iterator for nodes that matches the criteria
     * @throws  LibRDF_LookupError      If no statement with the given source and predicate is found
     * @access  public
     */
    public function getSources(LibRDF_Node $arc, LibRDF_Node $target)
    {
        $sources = librdf_model_get_sources($this->model,
            $arc->getNode(), $target->getNode());
        if ($sources) {
            return new LibRDF_Iterator($sources, $this);
        } else {
            throw new LibRDF_Error("Failed to create iterator");
        }
    }

    /**
     * Return a single predicate node that is part of a statement containing
     * the given source and target.
     *
     * This function is equivalent to
     * <code>$model->findStatements($source, NULL, $target)->current()->getPredicate()</code>
     *
     * @param   LibRDF_Node     $source The source node for which to search
     * @param   LibRDF_Node     $target The target node for which to search
     * @return  LibRDF_Node             A node that matches the criteria
     * @throws  LibRDF_LookupError      If no statement with the given source and target is found
     * @access  public
     */
    public function getArc(LibRDF_Node $source, LibRDF_Node $target)
    {
        $arc = librdf_model_get_arc($this->getModel(),
            $source->getNode(), $target->getNode());
        if ($arc) {
            return LibRDF_Node::makeNode($arc);
        } else {
            throw new LibRDF_LookupError("No such statement");
        }
    }

    /**
     * Return arc nodes that are part of a statement containing the
     * given source and predicate.
     *
     * @param   LibRDF_Node     $source The source node for which to search
     * @param   LibRDF_Node     $target The target node for which to search
     * @return  LibRDF_Iterator         An iterator for nodes that matches the criteria
     * @throws  LibRDF_LookupError      If no statement with the given source and predicate is found
     * @access  public
     */
    public function getArcs(LibRDF_Node $source, LibRDF_Node $target)
    {
        $arcs = librdf_model_get_arcs($this->model,
            $source->getNode(), $target->getNode());
        if ($arcs) {
            return new LibRDF_Iterator($arcs, $this);
        } else {
            throw new LibRDF_Error("Failed to create iterator");
        }
    }

    /**
     * Return a single target node that is part of a statement containing the
     * given source and predicate.
     *
     * This function is equivalent to
     * <code>$model->findStatements($source, $predicate, NULL)->current()->getTarget()</code>
     *
     * @param   LibRDF_Node     $source The source node for which to search
     * @param   LibRDF_Node     $arc    The predicate node for which to search
     * @return  LibRDF_Node             A node that matches the criteria
     * @throws  LibRDF_LookupError      If no statement with the given source and predicate is found
     * @access  public
     */
    public function getTarget(LibRDF_Node $source, LibRDF_Node $arc)
    {
        $target = librdf_model_get_target($this->model,
            $source->getNode(), $arc->getNode());
        if ($target) {
            return LibRDF_Node::makeNode($target);
        } else {
            throw new LibRDF_LookupError("No such statement");
        }
    }

    /**
     * Return target nodes that are part of a statement containing the
     * given source and predicate.
     *
     * @param   LibRDF_Node     $source The source node for which to search
     * @param   LibRDF_Node     $arc    The predicate node for which to search
     * @return  LibRDF_Iterator         An iterator for nodes that matches the criteria
     * @throws  LibRDF_LookupError      If no statement with the given source and predicate is found
     * @access  public
     */
    public function getTargets(LibRDF_Node $source, LibRDF_Node $arc)
    {
        $targets = librdf_model_get_targets($this->model,
            $source->getNode(), $arc->getNode());
        if ($targets) {
            return new LibRDF_Iterator($targets, $this);
        } else {
            throw new LibRDF_Error("Failed to create iterator");
        }
    }

    /**
     * Test whether the model contains a statement.
     *
     * @param   LibRDF_Statement    $statement  The statement for which to search
     * @return  boolean                         Whether such a statement exists in the graph
     * @access  public
     */
    public function hasStatement(LibRDF_Statement $statement)
    {
        if (librdf_model_contains_statement($this->model, 
                $statement->getStatement())) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Find a statement in the model.
     *
     * A NULL argument for any of source, predicate or target is treated as
     * a wildcard.  If a context is given, only statements from that context
     * will be returned.  The result is an object that be used in foreach
     * iteration.  The returned iterator cannot be rewound.
     *
     * The search arguments can be either a (source, predicate target) triple
     * of LibRDF_Node objects or a LibRDF_Statement object.  Valid argument 
     * lists are (source, predicate, target, [context]) or
     * (statement, [context]).
     *
     * For more complex queries, see {@link LibRDF_Query}.
     *
     * @param   mixed       $statement  The statement to match or a source node
     * @param   LibRDF_Node $predicate  The predicate to match
     * @param   LibRDF_Node $target     The target to match
     * @param   LibRDF_URINode  $context    The context in which to search
     * @return  LibRDF_StreamIterator   An iterator over the matched statements
     * @access  public
     */
    public function findStatements()
    {
        $num_args = func_num_args();
        if (($num_args == 1) or ($num_args == 2)) {
            $statement = func_get_arg(0);
            if (!($statement instanceof LibRDF_Statement)) {
                throw new LibRDF_Error("First argument must be a LibRDF_Statement");
            }

            if ($num_args == 2) {
                $context = func_get_arg(1);
                if (!($context instanceof LibRDF_URINode)) {
                    throw new LibRDF_Error("Context must be LibRDF_URINode");
                }
            } else {
                $context = NULL;
            }

            $statement = $statement->getStatement();
        } elseif (($num_args == 3) or ($num_args == 4)) {
            $source = func_get_arg(0);
            $predicate = func_get_arg(1);
            $target = func_get_arg(2);

            if ($source !== NULL) {
                if (!($source instanceof LibRDF_Node)) {
                    throw new LibRDF_Error("Argument 1 must be of type LibRDF_Node");
                } else {
                    $source = librdf_new_node_from_node($source->getNode());
                }
            }

            if ($predicate !== NULL) {
                if (!($predicate instanceof LibRDF_Node)) {
                    throw new LibRDF_Error("Argument 2 must be of type LibRDF_Node");
                } else {
                    $predicate = librdf_new_node_from_node($predicate->getNode());
                }
            }

            if ($target !== NULL) {
                if (!($target instanceof LibRDF_Node)) {
                    throw new LibRDF_Error("Argument 3 must be of type LibRDF_Node");
                } else {
                    $target = librdf_new_node_from_node($target->getNode());
                }
            }

            if ($num_args == 4) {
                $context = func_get_arg(3);
                if (!($context instanceof LibRDF_URINode)) {
                    throw new LibRDF_Error("Context must be LibRDF_URINode");
                }
            } else {
                $context = NULL;
            }

            $statement = librdf_new_statement_from_nodes(librdf_php_get_world(),
                $source, $predicate, $target);
        } else {
            throw new LibRDF_Error("findStatements takes 2-4 arguments");
        }

        if ($context !== NULL) {
            $stream_resource = librdf_model_find_statements_in_context($this->model,
                $statement, $context->getNode());
        } else {
            $stream_resource = librdf_model_find_statements($this->model,
                $statement);
        }

        if ($num_args > 2) {
            librdf_free_statement($statement);
        }

        if (!$stream_resource) {
            throw new LibRDF_Error("Unable to create new statement iterator");
        }

        return new LibRDF_StreamIterator($stream_resource, $this);
    }

    /**
     * Discard the current statement iterator and create a new one.
     *
     * @return  void
     * @access  private
     */
    private function resetIterator()
    {
        if ($this->iterator === NULL) {
            $this->iterator = new 
                LibRDF_StreamIterator(librdf_model_as_stream($this->model),
                    $this);
        }
    }

    /**
     * Reset the statement iterator.
     *
     * @return  void
     * @access  public
     */
    public function rewind()
    {
        $this->iterator = NULL;
    }

    /**
     * Return the current statement on the iterator.
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
     * Return the current iteration key.
     *
     * @return  integer The current key
     * @access  public
     */
    public function key()
    {
        $this->resetIterator();
        return $this->iterator->key();
    }

    /**
     * Advance the iterator's position.
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
     * Check whether the statement iterator is still valid.
     *
     * @return  boolean     Whether the iterator is still valid
     * @access  public
     */
    public function valid()
    {
        $this->resetIterator();
        return $this->iterator->valid();
    }

    /**
     * Load statements using a {@link LibRDF_Parser}.
     *
     * If no $base_uri is given, the RDF namespace URI will be used as the
     * base for relative URIs.
     *
     * @param   LibRDF_Parser   $parser The parser with which to parse the string
     * @param   string          $string The string to parse
     * @param   string          $base_uri   The base URI to use for relative URIs in the string
     * @return  void
     * @throws  LibRDF_Error    If unable to parse the string
     * @access  public
     */
    public function loadStatementsFromString(LibRDF_Parser $parser,
        $string, $base_uri=NULL)
    {
        if ($base_uri) {
            $base_uri = new LibRDF_URI($base_uri);
        } else {
            $base_uri = new LibRDF_URI(RDF_BASE_URI);
        }

        $ret = librdf_parser_parse_string_into_model($parser->getParser(),
            $string, $base_uri->getURI(), $this->model);

        if ($ret) {
            throw new LibRDF_Error("Unable to parse string into model");
        }
    }

    /**
     * Load statements from a URI using a {@link LibRDF_Parser}.
     *
     * @param   LibRDF_Parser   $parser The parser with which to parse the URI's contents
     * @param   string          $uri    The URI with the contents to load
     * @param   string          $base_uri   The base URI to use for relative URIs if different from $uri
     * @return  void
     * @throws  LibRDF_Error    If unable to parse the URI contents
     * @access  public
     */
    public function loadStatementsFromURI(LibRDF_Parser $parser,
        $uri, $base_uri=NULL)
    {
        if ($uri instanceof LibRDF_URINode) {
            $uri = new LibRDF_URI(substr($uri, 1, -1));
        } else {
            $uri = new LibRDF_URI($uri);
        }
        if ($base_uri) {
            $base_uri = new LibRDF_URI($base_uri);
        }

        $ret = librdf_parser_parse_into_model($parser->getParser(),
            $uri->getURI(), ($base_uri ? $base_uri->getURI() : $base_uri), 
            $this->model);

        if ($ret) {
            throw new LibRDF_Error("Unable to parse URI into model");
        }
    }

    /**
     * Load statements from a URI or string, detecting the necessary parser.
     *
     * @param   string          $content    The URI or string with the contents to load
     * @param   string          $base_uri   The base URI to use for relative URIs
     * @return  void
     * @throws  LibRDF_Error    If unable to parse the contents
     * @access  public
     */
    public function loadStatements($content, $base_uri=NULL)
    {
        $mtype = '';
        $ext = '';
        if (is_file($content)) {
            $ext = preg_match('/\.([^\.]+)$/', $content, $match)
                ? $match[1] : '';
            $data = file_get_contents($content);
        } else if (filter_var($content, FILTER_VALIDATE_URL)) {
            $ext = preg_match('/\.([^\.]+)$/', $content, $match)
                ? $match[1] : '';
            $ch = curl_init($content);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1) ;
            $data = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new LibRDF_Error("Unable to parse data into model");
            }
            $info = curl_getinfo($ch);
            $mtype = $info['content_type'];
            curl_close($ch);
        } else if (is_string($content)) {
            $data = $content;
        } else {
            throw new LibRDF_Error("Unable to parse data into model");
        }
        $format = ARC2_getFormat($data, $mtype, $ext);
        $parser = new LibRDF_Parser($format);
        $this->loadStatementsFromString($parser, $data,
                $base_uri);
    }

    /**
     * Serialize the model as a string.
     *
     * @param   LibRDF_Serializer   $serializer The serializer to use
     * @param   string              $base_uri   The base URI to use if relative URIs are desired
     * @return  string              The model as a string
     * @throws  LibRDF_Error        If unable to serialize the model
     * @access  public
     */
    public function serializeStatements(LibRDF_Serializer $serializer,
        $base_uri=NULL)
    {
        if ($base_uri) {
            $base_uri = new LibRDF_URI($base_uri);
        }

        $ret = librdf_serializer_serialize_model_to_string($serializer->getSerializer(),
            ($base_uri ? $base_uri->getURI() : $base_uri), $this->model);

        if (!$ret) {
            throw new LibRDF_Error("Unable to serialize model");
        } else {
            return $ret;
        }
    }

    /**
     * Serialize the model and write the contents to a file.
     *
     * @param   LibRDF_Serializer   $serializer The serializer to use
     * @param   string              $file_name  The name of the file to which to write
     * @param   string              $base_uri   The base URI to use
     * @return  void
     * @throws  LibRDF_Error        If unable to serialize the model
     * @access  public
     */
    public function serializeStatementsToFile(LibRDF_Serializer $serializer,
        $file_name, $base_uri=NULL)
    {
        if ($base_uri) {
            $base_uri = new LibRDF_URI($base_uri);
        }

        $ret = librdf_serializer_serialize_model_to_file($serializer->getSerializer(),
            $file_name, ($base_uri ? $base_uri->getURI() : $base_uri), $this->model);

        if ($ret) {
            throw new LibRDF_Error("Error serializing model to file");
        }
    }

    /**
     * Turns an RDF list into an ordered PHP array.
     *
     * @param  LibRDF_Node  $head The head of the list.
     * @return array  The list as an array.
     */
    public function getListAsArray(LibRDF_Node $head)
    {
        $rdfFirst =
            new LibRDF_URINode('http://www.w3.org/1999/02/22-rdf-syntax-ns#first');
        $rdfRest = 
            new LibRDF_URINode('http://www.w3.org/1999/02/22-rdf-syntax-ns#rest');
        $lst = array();
        try {
            $lst[] = $this->getTarget($head, $rdfFirst);
        } catch (LibRDF_LookupError $e) {
            return $lst;
        }
        $tail = $this->getTarget($head, $rdfRest);
        return array_merge($lst, $this->getListAsArray($tail));
    }
}

?>
