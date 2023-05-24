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
			let galacticStoryNode = dojo.place(`<img id='ERAgalacticStory' title='${_('Galactic story')}' src='${g_gamethemeurl}img/galacticStories/${this.bgagame.gamedatas.galacticStory}.png' draggable='false'>`, 'ERA-DP');
//
		},
		update: function (faction)
		{
			console.log('updateFaction', faction);
//
			if ('domination' in faction)
			{
				dojo.empty(`ERAdominationCards-${faction.color}`);
				for (let domination of faction.domination)
				{
					let node = dojo.place(this.bgagame.format_block('ERAdominationCard', {domination: domination}), `ERAdominationCards-${faction.color}`);
					dojo.setAttr(node.querySelector('img'), 'src', `${g_gamethemeurl}img/dominationCards/${domination}.jpg`);
				}
			}
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
						dojo.toggleClass(node, 'ERA-STS', faction.alignment === 'STS')
						if (dojo.hasClass(node, 'ERA-STS')) dojo.setAttr(node.querySelector('img'), 'src', dojo.getAttr(node, 'STS'));
						else dojo.setAttr(node.querySelector('img'), 'src', dojo.getAttr(node, 'STO'));
					}
				});
			}
//
			if ('DP' in faction)
			{
				let player_id = +this.bgagame.gamedatas.factions[faction.color].player_id;
				if (player_id === -2)
				{
					dojo.query('.ERAcounter-population', 'ERAoffboard').remove();
					for (let i = 0; i < faction.DP; i++)
					{
						let x = 20 + 30 * i;
						let y = 75;
						let node = dojo.place(this.bgagame.format_block('ERAcounter', {id: faction.color + '-population', color: faction.color, type: 'populationDisk', location: 'offbard'}), 'ERAoffboard');
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
					dojo.query(`.ERAcounter-cylinder.ERAcounter-${faction.color}`, 'ERA-DP').remove();
					let node = dojo.place(this.bgagame.format_block('ERAcounter', {id: faction.color + '-DP', color: faction.color, type: 'cylinder', location: faction.DP}), 'ERA-DP');
					dojo.style(node, 'position', 'absolute');
					dojo.style(node, 'left', (faction.DP * 49) + 'px');
//
					let nodes = dojo.query(`#ERA-DP .ERAcounter-cylinder`);
					let index = {};
					for (let node of nodes)
					{
						let DP = dojo.getAttr(node, 'location');
						if (!(DP in index)) index[DP] = 0;
						dojo.style(node, 'transform', `scale(.75) translate(+${index[DP] * node.clientWidth / 10}px, -${index[DP] * node.clientHeight / 5}px) `);
						dojo.style(node, 'z-index', index[DP] + 100);
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
//					dojo.query('.ERAcounter-populationDisk', nodePopulationTrack).remove();
					dojo.empty(nodePopulationTrack);
					for (let population = 1 + +faction.population; population < 40; population++)
					{
						let node = dojo.place(this.bgagame.format_block('ERAcounter', {id: faction.color + '-population', color: faction.color, type: 'populationDisk', location: 'populationTrack'}), nodePopulationTrack);
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
						dojo.query(`.ERAcounter-cube[location='${technology}']`, nodeTechnologiesTrack).remove();
						let node = dojo.place(this.bgagame.format_block('ERAcounter', {id: faction.color + '-technology', color: faction.color, type: 'cube', location: technology}), nodeTechnologiesTrack);
						dojo.setAttr(node, 'title', _(technology) + ' ' + faction[technology]);
						dojo.setAttr(node, 'level', faction[technology]);
						dojo.style(node, 'position', 'absolute');
						dojo.style(node, 'top', (['Military', 'Spirituality', 'Propulsion', 'Robotics', 'Genetics'].indexOf(technology) * 92.5 + 85) + 'px');
						dojo.style(node, 'left', [0, 55, 119, 193, 281, 388, 519][faction[technology]] + 'px');
						dojo.style(node, 'transform', 'scale(75%)');
					}
				}
			}
			dojo.query(`#ERAtechnologies-${faction.color} .ERAtechnology`).forEach((node) => {
				const technology = dojo.getAttr(node, 'technology');
				if (technology in faction)
				{
					let html = '';
					for (let i = 1; i <= 6; i++) html += `<span class='${i <= faction[technology] ? 'circleBlack' : 'circleWhite'}'>${i}</span>`;
					node.innerHTML = html;
				}
			});
//
			if ('order' in faction) dojo.setAttr(`ERAorder-${faction.color}`, 'order', faction.order);
//
			if ('atWar' in faction)
			{
				const atWar = JSON.parse(faction.atWar);
				dojo.query('.ERAcounter-peace', `ERAstatus-${faction.color}`).forEach((node) => dojo.toggleClass(node, 'ERAhide', atWar.includes(dojo.getAttr(node, 'color'))));
				dojo.query('.ERAcounter-war', `ERAstatus-${faction.color}`).forEach((node) => dojo.toggleClass(node, 'ERAhide', !atWar.includes(dojo.getAttr(node, 'color'))));
			}

		}
	}
	);
});
