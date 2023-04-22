<?php
function Point($x, $y)
{
    return ['x' => $x, 'y' => $y];
}
function Hex($q, $r, $s)
{
//    if (round($q + $r + $s) != 0)
//	throw new Exception("q + r + s must be 0");
    return ['q' => $q, 'r' => $r, 's' => $s];
}
function hex_add($a, $b)
{
    return Hex($a['q'] + $b['q'], $a['r'] + $b['r'], $a['s'] + $b['s']);
}
function hex_subtract($a, $b)
{
    return Hex($a['q'] - $b['q'], $a['r'] - $b['r'], $a['s'] - $b['s']);
}
function hex_scale($a, $k)
{
    return Hex($a['q'] * $k, $a['r'] * $k, $a['s'] * $k);
}
function hex_rotate_left($a)
{
    return Hex(-$a['s'], -$a['q'], -$a['r']);
}
function hex_rotate_right($a)
{
    return Hex(-$a['r'], -$a['s'], -$a['q']);
}
define('hex_directions', [Hex(-1, 0, 1), Hex(0, -1, 1), Hex(1, -1, 0), Hex(1, 0, -1), Hex(0, 1, -1), Hex(-1, 1, 0)]);
function hex_direction($direction)
{
    return hex_directions[$direction];
}
function hex_neighbor($hex1, $direction)
{
    $hex2 = hex_direction($direction);
    return hex_add($hex1, $hex2);
}
define('hex_diagonals', [Hex(2, -1, -1), Hex(1, -2, 1), Hex(-1, -1, 2), Hex(-2, 1, 1), Hex(-1, 2, -1), Hex(1, 1, -2)]);
function hex_diagonal_neighbor($hex, $direction)
{
    $hex2 = hex_diagonals[$direction];
    return hex_add($hex, $hex2);
}
function hex_length($hex)
{
    return (abs($hex['q']) + abs($hex['r']) + abs($hex['s'])) / 2;
}
function hex_distance($a, $b)
{
    return hex_length(hex_subtract($a, $b));
}
function OffsetCoord($col, $row)
{
    return ['col' => $col, 'row' => $row];
}
define('EVEN', 1);
define('ODD', -1);
function qoffset_from_cube($offset, $h)
{
    $col = $h['q'];
    $row = $h['r'] + ($h['q'] + $offset * ($h['q'] & 1)) / 2;
//    if ($offset !== EVEN && $offset !== ODD)
//    {
//	throw new Exception("offset must be EVEN (+1) or ODD (-1)");
//    }
    return OffsetCoord($col, $row);
}
function qoffset_to_cube($offset, $h)
{
    $q = h . col;
    $r = h['row'] - ($h['col'] + $offset * ($h['col'] & 1)) / 2;
    $s = -q - r;
//    if ($offset !== EVEN && $offset !== ODD)
//    {
//	throw new Exception("offset must be EVEN (+1) or ODD (-1)");
//    }
    return Hex($q, $r, $s);
}
function roffset_from_cube($offset, $h)
{
    $col = $h['q'] + ($h['r'] + $offset * ($h['r'] & 1)) / 2;
    $row = $h['r'];
//    if ($offset !== EVEN && $offset !== ODD)
//    {
//	throw new Exception("offset must be EVEN (+1) or ODD (-1)");
//    }
    return OffsetCoord($col, $row);
}
function roffset_to_cube($offset, $h)
{
    $q = $h['col'] - ($h['row'] + $offset * ($h['row'] & 1)) / 2;
    $r = $h['row'];
    $s = -$q - $r;
//    if ($offset !== EVEN && $offset !== ODD)
//    {
//	throw new Exception("offset must be EVEN (+1) or ODD (-1)");
//    }
    return Hex($q, $r, $s);
}
function find_hexside($src, $dst)
{
    $src_col = intval(substr($src, 2, 2));
    $src_row = intval(substr($src, 0, 2));
    $src_hex = OffsetCoord($src_col, $src_row);
    $src_cube = roffset_to_cube(EVEN, $src_hex);

    $dst_col = intval(substr($dst, 2, 2));
    $dst_row = intval(substr($dst, 0, 2));
    $dst_hex = OffsetCoord($dst_col, $dst_row);
    $dst_cube = roffset_to_cube(EVEN, $dst_hex);

    for ($i = 0; $i < 6; $i++)
    {
	$cube = hex_neighbor($src_cube, $i);
	if ($cube == $dst_cube) return $i;
    }
//    throw new Exception("Hexside not found");
}
function roffset_neighbor($col, $row, $direction)
{
    return roffset_from_cube(ODD, hex_neighbor(roffset_to_cube(ODD, OffsetCoord($col, $row)), $direction));
}
