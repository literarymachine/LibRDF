<?php
/**
 * LibRDF_Reasoner, an RDFS+ inference engine
 *
 * PHP version 5
 *
 * Copyright (C) 2014, Felix Ostrowski <felix.ostrowski@gmail.com>
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
 * @author      Felix Ostrowski <felix.ostrowski@googlemail.com>
 * @copyright   2014 Felix Ostrowski
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 */

/**
 */
require_once(dirname(__FILE__) . '/Model.php');
require_once(dirname(__FILE__) . '/Query.php');

/**
 * Simple RDFS+ inference engine, based on SPARQL queries
 */
class LibRDF_Reasoner
{

  /**
   * The mode to use, currently 'rdfs' or 'rdfs+'
   *
   * @var string
   */
  private $__mode;

  /**
   * Create a new LibRDF_Reasoner
   *
   * @param  string  $mode The mode to use
   */
  public function __construct($mode) {
    $this->__mode = $mode;
  }

  /**
   * Add inferred (implicit) statements to instance data
   *
   * @param  LibRDF_Model  $tbox The model containing the ontology
   * @param  LibRDF_Model  $abox The model containing instance data
   */
  public function inferStatements(LibRDF_Model $tbox, LibRDF_Model $abox)
  {
    $this->_inferStatements($this->_buildQueries($tbox), $abox);
  }

  /**
   * Recursively apply SPARQL CONSTRUCT queries until no new triples are
   * generated.
   *
   * @param  mixed  $queries The queries to excecute to generate
   *                         inferred triples
   * @param  array  $abox    The instance data that the inferred triples
   *                         will be added to
   */
  protected function _inferStatements($queries, $abox)
  {
    $add_count = 0;
    foreach ($queries as $query) {
      $inferred = $query->execute($abox);
      foreach ($inferred as $triple) {
        if (!$abox->hasStatement($triple)) {
          $abox->addStatement($triple);
          $add_count++;
        }
      }
    }
    if ($add_count > 0) $this->_inferStatements($queries, $abox);
  }

  /**
   * Generate SPARQL CONSTRUCT queries based on an ontology model
   *
   * @param  LibRDF_Model  $tbox The ontology model
   */
  protected function _buildQueries(LibRDF_Model $tbox)
  {
    $queries = array();
    switch ($this->__mode) {
      case 'rdfs+':
        $queries = array_merge($this->_buildOwlInverseOfQueries($tbox), $queries);
        $queries = array_merge($this->_buildOwlSymmetricPropertyQueries($tbox), $queries);
        $queries = array_merge($this->_buildOwlTransitivePropertyQueries($tbox), $queries);
      case 'rdfs':
      default:
        $queries = array_merge($this->_buildRdfsSubPropertyOfQueries($tbox), $queries);
        $queries = array_merge($this->_buildRdfsSubClassOfQueries($tbox), $queries);
        $queries = array_merge($this->_buildRdfsDomainQueries($tbox), $queries);
        $queries = array_merge($this->_buildRdfsRangeQueries($tbox), $queries);
    }
    return $queries;
  }


  /**
   * Generate SPARQL CONSTRUCT queries for rdfs:subPropertyOf
   *
   * @param  mixed  $tbox The ontology model
   * @return array        The generated queries
   */
  protected function _buildRdfsSubPropertyOfQueries($tbox)
  {
    $RDFS = new LibRDF_NS('http://www.w3.org/2000/01/rdf-schema#');
    $rdfsSubPropertyOfQueries = array();
    $rdfsSubPropertyOfStmts = $tbox->findStatements(
      null, $RDFS->subPropertyOf, null
    );
    foreach ($rdfsSubPropertyOfStmts as $rdfsSubPropertyOfStmt) {
      $rdfsSubPropertyOfQueryString = sprintf(
        "CONSTRUCT {?s %s ?o} WHERE {?s %s ?o}\n",
        $rdfsSubPropertyOfStmt->getObject(),
        $rdfsSubPropertyOfStmt->getSubject()
      );
      $rdfsSubPropertyOfQueries[] = new LibRDF_Query(
        $rdfsSubPropertyOfQueryString, null, 'sparql'
      );
    }
    return $rdfsSubPropertyOfQueries;
  }

  /**
   * Generate SPARQL CONSTRUCT queries for rdfs:subClassOf
   *
   * @param  mixed  $tbox The ontology model
   * @return array        The generated queries
   */
  protected function _buildRdfsSubClassOfQueries($tbox)
  {
    $RDFS = new LibRDF_NS('http://www.w3.org/2000/01/rdf-schema#');
    $rdfsSubClassOfQueries = array();
    $rdfsSubClassOfStmts = $tbox->findStatements(
      null, $RDFS->subClassOf, null
    );
    foreach ($rdfsSubClassOfStmts as $rdfsSubClassOfStmt) {
      $rdfsSubClassOfQueryString = sprintf(
        "CONSTRUCT {?s a %s} WHERE {?s a %s}\n",
        $rdfsSubClassOfStmt->getObject(),
        $rdfsSubClassOfStmt->getSubject()
      );
      $rdfsSubClassOfQueries[] = new LibRDF_Query(
        $rdfsSubClassOfQueryString, null, 'sparql'
      );
    }
    return $rdfsSubClassOfQueries;
  }

