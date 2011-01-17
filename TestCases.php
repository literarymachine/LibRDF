<?php
// $Id: TestCases.php 161 2006-06-15 20:30:03Z das-svn $
// usage: phpunit TestCases.php

require_once "PHPUnit2/Framework/TestCase.php";
require_once "PHPUnit2/Framework/TestSuite.php";

// test the LibRDF_Error class
require_once "LibRDF/Error.php";
class ErrorTest extends PHPUnit2_Framework_TestCase
{
    public function testConstructor() {
        $error = new LibRDF_Error();
        $this->assertType("LibRDF_Error", $error);

        $error = new LibRDF_Error("Message");
        $this->assertType("LibRDF_Error", $error);
    }

    public function testMessage() {
        $error = new LibRDF_Error("test message");
        $this->assertEquals("test message", $error->getMessage());
    }

    public function testIsThrowable() {
        $exp = new LibRDF_Error();
        try {
            throw $exp;
            $this->fail("Unable to throw exception");
        } catch (LibRDF_Error $e) {
            $this->assertTrue(true);
        }
    }
}

// test the LibRDF_URI class
require_once "LibRDF/URI.php";
class URITest extends PHPUnit2_Framework_TestCase
{
    public function setUp()
    {
        $this->test_string = "http://www.example.com/";
        $this->test_string2 = "http://www.example.org/";
    }

    public function testConstructor()
    {
        $uri = new LibRDF_URI($this->test_string);
        $this->assertType("LibRDF_URI", $uri);
    }

    public function testToString()
    {
        $uri = new LibRDF_URI($this->test_string);
        $this->assertEquals($this->test_string, $uri->__toString());
    }

    public function testClone()
    {
        $uri1 = new LibRDF_URI($this->test_string);
        $uri2 = clone $uri1;
        $this->assertType("LibRDF_URI", $uri2);
        $this->assertNotSame($uri1, $uri2);
        $this->assertNotSame($uri1->getURI(), $uri2->getURI());
        $this->assertEquals($this->test_string, $uri2->__toString());
    }

    public function testGetURI()
    {
        $uri = new LibRDF_URI($this->test_string);
        $this->assertType("resource", $uri->getURI());
    }

    public function testIsEqual()
    {
        $uri1 = new LibRDF_URI($this->test_string);
        $uri2 = new LibRDF_URI($this->test_string);
        $uri3 = new LibRDF_URI($this->test_string2);

        $this->assertTrue($uri1->isEqual($uri2));
        $this->assertFalse($uri1->isEqual($uri3));
    }
}

