Standard DOMDocument has rich functionality, but it is not handy enough.
XMLDocument is extension of standard PHP DOMDocument and DOMElement.

This project contains two classes for easy create, navigate and process XML documents.

Usage sample:
```
$doc = new XMLDocument("<Users/>");
$doc->getRoot()->addNode('User')
        ->setAttribute('Active', 'true')
        ->addNodeWithCData('Name', 'John')
        ->getParent()->getParent()
     ->addNode('User')
        ->setInnerXML('<Name><![CDATA[Peter]]></Name>');
echo $doc->saveXML();
```

Result:

```
<Users>
  <User Active="true">
    <Name><![CDATA[John]]></Name>
  </User>
  <User>
    <Name><![CDATA[Peter]]></Name>
  </User>
</Users>  
```


See [Samples](http://code.google.com/p/php-xmldocument/wiki/samples) for more details.