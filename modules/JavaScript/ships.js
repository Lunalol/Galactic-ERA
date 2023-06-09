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
			if (ship.location === 'stock') return;
//
			switch (ship.fleet)
			{
				case 'homeStar':
					{
						node = dojo.place(this.bgagame.format_block('ERAhomeStar', {id: ship.id, color: ship.color, location: ship.location}), 'ERAboard');
//
						dojo.style(node, 'position', 'absolute');
						dojo.style(node, 'left', this.board.hexagons[ship.location].x - node.clientWidth / 2 + 'px');
						dojo.style(node, 'top', this.board.hexagons[ship.location].y - node.clientHeight / 2 + 'px');
						dojo.style(node, 'transform', `scale(40%) rotate(calc(-1 * var(--ROTATE)))`);
//
						dojo.connect(node, 'click', this, 'click');
					}
					break;
				case 'ship':
					{
						node = dojo.place(this.bgagame.format_block('ERAship', {id: ship.id, color: ship.color, location: ship.location}), 'ERAboard');
//
						dojo.style(node, 'position', 'absolute');
						dojo.style(node, 'left', this.board.hexagons[ship.location].x - node.clientWidth / 2 + 'px');
						dojo.style(node, 'top', this.board.hexagons[ship.location].y - node.clientHeight / 2 + 'px');
//
						dojo.connect(node, 'click', this, 'click');
					}
					break;
				case 'fleet':
				case 'A':
				case 'B':
				case 'C':
				case 'D':
				case 'E':
					{
						node = dojo.place(this.bgagame.format_block('ERAship', {id: ship.id, color: ship.color, location: ship.location}), 'ERAboard');
						dojo.setAttr(node, 'fleet', '?');
//
						dojo.style(node, 'position', 'absolute');
						dojo.style(node, 'left', (this.board.hexagons[ship.location].x - node.clientWidth / 2) + 'px');
						dojo.style(node, 'top', (this.board.hexagons[ship.location].y - node.clientHeight / 2) + 'px');
						dojo.setAttr(node, 'fleet', ship.fleet);
//
						dojo.connect(node, 'click', this, 'click');
					}
					break;
//
			}
			if (/^\d:([+-]\d){3}$/.test(ship.location)) this.arrange(ship.location);