// test LibRDF_Node and subclasses
require_once "LibRDF/Node.php";
class NodeTest extends PHPUnit2_Framework_TestCase {
    public function setUp()
    {
        $this->testURI = "http://www.example.com/";
        $this->testURI2 = "http://www.example.org/";
        $this->testNodeID = "abcd";
        $this->testNodeID2 = "efgh";
        $this->xmlDatatype = "http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral";
        $this->testType = "http://www.example.com/types/#testtype";
        $this->testType2 = "http://www.example.com/types/#testtype2";
        $this->testLang = "en-us";
        $this->testLang2 = "fr";
        $this->testLiteral = "This is the first test literal";
        $this->testLiteral2 = "This is the second test literal";

        // create an xml test
        $xmlstr = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE document [
    <!ELEMENT document (nodelist+)>
    <!ELEMENT nodelist (child*)>
    <!ATTLIST nodelist id ID #REQUIRED>
    <!ELEMENT child (#PCDATA)>
]>
<document>
    <nodelist id="list1"><child/></nodelist>
</document>
EOT;

        $document = new DOMDocument();
        $document->loadXML($xmlstr);
        $document->validate();
        $this->xmllist = $document->getElementById("list1")->childNodes;
    }

    public function testURIConstruct()
    {
        $uri = new LibRDF_URINode($this->testURI);
        $this->assertType("LibRDF_URINode", $uri);
    }

    public function testURIAltConstruct()
    {
        $uri = new LibRDF_URINode($this->testURI);
        $uri2 = new LibRDF_URINode($uri->getNode());
        $this->assertType("LibRDF_URINode", $uri2);
    }

    public function testURIConstructFail()
    {
        try {
            $uri = new LibRDF_URINode(47);
            $this->fail("Constructor failed to throw exception for invalid argument");
        } catch (LibRDF_Error $e) {
            $this->assertTrue(true);
        }

        try {
            $blank_node = new LibRDF_BlankNode();
            $uri = new LibRDF_URINode($blank_node->getNode());
            $this->fail("Constructor failed to throw exception for invalid argument");
        } catch (LibRDF_Error $e) {
            $this->assertTrue(true);
        }
    }

    public function testURIGetNode()
    {
        $uri = new LibRDF_URINode($this->testURI);
        $this->assertType("resource", $uri->getNode());
    }

    public function testURIToString()
    {
        $uri = new LibRDF_URINode($this->testURI);
        $this->assertEquals($uri->__toString(), 
            "[" . $this->testURI . "]");
    }

    public function testURIClone()
    {
        $uri1 = new LibRDF_URINode($this->testURI);
        $uri2 = clone $uri1;
        $this->assertType("LibRDF_URINode", $uri2);
        $this->assertNotSame($uri1, $uri2);
        $this->assertTrue($uri1->isEqual($uri2));
        $this->assertTrue($uri2->isEqual($uri1));
    }

    public function testURIIsEqual()
    {
        $uri1 = new LibRDF_URINode($this->testURI);
        $uri2 = new LibRDF_URINode($this->testURI);
        $uri3 = new LibRDF_URINode($this->testURI2);

        $this->assertTrue($uri1->isEqual($uri2));
        $this->assertFalse($uri1->isEqual($uri3));
    }

    public function testBlankConstruct()
    {
        $blank1 = new LibRDF_BlankNode();
        $blank2 = new LibRDF_BlankNode($this->testNodeID);

        $this->assertType("LibRDF_BlankNode", $blank1);
        $this->assertType("LibRDF_BlankNode", $blank2);
    }

    public function testBlankAltConstruct()
    {
        $blank1 = new LibRDF_BlankNode();
        $blank2 = new LibRDF_BlankNode($blank1->getNode());
        $this->assertType("LibRDF_BlankNode", $blank2);
    }

    public function testBlankConstructFail()
    {
        try {
            // only a bad node is failure: everything else is converted
            // to a string
            $literal_node = new LibRDF_LiteralNode("value");
            $blank = new LibRDF_BlankNode($literal_node->getNode());
            $this->fail("Constructor failed to throw exception for bad argument");
        } catch (LibRDF_Error $e) {
            $this->assertTrue(true);
        }
    }

    public function testBlankToString()
    {
        $blank1 = new LibRDF_BlankNode();
        $blank2 = new LibRDF_BlankNode($this->testNodeID);

        $this->assertType("string", $blank1->__toString());
        $this->assertEquals($blank2->__toString(), 
            "(" . $this->testNodeID . ")");
    }

    public function testBlankClone()
    {
        $blank1 = new LibRDF_BlankNode($this->testNodeID);
        $blank2 = clone $blank1;
        $this->assertType("LibRDF_BlankNode", $blank2);
        $this->assertNotSame($blank1, $blank2);
        $this->assertEquals($blank1->__toString(), $blank2->__toString());
    }

    // just testing whether a node is equal to itself
    // librdf actually returns true for any two blank nodes with the same
    // nodeID, but this is somewhat ambiguous, since they should only 
    // be equal if they are from the same document, and therefore the same
    // node
    public function testBlankIsEqual()
    {
        $blank1 = new LibRDF_BlankNode($this->testNodeID);
        $blank2 = new LibRDF_BlankNode($this->testNodeID2);

        $this->assertTrue($blank1->isEqual($blank1));
        $this->assertFalse($blank1->isEqual($blank2));
    }

    public function testLiteralConstructPlain()
    {
        $literal = new LibRDF_LiteralNode($this->testLiteral);
        $literalLang = new LibRDF_LiteralNode($this->testLiteral, NULL,
            $this->testLang);
        $this->assertType("LibRDF_LiteralNode", $literal);
        $this->assertType("LibRDF_LiteralNode", $literalLang);
    }

    public function testLiteralConstructTyped()
    {
        $literal = new LibRDF_LiteralNode($this->testLiteral, $this->testType);
        $this->assertType("LibRDF_LiteralNode", $literal);

        try {
            $literal = new LibRDF_LiteralNode($this->testLiteral,
                $this->testType, $this->testLang);
            $this->fail("Constructor failed to throw exception for datatype/lang combination");
        } catch (LibRDF_Error $e) {
            $this->assertTrue(true);
        }

        $literal = new LibRDF_LiteralNode($this->xmllist);
        $this->assertType("LibRDF_LiteralNode", $literal);

        try {
            $literal = new LibRDF_LiteralNode($this->xmllist, $this->testType);
            $this->fail("Constructor failed to throw exception for datatype/xml combination");
        } catch (LibRDF_Error $e) {
            $this->assertTrue(true);
        }
    }

    public function testLiteralAltConstruct()
    {
        $literal = new LibRDF_LiteralNode($this->testLiteral, $this->testType);
        $literal1 = new LibRDF_LiteralNode($literal->getNode());
        $this->assertType("LibRDF_LiteralNode", $literal1);
    }

    public function testLiteralConstructFail()
    {
        // no arguments
        try {
            $literal = new LibRDF_LiteralNode();
            $this->fail("Constructor failed to throw exception for invalid arguments");
        } catch (LibRDF_Error $e) {
            $this->assertTrue(true);
        }

        // one argument, wrong resource type
        try {
            $blank_node = new LibRDF_BlankNode();
            $literal = new LibRDF_LiteralNode($blank_node->getNode());
            $this->fail("Constructor failed to throw exception for invalid arguments");
        } catch (LibRDF_Error $e) {
            $this->assertTrue(true);
        }

        // more than three arguments
        try {
            $literal = new LibRDF_LiteralNode("value",
                "http://www.example.org/",
                NULL, NULL);
            $this->fail("Constructor failed to throw exception for invalid arguments");
        } catch (LibRDF_Error $e) {
            $this->assertTrue(true);
        }
    }

    public function testLiteralToString()
    {
        $literal = new LibRDF_LiteralNode($this->testLiteral);
        $this->assertEquals($literal->__toString(), $this->testLiteral);
    }

    public function testLiteralClone()
    {
        $literal1 = new LibRDF_LiteralNode($this->testLiteral);
        $literal2 = clone $literal1;
        $this->assertType("LibRDF_LiteralNode", $literal2);
        $this->assertNotSame($literal1, $literal2);
        $this->assertTrue($literal1->isEqual($literal2));
    }

    public function testLiteralIsEqual()
    {
        $literal1 = new LibRDF_LiteralNode($this->testLiteral);
        $literal1_1 = new LibRDF_LiteralNode($this->testLiteral);
        $literal2 = new LibRDF_LiteralNode($this->testLiteral, $this->testType);
        $literal2_2 = new LibRDF_LiteralNode($this->testLiteral, $this->testType);
        $literal3 = new LibRDF_LiteralNode($this->testLiteral, $this->testType2);
        $literal4 = new LibRDF_LiteralNode($this->testLiteral2);
        $literal5 = new LibRDF_LiteralNode($this->testLiteral, NULL, $this->testLang);
        $literal5_2 = new LibRDF_LiteralNode($this->testLiteral, NULL, $this->testLang);
        $literal6 = new LibRDF_LiteralNode($this->testLiteral, NULL, $this->testLang2);

        $this->assertTrue($literal1->isEqual($literal1_1));
        $this->assertFalse($literal1->isEqual($literal2));
        $this->assertFalse($literal1->isEqual($literal4));
        $this->assertFalse($literal1->isEqual($literal5));
        $this->assertTrue($literal2->isEqual($literal2_2));
        $this->assertFalse($literal2->isEqual($literal3));
        $this->assertFalse($literal5->isEqual($literal6));
        $this->assertTrue($literal5->isEqual($literal5_2));
    }

    public function testLiteralGetDataType()
    {
        $literal = new LibRDF_LiteralNode($this->testLiteral, $this->testType);
        $literal1 = new LibRDF_LiteralNode($this->testLiteral);
        $this->assertEquals($literal->getDataType(), $this->testType);
        $this->assertNull($literal1->getDataType());
    }

    public function testLiteralGetLanguage()
    {
        $literal = new LibRDF_LiteralNode($this->testLiteral, NULL, $this->testLang);
        $literal1 = new LibRDF_LiteralNode($this->testLiteral);
        $this->assertEquals($literal->getLanguage(), $this->testLang);
        $this->assertNull($literal1->getLanguage());
    }
}

// test the LibRDF_Statement class
require_once "LibRDF/Statement.php";
class StatementTest extends PHPUnit2_Framework_TestCase
{
    public function setUp()
    {
        $this->sourceURI = "http://www.example.com/#source";
        $this->sourceURI1 = "http://www.example.com/#source1";
        $this->source = new LibRDF_URINode($this->sourceURI);
        $this->source1 = new LibRDF_URINode($this->sourceURI1);
        $this->predicateURI = "http://www.example.com/#predicate";
        $this->predicate = new LibRDF_URINode($this->predicateURI);
        $this->targetValue = "Value";
        $this->target = new LibRDF_LiteralNode($this->targetValue);
        $this->statement = new LibRDF_Statement($this->source, $this->predicate,
            $this->target);
    }

