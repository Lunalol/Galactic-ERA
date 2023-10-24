define(["dojo", "dojo/_base/declare"], function (dojo, declare)
{
	return declare("Factions", null,
	{
		constructor: function (bgagame)
		{
			console.log('factions constructor');
//
// Reference to BGA game
//
			this.bgagame = bgagame;
//
// Setup DP
//
			const galacticStoryNode = dojo.place(`<img id='ERAgalacticStory' src='${g_gamethemeurl}img/galacticStories/${this.bgagame.gamedatas.galacticStory}.png' draggable='false'>`, 'ERA-DP');
			const galacticStory = bgagame.GALATIC_STORIES[this.bgagame.gamedatas.galacticStory];
//
			let html = '<div style="display:grid;grid-template-columns:1fr 5fr;max-width:50vw;outline:1px solid white;">';
			for (let [ERA, string] of Object.entries({1: _('First Era'), 2: _('Second Era'), 3: _('Third Era')}))
			{
				html += '<div style="padding:12px;text-align:center;outline:1px solid grey;">' + (string) + '</div>';
				html += '<div style="padding:12px;outline:1px solid grey;">';
				for (let string of galacticStory[ERA]) html += '<div style="text-align:justify;">◇&#x2005;' + string + '</div>';
				html += '</div>';
			}
//
			new dijit.Tooltip({connectId: galacticStoryNode, showDelay: 500, hideDelay: 0, label: html, position: ['above']});
//
		},
		update: function (faction)
		{
			console.log('updateFaction', faction);
//
			if ('starPeople' in faction)
			{
				let node = dojo.query(`#ERAfaction-${faction.color} .ERAstarPeople,.ERApanel-${faction.color} .ERAstarPeople`).forEach((node) =>
				{
					dojo.setAttr(node, 'starPeople', faction.starPeople);
					if (faction.starPeople === 'none')
					{
						dojo.setAttr(node, 'STO', `${g_gamethemeurl}img/starPeoples/none.png`);
						dojo.setAttr(node, 'STS', `${g_gamethemeurl}img/starPeoples/none.png`);
						dojo.style(node, 'pointer-events', 'none');
					}
					else
					{
						dojo.setAttr(node, 'STO', `${g_gamethemeurl}img/starPeoples/${faction.starPeople}.STO.jpg`);
						dojo.setAttr(node, 'STS', `${g_gamethemeurl}img/starPeoples/${faction.starPeople}.STS.jpg`);
						dojo.style(node, 'pointer-events', '');
					}
					if ('alignment' in faction)
					{
						dojo.toggleClass(node, 'ERA-STS', faction.alignment === 'STS');
						if (dojo.hasClass(node, 'ERA-STS')) dojo.setAttr(node.querySelector('img'), 'src', dojo.getAttr(node, 'STS'));
						else dojo.setAttr(node.querySelector('img'), 'src', dojo.getAttr(node, 'STO'));
					}
				});
			}
//
			if ('domination' in faction)
			{
				dojo.empty(`ERAdominationCards-${faction.color}`);
				for (let index in faction.domination)
				{
					const domination = faction.domination[index];
					const node = dojo.place(this.bgagame.format_block('ERAdominationCard', {index: index, domination: domination, owner: faction.color}), `ERAdominationCards-${faction.color}`);
					dojo.setAttr(node.querySelector('img'), 'src', `${g_gamethemeurl}img/dominationCards/${domination}.jpg`);
					dojo.connect(node, 'click', (event) => {
						dojo.stopEvent(event);
						if (dojo.getAttr(event.currentTarget, 'domination') !== 'back') this.bgagame.focus(event.currentTarget);
						else if (this.bgagame.gamedatas.gamestate.name === 'remoteViewing') this.bgagame.remoteViewing('dominationCard', event.currentTarget);
					});
					dojo.connect(node, 'transitionend', () => dojo.style(node, {'pointer-events': '', 'z-index': ''}));
				}
			}
//
			if ('DP' in faction)
			{
				const player_id = +this.bgagame.gamedatas.factions[faction.color].player_id;
				if (player_id === -2)
				{
					dojo.query('.ERAcounter-population', 'ERAoffboard').remove();
					for (let i = 0; i < faction.DP; i++)
					{
						let x = 20 + 30 * i;
						let y = 75;
						let node = dojo.place(this.bgagame.format_block('ERAcounter', {id: faction.color + '-population', color: faction.color, type: 'populationDisc', location: 'offbard'}), 'ERAoffboard');
						dojo.style(node, 'position', 'absolute');
						dojo.style(node, 'left', x + 'px');
						dojo.style(node, 'top', y + 'px');
						dojo.style(node, 'transform', 'scale(12.5%)');
						dojo.style(node, 'transform-origin', 'left top');
					}
				}
				if (player_id in this.bgagame.gamedatas.players)
				{
					if (player_id in this.bgagame.scoreCtrl) this.bgagame.scoreCtrl[player_id].setValue(faction.DP);
//
//					dojo.query(`.ERAcounter-cylinder.ERAcounter-${faction.color}`, 'ERA-DP').remove();
					let node = $(`ERAcounter-${faction.color}-DP`);
					if (!node) node = dojo.place(this.bgagame.format_block('ERAcounter', {id: faction.color + '-DP', color: faction.color, type: 'cylinder', location: faction.DP}), 'ERA-DP');
					dojo.style(node, 'position', 'absolute');
					dojo.setAttr(node, 'location', faction.DP);
//
					let x = 00, dx = 49;
					let y = 0, dy = 50;
					let DP = faction.DP % 50;
					if (DP < 0) DP += 50;
					let score = 0;
					while (score < DP)
					{
						if (score < 15) x += dx;
						else if (score < 25) y += dy;
						else if (score < 40) x -= dx;
						else if (score < 50) y -= dy;
						score += 1;
					}
					dojo.style(node, 'left', x + 'px');
					dojo.style(node, 'top', y + 'px');
//
					let nodes = dojo.query(`#ERA-DP .ERAcounter-cylinder`);
					let index = {};
					for (let node of nodes)
					{
						let DP = dojo.getAttr(node, 'location');
						if (!(DP in index)) index[DP] = 0;
						dojo.style(node, 'transform', `scale(.75) translate(+${index[DP] * node.clientWidth / 10}px, -${index[DP] * node.clientHeight / 5}px) `);
						dojo.style(node, 'z-index', index[DP] + 100);
						dojo.style(node, 'pointer-events', 'all');
						dojo.setAttr(node, 'title', dojo.string.substitute(_('${DP} destiny points'), {DP: DP}));
						index[DP]++;
					}
				}
			}
//
			if ('population' in faction)
			{
				const nodePopulationTrack = $(`ERApopulationTrack-${faction.color}`);
				if (nodePopulationTrack)
				{
//					dojo.query('.ERAcounter-populationDisc', nodePopulationTrack).remove();
					dojo.empty(nodePopulationTrack);
					for (let population = 1 + +faction.population; population < 40; population++)
					{
						let node = dojo.place(this.bgagame.format_block('ERAcounter', {id: faction.color + '-population', color: faction.color, type: 'populationDisc', location: 'populationTrack'}), nodePopulationTrack);
						dojo.setAttr(node, 'population', population);
						dojo.style(node, 'position', 'absolute');
						if (population === 0) [x, y] = [543, 122];
						else [x, y] = [47.7 * ((39 - population) % 13) - 78, 81 * (Math.floor((39 - population) / 13)) - 40];
						dojo.style(node, 'left', x + 'px');
						dojo.style(node, 'top', y + 'px');
						dojo.style(node, 'transform', 'scale(20%)');
					}
				}
				$(`ERApopulation-${faction.color}`).innerHTML = 39 - faction.population;
			}
//
			const nodeTechnologiesTrack = $(`ERAtechTrack-${faction.color}`);
			if (nodeTechnologiesTrack)
			{
				for (let technology of ['Military', 'Spirituality', 'Propulsion', 'Robotics', 'Genetics'])
				{
					if (technology in faction)
					{
						let node = $(`ERAcounter-${faction.color}-${technology}`);
						if (!node) node = dojo.place(this.bgagame.format_block('ERAcounter', {id: faction.color + '-' + technology, color: faction.color, type: 'cube', location: technology}), nodeTechnologiesTrack);
						dojo.setAttr(node, 'title', _(technology) + ' ' + faction[technology]);
						dojo.setAttr(node, 'level', faction[technology]);
						dojo.style(node, 'position', 'absolute');
						dojo.style(node, 'top', (['Military', 'Spirituality', 'Propulsion', 'Robotics', 'Genetics'].indexOf(technology) * 92.5 + 85) + 'px');
						dojo.style(node, 'left', [0, 55, 119, 193, 281, 388, 519][faction[technology]] + 'px');
						dojo.style(node, 'transform', 'scale(75%)');
					}
				}
				if ('advancedFleetTactics' in faction)
				{
					const advancedFleetTactics = JSON.parse(faction.advancedFleetTactics);
					for (fleet in advancedFleetTactics)
					{
						const tactics = advancedFleetTactics[fleet];
						if (tactics)
						{
							const node = dojo.place(this.bgagame.format_block('ERAcounter', {id: '2x', color: faction.color, type: 'tactics', location: fleet}), nodeTechnologiesTrack);
							dojo.setAttr(node, 'tactics', tactics);
							dojo.style(node, 'position', 'absolute');
							dojo.style(node, 'left', 798 + 'px');
							dojo.style(node, 'top', {A: 18, B: 122, C: 225, D: 329, E: 433}[fleet] + 'px');
						}
					}
				}
			}
			dojo.query(`#ERAtechnologies-${faction.color} [technology]`).forEach((node) => {
				const technology = dojo.getAttr(node, 'technology');
				if (technology in faction)
				{
					dojo.setAttr(node, 'level', faction[technology]);
//
					let html = '';
					for (let i = 1; i <= 6; i++) html += `<span class='${i <= faction[technology] ? 'circleBlack' : 'circleWhite'}'>${i}</span>`;
					node.children[0].innerHTML = html;
				}
			});
//
			if ('order' in faction) dojo.query(`.ERAorder[faction=${faction.color}]`).forEach((node) => dojo.setAttr(node, 'order', faction.order));
//
			if ('atWar' in faction)
			{
				const atWar = JSON.parse(faction.atWar);
				dojo.query(`.ERAcounter-peace[color='${faction.color}']`).forEach((node) => dojo.toggleClass(node, 'ERAhide', atWar.includes(dojo.getAttr(node, 'on'))));
				dojo.query(`.ERAcounter-war[color='${faction.color}']`).forEach((node) => dojo.toggleClass(node, 'ERAhide', !atWar.includes(dojo.getAttr(node, 'on'))));
			}
//
			if ('ships' in faction) dojo.query(`.ERAships[faction=${faction.color}]`).forEach((node) => node.innerHTML = faction.ships);
			if ('emergencyReserve' in faction) dojo.query(`.ERAemergencyReserve-${faction.color}`).toggleClass('ERAhide', faction.emergencyReserve !== '1');
//
// Panels order
//
//			for (let node of dojo.query('.ERAorder', 'player_boards').sort((a, b) => dojo.getAttr(a, 'order') - dojo.getAttr(b, 'order')))
//			{
//				const faction = dojo.getAttr(node, 'faction');
//				$('player_boards').insertBefore($(`overall_player_board_${this.bgagame.gamedatas.factions[faction].player_id}`), null);
//			}
//
		}
	}
	);
});
