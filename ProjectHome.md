An algorithm and PHP implementation for triangulating the location of a target given range and coordinates in an arbitrary number of dimensions.

Algorithm:
We determine the region the target lies in by creating the smallest-possible n-sphere guaranteed to contain all of the points contained in the overlapping region between two other n-spheres.  While this necessarily returns an n-volume larger than really necessary, we're guaranteed that our target is within the region, and our calculation is simplified.

On each iteration of the algorithm, we intersect the newest data point with the previous estimated region, and the smallest n-sphere containing that intersection becomes our new estimated region.