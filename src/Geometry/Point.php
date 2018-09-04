<?php

declare(strict_types=1);

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Point.
 *
 * @author rick
 */

namespace Ricklab\Location\Geometry;

use Ricklab\Location\Location;

class Point implements GeometryInterface
{
    protected $longitude;
    protected $latitude;

    /**
     * Create a new Point from Longitude and latitude.
     *
     * Usage: new Point(latitude, longitude);
     * or new Point([longitude, latitude]);
     *
     * @param Number|array $lat  Latitude coordinates or a coordinates array in the order of [longitude, latitude]
     * @param Number       $long Longitude coordinates
     */
    public function __construct($lat, $long = null)
    {
        if (null === $long) {
            if (\is_array($lat)) {
                [$long, $lat] = $lat;
            } else {
                throw new \InvalidArgumentException('Arguments must be an array or two numbers.');
            }
        }

        if (!\is_numeric($long) || $long > 180 || $long < -180) {
            throw new \InvalidArgumentException('longitude must be a valid number between -180 and 180.');
        }

        if (!\is_numeric($lat) || $lat > 90 || $lat < -90) {
            throw new \InvalidArgumentException('latitude must be a valid number between -90 and 90.');
        }

        $this->longitude = (float) $long;
        $this->latitude = (float) $lat;
    }

    /**
     * Create a new point from Degrees, minutes and seconds.
     *
     * @param array $lat Latitude in the order of degress, minutes, seconds[, direction]
     * @param array $lon Longitude in the order of degress, minutes, seconds[, direction]
     *
     * @return Point
     */
    public static function fromDms(array $lat, array $lon): self
    {
        $decLat = Location::dmsToDecimal($lat[0], $lat[1], $lat[2], isset($lat[3]) ? $lat[3] : null);

        $decLon = Location::dmsToDecimal($lon[0], $lon[1], $lon[2], isset($lon[3]) ? $lon[3] : null);

        return new self($decLat, $decLon);
    }

    /**
     * Latitude in an array of [degrees, minutes, seconds].
     */
    public function getLatitudeInDms(): array
    {
        return Location::decimalToDms($this->latitude);
    }

    /**
     * Latitude in an array of [degrees, minutes, seconds].
     */
    public function getLongitudeInDms(): array
    {
        return Location::decimalToDms($this->longitude);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->longitude.' '.$this->latitude;
    }

    /**
     * The latitude.
     */
    public function getLatitude(): float
    {
        return $this->latitude;
    }

    /**
     * Find distance to another point.
     *
     * @param string $unit
     * @param int    $formula formula to use, should either be Location::HAVERSINE or Location::VINCENTY. Defaults to
     *                        Location::$defaultFormula
     *
     * @return float the distance
     */
    public function distanceTo(Point $point2, $unit = 'km', $formula = null): float
    {
        return Location::calculateDistance($this, $point2, $unit, $formula);
    }

    public function __get($request)
    {
        $request = \mb_strtolower($request);

        if (\in_array($request, ['x', 'lon', 'long', 'longitude'])) {
            return $this->longitude;
        }

        if (\in_array($request, ['y', 'lat', 'latitude'])) {
            return $this->latitude;
        }

        throw new \InvalidArgumentException('Unexpected value for retrieval');
    }

    /**
     * Find a location a distance and bearing from this one.
     *
     * @param Number $distance distance to other point
     * @param Number $bearing  initial bearing to other point
     * @param string $unit     The unit the distance is in
     */
    public function getRelativePoint(float $distance, float $bearing, string $unit = 'km'): Point
    {
        $rad = Location::getEllipsoid()->radius($unit);
        $lat1 = $this->latitudeToRad();
        $lon1 = $this->longitudeToRad();
        $bearing = \deg2rad($bearing);

        $lat2 = \sin($lat1) * \cos($distance / $rad) +
                \cos($lat1) * \sin($distance / $rad) * \cos($bearing);
        $lat2 = \asin($lat2);

        $lon2y = \sin($bearing) * \sin($distance / $rad) * \cos($lat1);
        $lon2x = \cos($distance / $rad) - \sin($lat1) * \sin($lat2);
        $lon2 = $lon1 + \atan2($lon2y, $lon2x);

        return new self(\rad2deg($lat2), \rad2deg($lon2));
    }

