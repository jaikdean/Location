<?php

declare(strict_types=1);

namespace Ricklab\Location\Geometry;

use PHPUnit\Framework\TestCase;
use Ricklab\Location\Location;

class MultiPointTest extends TestCase
{
    public function testGeoJson(): void
    {
        $geojson = '{ "type": "MultiPoint",
    "coordinates": [ [100.0, 0.0], [101.0, 1.0] ]
    }';

        $multipoint = Location::fromGeoJson($geojson);

        $this->assertInstanceOf(MultiPoint::class, $multipoint);

        $this->assertEquals([100.0, 0.0], $multipoint->getGeometries()[0]->toArray());
        $this->assertEquals([101.0, 1.0], $multipoint->getGeometries()[1]->toArray());

        $geojson = \json_encode(\json_decode($geojson));

        $this->assertEquals($geojson, \json_encode($multipoint));
    }

    public function testWkt(): void
    {
        $wktv1 = 'MULTIPOINT ((10 40), (40 30), (20 20), (30 10))';
        $wktv2 = 'MULTIPOINT(10 40, 40 30, 20 20, 30 10)';

        $mp1 = Location::fromWkt($wktv1);
        $mp2 = Location::fromWkt($wktv2);

        $this->assertInstanceOf(MultiPoint::class, $mp1);
        $this->assertInstanceOf(MultiPoint::class, $mp2);

        $this->assertEquals([10, 40], $mp1->getGeometries()[0]->toArray());
        $this->assertEquals([10, 40], $mp2->getGeometries()[0]->toArray());

        $this->assertEquals($wktv2, $mp2->toWkt());
    }
}
