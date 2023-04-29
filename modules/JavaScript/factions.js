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
			let galacticStoryNode = dojo.place(`<img id='ERAgalacticStory' title='${_('Galactic story')}' src='${g_gamethemeurl}img/galacticStories/${this.bgagame.gamedatas.galacticStory}.png'/>`, 'ERA-DP');
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
				let node = document.querySelector(`#ERAfaction-${faction.color}>.ERAstarPeople`);
//
				if (node)
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
				}
				if ('alignment' in faction)
				{
					if (faction.alignment === 'STS') dojo.setAttr(node.querySelector('img'), 'src', dojo.getAttr(node, 'STS'));
					else dojo.setAttr(node.querySelector('img'), 'src', dojo.getAttr(node, 'STO'));
				}
			}
//
			if ('DP' in faction)
			{
				if (faction.player_id in this.bgagame.scoreCtrl) this.bgagame.scoreCtrl[faction.player_id].setValue(faction.DP);
//
				dojo.query(`#ERA-DP .ERAcounter-cylinder.ERAcounter-${faction.color}`).remove();
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
//
			if ('population' in faction)
			{
				dojo.query(`#ERApopulationTrack-${faction.color}>.ERAcounter-populationDisk`).remove();
				for (let population = 1 + +faction.population; population < 40; population++)
				{
					let node = dojo.place(this.bgagame.format_block('ERAcounter', {id: faction.color + '-population', color: faction.color, type: 'populationDisk', location: 'populationTrack'}), `ERApopulationTrack-${faction.color}`);
					dojo.setAttr(node, 'population', population);
					dojo.style(node, 'position', 'absolute');
					if (population === 0) [x, y] = [543, 122];
					else [x, y] = [47.7 * ((39 - population) % 13) - 78, 81 * (Math.floor((39 - population) / 13)) - 40];
					dojo.style(node, 'left', x + 'px');
					dojo.style(node, 'top', y + 'px');
					dojo.style(node, 'transform', 'scale(20%)');
				}
//
//				let nodes = dojo.query(`.ERApopulationTrack .ERAcounter-populationDisk`);
//				let index = {};
//				for (let node of nodes)
//				{
//					let population = dojo.getAttr(node, 'population');
//					if (!(population in index)) index[population] = 0;
//					dojo.style(node, 'transform', `scale(20%) translateY(-${index[population] * node.clientHeight / 10}px) `);
//					dojo.style(node, 'z-index', index[population] + 100);
//					index[population]++;
//				}
			}
//
			for (let technology of ['Military', 'Spirituality', 'Propulsion', 'Robotics', 'Genetics'])
			{
				if (technology in faction)
				{
					dojo.query(`#ERAtechTrack-${faction.color}>.ERAcounter-cube[location='${technology}']`).remove();
					let node = dojo.place(this.bgagame.format_block('ERAcounter', {id: faction.color + '-technology', color: faction.color, type: 'cube', location: technology}), `ERAtechTrack-${faction.color}`);
					dojo.setAttr(node, 'level', faction[technology]);
					dojo.style(node, 'position', 'absolute');
					dojo.style(node, 'top', (['Military', 'Spirituality', 'Propulsion', 'Robotics', 'Genetics'].indexOf(technology) * 92.5 + 85) + 'px');
					dojo.style(node, 'left', [0, 55, 119, 193, 281, 388, 519][faction[technology]] + 'px');
					dojo.style(node, 'transform', 'scale(75%)');
//
//					let nodes = dojo.query(`.ERAtechTrack .ERAcounter-cube`);
//					let index = {};
//					for (let node of nodes)
//					{
//						let technologyLevel = dojo.getAttr(node, 'location') + dojo.getAttr(node, 'level');
//						if (!(technologyLevel in index)) index[technologyLevel] = 0;
//						dojo.style(node, 'transform', `scale(75%) translate(+${index[technologyLevel] * node.clientWidth / 10}px, -${index[technologyLevel] * node.clientHeight / 5}px) `);
//						dojo.style(node, 'z-index', index[technologyLevel] + 100);
//						index[technologyLevel]++;
//					}
				}
			}
//			if ('order' in faction) dojo.setAttr(`ERAorder-${faction.color}`, 'order', faction.order);
		}
	}
	);
});