    public function testConstructor()
    {
        $statement = new LibRDF_Statement($this->source, $this->predicate,
            $this->target);
        $this->assertType("LibRDF_Statement", $statement);
    }

    public function testAltConstructor()
    {
        $librdf_statement = librdf_new_statement_from_nodes(librdf_php_get_world(),
            librdf_new_node_from_node($this->source->getNode()),
            librdf_new_node_from_node($this->predicate->getNode()),
            librdf_new_node_from_node($this->target->getNode()));
        $statement = new LibRDF_Statement($librdf_statement);
        $this->assertType("LibRDF_Statement", $statement);
    }

    public function testConstructorFail()
    {
        // no arguments
        try {
            $statement = new LibRDF_Statement();
            $this->fail("Constructor failed to throw exception for invalid arguments");
        } catch (LibRDF_Error $e) {
            $this->assertTrue(true);
        }

        // one argument, not a resource
        try {
            $statement = new LibRDF_Statement("String");
            $this->fail("Constructor failed to throw exception for invalid arguments");
        } catch (LibRDF_Error $e) {
            $this->assertTrue(true);
        }

        // two arguments
        try {
            $statement = new LibRDF_Statement($this->source, $this->predicate);
            $this->fail("Constructor failed to throw exception for invalid arguments");
        } catch (LibRDF_Error $e) {
            $this->assertTrue(true);
        }

        // three arguments, source not a node
        try {
            $statement = new LibRDF_Statement($this->source->__toString(),
                $this->predicate,
                $this->target);
            $this->fail("Constructor failed to throw exception for invalid arguments");
        } catch (LibRDF_Error $e) {
            $this->assertTrue(true);
        }

        // too many arguments
        try {
            $statement = new LibRDF_Statement($this->source,
                $this->predicate,
                $this->target,
                $this->source);
            $this->fail("Constructor failed to throw exception for invalid arguments");
        } catch (LibRDF_Error $e) {
            $this->assertTrue(true);
        }
    }

