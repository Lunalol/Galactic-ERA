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
			dojo.place(`<img id='ERAgalacticStory' src='${g_gamethemeurl}img/galacticStories/${this.bgagame.gamedatas.galacticStory}.png'/>`, 'ERA-DP');
//
// Setup domination cards holder
//
			dojo.place(this.bgagame.format_block('ERAdominationCards', {}), 'game_play_area');
//
// Setup population & technology tracks
//
			dojo.place(this.bgagame.format_block('ERApanel', {color: 'FF3333'}), 'ERApanels');
//
		},
		update: function (faction)
		{
			console.log('updateFaction', faction);
//
			if ('domination' in faction)
			{
				dojo.empty('ERAdominationCards');
				for (let domination of faction.domination)
				{
					let node = dojo.place(this.bgagame.format_block('ERAdominationCard', {domination: domination}), 'ERAdominationCards');
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
				dojo.query(`#ERA-DP .ERAcounter-cylinder[color='${faction.color}']`).remove();
				let node = dojo.place(this.bgagame.format_block('ERAcylinder', {color: faction.color, value: faction.DP}), 'ERA-DP');
				dojo.style(node, 'position', 'absolute');
				dojo.style(node, 'left', (faction.DP * 49) + 'px');
//
				let nodes = dojo.query(`#ERA-DP .ERAcounter-cylinder`);
				let index = {};
				for (let node of nodes)
				{
					let DP = dojo.getAttr(node, 'value');
					if (!(DP in index)) index[DP] = 0;
					dojo.style(node, 'transform', `scale(.75) translate(+${index[DP] * node.clientWidth / 10}px, -${index[DP] * node.clientHeight / 5}px) `);
					dojo.style(node, 'z-index', index[DP] + 100);
					index[DP]++;
				}
			}
//
			if ('population' in faction)
			{
				dojo.query(`.ERApopulationTrack .ERAcounter-cylinder[color='${faction.color}']`).remove();
				let node = dojo.place(this.bgagame.format_block('ERAcylinder', {color: faction.color, value: faction.population}), 'ERApopulationTrack-FF3333');
				dojo.style(node, 'position', 'absolute');
				dojo.style(node, 'left', (faction.population * 49) + 'px');
//
				let nodes = dojo.query(`.ERApopulationTrack .ERAcounter-cylinder`);
				let index = {};
				for (let node of nodes)
				{
					let population = dojo.getAttr(node, 'value');
					if (!(population in index)) index[population] = 0;
					dojo.style(node, 'transform', `scale(.75) translate(+${index[population] * node.clientWidth / 10}px, -${index[population] * node.clientHeight / 5}px) `);
					dojo.style(node, 'z-index', index[population] + 100);
					index[population]++;
				}
			}
//
			for (let technology of ['Military', 'Spirituality', 'Propulsion', 'Robotics', 'Genetics'])
			{
				if (technology in faction)
				{
					dojo.query(`.ERAtechTrack .ERAcounter-cube[color='${faction.color}'][technology='${technology}']`).remove();
					let node = dojo.place(this.bgagame.format_block('ERAcube', {color: faction.color, technology: technology, level: faction[technology]}), 'ERAtechTrack-FF3333');
					dojo.style(node, 'position', 'absolute');
					dojo.style(node, 'top', (['Military', 'Spirituality', 'Propulsion', 'Robotics', 'Genetics'].indexOf(technology) * 93 + 60) + 'px');
					dojo.style(node, 'left', (faction[technology] * 60 + 0) + 'px');

					let nodes = dojo.query(`.ERAtechTrack .ERAcounter-cube`);
					let index = {};
					for (let node of nodes)
					{
						let technologyLevel = dojo.getAttr(node, 'technology') + dojo.getAttr(node, 'level');
						if (!(technologyLevel in index)) index[technologyLevel] = 0;
						dojo.style(node, 'transform', `scale(.75) translate(+${index[technologyLevel] * node.clientWidth / 10}px, -${index[technologyLevel] * node.clientHeight / 5}px) `);
						dojo.style(node, 'z-index', index[technologyLevel] + 100);
						index[technologyLevel]++;
					}
				}
			}
//			if ('order' in faction) dojo.setAttr(`ERAorder-${faction.color}`, 'order', faction.order);
		}
	}
	);
});
