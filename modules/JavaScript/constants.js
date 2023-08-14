//
// Game preferences
//
const SPEED = 100;
//
const SLOW = 0;
const NORMAL = 1;
const FAST = 2;
//
DELAYS = {[SLOW]: 1000, [NORMAL]: 500, [FAST]: 250};
//
// Size of board
//
const boardWidth = 10000;
const boardHeight = 10000;
//
const MARGIN = 2;
const HEIGHT = 183 / 2;
const SIZE = HEIGHT / Math.sqrt(3);
const WIDTH = 2 * HEIGHT / Math.sqrt(3);
const BOARDS = [
	[+0.00, +0.00],
	[+3.00, -7.00],
	[+6.75, -0.50],
	[+3.75, +6.50],
	[-3.00, +7.00],
	[-6.75, +0.50],
	[-3.75, -6.50]
];