    public function testToString()
    {
        $this->assertEquals($this->statement->__toString(),
            "{" . $this->source->__toString() . ", " .
            $this->predicate->__toString() . ", \"" .
            $this->target->__toString() . "\"}");
    }

    public function testClone()
    {
        $statement2 = clone $this->statement;
        $this->assertType("LibRDF_Statement", $statement2);
        $this->assertNotSame($statement2, $this->statement);
        $this->assertTrue($this->statement->isEqual($statement2));
        $this->assertTrue($statement2->isEqual($this->statement));
    }

    public function testGetStatement()
    {
        $this->assertType("resource", $this->statement->getStatement());
    }

    public function testGetSubject()
    {
        $subject = $this->statement->getSubject();
        $this->assertType("LibRDF_Node", $subject);
        $this->assertTrue($subject->isEqual($this->source));
        $this->assertEquals($subject->__toString(), 
            "[" . $this->sourceURI . "]");
    }

    public function testGetPredicate()
    {
        $predicate = $this->statement->getPredicate();
        $this->assertType("LibRDF_Node", $predicate);
        $this->assertTrue($predicate->isEqual($this->predicate));
        $this->assertEquals($predicate->__toString(),
            "[" . $this->predicateURI . "]");
    }

    public function testGetObject()
    {
        $object = $this->statement->getObject();
        $this->assertType("LibRDF_Node", $object);
        $this->assertTrue($object->isEqual($this->target));
        $this->assertEquals($object->__toString(), $this->targetValue);
    }