    /**
     * Get the latitude in Rads.
     *
     * @return Number Latitude in Rads
     */
    public function latitudeToRad()
    {
        return \deg2rad($this->latitude);
    }

    /**
     * Get the longitude in Rads.
     *
     * @return Number Longitude in Rads
     */
    public function longitudeToRad()
    {
        return \deg2rad($this->longitude);
    }

    /**
     * Get the bearing from this Point to another.
     *
     *
     * @return float bearing
     */
    public function initialBearingTo(Point $point2): float
    {
        if (\function_exists('initial_bearing') && Location::$useSpatialExtension) {
            return initial_bearing($this->jsonSerialize(), $point2->jsonSerialize());
        }
        $y = \sin(
                          \deg2rad($point2->getLongitude() - $this->getLongitude())
                      ) * \cos($point2->latitudeToRad());
        $x = \cos($this->latitudeToRad())
                      * \sin($point2->latitudeToRad()) - \sin(
                                                            $this->latitudeToRad()
                                                        ) * \cos($point2->latitudeToRad()) *
                                                        \cos(
                                                            \deg2rad($point2->getLongitude() - $this->getLongitude())
                                                        );
        $result = \atan2($y, $x);

        return \fmod(\rad2deg($result) + 360, 360);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => 'Point',
            'coordinates' => $this->toArray(),
        ];
    }

    /**
     * The point as an array in the order of longitude, latitude.
     *
     * @return float[]
     */
    public function toArray(): array
    {
        return [$this->longitude, $this->latitude];
    }

    /**
     * The longitude.
     */
    public function getLongitude(): float
    {
        return $this->longitude;
    }

    /**
     * Finds the mid point between two points.
     *
     *
     * @return Point the mid point
     */
    public function getMidpoint(Point $point): Point
    {
        return $this->getFractionAlongLineTo($point, 0.5);
    }

    /**
     * Returns the point which is a fraction along the line between 0 and 1.
     *
     * @param float $fraction between 0 and 1
     *
     * @throw \InvalidArgumentException
     */
    public function getFractionAlongLineTo(Point $point, $fraction): Point
    {
        if ($fraction < 0 || $fraction > 1) {
            throw new \InvalidArgumentException('$fraction must be between 0 and 1');
        }

        if (\function_exists('fraction_along_gc_line') && Location::$useSpatialExtension) {
            $result = fraction_along_gc_line($this->jsonSerialize(), $point->jsonSerialize(), $fraction);

            return new self($result['coordinates']);
        }
        $distance = Location::haversine($this, $point);

        $lat1 = $this->latitudeToRad();
        $lat2 = $point->latitudeToRad();
        $lon1 = $this->longitudeToRad();
        $lon2 = $point->longitudeToRad();

        $a = \sin((1 - $fraction) * $distance) / \sin($distance);
        $b = \sin($fraction * $distance) / \sin($distance);
        $x = $a * \cos($lat1) * \cos($lon1) +
                        $b * \cos($lat2) * \cos($lon2);
        $y = $a * \cos($lat1) * \sin($lon1) +
                        $b * \cos($lat2) * \sin($lon2);
        $z = $a * \sin($lat1) + $b * \sin($lat2);
        $res_lat = \atan2($z, \sqrt(\pow($x, 2) + \pow($y, 2)));
        $res_long = \atan2($y, $x);

        return new self(\rad2deg($res_lat), \rad2deg($res_long));
    }

    /**
     * Create a line between this point and another point.
     */
    public function lineTo(Point $point): LineString
    {
        return new LineString($this, $point);
    }

    /**
     * @param $distance
     * @param string $unit
     *
     * @deprecated
     */
    public function getMbr($distance, $unit = 'km'): Polygon
    {
        return $this->getBBoxByRadius($distance, $unit);
    }

    /**
     * @param $radius
     * @param string $unit
     */
    public function getBBoxByRadius($radius, $unit = 'km'): Polygon
    {
        return Location::getBBoxByRadius($this, $radius, $unit);
    }

    /**
     * Converts point to Well-Known Text.
     */
    public function toWkt(): string
    {
        return 'POINT('.(string) $this.')';
    }

    /**
     * The coordinates in the order of [longitude, latitude].
     *
     * @return float[]
     */
    public function getCoordinates(): array
    {
        return [$this->longitude, $this->latitude];
    }

    /**
     * This point in an array.
     *
     * @return Point[]
     */
    public function getPoints(): array
    {
        return [$this];
    }
}
