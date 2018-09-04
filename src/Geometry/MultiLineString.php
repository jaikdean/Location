<?php

declare(strict_types=1);
/**
 * Author: rick
 * Date: 16/07/15
 * Time: 16:34.
 */

namespace Ricklab\Location\Geometry;

/**
 * Class MultiLineString.
 */
class MultiLineString implements GeometryInterface, GeometryCollectionInterface
{
    /**
     * @var LineString[]
     */
    protected $geometries = [];

    public function __construct(array $lineStrings)
    {
        foreach ($lineStrings as $lineString) {
            if (!$lineString instanceof LineString) {
                $lineString = new LineString($lineString);
            }

            $this->geometries[] = $lineString;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function toWkt()
    {
        return 'MULTILINESTRING'.(string) $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPoints()
    {
        $points = [];
        foreach ($this->geometries as $line) {
            $linePoints = $line->getPoints();
            $points += $linePoints;
        }

        return $points;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'type' => 'MultiLineString',
            'coordinates' => $this->toArray(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $return = [];
        foreach ($this->geometries as $line) {
            $return[] = $line->toArray();
        }

        return $return;
    }

    /**
     * @return LineString[] an array of the LineStrings
     */
    public function getGeometries()
    {
        return $this->geometries;
    }

    /**
     * Adds a new LineString to the collection.
     *
     *
     * @return $this
     */
    public function addGeometry(LineString $lineString)
    {
        $this->geometries[] = $lineString;

        return $this;
    }

    /**
     * Removes a LineString from the collection.
     *
     *
     * @return $this
     */
    public function removeGeometry(LineString $lineString)
    {
        foreach ($this->geometries as $index => $geom) {
            if ($lineString === $geom) {
                unset($this->geometries[$index]);
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return '('.\implode(',', $this->geometries).')';
    }
}
