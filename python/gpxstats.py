#!/usr/bin/python

"""Outputs various information about GPX track files.

Given one or more GPX files (http://www.topografix.com/GPX/1/1/) this script
will print out the distance, minimum/maximum elevation and ascent/descent for
each track. It understands the GPX trk, trkseg, and trkpt types.
"""

import sys, math, urllib
from gpxlib import Gpx, Geopoint, Waypoint
from datetime import datetime

def computeStatistics(points):
  # Given a list, create a generator that yields a tuple of the current
  # element and the next one.
  def pairNext(l):
    for i in xrange(len(l) - 1):
      yield (l[i], l[i+1])

  ascent = 0.0
  descent = 0.0
  distance = 0.0
  minimumElevation = sys.maxint
  maximumElevation = -minimumElevation + 1

  # Calculate differences between adjacent points
  elevationChanges = [point.elevationChange(nextPoint) for point, nextPoint in
                      pairNext(points)]
  distances = [0.0]
  distances.extend([point.distance(nextPoint) for point, nextPoint in 
                       pairNext(points)])
  return {'distances':distances,
          'elevationChanges':elevationChanges,
          'minimumElevation':min([point.elevation for point in points]),
          'maximumElevation':max([point.elevation for point in points]),
          'starttime':min([point.time for point in points]),
          'endtime':max([point.time for point in points]),
          'ascent':sum([elevation for elevation in elevationChanges 
                        if elevation > 0]),
          'descent':-1 * sum([elevation for elevation in elevationChanges 
                              if elevation < 0])
         }

def getUnitSpecifics(units='metric'):
  unitSpecifics = dict({'elevationUnits':'m',
                        'distanceConverter':0.001,
                        'distanceUnits':'km',
                        'yAxisStepSize':100})

  if (units != 'metric'): # assume imperial
    MILES_IN_METER = 0.000621371192
    FEET_IN_METER = 3.2808399
    unitSpecifics = dict({'elevationUnits':'ft',
                          'distanceConverter':MILES_IN_METER,
                          'distanceUnits':'mi',
                          'yAxisStepSize':300})

  return unitSpecifics

# Given a list of waypoints, return a list of waypoints, all of which are
# minimumProximity away from the rest
def filterCloseWaypoints(waypoints, minimumProximity):
  if len(waypoints) < 2:
    return waypoints
  waypoint = waypoints[0]
  filteredWaypoints = []
  for nextWaypoint in waypoints[1:]:
    if waypoint[0].distance(nextWaypoint[0]) > minimumProximity:
      filteredWaypoints.append(nextWaypoint)
  output = [waypoint]
  output.extend(filterCloseWaypoints(filteredWaypoints, minimumProximity))
  return output

def outputTrackDetails(track, waypoints, units):
#  print 'Track: %s' % (track.name)
  points = track.points
  statistics = computeStatistics(points)
  distance = sum(statistics['distances'])

  unitSpecifics = getUnitSpecifics(units)
  elevationUnits = unitSpecifics['elevationUnits']
  distanceConverter = unitSpecifics['distanceConverter']
  distanceUnits = unitSpecifics['distanceUnits']

  start = datetime.strptime(statistics['starttime'], "%Y-%m-%dT%H:%M:%S.%fZ")
  end = datetime.strptime(statistics['endtime'], "%Y-%m-%dT%H:%M:%S.%fZ")
  diff = (end-start).seconds

#  print " total distance [%s]:\t%.2f" % (distanceUnits, distance * distanceConverter)
#  print " ascent [%s]:\t%.1f" % (elevationUnits, statistics['ascent']) 
#  print " descent [%s]:\t%.1f" % (elevationUnits, statistics['descent']) 
#  print " minimum elevation [%s]:\t%.1f" % (elevationUnits, statistics['minimumElevation'])
#  print " maximum elevation [%s]:\t%.1f" % (elevationUnits, statistics['maximumElevation'])
#  print " start:\t%s" % (statistics['starttime'])
#  print " end:\t%s" % (statistics['endtime'])
#  print " length:\t%d:%d" % ((diff/60), diff-(int((diff/60))*60))
#  print generateChartURL(points, waypoints, statistics, units) 

  print "%.2f\t%d:%d" %(distance * distanceConverter, (diff/60), diff-(int((diff/60))*60)) 

def outputFileDetails(fileName, units):
  gpx = Gpx(fileName, units)
  _ = [outputTrackDetails(track, gpx.waypoints, units) 
       for track in gpx.tracks]

def main(argv=None):
  if argv == None:
    argv = sys.argv
  if len(argv) < 2:
    print "No GPX file provided.\nUsage is: gpxstats.py [-i] <file1> <file2>..."
    return 0
  units = 'metric'
  firstFileIndex = 1
  if argv[1] == '-i':
    units = 'imperial'
    firstFileIndex = 2
  for i in xrange(firstFileIndex,len(argv)):
    outputFileDetails(argv[i], units)

if __name__ == '__main__':
  sys.exit(main())