    public function testIsEqual()
    {
        $statement1 = $this->statement;
        $statement2 = new LibRDF_Statement($this->source, $this->predicate,
            $this->target);
        $statement3 = new LibRDF_Statement($this->source1, $this->predicate,
            $this->target);

        $this->assertTrue($statement1->isEqual($statement2));
        $this->assertFalse($statement1->isEqual($statement3));
    }

}

// test the LibRDF_Storage class
require_once "LibRDF/Storage.php";
class StorageTest extends PHPUnit2_Framework_TestCase
{
    public function setUp()
    {
        $this->storage = new LibRDF_Storage();
    }

    public function testConstructor()
    {
        $this->assertType("LibRDF_Storage", $this->storage);
    }

    // most storage backends don't support cloning, skipping that test

    public function testGetStorage()
    {
        $this->assertType("resource", $this->storage->getStorage());
    }
}

// test the LibRDF_Parser class
require_once "LibRDF/Parser.php";
class ParserTest extends PHPUnit2_Framework_TestCase
{
    public function setUp()
    {
        $this->parser = new LibRDF_Parser();
        $this->testXMLFile = "test.rdf";
        $this->testXML = file_get_contents($this->testXMLFile);
    }

    public function testConstructor()
    {
        $this->assertType("LibRDF_Parser", $this->parser);
    }

    public function testGetParser()
    {
        $this->assertType("resource", $this->parser->getParser());
    }

    public function testParseString()
    {
        // the file contains 13 statements, make sure all are parsed
        $count = 0;
        foreach ($this->parser->parseString($this->testXML) as $statement) {
            $this->assertType("LibRDF_Statement", $statement);
            $count++;
        }
        $this->assertEquals($count, 13);

        $count = 0;
        foreach ($this->parser->parseString($this->testXML,
                "http://www.example.org/#") as $statement) {
            $this->assertType("LibRDF_Statement", $statement);
            $count++;
        }
        $this->assertEquals($count, 13);
    }

    // I can't get the file URIs to work, but I don't know if that's because
    // I'm choosing the wrong parser or what.  It works fine with http, and
    // I don't want for an HTTP request every time I run the tests
    // public function testParseURI()
    // {
    //     $count = 0;
    //     $testURI = "http://www.w3.org/1999/02/22-rdf-syntax-ns";
    //     foreach ($this->parser->parseURI($testURI) as $statement) {
    //         $count++;
    //         $this->assertType("LibRDF_Statement", $statement);
    //     }
    //     echo "Count: $count\n";
    //     $this->assertNotEquals($count, 0);
    // }
}

// test the LibRDF_Serializer class
require_once "LibRDF/Serializer.php";
class SerializerTest extends PHPUnit2_Framework_TestCase
{
    public function setUp()
    {
        $this->serializer = new LibRDF_Serializer("rdfxml");
    }

    public function testConstructor()
    {
        $this->assertType("LibRDF_Serializer", $this->serializer);
    }

    public function testGetSerializer()
    {
        $this->assertType("resource", $this->serializer->getSerializer());
    }

