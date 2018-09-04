<?php

namespace Ricklab\Location\Geometry;

use Ricklab\Location\Location;

class PolygonTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     * @var \Ricklab\Location\Geometry\Polygon
     */
    public $polygon;

    public function setUp()
    {

        Location::$useSpatialExtension = false;
        $this->polygon                 = new Polygon( [ new Point( 2, 3 ), new Point( 2, 4 ), new Point( 3, 4 ) ] );
    }

    public function testConstruction()
    {
        $poly1 = new Polygon( [
            [
                new Point( 2, 3 ),
                new Point( 2, 4 ),
                new Point( 3, 4 ),
                new Point( 2, 3 )
            ]
        ] );
        $this->assertEquals( $this->polygon, $poly1 );

        $poly2 = new Polygon( [
            new LineString( [
                new Point( 2, 3 ),
                new Point( 2, 4 ),
                new Point( 3, 4 ),
                new Point( 2, 3 )
            ] )
        ] );
        $this->assertEquals( $this->polygon, $poly2 );
    }

    public function testLastPointIsTheSameAsFirstPoint()
    {
        $a = $this->polygon;
        $this->assertEquals( $a[0][0]->getLatitude(), $a[0][count( $a ) - 1]->getLatitude() );
        $this->assertEquals( $a[0][0]->getLongitude(), $a[0][count( $a ) - 1]->getLongitude() );
    }

    public function testToArrayReturnsAnArray()
    {
        $this->assertTrue(is_array($this->polygon->toArray()));
    }

    public function testObjectIsAPolygon()
    {

        $this->assertInstanceOf( 'Ricklab\Location\Geometry\Polygon', $this->polygon );
    }

    public function testToString()
    {
        $retval = '((3 2, 4 2, 4 3, 3 2))';
        $this->assertEquals( $retval, (string) $this->polygon );
    }

    public function testToWkt()
    {
        $retVal = $this->polygon->toWkt();
        $this->assertEquals( 'POLYGON((3 2, 4 2, 4 3, 3 2))', $retVal );
    }

    public function tearDown()
    {
        $this->polygon = null;
    }

    public function testJsonSerialize()
    {
        $json = json_encode($this->polygon);
        $this->assertEquals('{"type":"Polygon","coordinates":[[[3,2],[4,2],[4,3],[3,2]]]}', $json);
    }

    public function testBBox()
    {
        $polygon = new Polygon( [
            [
                new Point( 3, 4 ),
                new Point( 2, 3 ),
                new Point( 2, 4 ),
                new Point( 3, 2 )
            ]
        ] );
        $this->assertEquals( '{"type":"Polygon","coordinates":[[[2,3],[4,3],[4,2],[2,2],[2,3]]]}',
            json_encode( $polygon->getBBox() ) );

        $this->assertEquals( '{"type":"Polygon","coordinates":[[[3,3],[4,3],[4,2],[3,2],[3,3]]]}',
            json_encode( $this->polygon->getBBox() ) );
    }

    public function testFromArray()
    {
        $ar = [[[100.0, 0.0], [101.0, 1.0], [102.0, 2.0], [103.0, 3.0]]];

        $polygon = new Polygon($ar);

        $this->assertInstanceOf('Ricklab\Location\Geometry\Polygon', $polygon);
        $this->assertEquals([100.0, 0.0], $polygon[0][0]->toArray());
    }

}
