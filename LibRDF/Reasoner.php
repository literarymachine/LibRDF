<?php
/**
 * LibRDF_Reasoner, an RDFS inference engine
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
 * Simple RDFS inference engine, based on SPARQL queries
 */
class LibRDF_Reasoner {

  /**
   * Add inferred (implicit) statements to instance data
   *
   * @param  LibRDF_Model  $tbox The model containing the ontology
   * @param  LibRDF_Model  $abox The model containing instance data
   */
  public function inferStatements(LibRDF_Model $tbox, LibRDF_Model $abox) {
    $this->_inferStatements($this->buildQueries($tbox), $abox);
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
  protected function _inferStatements($queries, $abox) {
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
  public function buildQueries(LibRDF_Model $tbox) {
    $RDFS = new LibRDF_NS('http://www.w3.org/2000/01/rdf-schema#');

    $rdfsQueries = array();

    $rdfsSubPropertyOfStmts = $tbox->findStatements(
      null, $RDFS->subPropertyOf, null
    );
    foreach ($rdfsSubPropertyOfStmts as $rdfsSubPropertyOfStmt) {
      $rdfsSubPropertyOfQueryString = sprintf(
        "CONSTRUCT {?s %s ?o} WHERE {?s %s ?o}\n",
        $rdfsSubPropertyOfStmt->getObject(),
        $rdfsSubPropertyOfStmt->getSubject()
      );
      $rdfsQueries[] = new LibRDF_Query(
        $rdfsSubPropertyOfQueryString, null, 'sparql'
      );
    }

    $rdfsSubClassOfStmts = $tbox->findStatements(
      null, $RDFS->subClassOf, null
    );
    foreach ($rdfsSubClassOfStmts as $rdfsSubClassOfStmt) {
      $rdfsSubClassOfQueryString = sprintf(
        "CONSTRUCT {?s a %s} WHERE {?s a %s}\n",
        $rdfsSubClassOfStmt->getObject(),
        $rdfsSubClassOfStmt->getSubject()
      );
      $rdfsQueries[] = new LibRDF_Query(
        $rdfsSubClassOfQueryString, null, 'sparql'
      );
    }

    $rdfsDomainStmts = $tbox->findStatements(
      null, $RDFS->domain, null
    );
    foreach ($rdfsDomainStmts as $rdfsDomainStmt) {
      $rdfsDomainQueryString = sprintf(
        "CONSTRUCT {?s a %s} WHERE {?s %s _:o}\n",
        $rdfsDomainStmt->getObject(),
        $rdfsDomainStmt->getSubject()
      );
      $rdfsQueries[] = new LibRDF_Query(
        $rdfsDomainQueryString, null, 'sparql'
      );
    }

    $rdfsRangeStmts = $tbox->findStatements(
      null, $RDFS->range, null
    );
    foreach ($rdfsRangeStmts as $rdfsRangeStmt) {
      $rdfsRangeQueryString = sprintf(
        "CONSTRUCT {?o a %s} WHERE {_:s %s ?o}\n",
        $rdfsRangeStmt->getObject(),
        $rdfsRangeStmt->getSubject()
      );
      $rdfsQueries[] = new LibRDF_Query(
        $rdfsRangeQueryString, null, 'sparql'
      );
    }

    return $rdfsQueries;
  }

}
