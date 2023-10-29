define(["dojo", "dojo/_base/declare"], function (dojo, declare)
{
	return declare("Counters", null,
	{
		constructor: function (bgagame)
		{
			console.log('counters constructor');
//
// Reference to BGA game
//
			this.bgagame = bgagame;
			this.board = bgagame.board;
		},
		place: function (counter)
		{
			console.info('placeCounter', counter);
//
			const node = dojo.place(this.bgagame.format_block('ERAcounter', {id: counter.id, color: counter.color, type: counter.type, location: counter.location}), 'ERAboard');
//
			dojo.style(node, 'position', 'absolute');
			dojo.style(node, 'left', (this.board.hexagons[counter.location].x - node.clientWidth / 2) + 'px');
			dojo.style(node, 'top', (this.board.hexagons[counter.location].y - node.clientHeight / 2) + 'px');
//
			switch (counter.type)
			{
				case 'wormhole':
					{
						const color = [null, 'blue', 'blue', 'gold', 'gold', 'purple', 'purple'][dojo.query('#ERAboard .ERAcounter-wormhole').length];
						dojo.addClass(node, `ERAcounter-${color}`);
//
						const center = this.board.hexagons[counter.location[0] + ':+0+0+0'];
						const dx = 0.18 * (this.board.hexagons[counter.location].x - center.x);
						const dy = 0.18 * (this.board.hexagons[counter.location].y - center.y);
						dojo.style(node, 'transform', `translate(${dx}px, ${dy}px)`);
					}
					break;
				case 'star':
					{
						dojo.style(node, 'transform', `rotate(calc(-1 * var(--ROTATE)))`);
						dojo.style(node, 'z-index', 100);
						node.addEventListener('animationend', (event) => {
							if (event.animationName === 'flip')
							{
								dojo.style(node, 'animation', `unflip ${DELAY / 2}ms`);
								dojo.addClass(node, `ERAcounter-${dojo.getAttr(node, 'back')}`);
//								dojo.removeAttr(node, 'back');
							}
							else dojo.style(node, 'animation', '');
						});
						dojo.connect(node, 'click', this, 'click');
					}
					break;
				case 'relic':
					{
						dojo.style(node, 'transform', `rotate(calc(-1 * var(--ROTATE))) translate(32px, -32px)`);
						dojo.style(node, 'z-index', 100);
						node.addEventListener('animationend', (event) => {
							if (event.animationName === 'flip')
							{
								dojo.style(node, 'animation', `unflip ${DELAY / 2}ms`);
								dojo.addClass(node, `ERAcounter-${dojo.getAttr(node, 'back')}`);
//								dojo.removeAttr(node, 'back');
							}
							else dojo.style(node, 'animation', '');
						});
						dojo.connect(node, 'click', this, 'click');
					}
					break;
				case 'populationDisc':
					dojo.connect(node, 'click', this, 'click');
					break;
			}
//
			if (/^\d:([+-]\d){3}$/.test(counter.location)) this.arrange(counter.location);
//
			return node;
		},
		flip: function (counter)
		{
			let node = $(`ERAcounter-${counter.id}`);
			if (node)
			{
				dojo.style(node, 'animation', `flip ${DELAY / 2}ms`);
				dojo.setAttr(node, 'back', counter.type);
			}
		},
		arrange: function (location)
		{
			index = 0;
			nodes = dojo.query(`#ERAboard .ERAcounter[location='${location}'].ERAcounter-populationDisc`);
			for (const node of nodes)
			{
				dojo.style(node, 'transform', `scale(25%) rotate(calc(-1 * var(--ROTATE))) translateY(-${index * node.clientHeight / 5}px)`);
				dojo.style(node, 'z-index', index + 100);
				index++;
			}
			homeStar = $('ERAboard').querySelector(`.ERAhomeStar[location='${location}']`);
			if (homeStar)
			{
				dojo.style(homeStar, 'transform', `scale(39%) rotate(calc(-1 * var(--ROTATE))) translate(-2px, -${50 + index * 32}px)`);
				dojo.style(homeStar, 'z-index', index + 100);
			}
//
		},
		remove: function (counter)
		{
			console.info('removeCounter', counter);
//
			dojo.query(`#ERAcounter-${counter.id}`).remove();
			this.arrange(counter.location);
//
		},
		click: function (event)
		{
			if (this.bgagame.board.dragging === true) return;
//
			const counter = event.currentTarget;
			const location = dojo.getAttr(counter, 'location');
//
			if (this.bgagame.isCurrentPlayerActive())
			{
				if (dojo.hasClass(counter, 'ERAselectable'))
				{
					dojo.stopEvent(event);
					if (this.bgagame.gamedatas.gamestate.name === 'homeStarEvacuation') return this.bgagame.homeStarEvacuation(location);
					if (this.bgagame.gamedatas.gamestate.name === 'remoteViewing') return this.bgagame.remoteViewing('counter', counter);
					if (this.bgagame.gamedatas.gamestate.name === 'combatChoice') return this.bgagame.combatChoice(location);
					if (this.bgagame.gamedatas.gamestate.name === 'gainStar') return this.bgagame.gainStar(location);
					if (this.bgagame.gamedatas.gamestate.name === 'buildShips') return this.bgagame.buildShips(location);
					if (this.bgagame.gamedatas.gamestate.name === 'growPopulation') return this.bgagame.growPopulation(location);
					if (this.bgagame.gamedatas.gamestate.name === 'bonusPopulation') return this.bgagame.bonusPopulation(location);
					if (this.bgagame.gamedatas.gamestate.name === 'teleportPopulation') return this.bgagame.teleportPopulation(location);
					if (this.bgagame.gamedatas.gamestate.name === 'planetaryDeathRay') return this.bgagame.planetaryDeathRay(location, counter);
				}
			}
		}
	}
	);
});
