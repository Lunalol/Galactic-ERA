define(["dojo", "dojo/_base/declare"], function (dojo, declare)
{
	return declare("Ships", null,
	{
		constructor: function (bgagame)
		{
			console.log('ships constructor');
//
// Reference to BGA game and Board class
//
			this.bgagame = bgagame;
			this.board = bgagame.board;
//
		},
		place: function (ship)
		{
			console.info('placeShip', ship);
//
			switch (ship.fleet)
			{
				case 'homeStar':
//
					break;
//
				case 'ship':
//
					const node = dojo.place(this.bgagame.format_block('ERAship', {id: ship.id, color: ship.color, location: ship.location}), 'ERAboard');
//
					dojo.style(node, 'position', 'absolute');
					dojo.style(node, 'left', this.board.hexagons[ship.location].x - node.clientWidth / 2 + 'px');
					dojo.style(node, 'top', this.board.hexagons[ship.location].y - node.clientHeight / 2 + 'px');
//
					dojo.connect(node, 'click', this, 'click');
					break;
//
				case 'A':
				case 'B':
				case 'C':
				case 'D':
				case 'E':
//
					break;
//
			}
			this.arrange(ship.location);
//
		},
		move: function (ships, to)
		{
			console.info('moveShips', ships, to);
//
			for (const ship of ships)
			{
				const node = $(`ERAship-${ship}`);
				dojo.style(node, 'left', this.board.hexagons[to].x - node.clientWidth / 2 + 'px');
				dojo.style(node, 'top', this.board.hexagons[to].y - node.clientHeight / 2 + 'px');
				const from = dojo.getAttr(node, 'location');
				dojo.setAttr(node, 'location', to);
				this.arrange(from);
			}
			this.arrange(to);
//
		},
		remove: function (ship)
		{
			console.info('removeShip', ship);
//
			dojo.query(`#ERAboard .ERAship[ship=${ship.id}]`).remove();
			this.arrange(ship.location);
//
		}
		,
		arrange: function (location)
		{
			let index = 0;
			const nodes = dojo.query(`#ERAboard .ERAship[location='${location}']`);
			for (const node of nodes)
			{
				dojo.style(node, 'transform', `scale(25%) translate(${2 * (index - nodes.length / 2) * node.clientWidth / 10}px, ${2 * (index - nodes.length / 2) * node.clientHeight / 10}px) rotate(calc(-1 * var(--ROTATE)))`);
				dojo.style(node, 'z-index', index + 200);
				index++;
			}

		}
		,
		showPath: function ()
		{
			dojo.destroy('ERApath');
//
			const selected = dojo.query(`#ERAboard .ERAship.ERAselected`);
			if (selected.length === 0) return;
//
			let paths = this.bgagame.gamedatas.gamestate.args._private['move'][dojo.getAttr(selected[0], 'ship')];
//
			let possible = Object.keys(paths);
			selected.forEach((node) =>
			{
				let paths = Object.keys(this.bgagame.gamedatas.gamestate.args._private['move'][dojo.getAttr(node, 'ship')]);
				possible = possible.filter((location) => paths.includes(location));
			}
			);
//
			const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
			for (let location of possible)
			{
				const SVGpath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
				let path = '';
				let first = true;
				for (let next of paths[location].path)
				{
					path += (first ? 'M' : 'L') + this.board.hexagons[next].x + ' ' + this.board.hexagons[next].y;
					first = false;
				}
				SVGpath.setAttribute('stroke', '#ffffff40');
				SVGpath.setAttribute('fill', 'none');
				SVGpath.setAttribute('d', path);
				SVGpath.setAttribute('stroke-width', '2');
				svg.appendChild(SVGpath);
			}
			for (let location of possible)
			{
				const SVGcircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
				SVGcircle.setAttribute('fill', '#' + this.bgagame.gamedatas.gamestate.args.active);
				SVGcircle.setAttribute('cx', this.board.hexagons[location].x);
				SVGcircle.setAttribute('cy', this.board.hexagons[location].y);
				SVGcircle.setAttribute('r', 10);
				svg.appendChild(SVGcircle);
//
				const SVGshape = this.board.drawHexagon(this.board.hexagons[location], 'none');
				dojo.setAttr(SVGshape, 'location', location);
				svg.appendChild(SVGshape);
//
				dojo.connect(SVGshape, 'click', (event) => {
					let ships = dojo.query(`#ERAboard .ERAship.ERAselected`).reduce((L, node) => [...L, +node.getAttribute('ship')], []);
					let location = dojo.getAttr(event.target, 'location');
					this.bgagame.action('move', {color: this.bgagame.gamedatas.gamestate.args.active, location: JSON.stringify(location), ships: JSON.stringify(ships)});
					dojo.stopEvent(event);
				});
			}
			dojo.setStyle(svg, 'position', 'absolute');
			dojo.setStyle(svg, 'left', '0px');
			dojo.setStyle(svg, 'top', '0px');
			dojo.setStyle(svg, 'z-index', '150');
			dojo.setStyle(svg, 'pointer-events', 'all');
			svg.setAttribute("width", 10000);
			svg.setAttribute("height", 10000);
			svg.id = 'ERApath';
			this.board.board.appendChild(svg);
		}
		,
		click: function (event)
		{
			const ship = event.currentTarget;
//
			if (dojo.hasClass(ship, 'ERAselectable'))
			{
				dojo.stopEvent(event);
//
				const location = dojo.getAttr(ship, 'location');
				const color = dojo.getAttr(ship, 'color');
//
				dojo.query(`#ERAboard .ERAship[color='${color}']:not([location='${location}'])`).removeClass('ERAselected');
				if (event.detail === 1) dojo.toggleClass(ship, 'ERAselected');
				if (event.detail === 2) dojo.query(`#ERAboard .ERAship[color='${color}'][location='${location}']`).toggleClass('ERAselected', dojo.hasClass(ship, 'ERAselected'));
//
				this.showPath();
			}
		}
	}
	);
});