    public function testSetNamespace()
    {
        // just make sure it doesn't throw an exception
        $this->serializer->setNamespace("http://www.example.com/#",
            "ex");
        $this->assertTrue(true);
    }
}

// test the LibRDF_Model class
require_once "LibRDF/Model.php";
class ModelTest extends PHPUnit2_Framework_TestCase
{
    public function setUp()
    {
        $this->storage = new LibRDF_Storage();

        $this->sourceURI1 = "http://www.example.com/sources/#s1";
        $this->sourceURI2 = "http://www.example.com/sources/#s2";
        $this->predURI1 = "http://www.example.com/predicates/#p1";
        $this->predURI2 = "http://www.example.com/predicates/#p2";
        $this->targetValue1 = "Literal value 1";
        $this->targetValue2 = "Literal value 2";

        $this->sourceNode1 = new LibRDF_URINode($this->sourceURI1);
        $this->sourceNode2 = new LibRDF_URINode($this->sourceURI2);
        $this->predNode1 = new LibRDF_URINode($this->predURI1);
        $this->predNode2 = new LibRDF_URINode($this->predURI2);
        $this->targetNode1 = new LibRDF_LiteralNode($this->targetValue1);
        $this->targetNode2 = new LibRDF_LiteralNode($this->targetValue2);

        $this->statement1 = new LibRDF_Statement($this->sourceNode1,
            $this->predNode1, $this->targetNode1);
        $this->statement2 = new LibRDF_Statement($this->sourceNode1,
            $this->predNode2, $this->targetNode1);
        $this->statement3 = new LibRDF_Statement($this->sourceNode1,
            $this->predNode2, $this->targetNode2);
        $this->statement4 = new LibRDF_Statement($this->sourceNode2,
            $this->predNode2, $this->targetNode2);

        $this->model = new LibRDF_Model($this->storage);
        $this->model->addStatement($this->statement1);
        $this->model->addStatement($this->statement3);
        $this->model->addStatement($this->statement4);

        // test.rdf is a copy of rmannoy's install.rdf file
        // store the filename and load it as a string
        $this->testXMLFile = "test.rdf";
        $this->testXML = file_get_contents($this->testXMLFile);

        $this->parser = new LibRDF_Parser("rdfxml");
        $this->serializer = new LibRDF_Serializer("rdfxml");
    }

    public function testConstructor()
    {
        $model = new LibRDF_Model(new LibRDF_Storage());
        $this->assertType("LibRDF_Model", $model);
    }

    public function testToString()
    {
        // this isn't really meant to be used except as a convenience
        // function, so just check that it spits out a string.  The
        // serializer functions are what really matter
        $this->assertType("string", $this->model->__toString());
    }

    public function testGetModel()
    {
        $this->assertType("resource", $this->model->getModel());
    }

    // clone isn't supported for memory storage, and I don't feel like
    // using a different backend

    public function testSize()
    {
        $this->assertEquals($this->model->size(), 3);
    }

    public function testAddRemove()
    {
        // ensure statement2 isn't in the model
        $count = 0;
        foreach ($this->model->findStatements($this->statement2) as $statement) {
            $count++;
        }
        $this->assertEquals($count, 0);

        // add the statement and make sure it shows up in find_statement
        $count = 0;
        $this->model->addStatement($this->statement2);
        foreach ($this->model->findStatements($this->statement2) as $statement) {
            $count++;
        }
        $this->assertEquals($count, 1);

        // remove it and make sure it's gone
        $count = 0;
        $this->model->removeStatement($this->statement2);
        foreach ($this->model->findStatements($this->statement2) as $statement) {
            $count++;
        }
        $this->assertEquals($count, 0);
    }

    public function testGetSource()
    {
        $source = $this->model->getSource($this->predNode1, $this->targetNode1);
        $this->assertTrue($this->sourceNode1->isEqual($source));

        try {
            $this->model->getSource($this->predNode1, $this->targetNode2);
            $this->fail("Failed to throw exception for no statement");
        } catch (LibRDF_LookupError $e) {
            $this->assertTrue(true);
        }
    }

    public function testGetArc()
    {
        $arc = $this->model->getArc($this->sourceNode1, $this->targetNode1);
        $this->assertTrue($this->predNode1->isEqual($arc));

        try {
            $this->model->getArc($this->sourceNode2, $this->targetNode1);
            $this->fail("Failed to throw exception for no statement");
        } catch (LibRDF_LookupError $e) {
            $this->assertTrue(true);
        }
    }

    public function testGetTarget()
    {
        $target = $this->model->getTarget($this->sourceNode1, $this->predNode1);
        $this->assertTrue($this->targetNode1->isEqual($target));

        try {
            $this->model->getTarget($this->sourceNode2, $this->predNode1);
            $this->fail("Failed to throw exception for no statement");
        } catch (LibRDF_LookupError $e) {
            $this->assertTrue(true);
        }
    }

    public function testHasStatement() {
        $this->assertTrue($this->model->hasStatement(
            new LibRDF_Statement($this->sourceNode1, $this->predNode1, $this->targetNode1)));
        $this->assertFalse($this->model->hasStatement(
            new LibRDF_Statement($this->sourceNode2, $this->predNode1, $this->targetNode2)));
    }


