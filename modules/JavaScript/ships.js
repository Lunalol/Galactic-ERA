const STEP = 2.5;
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
			this.ERAfleet = new dijit.Tooltip({
				showDelay: 500, hideDelay: 0,
				getContent: (node) =>
				{
					const color = node.getAttribute('color');
					const fleet = node.getAttribute('fleet');
					const ships = node.getAttribute('ships');
//
					let tactics = null;
					let tacticsNode = $(`ERAtechTrack-${color}`).querySelector(`.ERAcounter-tactics[location='${fleet}']`);
					if (tacticsNode) tactics = tacticsNode.getAttribute('tactics');
//
					let html = `<div>`;
					html += `<div class='ERAship ERAship-${color}' fleet='${fleet}' style='display:inline-block;position:relative;margin:5px;'></div>`;
					if (tactics) html += `<div class='ERAcounter ERAcounter-tactics' tactics='${tactics}' style='display:inline-block;position:relative;margin:5px;'></div>`;
					html += `</div>`;
//
					if (fleet !== 'fleet' && fleet !== '?')
					{
						if (tactics === '2x') html += `<div><HR>${this.bgagame.FLEETS[fleet + '2x']}<HR></div>`;
						else html += `<div><HR>${this.bgagame.FLEETS[fleet]}<HR></div>`;
//						html += `<div><B>"${fleet}" ${_('Fleet')}</B> : ${this.bgagame.FLEETS[fleet]}</div>`;
//
						if (ships)
						{
							html += `<div>${ships} ${_('Ship(s)')}</div>`;
							CV = ships * ((fleet === 'A' ? ((tactics === '2x' ? 2 : 1)) : 0) + this.bgagame.gamedatas.technologies.Military[dojo.query('.circleBlack', `ERAtech-${color}-Military`).length]);
							html += `<div><B>${_('CV')} : ${CV}</B></div>`;
							if (fleet === 'C')
							{
								CV = ships * ((tactics === '2x' ? 4 : 2) + this.bgagame.gamedatas.technologies.Military[dojo.query('.circleBlack', `ERAtech-${color}-Military`).length]);
								html += `<div>${_('CV')} : ${CV} ${_('vs. “A” fleet')}</div>`;
							}
						}
					}
					else html += `<div>${_('Unrevealed fleet')}</div>`;
					return html;
				}
			});
