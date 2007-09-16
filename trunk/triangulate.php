<?
// Copyright (c) 2007 Michael Mol <mikemol@gmail.com>
// This source code is released under VERSION 2 of the GNU General Public
// License. You should have received a copy of the license with this source code
// in a file named copying.txt. If you have not, you may email Michael Mol at
// mikemol@gmail.com.
//
// If it's not already patented (I just came up with it; I didn't check to
// see if someone else already had...), the algorithm described
// and implemented herein is under the public domain.
//
// If you wish to receive a different license for this software, send Michael
// Mol an email...However, it's no skin off of my back if you implement the
// algorithm on your own.  Though I would appreciate credit.

session_start();
if ( $_GET['reset'] )
{
	session_destroy();
	session_start();
}

// Change this to the name of the file.  I had trouble calculating it dynamically, so I let it sit as static.
$target="triangulate.php";

// Set our defalt decimal precision
bcscale(9);

function newregion()
{
	// NOTE: To the best of my knowledge, this method of triangulation is
	// unique. Consider the *algorithm* as released into the Public Domain.
	// The code, sloppy as it may be, is mine.

	// Algorithm
	// We determine the region the target lies in by creating the smallest-
	// possible n-sphere gauranteed to contain all of the points contained
	// in the overlapping region between two other n-spheres.  While this
	// necessarily returns an n-volume larger than really necessary, we're
	// gauranteed that our target is within the region, and our calculation
	// is simplified.

	// On each iteration of the algorithm, we intersect the newest data
	// point with the previous estimated region, and the smallest n-sphere
	// containing that intersection becomes our new estimated region.

	// One advantage to this method lies in that we need not keep track of
	// all of the data points we've been given.  Instead, we can simply
	// return the n-sphere approximation of the intersection between the
	// new data point and the previously calculated n-sphere. (Or the first
	// data point, if this is the first time we've calculated an n-sphere.)

	// This function was written in such a way that it should scale to as
	// many dimensions as can be reliably iterated through using a
	// floating-point iterator. (This first version is sloppy...I don't
	// know that PHP
	
	// I use PHP's arbitraty precision math support extensively, as it's the
	// only way to get around inaccuracies in IEEE floating point.  However,
	// I noticed that there might be a bug in it.

	// A couple operating variables
	$r1 = $_SESSION['radius'];
	$r2 = $_GET['radius'];
	$dimensions = $_SESSION['dimensions'];

	// Calculate distance between P1 and P2.
	for ( $i = 1; $i <= $dimensions; $i++ )
	{
		$presquare = bcadd( $presquare, bcpow(bcsub( $_SESSION["p1$i"], $_GET[$i] ), 2) );
	}
	$d = bcsqrt($presquare);

	// Calculate the distance between P1 and the center point of the new n-sphere

	$dN = bcdiv( bcadd( bcsub(bcpow($r1, 2), bcpow($r2,2)), bcpow($d,2) ), ( bcmul(2, $d )));

	// Calculate the ratio dN/d. (Used to find per-dimension distances between P1 and the centerpoint of the new n-sphere.
	$dR = bcdiv($dN,$d);

	// Calculate square of radius
	$rad_square = bcsub(bcpow($r1,2), bcpow($dN,2) );

	if( 0 > $rad_square )
	{
		print "<strong>Error:</strong> New data point does not overlap established region of possible location.  This means you've entered an incorrect data point at some point.  If you suspect it to be the one you just entered, you may try entering again.  However, if you entered a previous data point in error, your current results are suspect, and it is recommended that you <a href=\"$target?reset=reset\">start over</a>.<br><br>";
		return false;
	}

	//and save the radius of the new n-sphere.
	$new['radius'] = bcsqrt( $rad_square );

	// Calculate center point of new n-sphere
	for( $i = 1; $i <= $dimensions; $i++ )
	{
		// x = x_old + distance_x * distance_ratio

		// Calculate distance between P1 and P2 on this dimension
		$d_x = bcsub( $_GET["$i"], $_SESSION["p1$i"]);

		// Calculate distance between P1 and the center point of the new n-sphere on this dimension
		$x_o = bcmul($d_x, $dR);

		// Calculate the coordinate of the center point of the new n-sphere on this dimension.
		$x = bcadd( $_SESSION["p1$i"], $x_o);

		// Save the coordinate into the new n-sphere
		$new["p1$i"] = $x;
	}

	// Save new n-sphere into our session
	foreach ( $new as $property => $value )
	{
		$_SESSION[$property] = $value;
	}

}

if( $_GET['dimensions'] > 0 )
{
	$_SESSION['dimensions'] = $_GET['dimensions'];
}
elseif( empty( $_SESSION['dimensions'] ) 
   && ( empty( $_GET['dimensions'] )   )  )
{
?>

<form name=dimensions target="<? print $target ?>" method=GET>
 Please enter a number of dimensions: <input type=text name=dimensions value=3>
<input type=submit>
</form>
<?
}

if( $_SESSION['dimensions'] )
{
	if( isset ( $_SESSION['radius'] ) )
	{
		// Calculate a new region based on the intersection of the current and previous.
		newregion();

		switch( $_SESSION['dimensions'] )
		{
		case 1:
			$name="line segment";
			break;
		case 2:
			$name="circle volume";
			break;
		case 3:
			$name='sphere volume';
			break;
		default:
			$name=$_SESSION['dimensions'] . "-sphere encapsulated region";
		}

		?>Current determined region is a <? print $name ?> centered at at (<?
		for( $i = 1; $i < $_SESSION['dimensions']; $i++ )
		{
			print $_SESSION["p1$i"] . ", ";
		}
		print $_SESSION["p1$i"];
	
		?>) with a radius of <? print $_SESSION['radius'] ?><br><br><?
	}
	elseif( isset ( $_GET['radius'] ) )
	{
		$_SESSION['radius']=$_GET['radius'];
		for( $i = 1; $i <= $_SESSION['dimensions']; $i++ )
		{
			$_SESSION["p1$i"] = $_GET[$i];
		}
	}
?>

<form name=newregion target=<? print $target ?> method=GET>
Next data point:<br>
Radius: <input type=text name=radius><br>
<?
	for ( $i = 1; $i <= $_SESSION['dimensions']; $i++ )
	{
		?>Dimension <? print $i ?>: <input type=text name=<? print $i ?>><br><?
	}
?>
 <input type=submit><a href="<? print $target . "?reset=reset" ?>">Done</a>
</form>
<?
}