//
			return node;
		},
		reveal: function (fleet)
		{
			let node = $(`ERAship-${fleet.id}`);
			if (node) dojo.setAttr(node, 'fleet', fleet.fleet);
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
		},
		arrange: function (location)
		{
			let index = fleet = 0;
			nodes = Array.from(dojo.query(`.ERAship[location='${location}']`)).sort((a, b) => dojo.hasAttr(a, 'fleet') ? -1 : 1);
			for (const node of nodes)
			{
				if (dojo.hasAttr(node, 'fleet'))
				{
					dojo.style(node, 'transform', `rotate(calc(-1 * var(--ROTATE))) translate(${2 * (index - nodes.length / 2) * node.clientWidth / 10}px, ${2 * (index - nodes.length / 2) * node.clientHeight / 10}px)`);
					dojo.style(node, 'z-index', 200 + fleet);
					fleet++;
				}
				else
				{
					dojo.style(node, 'transform', `scale(25%) rotate(calc(-1 * var(--ROTATE))) translate(${2 * (index - nodes.length / 2) * node.clientWidth / 10}px, ${2 * (index - nodes.length / 2) * node.clientHeight / 10}px)`);
					dojo.style(node, 'z-index', 205 + index);
				}
				index++;
			}
		},
		showPath: function ()
		{
			dojo.destroy('ERApath');
//
			const selected = dojo.query(`#ERAboard .ERAship.ERAselected`);
			if (selected.length === 0) return;
//
			const color = dojo.getAttr(selected[0], 'color');
			let paths = this.bgagame.gamedatas.gamestate.args._private['move'][dojo.getAttr(selected[0], 'ship')];
//
			let possible = Object.keys(paths);
			selected.forEach((node) =>
			{
				let paths = Object.keys(this.bgagame.gamedatas.gamestate.args._private['move'][dojo.getAttr(node, 'ship')]);
				possible = possible.filter((location) => paths.includes(location));
			}
			);
			if (possible.length === 0) return;
//
			const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
			for (let location of possible)
			{
				const SVGpath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
				let path = 'M' + this.board.hexagons[location].x + ' ' + this.board.hexagons[location].y;
				let from = paths[location].from;
				while (from)
				{
					path += 'L' + this.board.hexagons[from].x + ' ' + this.board.hexagons[from].y;
					from = paths[from].from;
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
				SVGcircle.setAttribute('fill', '#' + color);
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
					this.bgagame.action('move', {color: color, location: JSON.stringify(location), ships: JSON.stringify(ships)});
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
		},
		click: function (event)
		{
			const ship = event.currentTarget;
			const location = dojo.getAttr(ship, 'location');
			const color = dojo.getAttr(ship, 'color');
//
			if (this.bgagame.isCurrentPlayerActive())
			{
//
				if (this.bgagame.gamedatas.gamestate.name === 'combatChoice')
				{
					dojo.stopEvent(event);
					return this.bgagame.combatChoice(location);
				}
				if (this.bgagame.gamedatas.gamestate.name === 'gainStar')
				{
					dojo.stopEvent(event);
					return this.bgagame.gainStar(location);
				}
				if (this.bgagame.gamedatas.gamestate.name === 'buildShips')
				{
					dojo.stopEvent(event);
					return this.bgagame.buildShips(location);
				}
				if (this.bgagame.gamedatas.gamestate.name === 'growPopulation')
				{
					dojo.stopEvent(event);
					return this.bgagame.growPopulation(location);
				}
				if (this.bgagame.gamedatas.gamestate.name === 'bonusPopulation')
				{
					dojo.stopEvent(event);
					return this.bgagame.bonusPopulation(location);
				}
//
				if (dojo.hasClass(ship, 'ERAselectable'))
				{
					dojo.stopEvent(event);
//
					if (this.bgagame.gamedatas.gamestate.name === 'fleets')
					{
						dojo.query(`#ERAboard .ERAship[color='${color}']:not([location='${location}'])`).removeClass('ERAselected');
//
						if (dojo.hasAttr(ship, 'fleet'))
						{
							dojo.query(`#ERAboard .ERAship[color='${color}']:not([fleet])`).removeClass('ERAselected');
							dojo.addClass(ship, 'ERAselected');
							return this.bgagame.fleets(location, 'fleet', dojo.query(`#ERAboard .ERAship.ERAselected`));
						}
						else
						{
							dojo.query(`#ERAboard .ERAship[color='${color}'][fleet]`).removeClass('ERAselected');
//
							if (event.detail === 1) dojo.toggleClass(ship, 'ERAselected');
							if (event.detail === 2) dojo.query(`#ERAboard .ERAship[color='${color}'][location='${location}']:not([fleet]).ERAselectable`).toggleClass('ERAselected', dojo.hasClass(ship, 'ERAselected'));
						}
						return this.bgagame.fleets(location, 'ships', dojo.query(`#ERAboard .ERAship.ERAselected`));
					}
					else if (this.bgagame.gamedatas.gamestate.name === 'movement')
					{
						dojo.query(`#ERAboard .ERAship[color='${color}']:not([location='${location}'])`).removeClass('ERAselected');
						if (event.detail === 1) dojo.toggleClass(ship, 'ERAselected');
						if (event.detail === 2) dojo.query(`#ERAboard .ERAship[color='${color}'][location='${location}']`).toggleClass('ERAselected', dojo.hasClass(ship, 'ERAselected'));
//
						dojo.destroy('ERApath');
						if (dojo.getAttr(ship, 'ship') in this.bgagame.gamedatas.gamestate.args._private['move']) this.showPath();
//
						let scout = false;
						dojo.query(`#ERAboard .ERAship.ERAselected`).forEach((node) => {
							if (dojo.getAttr(node, 'ship') in this.bgagame.gamedatas.gamestate.args._private['scout']) scout = true;
						});
						dojo.toggleClass('ERAscoutButton', 'disabled', !scout);
					}
				}
			}
		}
	}
	);
});
