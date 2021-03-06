<?php
/**
 * @link      https://github.com/weierophinney/PhlyRestfully for the canonical source repository
 * @copyright Copyright (c) 2013 Matthew Weier O'Phinney
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @package   PhlyRestfully
 */

namespace PhlyRestfullyTest;

use PhlyRestfully\Exception;
use PhlyRestfully\HalCollection;
use PhlyRestfully\Link;
use PhlyRestfully\LinkCollection;
use PHPUnit\Framework\TestCase as TestCase;
use stdClass;

class HalCollectionTest extends TestCase
{
    public function invalidCollections()
    {
        return [
            'null'       => [null],
            'true'       => [true],
            'false'      => [false],
            'zero-int'   => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'string'     => ['string'],
            'stdclass'   => [new stdClass],
        ];
    }

    /**
     * @dataProvider invalidCollections
     */
    public function testConstructorRaisesExceptionForNonTraversableCollection($collection)
    {
        $this->expectException(Exception\InvalidCollectionException::class);
        $hal = new HalCollection($collection, 'collection/route', 'item/route');
    }

    public function testPropertiesAreAccessibleFollowingConstruction()
    {
        $hal = new HalCollection([], 'item/route', ['version' => 1], ['query' => 'format=json']);
        $this->assertEquals([], $hal->collection);
        $this->assertEquals('item/route', $hal->resourceRoute);
        $this->assertEquals(['version' => 1], $hal->resourceRouteParams);
        $this->assertEquals(['query' => 'format=json'], $hal->resourceRouteOptions);
    }

    public function testDefaultPageIsOne()
    {
        $hal = new HalCollection([], 'item/route');
        $this->assertEquals(1, $hal->page);
    }

    public function testPageIsMutable()
    {
        $hal = new HalCollection([], 'item/route');
        $hal->setPage(5);
        $this->assertEquals(5, $hal->page);
    }

    public function testDefaultPageSizeIsThirty()
    {
        $hal = new HalCollection([], 'item/route');
        $this->assertEquals(30, $hal->pageSize);
    }

    public function testPageSizeIsMutable()
    {
        $hal = new HalCollection([], 'item/route');
        $hal->setPageSize(3);
        $this->assertEquals(3, $hal->pageSize);
    }

    public function testDefaultCollectionNameIsItems()
    {
        $hal = new HalCollection([], 'item/route');
        $this->assertEquals('items', $hal->collectionName);
    }

    public function testCollectionNameIsMutable()
    {
        $hal = new HalCollection([], 'item/route');
        $hal->setCollectionName('records');
        $this->assertEquals('records', $hal->collectionName);
    }

    public function testDefaultAttributesAreEmpty()
    {
        $hal = new HalCollection([], 'item/route');
        $this->assertEquals([], $hal->attributes);
    }

    public function testAttributesAreMutable()
    {
        $hal = new HalCollection([], 'item/route');
        $attributes = [
            'count' => 1376,
            'order' => 'desc',
        ];
        $hal->setAttributes($attributes);
        $this->assertEquals($attributes, $hal->attributes);
    }

    public function testComposesLinkCollectionByDefault()
    {
        $hal = new HalCollection([], 'item/route');
        $this->assertInstanceOf(LinkCollection::class, $hal->getLinks());
    }

    public function testLinkCollectionMayBeInjected()
    {
        $hal   = new HalCollection([], 'item/route');
        $links = new LinkCollection();
        $hal->setLinks($links);
        $this->assertSame($links, $hal->getLinks());
    }

    public function testAllowsSettingAdditionalResourceLinks()
    {
        $links = new LinkCollection();
        $links->add(new Link('describedby'));
        $links->add(new Link('orders'));
        $hal   = new HalCollection([], 'item/route');
        $hal->setResourceLinks($links);
        $this->assertSame($links, $hal->getResourceLinks());
        $this->assertSame($links, $hal->resourceLinks);
    }
}
