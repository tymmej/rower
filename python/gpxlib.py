#!/usr/bin/python

import math, sys, time
from xml.dom import minidom 

class Geopoint(object):
  # http://en.wikipedia.org/wiki/Earth_radius
  EARTH_MEAN_RADIUS = 6371000.0 #meters

  def __init__(self, latitude, longitude, elevation, time, elevationConverter=1.0):
    self.latitude = float(latitude)
    self.longitude = float(longitude)
    self.elevation = float(elevation) * elevationConverter
    self.waypoint = None
    self.distanceToWaypoint = sys.maxint
    self.time = time
  # radians = degrees * PI / 180
  def latitudeRadians(self):
    return math.radians(self.latitude)

  def longitudeRadians(self):
    return math.radians(self.longitude)

  # http://en.wikipedia.org/wiki/Haversine
  def haversine(self, angle):
    return (1 - math.cos(angle)) / 2

  # http://en.wikipedia.org/wiki/Haversine_formula
  def haversineDistance(self, point):
    haversindR = self.haversine(self.latitudeRadians() - point.latitudeRadians()) + \
        math.cos(self.latitudeRadians()) * \
        math.cos(point.latitudeRadians()) * \
        self.haversine(self.longitudeRadians() - point.longitudeRadians())
    return 2 * Geopoint.EARTH_MEAN_RADIUS * math.asin(math.sqrt(haversindR))

  # http://en.wikipedia.org/wiki/Spherical_law_of_cosines 
  def sphericalLawOfCosinesDistance(self, point):
    return math.acos(math.sin(self.latitudeRadians()) *
                     math.sin(point.latitudeRadians()) +
                     math.cos(self.latitudeRadians()) *
                     math.cos(point.latitudeRadians()) *
                     math.cos(point.longitudeRadians() -
                              self.longitudeRadians())) * Geopoint.EARTH_MEAN_RADIUS

  def elevationChange(self, point):
    return point.elevation - self.elevation

  # Calculate a true distance by averaging the two surface distance measures as
  # well as taking into account elevation change via the Pythagorean Theorem
  # http://en.wikipedia.org/wiki/Pythagorean_theorem
  def distance(self, point):
    if self.latitude == point.latitude and self.longitude == point.longitude:
      return 0
    averageDistance = (self.haversineDistance(point) +
                       self.sphericalLawOfCosinesDistance(point)) / 2
    return averageDistance
## factor elevation change into distance travelled
#    elevationChange = self.elevationChange(point)
#    return math.sqrt(averageDistance * averageDistance + elevationChange *
#                     elevationChange)

  def pairWithWaypoint(self, waypoint, distanceToWaypoint):
    self.waypoint = waypoint
    self.distanceToWaypoint = distanceToWaypoint

  def tostring(self):
    return "latitude: %f, longitiude: %f, elevation %f" % \
        (self.latitude, self.longitude, self.elevation)

class Waypoint(Geopoint):
  def __init__(self, latitude, longitude, elevation, time, elevationConverter=1.0, 
               name=None, type=None, labelText=None):
    Geopoint.__init__(self, latitude, longitude, elevation, time, elevationConverter)
    self.name = name
    self.type = type
    self.labelText = labelText

  def getLabel(self):
    if self.labelText != None:
      return self.labelText
    elif self.name != None:
      return self.name
    else:
      return self.type

  def tostring(self):
    return "%s, name: %s, type: %s, label: %s" % (super(Waypoint, self)
                                                  .tostring(), self.name, 
                                                  self.type, self.getLabel())

class Track(object):
  def __init__(self, name, points):
    self.name = name
    self.points = points

class Gpx(object):
  def __init__(self, file, units='metric'):
    xmldoc = minidom.parse(file)
    self.elevationConverter = 1.0
    if units != 'metric':
      FEET_IN_METER = 3.2808399
      self.elevationConverter = FEET_IN_METER
    self.waypoints = [Waypoint(waypoint.attributes['lat'].value, 
                               waypoint.attributes['lon'].value, 
                               waypoint.getElementsByTagName('ele')[0].childNodes[0].data,
                               waypoint.getElementsByTagName('time')[0].childNodes[0].data,
                               self.elevationConverter,
                               waypoint.getElementsByTagName('name')[0].childNodes[0].data,
                               waypoint.getElementsByTagName('type')[0].childNodes[0].data,
                               waypoint.getElementsByTagName('label_text')[0].childNodes[0].data)
                      for waypoint in xmldoc.getElementsByTagName('wpt')]
    self.tracks = [] 
    for track in xmldoc.getElementsByTagName('trk'):
      self.parseTrack(track)

  def parseTrack(self, track):
    trackNameNodeList = track.getElementsByTagName('name')
    trackName = time.gmtime() 
    if trackNameNodeList != None:
      trackName = trackNameNodeList[0].firstChild.data
      if trackName in self.tracks:
        trackName += '-' + str(time.gmtime())

    points = [Geopoint(trackPoint.attributes['lat'].value, 
                       trackPoint.attributes['lon'].value, 
                       trackPoint.getElementsByTagName('ele')[0].childNodes[0].data,
                       trackPoint.getElementsByTagName('time')[0].childNodes[0].data,
                       self.elevationConverter)
              for trackSegment in track.getElementsByTagName('trkseg')
              for trackPoint in trackSegment.getElementsByTagName('trkpt')]
    self.pairWaypointWithClosestPoint(self.waypoints, points)
    self.tracks.append(Track(trackName,points))

  def pairWaypointWithClosestPoint(self, waypoints, points):
    for waypoint in waypoints:
      closestPoint = None
      distance = sys.maxint * 1.0
      for point in points:
        tempDistance = waypoint.distance(point)
        if tempDistance < distance:
          distance = tempDistance
          closestPoint = point
      closestPoint.pairWithWaypoint(waypoint, distance)


def main(argv=None):
  if argv == None:
    argv = sys.argv
  if len(argv) != 2:
    print "No GPX file provided.\nUsage is: gpxlib.py <file>"
    return 0
  gpx = Gpx(argv[1])

  numberOfTracks = len(gpx.tracks)
  if numberOfTracks == 0:
    print 'No tracks found!'
  elif numberOfTracks == 1:
    print '%d track found' % (numberOfTracks)
  else:
    print '%d tracks found' % (numberOfTracks)
  for trackName, _ in gpx.tracks.items():
    print ' ' + trackName

  print ''

  numberOfWaypoints = len(gpx.waypoints)
  if numberOfWaypoints == 0:
    print "No waypoints found!"
  elif numberOfWaypoints == 1:
    print "%d waypoint found" % (numberOfWaypoints)
  else:
    print "%d waypoints found" % (numberOfWaypoints)
  for waypoint in gpx.waypoints:
    print ' ' + waypoint.getLabel()

if __name__ == '__main__':
  sys.exit(main())