//
		},
		place: function (ship)
		{
//			console.info('placeShip', ship);
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
						dojo.style(node, 'transform', `scale(30%) rotate(calc(-1 * var(--ROTATE))) translateX(3px)`);
//
						dojo.connect(node, 'click', this, 'click');
//						dojo.connect(node, 'dragstart', this, (event) => event.dataTransfer.setData("text", ship.id));
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
						dojo.connect(node, 'dragstart', this, (event) => event.dataTransfer.setData("text", ship.id));
					}
					break;
				case 'fleet':
				case 'A':
				case 'B':
				case 'C':
				case 'D':
				case 'E':
					{
						if (ship.location === 'stock')
						{
							const container = `ERAboardFleets-${ship.color}`;
							node = dojo.place(this.bgagame.format_block('ERAship', {id: ship.id, color: ship.color, ship: 0, location: 'stock'}), container);
							dojo.setAttr(node, 'fleet', '?');
						}
						else
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
							dojo.connect(node, 'dragstart', this, (event) => event.dataTransfer.setData("text", ship.id));
//
// GOD MODE
//
							dojo.connect(node, 'oncontextmenu', (event) => {
//
								if (+this.bgagame.gamedatas.GODMODE === 1)
								{
									dojo.stopEvent(event);
									dojo.query('.godMode').remove();
//
									const menu = dojo.place(`<div class='godMode' id='godMode'><ul class='godMode-options' style='color:#${ship.color};'>${ship.fleet}</ul></div>`, 'ebd-body');
									menu.style.left = `${event.clientX - 10}px`;
									menu.style.top = `${event.clientY - 10}px`;
//
									dojo.place(`<li class='godMode-option'><HR></li>`, menu);
//
									dojo.place(`<li class='godMode-option' onclick="gameui.action('GODMODE', {god: JSON.stringify({action: 'fleet', color: '${ship.color}', id: ${ship.id}, delta: -50})});">-50</li>`, menu);
									dojo.place(`<li class='godMode-option' onclick="gameui.action('GODMODE', {god: JSON.stringify({action: 'fleet', color: '${ship.color}', id: ${ship.id}, delta: -10})});">-10</li>`, menu);
									dojo.place(`<li class='godMode-option' onclick="gameui.action('GODMODE', {god: JSON.stringify({action: 'fleet', color: '${ship.color}', id: ${ship.id}, delta: -5})});">-5</li>`, menu);
									dojo.place(`<li class='godMode-option' onclick="gameui.action('GODMODE', {god: JSON.stringify({action: 'fleet', color: '${ship.color}', id: ${ship.id}, delta: -1})});">-1</li>`, menu);
									dojo.place(`<li class='godMode-option' onclick="gameui.action('GODMODE', {god: JSON.stringify({action: 'fleet', color: '${ship.color}', id: ${ship.id}, delta: +1})});">+1</li>`, menu);
									dojo.place(`<li class='godMode-option' onclick="gameui.action('GODMODE', {god: JSON.stringify({action: 'fleet', color: '${ship.color}', id: ${ship.id}, delta: +5})});">+5</li>`, menu);
									dojo.place(`<li class='godMode-option' onclick="gameui.action('GODMODE', {god: JSON.stringify({action: 'fleet', color: '${ship.color}', id: ${ship.id}, delta: +10})});">+10</li>`, menu);
									dojo.place(`<li class='godMode-option' onclick="gameui.action('GODMODE', {god: JSON.stringify({action: 'fleet', color: '${ship.color}', id: ${ship.id}, delta: +50})});">+50</li>`, menu);
//
									dojo.place(`<li class='godMode-option'><HR></li>`, menu);
									dojo.place(`<li class='godMode-option'>CANCEL</li>`, menu);
//
									dojo.connect(menu, 'onclick', () => dojo.destroy(menu));
								}
							});
//
// GOD MODE
//

						}
						this.ERAfleet.addTarget(node);
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
//			console.info('reveal', fleet);
//
			let node = $(`ERAship-${fleet.id}`);
			if (node)
			{
				dojo.setAttr(node, 'fleet', fleet.fleet);
				if (+fleet.ships > 0) dojo.setAttr(node, 'ships', fleet.ships);
				if (/^\d:([+-]\d){3}$/.test(dojo.getAttr(node, 'location'))) this.arrange(dojo.getAttr(node, 'location'));
			}
		},
		homeStarEvacuation: function (homeStar, to)
		{
//			console.info('homeStarEvacuation', homeStar, to);
//
			const node = $(`ERAhomeStar-${homeStar}`);
			dojo.style(node, 'left', this.board.hexagons[to].x - node.clientWidth / 2 + 'px');
			dojo.style(node, 'top', this.board.hexagons[to].y - node.clientHeight / 2 + 'px');
			const from = dojo.getAttr(node, 'location');
			dojo.setAttr(node, 'location', to);
			this.bgagame.counters.arrange(from);
			this.bgagame.counters.arrange(to);
		},
		move: function (ships, to)
		{
//			console.info('moveShips', ships, to);
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
		},
		remove: function (ship)
		{
//			console.info('removeShip', ship);
//
			this.ERAfleet.removeTarget(`ERAship-${ship.id}`);
			dojo.query(`#ERAboard .ERAship[ship=${ship.id}]`).remove();
//
			if (/^\d:([+-]\d){3}$/.test(ship.location)) this.arrange(ship.location);
		},
		arrange: function (location)
		{
			let index = fleet = 0;
			nodes = Array.from(dojo.query(`.ERAship[location='${location}']`, 'ERAboard')).sort((a, b) => {
				const score_a = (dojo.getAttr(a, 'color') === this.bgagame.color ? 1 : 0) * 10 + {null: -1, '?': 0, A: 1, B: 2, C: 3, D: 4, E: 5}[dojo.getAttr(a, 'fleet')];
				const score_b = (dojo.getAttr(b, 'color') === this.bgagame.color ? 1 : 0) * 10 + {null: -1, '?': 0, A: 1, B: 2, C: 3, D: 4, E: 5}[dojo.getAttr(b, 'fleet')];
				return score_b - score_a;
			});
//
			for (const node of nodes)
			{
				if (dojo.hasAttr(node, 'fleet'))
				{
					dojo.style(node, 'transform', `rotate(calc(-1 * var(--ROTATE))) translate(${STEP * (index - nodes.length / 2) * node.clientWidth / 10}px, ${ -10 + STEP * (index - nodes.length / 2) * node.clientHeight / 10}px)`);
					dojo.style(node, 'z-index', 200 + index);
					fleet++;
				}
				else
				{
					dojo.style(node, 'transform', `scale(25%) rotate(calc(-1 * var(--ROTATE))) translate(${STEP * (index - nodes.length / 2) * node.clientWidth / 10}px, ${ -10 + STEP * (index - nodes.length / 2) * node.clientHeight / 10}px)`);
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
			if (paths === undefined) return;
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
				SVGpath.setAttribute('class', 'ERApath');
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
				if ('wormhole' in paths[location]) svg.appendChild(this.board.drawHexagon(this.board.hexagons[location], "#" + 'FFFFFF' + 'C0'));
				if ('stargate' in paths[location]) svg.appendChild(this.board.drawHexagon(this.board.hexagons[location], "#" + 'FFFFFF' + 'C0'));
//
				const SVGshape = this.board.drawHexagon(this.board.hexagons[location], 'none');
				dojo.setAttr(SVGshape, 'location', location);
				svg.appendChild(SVGshape);
//
				dojo.connect(SVGshape, 'click', (event) => {
					dojo.stopEvent(event);
					let ships = dojo.query(`#ERAboard .ERAship.ERAselected`).reduce((L, node) => [...L, +node.getAttribute('ship')], []);
					let location = dojo.getAttr(event.target, 'location');
					if (location !== possible[0]) this.bgagame.action('move', {color: color, location: JSON.stringify(location), ships: JSON.stringify(ships)});
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
			if (this.bgagame.board.dragging === true) return;
//
			const ship = event.currentTarget;
			const location = dojo.getAttr(ship, 'location');
			const color = dojo.getAttr(ship, 'color');
//
			if (this.bgagame.isCurrentPlayerActive())
			{
//
				if (dojo.hasClass(ship, 'ERAselectable'))
				{
					dojo.stopEvent(event);
//
					if (this.bgagame.gamedatas.gamestate.name === 'homeStarEvacuation') return this.bgagame.homeStarEvacuation(location);
					if (this.bgagame.gamedatas.gamestate.name === 'emergencyReserve') return this.bgagame.buildShips(location);
					if (this.bgagame.gamedatas.gamestate.name === 'remoteViewing') return this.bgagame.remoteViewing('fleet', ship);
					if (this.bgagame.gamedatas.gamestate.name === 'combatChoice') return this.bgagame.combatChoice(location);
					if (this.bgagame.gamedatas.gamestate.name === 'gainStar') return this.bgagame.gainStar(location);
					if (this.bgagame.gamedatas.gamestate.name === 'gainStar+') return this.bgagame.gainStar(location);
					if (this.bgagame.gamedatas.gamestate.name === 'buildShips') return this.bgagame.buildShips(location);
					if (this.bgagame.gamedatas.gamestate.name === 'growPopulation') return this.bgagame.growPopulation(location);
					if (this.bgagame.gamedatas.gamestate.name === 'growPopulation+') return this.bgagame.growPopulation(location);
					if (this.bgagame.gamedatas.gamestate.name === 'bonusPopulation') return this.bgagame.bonusPopulation(location);
					if (this.bgagame.gamedatas.gamestate.name === 'teleportPopulation') return this.bgagame.teleportPopulation(location);
					if (this.bgagame.gamedatas.gamestate.name === 'removePopulation') return this.bgagame.removePopulation(location);
					if (this.bgagame.gamedatas.gamestate.name === 'planetaryDeathRay') return this.bgagame.planetaryDeathRay(location, ship);
					if (this.bgagame.gamedatas.gamestate.name === 'fleets')
					{
						dojo.query(`#ERAboard .ERAship[color='${color}']:not([location='${location}'])`).removeClass('ERAselected');
						dojo.query(`#ERAfleets .ERAship`).removeClass('ERAselected');
//
						if (dojo.hasAttr(ship, 'fleet'))
						{
							dojo.addClass('ERAfleets', 'ERAhide');
							dojo.query(`#ERAboard .ERAship[color='${color}']`).removeClass('ERAselected');
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
//
						if (dojo.getAttr(ship, 'fleet')) return this.bgagame.fleets(location, 'fleet', dojo.query(`#ERAboard .ERAship.ERAselected`));
						return this.bgagame.fleets(location, 'ships', dojo.query(`#ERAboard .ERAship.ERAselected`));
					}
				}
			}
		}
	}
	);
});