  /**
   * Generate SPARQL CONSTRUCT queries for rdfs:domain
   *
   * @param  mixed  $tbox The ontology model
   * @return array        The generated queries
   */
  protected function _buildRdfsDomainQueries($tbox)
  {
    $RDFS = new LibRDF_NS('http://www.w3.org/2000/01/rdf-schema#');
    $rdfsDomainQueries = array();
    $rdfsDomainStmts = $tbox->findStatements(
      null, $RDFS->domain, null
    );
    foreach ($rdfsDomainStmts as $rdfsDomainStmt) {
      $rdfsDomainQueryString = sprintf(
        "CONSTRUCT {?s a %s} WHERE {?s %s _:o}\n",
        $rdfsDomainStmt->getObject(),
        $rdfsDomainStmt->getSubject()
      );
      $rdfsDomainQueries[] = new LibRDF_Query(
        $rdfsDomainQueryString, null, 'sparql'
      );
    }
    return $rdfsDomainQueries;
  }

  /**
   * Generate SPARQL CONSTRUCT queries for rdfs:range
   *
   * @param  mixed  $tbox The ontology model
   * @return array        The generated queries
   */
  protected function _buildRdfsRangeQueries($tbox)
  {
    $RDFS = new LibRDF_NS('http://www.w3.org/2000/01/rdf-schema#');
    $rdfsRangeQueries = array();
    $rdfsRangeStmts = $tbox->findStatements(
      null, $RDFS->range, null
    );
    foreach ($rdfsRangeStmts as $rdfsRangeStmt) {
      $rdfsRangeQueryString = sprintf(
        "CONSTRUCT {?o a %s} WHERE {_:s %s ?o}\n",
        $rdfsRangeStmt->getObject(),
        $rdfsRangeStmt->getSubject()
      );
      $rdfsRangeQueries[] = new LibRDF_Query(
        $rdfsRangeQueryString, null, 'sparql'
      );
    }
    return $rdfsRangeQueries;
  }

  /**
   * Generate SPARQL CONSTRUCT queries for owl:inverseOf
   *
   * @param  mixed  $tbox The ontology model
   * @return array        The generated queries
   */
  protected function _buildOwlInverseOfQueries($tbox)
  {
    $OWL = new LibRDF_NS('http://www.w3.org/2002/07/owl#');
    $owlInverseOfQueries = array();
    $owlInverseOfStmts = $tbox->findStatements(
      null, $OWL->inverseOf, null
    );
    foreach ($owlInverseOfStmts as $owlInverseOfStmt) {
      $owlInverseOfQueryString = sprintf(
        "CONSTRUCT {?o %s ?s} WHERE {?s %s ?o}\n",
        $owlInverseOfStmt->getSubject(),
        $owlInverseOfStmt->getObject()
      );
      $owlInverseOfQueries[] = new LibRDF_Query(
        $owlInverseOfQueryString, null, 'sparql'
      );
    }
    return $owlInverseOfQueries;
  }

  /**
   * Generate SPARQL CONSTRUCT queries for owl:SymmetricProperty
   *
   * @param  mixed  $tbox The ontology model
   * @return array        The generated queries
   */
  protected function _buildOwlSymmetricPropertyQueries($tbox)
  {
    $OWL = new LibRDF_NS('http://www.w3.org/2002/07/owl#');
    $RDF = new LibRDF_NS('http://www.w3.org/1999/02/22-rdf-syntax-ns#');
    $owlSymmetricPropertyQueries = array();
    $owlSymmetricPropertyStmts = $tbox->findStatements(
      null, $RDF->type, $OWL->SymmetricProperty
    );
    foreach ($owlSymmetricPropertyStmts as $owlSymmetricPropertyStmt) {
      $owlSymmetricPropertyQueryString = sprintf(
        "CONSTRUCT {?o %s ?s} WHERE {?s %s ?o}\n",
        $owlSymmetricPropertyStmt->getSubject(),
        $owlSymmetricPropertyStmt->getSubject()
      );
      $owlSymmetricPropertyQueries[] = new LibRDF_Query(
        $owlSymmetricPropertyQueryString, null, 'sparql'
      );
    }
    return $owlSymmetricPropertyQueries;
  }

  /**
   * Generate SPARQL CONSTRUCT queries for owl:TransitiveProperty
   *
   * @param  mixed  $tbox The ontology model
   * @return array        The generated queries
   */
  protected function _buildOwlTransitivePropertyQueries($tbox)
  {
    $OWL = new LibRDF_NS('http://www.w3.org/2002/07/owl#');
    $RDF = new LibRDF_NS('http://www.w3.org/1999/02/22-rdf-syntax-ns#');
    $owlTransitivePropertyQueries = array();
    $owlTransitivePropertyStmts = $tbox->findStatements(
      null, $RDF->type, $OWL->TransitiveProperty
    );
    foreach ($owlTransitivePropertyStmts as $owlTransitivePropertyStmt) {
      $owlTransitivePropertyQueryString = sprintf(
        "CONSTRUCT {?s %s ?o} WHERE {?s %s ?x . ?x %s ?o}\n",
        $owlTransitivePropertyStmt->getSubject(),
        $owlTransitivePropertyStmt->getSubject(),
        $owlTransitivePropertyStmt->getSubject()
      );
      $owlTransitivePropertyQueries[] = new LibRDF_Query(
        $owlTransitivePropertyQueryString, null, 'sparql'
      );
    }
    return $owlTransitivePropertyQueries;
  }

}
