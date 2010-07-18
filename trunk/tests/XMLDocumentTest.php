<?php

require_once 'PHPUnit/Framework.php';

require_once dirname(__FILE__) . '/../core/XMLDocument.php';
require_once dirname(__FILE__) . '/../core/XMLElement.php';

use Amba\Core\XMLDocument;
use Amba\Core\XMLElement;

/**
 * 
 */
class XMLDocumentTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }

    private $testXml =
"<root>
    <x id='1'/>
    <z id='0'/>
    <x id='2'>
        <text><![CDATA[woof woof]]></text>
    </x>
    <z id='1'/>
    <x id='3'>
        <y id='1'/>
        <y id='2'/>
    </x>
    <x id='4'/>
    <z id='2'/>
 </root>";

    private $testXmlSubnode = "<x id='3'>
        <y id='1'/>
        <y id='2'/>
    </x>";

    public function testSaveXML()
    {
       $doc1 = new XMLDocument($this->testXml);
       $doc2 = new DOMDocument();
       $doc2->loadXML($this->testXml);
       $this->assertEquals($doc2->saveXML(), $doc1->saveXML());
    }

    public function testSelectSingleNode()
    {
        $doc1 = new XMLDocument($this->testXml);
        $this->assertNull($doc1->getRoot()->selectSingleNode("x[@id='55']"));
        $node = $doc1->getRoot()->selectSingleNode("x[@id='3']");

        $doc2 = new DOMDocument();
        $doc2->loadXML($this->testXmlSubnode);
        $this->assertEquals($doc2->saveXML($doc2->documentElement), $node->saveXML());

        $doc2->loadXML("<x id='1'/>");
        $node2 = $doc1->getRoot()->selectSingleNode("x");
        $this->assertEquals($doc2->saveXML($doc2->documentElement), $node2->saveXML());

        $doc1 = new XMLDocument();
        $this->assertNull($doc1->selectSingleNode("x"));
    }

    public function testSelectNodes()
    {
        $doc1 = new XMLDocument();
        $this->assertEquals(0, count($doc1->selectNodes("x")));

        $doc1 = new XMLDocument($this->testXml);
        
        $nodes = $doc1->getRoot()->selectNodes("x");
        $this->assertEquals(4, count($nodes));
        $nodes = $doc1->getRoot()->selectNodes("//x");
        $this->assertEquals(4, count($nodes));
        $nodes = $doc1->getRoot()->selectNodes("x[@id='3']/y");
        $this->assertEquals(2, count($nodes));

        $this->assertEquals(0, count($doc1->selectNodes("root/x[@id='55']")));
    }

    public function testSiblings()
    {
        $doc1 = new XMLDocument($this->testXml);
        $following = $doc1->getRoot()->followingSibling();
        $this->assertEquals(0, count($following));
        $precending = $doc1->getRoot()->precendingSibling();
        $this->assertEquals(0, count($precending));

        $node = $doc1->selectSingleNode("root/x[@id='2']");

        $precending = $node->precendingSibling();
        $following = $node->followingSibling();
        $this->assertEquals(2, count($precending));
        $this->assertEquals(4, count($following));

        $control = "";
        foreach($precending as $x)
        {
            $control .= "($x->nodeName,$x->id)";
        }
        $this->assertEquals("(x,1)(z,0)", $control);
        $control = "";
        foreach($following as $x)
        {
            $control .= "($x->nodeName,$x->id)";
        }
        $this->assertEquals("(z,1)(x,3)(x,4)(z,2)", $control);

        // x name
        $precending = $node->precendingSibling("x");
        $following = $node->followingSibling("x");
        $this->assertEquals(1, count($precending));
        $this->assertEquals(2, count($following));

        $control = "";
        foreach($precending as $x)
        {
            $control .= "($x->nodeName,$x->id)";
        }
        $this->assertEquals("(x,1)", $control);
        $control = "";
        foreach($following as $x)
        {
            $control .= "($x->nodeName,$x->id)";
        }
        $this->assertEquals("(x,3)(x,4)", $control);

        // y name
        $precending = $node->precendingSibling("z");
        $following = $node->followingSibling("z");
        $this->assertEquals(1, count($precending));
        $this->assertEquals(2, count($following));

        $control = "";
        foreach($precending as $x)
        {
            $control .= "($x->nodeName,$x->id)";
        }
        $this->assertEquals("(z,0)", $control);
        $control = "";
        foreach($following as $x)
        {
            $control .= "($x->nodeName,$x->id)";
        }
        $this->assertEquals("(z,1)(z,2)", $control);
    }

    public function testSetInnerXML()
    {
        $dom = new XMLDocument("<root id='2'><bi/></root>");
        $dom->getRoot()->setInnerXML("<x><y/></x>");
        $dom2 = new XMLDocument("<root id='2'><x><y/></x></root>");
        $this->assertEquals($dom2->saveXML(), $dom->saveXML());
    }
    
    public function testExportNode()
    {
         $dom = new XMLDocument("<root id='2'><x id='3'><y/></x></root>");
         $domC = new XMLDocument("<root id='2'><x id='3'><y/></x></root>");
         $node = $dom->getRoot()->getChildNode();
         $this->assertNotNull($node);
         $dom2 = $node->exportNode();

         $dom2C = new XMLDocument("<x id='3'><y/></x>");
         $this->assertEquals($domC->saveXML(), $dom->saveXML());
         $this->assertEquals($dom2C->saveXML(), $dom2->saveXML());
    }

    public function testAttributes()
    {
        $doc1 = new XMLDocument($this->testXml);
        foreach($doc1->map("//x") as $xNode)
        {
            $xNode->id = $xNode->id + 10;
        }
        $control = "";
        foreach($doc1->map("//x") as $xNode)
        {
            $control .= $xNode->id . ",";
        }
        $this->assertEquals("11,12,13,14,", $control);
        $node = $doc1->selectSingleNode("//x[@id='11']");
        $this->assertEquals("bubu", $node->getAttribute('name', 'bubu'));
        $this->assertEquals("", $node->getAttribute('name'));
        $node->name = "woof";
        $attr = $node->getAttributeNode('name');
        $this->assertEquals(true, $attr instanceof \DOMAttr);
    }

    public function testCopyAttributes()
    {
        $doc1 = new XMLDocument("<root><node id='2' name='name' val='value'/></root>");
        $doc2 = new XMLDocument("<root id='3'><node id='1'/></root>");
        $node = $doc1->selectSingleNode("root/node[@id='2']");
        $nodes = $doc2->selectNodes("root");
        $nodes[0]->copyAttributes($node);
        $doc2->selectSingleNode("//node")->copyAttributes($node, false);
        $doc3 = new XMLDocument('<root id="2" name="name" val="value"><node id="1" name="name" val="value"/></root>');
        $this->assertEquals($doc3->saveXML(), $doc2->saveXML());
    }

    public function testDocCreating()
    {
        $testXmlSubnode = "<x id='3'><y id='1'/><y id='2'/></x>";

        $doc1= new XMLDocument($testXmlSubnode);
        $doc2 = new XMLDocument();
        $doc2->addNode("root")
                ->addNode("x")
                    ->setAttribute("id", "1")
                ->getParent()
                ->addNode("x")
                    ->setAttribute("id", "2")
                    ->addNodeWithCData("data", "text text]]> text")
                        ->setAttribute("name", "3")
                    ->getParent()
                        ->appendXML("<y><z/></y>")
                        ->importNode($doc1->getRoot());
        $doc3 = new XMLDocument('<root><x id="1"/><x id="2"><data name="3"><![CDATA[text text]]]]><![CDATA[> text]]></data><y><z/></y><x id="3"><y id="1"/><y id="2"/></x></x></root>');
        $this->assertEquals($doc3->saveXML(), $doc2->saveXML());
    }

    public function testMap()
    {
        $doc1 = new XMLDocument($this->testXml);
        $con = "";
        $result = $doc1->map("//x", function($node) use (&$con){$con .= "$node->id,"; return $node->id;});
        $this->assertEquals("1,2,3,4,", $con);
        $this->assertEquals("1,2,3,4", join(",", $result));
    }

    public function testGetInnerXML()
    {
        $doc1 = new XMLDocument('<x><y><z/></y></x>');
        $this->assertEquals('<y><z/></y>', $doc1->getRoot()->getInnerXML());
        $doc2 = new XMLDocument('<woof><test><![CDATA[woof]]></test></woof>');

        $this->assertEquals('woof', $doc2->selectSingleNode("/woof/test")->getInnerXML());
        $doc2 = new XMLDocument('<woof><x/><test> <![CDATA[woof]]></test></woof>');

        $this->assertEquals('<x/><test> <![CDATA[woof]]></test>', $doc2->selectSingleNode("/woof")->getInnerXML());
    }

    public function testAddArray()
    {
        $doc = new XMLDocument('<root/>');
        $array = array('vasya' => 'cdata1', 'petya' => '');
        $doc->getRoot()->addArray($array, "Item", "Name");
        $this->assertEqualXMLStructure(
            new XMLDocument(
                '<root>
                    <Item Name="vasya"><![CDATA[cdata1]]></Item>
                    <Item Name="petya"><![CDATA[]]></Item>
                 </root>'), $doc, true);
    }
    
    public function testAddNodeS()
    {
        $doc1 = new XMLDocument('<x><y><z/></y></x>');
        $doc2 = new XMLDocument('<woof><test/></woof>');
        $doc1->getRoot()
            ->getChildNode("y")
            ->getChildNode("z")
            ->addNode("int", $doc2->getRoot());
        $this->assertEqualXMLStructure(
            new XMLDocument('<x><y><z><int><woof><test/></woof></int></z></y></x>'),
            $doc1);

        $doc2->getRoot()
            ->getChildNode('test')
            ->addNode('int', $doc2->createElement("newElem", ""));

        $this->assertEqualXMLStructure(
            new XMLDocument('<woof><test><int><newElem/></int></test></woof>'),
            $doc2);
    }

    public function testTransform()
    {
        $xml =
'<?xml version="1.0" encoding="UTF-8"?>
<Module>
  <Text><![CDATA[text data]]> </Text>
</Module>';

$xslt1 =
'<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="xml" encoding="utf-8" standalone="no" omit-xml-declaration="yes"/>
    <xsl:template match="/">
        <module><xsl:value-of select="Module/Text/text()"/></module>
    </xsl:template>
</xsl:stylesheet>';

        $doc = new XMLDocument($xml);
        $xslt = new XMLDocument($xslt1);
        $this->assertEquals('<module>text data</module>',  trim($doc->transform($xslt)));
    }

    public function testDocumentWithOptions()
    {
        $x = new XMLDocument('<root> <c>    <bubu/>   <t>woof </t>     </c>
        </root>', LIBXML_NOBLANKS | LIBXML_NOXMLDECL);
        $this->assertEquals(
            "<root><c><bubu/><t>woof </t></c></root>",
            trim(str_replace('<?xml version="1.0"?>', '', $x->saveXML())));
    }

    public function testWrapper()
    {
        $doc = new XMLDocument('<aa><bb/><cc/></aa>', LIBXML_NOBLANKS);
        $doc->getRoot()->wrapNode('woof');
        $this->assertEquals('<woof><aa><bb/><cc/></aa></woof>', $doc->getRoot()->saveXML());
        $doc = new XMLDocument('<aa><bb/><cc/></aa>', LIBXML_NOBLANKS);
        $doc->getRoot()->getChildNode('bb')->wrapNode('woof');
        $this->assertEquals('<aa><woof><bb/></woof><cc/></aa>', $doc->getRoot()->saveXML());
    }

    public function testDeleteAllChildren()
    {
        $doc = new XMLDocument('<aa><bb><x/><y/><z/></bb><cc/></aa>', LIBXML_NOBLANKS);
        $doc->selectSingleNode('//bb')->removeAllChildren();
        $this->assertEquals(
            '<aa><bb/><cc/></aa>',
            $doc->getRoot()->saveXML());
    }

    public function testSubtreeWalk()
    {
        $doc = new XMLDocument('<aa><bb><x/><y/><z/></bb><cc/></aa>', LIBXML_NOBLANKS);
        $doc->getRoot()->subtreeWalk(
                function($node)
                {
                    if ($node->nodeName == 'x' || $node->nodeName == 'cc')
                    {
                        $node->setAttribute('checked', 'true');
                    }
                }
            );
        $this->assertEquals(
                self::spawn(new XMLDocument('<aa><bb><x checked="true"/><y/><z/></bb><cc checked="true"/></aa>', LIBXML_NOBLANKS))->saveXML(),
                $doc->saveXML());
    }

    public function testRemoveExceptOne()
    {
        $doc = new XMLDocument(
                '<Gallery>
                    <Image>
                        <Description Culture="ru">RU</Description>
                        <Description Culture="en">EN</Description>
                        <Description Culture="kz">KZ</Description>
                    </Image>
                    <Image>
                        <Description Culture="ru">RU1</Description>
                        <Description Culture="en">EN1</Description>
                        <Description Culture="kz">KZ1</Description>
                    </Image>
                    </Gallery>');
        $doc2 = new XMLDocument('<Gallery/>', LIBXML_NOBLANKS);
        foreach($doc->getRoot()->selectNodes('Image') as $galleryItem)
        {
            $importedNode = $doc2->getRoot()->importNode($galleryItem);
            $cultureNode = self::getNodeByCulture($importedNode, 'Description', 'ru');
            $importedNode->removeExceptOne('Description', $cultureNode);
        }
        $docExpected = new XMLDocument('
            <Gallery>
                <Image>
                    <Description Culture="ru">RU</Description>
                </Image>
                <Image>
                    <Description Culture="ru">RU1</Description>
                </Image>
           </Gallery>', LIBXML_NOBLANKS);
        $doc2 = new XMLDocument($doc2->saveXML(), LIBXML_NOBLANKS);
        $this->assertEquals($docExpected->saveXML(), $doc2->saveXML());
    }

    public static function getNodeByCulture(XMLElement $parent, $nodeName, $cultureName)
    {
        $node = $parent->selectSingleNode($nodeName . "[@Culture='$cultureName']");
        if ($node == null)
        {
            $defaultCulture = App::defaultCulture()->getName();
            $node = $parent->selectSingleNode($nodeName . "[@Culture='$defaultCulture']");
            if ($node == null)
            {
                $node = $parent->selectSingleNode($nodeName . "[not(@Culture)]");
            }
        }
        return $node;
    }

    private static function spawn($x)
    {
        return $x;
    }
}
?>
