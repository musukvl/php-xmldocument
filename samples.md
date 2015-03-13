Samples to demostrate XMLDocument features

# XMLDocument Samples #

## Chains style document creating ##

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

## Navigation (SelectSingleNode & SelectNodes) ##
```
$doc = new XMLDocument(
  '<aa>
    <b id="1"/>
     <cc/>
     <d>
       <b id="2"/>
     </d>
  </aa>');
echo count($doc->selectNodes('//b'));
echo $doc->selectSingleNode('//b[@id="2"]')->saveXML();
```

Result:
```
2
<b id="2"/>
```

## Inner XML Processing ##

```
$doc = new XMLDocument('<aa><b id="1"/></aa>');    
$innerXML = $doc->getRoot()->getInnerXML();
$doc2 = new XMLDocument('<xx/>');
$doc2->getRoot()->setInnerXML($innerXML);
echo $doc2->saveXML();
```

Result:
```
<?xml version="1.0"?>
<xx>
  <b id="1"/>
</xx>
```


## Array serializing ##

```
$doc = new XMLDocument('<colors/>');
$doc->getRoot()->addArray(
        array('black'=>'#000000', 'red'=>'#FF0000', 'green' =>'#00FF00'), 
        'color');
```

Result:
```
<colors>
    <color Name="black"><![CDATA[#000000]]></color>
    <color Name="red"><![CDATA[#FF0000]]></color>
    <color Name="green"><![CDATA[#00FF00]]></color>
    <color Name="blue"><![CDATA[#0000FF]]></color>
    <color Name="white"><![CDATA[#FFFFFF]]></color>
</colors>
```

## Subtree walking ##

```
$doc = new XMLDocument(
'<aa>
   <bb>
     <x/>
   </bb>
   <cc/>
</aa>');
$doc->getRoot()->subtreeWalk(
    function($node)
    {
        if ($node->nodeName == 'x' || $node->nodeName == 'cc')
            $node->setAttribute('checked', 'true');
    }
);
```

Result:
```
<aa>
  <bb>
    <x checked="true"/>
  </bb>
  <cc checked="true"/>
</aa>
```

## Node wrapping ##
```
$doc = new XMLDocument('<aa><bb/><cc/></aa>');
$doc->getRoot()->wrapNode('woof');
```

Result:

```
<woof>
  <aa>
    <bb/>
    <cc/>
  </aa>
</woof> 
```