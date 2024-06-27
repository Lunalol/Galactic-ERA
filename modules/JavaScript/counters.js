/* global dijit */

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
//
			this.STARS = {
				UNINHABITED: {
					type: _('(uninhabited)'),
					BOTH: _('<B>Colonize:</B> This option is available to players of both alignments. The player must have 1 ship in the same hex as this star. The player removes the star counter and places 1 population disc of their own color there.')},
				PRIMITIVE: {
					type: _('(primitive neutral)'),
					STO: _('STO players cannot take this star'),
					STS: _('<B>Subjugate:</B> Only 1 ship at this star is needed for this. The STS player removes the star counter and places 2 of their population discs there.')},
				ADVANCED: {
					type: _('(advanced neutral)'),
					STO: _('<B>Ally:</B> Only 1 ship at this star is needed for this. The STO player removes the star counter and places 3 of their population discs there.'),
					STS: _('<B>Conquer:</B> STS players can only “conquer” this star. It is considered to have 3 population discs. Thus 4 ships are needed to conquer it. The player removes the star counter and places 1 population disc there.')}
			};
//
			this.NeutralStarTooltips = new dijit.Tooltip({
				showDelay: 500, hideDelay: 0,
				getContent: (node) =>
				{
					const location = dojo.getAttr(node, 'location');
					const type = dojo.getAttr(node, 'back');
//
					let html = `<H1 style='font-family:ERA;'><div class='ERAcounter ERAcounter-star' style='display:inline-block;vertical-align:middle;margin:5px;'></div>${bgagame.gamedatas.sectors[+location[0]].shape[location.substring(2)].star}</H1><BR>`;
//
					html += '<div style="display:grid;grid-template-columns:auto 5fr 5fr;max-width:50vw;outline:1px solid white;">';
					html += '<div style="padding:12px;text-align:center;outline:1px solid grey;font-style:italic;font-weight:bold;">' + _('Star') + '</div>';
					html += '<div style="padding:12px;text-align:center;outline:1px solid grey;font-style:italic;font-weight:bold;">' + _('Option for STO players') + '</div>';
					html += '<div style="padding:12px;text-align:center;outline:1px solid grey;font-style:italic;font-weight:bold;">' + _('Option for STS players') + '</div>';
//
					for (let [star, description] of Object.entries(this.STARS))
					{
						if (!type || type === star)
						{
							html += `<div style="padding:12px;outline:1px solid grey;"><div class='ERAcounter ERAcounter-star ERAcounter-${star}'></div><div style="text-align:center;font-style:italic;font-weight:bold;">${_(description.type)}</div></div>`;
							if ('BOTH' in description) html += '<div style="padding:12px;text-align:justify;outline:1px solid grey;grid-column:span 2;">' + _(description.BOTH) + '</div>';
							else
							{
								html += '<div style="padding:12px;text-align:justify;outline:1px solid grey;">' + _(description.STO) + '</div>';
								html += '<div style="padding:12px;text-align:justify;outline:1px solid grey;">' + _(description.STS) + '</div>';
							}
						}
					}
					return html;
				}
			});
//
		},
		place: function (counter)
		{
//			console.info('placeCounter', counter);
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
						this.NeutralStarTooltips.addTarget(node);
					}
					break;
				case 'relic':
					{
						dojo.style(node, 'transform', `rotate(calc(-1 * var(--ROTATE))) translate(32px, -32px)`);
						dojo.style(node, 'z-index', 100);
						this.bgagame.addTooltip(node.id, _('Ancient Relic'), '');
						node.addEventListener('animationend', (event) => {
							if (event.animationName === 'flip')
							{
								dojo.style(node, 'animation', `unflip ${DELAY / 2}ms`);
								dojo.addClass(node, `ERAcounter-${dojo.getAttr(node, 'back')}`);
//								dojo.removeAttr(node, 'back');
								this.bgagame.addTooltip(node.id, _('Ancient Relic: ') + this.bgagame.RELICS[dojo.getAttr(node, 'back')][0], this.bgagame.RELICS[dojo.getAttr(node, 'back')][1].reduce((html, e) => html + `<div>${e}</div>`, ''));
							}
							else dojo.style(node, 'animation', '');
						});
						dojo.connect(node, 'click', this, 'click');
					}
					break;
				case 'populationDisc':
					dojo.connect(node, 'click', this, 'click');
					this.bgagame.ERAstarTooltips.addTarget(node);
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
				dojo.style(homeStar, 'transform', `scale(30%) rotate(calc(-1 * var(--ROTATE))) translate(3px, -${50 + index * 42}px)`);
				dojo.style(homeStar, 'z-index', index + 100);
			}
//
		},
		remove: function (counter)
		{
//			console.info('removeCounter', counter);
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
					if (this.bgagame.gamedatas.gamestate.name === 'emergencyReserve') return this.bgagame.buildShips(location);
					if (this.bgagame.gamedatas.gamestate.name === 'remoteViewing') return this.bgagame.remoteViewing('counter', counter);
					if (this.bgagame.gamedatas.gamestate.name === 'combatChoice') return this.bgagame.combatChoice(location);
					if (this.bgagame.gamedatas.gamestate.name === 'gainStar') return this.bgagame.gainStar(location);
					if (this.bgagame.gamedatas.gamestate.name === 'gainStar+') return this.bgagame.gainStar(location);
					if (this.bgagame.gamedatas.gamestate.name === 'buildShips') return this.bgagame.buildShips(location);
					if (this.bgagame.gamedatas.gamestate.name === 'growPopulation') return this.bgagame.growPopulation(location);
					if (this.bgagame.gamedatas.gamestate.name === 'growPopulation+') return this.bgagame.growPopulation(location);
					if (this.bgagame.gamedatas.gamestate.name === 'bonusPopulation') return this.bgagame.bonusPopulation(location);
					if (this.bgagame.gamedatas.gamestate.name === 'teleportPopulation') return this.bgagame.teleportPopulation(location);
					if (this.bgagame.gamedatas.gamestate.name === 'removePopulation') return this.bgagame.removePopulation(location);
					if (this.bgagame.gamedatas.gamestate.name === 'planetaryDeathRay') return this.bgagame.planetaryDeathRay(location, counter);
				}
			}
		}
	}
	);
});