    public function testFindStatements()
    {
        // two statements with sourceNode1 as subject
        $count = 0;
        foreach ($this->model->findStatements($this->sourceNode1, NULL, NULL)
            as $statement) {
            $this->assertType("LibRDF_Statement", $statement);
            $count++;
        }
        $this->assertEquals($count, 2);

        // two statements with predNode2 as predicate
        $count = 0;
        foreach ($this->model->findStatements(NULL, $this->predNode2, NULL)
            as $statement) {
            $this->assertType("LibRDF_Statement", $statement);
            $count++;
        }
        $this->assertEquals($count, 2);

        // one statement with targetNode1 as target
        $count = 0;
        foreach ($this->model->findStatements(NULL, NULL, $this->targetNode1)
            as $statement) {
            $this->assertType("LibRDF_Statement", $statement);
            $count++;
        }
        $this->assertEquals($count, 1);
    }

    public function testIterator()
    {
        // just make sure that three statements pop out
        $count = 0;
        foreach ($this->model as $statement) {
            $this->assertType("LibRDF_Statement", $statement);
            $count++;
        }
        $this->assertEquals($count, 3);
    }

    public function testLoadStatementsFromString()
    {
        $this->model->loadStatementsFromString($this->parser, $this->testXML);
        $count = 0;
        foreach ($this->model as $statement) {
            $this->assertType("LibRDF_Statement", $statement);
            $count++;
        }
        // 3 initially plus 13 from the file
        $this->assertEquals($count, 16);
    }

    // disabling to get rid of the network traffic
    // public function testLoadStatementsFromURI()
    // {
    //     $testURI = "http://www.w3.org/1999/02/22-rdf-syntax-ns";
    //     $count = 0;
    //     $this->model->loadStatementsFromURI($this->parser, $testURI);
    //     foreach ($this->model as $statement) {
    //         $count++;
    //         $this->assertType("LibRDF_Statement", $statement);
    //     }
    //     $this->assertNotEquals($count, 0);
    // }

    public function testSerialize()
    {
        // just make sure it does something; other tests can make
        // sure it does the right thing
        $this->assertType("string", $this->model->serializeStatements($this->serializer));
    }

    public function testSerializeToFile()
    {
        $tempfile = tempnam(".", "serializer");
        $this->model->serializeStatementsToFile($this->serializer,
            $tempfile);
        $stat = stat($tempfile);
        unlink($tempfile);
        $this->assertNotEquals($stat["size"], 0);
    }
}

// test the LibRDF_Query and LibRDF_QueryResults classes
require_once "LibRDF/Query.php";
require_once "LibRDF/QueryResults.php";
class QueryTest extends PHPUnit2_Framework_TestCase
{
    public function setUp()
    {
        $this->rdqlQuery = new LibRDF_Query("SELECT ?a, ?c WHERE (?a, <http://www.example.com/predicates/#p2>, ?c)");

        // copied from ModelTest because I need a model
        $this->storage = new LibRDF_Storage();

        $this->sourceURI1 = "http://www.example.com/sources/#s1";
        $this->sourceURI2 = "http://www.example.com/sources/#s2";
        $this->predURI1 = "http://www.example.com/predicates/#p1";
        $this->predURI2 = "http://www.example.com/predicates/#p2";
        $this->targetValue1 = "Literal value 1";
        $this->targetValue2 = "Literal value 2";

        $this->sourceNode1 = new LibRDF_URINode($this->sourceURI1);
        $this->sourceNode2 = new LibRDF_URINode($this->sourceURI2);
        $this->predNode1 = new LibRDF_URINode($this->predURI1);
        $this->predNode2 = new LibRDF_URINode($this->predURI2);
        $this->targetNode1 = new LibRDF_LiteralNode($this->targetValue1);
        $this->targetNode2 = new LibRDF_LiteralNode($this->targetValue2);

        $this->statement1 = new LibRDF_Statement($this->sourceNode1,
            $this->predNode1, $this->targetNode1);
        $this->statement2 = new LibRDF_Statement($this->sourceNode1,
            $this->predNode2, $this->targetNode1);
        $this->statement3 = new LibRDF_Statement($this->sourceNode1,
            $this->predNode2, $this->targetNode2);
        $this->statement4 = new LibRDF_Statement($this->sourceNode2,
            $this->predNode2, $this->targetNode2);

        $this->model = new LibRDF_Model($this->storage);
        $this->model->addStatement($this->statement1);
        $this->model->addStatement($this->statement3);
        $this->model->addStatement($this->statement4);
    }

    public function testConstructor()
    {
        $this->assertType("LibRDF_Query", $this->rdqlQuery);
    }

    // clone not implemented and I don't care
    //public function testClone()
    // {
    //     $query = clone $this->rdqlQuery;
    //     $this->assertType("LibRDF_Query", $query);
    //     $this->assertNotSame($this->rdqlQuery, $query);
    // }
    
    public function testExecute()
    {
        $result1 = $this->rdqlQuery->execute($this->model);

        $this->assertType("LibRDF_QueryResults", $result1);
    }

    public function testBindings()
    {
        $count = 0;
        foreach ($this->rdqlQuery->execute($this->model) as $binding) {
            $this->assertType("LibRDF_URINode", $binding["a"]);
            $this->assertType("LibRDF_LiteralNode", $binding["c"]);
            if ($count == 0) {
                $this->assertTrue($binding["a"]->isEqual($this->sourceNode1));
                $this->assertTrue($binding["c"]->isEqual($this->targetNode2));
            } elseif ($count == 1) {
                $this->assertTrue($binding["a"]->isEqual($this->sourceNode2));
                $this->assertTrue($binding["c"]->isEqual($this->targetNode2));
            }
            $count++;
        }
        $this->assertEquals($count, 2);
    }

    public function testBoolean()
    {
        $trueQuery = new LibRDF_Query("ASK WHERE { <http://www.example.com/sources/#s1> <http://www.example.com/predicates/#p1> ?x }", NULL, "sparql");
        $this->assertTrue($trueQuery->execute($this->model)->getValue());
        $falseQuery = new LibRDF_Query("ASK WHERE { <http://www.example.com/sources/#s1> <http://www.example.com/predicates/#p3> ?x }", NULL, "sparql");
        $this->assertFalse($falseQuery->execute($this->model)->getValue());

        $count = 0;
        foreach ($trueQuery->execute($this->model) as $bool) {
            $this->assertType("boolean", $bool);
            $count++;
        }
        $this->assertEquals($count, 1);
    }

    public function testGraph()
    {
        //$graphQuery = new LibRDF_Query("DESCRIBE <http://www.example.com/>", NULL, "sparql");
        $graphQuery = new LibRDF_Query("CONSTRUCT { <http://www.example.com/sources/#s1> <http://www.example.com/predicates/#p3> ?name } WHERE { ?x <http://www.example.com/predicates/#p2> ?name }", NULL, "sparql");
        $count = 0;
        foreach ($graphQuery->execute($this->model) as $statement) {
            $this->assertType("LibRDF_Statement", $statement);
            $this->assertEquals($statement->getPredicate()->__toString(), "[http://www.example.com/predicates/#p3]");
            $count++;
        }
        $this->assertEquals($count, 2);
    }
}

class TestCases
{
    public static function suite()
    {
        $suite = new PHPUnit2_Framework_TestSuite();
        $suite->setName("LibRDF tests");
        $suite->addTest(new PHPUnit2_Framework_TestSuite("ErrorTest"));
        $suite->addTest(new PHPUnit2_Framework_TestSuite("URITest"));
        $suite->addTest(new PHPUnit2_Framework_TestSuite("NodeTest"));
        $suite->addTest(new PHPUnit2_Framework_TestSuite("StatementTest"));
        $suite->addTest(new PHPUnit2_Framework_TestSuite("StorageTest"));
        $suite->addTest(new PHPUnit2_Framework_TestSuite("ParserTest"));
        $suite->addTest(new PHPUnit2_Framework_TestSuite("SerializerTest"));
        $suite->addTest(new PHPUnit2_Framework_TestSuite("ModelTest"));
        $suite->addTest(new PHPUnit2_Framework_TestSuite("QueryTest"));
        return $suite;
    }
}

?>
